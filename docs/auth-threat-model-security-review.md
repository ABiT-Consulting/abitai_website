# Auth Threat Model and Security Review

Task: TASK-2026-02025 / SEC-08
Project: PROJ-0130 abit_ai_website
Review date: 2026-06-21
Status: Signed off for launch after listed medium and low follow-ups are tracked outside the launch-critical gate

## Objective

Run a launch authentication threat model and security review for the abit.ai SaaS auth MVP. The acceptance bar for this review is that no critical or high authentication/security issue remains open at launch.

## Reviewed Scope

Primary implementation reviewed:

- `wp-content/mu-plugins/abit-saas-auth.php`
- `wp-content/themes/astra/inc/abitai-company-profile-api.php`
- `docs/backend-auth-api-contract.md`
- `docs/signup-bot-protection-risk-rules.md`
- `docs/transactional-email-sender.md`
- `tests/session-token-security.php`
- `tests/tenant-isolation.php`
- `tests/consent-privacy-audit.php`

Primary auth surfaces reviewed:

- Public signup: `POST /api/auth/register`
- Public login: `POST /api/auth/login`
- Public email verification: `GET|POST /api/auth/verify`
- Public resend verification: `POST /api/auth/resend-verification`
- Authenticated logout: `POST /api/auth/logout`
- Authenticated account state: `GET /api/auth/me`
- Authenticated provisioning request: `POST /api/provisioning/request`
- Admin workspace slug validation: `POST /api/workspace/slug/validate`
- Admin qualification decision: `POST /wp-json/abit-ai/v1/admin/access-requests/{id}/qualification-decision`
- Native WordPress lost-password, reset-password, and login hooks hardened by the auth plugin

## Assets and Trust Boundaries

| Asset | Security goal | Trust boundary |
| --- | --- | --- |
| WordPress user credentials and password hashes | Prevent disclosure, guessing, weak-password acceptance, and session persistence after reset. | Browser to WordPress auth API and native WordPress auth handlers. |
| Email verification and password reset tokens | Single-use, expiring, unguessable, and not stored/logged in raw form. | Browser/email client to public verification/reset routes. |
| Auth cookies and REST nonce | Prevent theft, cross-site mutation, and long-lived sessions beyond MVP policy. | Browser cookie jar, same-site navigation, and REST/pretty API handlers. |
| Access requests, company records, workspaces, and provisioning requests | Prevent cross-tenant read/write and premature product/provisioning access. | Authenticated user session to company/access-request scoped APIs. |
| Admin review decisions | Restrict to admin-review capable users and protect against CSRF. | WordPress admin session to admin-post and REST admin routes. |
| Audit, rate-limit, consent, and email observability records | Preserve security evidence without raw secrets, raw IPs, raw user agents, or raw tokens. | Runtime auth plugin to local WordPress database/audit writer. |

## Threat Model Summary

| Threat | Current control | Residual risk |
| --- | --- | --- |
| Account enumeration through signup, resend, login, or forgot-password | Duplicate signup and unknown reset flows return generic accepted responses; invalid login copy is generic; resend returns generic accepted response for ineligible users. | Low: timing differences may still exist across database and mail paths; monitor externally if abuse appears. |
| Credential stuffing and brute-force login | Login, failed-login, forgot-password, reset-password, signup, resend, and admin-sensitive actions are rate limited by HMAC-hashed identifier/IP/user context; repeated failed known-user attempts create temporary lockouts. | Medium: local database-backed limits are per deployment and may not stop distributed attacks without edge WAF/CDN throttling. |
| Weak or reused passwords | Passwords must be 12-128 characters, include at least three character classes, reject common examples, and prefer Argon2id when available. | Low: no breached-password corpus check is implemented for MVP. |
| Token replay or token database disclosure | Verification tokens use 32 random bytes, URL-safe encoding, HMAC persistence, uniqueness, expiry, transaction locking, and consumed timestamps; resend consumes prior tokens. | Low: reset tokens use WordPress native storage and lifecycle; plugin hardens reset email/logging and session revocation but does not replace WordPress reset-token internals. |
| Session theft or fixation | Login clears existing auth cookies before setting a new cookie; cookies are forced Secure, HttpOnly, SameSite=Lax; session lifetime is bounded; logout destroys the current session token; password reset destroys all sessions. | Low: Secure cookies require production HTTPS; launch runbook must confirm TLS and proxy headers. |
| CSRF on authenticated mutations | Authenticated unsafe API routes require `X-WP-Nonce` or `_wpnonce`; admin-post decisions require `check_admin_referer`; rejections are audited. | Low: public unauthenticated signup/login/verification routes intentionally do not require nonce and rely on rate limits/bot controls. |
| Cross-origin credential abuse | Auth API CORS grants only WordPress allowed origins and credentials; disallowed origins return stable `cors_origin_denied` and are audited by origin hash. | Low: allowed-origin configuration must be reviewed in production. |
| Cross-tenant data access | `/auth/me`, provisioning, company profile, and related scope checks reject mismatched requested user/company/access-request IDs and audit sanitized denial metadata. | Low: current MVP has first-user workspace membership only; broader organization RBAC is out of scope. |
| Premature product/provisioning access | Product access and provisioning are gated by email verification, onboarding/company profile, admin approval, current consent, and capacity readiness. | Low: manual provisioning process must honor the API gate state. |
| Bot or spam signup | Signup risk scoring covers disposable/suspicious domains, per-IP velocity, suspicious user agents, honeypot fields, HMAC challenge tokens, and high-risk hold states. | Medium: no external CAPTCHA or reputation provider is wired for MVP; add if signup abuse exceeds support tolerance. |
| Secret leakage through logs/audit/email observability | Audit writer applies recursive sensitive-key redaction; rate-limit and consent records store HMACs for identifiers/IP/user-agent; email observability excludes message bodies, provider payloads, raw tokens, and reset keys. | Low: some audit event data includes submitted email for operational workflows; ensure the audit sink remains access restricted. |
| Admin privilege misuse or accidental approval | Admin review surfaces require `list_users`, nonce checks, rate limits, required decision reasons, and audit events. | Medium: `list_users` is broader than a dedicated auth-review capability; acceptable for MVP but should be narrowed before larger rollout. |

## Security Review Findings

### Critical

No open critical findings.

### High

No open high findings.

### Medium

| ID | Finding | Status | Launch decision |
| --- | --- | --- | --- |
| SEC-08-M1 | Admin review access uses the broad WordPress `list_users` capability instead of a dedicated `abit_saas_auth_review` capability. | Open follow-up | Accepted for MVP launch because only trusted admins should have `list_users`; create a dedicated capability before broader admin delegation. |
| SEC-08-M2 | Edge-layer distributed attack controls are not represented in this repo; app rate limits are database-backed and deployment-local. | Open follow-up | Accepted for MVP launch if production enables CDN/WAF login/signup throttles and alerting before public launch. |
| SEC-08-M3 | No breached-password or zxcvbn-style strength service is enforced. | Open follow-up | Accepted for MVP launch because baseline length/complexity/common-password rejection is implemented; add stronger screening for public scale. |

### Low

| ID | Finding | Status | Launch decision |
| --- | --- | --- | --- |
| SEC-08-L1 | Production configuration must confirm HTTPS, `WP_HOME`/`WP_SITEURL`, proxy headers, and allowed CORS origins. | Open operational checklist | Not code-blocking; verify in deployment runbook. |
| SEC-08-L2 | Audit events intentionally include business email on selected operational events. | Open operational checklist | Not code-blocking; restrict audit log access and retention. |
| SEC-08-L3 | Public signup bot protection is local/rules-based. | Open operational checklist | Not code-blocking; monitor signup hold/challenge rates and add provider-backed challenge if needed. |

## Remediation List

| Priority | Item | Owner | Due |
| --- | --- | --- | --- |
| Medium | Add a dedicated auth-review/admin-review capability and map it to the intended launch admin role instead of using `list_users` directly. | Engineering | Post-MVP hardening |
| Medium | Add production edge controls for `/api/auth/register`, `/api/auth/login`, `/api/auth/resend-verification`, and WordPress login/reset paths. | Operations | Before broad public traffic |
| Medium | Evaluate breached-password screening or zxcvbn-style scoring for signup/reset password changes. | Engineering | Post-MVP hardening |
| Low | Confirm HTTPS-only deployment, reverse-proxy client IP handling, and allowed CORS origins. | Operations | Launch runbook |
| Low | Confirm auth audit log access is limited to approved operations/security users. | Operations | Launch runbook |
| Low | Add alert thresholds for auth rate-limit throttles, account lockouts, signup holds, email bounces, and verification token expiry spikes. | Operations | Launch runbook |

## Launch Signoff

SEC-08 signoff: approved for launch from an application authentication/security perspective.

Launch gate result: Pass.

Open critical findings: 0.

Open high findings: 0.

Medium and low findings listed above are accepted as non-launch-blocking follow-ups because the implemented code provides baseline controls for authentication, token lifecycle, session security, CSRF/CORS, tenant isolation, rate limiting, privacy-preserving audit evidence, and admin authorization.

## Validation Evidence

Static security tests to run from the repository root:

```bash
php tests/session-token-security.php
php tests/tenant-isolation.php
php tests/consent-privacy-audit.php
php tests/auth-threat-model-signoff.php
```

Manual review evidence:

- Reviewed endpoint registration and permission callbacks for all custom auth REST routes.
- Reviewed pretty-route bridge security handling for CORS, method enforcement, and nonce enforcement.
- Reviewed auth cookie hardening and session revocation hooks.
- Reviewed verification token creation, storage, lookup, consumption, resend, and expiry behavior.
- Reviewed rate-limit policy and event storage for signup, login, failed login, resend, forgot password, reset password, and admin-sensitive actions.
- Reviewed tenant-scope denial behavior for authenticated company/access-request APIs.
- Reviewed audit redaction and privacy-preserving consent/email observability fields.
