# QA-05 Admin UAT Report

Task: `TASK-2026-02030 / QA-05`
Project: `PROJ-0130 abit_ai_website`
Evidence captured: `2026-06-22 01:46 Asia/Dubai`
Scope: Internal admin signup review implemented in `wp-content/mu-plugins/abit-saas-auth.php`.
Source: `PROJ-0130_abit_saas_auth_task_tree.md`

## Summary

Result: Pass with environment limitation.

The repository contains the admin UAT surface needed for the ERP acceptance test: an authorized admin can open the signup queue, inspect submitted identity/profile/ERP context, make an approval or hold decision, populate handoff fields, and persist audit evidence. This local snapshot does not include a runnable `wp-config.php` and database, so this pass is code-level UAT verification plus static guard coverage rather than a live browser/database execution.

## Acceptance Test

ERP acceptance test: Admin can process a new signup from ready-for-review to approved/held with audit evidence.

Validated implementation path:

1. A verified/onboarded signup enters `pending_admin_review`.
2. Admin opens `Tools > ABiT Signup Review`, backed by `render_signup_review_page()`.
3. Admin filters the queue by `pending_admin_review`, country, ERP module, or verification state.
4. Admin opens a request detail from the queue.
5. Detail view exposes signup profile, company profile, module interests, risk indicators, Consent Evidence, Provisioning Readiness, Handoff notes, and Audit Trail panels.
6. Admin submits `abit_saas_auth_admin_decision` with decision `approve` or `hold`, required reason, owner/queue/priority, next action, follow-up date, source campaign, notes, and optional provisioning readiness.
7. `process_admin_decision()` updates the request to `approved_for_mvp_access` or `on_hold`.
8. The same handler writes mirrored user metadata for status and handoff state.
9. The handler records `auth_admin_qualification_decision` with previous status, new status, decision, reason, owner, handoff, queue status, and readiness evidence.

## Admin Queue Review

Status: Pass.

The admin menu registers `ABiT Signup Review` under Tools with `list_users` capability. Queue rows include request identity, masked email, company, verification state, industry, company size, country, ERP module summary, risk summary, handoff summary, and review status. Queue filters support:

| Filter | UAT expectation | Implementation evidence |
| --- | --- | --- |
| Review status | Admin can isolate ready-for-review requests. | `review_status` filter and `pending_admin_review` option. |
| Country | Admin can route by market or region. | `country_region` filter from existing request rows. |
| ERP module | Admin can route by ERP interest. | `erp_module` filter over stored module interests. |
| Verification | Admin can distinguish verified and unverified records. | `verification_state` supports `verified` and `unverified`. |

## Profile Review

Status: Pass.

The request detail page exposes the fields required for internal admin review:

| Review area | Visible fields |
| --- | --- |
| Signup data | Request ID, status, queue status, owner, handoff team, priority, next action, follow-up date, campaign, last decision, reason, reviewer, timestamps, full name, business email, verification, WordPress user. |
| Company profile | Company, company ID, country/region, company status, company size, industry, role, persona. |
| ERP needs | ERP modules, onboarding templates, qualification tags, current system, timeline, primary workflow, notes. |
| Risk indicators | Risk level, duplicate company/domain/shared-IP indicators, suspicious module/context flags, failed login count. |
| Consent Evidence | Consent audit ID, accepted timestamp, terms/privacy/consent versions, locale, capture source, redacted email/IP/user-agent hashes, retention rule. |
| Provisioning Readiness | Eligibility, missing requirements, provisioning request, readiness override/reason, workspace summary, workspace slug override. |

Sensitive data is intentionally withheld: raw passwords, raw verification/reset tokens, cookies, sessions, raw token hashes, and raw IP/user-agent values are not displayed.

## Decision UAT

Status: Pass.

The admin decision form supports the required decision paths:

| Decision | Expected status | UAT result |
| --- | --- | --- |
| `approve` | `approved_for_mvp_access` | Covered by status transition map and audit event payload. |
| `hold` | `on_hold` | Covered by status transition map and handoff queue status `held`. |
| `reject` | `rejected` | Available for broader admin workflow, though not required by this acceptance test. |
| `request_info` | `more_information_requested` | Available for broader admin workflow. |
| `assign_owner` | Existing status retained | Available for queue triage without changing review status. |
| `update_provisioning_readiness` | Existing status retained | Available for readiness updates without changing review status. |

Decision submission requires capability, nonce, a valid access request, a valid decision, a reason between 10 and 1000 characters, valid owner, valid handoff team, valid priority, safe next action, valid follow-up date, safe campaign text, safe handoff notes, and valid readiness value. Excessive admin review attempts are rate-limited.

## Handoff Fields

Status: Pass.

The admin form and persistence layer cover:

| Field | Validation / behavior |
| --- | --- |
| Admin owner | Existing WordPress user or no owner. |
| Handoff team | `sales`, `support`, or unassigned. |
| Priority | `low`, `normal`, `high`, or `urgent`. |
| Next action | Text, maximum 180 characters. |
| Follow-up date | Valid `YYYY-MM-DD` date. |
| Source campaign | Text, maximum 120 characters. |
| Handoff notes | Textarea, maximum 2000 characters. |
| Queue status | Derived as `completed`, `held`, `follow_up_due`, `assigned`, or `unassigned`. |

For approved or rejected decisions the queue status becomes `completed`; for held decisions it becomes `held`; for assigned requests it becomes `assigned`; for overdue follow-up dates it becomes `follow_up_due`.

## Audit Evidence

Status: Pass.

Every saved admin decision records the `auth_admin_qualification_decision` audit event with:

| Evidence field | Included |
| --- | --- |
| Actor | `actor_user_id` and `actor_type = admin`. |
| Entity | `entity_type = access_request`, `entity_id`, `access_request_id`, `company_id`. |
| Decision | Decision key and required reason. |
| Status change | Previous review status and new review status. |
| Handoff | Owner, previous owner, team, previous team, priority, previous priority, next action, follow-up date, queue status, previous queue status. |
| Readiness | Current and previous provisioning readiness status. |

The detail page also includes Consent Evidence, email delivery events, provisioning request context, workspace state, signup notes, Handoff notes, and Audit Trail context so an internal reviewer can reconstruct why the decision was made.

## Residual Risk

Live admin UAT should still be repeated in a provisioned WordPress QA environment with database fixtures:

- Create a unique signup and complete email verification/onboarding.
- Confirm the request appears under `pending_admin_review`.
- Approve one fixture and confirm next login reaches the approved product gate.
- Hold one fixture and confirm it remains blocked with admin-visible handoff evidence.
- Confirm a new `auth_admin_qualification_decision` audit event exists for each decision.

## Validation Commands

Run locally with `C:\xampp\php\php.exe`:

```text
C:\xampp\php\php.exe tests\admin-uat.php
QA-05 admin UAT checks passed.

C:\xampp\php\php.exe -l tests\admin-uat.php
No syntax errors detected in tests\admin-uat.php

C:\xampp\php\php.exe -l wp-content\mu-plugins\abit-saas-auth.php
No syntax errors detected in wp-content\mu-plugins\abit-saas-auth.php
```
