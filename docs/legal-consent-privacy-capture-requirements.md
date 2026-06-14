# Legal Consent and Privacy Capture Requirements

Task: TASK-2026-01964 / DISC-05
Project: PROJ-0130 abit_ai_website
Status: Pending legal and project owner approval

## Objective

Define the MVP requirements for capturing legal consent to the current terms and privacy notices during the abit.ai SaaS access request flow. The implementation must create an auditable consent record that proves what the requester accepted, when it was accepted, and which request context produced the acceptance without storing unnecessary raw network fingerprints.

Source references:

- `docs/saas-auth-mvp-scope.md`
- `docs/signup-company-profile-field-dictionary.md`
- `docs/signup-multi-step-flow-decision-record.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Consent Capture Requirement

The MVP signup and onboarding flow must require explicit acceptance of the current terms and privacy notices before an access request can enter `pending_admin_review`.

User-facing rules:

- The consent checkbox must be unchecked by default.
- The label must identify both the terms and privacy notices.
- The UI must link to the exact current terms and privacy notice pages or documents.
- The user must not be able to proceed to admin review unless consent is accepted.
- If either legal document changes after a saved draft, the requester must accept the new current version before submitting onboarding.

Server-side rules:

- The API must reject onboarding completion when the consent boolean is missing or false.
- The API must resolve the current legal document versions server-side; clients must not be trusted to supply the authoritative current version.
- Consent acceptance must be recorded in an append-only audit record at the same time the access request is accepted for admin review.
- Consent records must be immutable except for retention/anonymization jobs documented below.

## Versioning Rules

Each legal document shown during signup must have a stable version identifier.

Required version fields:

| Field key | Required | Rule |
| --- | --- | --- |
| `terms_version` | Yes | Stable semantic or dated version for the Terms of Service accepted by the requester. |
| `privacy_version` | Yes | Stable semantic or dated version for the Privacy Notice accepted by the requester. |
| `consent_text_version` | Yes | Version of the checkbox label or consent statement shown in the UI. |
| `legal_locale` | Yes | Locale used for the accepted legal text, defaulting to the site fallback locale when only one language is available. |

Version identifiers must not be overwritten when legal copy changes. New legal copy creates a new version, and future consent records point to that new version.

## Backend Data Model

Implementation tasks must include a dedicated consent audit record. The preferred model is an append-only `consent_audit_records` table or equivalent collection linked to `access_requests`.

Required fields:

| Field key | Type | Required | Rule |
| --- | --- | --- | --- |
| `id` | UUID or database ID | Yes | Unique consent audit record identifier. |
| `access_request_id` | Foreign key | Yes | References the canonical SaaS access request. |
| `user_id` | Foreign key, nullable | Conditional | Required when a user identity exists at consent time; nullable for pre-identity flows. |
| `business_email_hash` | String | Yes | HMAC or keyed hash of normalized business email for audit lookup without exposing email in the audit table. |
| `terms_version` | String | Yes | Terms version accepted. |
| `privacy_version` | String | Yes | Privacy notice version accepted. |
| `consent_text_version` | String | Yes | Version of the consent statement shown beside the checkbox. |
| `legal_locale` | String | Yes | Locale of the accepted legal text. |
| `accepted_at` | UTC timestamp | Yes | Server-generated timestamp when consent was accepted. |
| `ip_hash` | String | Yes | Keyed hash of the request IP address. Do not store raw IP in this record. |
| `user_agent_hash` | String | Yes | Keyed hash of the user-agent string. Do not store raw user agent in this record. |
| `hash_key_version` | String | Yes | Version identifier for the secret or pepper used to hash IP, user agent, and email values. |
| `capture_source` | Enum | Yes | Must identify the source, such as `signup_onboarding`, `admin_resubmission`, or `reconsent`. |
| `created_at` | UTC timestamp | Yes | Server-generated record creation timestamp. |

Recommended constraints:

- One access request can have multiple consent audit records over time if re-consent is required.
- The latest valid consent for an access request is the most recent record matching the current required document versions.
- Audit lookup should use `access_request_id` first; hashed email lookup is only a secondary audit aid.
- Raw IP address and raw user-agent values may be used transiently during request handling, but must not be persisted in the consent audit record.

## Access Request Summary Fields

The canonical `access_requests` record should keep summary fields for review and filtering while the full evidence lives in `consent_audit_records`.

Required summary fields:

| Field key | Required | Rule |
| --- | --- | --- |
| `terms_privacy_accepted_at` | Yes before admin review | Mirrors the latest valid consent record timestamp. |
| `terms_version` | Yes before admin review | Mirrors latest accepted terms version. |
| `privacy_version` | Yes before admin review | Mirrors latest accepted privacy version. |
| `latest_consent_audit_record_id` | Yes before admin review | References the latest valid consent audit record. |

## Retention Rule

Consent audit records must be retained long enough to prove lawful acceptance for account access, dispute handling, and compliance review.

MVP retention requirements:

- Retain consent audit records while the related access request or user account is active.
- Retain consent audit records for 7 years after rejection, account closure, or last account activity, unless legal counsel defines a longer requirement.
- After the retention period, delete the audit record or irreversibly anonymize hashed identifiers by deleting the hash key material and removing direct `access_request_id` or `user_id` links when no legal hold applies.
- Suspend deletion for records under legal hold, active dispute, security investigation, or regulatory request.
- Document retention jobs so QA can verify records are eligible for deletion/anonymization without manually deleting production evidence.

## QA Checklist

QA must verify consent capture as part of the signup and onboarding acceptance suite.

Required checks:

| Check | Expected result |
| --- | --- |
| Checkbox default state | Terms/privacy checkbox is visible and unchecked by default. |
| Missing consent | Submission without consent is blocked client-side and rejected server-side. |
| Current versions | Accepted consent records include current `terms_version`, `privacy_version`, and `consent_text_version`. |
| Timestamp | `accepted_at` and `terms_privacy_accepted_at` are server-generated UTC timestamps. |
| IP hash | Consent audit record stores `ip_hash` and does not store raw IP address. |
| User-agent hash | Consent audit record stores `user_agent_hash` and does not store raw user-agent string. |
| Hash key version | Consent audit record includes `hash_key_version`. |
| Access request link | Consent audit record links to the correct `access_request_id`; `access_requests.latest_consent_audit_record_id` points back to it. |
| Status gate | Request cannot move to `pending_admin_review` until required onboarding fields and valid current consent are present. |
| Legal version change | A draft accepted against an old version must re-accept current terms/privacy before admin review. |
| Admin review visibility | Admin review shows consent accepted timestamp and accepted document versions. |
| Retention metadata | Retention or anonymization rule is documented and testable for closed/rejected records. |

## Acceptance Criteria

- Backend data model includes an append-only consent audit record linked to access requests.
- Consent audit record captures accepted legal versions, server timestamp, hashed IP, hashed user agent, hash key version, and retention behavior.
- `access_requests` keeps summary fields required for admin review and status gating.
- QA checklist includes consent audit record creation, version capture, timestamp capture, IP/user-agent hashing, and retention rule verification.
- Implementation tasks treat legal copy version resolution as server-owned, not client-owned.
