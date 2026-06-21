# Backend Auth API Contract

Task: TASK-2026-02000 / BE-09
Project: PROJ-0130 abit_ai_website
Status: Published frontend integration contract

## Objective

Document the backend authentication API surface that frontend work can mock before live integration. This contract is based on the current `ABiT SaaS Auth API` must-use plugin in `wp-content/mu-plugins/abit-saas-auth.php`.

## Transport

Base URL options:

- Pretty API routes: `/api`
- WordPress REST routes: `/wp-json/abit-ai/v1`

Use the pretty routes for frontend code unless a WordPress REST client already targets `/wp-json/abit-ai/v1`.

Common request headers:

```http
Content-Type: application/json
Accept: application/json
```

Session handling:

- `POST /api/auth/login` creates a WordPress auth cookie session.
- Authenticated requests must include browser credentials, for example `fetch(url, { credentials: "include" })`.
- Successful login also returns `nonce`; pass it as `X-WP-Nonce` for WordPress REST requests when the client calls `/wp-json/abit-ai/v1/*` directly.

## Endpoint Summary

| Method | Pretty path | REST path | Auth | Purpose |
| --- | --- | --- | --- | --- |
| POST | `/api/auth/register` | `/wp-json/abit-ai/v1/auth/register` | Public | Accept a SaaS access request and send verification instructions when the email is eligible. |
| POST | `/api/auth/resend-verification` | `/wp-json/abit-ai/v1/auth/resend-verification` | Public | Resend an email verification link for an eligible pending request with cooldown, rate limit, and token supersession. |
| POST | `/api/auth/login` | `/wp-json/abit-ai/v1/auth/login` | Public | Authenticate credentials and return status-aware routing state. |
| POST | `/api/auth/logout` | `/wp-json/abit-ai/v1/auth/logout` | Optional session | Revoke current session token when present. |
| GET | `/api/auth/me` | `/wp-json/abit-ai/v1/auth/me` | Required | Return the current authenticated user, onboarding, gate, and provisioning state. |
| POST | `/api/provisioning/request` | `/wp-json/abit-ai/v1/provisioning/request` | Required | Record or return the manual provisioning request for an eligible access request. |
| POST | `/api/workspace/slug/validate` | `/wp-json/abit-ai/v1/workspace/slug/validate` | Admin review | Validate a generated or admin-entered workspace slug and return a safe suggestion when blocked. |

## Account Statuses and Routes

`status` is the canonical backend review status. `state`, `route`, and `gate.next_path` are frontend routing helpers.

| Review status | State | Route | Next path | Product access |
| --- | --- | --- | --- | --- |
| `pending_email_verification` | `verification_required` | `verify_email` | `/auth/verify` | No |
| `onboarding_required` | `onboarding_required` | `onboarding` | `/auth/onboarding` | No |
| `pending_admin_review` | `review_pending` | `review_pending` | `/auth/review-pending` | No |
| `more_information_requested` | `more_information_requested` | `onboarding` | `/auth/more-information` | No |
| `approved_for_mvp_access` | `approved` | `app` | `/dashboard` | Yes |
| `rejected` | `rejected` | `rejected` | `/auth/rejected` | No |
| Any locked account | `account_locked` | `locked` | `/auth/sign-in` | No |

Locked accounts are identified by non-zero WordPress `user_status` or truthy user meta keys `abit_saas_account_locked`, `abit_saas_security_hold`, or `account_locked`.

## Onboarding Statuses

These values appear in `GET /api/auth/me` as `onboarding.status`.

| Status | Meaning |
| --- | --- |
| `blocked_until_email_verified` | Email verification is still required. |
| `required` | Verified user still needs required onboarding fields. |
| `ready_to_submit` | Required onboarding fields are present while review status is `onboarding_required` or `more_information_requested`. |
| `submitted_for_review` | Request is waiting for admin review. |
| `approved` | Request has MVP access approval. |
| `closed` | Request was rejected. |

Required onboarding fields used by readiness checks are `role`, `company_size`, `industry`, `primary_workflow`, and at least one `erp_module_interest` value.

## Provisioning Preflight and Eligibility

Provisioning is manual/deferred for MVP, but the frontend can show request status from the API.

`provisioning.preflight` exposes the required checklist that must pass before `POST /api/provisioning/request` can record a request:

| Check key | Required field or condition |
| --- | --- |
| `company_profile` | Access request, company ID, company name, company size, and industry are present. |
| `country` | `country_region` is present. |
| `modules` | At least one normalized `erp_module_interest` value is present. |
| `owner` | The access request has a linked owner user, owner name, and owner email. |
| `consent` | Current consent evidence is present: accepted timestamp, terms version, privacy version, and latest consent audit record ID. |
| `admin_approval` | `review_status` is `approved_for_mvp_access`. |
| `capacity_readiness` | Provisioning capacity is enabled by `ABIT_SAAS_PROVISIONING_CAPACITY_READY`, the `abit_saas_provisioning_capacity_ready` WordPress option, or the `abit_saas_auth_provisioning_capacity_ready` filter. |

`provisioning.missing_requirements` can contain:

| Requirement | Meaning |
| --- | --- |
| `account_available` | Account is locked or unavailable. |
| `access_request` | No access request record is linked to the user. |
| `company_profile` | No company profile is linked to the access request. |
| `country_region` | Country or region is missing from the company profile. |
| `email_verified` | Email verification is incomplete. |
| `erp_module_interest` | Required ERP module interest is missing. |
| `request_owner` | The request owner user, owner name, or owner email is missing. |
| `consent_audit` | Consent acceptance evidence or the consent audit record link is incomplete. |
| `admin_approval` | The access request has not been approved for MVP access. |
| `capacity_readiness` | Operational capacity has not been marked ready for provisioning. |
| `request_not_rejected` | Access request has been rejected. |

## Error Envelope

Validation errors use this shape:

```json
{
  "message": "Please correct the highlighted fields.",
  "code": "validation_failed",
  "field_errors": {
    "business_email": "Enter a valid business email address."
  }
}
```

Non-validation errors use this shape:

```json
{
  "message": "Authentication is required.",
  "code": "not_authenticated",
  "authenticated": false
}
```

## Error Codes

| HTTP status | Code | Used by | Frontend handling |
| --- | --- | --- | --- |
| 401 | `invalid_login` | Login with invalid credentials. | Show generic sign-in error; do not reveal whether account exists. |
| 401 | `not_authenticated` | Auth-required endpoint without active session. | Clear local auth state and route to sign-in. |
| 401 | none | Logout with no active session. | Treat as signed out; response has `authenticated: false`. |
| 422 | `validation_failed` | Invalid registration or login payload. | Show inline field errors. |
| 422 | `provisioning_not_allowed` | Provisioning requested before onboarding/company requirements are met. | Keep user on current gate and display missing requirements if useful. |
| 422 | `workspace_slug_reserved` or `workspace_slug_taken` | Workspace slug validation. | Reject the entered slug and offer `suggested_slug`. |
| 423 | `account_locked` | Login for locked account. | Route to locked/support state. |
| 429 | `rate_limited` | Signup, login, resend, forgot/reset, and admin-sensitive endpoints after too many attempts. | Disable retry until `retry_after`/`Retry-After` has elapsed and show generic cooldown copy. |
| 423 | `provisioning_not_allowed` | Provisioning requested for locked or rejected account. | Route to locked/rejected state based on account status. |
| 500 | `registration_failed` | Registration transaction or verification email failed. | Show retry message. |
| 500 | `provisioning_request_failed` | Provisioning request could not be recorded. | Show retry/support message. |

Pretty routes return `405` with `{ "message": "Method not allowed." }` when called with the wrong method.

## POST /api/auth/register

Accepts the signup request. When the submitted email is eligible and not already present, the backend creates the user identity, company draft, access request, consent audit record, and verification email token. Duplicate emails receive the same neutral accepted response so the API does not disclose whether an account or access request already exists.

Request:

```json
{
  "full_name": "Jane Ahmed",
  "business_email": "jane.ahmed@example.com",
  "company_name": "ABiT Trading LLC",
  "country_region": "AE",
  "intended_use_case": "We want to evaluate ERPNext for purchase approvals, inventory receipts, and invoice matching.",
  "password": "StrongerPass123!",
  "terms_privacy_acceptance": true
}
```

Accepted aliases:

- `email` can be sent instead of `business_email`.
- `terms_accepted` can be sent instead of `terms_privacy_acceptance`.

Validation:

| Field | Rule |
| --- | --- |
| `full_name` | 2-120 characters; starts with a letter; letters, spaces, apostrophes, hyphens, and periods. |
| `business_email` | Valid email, max 254 characters, normalized lowercase, unique across users and access requests. |
| `company_name` | 2-160 characters; not unsafe markup/control text; not links-only. |
| `country_region` | Two-letter country code or `EU`, `GCC`, `MENA`; normalized uppercase. |
| `intended_use_case` | 20-1000 characters; not unsafe markup/control text; not links-only. |
| `password` | 12-128 characters; at least three character classes; rejects common examples. |
| `terms_privacy_acceptance` | Must be boolean true. |

Accepted response, `202`:

```json
{
  "message": "If this email is eligible, verification instructions will be sent to that address.",
  "status": "accepted"
}
```

Mock validation response, `422`:

```json
{
  "message": "Please correct the highlighted fields.",
  "code": "validation_failed",
  "field_errors": {
    "full_name": "Enter a valid full name using 2 to 120 letters and name punctuation characters.",
    "business_email": "Enter a valid business email address.",
    "password": "Password must be 12 to 128 characters.",
    "terms_privacy_acceptance": "You must accept the current terms and privacy notices."
  }
}
```

Duplicate email response, `202`:

```json
{
  "message": "If this email is eligible, verification instructions will be sent to that address.",
  "status": "accepted"
}
```

## POST /api/auth/resend-verification

Creates a fresh single-use email verification token for an eligible `pending_email_verification` request and supersedes previous unconsumed verification tokens. The endpoint uses a 60-second resend cooldown and a 5-request hourly limit per eligible request/user context.

Request:

```json
{
  "business_email": "jane.ahmed@example.com"
}
```

Accepted aliases:

- `email` can be sent instead of `business_email`.

Accepted response, `202`:

```json
{
  "message": "If an eligible request exists, we will send a new verification link.",
  "status": "accepted",
  "sent": true,
  "email_verification_token_id": 202
}
```

Unknown, already verified, locked, or otherwise ineligible emails receive the same generic `202` response with `sent: false` and no token ID.

Cooldown or rate-limit response, `429`:

```json
{
  "message": "If an eligible request exists, we will send a new verification link.",
  "status": "rate_limited",
  "sent": false,
  "retry_after": 60
}
```

Validation response, `422`:

```json
{
  "message": "Please correct the highlighted fields.",
  "code": "validation_failed",
  "field_errors": {
    "business_email": "Enter a valid business email address."
  }
}
```

Security behavior:

- Raw tokens are never returned or logged.
- Previous unconsumed verification tokens are marked consumed/superseded before a new token is created.
- Legacy meta-only users keep one current token hash; new resends overwrite prior valid token metadata.
- Delivery attempts and resend outcomes are written to the auth audit log when the audit table is available.
- Repeated resend attempts are also tracked in the auth rate-limit event table by hashed identifier/IP and return `429` with `retry_after` plus `Retry-After` when throttled.

## POST /api/auth/login

Authenticates by email and password, creates an auth cookie session, and returns status-aware routing fields.

Request:

```json
{
  "email": "jane.ahmed@example.com",
  "password": "StrongerPass123!",
  "remember": true
}
```

Accepted aliases:

- `business_email` can be sent instead of `email`.
- `remember_me` can be sent instead of `remember`.

Success response for unverified user, `200`:

```json
{
  "message": "Signed in. Redirecting...",
  "authenticated": true,
  "user_id": 123,
  "access_request_id": 789,
  "company_id": 456,
  "status": "pending_email_verification",
  "state": "verification_required",
  "route": "verify_email",
  "email_verified": false,
  "nonce": "mock-wp-rest-nonce"
}
```

Success response for approved user, `200`:

```json
{
  "message": "Signed in. Redirecting...",
  "authenticated": true,
  "user_id": 123,
  "access_request_id": 789,
  "company_id": 456,
  "status": "approved_for_mvp_access",
  "state": "approved",
  "route": "app",
  "email_verified": true,
  "nonce": "mock-wp-rest-nonce"
}
```

Mock invalid credentials response, `401`:

```json
{
  "message": "We could not sign you in with those details. Check your email and password, then try again.",
  "code": "invalid_login"
}
```

Repeated invalid credentials are counted by hashed email/IP. When the failed-login policy is exceeded, the backend writes an `auth_account_lockout_started` audit/risk event and applies a temporary account lockout; later login attempts return the locked response until the lockout expires. Generic high-volume login attempts may return:

```json
{
  "message": "Too many attempts. Try again in 900 seconds.",
  "code": "rate_limited",
  "retry_after": 900
}
```

Mock locked response, `423`:

```json
{
  "message": "This account cannot sign in right now. Contact support for help.",
  "code": "account_locked",
  "state": "account_locked"
}
```

## POST /api/auth/logout

Revokes the current session token when one exists.

Request:

```json
{}
```

Success response, `200`:

```json
{
  "message": "Signed out.",
  "authenticated": false,
  "revoked": true
}
```

No active session response, `401`:

```json
{
  "message": "No active session to sign out.",
  "authenticated": false,
  "revoked": false
}
```

Frontend note: treat both responses as signed-out terminal states.

## GET /api/auth/me

Returns the current authenticated account, onboarding, gate, and provisioning state.

Request:

```http
GET /api/auth/me
Cookie: wordpress_logged_in_*=...
```

Mock response for review-pending user, `200`:

```json
{
  "authenticated": true,
  "user": {
    "id": 123,
    "email": "jane.ahmed@example.com",
    "display_name": "Jane Ahmed",
    "full_name": "Jane Ahmed",
    "wp_roles": ["subscriber"]
  },
  "verification": {
    "email_verified": true,
    "status": "verified",
    "email_verified_at": "2026-06-14 08:30:00"
  },
  "company": {
    "id": 456,
    "name": "ABiT Trading LLC",
    "country_region": "AE",
    "company_size": "11_50",
    "industry": "trading_distribution",
    "status": "draft"
  },
  "workspace": {
    "created": true,
    "status": "active",
    "hold_reason": null,
    "workspace": {
      "id": 601,
      "company_id": 456,
      "key": "abit-trading-llc-456",
      "display_name": "ABiT Trading LLC",
      "status": "active",
      "created_by_user_id": 123,
      "created_at": "2026-06-14 08:30:00",
      "updated_at": "2026-06-14 08:30:00"
    },
    "membership": {
      "id": 701,
      "workspace_id": 601,
      "company_id": 456,
      "user_id": 123,
      "access_request_id": 789,
      "role": "owner",
      "status": "active",
      "joined_at": "2026-06-14 08:30:00"
    }
  },
  "role": "Operations Manager",
  "onboarding": {
    "status": "submitted_for_review",
    "completed": true,
    "required_fields_complete": true,
    "role": "Operations Manager",
    "company_size": "11_50",
    "industry": "trading_distribution",
    "primary_workflow_provided": true,
    "erp_module_interest": ["buying", "stock", "accounting"],
    "onboarding_templates": [
      "erpnext_procurement_onboarding",
      "erpnext_inventory_onboarding",
      "erpnext_finance_onboarding"
    ],
    "qualification_tags": [
      "module:procurement",
      "workflow:purchase_to_pay",
      "module:inventory",
      "workflow:stock_control",
      "qualification:inventory_review",
      "module:finance",
      "workflow:financial_controls",
      "qualification:finance_review",
      "qualification:finance_inventory_review"
    ],
    "admin_review_fields": {
      "persona": "operations_lead",
      "company_size": "11_50",
      "industry": "trading_distribution",
      "country_region": "AE",
      "erp_module_interest": ["buying", "stock", "accounting"],
      "onboarding_templates": [
        "erpnext_procurement_onboarding",
        "erpnext_inventory_onboarding",
        "erpnext_finance_onboarding"
      ],
      "qualification_tags": [
        "module:procurement",
        "workflow:purchase_to_pay",
        "module:inventory",
        "workflow:stock_control",
        "qualification:inventory_review",
        "module:finance",
        "workflow:financial_controls",
        "qualification:finance_review",
        "qualification:finance_inventory_review"
      ]
    },
    "current_system": "Spreadsheets",
    "timeline": "1_3_months"
  },
  "access_request": {
    "id": 789,
    "status": "pending_admin_review",
    "created_at": "2026-06-14 08:00:00",
    "updated_at": "2026-06-14 08:35:00"
  },
  "provisioning": {
    "eligible": false,
    "missing_requirements": ["admin_approval", "capacity_readiness"],
    "preflight": {
      "completed": false,
      "missing_requirements": ["admin_approval", "capacity_readiness"],
      "checks": [
        { "key": "company_profile", "label": "Company profile", "passed": true },
        { "key": "country", "label": "Country or region", "passed": true },
        { "key": "modules", "label": "ERP modules", "passed": true },
        { "key": "owner", "label": "Request owner", "passed": true },
        { "key": "consent", "label": "Consent audit", "passed": true },
        { "key": "admin_approval", "label": "Admin approval", "passed": false },
        { "key": "capacity_readiness", "label": "Capacity readiness", "passed": false }
      ]
    },
    "request": null
  },
  "gate": {
    "state": "review_pending",
    "route": "review_pending",
    "next_path": "/auth/review-pending",
    "product_access": false,
    "locked": false
  }
}
```

Mock unauthenticated response, `401`:

```json
{
  "message": "Authentication is required.",
  "code": "not_authenticated",
  "authenticated": false
}
```

## POST /api/provisioning/request

Records a manual provisioning request for an account with a completed provisioning preflight checklist, or returns the existing request if one was already recorded.

Request:

```json
{}
```

Created response, `201`:

```json
{
  "message": "Provisioning request recorded.",
  "eligible": true,
  "preflight": {
    "completed": true,
    "missing_requirements": []
  },
  "provisioning": {
    "id": 301,
    "access_request_id": 789,
    "company_id": 456,
    "status": "requested",
    "requested_at": "2026-06-14 09:15:00",
    "processed_at": null,
    "erp_tenant_reference": ""
  },
  "access_request": {
    "id": 789,
    "status": "approved_for_mvp_access"
  }
}
```

Existing request response, `200`, has the same shape as the `201` response.

Mock ineligible response, `422`:

```json
{
  "message": "Provisioning can only be requested after email verification, admin approval, and all required preflight checks are complete.",
  "code": "provisioning_not_allowed",
  "eligible": false,
  "missing_requirements": ["admin_approval", "capacity_readiness"],
  "provisioning": {
    "eligible": false,
    "missing_requirements": ["admin_approval", "capacity_readiness"],
    "preflight": {
      "completed": false,
      "missing_requirements": ["admin_approval", "capacity_readiness"]
    },
    "request": null
  }
}
```

Mock unverified response, `403`:

```json
{
  "message": "Provisioning can only be requested after email verification, admin approval, and all required preflight checks are complete.",
  "code": "provisioning_not_allowed",
  "eligible": false,
  "missing_requirements": ["email_verified", "admin_approval", "capacity_readiness"],
  "provisioning": {
    "eligible": false,
    "missing_requirements": ["email_verified", "admin_approval", "capacity_readiness"],
    "preflight": {
      "completed": false,
      "missing_requirements": ["admin_approval", "capacity_readiness"]
    },
    "request": null
  }
}
```

Mock locked or rejected response, `423`:

```json
{
  "message": "Provisioning can only be requested after email verification, admin approval, and all required preflight checks are complete.",
  "code": "provisioning_not_allowed",
  "eligible": false,
  "missing_requirements": ["admin_approval", "capacity_readiness", "request_not_rejected"],
  "provisioning": {
    "eligible": false,
    "missing_requirements": ["admin_approval", "capacity_readiness", "request_not_rejected"],
    "preflight": {
      "completed": false,
      "missing_requirements": ["admin_approval", "capacity_readiness"]
    },
    "request": null
  }
}
```

## POST /api/workspace/slug/validate

Admin-only endpoint for validating a generated or manually overridden workspace slug before a workspace is created.

Request:

```json
{
  "slug": "admin",
  "company_name": "Admin Trading LLC",
  "company_id": 456
}
```

Reserved-name response, `422`:

```json
{
  "valid": false,
  "code": "workspace_slug_reserved",
  "message": "This workspace slug is reserved.",
  "slug": "admin",
  "suggested_slug": "admin-workspace",
  "reserved": true,
  "available": false
}
```

Duplicate response, `422`:

```json
{
  "valid": false,
  "code": "workspace_slug_taken",
  "message": "This workspace slug is already in use.",
  "slug": "acme",
  "suggested_slug": "acme-2",
  "reserved": false,
  "available": false
}
```

Available response, `200`:

```json
{
  "valid": true,
  "code": "workspace_slug_available",
  "message": "Workspace slug is available.",
  "slug": "admin-trading-llc",
  "suggested_slug": "admin-trading-llc",
  "reserved": false,
  "available": true
}
```

Admin override storage:

- `access_requests.workspace_slug_override` stores the optional admin-selected slug before workspace creation.
- Workspace creation validates the override. Reserved or duplicate overrides hold workspace creation with `hold_reason` set to the validation code and `suggested_workspace_key` set to a safe alternative.
- When no override is set, the backend generates a normalized slug from `company_name` and appends a numeric suffix for collisions.

## Auth Rate Limiting and Lockout

The backend records auth attempt metadata in `wp_abit_saas_auth_rate_limit_events` using HMAC hashes for IP and user-supplied identifiers. It does not store raw passwords, reset keys, verification tokens, IP addresses, or user agents in the rate-limit table.

Protected surfaces:

| Surface | Default control |
| --- | --- |
| Signup | 10 attempts per hour by hashed email/IP. |
| Login | 20 attempts per 15 minutes by hashed email or username/IP across the custom API and native WordPress login; 5 failed credential attempts per 15 minutes temporarily lock known accounts for 15 minutes. |
| Resend verification | Existing 60-second request cooldown and 5 hourly sends per access request, plus 10 attempts per hour by hashed email/IP. |
| Forgot password | WordPress lost-password requests are limited to 5 attempts per hour by hashed identifier/IP. |
| Reset password | WordPress reset submissions are limited to 5 attempts per hour by user/IP. |
| Admin-sensitive endpoints | Workspace slug validation, provisioning request, and admin qualification decision actions are limited to 30 attempts per 5 minutes by admin user/IP. |

Throttle responses use HTTP `429`, JSON `code: "rate_limited"`, body field `retry_after`, and a `Retry-After` response header. Throttles write `auth_rate_limit_throttled` audit/risk events; failed-login lockouts write `auth_account_lockout_started`.

## Frontend Mock Scenarios

Build mocks around these scenarios before live API integration:

| Scenario | Primary endpoint | Mock status | Expected frontend route |
| --- | --- | --- | --- |
| New registration accepted | `POST /api/auth/register` | `202` | Verify-email prompt. |
| Registration field errors | `POST /api/auth/register` | `422` | Signup form with inline errors. |
| Duplicate access request | `POST /api/auth/register` | `202` | Same verify-email/check-email prompt as a new accepted request. |
| Verification resend accepted or limited | `POST /api/auth/resend-verification` | `202` or `429` | Verify-email prompt with sent or cooldown message. |
| Login invalid credentials | `POST /api/auth/login` | `401` | Sign-in form with generic error. |
| Login unverified | `POST /api/auth/login` | `200`, `route: "verify_email"` | `/auth/verify`. |
| Login onboarding required | `POST /api/auth/login` | `200`, `route: "onboarding"` | `/auth/onboarding`. |
| Login review pending | `POST /api/auth/login` | `200`, `route: "review_pending"` | `/auth/review-pending`. |
| Login approved | `POST /api/auth/login` | `200`, `route: "app"` | `/dashboard`. |
| Login rejected | `POST /api/auth/login` | `200`, `route: "rejected"` | `/auth/rejected`. |
| Login locked | `POST /api/auth/login` | `423` | Locked/support state. |
| Session restore | `GET /api/auth/me` | `200` | Use `gate.next_path`. |
| Session expired | `GET /api/auth/me` | `401` | Sign-in. |
| Provisioning requested | `POST /api/provisioning/request` | `201` or `200` | Show requested/manual provisioning state. |
| Provisioning blocked | `POST /api/provisioning/request` | `403`, `422`, or `423` | Keep the user on the current gate and show an appropriate blocked state. |

## Integration Notes

- Do not hard-code product access from login success alone. Use `status`, `route`, and `gate.product_access` from `/api/auth/me`.
- Treat `status` as the durable backend state and `route`/`next_path` as routing helpers.
- Do not display raw internal error messages for locked, rejected, or provisioning states. Map them to the approved UX copy in `docs/auth-onboarding-ux-copy.md`.
- Keep duplicate registration and invalid login copy generic enough to avoid account enumeration.
- Email verification and password reset token validation endpoints are not currently exposed in this plugin contract; frontend mocks for those screens should use the UX states documented in `docs/auth-onboarding-ux-copy.md` until the corresponding backend endpoints are published.
- Registration consent is captured at signup by the current plugin implementation. The backend resolves legal version fields server-side and stores consent audit evidence with hashed IP and user-agent values.
- `/api/auth/me` maps selected ERP module interests to `onboarding.onboarding_templates`, `onboarding.qualification_tags`, and `onboarding.admin_review_fields`. The backend normalizes finance/inventory aliases to the configured `accounting`/`stock` module keys; selecting finance and inventory yields `erpnext_finance_onboarding`, `erpnext_inventory_onboarding`, `module:finance`, `module:inventory`, and `qualification:finance_inventory_review`.
- The current backend does not expose an onboarding update endpoint in this contract. Existing onboarding fields are read from the access request or legacy user meta by `/api/auth/me`; a future endpoint should update `role`, `company_size`, `industry`, `primary_workflow`, `erp_module_interest`, `current_system`, `timeline`, and `notes`.

## Mock Data Defaults

Use these stable IDs and timestamps in frontend tests unless a test needs distinct values:

```json
{
  "user_id": 123,
  "company_id": 456,
  "access_request_id": 789,
  "consent_audit_record_id": 101,
  "email_verification_token_id": 202,
  "provisioning_request_id": 301,
  "created_at": "2026-06-14 08:00:00",
  "updated_at": "2026-06-14 08:35:00"
}
```

## Contract Acceptance Checklist

- Request and response examples are available for every implemented backend auth endpoint.
- Error codes and frontend handling expectations are documented.
- Canonical account states, routes, and product-access gates are documented.
- Provisioning eligibility requirements are mockable before live integration.
- Frontend teams can run against documented mocked responses without a live WordPress session or database.
