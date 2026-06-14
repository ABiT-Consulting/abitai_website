# Auth and Onboarding UX Copy

Task: TASK-2026-01975 / UX-07
Project: PROJ-0130 abit_ai_website
Status: Approved copy baseline

## Objective

Provide the approved headings, labels, placeholders, helper text, button text, and error messages for the abit.ai authentication and onboarding flow.

This copy is written for business buyers and operations teams. It should sound direct, calm, and review-aware. User-facing copy must not mention implementation details, infrastructure, internal status keys, or provisioning mechanics.

## Tone Rules

- Use `access request`, `workspace`, `review team`, and `business email` in user-facing copy.
- Use `ERP` only when asking about business systems, modules, or workflows.
- Do not use internal platform, security, or provisioning terminology in user-facing copy.
- Do not promise access before verification, onboarding, and review are complete.
- Keep credential and recovery errors generic enough to avoid disclosing whether an account exists.

## Sign In

| Element | Approved copy |
| --- | --- |
| Heading | `Sign in to your workspace` |
| Intro | `View your access request, onboarding steps, and workspace status.` |
| Email label | `Business email` |
| Email placeholder | `jane@company.com` |
| Password label | `Password` |
| Password placeholder | `Enter your password` |
| Remember label | `Remember this device` |
| Forgot link | `Forgot password?` |
| Primary button | `Sign in` |
| Loading button | `Signing in...` |
| Signup link | `New to abit.ai? Start access request` |
| Success message | `Signed in. Redirecting...` |
| Generic error | `We could not sign you in with those details. Check your email and password, then try again.` |
| Email format error | `Enter a valid email address.` |
| Missing password error | `Enter your password.` |

## Signup Account Step

| Element | Approved copy |
| --- | --- |
| Step label | `Account` |
| Heading | `Start access request` |
| Intro | `Tell us who should receive updates for this business request.` |
| Full name label | `Full name` |
| Full name placeholder | `Jane Ahmed` |
| Business email label | `Business email` |
| Business email placeholder | `jane@company.com` |
| Password label | `Password` |
| Password placeholder | `Create a password` |
| Password helper | `Minimum 12 characters.` |
| Primary button | `Continue` |
| Loading button | `Creating request...` |
| Sign-in link | `Already requested access? Sign in` |
| Required field error | `Complete this field to continue.` |
| Email format error | `Enter a valid business email address.` |
| Duplicate request error | `A request may already exist for this email. Sign in or check your inbox for the next step.` |
| Weak password error | `Use at least 12 characters and avoid common passwords.` |
| Request creation error | `We could not start your access request right now. Please try again.` |

## Company Step

| Element | Approved copy |
| --- | --- |
| Step label | `Company` |
| Heading | `Company profile` |
| Intro | `This helps us route your request for review.` |
| Company name label | `Company name` |
| Company name placeholder | `ABiT Trading LLC` |
| Country label | `Country or region` |
| Country placeholder | `Select country or region` |
| Role label | `Your role` |
| Role placeholder | `Select your role` |
| Company size label | `Company size` |
| Company size placeholder | `Select company size` |
| Industry label | `Industry` |
| Industry placeholder | `Select industry` |
| Primary button | `Continue` |
| Loading button | `Saving company...` |
| Back button | `Back` |
| Required select error | `Choose an option to continue.` |
| Company name error | `Enter a company name, not a website or markup.` |

## ERP Needs Step

| Element | Approved copy |
| --- | --- |
| Step label | `ERP needs` |
| Heading | `ERP needs` |
| Intro | `Tell us which workflow you want to improve first.` |
| Intended use label | `Intended use case` |
| Intended use placeholder | `Describe the business outcome you need.` |
| Primary workflow label | `Primary workflow` |
| Primary workflow placeholder | `Example: purchase request to supplier order to stock receipt and invoice matching.` |
| Module interest label | `ERP module interest` |
| Module helper | `Select every area you want included in the review.` |
| Current system label | `Current system` |
| Current system placeholder | `Example: spreadsheets, Tally, Zoho, or another system` |
| Timeline label | `Timeline` |
| Timeline placeholder | `Select timeline` |
| Notes label | `Notes or use case detail` |
| Notes placeholder | `Add optional context for the review team.` |
| Consent label | `I accept the current Terms of Service and Privacy Notice.` |
| Primary button | `Submit for review` |
| Loading button | `Submitting...` |
| Back button | `Back` |
| Success message | `Request submitted for review.` |
| Workflow length error | `Describe the workflow in at least 20 characters.` |
| Module required error | `Select at least one ERP module interest.` |
| Not sure helper | `Use the workflow field to describe the process you need help with.` |
| Consent error | `Accept the current terms and privacy notices to submit for review.` |
| Legal update warning | `The legal notices were updated. Review and accept the current versions to continue.` |

## Email Verification

| State | Heading | Body | Primary action | Secondary actions |
| --- | --- | --- | --- | --- |
| Verification required | `Verify your email to continue` | `We need to confirm your business email before you can continue the access request.` | `Resend verification email` | `Change email`, `Sign out` |
| Email sent | `Check your email` | `We sent a verification link to:` | `Open email app` | `Resend verification email`, `Change email`, `Sign in` |
| Verified | `Email verified` | `Your business email has been confirmed.` | `Continue access request` | `Sign in with another account` |
| Expired link | `Verification link expired` | `This link can no longer verify your email. Request a new verification email to continue.` | `Send a new verification email` | `Use a different email`, `Back to sign in` |
| Link cannot be used | `Verification link cannot be used` | `We could not verify your email with this link. Request a new email if you still need to verify.` | `Send verification email` | `Back to sign in`, `Contact support` |
| Already verified | `Email already verified` | `This email address is already confirmed.` | `Continue access request` | `Back to sign in` |

Shared resend copy:

| State | Approved copy |
| --- | --- |
| Loading | `Sending...` |
| Success | `Verification email sent. Check your inbox and spam folder.` |
| Rate limited | `Please wait before requesting another verification email.` |
| Unknown email helper | `Enter your business email and we will send a new verification link if a request is eligible.` |

## Password Recovery

| State | Heading | Body | Primary action | Secondary actions |
| --- | --- | --- | --- | --- |
| Request reset | `Reset your password` | `Enter your email and we will send reset instructions if the account is eligible.` | `Send reset instructions` | `Back to sign in` |
| Request accepted | `Check your email` | `If an eligible account exists, reset instructions will be sent to that address.` | `Back to sign in` | `Send another reset request` |
| Checking link | `Checking reset link` | `Please wait while we check whether this reset link can be used.` | `Validating...` | None |
| Set password | `Create a new password` | `Use a strong password for your abit.ai account.` | `Save new password` | `Back to sign in` |
| Expired link | `Reset link expired` | `This link can no longer reset your password. Request a new reset email to continue.` | `Send a new reset email` | `Back to sign in` |
| Link cannot be used | `Reset link cannot be used` | `We could not reset your password with this link. Request a new reset email if you still need to change your password.` | `Send reset email` | `Back to sign in`, `Contact support` |
| Success | `Password updated` | `Your password has been changed. Sign in with your new password to continue.` | `Back to sign in` | None |

Password recovery field and error copy:

| Element | Approved copy |
| --- | --- |
| Email label | `Email address` |
| Email placeholder | `jane@company.com` |
| New password label | `New password` |
| Confirm password label | `Confirm new password` |
| Password helper | `Minimum 12 characters.` |
| Sending button | `Sending...` |
| Saving button | `Saving...` |
| Invalid email error | `Enter a valid email address.` |
| Rate-limit error | `Please wait before requesting another reset email.` |
| Weak password error | `Use at least 12 characters and avoid common passwords.` |
| Mismatch error | `Passwords do not match.` |
| Required field error | `Complete this field to continue.` |

## Review And Access States

| State | Heading | Body | Primary action | Secondary actions |
| --- | --- | --- | --- | --- |
| Review pending | `Request submitted for review` | `The review team has your company profile and ERP needs. We will email you when the next step is ready.` | `Back to website` | `Sign out` |
| More information needed | `More information needed` | `The review team needs a little more detail before a decision can be made.` | `Update request` | `Contact support`, `Sign out` |
| Approved | `Access approved` | `Your workspace access is ready.` | `Open workspace` | `Contact support` |
| Not approved | `Access request not approved` | `We cannot provide workspace access for this request at this time.` | `Contact support` | `Back to website` |
| Locked or unavailable | `Access unavailable` | `This account cannot continue right now. Contact support for help.` | `Contact support` | `Back to sign in` |

## Acceptance Checklist

- Headings, labels, placeholders, helper text, button text, and errors are specified for sign-in, signup, verification, password recovery, onboarding, and review states.
- User-facing copy uses business language and avoids implementation, provisioning, security, and internal status terminology.
- Copy avoids promising workspace access until approval.
- Credential, reset, and duplicate-request messages avoid account enumeration.
- Existing UX wireframes can reference this file as the approved copy baseline.
