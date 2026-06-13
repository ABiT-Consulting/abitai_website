# Signup and Company Profile Field Dictionary

Task: TASK-2026-01962 / DISC-03
Project: PROJ-0130 abit_ai_website
Status: Pending project owner approval

## Objective

Finalize the required signup and company profile fields for the abit.ai SaaS access flow. This dictionary defines UI labels, required or optional status, validation rules, storage targets, and database/API ownership for every signup, onboarding, company profile, and admin review field referenced by the MVP scope and lead qualification brief.

Source references:

- `docs/saas-auth-mvp-scope.md`
- `docs/business-customer-personas-lead-qualification.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Ownership Model

Use these owners consistently in implementation tasks:

| Owner | Responsibility |
| --- | --- |
| Auth API | Public signup, login, email verification, password reset, and authenticated onboarding submission endpoints. |
| Access Request DB | Canonical record for the SaaS access request, review status, submitted signup fields, onboarding fields, and admin decisions. |
| User Identity DB | Credential and identity fields required to authenticate a verified requester. |
| Token DB | Single-use email verification and password reset tokens with expiry, consumption, and audit metadata. |
| Admin Review API | Admin-facing read/update endpoints for approve, reject, request-more-information, and review queue filtering. |

## Public Signup Fields

| Field key | UI label | Required | Validation rule | Storage target | Database/API owner |
| --- | --- | --- | --- | --- | --- |
| `full_name` | Full name | Yes | Trim whitespace; 2-120 characters; allow letters, spaces, apostrophes, hyphens, and periods; reject URLs, markup, and control characters. | `access_requests.full_name` | Access Request DB / Auth API |
| `business_email` | Business email | Yes | Normalize to lowercase; must pass RFC-compatible email validation; max 254 characters; reject disposable or malformed addresses where detection is available; reject duplicate active requests for the same normalized email. | `access_requests.business_email`; mirrored to `users.email` after identity creation | Access Request DB / User Identity DB / Auth API |
| `company_name` | Company name | Yes | Trim whitespace; 2-160 characters; reject URLs-only values, markup, and control characters. | `access_requests.company_name` | Access Request DB / Auth API |
| `country_region` | Country or region | Yes | Must match one configured ISO 3166 country code or approved regional routing value; store normalized code, display localized name in UI. | `access_requests.country_region` | Access Request DB / Auth API |
| `intended_use_case` | Intended use case | Yes | Trim whitespace; 20-1000 characters; must include human-readable business context; reject spam patterns, links-only values, markup, and control characters. | `access_requests.intended_use_case` | Access Request DB / Auth API |
| `password` | Password | Yes for credential signup | Minimum 12 characters; max 128 characters; require password strength check; reject common breached/password-list values where available; never store plaintext. | `users.password_hash` | User Identity DB / Auth API |
| `terms_privacy_acceptance` | I accept the current terms and privacy notices | Yes before admin review | Boolean must be `true`; capture accepted terms/privacy version and timestamp; cannot be pre-checked in UI. | `access_requests.terms_privacy_accepted_at`; `access_requests.terms_privacy_version` | Access Request DB / Auth API |

## Company Profile and Onboarding Fields

These fields are submitted after email verification and before the request enters `pending_admin_review`.

| Field key | UI label | Required | Validation rule | Storage target | Database/API owner |
| --- | --- | --- | --- | --- | --- |
| `role` | Your role | Yes | Required select or text-assisted select; must map to one persona value during admin review; if free text is allowed, trim to 2-120 characters and reject markup/control characters. | `access_requests.role` | Access Request DB / Auth API |
| `company_size` | Company size | Yes | Must be one of `1_10`, `11_50`, `51_200`, `201_500`, `501_plus`. | `access_requests.company_size` | Access Request DB / Auth API |
| `industry` | Industry | Yes | Must be one of `professional_services`, `trading_distribution`, `manufacturing`, `retail_ecommerce`, `construction_real_estate`, `healthcare`, `education`, `nonprofit`, `technology`, `other`. | `access_requests.industry` | Access Request DB / Auth API |
| `primary_workflow` | Primary workflow | Yes | Trim whitespace; 20-1000 characters; must describe a real business workflow; reject spam patterns, links-only values, markup, and control characters. | `access_requests.primary_workflow` | Access Request DB / Auth API |
| `erp_module_interest` | ERP module interest | Yes | Multi-select with at least one value; each value must be one of the configured module keys; `not_sure` may be selected alone or with no other modules depending on UI design, but requires meaningful `primary_workflow`. | `access_request_module_interests.module_key` or JSON array on `access_requests.erp_module_interest` | Access Request DB / Auth API |
| `current_system` | Current system | Optional | Trim whitespace; max 160 characters; reject markup/control characters. | `access_requests.current_system` | Access Request DB / Auth API |
| `timeline` | Timeline | Optional | Must be one configured value such as `immediate`, `1_3_months`, `3_6_months`, `6_plus_months`, `exploring`, or blank. | `access_requests.timeline` | Access Request DB / Auth API |
| `notes` | Notes or use case detail | Optional | Trim whitespace; max 2000 characters; reject markup/control characters and links-only values. | `access_requests.notes` | Access Request DB / Auth API |

## Normalized Review Fields

These fields support admin review and lead qualification. Some are derived from submitted fields rather than collected directly from the requester.

| Field key | UI label | Required | Validation rule | Storage target | Database/API owner |
| --- | --- | --- | --- | --- | --- |
| `persona` | Persona | Yes for admin review | Derived or admin-selected value; must be one of `owner_executive`, `finance_lead`, `operations_lead`, `it_admin_buyer`, `other_business_user`; derive from `role`, `primary_workflow`, and `erp_module_interest`. | `access_requests.persona` | Access Request DB / Admin Review API |
| `review_status` | Request status | Yes | Must follow allowed MVP statuses: `pending_email_verification`, `onboarding_required`, `pending_admin_review`, `approved_for_mvp_access`, `rejected`, or `more_information_requested` if implemented for admin follow-up. | `access_requests.review_status` | Access Request DB / Auth API / Admin Review API |
| `verification_status` | Email verification status | Yes | Boolean or enum derived from successful single-use verification token consumption; unverified requests cannot reach product access. | `access_requests.email_verified_at` | Access Request DB / Auth API |
| `admin_decision_reason` | Admin decision reason | Required for rejection or more information | Trim whitespace; 10-1000 characters when status is `rejected` or `more_information_requested`; reject markup/control characters. | `access_requests.admin_decision_reason` | Access Request DB / Admin Review API |
| `admin_reviewed_by` | Reviewed by | Required when admin decision is made | Must reference an authorized admin user ID. | `access_requests.admin_reviewed_by` | Access Request DB / Admin Review API |
| `admin_reviewed_at` | Reviewed at | Required when admin decision is made | Server-generated timestamp in UTC; immutable for the recorded decision unless a new decision event model is implemented. | `access_requests.admin_reviewed_at` | Access Request DB / Admin Review API |

## Auth Workflow Fields

These fields are not directly shown as editable profile fields, but the source brief requires them for verification, password reset, duplicate prevention, and auditability.

| Field key | UI label | Required | Validation rule | Storage target | Database/API owner |
| --- | --- | --- | --- | --- | --- |
| `email_verification_token_hash` | Email verification token | Yes until consumed | Store only a secure hash of a random single-use token; token must expire; successful use marks token consumed and advances status to `onboarding_required`. | `email_verification_tokens.token_hash` | Token DB / Auth API |
| `email_verification_expires_at` | Verification link expiry | Yes | Server-generated UTC timestamp; expired tokens cannot verify email and must offer resend. | `email_verification_tokens.expires_at` | Token DB / Auth API |
| `email_verification_consumed_at` | Verification completed at | Optional until consumed | Server-generated UTC timestamp set once; token cannot be reused after this value is set. | `email_verification_tokens.consumed_at` | Token DB / Auth API |
| `password_reset_token_hash` | Password reset token | Required for active reset request | Store only a secure hash of a random single-use token; response to reset request must not disclose whether email exists. | `password_reset_tokens.token_hash` | Token DB / Auth API |
| `password_reset_expires_at` | Password reset expiry | Required for active reset request | Server-generated UTC timestamp; expired reset tokens cannot change passwords. | `password_reset_tokens.expires_at` | Token DB / Auth API |
| `password_reset_consumed_at` | Password reset completed at | Optional until consumed | Server-generated UTC timestamp set after successful password change; consumed token cannot be reused. | `password_reset_tokens.consumed_at` | Token DB / Auth API |
| `created_at` | Created at | Yes | Server-generated UTC timestamp when signup request is accepted. | `access_requests.created_at` | Access Request DB / Auth API |
| `updated_at` | Updated at | Yes | Server-generated UTC timestamp whenever request data or status changes. | `access_requests.updated_at` | Access Request DB / Auth API / Admin Review API |

## Enumerated Values

### Company Size

- `1_10`
- `11_50`
- `51_200`
- `201_500`
- `501_plus`

### Industry

- `professional_services`
- `trading_distribution`
- `manufacturing`
- `retail_ecommerce`
- `construction_real_estate`
- `healthcare`
- `education`
- `nonprofit`
- `technology`
- `other`

### ERP Module Interest

- `accounting`
- `crm`
- `sales`
- `buying`
- `stock`
- `manufacturing`
- `projects`
- `hr_payroll`
- `support_helpdesk`
- `website_portal`
- `reports_analytics`
- `integrations`
- `full_erp_evaluation`
- `not_sure`

### Persona

- `owner_executive`
- `finance_lead`
- `operations_lead`
- `it_admin_buyer`
- `other_business_user`

### Request Status

- `pending_email_verification`
- `onboarding_required`
- `pending_admin_review`
- `approved_for_mvp_access`
- `rejected`
- `more_information_requested`

## Implementation Notes

- Public signup should create one `access_requests` record with `review_status = pending_email_verification` and send one email verification token.
- A verified requester who has not completed onboarding may authenticate only into the onboarding gate.
- Onboarding completion requires `role`, `company_size`, `industry`, `primary_workflow`, `erp_module_interest`, and current terms/privacy acceptance before setting `review_status = pending_admin_review`.
- Admin review must expose full name, business email, company name, country or region, role, company size, industry, primary workflow, ERP module interest, current system, timeline, notes, verification status, and request status.
- Admin approval does not require automated ERPNext tenant provisioning.

## Acceptance Criteria

- Every field referenced by the MVP signup, onboarding, lead qualification, admin review, verification, and password reset brief has a validation rule.
- Every field has an explicit storage target and database/API owner.
- Required and optional field status is defined for both public signup and verified onboarding.
- Admin review has enough normalized data to evaluate persona, company size, industry, country, ERP module interest, and request status.
