#!/usr/bin/env python3
# /// script
# requires-python = ">=3.9"
# dependencies = []
# ///
#
# --- How to run ---
# 1. Produce local layout text from a lab PDF outside this repo:
#    pdftotext -layout /path/to/lab.pdf /tmp/cma-layout.txt
# 2. Run the dev-only lab:
#    python3 tools/parser_lab/cma_layout_lab.py --layout-text /tmp/cma-layout.txt
# 3. Keep output sanitized. Do not commit real layout text or extracted values.
# ------------------
#
# --- Heuristic vs. production parser ---
# This lab uses its own header-matching rule (word-boundary regex) and is not
# kept in lockstep with the PHP CMA-layout parser
# (app/Domain/Intake/ExtractCmaLayoutBiomarkerCandidates.php), which matches
# headers as a case-insensitive substring. The two can disagree on whether a
# given layout has a detectable header. Treat a lab result as a quick signal
# of whether a PDF is plausibly CMA-shaped, not as a guarantee that the real
# parser will or will not extract it. It writes nothing to the database, so a
# false reading here costs you a wasted check, never a wrong stored value.
# ------------------

from __future__ import annotations

import argparse
import json
import re
from dataclasses import dataclass
from enum import Enum
from pathlib import Path
from typing import Sequence, TypedDict


PRIVACY_NOTE = (
    "sanitized: no extracted marker names, values, units, or reference text"
)

_HEADER_NAME_RE = re.compile(r"\b(analyse|analysis)\b", re.IGNORECASE)
_HEADER_UNIT_RE = re.compile(r"\b(eenheid|unit)\b", re.IGNORECASE)
_HEADER_REFERENCE_RE = re.compile(r"\b(referentie|reference)\b", re.IGNORECASE)
_NUMBER_RE = re.compile(r"[-+]?\d+(?:[,.]\d+)?")
_RANGE_REFERENCE_RE = re.compile(
    r"[-+]?\d+(?:[,.]\d+)?\s*-\s*[-+]?\d+(?:[,.]\d+)?",
)
_ONE_SIDED_REFERENCE_RE = re.compile(
    r"(?:<=|>=|<|>)\s*[-+]?\d+(?:[,.]\d+)?",
)
_UNIT_HINT_RE = re.compile(r"[A-Za-z][A-Za-z0-9/%.,^ -]*")


class ReferenceKind(str, Enum):
    RANGE = "range"
    ONE_SIDED = "one_sided"
    NONE = "none"


class RowPayload(TypedDict):
    line_number: int
    name_chars: int
    value_present: bool
    unit_present: bool
    reference_kind: str
    confidence_hint: float | None


class ReportPayload(TypedDict):
    privacy_note: str
    header_found: bool
    header_line: int | None
    value_start: int | None
    unit_start: int | None
    reference_start: int | None
    candidate_count: int
    range_reference_count: int
    one_sided_reference_count: int
    missing_unit_count: int
    non_candidate_line_count: int
    rows: list[RowPayload]


@dataclass(frozen=True)
class CmaColumns:
    header_index: int
    value_start: int
    unit_start: int
    reference_start: int


@dataclass(frozen=True)
class CmaRowSummary:
    line_number: int
    name_chars: int
    value_present: bool
    unit_present: bool
    reference_kind: ReferenceKind

    def to_payload(self) -> RowPayload:
        return {
            "line_number": self.line_number,
            "name_chars": self.name_chars,
            "value_present": self.value_present,
            "unit_present": self.unit_present,
            "reference_kind": self.reference_kind.value,
            "confidence_hint": confidence_hint(self.reference_kind),
        }


@dataclass(frozen=True)
class CmaLabReport:
    columns: CmaColumns | None
    rows: tuple[CmaRowSummary, ...]
    non_candidate_line_count: int

    def to_payload(self) -> ReportPayload:
        columns = self.columns

        return {
            "privacy_note": PRIVACY_NOTE,
            "header_found": columns is not None,
            "header_line": None if columns is None else columns.header_index + 1,
            "value_start": None if columns is None else columns.value_start,
            "unit_start": None if columns is None else columns.unit_start,
            "reference_start": (
                None if columns is None else columns.reference_start
            ),
            "candidate_count": len(self.rows),
            "range_reference_count": count_reference_kind(
                self.rows,
                ReferenceKind.RANGE,
            ),
            "one_sided_reference_count": count_reference_kind(
                self.rows,
                ReferenceKind.ONE_SIDED,
            ),
            "missing_unit_count": sum(
                1 for row in self.rows if not row.unit_present
            ),
            "non_candidate_line_count": self.non_candidate_line_count,
            "rows": [row.to_payload() for row in self.rows],
        }


def analyze_layout_text(layout_text: str) -> CmaLabReport:
    lines = layout_text.splitlines()
    columns = detect_columns(lines)

    if columns is None:
        return CmaLabReport(
            columns=None,
            rows=(),
            non_candidate_line_count=0,
        )

    rows: list[CmaRowSummary] = []
    non_candidate_line_count = 0

    for index, line in enumerate(lines[columns.header_index + 1 :], start=2):
        if not line.strip():
            continue

        summary = summarize_line(index, line, columns)
        if summary is None:
            non_candidate_line_count += 1
            continue

        rows.append(summary)

    return CmaLabReport(
        columns=columns,
        rows=tuple(rows),
        non_candidate_line_count=non_candidate_line_count,
    )


def detect_columns(lines: Sequence[str]) -> CmaColumns | None:
    for index, line in enumerate(lines):
        name_start = regex_start(_HEADER_NAME_RE, line)
        unit_start = regex_start(_HEADER_UNIT_RE, line)
        reference_start = regex_start(_HEADER_REFERENCE_RE, line)

        if not ordered_columns(name_start, unit_start, reference_start):
            continue

        value_start = detect_value_start(lines[index + 1 :], unit_start)
        if value_start is None:
            continue

        return CmaColumns(
            header_index=index,
            value_start=value_start,
            unit_start=unit_start,
            reference_start=reference_start,
        )

    return None


def summarize_line(
    line_number: int,
    line: str,
    columns: CmaColumns,
) -> CmaRowSummary | None:
    name = cell(line, 0, columns.value_start)
    value = cell(line, columns.value_start, columns.unit_start)
    unit = cell(line, columns.unit_start, columns.reference_start)
    reference = line[columns.reference_start :].strip()

    if not name or not has_numeric_value(value):
        return None

    reference_kind = classify_reference(reference)
    if reference_kind is ReferenceKind.NONE:
        return None

    return CmaRowSummary(
        line_number=line_number,
        name_chars=len(clean_marker_name(name)),
        value_present=True,
        unit_present=looks_like_unit(unit),
        reference_kind=reference_kind,
    )


def detect_value_start(lines: Sequence[str], unit_start: int) -> int | None:
    for line in lines[:40]:
        before_unit = line[:unit_start]
        match = last_numeric_match(before_unit)
        if match is not None:
            return match.start()

    return None


def regex_start(pattern: re.Pattern[str], text: str) -> int | None:
    match = pattern.search(text)
    if match is None:
        return None

    return match.start()


def ordered_columns(
    name_start: int | None,
    unit_start: int | None,
    reference_start: int | None,
) -> bool:
    if name_start is None:
        return False
    if unit_start is None:
        return False
    if reference_start is None:
        return False

    return name_start < unit_start < reference_start


def last_numeric_match(text: str) -> re.Match[str] | None:
    matches = list(_NUMBER_RE.finditer(text))
    if not matches:
        return None

    return matches[-1]


def cell(line: str, start: int, end: int) -> str:
    if start >= len(line):
        return ""

    return line[start:end].strip()


def clean_marker_name(name: str) -> str:
    return name.removesuffix("deg").strip()


def has_numeric_value(value: str) -> bool:
    return _NUMBER_RE.search(value) is not None


def looks_like_unit(unit: str) -> bool:
    return bool(unit.strip() and _UNIT_HINT_RE.search(unit))


def classify_reference(reference: str) -> ReferenceKind:
    if _RANGE_REFERENCE_RE.search(reference):
        return ReferenceKind.RANGE
    if _ONE_SIDED_REFERENCE_RE.search(reference):
        return ReferenceKind.ONE_SIDED

    return ReferenceKind.NONE


def confidence_hint(reference_kind: ReferenceKind) -> float | None:
    if reference_kind is ReferenceKind.RANGE:
        return 0.85
    if reference_kind is ReferenceKind.ONE_SIDED:
        return 0.82

    return None


def count_reference_kind(
    rows: Sequence[CmaRowSummary],
    reference_kind: ReferenceKind,
) -> int:
    return sum(1 for row in rows if row.reference_kind is reference_kind)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description=(
            "Inspect CMA layout text locally and emit sanitized extraction "
            "metrics only."
        ),
    )
    parser.add_argument(
        "--layout-text",
        required=True,
        type=Path,
        help="Path to local text produced by pdftotext -layout.",
    )

    return parser


def main(argv: Sequence[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    if not args.layout_text.is_file():
        parser.error(f"layout text file does not exist: {args.layout_text}")

    layout_text = args.layout_text.read_text(encoding="utf-8")
    report = analyze_layout_text(layout_text)

    print(json.dumps(report.to_payload(), indent=2, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
