# QA-03 Token Edge-Case Verification Evidence

Task: `TASK-2026-02028 / QA-03`
Project: `PROJ-0130 abit_ai_website`
Evidence captured: `2026-06-22 00:23 Asia/Dubai`

## Objective

Provide durable verification evidence for expired, reused, invalid, already-used, already-verified, and missing token edge cases in the abit.ai auth verification and password reset flows.

## Implementation Commit

Initial implementation commit:

```text
f9056a9ad216972318cc3223fbc88732746e5b08 TASK-2026-02028: [QA-03] Test verification and password reset edge cases
```

Changed files in that implementation commit:

```text
A tests/auth-token-edge-cases.php
M wp-content/themes/astra/functions.php
M wp-content/themes/astra/template-auth.php
```

## Validation Commands

The following commands passed locally with `C:\xampp\php\php.exe`:

```text
C:\xampp\php\php.exe tests\auth-token-edge-cases.php
Auth token edge-case checks passed.

C:\xampp\php\php.exe tests\auth-test-plan-coverage.php
Auth test-plan coverage checks passed.

C:\xampp\php\php.exe tests\auth-e2e-coverage.php
Auth E2E coverage checks passed.

C:\xampp\php\php.exe -l tests\auth-token-edge-cases.php
No syntax errors detected in tests\auth-token-edge-cases.php

C:\xampp\php\php.exe -l wp-content\themes\astra\template-auth.php
No syntax errors detected in wp-content\themes\astra\template-auth.php

C:\xampp\php\php.exe -l wp-content\themes\astra\functions.php
No syntax errors detected in wp-content\themes\astra\functions.php
```

`git diff --check` passed with no output.

## Live Local HTTP Evidence

A local PHP verification server was started from the repository root:

```text
C:\xampp\php\php.exe -S 127.0.0.1:8028 -t C:\Users\Jimran\ABIT_DEV_AGENT_REPOS\ABiT-Consulting\abitai_website
```

Observed process:

```text
PID 63768
Path C:\xampp\php\php.exe
URL root http://127.0.0.1:8028
```

HTTP checks:

```text
http://127.0.0.1:8028/tests/auth-token-edge-cases.php => HTTP 200; body=Auth token edge-case checks passed.
http://127.0.0.1:8028/tests/auth-test-plan-coverage.php => HTTP 200; body=Auth test-plan coverage checks passed.
http://127.0.0.1:8028/tests/auth-e2e-coverage.php => HTTP 200; body=Auth E2E coverage checks passed.
```

## Scope Note

QA-03 is a test-verification task, not a deployment task. No production deployment was performed. The objective evidence is the local implementation commit, committed changed-file list, CLI test output, and live local HTTP responses above.
