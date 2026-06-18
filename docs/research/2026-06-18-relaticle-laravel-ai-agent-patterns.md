# Research Run: Relaticle Laravel AI Agent Patterns

Date: 2026-06-18

Question:

- What can this Laravel AI-agent case study teach us about any future AI
  feature in the private blood values dashboard?

Decision affected:

- Whether an in-app AI agent belongs in V1.
- What safety architecture would be required if AI is ever allowed to read or
  propose changes to private health data.

Scope:

- Public Reddit thread.
- Public Relaticle GitHub repository.
- Public `packages/Chat` source structure and selected source files.
- Public Laravel AI SDK documentation.

Out of scope:

- Building an AI feature now.
- Sending private lab PDFs or health data to an AI provider.
- Medical interpretation, diagnosis, advice, supplement recommendations, or
  treatment suggestions.

## Sources

- Reddit:
  - `https://www.reddit.com/r/laravel/comments/1u7eiwc/how_i_built_an_ai_agent_into_my_opensource/`
- Relaticle:
  - `https://github.com/relaticle/relaticle`
  - `https://github.com/relaticle/relaticle/tree/main/packages/Chat`
- Laravel:
  - `https://github.com/laravel/ai`
  - `https://laravel.com/docs/13.x/ai-sdk`
  - `https://laravel.com/docs/12.x/ai`

## Observed Case Study

The Reddit post describes an open-source Laravel CRM with an in-app AI agent
built with:

- `laravel/ai`;
- Reverb streaming;
- Filament and Livewire UI;
- Horizon queued processing;
- human approval for every write;
- provider choice per conversation;
- an MCP server as a complementary technical integration.

Relaticle's repository describes itself as a self-hosted CRM with native AI
agent support, MCP tools, REST API, multi-team isolation, custom fields, and a
Laravel/Filament/Livewire stack.

## Findings

### Fact: The Agent Does Not Write Directly

The Reddit post and `packages/Chat` source show a proposal-based model:

- write tools return `pending_action`;
- proposal cards show the user the proposed operation;
- approval/rejection happens separately;
- the agent is instructed to stop after proposing a write.

Decision impact:

- If this project ever adds an AI assistant, it must not directly create,
  update, delete, confirm, export, or interpret health records.
- AI can at most produce drafts or proposals.
- Human confirmation remains the source of truth.

### Fact: Approval Safety Is A Real Subsystem

Relaticle's `PendingActionService` uses:

- allowlisted action classes and model classes;
- database transactions;
- row locks;
- pending/expired/resolved status checks;
- idempotency for retries;
- duplicate warnings;
- team scoping during execution;
- per-item approval/rejection for batches.

Decision impact:

- "Human approval" is not just a button. It needs a durable state machine.
- Any future AI proposal system for health data needs persisted proposals,
  expiry, idempotency, scoped execution, and auditability.

### Fact: Tenant/Owner Scope Must Be Rechecked At Execution Time

The post highlights that every write is scoped to the tenant the proposal was
created in. The source also resolves models by team during approval.

Decision impact:

- For this project, future proposals must store the owning `user_id` or parent
  owner context at proposal creation time.
- Approval must re-check ownership from persisted proposal data.
- Never trust ambient request/session context when applying an AI proposal.

### Fact: Stale Proposals Are Superseded

Relaticle marks pending actions as superseded when the user continues the
conversation before resolving them. The conversation store hides superseded
turns so the model does not keep acting on no-longer-visible state.

Decision impact:

- Future AI proposals for blood values must be invalidated when context changes.
- The UI must not let the user approve a proposal that belongs to stale context.
- The model must be told which proposals were superseded or resolved.

### Fact: Streaming Requires Identity And Recovery

The Reddit thread describes queued chat jobs streaming over Reverb. The author
explains that the browser is just a viewport; reloads re-subscribe to the
conversation channel, and deltas carry an invocation identity.

Decision impact:

- Streaming AI should not be tied to the browser socket.
- If AI is added later, use background jobs, conversation IDs, invocation IDs,
  persisted messages, and explicit retry/failure states.

### Fact: Custom/Dynamic Data Shapes Need Runtime Descriptions And Validation

Relaticle has tenant-defined custom fields. Its source includes schema
description and request validation for custom fields before proposals are saved.

Decision impact:

- A future health assistant would need runtime descriptions of allowed
  biomarkers, units, date fields, context-note fields, and status boundaries.
- The LLM-facing schema is not enough; server-side validation must translate and
  verify every submitted value.

### Fact: Provider Differences Matter

The Reddit post says Gemini was excluded because provider options made a
sequential-write guard unenforceable in that setup.

Decision impact:

- Do not assume every AI provider supports the same safety constraints.
- If the product later uses AI, providers must be allowlisted based on the
  required safety features, not only cost or model quality.

### Fact: Honest Failure States Build Trust

The post calls out visible retrying/failed/resume states for rate limits and
provider errors. Source code shows retry handling and stream failure events.

Decision impact:

- Hidden AI failures are unacceptable in a health-data product.
- Any later AI workflow must show when work is draft, pending, retrying, failed,
  superseded, approved, or rejected.

### Fact: MCP And In-App Agents Solve Different Problems

The Reddit author says MCP exists for technical users, while the in-app agent is
for non-technical users already inside the product and needs app-level approval,
metering, and provider control.

Decision impact:

- For this project, MCP could be a developer/research tool later.
- An in-app assistant would require product UX, permissions, approval cards,
  audit logs, and privacy review.
- They should not be treated as interchangeable.

## Transfer To This Project

Adopt as future AI principles:

- AI can draft or propose; it cannot directly mutate health records.
- User confirmation remains mandatory before structured health data changes.
- Store original source documents separately from AI/draft output.
- Persist proposals with status, owner, expiry, action data, display data, and
  result data.
- Apply proposals only through allowlisted domain actions.
- Re-check ownership at approval time.
- Make approvals idempotent.
- Supersede stale proposals when the user changes context.
- Surface retry, failure, superseded, approved, and rejected states.
- Treat provider capability differences as architecture constraints.

Reject for V1:

- runtime `laravel/ai`;
- Reverb streaming for AI;
- MCP server for private health data;
- autonomous writes;
- AI confirmation of extracted blood values;
- AI medical explanations or recommendations;
- provider bring-your-own-key flows.

## Candidate Future Health Use Cases

Allowed only after a later ADR/spec/privacy review:

- propose draft biomarker rows from a local parser result;
- summarize already-confirmed values for a consult export without advice;
- help find "which blood test had ferritin?" from confirmed records;
- propose context-note tags from user-written notes;
- prepare doctor questions from user-selected data.

Still forbidden:

- diagnosis;
- treatment suggestions;
- supplement/diet/training advice;
- changing confirmed values without explicit approval;
- silently exporting or sending private data to a provider.

## Recommendation

This source should not change V1.

It should create a future architecture boundary:

```text
No runtime AI agent in V1.
If AI is added later, it must be proposal-only,
owner-scoped, idempotent, auditable, and human-approved.
```

For the current project, this strengthens the existing principle:

```text
Human-confirmed data is trusted.
AI output is never trusted until reviewed.
```
