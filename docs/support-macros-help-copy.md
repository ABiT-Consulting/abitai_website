# Support Macros and Help Copy

Task: `TASK-2026-02034 / LCH-03`
Project: `PROJ-0130 abit_ai_website`
Milestone: `10. Launch and Post-launch Monitoring`
Status: Support replies prepared for launch auth triage.
Source: `PROJ-0130_abit_saas_auth_task_tree.md`

## Objective

Give support a ready-to-use reply set for normal abit.ai SaaS auth issues so verification, login, reset, admin hold, and company profile cases can be handled without engineering.

These macros are for customer replies and first-line notes. They do not replace the launch runbook escalation rules. Use `docs/launch-runbook-rollback-plan.md` for incidents, rollback triggers, and engineering escalation.

## Support Rules

- Keep account, reset, and duplicate-request replies generic enough to avoid confirming whether an email address has an account.
- Never ask for or collect a password, raw verification token, raw reset key, browser cookie, full email headers, raw IP address, or provider payload.
- Ask for safe identifiers only: business email, company name, approximate time of action, screenshot of the visible page without secrets, and the error wording shown on screen.
- Check WordPress admin `Tools > ABiT Signup Review` and `Tools > ABiT Email Observability` before escalating normal delivery, review, or profile questions.
- Escalate to engineering when many customers report the same auth failure, transactional email delivery is broadly failing, an approved user gets unapproved product access, or any secret/token exposure is suspected.
- Treat product access for an unapproved or rejected user as a P0 security incident.

## Triage Checklist

| Issue | Support checks | Normal support action | Escalate when |
| --- | --- | --- | --- |
| Verification email missing | Confirm the requester used their business email, check Email Observability for sanitized delivery status, and check resend cooldown or hourly resend limit. | Send the verification resend macro and advise inbox/spam check plus cooldown wait. | No verification emails are being delivered broadly, provider dashboard shows outage, or Email Observability is unavailable. |
| Verification link expired or cannot be used | Confirm the customer is on the current verification email and not an older link. Do not inspect raw token values. | Send the expired or invalid verification link macro and direct them to request a new email. | Multiple fresh links fail for the same verified delivery, or page exposes token details. |
| Login failure | Confirm they are using the sign-in page, ask for visible error text, check review status in Signup Review, and check whether account lockout/cooldown applies. | Send login macro matched to invalid credentials, verification required, review pending, rejected, or access unavailable. | Status routing appears wrong, lockout does not clear after the expected window, or approved users cannot sign in broadly. |
| Password reset | Check whether reset email delivery was attempted, confirm generic accepted copy appears, and ensure the customer requests a fresh reset email. | Send reset request, expired link, or invalid link macro. | Reset emails fail broadly, reset success does not allow sign-in, or a reset link exposes key/token data. |
| Admin hold | Check Signup Review detail, hold reason, handoff notes, and requested follow-up. | Send admin hold macro with the specific safe next step from admin notes. | Hold reason is missing, sensitive, contradictory, or requires manual data correction. |
| Company profile issue | Check required company fields, ERP needs, module interests, and any "more information needed" decision. | Send company profile update macro with the missing fields listed in plain language. | Customer cannot edit after a support-approved correction path, profile data appears attached to the wrong company, or data isolation is suspected. |

## Verification Macros

### Verification Email Missing

Subject: `Verify your abit.ai email`

Hi {{first_name}},

Thanks for starting your abit.ai access request. If the business email you entered is eligible, a verification email will be sent to that address.

Please check your inbox and spam folder for a message from abit.ai. If you still do not see it, return to the verification page and choose `Resend verification email`. For security, resend requests may ask you to wait before sending another email.

If it still does not arrive, reply with your business email, company name, and the approximate time you requested the email. Do not send passwords, verification links, or token values.

Thanks,  
abit.ai Support

### Verification Link Expired

Subject: `Your verification link has expired`

Hi {{first_name}},

The verification link you opened has expired. Please request a new verification email from the verification page and use the newest message in your inbox.

Older verification emails may stop working after a newer email is sent. If the newest link also fails, reply with your business email, company name, and the time you requested the latest email. Please do not forward the verification link or token.

Thanks,  
abit.ai Support

### Verification Link Cannot Be Used

Subject: `Request a new verification email`

Hi {{first_name}},

That verification link cannot be used. This can happen when a link is older, already used, or replaced by a newer verification email.

Please return to the verification page, choose `Send verification email`, and open the newest email from abit.ai. If the issue continues with a fresh email, send us your business email, company name, and a screenshot of the visible message with any link details hidden.

Thanks,  
abit.ai Support

## Login Macros

### Generic Sign-In Failure

Subject: `Help signing in to abit.ai`

Hi {{first_name}},

We could not sign you in with those details. Please check that you are using the same business email used for your access request and enter your password again.

If you are not sure about the password, use `Forgot password?` on the sign-in page. For security, we cannot view or reset your password manually and will never ask you to send it.

Thanks,  
abit.ai Support

### Email Verification Required

Subject: `Verify your email before signing in`

Hi {{first_name}},

Your access request still needs email verification before you can continue. Please open the latest verification email from abit.ai or request a new verification email from the verification page.

After your business email is verified, sign in again to continue the access request or review status.

Thanks,  
abit.ai Support

### Review Pending

Subject: `Your abit.ai request is under review`

Hi {{first_name}},

Your access request is with our review team. Workspace access is not available until email verification, company profile review, and approval are complete.

We will email you when the next step is ready. If your company details have changed, reply with the updated company name and the specific field that needs correction.

Thanks,  
abit.ai Support

### Access Unavailable Or Locked

Subject: `abit.ai access is unavailable right now`

Hi {{first_name}},

This account cannot sign in right now. This may be temporary after repeated failed sign-in attempts, or it may require a support review.

Please wait 15 minutes before trying again. If the message remains after that, reply with your business email, company name, approximate sign-in time, and the exact message shown on screen. Do not send your password.

Thanks,  
abit.ai Support

## Password Reset Macros

### Reset Instructions Sent

Subject: `Password reset instructions`

Hi {{first_name}},

If an eligible account exists for that email, password reset instructions will be sent to the address entered.

Please check your inbox and spam folder. If you request another reset email, use the newest message because older reset links may no longer work.

Thanks,  
abit.ai Support

### Reset Link Expired

Subject: `Your reset link has expired`

Hi {{first_name}},

The reset link you opened has expired. Please return to the password reset page and request a new reset email.

Use the newest reset email from abit.ai. If you continue to see the expired-link message, reply with your business email, company name, and the approximate time you requested the latest reset email. Do not send the reset link or key.

Thanks,  
abit.ai Support

### Reset Link Cannot Be Used

Subject: `Request a new password reset email`

Hi {{first_name}},

That reset link cannot be used. This can happen when a link is older, already used, or replaced by a newer reset email.

Please request a new password reset email and open the newest message from abit.ai. For security, support cannot set or view your password.

Thanks,  
abit.ai Support

## Admin Hold Macro

Subject: `We need one more step for your abit.ai request`

Hi {{first_name}},

Your abit.ai access request is currently on hold while our team reviews the information below:

{{safe_hold_reason_or_next_step}}

Please reply with the requested business details, or confirm the corrected information we should use. Do not send passwords, private credentials, or confidential system exports.

Once we receive the requested information, the review team will update your request.

Thanks,  
abit.ai Support

## Company Profile Macros

### Missing Or Incomplete Company Profile

Subject: `Update your company profile for abit.ai review`

Hi {{first_name}},

We need a little more company information before the review team can continue your abit.ai access request.

Please sign in and update these fields:

{{missing_company_fields}}

The review team uses this information to understand your company, ERP needs, and next steps. Workspace access remains unavailable until the request is reviewed and approved.

Thanks,  
abit.ai Support

### Company Profile Correction

Subject: `Company profile correction received`

Hi {{first_name}},

Thanks for sending the updated company profile details. We have noted the correction for review:

{{safe_company_profile_summary}}

The review team will continue from the updated information and email you when the next step is ready.

Thanks,  
abit.ai Support

## Internal Notes Template

Use this format in support tooling or handoff notes. Keep it free of secrets.

```text
Customer issue:
Visible state/message:
Business email:
Company name:
Approximate customer action time:
Support checks completed:
Macro sent:
Next support action:
Escalation needed: yes/no
Escalation reason:
```

## Acceptance Checklist

- Verification macros cover missing email, expired link, and unusable link without asking for raw tokens.
- Login macros cover invalid sign-in, verification-required, review-pending, and access-unavailable cases without account enumeration or password collection.
- Password reset macros cover accepted request, expired link, and unusable link without disclosing account existence.
- Admin hold macro lets support communicate safe next steps from admin notes.
- Company profile macros cover missing fields and corrections for normal review cases.
- Triage guidance tells support when normal cases can be handled without engineering and when to escalate.
