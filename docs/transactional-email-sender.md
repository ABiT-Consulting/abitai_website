# Transactional Email Sender

Task: TASK-2026-02001 / EMAIL-01
Project: PROJ-0130 abit_ai_website
Status: Implementation notes for deployment

## Scope

The auth API sends transactional account email through WordPress `wp_mail()` with an optional SMTP transport configured by environment-backed constants. Implemented account templates cover the email verification message created by `POST /api/auth/register` and the WordPress password reset email.

## Approved Sender

Auth transactional email is forced to the approved `abit.ai` sender domain. If `ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL` is missing, invalid, or outside `@abit.ai`, the plugin falls back to:

```text
no-reply@abit.ai
```

Recommended production values:

```text
ABIT_TRANSACTIONAL_MAIL_PROVIDER=postmark
ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL=no-reply@abit.ai
ABIT_TRANSACTIONAL_MAIL_FROM_NAME=abit.ai
ABIT_TRANSACTIONAL_MAIL_REPLY_TO=support@abit.ai
```

## SMTP Environment

Set these only when the host should send through an SMTP provider instead of the platform default mail transport:

```text
ABIT_TRANSACTIONAL_SMTP_HOST=
ABIT_TRANSACTIONAL_SMTP_PORT=587
ABIT_TRANSACTIONAL_SMTP_SECURE=tls
ABIT_TRANSACTIONAL_SMTP_AUTH=true
ABIT_TRANSACTIONAL_SMTP_USERNAME=
ABIT_TRANSACTIONAL_SMTP_PASSWORD=
```

The sending domain must be verified with the selected provider before production traffic is enabled. DNS records are provider-specific, but the deployment checklist should include SPF, DKIM, DMARC alignment, and a successful provider-side verification check for `abit.ai`.

## Template And Links

Verification email uses:

- Subject: `Verify your abit.ai access request`
- From: configured approved `@abit.ai` sender
- Reply-To: configured support mailbox when valid
- HTML branded body with an automatic PHPMailer plain-text alternative
- Verification link path: `/auth/verify/?token=...&email=...`

Password reset email uses:

- Subject: `Reset your abit.ai password`
- From: configured approved `@abit.ai` sender
- Plain-text branded body
- Reset link path: `/auth/reset-password/?key=...&login=...`

Email delivery logs do not store raw verification tokens or password reset keys.

## Delivery Logging

Every auth email send attempt emits `auth_email_delivery_attempted` through `abitai_auth_write_audit_log()` when that audit writer is available. Logged metadata includes delivery result, message type, provider key, approved sender domain, token id, access request id, company id, hashed recipient domain, and branded link path. Raw tokens and full recipient addresses are not logged.

When `WP_DEBUG_LOG` is enabled, the same delivery metadata is also written to the WordPress debug log for local troubleshooting.

## Email Observability

Email delivery metadata is also stored in the local auth tables for support/admin visibility:

- `wp_abit_saas_email_delivery_events` stores sanitized event rows for sent or accepted delivery attempts, failed attempts, bounced events, verification-token expiry, and resend throttling.
- `wp_abit_saas_access_requests` keeps dashboard summary fields: `email_delivery_status`, `email_delivery_last_event`, `email_delivery_last_event_at`, `email_delivery_sent_count`, `email_delivery_failed_count`, `email_delivery_bounced_count`, `email_token_expired_count`, and `email_resend_throttled_count`.
- WordPress admin users with `list_users` can review the support dashboard at Tools > ABiT Email Observability.
- The dashboard shows masked recipient addresses and delivery metadata only. It does not show raw verification tokens, password reset keys, email body content, provider payloads, or full recipient email addresses.

Provider bounce webhooks can be normalized by calling the WordPress action `abit_saas_auth_email_bounced` with IDs and hashed recipient-domain metadata. The handler records the bounce without storing message content.

## Acceptance Check

1. Configure the production provider credentials and approved `@abit.ai` sender values.
2. Verify `abit.ai` sending domain in the provider console.
3. Submit `POST /api/auth/register` with a real test inbox.
4. Confirm the delivered verification email is from the approved `@abit.ai` sender.
5. Confirm the verification call-to-action URL uses the branded `/auth/verify/` path on the site domain.
6. Request a password reset for the same test account.
7. Confirm the delivered reset email is from the approved `@abit.ai` sender.
8. Confirm the reset URL uses the branded `/auth/reset-password/` path on the site domain.
9. Confirm delivery attempts appear in the audit log or, in debug environments, `debug.log`.
10. Confirm Tools > ABiT Email Observability shows delivery status and counts without raw tokens or message content.
