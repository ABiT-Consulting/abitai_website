# Signup Multi-Step Flow Decision Record

Task: TASK-2026-01963 / DISC-04
Project: PROJ-0130 abit_ai_website
Status: Approved recommendation pending stakeholder signoff

## Decision

Approve a multi-step signup and onboarding flow for the abit.ai SaaS access request. The form must be grouped into three user-facing steps:

1. Account
2. Company
3. ERP needs

The signup experience must not ship as one long ungrouped page. Each step should collect only the fields needed for that stage, save or submit through the MVP access request model, and move the requester toward email verification, onboarding completion, and admin review.

## Source Context

- `docs/saas-auth-mvp-scope.md`
- `docs/business-customer-personas-lead-qualification.md`
- `docs/signup-company-profile-field-dictionary.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Recommended Step Structure

### Step 1: Account

Purpose: identify the requester, create the access request, and start the verification path.

Fields:

| Field key | UI label | Required | Notes |
| --- | --- | --- | --- |
| `full_name` | Full name | Yes | Used for requester identity and admin review. |
| `business_email` | Business email | Yes | Must be normalized, validated, duplicate-checked, and verified before product access. |
| `password` | Password | Yes for credential signup | Required when the initial signup also creates credentials. |

Outcome:

- Create the access request with `review_status = pending_email_verification`.
- Create the user identity record if credential signup is part of launch implementation.
- Send a single-use email verification link.
- Do not ask for detailed ERP needs before the requester has provided a valid account identity.

### Step 2: Company

Purpose: capture the business context needed to validate company legitimacy and route admin review.

Fields:

| Field key | UI label | Required | Notes |
| --- | --- | --- | --- |
| `company_name` | Company name | Yes | Supports business validation and duplicate account checks. |
| `country_region` | Country or region | Yes | Store a normalized country or approved routing value. |
| `role` | Your role | Yes | Used to map the requester to a lead qualification persona. |
| `company_size` | Company size | Yes | Use the configured size bands from the field dictionary. |
| `industry` | Industry | Yes | Use the configured industry values from the field dictionary. |

Outcome:

- Save company and requester role context to the access request.
- Give admins enough structured information to evaluate persona, company size, industry, country, and legitimacy.

### Step 3: ERP Needs

Purpose: capture the business problem and module fit without turning signup into a full consulting discovery form.

Fields:

| Field key | UI label | Required | Notes |
| --- | --- | --- | --- |
| `intended_use_case` | Intended use case | Yes | Initial business context from signup scope; may be collected here when the UI favors a shorter first step. |
| `primary_workflow` | Primary workflow | Yes | Must describe a real business workflow. |
| `erp_module_interest` | ERP module interest | Yes | Multi-select using the configured module values. |
| `current_system` | Current system | Optional | Helps admins understand migration or integration expectations. |
| `timeline` | Timeline | Optional | Helps prioritize active evaluations. |
| `notes` | Notes or use case detail | Optional | Captures context not covered by structured fields. |
| `terms_privacy_acceptance` | I accept the current terms and privacy notices | Yes before admin review | Must be explicit, versioned, timestamped, and not pre-checked. |

Outcome:

- Confirm the requester has submitted required onboarding context.
- On completion after email verification, move the request to `pending_admin_review`.
- Present admins with enough ERP context to approve, reject, or request more information.

## Flow Rules

- The user-facing layout must use step grouping, progress indication, and clear step titles matching Account, Company, and ERP needs.
- Email verification remains required before product access. The implementation may allow Company and ERP needs to be completed either before or after email verification, but the request cannot proceed to product access until verification is complete.
- Admin review must receive the same canonical fields regardless of whether implementation stores draft progress after each step or submits all onboarding fields at the end.
- Required fields must follow the validation, storage target, and owner rules in `docs/signup-company-profile-field-dictionary.md`.
- Optional fields must stay optional for MVP approval unless a later stakeholder decision changes the qualification model.
- If the requester abandons the flow, saved partial records must retain a status that does not enter the admin approval queue until required fields and email verification are complete.

## Rationale

A three-step flow keeps the initial request approachable while preserving the structured data required for lead qualification and admin review. Account fields establish identity and contactability first. Company fields confirm business context and routing information. ERP needs fields collect the workflow and module fit only after the requester has provided enough identity and company context.

This structure also creates a direct implementation contract for the acceptance test: the form is intentionally grouped into steps and must not be delivered as one long ungrouped page.

## Rejected Alternative

Do not implement a single-page signup form containing all account, company, and ERP needs fields in one uninterrupted list.

Reasons:

- It increases perceived effort before the requester understands the access flow.
- It mixes identity, company validation, and ERP qualification concerns.
- It makes the ERP acceptance test fail because stakeholders explicitly require confirmation that the form will not ship as one long ungrouped page.

## Acceptance Criteria

- Stakeholders sign off on Account, Company, and ERP needs as the approved step grouping.
- Implementation tasks design the signup UI as a multi-step flow, not a single ungrouped form.
- Required fields from the field dictionary are assigned to one of the three approved steps.
- Completion of the steps can produce the MVP status progression defined in `docs/saas-auth-mvp-scope.md`.
- Admin review receives persona, company size, industry, country, ERP module interest, and supporting requester context required by the lead qualification brief.
