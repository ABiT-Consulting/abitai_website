# Multi-Step Signup Wireframes

Task: TASK-2026-01970 / UX-03
Project: PROJ-0130 abit_ai_website
Status: Draft for UX review

## Objective

Define mobile-first wireframes for the abit.ai SaaS access request signup flow. The design must cover the Account, Company, ERP module interest, and Check email screens while preserving the approved multi-step structure and every required field from the signup field dictionary.

## Source Context

- `docs/saas-auth-mvp-scope.md`
- `docs/auth-user-flow-map.md`
- `docs/signup-multi-step-flow-decision-record.md`
- `docs/signup-company-profile-field-dictionary.md`
- `docs/legal-consent-privacy-capture-requirements.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Shared Layout Pattern

All signup screens use a single-column form surface on mobile and a constrained centered form on tablet and desktop.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Step 1 of 3                                      |
| [progress: Account > Company > ERP needs]        |
|                                                  |
| Screen title                                     |
| Short operational helper copy.                   |
|                                                  |
| [ Field label                                  ] |
| [ Field input                                  ] |
|                                                  |
| [ Primary action                              ] |
| Secondary action or sign-in link                 |
+--------------------------------------------------+
```

### Responsive Rules

| Area | Mobile requirement |
| --- | --- |
| Page width | Form container uses `width: 100%`, `max-width: 440px`, and inline padding of 16px. |
| Inputs | Inputs, selects, textareas, checkboxes, and buttons use `width: 100%` or wrap inside the container. |
| Progress | Step labels may stack or abbreviate, but must not require horizontal scroll. |
| Module choices | ERP module interest options wrap into one-column chips or checkbox rows on narrow screens. |
| Footer actions | Primary and secondary actions stack vertically on mobile. |
| Long text | Helper copy and legal labels wrap naturally; no fixed-width rows. |

## Screen 1: Account

Purpose: identify the requester, create the access request, create credentials when enabled, and send the email verification message.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Step 1 of 3                                      |
| Account                                          |
| Company                                          |
| ERP needs                                        |
|                                                  |
| Start SaaS access request                        |
| Tell us who should receive the verification      |
| email for this business request.                 |
|                                                  |
| Full name *                                      |
| [ Jane Ahmed                                  ] |
|                                                  |
| Business email *                                 |
| [ jane@company.com                            ] |
|                                                  |
| Password *                                       |
| [ ************                                ] |
| Minimum 12 characters.                           |
|                                                  |
| [ Continue ]                                     |
| Already requested access? Sign in                |
+--------------------------------------------------+
```

### Account Fields

| Field key | UI label | Required | Control |
| --- | --- | --- | --- |
| `full_name` | Full name | Yes | Text input with visible label. |
| `business_email` | Business email | Yes | Email input using the mobile email keyboard. |
| `password` | Password | Yes for credential signup | Password input with strength feedback below the field. |

### Account States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Missing required field | Inline error under the field and error border. | `Complete this field to continue.` |
| Invalid email | Inline error under business email. | `Enter a valid business email address.` |
| Duplicate active request | Error summary above fields; do not reveal sensitive account details beyond the submitted address context. | `A request may already exist for this email. Sign in or check your inbox for the next step.` |
| Weak password | Password helper changes to error treatment. | `Use at least 12 characters and avoid common passwords.` |
| Loading | Disable fields and primary action; preserve entered values. | Button label: `Creating request...` |

## Screen 2: Company

Purpose: collect company context needed for legitimacy checks, routing, and persona mapping.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Step 2 of 3                                      |
| Account [done]                                   |
| Company                                          |
| ERP needs                                        |
|                                                  |
| Company profile                                  |
| This helps us route your request for review.     |
|                                                  |
| Company name *                                   |
| [ ABiT Trading LLC                            ] |
|                                                  |
| Country or region *                              |
| [ United Arab Emirates                       v ] |
|                                                  |
| Your role *                                      |
| [ Operations manager                         v ] |
|                                                  |
| Company size *                                   |
| [ 11-50 employees                            v ] |
|                                                  |
| Industry *                                       |
| [ Trading and distribution                   v ] |
|                                                  |
| [ Continue ]                                     |
| Back                                             |
+--------------------------------------------------+
```

### Company Fields

| Field key | UI label | Required | Control |
| --- | --- | --- | --- |
| `company_name` | Company name | Yes | Text input. |
| `country_region` | Country or region | Yes | Searchable select using normalized countries or approved region values. |
| `role` | Your role | Yes | Select or text-assisted select mapped to review persona. |
| `company_size` | Company size | Yes | Select with configured size bands. |
| `industry` | Industry | Yes | Select with configured industry values. |

### Company Select Values

| Control | Values shown in UX |
| --- | --- |
| Company size | `1-10`, `11-50`, `51-200`, `201-500`, `501+` employees. |
| Industry | Professional services, Trading and distribution, Manufacturing, Retail or ecommerce, Construction or real estate, Healthcare, Education, Nonprofit, Technology, Other. |

### Company States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Missing select value | Inline error under the select. | `Choose an option to continue.` |
| Invalid company name | Inline error under company name. | `Enter a company name, not a website or markup.` |
| Back navigation | Return to Account step with saved values. | No warning unless unsaved changes would be lost. |
| Loading | Disable controls and keep button height stable. | Button label: `Saving company...` |

## Screen 3: ERP Module Interest

Purpose: capture the business workflow, ERP module fit, optional migration context, and required legal consent before the request can enter admin review.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Step 3 of 3                                      |
| Account [done]                                   |
| Company [done]                                   |
| ERP needs                                        |
|                                                  |
| ERP needs                                        |
| Tell us which workflow you want to improve first.|
|                                                  |
| Intended use case *                              |
| [ We need better purchasing and stock visibility |
|   across two warehouses...                    ] |
|                                                  |
| Primary workflow *                               |
| [ Purchase request to supplier order to stock    |
|   receipt and invoice matching...             ] |
|                                                  |
| ERP module interest *                            |
| [ ] Accounting                                   |
| [ ] CRM                                          |
| [ ] Sales                                        |
| [ ] Buying                                       |
| [ ] Stock                                        |
| [ ] Manufacturing                                |
| [ ] Projects                                     |
| [ ] HR and payroll                               |
| [ ] Support or helpdesk                          |
| [ ] Website or portal                            |
| [ ] Reports and analytics                        |
| [ ] Integrations                                 |
| [ ] Full ERP evaluation                          |
| [ ] Not sure                                     |
|                                                  |
| Current system                                   |
| [ spreadsheets and Tally                      ] |
|                                                  |
| Timeline                                         |
| [ 1-3 months                                  v ] |
|                                                  |
| Notes or use case detail                         |
| [ Optional detail for admin review...         ] |
|                                                  |
| [ ] I accept the current Terms of Service and    |
|     Privacy Notice.                              |
|                                                  |
| [ Submit for review ]                            |
| Back                                             |
+--------------------------------------------------+
```

### ERP Needs Fields

| Field key | UI label | Required | Control |
| --- | --- | --- | --- |
| `intended_use_case` | Intended use case | Yes | Textarea, 20-1000 characters. |
| `primary_workflow` | Primary workflow | Yes | Textarea, 20-1000 characters. |
| `erp_module_interest` | ERP module interest | Yes | Multi-select checkbox list or wrapping chips; at least one selected. |
| `current_system` | Current system | Optional | Text input. |
| `timeline` | Timeline | Optional | Select. |
| `notes` | Notes or use case detail | Optional | Textarea. |
| `terms_privacy_acceptance` | I accept the current Terms of Service and Privacy Notice. | Yes before admin review | Unchecked checkbox with links to current legal pages. |

### ERP Needs Select Values

| Control | Values shown in UX |
| --- | --- |
| ERP module interest | Accounting, CRM, Sales, Buying, Stock, Manufacturing, Projects, HR and payroll, Support or helpdesk, Website or portal, Reports and analytics, Integrations, Full ERP evaluation, Not sure. |
| Timeline | Immediate, 1-3 months, 3-6 months, 6+ months, Exploring. |

### ERP Needs States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Missing workflow detail | Inline error under the textarea. | `Describe the workflow in at least 20 characters.` |
| No module selected | Inline error above module choices. | `Select at least one ERP module interest.` |
| `Not sure` selected | Keep selected state visible; require meaningful workflow detail. | Helper: `Use the workflow field to describe the process you need help with.` |
| Missing consent | Inline error below checkbox and block submission. | `Accept the current terms and privacy notices to submit for review.` |
| Legal version changed | Show warning above checkbox; checkbox returns to unchecked. | `The legal notices were updated. Review and accept the current versions to continue.` |
| Loading | Disable controls; preserve entered values. | Button label: `Submitting...` |
| Success | Route to review-pending state only after email verification and required onboarding acceptance. | `Request submitted for review.` |

## Screen 4: Check Email

Purpose: confirm that the account step succeeded, explain that product access is blocked until verification, and provide a resend path.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Check your email                                 |
|                                                  |
| We sent a verification link to:                  |
| jane@company.com                                 |
|                                                  |
| Open the link to verify your email. You can then |
| continue the company and ERP needs steps, or     |
| return here after signing in.                    |
|                                                  |
| [ Open email app ]                               |
| Resend verification email                        |
| Change email                                     |
| Sign in                                          |
+--------------------------------------------------+
```

### Check Email States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Initial sent | Shows submitted email and resend option. | `We sent a verification link to:` |
| Resend loading | Disable resend link and keep screen content stable. | `Sending...` |
| Resend accepted | Inline confirmation near resend action. | `Verification email sent. Check your inbox and spam folder.` |
| Resend rate limited | Inline warning near resend action. | `Please wait before requesting another verification email.` |
| Expired link return | Error summary with resend CTA. | `This verification link expired. Request a new email to continue.` |
| Invalid or consumed link | Error summary with resend CTA. | `This verification link cannot be used. Request a new email if you still need to verify.` |
| Verified success | Brief success state before onboarding route. | `Email verified. Continue your access request.` |

## Mobile No-Scroll Acceptance Notes

The wireframes are designed to avoid horizontal scroll at 320px and wider viewports:

- No screen uses side-by-side form fields on mobile.
- Step labels stack vertically when there is not enough width for a horizontal progress tracker.
- ERP module choices render as one-column checkbox rows on mobile.
- Long legal labels wrap under the checkbox text area and remain inside the form container.
- Textareas use flexible width and fixed minimum height rather than fixed pixel widths.
- Buttons use full-width mobile layout with secondary actions below the primary action.

## Field Coverage Matrix

| Required deliverable item | Covered by |
| --- | --- |
| Account screen | Screen 1: Account. |
| Company screen | Screen 2: Company. |
| ERP module interest screen | Screen 3: ERP Module Interest. |
| Check-email screen | Screen 4: Check Email. |
| Required account fields | `full_name`, `business_email`, `password`. |
| Required company fields | `company_name`, `country_region`, `role`, `company_size`, `industry`. |
| Required ERP fields | `intended_use_case`, `primary_workflow`, `erp_module_interest`, `terms_privacy_acceptance`. |
| Optional ERP fields | `current_system`, `timeline`, `notes`. |
| Mobile no-horizontal-scroll requirement | Shared Layout Pattern and Mobile No-Scroll Acceptance Notes. |

## UX Review Notes

- The Account step creates the pending email verification request and sends the verification email.
- The Check email screen can appear immediately after Account creation, while Company and ERP needs can be completed after verification according to the approved flow map.
- Company and ERP needs must remain grouped steps, not a single long ungrouped form.
- Consent is placed on the final ERP needs step so the accepted legal versions align with the complete onboarding submission to admin review.
- Admin review receives the same canonical fields listed in `docs/signup-company-profile-field-dictionary.md`.
