from __future__ import annotations

import contextlib
import importlib.util
import io
import json
import sys
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
MODULE_PATH = ROOT / "tools" / "parser_lab" / "cma_layout_lab.py"


def load_lab_module():
    spec = importlib.util.spec_from_file_location("cma_layout_lab", MODULE_PATH)
    if spec is None or spec.loader is None:
        raise RuntimeError("Could not load cma_layout_lab module spec.")

    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


def cma_line(name: str, value: str, unit: str, reference: str) -> str:
    return f"{name:<35}{value:<14}{unit:<20}{reference}"


def synthetic_cma_layout() -> str:
    return "\n".join(
        [
            f"{'Analyse':<49}{'Eenheid':<20}Referentie",
            "SYNTHETIC SECTION",
            cma_line("Marker Alpha deg", "10,1", "umol/L", "5,0 - 15,0"),
            cma_line("Marker Beta C deg", "110", "mL/min/1,73m2", ">=90"),
            cma_line("Marker Gamma", "162", "mg/dL", "<100"),
        ],
    )


class CmaLayoutLabTest(unittest.TestCase):
    def test_reports_sanitized_cma_structure_without_pdf_values(self) -> None:
        lab = load_lab_module()

        report = lab.analyze_layout_text(synthetic_cma_layout())
        payload = report.to_payload()
        encoded = json.dumps(payload, sort_keys=True)

        self.assertTrue(payload["header_found"])
        self.assertEqual(payload["candidate_count"], 3)
        self.assertEqual(payload["range_reference_count"], 1)
        self.assertEqual(payload["one_sided_reference_count"], 2)
        self.assertEqual(payload["missing_unit_count"], 0)

        sensitive_tokens = [
            "Marker Alpha",
            "Marker Beta",
            "Marker Gamma",
            "10,1",
            "110",
            "162",
            "umol/L",
            "mL/min/1,73m2",
            "mg/dL",
            "5,0",
            "15,0",
            ">=90",
            "<100",
        ]

        for token in sensitive_tokens:
            self.assertNotIn(token, encoded)

    def test_cli_outputs_sanitized_json_for_layout_file(self) -> None:
        lab = load_lab_module()

        with tempfile.TemporaryDirectory() as directory:
            layout_path = Path(directory) / "synthetic-layout.txt"
            layout_path.write_text(synthetic_cma_layout(), encoding="utf-8")

            stdout = io.StringIO()
            with contextlib.redirect_stdout(stdout):
                exit_code = lab.main(["--layout-text", str(layout_path)])

        payload = json.loads(stdout.getvalue())

        self.assertEqual(exit_code, 0)
        self.assertEqual(payload["candidate_count"], 3)
        self.assertNotIn("Marker Alpha", stdout.getvalue())
        self.assertNotIn("10,1", stdout.getvalue())

    def test_cli_reports_missing_layout_file_without_traceback(self) -> None:
        lab = load_lab_module()
        stderr = io.StringIO()

        with contextlib.redirect_stderr(stderr):
            try:
                lab.main(["--layout-text", "/tmp/cma-layout-lab-missing-input.txt"])
            except SystemExit as error:
                exit_code = error.code
            except FileNotFoundError as error:
                self.fail(f"expected argparse exit, got FileNotFoundError: {error}")
            else:
                self.fail("expected argparse exit for missing layout text")

        self.assertEqual(exit_code, 2)
        self.assertIn("does not exist", stderr.getvalue())
        self.assertNotIn("Traceback", stderr.getvalue())

    def test_reports_no_header_for_non_cma_text(self) -> None:
        lab = load_lab_module()

        report = lab.analyze_layout_text("No tabular CMA header here\nplain text only")
        payload = report.to_payload()

        self.assertFalse(payload["header_found"])
        self.assertEqual(payload["candidate_count"], 0)


if __name__ == "__main__":
    unittest.main()
