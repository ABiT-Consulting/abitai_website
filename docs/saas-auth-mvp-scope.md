# SaaS Auth MVP Scope

Task: TASK-2026-01959 / DISC-01
Project: PROJ-0130 abit_ai_website
Status: Pending project owner approval

## Objective

Confirm the minimum launch scope for SaaS authentication on the abit.ai website. The MVP must let a prospective SaaS user request access, prove control of their email address, recover access, complete the required onboarding gate, and enter an admin review queue before any paid or provisioned SaaS workspace is activated.

Full ERP tenant provisioning is explicitly out of scope for launch readiness and must not block dependent implementation tasks.

## In Scope

### Signup

- Public signup entry point is available from the website.
- Signup captures name, business email, company name, country or region, and intended use case.
- Signup rejects malformed email addresses and obvious duplicate pending requests for the same email.
- Signup stores the request with a status of `pending_email_verification`.
- Signup sends an email verification message with a single-use, expiring verification link.

### Login

- Verified users can log in with email and password.
- Unverified users are blocked from product access and prompted to verify email.
- Users pending admin review can authenticate, but only see the onboarding or review-pending state.
- Failed login attempts return a generic error that does not disclose whether the email exists.

### Email Verification

- Verification links are single-use and expire.
- Successful verification changes the request status from `pending_email_verification` to `onboarding_required`.
- Expired or consumed verification links offer a resend path.
- Verification status is visible to admins reviewing access requests.

### Password Reset

- Users can request a password reset by email.
- Reset emails use a single-use, expiring token.
- Reset request responses are generic and do not disclose whether the email exists.
- A successful reset invalidates the used token and allows the user to log in with the new password.

### Onboarding Gate

- Verified users must complete required onboarding fields before reaching the SaaS application.
- Required onboarding fields for MVP are company size, role, primary workflow, and acceptance of current terms or privacy notices.
- Completing onboarding changes the request status to `pending_admin_review`.
- Users who have completed onboarding but are not approved see a review-pending state.

### Admin Review

- Admins can view access requests by status.
- Admins can inspect submitted signup, verification, and onboarding details.
- Admins can approve, reject, or request more information.
- Approval changes the user status to `approved_for_mvp_access`.
- Rejection keeps the user out of the SaaS application and records an admin-visible reason.
- Admin review does not require a fully provisioned ERP tenant.

## Out of Scope for MVP Launch

- Automated ERPNext tenant creation.
- Automated billing, subscription plans, invoices, or payment capture.
- Multi-tenant workspace administration beyond the approved access flag.
- Organization-level role management beyond the first approved user.
- Social login, SSO, passkeys, MFA, or SCIM.
- Self-service deletion and data export workflows beyond baseline privacy contact handling.

## Launch Dependency Decision

No task depends on full ERP tenant provisioning as a launch blocker. MVP launch readiness is met when the authentication and review flow can move a user through these states:

1. `pending_email_verification`
2. `onboarding_required`
3. `pending_admin_review`
4. `approved_for_mvp_access` or `rejected`

After approval, the user may be routed to a manually prepared MVP experience, a waitlist-approved landing state, or a limited SaaS application shell while ERP tenant provisioning remains manual or separately tracked.

## Success Metrics

- At least 95% of valid signup submissions create a pending verification record and send a verification email without manual intervention.
- At least 90% of verification emails are delivered to the recipient mail server within 5 minutes.
- At least 80% of users who click a valid verification link successfully reach the onboarding gate.
- At least 90% of completed onboarding submissions appear in the admin review queue within 1 minute.
- Admins can approve or reject an access request in under 2 minutes using the review information captured by the MVP.
- Password reset requests send reset emails within 5 minutes and successful resets allow login with the new password.
- No launch-critical task is blocked by automated ERP tenant provisioning.

## Acceptance Criteria

- Project owner approves this MVP scope.
- Signup, login, email verification, password reset, onboarding gate, and admin review are covered by implementation tasks.
- Dependent implementation tasks treat ERP tenant provisioning as manual, deferred, or separately scoped.
- The MVP status model is sufficient for implementation planning and QA scenario design.

## Open Items for Owner Approval

- Confirm whether the public signup entry point should be broadly visible or limited to a private invite link for the first release.
- Confirm the exact email sender domain and operational owner for deliverability monitoring.
- Confirm which admin role or team owns approve, reject, and request-more-information decisions.
