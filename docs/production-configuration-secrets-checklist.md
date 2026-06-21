# Production Configuration and Secrets Checklist

Task: `TASK-2026-02032 / LCH-01`
Project: `PROJ-0130 abit_ai_website`
Milestone: `10. Launch and Post-launch Monitoring`
Status: Production launch checklist prepared; secret values intentionally redacted from repository docs.
Source: `PROJ-0130_abit_saas_auth_task_tree.md`

## Objective

Confirm the production auth, email, domain, consent-version, rate-limit, and monitoring configuration needed to launch the abit.ai SaaS auth MVP without committing secret values.

This checklist records required keys, owners, expected non-secret shape, and verification evidence. Actual passwords, API tokens, salts, SMTP credentials, webhook secrets, and HMAC keys must live only in the production secret store, cPanel/hosting configuration, GitHub Actions secrets, or the relevant provider console.

## Secret Handling Rules

| Rule | Launch requirement |
| --- | --- |
| No secret values in Git | Repository docs and sample config may list variable names only. |
| Redacted evidence | Screenshots or release notes may show `configured`, key version, last rotation date, or provider verification state, but not values. |
| Rotation ownership | Each secret must have an owner and a rotation path before launch. |
| Access scope | Production secret access is limited to approved engineering/operations maintainers. |
| Audit trail | Changes to deployment secrets must be recorded in the hosting/provider audit trail or release checklist. |

## Required Production Variables

| Area | Variable or setting | Production status | Non-secret expected value / verification |
| --- | --- | --- | --- |
| Domain | `WP_HOME` | Required | `https://abit.ai`; confirm HTTPS loads without mixed-content warnings. |
| Domain | `WP_SITEURL` | Required | `https://abit.ai`; confirm WordPress admin and REST URLs use the production domain. |
| WordPress auth | `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY` | Required secret | Configured with unique production values; values redacted. |
| WordPress auth | `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT` | Required secret | Configured with unique production values; values redacted. |
| Auth hashing | `ABIT_SAAS_AUTH_HASH_KEY` | Required secret | Configured as a production-only HMAC key for email/IP/user-agent hashes; value redacted. |
| Auth hashing | `ABIT_SAAS_AUTH_HASH_KEY_VERSION` | Required | Set to current production key version, for example `prod-hash-key-v1`; do not expose the key itself. |
| Legal consent | `ABIT_SAAS_TERMS_VERSION` | Required | Matches the approved production Terms version. |
| Legal consent | `ABIT_SAAS_PRIVACY_VERSION` | Required | Matches the approved production Privacy Notice version. |
| Legal consent | `ABIT_SAAS_CONSENT_TEXT_VERSION` | Required | Matches the approved signup checkbox/consent statement version. |
| Legal consent | `ABIT_SAAS_LEGAL_LOCALE` | Required | Set to launch legal locale, currently `en` unless legal approves another locale. |
| Email sender | `ABIT_TRANSACTIONAL_MAIL_PROVIDER` | Required | Provider key such as `postmark`, `sendgrid`, `ses`, or `wordpress`; provider account verified. |
| Email sender | `ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL` | Required | Approved `@abit.ai` sender, recommended `no-reply@abit.ai`. |
| Email sender | `ABIT_TRANSACTIONAL_MAIL_FROM_NAME` | Required | Recommended `abit.ai`. |
| Email sender | `ABIT_TRANSACTIONAL_MAIL_REPLY_TO` | Required | Monitored support mailbox, recommended `support@abit.ai`. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_HOST` | Conditional secret-adjacent | Required when using SMTP; host configured in production only. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_PORT` | Conditional | Required when SMTP host is set; usually `587`. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_SECURE` | Conditional | Required when SMTP host is set; `tls` or provider-approved equivalent. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_AUTH` | Conditional | Required when SMTP host is set; usually `true`. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_USERNAME` | Conditional secret | Required when SMTP auth is enabled; value redacted. |
| SMTP transport | `ABIT_TRANSACTIONAL_SMTP_PASSWORD` | Conditional secret | Required when SMTP auth is enabled; value redacted. |
| Provisioning capacity | `ABIT_SAAS_PROVISIONING_CAPACITY_READY` | Required launch decision | Set `true` only after operations can process MVP provisioning requests; otherwise keep `false` and use admin readiness overrides for controlled accounts. |
| Deploy automation | `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_TOKEN`, `CPANEL_REPO_ROOT` | Required secret/config | Configured in GitHub Actions secrets; values redacted. |
| Deploy automation | `CPANEL_PORT`, `CPANEL_BRANCH`, `CPANEL_INSECURE` | Optional | Use only when production deployment needs non-default cPanel behavior. |

## Domain and HTTPS Checklist

| Check | Required result |
| --- | --- |
| DNS | `abit.ai` and `www.abit.ai` point to the production host or CDN. |
| TLS | Valid certificate covers the production hostnames and renews automatically. |
| Canonical URL | `WP_HOME` and `WP_SITEURL` resolve to the canonical HTTPS domain. |
| Redirects | HTTP redirects to HTTPS; duplicate hostnames redirect to the canonical host. |
| Secure cookies | Production traffic is HTTPS so auth cookies with `Secure`, `HttpOnly`, and `SameSite=Lax` are honored. |
| Reverse proxy IP | If behind CDN/proxy, trusted client IP headers are configured and tested for rate-limit/audit hashing. |
| CORS origins | WordPress allowed origins include only approved production origins; disallowed origins return `cors_origin_denied`. |

## Transactional Email Checklist

| Check | Required result |
| --- | --- |
| Sender domain | `abit.ai` sender domain verified in the selected provider. |
| SPF | Production DNS includes provider-approved SPF alignment. |
| DKIM | Production DNS includes active DKIM records and provider verification passes. |
| DMARC | DMARC exists for `abit.ai` with an approved launch policy. |
| Verification email | Test signup receives `Verify your abit.ai access request` from the approved sender. |
| Password reset email | Test reset receives `Reset your abit.ai password` from the approved sender. |
| Email observability | Tools > ABiT Email Observability shows sanitized delivery attempts without raw tokens, reset keys, provider payloads, or full recipient addresses. |
| Bounce handling | Provider bounce monitoring is enabled; webhook or manual support handling maps bounces to sanitized delivery events. |

## Consent Version Checklist

| Check | Required result |
| --- | --- |
| Legal approval | Terms, Privacy Notice, and checkbox text versions are approved for launch. |
| Server-owned versions | Production `ABIT_SAAS_*VERSION` constants match approved versions and are not supplied by clients. |
| Consent capture | Signup stores `terms_version`, `privacy_version`, `consent_text_version`, `legal_locale`, timestamp, and keyed hashes. |
| Evidence display | Admin review shows accepted versions and redacted hash evidence only. |
| Retention | Consent retention follows `active_plus_7_years_after_closure` unless legal hold requires longer retention. |

## Rate Limit and Edge Protection Checklist

Built-in app policy:

| Surface | Built-in policy |
| --- | --- |
| Signup | 10 attempts per hour by identifier/IP scope. |
| Login | 20 attempts per 15 minutes by identifier/IP/user scope. |
| Failed known-user login | 5 attempts per 15 minutes, then 15-minute account lockout. |
| Resend verification | 10 attempts per hour plus per-request resend cooldown. |
| Forgot password | 5 attempts per hour. |
| Reset password submit | 5 attempts per hour. |
| Admin-sensitive actions | 30 attempts per 5 minutes by admin user/IP scope. |

Production edge/WAF requirements:

| Check | Required result |
| --- | --- |
| Edge throttles | CDN/WAF rules protect `/api/auth/register`, `/api/auth/login`, `/api/auth/resend-verification`, `/wp-login.php`, and password reset paths. |
| No account disclosure | Edge blocks use generic responses where possible and do not disclose whether an email/account exists. |
| Alerting | Alert on spikes in `rate_limited`, account lockouts, signup risk holds, verification token expiry, and resend throttles. |
| Filter overrides | Any use of `abit_saas_auth_rate_limit_policies` is documented with the launch owner and reviewed by security. |

## Monitoring Checklist

| Signal | Required production monitoring |
| --- | --- |
| Deployments | GitHub Actions/cPanel deployment result monitored for success/failure. |
| PHP errors | Production PHP/WordPress error logging enabled to a restricted sink; public display disabled. |
| Auth audit events | `auth_*` audit events are retained in restricted logs without raw passwords, tokens, reset keys, IP addresses, or user agents. |
| Email delivery | Sent, failed, bounced, token-expired, and resend-throttled events are reviewed after launch. |
| Signup risk | Signup hold/challenge rates are checked daily during launch week. |
| Admin decisions | Approval, more-information, rejection, readiness override, and provisioning request events are monitored for unexpected failures. |
| Synthetic smoke | At least one production smoke run covers signup, verification, login, password reset, admin approval, and provisioning gate state before broad traffic. |

## Launch Acceptance Record

| Acceptance item | Result |
| --- | --- |
| Required production variable names are listed | Pass |
| Secret values are excluded from repository docs | Pass |
| Auth/domain/email/legal/rate-limit/monitoring coverage is present | Pass |
| Runtime sample config supports production env-backed settings | Pass |
| Remaining work | Operations must attach redacted provider/hosting evidence during the release run. |

## Validation Commands

Run from the repository root:

```text
php tests/production-config-checklist.php
php -l tests/production-config-checklist.php
```
