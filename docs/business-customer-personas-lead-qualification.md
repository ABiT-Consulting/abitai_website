# Business Customer Personas and Lead Qualification

Task: TASK-2026-01961 / DISC-02
Project: PROJ-0130 abit_ai_website
Status: Pending project owner approval

## Objective

Define the initial business customer personas and lead qualification rules for the abit.ai website SaaS access flow. The rules must give admins enough review context to decide whether a verified signup should be approved for MVP access, rejected, or sent back for more information.

The MVP admin review must map each completed onboarding submission to persona, company size, industry, country, and ERP module interest.

## Target Customer Personas

### Owner or Executive Sponsor

- Typical title: owner, founder, managing director, general manager, CEO, partner.
- Primary concern: business control, visibility, profitability, growth, customer service, and reducing dependence on informal processes.
- Buying trigger: the company has outgrown spreadsheets, disconnected tools, or manual approvals and needs a clearer operating system.
- High-value signals: authority to approve budget, clear business pain, active ERP or process improvement initiative, and willingness to assign internal owners.
- Review focus: confirm company legitimacy, decision authority, business urgency, and whether the requested modules match executive-level transformation needs.
- Recommended module interests: CRM, Sales, Accounting, Projects, HR, Support, Analytics, or full ERP evaluation.

### Finance Lead

- Typical title: finance manager, accountant, head of finance, CFO, controller, accounts lead.
- Primary concern: accurate books, invoicing, collections, cash flow, tax readiness, approvals, auditability, and management reporting.
- Buying trigger: finance work is delayed by manual entries, weak invoice tracking, poor stock or project cost visibility, or limited reporting.
- High-value signals: owns finance process, can describe current accounting gaps, has monthly close or compliance pressure, and asks about integrations or controls.
- Review focus: confirm accounting need, company size, country-specific compliance expectations, and whether finance is the primary MVP workflow.
- Recommended module interests: Accounting, Selling, Buying, Payroll, Expense Claims, Assets, Inventory valuation, and Reports.

### Operations Lead

- Typical title: operations manager, service manager, project manager, procurement lead, warehouse manager, production manager.
- Primary concern: delivery reliability, stock visibility, purchasing, fulfillment, work allocation, service response times, and operational bottlenecks.
- Buying trigger: operational teams cannot see work status, inventory, purchasing, production, projects, or service tasks in one place.
- High-value signals: specific workflow volume, active team coordination problem, measurable delays or rework, and need for cross-department visibility.
- Review focus: confirm operational workflow, complexity, ERP module fit, and whether the business has enough repeatable process for an MVP pilot.
- Recommended module interests: Stock, Buying, Selling, Manufacturing, Projects, Helpdesk or Support, Quality, Maintenance, and CRM.

### IT or Admin Buyer

- Typical title: IT manager, systems administrator, digital transformation lead, business applications manager, office administrator.
- Primary concern: access control, implementation effort, data migration, integrations, security, user support, and maintainability.
- Buying trigger: the organization needs a configurable ERPNext-based system but wants guided evaluation before a full rollout.
- High-value signals: owns system selection or administration, can identify current systems, asks about roles or integrations, and can coordinate pilot users.
- Review focus: confirm technical ownership, implementation readiness, integration expectations, and whether the request is for real business adoption rather than casual testing.
- Recommended module interests: Users and Permissions, Website or Portal, CRM, Integrations, Data Import, Workflow, Reports, and the business modules requested by internal stakeholders.

## Lead Qualification Fields

The MVP signup and onboarding flow should capture enough structured data for admin review without turning the first request into a full discovery form.

| Field | Source | Required | Admin use |
| --- | --- | --- | --- |
| Full name | Signup | Yes | Identify requester and personalize follow-up. |
| Business email | Signup | Yes | Verify domain, contactability, and duplicate requests. |
| Company name | Signup | Yes | Confirm business identity and existing account overlap. |
| Country or region | Signup | Yes | Route compliance, language, timezone, and market-fit review. |
| Role | Onboarding | Yes | Map requester to one of the target personas. |
| Company size | Onboarding | Yes | Estimate complexity, implementation effort, and MVP fit. |
| Industry | Onboarding | Yes | Evaluate module fit and sector priority. |
| Primary workflow | Onboarding | Yes | Understand the immediate business problem. |
| ERP module interest | Onboarding | Yes | Route review and follow-up by capability area. |
| Current system | Onboarding | Optional | Understand migration and integration expectations. |
| Timeline | Onboarding | Optional | Prioritize urgent, active buying cycles. |
| Notes or use case detail | Onboarding | Optional | Capture context that structured fields miss. |

## Normalized Review Values

### Persona

Admin review should map the submitted role to one primary persona:

- `owner_executive`
- `finance_lead`
- `operations_lead`
- `it_admin_buyer`
- `other_business_user`

If a role could map to more than one persona, admins should choose the persona most aligned with the submitted primary workflow and ERP module interest.

### Company Size

Use a simple size band that supports lead triage without requiring exact employee count:

- `1_10`
- `11_50`
- `51_200`
- `201_500`
- `501_plus`

Best early MVP fit is typically `11_50` through `201_500`, unless a smaller company has a clear urgent workflow or a larger company is explicitly requesting a limited pilot.

### Industry

Use broad industry options first, with an `other` option for admin review:

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

### Country

Store country as a normalized country value, not free text, while allowing the UI label to show the local country name. Country should support market routing, compliance notes, and timezone-aware follow-up.

### ERP Module Interest

Allow one or more module interests:

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

If `not_sure` is selected, the primary workflow and notes become more important for admin review.

## Qualification Rules

### Qualified for Admin Approval

A lead is qualified for MVP access when all of the following are true:

- Email is verified and uses a business domain, or the admin can clearly validate the business despite a common email domain.
- Company name, country, role, company size, industry, and ERP module interest are present.
- Persona maps to owner or executive sponsor, finance lead, operations lead, IT or admin buyer, or another business user with a clear implementation role.
- Primary workflow describes a real business process rather than generic product exploration.
- Module interest matches the stated role or workflow.
- Company size and requested workflow are realistic for guided MVP access.

### Request More Information

Admins should request more information when any of the following apply:

- The role is unclear and cannot be mapped to a persona.
- The company size, industry, country, or ERP module interest is missing or contradictory.
- The requester selected `not_sure` and did not provide enough workflow detail.
- The request appears valid but the admin needs implementation context, such as current system, timeline, or pilot users.
- The business case is plausible but the selected modules do not match the stated pain.

### Reject or Defer

Admins should reject or defer MVP access when any of the following apply:

- The signup is spam, a test submission, or unrelated to business ERP evaluation.
- The requester cannot be contacted or the company cannot be reasonably identified.
- The submission is from a student, competitor, vendor solicitation, or individual consumer with no business implementation intent.
- The requested need is outside the MVP scope, such as custom enterprise procurement, unsupported compliance commitments, or immediate automated tenant provisioning.
- The lead requires modules or implementation services that the MVP cannot responsibly support.

## Scoring Guide

Use scoring as an admin aid, not as an automatic approval rule.

| Signal | Strong fit | Weak fit |
| --- | --- | --- |
| Persona | Decision-maker or process owner | Unclear role or no business ownership |
| Company size | 11 to 200 employees for initial MVP | No business entity or very high complexity rollout |
| Industry | Clear fit for ERPNext workflows | Industry need is unrelated to ERP modules |
| Country | In a supported follow-up region | Country creates unsupported compliance or support requirements |
| Module interest | Matches role and primary workflow | Random modules or no clear operational need |
| Timeline | Active evaluation or near-term pilot | No timeline or casual browsing |

Recommended admin outcomes:

- 5 or more strong signals: approve if required fields are complete.
- 3 to 4 strong signals: request more information if any required context is weak.
- 2 or fewer strong signals: reject or defer unless owner approval overrides the default.

## Admin Review Field Mapping

The admin review queue must expose these fields directly so the ERP acceptance test can verify them:

| Review field | Source field | Normalization rule |
| --- | --- | --- |
| Persona | Role, primary workflow, ERP module interest | Map to `owner_executive`, `finance_lead`, `operations_lead`, `it_admin_buyer`, or `other_business_user`. |
| Company size | Company size | Store one configured size band. |
| Industry | Industry | Store one configured industry value. |
| Country | Country or region | Store normalized country value. |
| ERP module interest | ERP module interest | Store one or more configured module values. |

Admins may also see full name, business email, company name, current system, timeline, notes, verification status, and request status, but those fields are supporting context rather than the required persona qualification mapping.

## Acceptance Criteria

- Owner, finance lead, operations lead, and IT/admin buyer personas are defined with concerns, triggers, signals, review focus, and likely module interests.
- Lead qualification rules cover approve, request-more-information, and reject or defer decisions.
- Admin review fields map to persona, company size, industry, country, and ERP module interest.
- Required onboarding fields align with the DISC-01 MVP admin review scope.

## Open Items for Owner Approval

- Confirm supported launch countries or regions for initial admin routing.
- Confirm whether common email domains are allowed when the company is otherwise verifiable.
- Confirm whether `other_business_user` can be approved by admins or requires owner review.
