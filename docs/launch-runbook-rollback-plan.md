# Launch Runbook and Rollback Plan

Task: `TASK-2026-02033 / LCH-02`
Project: `PROJ-0130 abit_ai_website`
Milestone: `10. Launch and Post-launch Monitoring`
Status: Launch runbook prepared for deploy, verify, rollback, support escalation, and incident response.
Source: `PROJ-0130_abit_saas_auth_task_tree.md`

## Objective

Give on-call, support, and release operators a single launch procedure for the abit.ai SaaS auth MVP. The runbook covers deployment through GitHub Actions and cPanel, production smoke verification for signup, email verification, login, and password reset, rollback triggers, support escalation, and incident response.

This document does not contain secret values. Use GitHub Actions secrets, cPanel, the production WordPress configuration, and provider consoles for secret inspection or rotation.

## Roles

| Role | Owner | Launch responsibility |
| --- | --- | --- |
| Release lead | Engineering | Owns go/no-go, starts deploy, records release evidence, and calls rollback. |
| On-call engineer | Engineering | Watches deployment, PHP/runtime errors, auth audit events, and smoke-test results. |
| Support lead | Support | Monitors support inbox, customer reports, and escalation notes. |
| Operations owner | Operations | Confirms cPanel, DNS, TLS, WAF/rate-limit, transactional email, and backups. |
| Security owner | Security/Engineering | Reviews auth abuse, account enumeration, token, audit, and secret-handling incidents. |

## Pre-Launch Checklist

Complete these checks before pushing public traffic to the launch build.

| Check | Required result |
| --- | --- |
| Launch config | `docs/production-configuration-secrets-checklist.md` is complete and all required production settings are configured with secret values redacted from repository evidence. |
| Regression gate | QA-06 has no open critical or high defects. |
| Deployment secrets | `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_TOKEN`, and `CPANEL_REPO_ROOT` exist in GitHub Actions secrets. |
| Deployment branch | Confirm the deploy branch is `main` unless `CPANEL_BRANCH` intentionally overrides it. |
| Production backup | Database and deployed file backup or restore point exists and has an owner. |
| Maintenance window | Release lead has announced start time, expected duration, rollback owner, and support contact path. |
| Email provider | Sender domain, SPF, DKIM, DMARC, bounce handling, and provider dashboard access are confirmed. |
| Edge protection | CDN/WAF throttles are active for auth endpoints and WordPress login/reset paths before broad traffic. |
| Monitoring access | Release lead and on-call can view GitHub Actions, cPanel deployment logs, PHP logs, WordPress admin, auth audit events, and email observability. |
| Test accounts | Smoke-test aliases and fixture accounts are available and disposable. Do not use production customer accounts. |

## Deployment Procedure

The repository deploys through `.github/workflows/cpanel-deploy.yml` and `.cpanel.yml`.

Deployment contract:

| File | Contract |
| --- | --- |
| `.github/workflows/cpanel-deploy.yml` | `name: Deploy to cPanel` |
| `.github/workflows/cpanel-deploy.yml` | Calls `VersionControlDeployment/create` after best-effort `VersionControl/update`. |
| `.cpanel.yml` | Starts with `DEPLOYPATH="${HOME}/public_html/"` and then selects a production path if an existing `wp-config.php` is found. |

1. Confirm the release commit has passed repository validation and has been approved for launch.
2. In GitHub Actions, open the `Deploy to cPanel` workflow.
3. Trigger deployment by pushing to `main`/`master`, or run `workflow_dispatch` for a controlled manual release.
4. Confirm the workflow validates required cPanel secrets.
5. Confirm the workflow selects the intended branch:
   - `CPANEL_BRANCH` when set.
   - The pushed branch for push events.
   - `main` for manual dispatch without an override.
6. Watch the `Trigger and monitor cPanel deployment` step.
7. Confirm `VersionControlDeployment/create` returns a `deploy_id`.
8. Wait until the workflow prints `Deployment succeeded.`
9. If the workflow fails, capture the GitHub Actions run URL, the cPanel `deploy_id`, and any fetched cPanel deployment log before retrying or rolling back.

The cPanel deployment script detects the production path in this order:

| Path check | Deploy target |
| --- | --- |
| `${HOME}/public_html/abit.ai/wp-config.php` exists | `${HOME}/public_html/abit.ai/` |
| `${HOME}/abit.ai/wp-config.php` exists | `${HOME}/abit.ai/` |
| Neither exists | `${HOME}/public_html/` |

The deployment copies these repository assets:

| Asset | Behavior |
| --- | --- |
| `dist/` | Copied into the deploy path only when the directory exists. |
| `wp-content/themes/astra/functions.php` | Copied to the deployed Astra theme. |
| `wp-content/themes/astra/front-page.php` | Copied to the deployed Astra theme. |
| `web.config` | Copied to the deploy path when present. |

## Post-Deploy Technical Verification

Run these checks immediately after deployment and before public announcement.

| Area | Verification |
| --- | --- |
| HTTPS and canonical domain | `https://abit.ai` loads, HTTP redirects to HTTPS, and `www` behavior matches the approved canonical policy. |
| WordPress home/site URLs | `WP_HOME` and `WP_SITEURL` resolve to the production HTTPS domain. |
| Static assets | Front page, auth pages, CSS, and JavaScript load without 404s or mixed-content errors. |
| PHP health | PHP error display is disabled publicly; restricted PHP logs show no new fatal errors after deploy. |
| Admin access | Approved launch admin can access WordPress admin and `Tools > ABiT Signup Review`. |
| REST/CORS | Allowed production origin works; disallowed origins are rejected with the expected CORS denial behavior. |
| Rate limits | WAF/CDN and app rate-limit controls are enabled for signup, login, resend verification, forgot/reset password, and admin-sensitive actions. |
| Email observability | `Tools > ABiT Email Observability` shows sanitized delivery attempts and no raw tokens, reset keys, provider payloads, or full recipient exposure. |

## Auth Smoke Test

The ERP acceptance test requires on-call/support to validate signup, verification, login, and reset after deploy. Use a unique disposable email alias for each run, for example `launch+YYYYMMDDHHMM@abit.ai` or a monitored provider alias.

Capture screenshots, timestamps, tested email alias, GitHub Actions run URL, cPanel `deploy_id`, and any relevant sanitized audit/email observability rows.

### Signup And Verification

1. Open `https://abit.ai/auth/signup`.
2. Complete the Account step with a unique launch test email, a strong test password, and consent checked.
3. Complete the Company step with a launch test company, country/region, job title, company size, industry, and business description.
4. Complete the ERP needs step by selecting at least one ERP module.
5. Submit the form.
6. Confirm navigation to `/auth/verify?state=sent` or equivalent verification-sent state.
7. Confirm the verification email arrives from the approved `@abit.ai` sender.
8. Open the verification link once.
9. Confirm the page shows email verified success and does not expose raw token data.
10. Confirm the user routes to onboarding, pending review, or dashboard gate without product access until approval.

Pass criteria:

- Signup succeeds without duplicate/account-existence disclosure.
- Verification email is delivered.
- Verification token works once.
- Product access remains gated until admin approval and provisioning readiness allow it.

### Login Routing

1. Open `https://abit.ai/auth/sign-in`.
2. Sign in as the newly verified test user or an approved launch fixture.
3. Confirm routing reflects the account status:
   - Unverified users route to verification-required copy.
   - Onboarding or review-pending users remain blocked from product access.
   - Approved users reach the approved/workspace-ready state.
   - Rejected or blocked users show support/unavailable copy.
4. Confirm `/api/auth/me`, when available to the session, reports a `gate.product_access` state consistent with the UI.

Pass criteria:

- Login does not grant product access by itself.
- Status-aware routing matches the account review/provisioning state.
- No browser console errors or PHP fatal errors occur.

### Password Reset

1. Open `https://abit.ai/auth/reset`.
2. Submit the test account email.
3. Confirm the page shows generic check-email copy.
4. Submit an unknown email and confirm the same generic check-email copy.
5. Open the reset email link for the test account.
6. Set a new strong password.
7. Confirm reset success routes back to sign-in.
8. Sign in with the new password and confirm the same status-aware routing as before.

Pass criteria:

- Reset request is non-disclosing for known and unknown emails.
- Reset email is delivered from the approved sender.
- Reset token does not expose raw token details.
- Old sessions are not trusted after password reset.

### Admin Review Smoke

1. In WordPress admin, open `Tools > ABiT Signup Review`.
2. Locate the launch test signup under the expected review status.
3. Open the detail view.
4. Confirm identity, company, ERP interests, consent evidence, email delivery context, and audit trail are visible without raw secrets.
5. For a controlled test record only, record a hold or approval decision with a clear reason and verify an `auth_admin_qualification_decision` audit event is written.

Pass criteria:

- Support/admin can inspect the request and understand the next action.
- Admin decision is persisted with audit evidence.
- Sensitive values remain redacted.

## Monitoring Window

Monitor closely for at least the first 60 minutes after launch and then daily during launch week.

| Signal | Watch for | First response |
| --- | --- | --- |
| GitHub/cPanel deployment | Failed deploy, timeout, missing copied assets | Pause launch, fetch logs, retry once only when cause is understood. |
| PHP/WordPress errors | New fatal errors, repeated warnings in auth paths | Triage immediately; rollback if signup/login/reset are affected. |
| Signup funnel | Spike in validation failures, risk holds, duplicate submissions | Check frontend console, backend validation, and WAF behavior. |
| Verification | Delivery failures, expired-token spike, resend throttles | Check provider status, DNS, sender reputation, and token lifecycle logs. |
| Login | Lockout spike, rate-limited spike, unexpected status routing | Check abuse signals and routing status metadata. |
| Password reset | Delivery failures, invalid/expired-token spike | Check provider delivery and reset-token path. |
| Admin review | Queue not updating, decisions not persisting, audit write failures | Escalate to engineering; keep manual support notes until fixed. |
| Support inbox | Customer reports of blocked signup, missing email, login failure | Follow support triage templates and escalate P1/P0 patterns. |

## Rollback Triggers

Rollback is required when any of these occur and cannot be fixed safely within the launch window.

| Severity | Trigger |
| --- | --- |
| P0 | Users cannot sign up, verify, log in, or reset passwords due to the release. |
| P0 | Auth flow grants product/provisioning access to an unapproved user. |
| P0 | Secret, raw token, reset key, password, raw IP/user-agent, or provider payload is exposed publicly or in broad-access logs. |
| P0 | Production site is down or serving fatal errors on primary auth or front-page routes. |
| P1 | Verification or reset email delivery fails broadly. |
| P1 | Admin cannot review or hold/approve launch requests and support has no safe manual workaround. |
| P1 | WAF/CDN or app rate limits block normal launch traffic and cannot be tuned quickly. |

## Rollback Plan

Use the safest available rollback path for the failure type. The release lead owns the rollback decision; operations executes hosting restore actions; engineering verifies the restored state.

1. Announce rollback start in the launch channel with incident severity, release commit, GitHub Actions run URL, and cPanel `deploy_id`.
2. Stop additional deployments by pausing the workflow queue or coordinating with maintainers.
3. Preserve evidence before changing state:
   - GitHub Actions run URL and logs.
   - cPanel deployment log path and `deploy_id`.
   - PHP error excerpts with secrets redacted.
   - Screenshots and sanitized auth/email/audit rows.
4. Choose rollback method:

| Method | Use when | Procedure |
| --- | --- | --- |
| Re-deploy previous good commit | Release changed only deployed repository files and database is compatible. | Revert or select the last known good commit on the deploy branch, trigger `Deploy to cPanel`, and verify deployment success. |
| cPanel file restore | Deployed files are broken and a known good file backup exists. | Restore the prior deployed `functions.php`, `front-page.php`, optional `dist/`, and `web.config` from hosting backup, then clear caches. |
| Database restore | Release or launch action corrupted auth/user/request data. | Stop public auth traffic if possible, restore the approved database backup, then reconcile any legitimate launch signups from captured evidence. |
| Feature/config disable | Failure is isolated to launch gating, WAF, email provider, or provisioning readiness. | Disable the risky flag or set `ABIT_SAAS_PROVISIONING_CAPACITY_READY=false`; keep signup/login safe while preserving data. |

5. Clear application, host, CDN, and browser-relevant caches as appropriate.
6. Re-run the Auth Smoke Test sections for signup, verification, login, and reset.
7. Confirm no new PHP fatal errors or high-severity auth audit failures occur for 15 minutes.
8. Announce rollback result with current production commit/config state and next owner.

Rollback pass criteria:

- Production site loads.
- Signup, verification, login, and reset pass or are intentionally disabled with approved customer-facing support messaging.
- No unapproved product access is possible.
- Support has an accurate customer response and escalation owner.

## Support Escalation

Support should classify incoming launch reports using this matrix.

| Customer symptom | Support action | Escalate to |
| --- | --- | --- |
| Did not receive verification or reset email | Confirm address spelling, ask customer to check spam, trigger resend only within policy, and check provider delivery event. | Operations if provider/DNS issue; engineering if token state is wrong. |
| Verification link expired or invalid | Ask customer to request a new link. Do not ask for or collect the raw token. | Engineering if repeated for fresh links. |
| Login blocked after signup | Confirm expected account status and explain review/onboarding gate. | Support lead for review status; engineering if status routing is inconsistent. |
| Password reset does not work | Confirm generic reset flow and provider delivery. Never request the password. | Engineering for token/session issues. |
| Customer claims unexpected access or another company data | Treat as P0 security incident. Preserve evidence and escalate immediately. | Security owner and release lead. |
| Admin review missing request | Capture test email, timestamp, and company name; do not edit database manually. | Engineering/on-call. |

## Incident Response

1. Declare severity:
   - P0: site down, unsafe access, secret/token exposure, broad auth failure.
   - P1: major launch path degraded for multiple users with workaround possible.
   - P2: isolated issue with support workaround.
2. Assign incident commander, engineering lead, operations lead, support lead, and scribe.
3. Freeze unrelated deploys until the incident is resolved.
4. Preserve evidence with secret redaction.
5. Decide within 15 minutes whether to rollback for P0, or within 30 minutes for P1 when no fix is ready.
6. Communicate customer-facing status through the approved support channel.
7. After mitigation, run the Auth Smoke Test and monitor for recurrence.
8. File follow-up defects with owner, severity, reproduction steps, evidence, and launch impact.

## Evidence Record

Use this table in the launch ticket or release notes.

| Evidence item | Value |
| --- | --- |
| Release commit |  |
| GitHub Actions run URL |  |
| cPanel deploy ID |  |
| Deployed path |  |
| Smoke-test email alias |  |
| Signup result |  |
| Verification result |  |
| Login result |  |
| Reset result |  |
| Admin review result |  |
| PHP error review |  |
| Email provider review |  |
| WAF/rate-limit review |  |
| Rollback decision |  |
| Launch approver |  |

## Validation Commands

Run from the repository root:

```text
php tests/launch-runbook-coverage.php
php -l tests/launch-runbook-coverage.php
```
