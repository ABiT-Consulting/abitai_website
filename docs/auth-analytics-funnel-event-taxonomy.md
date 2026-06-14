# Auth Analytics and Funnel Event Taxonomy

Task: TASK-2026-01966 / DISC-06
Project: PROJ-0130 abit_ai_website
Status: Pending project owner approval

## Objective

Define the MVP analytics event taxonomy for the abit.ai SaaS authentication and onboarding funnel. The taxonomy must let product, engineering, and admin stakeholders measure signup start, account creation, email verification, login, password reset, and onboarding completion without collecting unnecessary personal data in analytics tools.

Source references:

- `docs/saas-auth-mvp-scope.md`
- `docs/signup-company-profile-field-dictionary.md`
- `docs/signup-multi-step-flow-decision-record.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Event Naming Rules

- Use lowercase snake_case event names.
- Emit server-side events for state changes that must be accurate for funnel reporting.
- Client-side events may be used for UI starts and form interaction context, but they must not be the only source for account, verification, reset, or onboarding state changes.
- Do not send raw passwords, verification tokens, reset tokens, raw IP addresses, raw user-agent strings, or free-text business descriptions to analytics.
- Use stable opaque identifiers for `access_request_id`, `user_id`, and `session_id` where available. Hash or omit email values unless a downstream analytics system has an approved PII handling policy.
- All timestamps must be recorded in UTC by the emitting service.

## Common Required Properties

Every auth analytics event must include these properties unless specifically marked not available.

| Property | Required | Type | Description |
| --- | --- | --- | --- |
| `event_id` | Yes | UUID/string | Unique event identifier for deduplication. |
| `event_name` | Yes | String | One of the event names defined in this taxonomy. |
| `occurred_at` | Yes | ISO 8601 datetime | Server or client UTC timestamp for when the event occurred. |
| `source` | Yes | Enum | Emitting source, such as `client_web`, `auth_api`, `token_service`, or `admin_review_api`. |
| `environment` | Yes | Enum | Runtime environment, such as `production`, `staging`, or `development`. |
| `session_id` | Yes when available | String | Anonymous or authenticated session identifier. |
| `request_locale` | Yes when available | String | Locale active for the request or UI. |
| `referrer_source` | Yes when available | String | Normalized acquisition or referrer value, not a full URL containing sensitive query strings. |
| `utm_source` | No | String | Marketing source from allowlisted UTM parameters. |
| `utm_medium` | No | String | Marketing medium from allowlisted UTM parameters. |
| `utm_campaign` | No | String | Marketing campaign from allowlisted UTM parameters. |

## Auth Funnel Events

| Event name | Funnel stage | Trigger point | Required properties | Notes |
| --- | --- | --- | --- | --- |
| `auth_signup_started` | Signup started | Client records the first intentional start of the signup flow, such as opening the signup form, landing on the first Account step, or focusing the first required field after page load. Emit once per session per signup attempt. | Common properties; `signup_entry_point`; `signup_flow_version`; `current_step = account`; `is_authenticated = false`. | Use this as the top-of-funnel denominator. Do not emit repeatedly on validation errors or field focus changes. |
| `auth_account_created` | Account created | Auth API successfully creates the canonical access request and, when credential signup is enabled, the user identity record. | Common properties; `access_request_id`; `user_id` when created; `review_status = pending_email_verification`; `signup_flow_version`; `account_creation_method`; `company_country_region`; `industry` when already collected; `company_size` when already collected. | Server-side event. Do not emit if validation fails or duplicate prevention blocks creation. |
| `auth_verification_sent` | Verification sent | Auth API or token service creates an email verification token and queues or sends the verification email for an access request. | Common properties; `access_request_id`; `user_id` when available; `verification_delivery_channel = email`; `verification_send_reason`; `token_expires_at`; `email_domain_hash`; `email_delivery_provider`; `review_status = pending_email_verification`. | Server-side event. `verification_send_reason` values should include `initial_signup` and `resend`. Store only token metadata, never the token value. |
| `auth_email_verified` | Verified | Auth API successfully consumes a valid single-use verification token and advances the access request from `pending_email_verification` to `onboarding_required`. | Common properties; `access_request_id`; `user_id`; `previous_review_status = pending_email_verification`; `review_status = onboarding_required`; `verification_age_seconds`; `token_issue_reason`; `verification_attempt_result = success`. | Server-side event. Failed verification attempts can be tracked separately as security telemetry, but are outside the required MVP funnel events. |
| `auth_login_succeeded` | Login success | Auth API validates credentials and creates an authenticated session. | Common properties; `user_id`; `access_request_id` when linked; `review_status`; `auth_method`; `post_login_destination`; `is_email_verified`; `is_admin_approved`; `login_attempt_result = success`. | Server-side event. `post_login_destination` should use normalized values such as `onboarding_gate`, `review_pending`, or `mvp_app_shell`. |
| `auth_login_failed` | Login failure | Auth API rejects a login attempt because credentials are invalid, the account is blocked, email is unverified, rate limits apply, or another generic auth failure occurs. | Common properties; `failure_reason_category`; `auth_method`; `email_domain_hash` when an email was submitted; `is_rate_limited`; `login_attempt_result = failure`; `has_matching_user` only if approved for internal analytics. | Server-side event. User-facing responses must remain generic and must not disclose whether the email exists. Avoid sending submitted email or detailed credential failure text. |
| `auth_password_reset_requested` | Reset requested | Auth API accepts a password reset request and returns the generic reset-request response, regardless of whether a matching user exists. | Common properties; `reset_request_result`; `email_domain_hash` when an email was submitted; `reset_delivery_channel = email`; `reset_token_created`; `is_rate_limited`; `password_reset_flow_version`. | Server-side event. `reset_token_created` may be false for unmatched emails while the response stays generic. Do not expose this value to the requester. |
| `auth_onboarding_completed` | Onboarding completed | Auth API accepts all required onboarding fields and current legal consent, then advances the request to `pending_admin_review`. | Common properties; `access_request_id`; `user_id`; `previous_review_status = onboarding_required`; `review_status = pending_admin_review`; `signup_flow_version`; `onboarding_completed_step = erp_needs`; `company_size`; `industry`; `country_region`; `erp_module_interest`; `persona` when derived; `terms_version`; `privacy_version`; `consent_audit_record_id`. | Server-side event. Use configured enum values from the field dictionary. Do not send free-text `primary_workflow`, `intended_use_case`, or `notes` to analytics. |

## Property Definitions

| Property | Type | Allowed values or rule |
| --- | --- | --- |
| `access_request_id` | String | Stable opaque identifier for the canonical access request. |
| `user_id` | String | Stable opaque user identity identifier when an identity exists. |
| `signup_entry_point` | Enum/string | Normalized source such as `website_nav`, `pricing_cta`, `invite_link`, `footer`, or `direct_signup_url`. |
| `signup_flow_version` | String | Version identifier for the active signup and onboarding flow. |
| `current_step` | Enum | `account`, `company`, or `erp_needs`. |
| `is_authenticated` | Boolean | Whether the requester had an authenticated session when the event occurred. |
| `account_creation_method` | Enum | `email_password` for MVP credential signup; reserve `admin_invite` or `imported` for future flows if needed. |
| `company_country_region` | String | Normalized country or approved regional routing value when collected at account creation. |
| `country_region` | String | Same normalized country or region value defined in the field dictionary. |
| `company_size` | Enum | `1_10`, `11_50`, `51_200`, `201_500`, or `501_plus`. |
| `industry` | Enum | One configured industry value from the field dictionary. |
| `erp_module_interest` | Array of strings | One or more configured module keys from the field dictionary. |
| `persona` | Enum | `owner_executive`, `finance_lead`, `operations_lead`, `it_admin_buyer`, or `other_business_user`. |
| `review_status` | Enum | `pending_email_verification`, `onboarding_required`, `pending_admin_review`, `approved_for_mvp_access`, `rejected`, or `more_information_requested`. |
| `previous_review_status` | Enum | Prior request status before the tracked state transition. |
| `auth_method` | Enum | `email_password` for MVP; future methods must be added before instrumentation. |
| `post_login_destination` | Enum | `verify_email`, `onboarding_gate`, `review_pending`, `mvp_app_shell`, `admin_review`, or `blocked`. |
| `is_email_verified` | Boolean | Whether the requester has completed email verification at login time. |
| `is_admin_approved` | Boolean | Whether the requester has `approved_for_mvp_access` at login time. |
| `login_attempt_result` | Enum | `success` or `failure`. |
| `failure_reason_category` | Enum | `invalid_credentials`, `unverified_email`, `rate_limited`, `blocked`, `password_reset_required`, or `unknown`. |
| `is_rate_limited` | Boolean | Whether the request hit an auth rate limit. |
| `has_matching_user` | Boolean | Optional internal-only diagnostic flag. Include only if approved for analytics use because it can reveal account existence. |
| `reset_request_result` | Enum | `accepted_generic_response`, `rate_limited`, or `validation_error`. |
| `reset_delivery_channel` | Enum | `email` for MVP password reset delivery. |
| `reset_token_created` | Boolean | Whether a reset token was created internally. Must not be exposed in the requester-facing response. |
| `password_reset_flow_version` | String | Version identifier for the active password reset flow. |
| `verification_send_reason` | Enum | `initial_signup`, `resend`, or `admin_resend`. |
| `verification_delivery_channel` | Enum | `email` for MVP verification delivery. |
| `email_delivery_provider` | String | Normalized provider key for the email service that handled the message. |
| `verification_attempt_result` | Enum | `success` for the required verified event. Failure telemetry is tracked separately if implemented. |
| `token_issue_reason` | Enum | `initial_signup`, `resend`, `admin_resend`, or `unknown`. |
| `email_domain_hash` | String | Keyed hash of the normalized email domain or approved domain classification. Do not send full email addresses by default. |
| `verification_age_seconds` | Number | Time between token creation and successful token consumption. |
| `token_expires_at` | ISO 8601 datetime | UTC expiry timestamp for token metadata. |
| `onboarding_completed_step` | Enum | `erp_needs` for the approved multi-step MVP flow. |
| `terms_version` | String | Server-resolved terms version accepted during onboarding. |
| `privacy_version` | String | Server-resolved privacy notice version accepted during onboarding. |
| `consent_audit_record_id` | String | Identifier of the append-only consent audit record linked to onboarding completion. |

## Funnel Reporting Contract

MVP funnel reports should calculate these conversions:

1. `auth_signup_started` to `auth_account_created`
2. `auth_account_created` to `auth_verification_sent`
3. `auth_verification_sent` to `auth_email_verified`
4. `auth_email_verified` to `auth_onboarding_completed`
5. `auth_login_succeeded` by `review_status` and `post_login_destination`
6. `auth_login_failed` by `failure_reason_category` and rate-limit state
7. `auth_password_reset_requested` by `reset_request_result`

Where a user repeats a step, reports should count both raw event volume and deduplicated access-request conversion. The primary funnel conversion should deduplicate by `access_request_id` and use the earliest event for each stage.

## Implementation Notes

- Instrument `auth_signup_started` client-side and all other required events server-side.
- Make event emission idempotent for state transitions so retries do not create duplicate canonical funnel events for the same `access_request_id` and transition.
- Use `event_id` plus the relevant state transition key for deduplication in the analytics pipeline.
- Keep analytics event delivery non-blocking for user-facing requests after the canonical database transaction succeeds.
- Emit events only after successful database writes for account creation, verification, and onboarding completion.
- Store detailed security audit logs separately from product analytics where sensitive auth diagnostics are required.

## Acceptance Criteria

- Signup started, account created, verification sent, verified, login success, login failure, reset requested, and onboarding completed events are defined.
- Each event has a trigger point and required properties.
- Required properties reuse the MVP request statuses and field keys defined in the auth scope and field dictionary.
- Analytics guidance excludes raw passwords, tokens, raw IP addresses, raw user-agent strings, and free-text business descriptions.
- Funnel reporting can measure conversion from signup start through onboarding completion and segment login/reset health without exposing requester-sensitive data by default.
