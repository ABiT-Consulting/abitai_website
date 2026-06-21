# Auth Test Plan

Task: TASK-2026-02026 / QA-01
Project: PROJ-0130 abit_ai_website
Status: Draft for QA and UAT handoff

## Objective

Define the launch QA test plan for the abit.ai SaaS authentication MVP. The plan covers signup, login, email verification, resend, password reset, onboarding, admin review, accessibility, and security so each product acceptance criterion has at least one executable manual, API, or automated test.

## Source Context

- `docs/saas-auth-mvp-scope.md`
- `docs/auth-user-flow-map.md`
- `docs/backend-auth-api-contract.md`
- `docs/signup-multi-step-flow-decision-record.md`
- `docs/signup-company-profile-field-dictionary.md`
- `docs/signup-bot-protection-risk-rules.md`
- `docs/email-verification-resend-wireframes.md`
- `docs/forgot-reset-password-wireframes.md`
- `docs/auth-onboarding-ux-copy.md`
- `docs/legal-consent-privacy-capture-requirements.md`
- `docs/auth-analytics-funnel-event-taxonomy.md`
- `docs/auth-threat-model-security-review.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Test Approach

Run this plan across three layers:

| Layer | Purpose | Evidence |
| --- | --- | --- |
| API and integration | Validate backend state transitions, routing fields, rate limits, tokens, consent, and audit evidence. | API responses, database records, email test inbox, audit logs. |
| End-to-end UI | Validate user journeys, route guards, copy, responsive layout, and recovery paths. | Browser screenshots, screen recordings, network traces, console logs. |
| Security and accessibility | Validate non-disclosure, product-access gates, keyboard/screen-reader behavior, and WCAG-oriented checks. | Axe or equivalent report, manual keyboard notes, security checklist results. |

Use isolated users, deterministic test email aliases, and a controlled mail capture service. Do not reuse production customer accounts or live customer emails.

## Signup Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-SIGNUP-001 | E2E/API | Valid public access request creates pending verification state. | Signup entry point is available; mail capture is configured. | Submit Account, Company, and ERP needs fields with valid business data and accepted current terms/privacy notices. | Request is accepted; access request exists with `pending_email_verification`; verification email is sent; no product access CTA appears. |
| QA-AUTH-SIGNUP-002 | E2E | Signup is grouped into Account, Company, and ERP needs steps. | Visitor is on signup start. | Inspect step titles, progress behavior, and field grouping across desktop and mobile. | Signup is not one long ungrouped form; required fields are assigned to the approved steps. |
| QA-AUTH-SIGNUP-003 | API/UI | Registration validation rejects malformed or incomplete required fields. | Visitor is unauthenticated. | Submit invalid full name, malformed email, weak password, missing company fields, invalid use case, and unchecked consent. | API returns `422 validation_failed`; UI shows inline errors from approved copy; password is never logged or echoed. |
| QA-AUTH-SIGNUP-004 | API/UI | Duplicate or existing email receives a neutral response. | A user or pending request already exists for the email. | Submit signup with the same email. | Response is the same neutral accepted message as a new eligible request; UI does not disclose account existence. |
| QA-AUTH-SIGNUP-005 | API/Security | Signup rate limit and bot-risk controls activate. | Test harness can repeat requests from the same hashed identifier/IP context. | Exceed signup attempt threshold and submit obvious bot-risk payloads or honeypot values. | Excessive attempts return `429` or risk-hold behavior per policy; no unlimited request or token creation occurs; audit/risk evidence is written. |

## Login Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-LOGIN-001 | API/E2E | Unverified user login routes to verification gate. | User exists with valid credentials and `pending_email_verification`. | Log in with valid credentials. | Response is authenticated but route is `verify_email`; UI opens `/auth/verify`; product access remains blocked. |
| QA-AUTH-LOGIN-002 | API/E2E | Onboarding-required user login routes to onboarding. | User is verified with `review_status = onboarding_required`. | Log in with valid credentials. | Response route is `onboarding`; UI opens onboarding gate; app dashboard is blocked. |
| QA-AUTH-LOGIN-003 | API/E2E | Review-pending user login routes to review-pending state. | User completed onboarding with `pending_admin_review`. | Log in with valid credentials. | Response route is `review_pending`; UI shows review-pending copy; product access remains blocked. |
| QA-AUTH-LOGIN-004 | API/E2E | Approved user login reaches allowed app or approved landing. | User has `approved_for_mvp_access` and account is not locked. | Log in with valid credentials, then call `/api/auth/me`. | Login route is `app`; `/api/auth/me` gate has `product_access: true`; session cookie is issued with hardened attributes. |
| QA-AUTH-LOGIN-005 | API/UI | Rejected, more-information, and locked users route to safe blocked states. | Test users exist for `rejected`, `more_information_requested`, and locked states. | Log in with each account. | Rejected remains blocked; more-information opens editable onboarding context; locked returns locked/support state without product access. |
| QA-AUTH-LOGIN-006 | API/Security | Invalid credentials and excessive attempts do not disclose account existence. | Known and unknown email addresses are available. | Submit bad passwords for known and unknown emails; exceed failed-login threshold for a known account. | Invalid responses use generic copy; account existence is not revealed; lockout/rate limit returns policy response and audit evidence. |
| QA-AUTH-LOGIN-007 | API | Session restore and expiry route correctly. | One active session and one expired/cleared session are available. | Call `/api/auth/me` with active cookies, then without valid cookies. | Active response returns gate state and next path; invalid session returns `401 not_authenticated` and UI routes to sign-in. |

## Verification Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-VERIFY-001 | API/E2E | Valid verification token is consumed once. | User is `pending_email_verification` with a fresh token. | Open verification link. | Email is marked verified; token is consumed; status advances to `onboarding_required`; user can continue only to onboarding. |
| QA-AUTH-VERIFY-002 | API/UI | Expired verification link shows recovery path. | Expired verification token exists. | Open expired link. | UI shows expired-link state with resend action; email is not verified; status remains unchanged. |
| QA-AUTH-VERIFY-003 | API/UI | Invalid, malformed, mismatched, or already consumed verification token is generic. | Invalid and consumed token links are available. | Open each link. | UI shows invalid-link copy without token details; request status is unchanged; resend or sign-in recovery is available. |
| QA-AUTH-VERIFY-004 | UI/API | Already verified link routes by current status. | User email is already verified. | Open an old verification link or already-verified link. | UI shows already-verified state and routes by fresh status check; no product access appears unless status is approved. |
| QA-AUTH-VERIFY-005 | E2E | Verification success has accessible focus and copy. | Valid verification link exists. | Open link with keyboard and screen reader enabled. | Focus moves to success summary; title/action match approved copy; no layout shift blocks the primary action. |

## Resend Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-RESEND-001 | API/UI | Eligible unverified user can resend verification. | User is `pending_email_verification`. | Click resend or call `POST /api/auth/resend-verification`. | Response is accepted; a new single-use token is created; previous unconsumed token is superseded; confirmation copy is shown. |
| QA-AUTH-RESEND-002 | API/UI | Unknown, already verified, rejected, locked, or otherwise ineligible email receives neutral response. | Emails for each state are available. | Submit resend request for each email. | Response remains generic and non-disclosing; no unauthorized token is created. |
| QA-AUTH-RESEND-003 | API/UI | Resend cooldown and hourly limits work. | Eligible user exists. | Request resend repeatedly within cooldown and beyond hourly threshold. | Rate-limited response returns `429` with `retry_after`/`Retry-After`; UI shows cooldown-safe copy and keeps layout stable. |
| QA-AUTH-RESEND-004 | API/Security | Admin resend preserves pending-verification state. | Admin reviewer is authorized; request is pending verification. | Trigger admin resend. | Verification email is sent with `admin_resend` reason; status remains `pending_email_verification` until token consumption. |

## Password Reset Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-RESET-001 | API/UI | Reset request for eligible account shows neutral confirmation and sends email. | Verified eligible account exists. | Submit forgot-password request. | UI shows neutral confirmation; reset email is sent with single-use expiring token; account existence is not confirmed in the response. |
| QA-AUTH-RESET-002 | API/UI | Reset request for unknown, unverified, locked, rejected, or held account is non-disclosing. | Test emails cover each condition. | Submit forgot-password request for each email. | Same neutral confirmation appears; no account-specific disclosure is made. |
| QA-AUTH-RESET-003 | API/E2E | Valid reset token allows password update and later login. | User has valid reset token. | Open reset link, set valid matching password, then log in with new password. | Token is consumed; password hash changes; old password fails; new password login routes by current review status. |
| QA-AUTH-RESET-004 | API/UI | Expired reset token cannot change password. | Expired reset token exists. | Open link and try to continue. | UI shows expired reset-link state; password is unchanged; user can request a new reset email. |
| QA-AUTH-RESET-005 | API/UI | Invalid or consumed reset token stays generic. | Invalid and consumed token links exist. | Open each link. | UI shows invalid-link state; no password fields are shown; no token internals are disclosed. |
| QA-AUTH-RESET-006 | API/UI | Password validation protects reset submission. | Valid reset token exists. | Submit weak password, mismatched confirmation, missing field, and token-expired-during-submit cases. | Inline errors are shown; password fields clear when appropriate; password changes only on valid token plus valid password. |
| QA-AUTH-RESET-007 | API/Security | Reset success does not bypass normal auth routing. | User completes password reset. | Click success primary action and sign in. | Success only returns to sign-in; product access is determined by login and `/api/auth/me`, not by reset completion. |

## Onboarding Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-ONBOARD-001 | E2E/API | Verified user completes required onboarding and enters admin review. | User is `onboarding_required`. | Submit role, company size, industry, primary workflow, ERP module interest, country/company context, and current consent. | Required fields validate; consent audit record is created; status advances to `pending_admin_review`; review-pending screen appears. |
| QA-AUTH-ONBOARD-002 | UI/API | Missing onboarding fields or consent block submission. | User is `onboarding_required`. | Submit with missing company size, role, industry, primary workflow, ERP module interest, or unchecked consent. | UI shows inline errors; request does not enter admin review; consent is not falsely recorded. |
| QA-AUTH-ONBOARD-003 | E2E/API | More-information user can revise requested onboarding context. | Admin has set `more_information_requested` with a message. | Log in, review admin request, edit allowed fields, and resubmit. | User sees editable onboarding context; status returns to `pending_admin_review`; admin-visible updated context is stored. |
| QA-AUTH-ONBOARD-004 | API/UI | Onboarding and provisioning gates block premature product access. | Users exist in unverified, onboarding, and review-pending states. | Attempt dashboard/provisioning routes and call `/api/provisioning/request`. | Requests fail or route to current gate; missing requirements include relevant blockers; product access remains false. |
| QA-AUTH-ONBOARD-005 | API/Analytics | Onboarding completion emits required analytics without sensitive free text. | Analytics capture is enabled. | Complete onboarding with optional notes/use case details. | `auth_onboarding_completed` is emitted after successful write; raw passwords, tokens, IPs, user agents, and free-text notes are not sent. |

## Admin Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-ADMIN-001 | E2E/API | Authorized admin can view request queue by status. | Admin account has review permission; requests exist in all statuses. | Open admin review queue and filter by each status. | Queue shows relevant requests; unauthorized users cannot access it. |
| QA-AUTH-ADMIN-002 | E2E/API | Admin detail page shows submitted identity, verification, company, ERP needs, consent, and persona signals. | Request has completed onboarding. | Open request detail. | Required review evidence is visible; raw secrets, token values, and raw IP/user-agent values are not displayed. |
| QA-AUTH-ADMIN-003 | API/E2E | Admin approval grants next-login product access. | Request is `pending_admin_review`. | Approve with required reason/context, then log in as user. | Status becomes `approved_for_mvp_access`; audit event is recorded; next login can reach approved landing/app shell. |
| QA-AUTH-ADMIN-004 | API/E2E | Admin rejection records reason and blocks product access. | Request is `pending_admin_review`. | Reject with required reason, then log in as user. | Status becomes `rejected`; reason is admin-visible; user sees safe rejection state without product access. |
| QA-AUTH-ADMIN-005 | API/E2E | Admin request-more-information returns user to editable onboarding context. | Request is `pending_admin_review`. | Request more information with a clear message, then log in as user. | Status becomes `more_information_requested`; user sees admin message and can resubmit allowed fields. |
| QA-AUTH-ADMIN-006 | API/Security | Admin-sensitive actions require authorization, nonce, and rate-limit controls. | Admin and non-admin sessions are available. | Attempt decisions and workspace slug validation with missing nonce, bad nonce, unauthorized user, and excessive requests. | Missing/bad nonce returns `403`; unauthorized user is denied; excessive attempts are throttled and audited. |

## Accessibility Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-A11Y-001 | Manual/Automated | Auth forms are keyboard operable. | Signup, login, verification, reset, onboarding, and admin screens are available. | Navigate each flow using keyboard only. | Focus order is logical; all controls are reachable; no keyboard trap exists; visible focus meets design requirements. |
| QA-AUTH-A11Y-002 | Automated/Manual | Form fields and errors are screen-reader friendly. | Test pages are loaded. | Run axe or equivalent and inspect labels, descriptions, error association, and status regions. | Inputs have accessible names; errors are associated to fields; status changes use appropriate live/focus behavior. |
| QA-AUTH-A11Y-003 | Manual | Responsive auth UI remains usable at mobile, tablet, and desktop widths. | Browser viewport can be resized. | Exercise signup, login, verification, reset, onboarding, and admin screens at common widths. | Text does not overlap or truncate critical actions; controls remain targetable; action layout stays stable while loading. |
| QA-AUTH-A11Y-004 | Manual/Automated | Color, contrast, and reduced-motion preferences are respected. | Test environment can emulate color contrast and reduced motion. | Inspect all error, success, warning, disabled, and focus states. | State is not communicated by color alone; contrast is acceptable; reduced motion avoids disruptive animation. |

## Security Test Cases

| ID | Type | Scenario | Preconditions | Steps | Expected result |
| --- | --- | --- | --- | --- | --- |
| QA-AUTH-SEC-001 | API | Product access is gated by status and `/api/auth/me`. | Users exist for every canonical status. | Attempt dashboard, onboarding, provisioning, and admin paths for each status. | Only `approved_for_mvp_access` receives product access; all other statuses route to the correct gate. |
| QA-AUTH-SEC-002 | API | Tokens are single-use, expiring, and not exposed. | Verification and reset tokens exist. | Use tokens twice, after expiry, and with malformed values; inspect API responses/logs. | Tokens are consumed once; expired/invalid attempts fail generically; raw tokens are not returned or logged. |
| QA-AUTH-SEC-003 | API | Auth responses avoid account enumeration. | Known, unknown, unverified, rejected, and locked emails are available. | Compare signup duplicate, login failure, resend, and reset request responses. | Public responses stay generic where required; timing and message differences do not reveal account existence through normal UI/API output. |
| QA-AUTH-SEC-004 | API | Rate limits and lockouts cover protected auth surfaces. | Test harness can repeat requests. | Exceed limits for signup, login, resend, forgot password, reset password, and admin-sensitive endpoints. | `429` or lockout policy triggers; `retry_after` is provided where applicable; audit/risk records are written. |
| QA-AUTH-SEC-005 | API/Browser | Session and CSRF protections work. | Authenticated browser session exists. | Inspect cookies; call unsafe authenticated routes with missing/invalid nonce; log out and retry. | Cookies are `Secure`, `HttpOnly`, `SameSite=Lax` in HTTPS environments; nonce failures are rejected; logout revokes current session. |
| QA-AUTH-SEC-006 | API | Cross-tenant and unauthorized data access is denied. | Two users/companies/access requests exist. | Request another user's company, access request, onboarding, or provisioning context where APIs support identifiers. | Mismatched scope is denied and audited; no cross-company data leaks. |
| QA-AUTH-SEC-007 | Review | Launch security signoff has no critical or high blockers. | `docs/auth-threat-model-security-review.md` is current. | Run `php tests/auth-threat-model-signoff.php` and review open findings. | Threat-model signoff passes; critical and high findings remain zero; accepted medium/low follow-ups are tracked. |

## Acceptance Criteria Traceability

| Acceptance criterion | Test coverage |
| --- | --- |
| Public signup entry point is available from the website. | QA-AUTH-SIGNUP-001, QA-AUTH-SIGNUP-002 |
| Signup captures name, business email, company name, country or region, and intended use case. | QA-AUTH-SIGNUP-001, QA-AUTH-SIGNUP-003 |
| Signup rejects malformed email addresses and obvious duplicate pending requests for the same email. | QA-AUTH-SIGNUP-003, QA-AUTH-SIGNUP-004 |
| Signup stores the request with a status of `pending_email_verification`. | QA-AUTH-SIGNUP-001 |
| Signup sends an email verification message with a single-use, expiring verification link. | QA-AUTH-SIGNUP-001, QA-AUTH-VERIFY-001, QA-AUTH-SEC-002 |
| Verified users can log in with email and password. | QA-AUTH-LOGIN-002, QA-AUTH-LOGIN-003, QA-AUTH-LOGIN-004 |
| Unverified users are blocked from product access and prompted to verify email. | QA-AUTH-LOGIN-001, QA-AUTH-ONBOARD-004, QA-AUTH-SEC-001 |
| Users pending admin review can authenticate, but only see the onboarding or review-pending state. | QA-AUTH-LOGIN-003, QA-AUTH-ONBOARD-004 |
| Failed login attempts return a generic error that does not disclose whether the email exists. | QA-AUTH-LOGIN-006, QA-AUTH-SEC-003 |
| Verification links are single-use and expire. | QA-AUTH-VERIFY-001, QA-AUTH-VERIFY-002, QA-AUTH-VERIFY-003, QA-AUTH-SEC-002 |
| Successful verification changes the request status from `pending_email_verification` to `onboarding_required`. | QA-AUTH-VERIFY-001 |
| Expired or consumed verification links offer a resend path. | QA-AUTH-VERIFY-002, QA-AUTH-VERIFY-003, QA-AUTH-RESEND-001 |
| Verification status is visible to admins reviewing access requests. | QA-AUTH-ADMIN-002 |
| Users can request a password reset by email. | QA-AUTH-RESET-001, QA-AUTH-RESET-002 |
| Reset emails use a single-use, expiring token. | QA-AUTH-RESET-001, QA-AUTH-RESET-003, QA-AUTH-RESET-004, QA-AUTH-SEC-002 |
| Reset request responses are generic and do not disclose whether the email exists. | QA-AUTH-RESET-002, QA-AUTH-SEC-003 |
| A successful reset invalidates the used token and allows the user to log in with the new password. | QA-AUTH-RESET-003, QA-AUTH-RESET-007 |
| Verified users must complete required onboarding fields before reaching the SaaS application. | QA-AUTH-ONBOARD-001, QA-AUTH-ONBOARD-002, QA-AUTH-ONBOARD-004 |
| Required onboarding fields for MVP are company size, role, primary workflow, and acceptance of current terms or privacy notices. | QA-AUTH-ONBOARD-001, QA-AUTH-ONBOARD-002 |
| Completing onboarding changes the request status to `pending_admin_review`. | QA-AUTH-ONBOARD-001 |
| Users who have completed onboarding but are not approved see a review-pending state. | QA-AUTH-LOGIN-003, QA-AUTH-ONBOARD-001 |
| Admins can view access requests by status. | QA-AUTH-ADMIN-001 |
| Admins can inspect submitted signup, verification, and onboarding details. | QA-AUTH-ADMIN-002 |
| Admins can approve, reject, or request more information. | QA-AUTH-ADMIN-003, QA-AUTH-ADMIN-004, QA-AUTH-ADMIN-005 |
| Approval changes the user status to `approved_for_mvp_access`. | QA-AUTH-ADMIN-003 |
| Rejection keeps the user out of the SaaS application and records an admin-visible reason. | QA-AUTH-ADMIN-004, QA-AUTH-SEC-001 |
| Admin review does not require a fully provisioned ERP tenant. | QA-AUTH-ADMIN-003, QA-AUTH-ONBOARD-004 |
| Dependent implementation tasks treat ERP tenant provisioning as manual, deferred, or separately scoped. | QA-AUTH-ONBOARD-004, QA-AUTH-SEC-001 |
| The MVP status model is sufficient for implementation planning and QA scenario design. | QA-AUTH-LOGIN-001, QA-AUTH-LOGIN-002, QA-AUTH-LOGIN-003, QA-AUTH-LOGIN-004, QA-AUTH-LOGIN-005, QA-AUTH-SEC-001 |
| Auth UI states meet accessibility requirements for keyboard, screen reader, responsive layout, and status messages. | QA-AUTH-A11Y-001, QA-AUTH-A11Y-002, QA-AUTH-A11Y-003, QA-AUTH-A11Y-004, QA-AUTH-VERIFY-005 |
| Auth flows meet security requirements for rate limits, non-disclosure, CSRF, sessions, tenant isolation, and launch signoff. | QA-AUTH-SIGNUP-005, QA-AUTH-LOGIN-006, QA-AUTH-ADMIN-006, QA-AUTH-SEC-001, QA-AUTH-SEC-002, QA-AUTH-SEC-003, QA-AUTH-SEC-004, QA-AUTH-SEC-005, QA-AUTH-SEC-006, QA-AUTH-SEC-007 |
| Test plan covers signup, login, verification, resend, reset, onboarding, admin, accessibility, and security. | QA-AUTH-SIGNUP-001, QA-AUTH-LOGIN-001, QA-AUTH-VERIFY-001, QA-AUTH-RESEND-001, QA-AUTH-RESET-001, QA-AUTH-ONBOARD-001, QA-AUTH-ADMIN-001, QA-AUTH-A11Y-001, QA-AUTH-SEC-001 |

## Exit Criteria

- All P0/P1 test cases pass in the target launch environment or have an owner-approved launch exception.
- No critical or high security findings remain open.
- No tested status other than `approved_for_mvp_access` can reach product access.
- Account enumeration checks pass for signup duplicate handling, login failure, resend, and password reset request.
- Accessibility checks have no blocker issues for primary auth flows.
- Evidence links or attachments are recorded for every failed test and retest.

