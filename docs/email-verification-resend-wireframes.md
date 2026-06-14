# Email Verification and Resend Wireframes

Task: TASK-2026-01971 / UX-04
Project: PROJ-0130 abit_ai_website
Status: Draft for UX review

## Objective

Define the verification-required, email-sent, success, expired, failed, and already-verified screens for the abit.ai SaaS authentication MVP. Each state must give the user a clear recovery action and must not show any full ERP access call to action before email verification is complete.

## Source Context

- `docs/saas-auth-mvp-scope.md`
- `docs/auth-user-flow-map.md`
- `docs/sign-in-wireframe-and-states.md`
- `docs/signup-multi-step-wireframes.md`
- `docs/auth-onboarding-ux-copy.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Shared Verification Layout

All verification states use the same compact auth surface as sign-in and signup follow-up screens. The screen is centered on tablet and desktop, single-column on mobile, and keeps actions stacked at narrow widths.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Verification state title                         |
| Short status or recovery explanation.            |
|                                                  |
| user@company.com                                 |
|                                                  |
| [ Primary recovery action ]                      |
| Secondary action                                 |
| Tertiary support or sign-in action               |
+--------------------------------------------------+
```

### Shared Rules

| Area | UX requirement |
| --- | --- |
| Email display | Show the target email when it is already known from signup, sign-in, or token metadata. Mask only if required by security policy. |
| Primary action | Use the action that most directly lets the user recover or continue. |
| Product access | Do not show `Go to ERP`, `Open workspace`, `Continue to dashboard`, or any equivalent full ERP access CTA until verification has succeeded. |
| Resend behavior | Resend creates a new single-use verification token only when the request is eligible and not rate limited. |
| Support behavior | Support links are secondary and should not bypass verification. |
| Accessibility | Move focus to the status summary after token success, expiry, failure, or resend result. |

## Screen 1: Verification Required

Purpose: shown after sign-in when credentials are valid but the access request is still `pending_email_verification`, or when a signed-in user tries to reach a protected onboarding or product route before verifying.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Verify your email to continue                    |
| We need to confirm your business email before    |
| you can continue the access request.             |
|                                                  |
| Verification email sent to:                      |
| jane@company.com                                 |
|                                                  |
| [ Resend verification email ]                    |
| Change email                                     |
| Sign out                                         |
+--------------------------------------------------+
```

### Verification Required Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Verify your email to continue` |
| Body copy | Make the verification block explicit before onboarding or product access. |
| Primary action | `Resend verification email` |
| Secondary action | `Change email` returns to the allowed account-safe email correction path. |
| Tertiary action | `Sign out` ends the session without implying access. |
| Blocked action | No full ERP access CTA appears in this state. |

### Verification Required States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Initial blocked state | Neutral status panel with visible email and resend button. | `We need to confirm your business email before you can continue the access request.` |
| Resend loading | Disable resend, change email, and sign out only if the request cannot safely be interrupted. Keep layout stable. | Button label: `Sending...` |
| Resend accepted | Inline success message below the primary action. | `Verification email sent. Check your inbox and spam folder.` |
| Resend rate limited | Inline warning below the primary action with cooldown-safe copy. | `Please wait before requesting another verification email.` |

## Screen 2: Email Sent

Purpose: shown immediately after account creation or an accepted resend request. It confirms delivery intent and tells the user what they can do next without suggesting product access.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Check your email                                 |
| We sent a verification link to:                  |
| jane@company.com                                 |
|                                                  |
| Open the link in that email to verify your       |
| address. Verification is required before your    |
| access request can continue.                     |
|                                                  |
| [ Open email app ]                               |
| Resend verification email                        |
| Change email                                     |
| Sign in                                          |
+--------------------------------------------------+
```

### Email Sent Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Check your email` |
| Primary action | `Open email app` when device support exists. On desktop this may open the default mail client or be hidden if unreliable. |
| Secondary action | `Resend verification email` remains available, subject to rate limits. |
| Recovery action | `Change email` lets the user correct a typo through an account-safe flow. |
| Return action | `Sign in` supports users who left and came back later. |
| Blocked action | No full ERP access CTA appears in this state. |

### Email Sent States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Initial sent | Show email address prominently under the delivery sentence. | `We sent a verification link to:` |
| Resend accepted | Keep user on the same screen and add confirmation near resend. | `Verification email sent. Check your inbox and spam folder.` |
| Email app unavailable | Hide or disable `Open email app`; keep resend, change email, and sign-in actions. | No additional copy required. |

## Screen 3: Verification Success

Purpose: shown after a valid, unexpired, unused token is consumed. It confirms verification and routes the user to the next allowed step, usually onboarding.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Email verified                                   |
| Your business email has been confirmed.          |
|                                                  |
| jane@company.com                                 |
|                                                  |
| [ Continue access request ]                      |
| Sign in with another account                     |
+--------------------------------------------------+
```

### Success Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Email verified` |
| Primary action | `Continue access request` routes to onboarding when `review_status = onboarding_required`. |
| Secondary action | `Sign in with another account` is available for users who verified the wrong browser session. |
| Status update | Token is consumed, `email_verified_at` is set, and request status advances to `onboarding_required`. |
| Product access | This screen may continue the access request, but still must not offer full ERP access unless a later status check confirms approval. |

### Success States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Verified and route ready | Success panel with primary action. | `Your business email has been confirmed.` |
| Auto-routing | Briefly disable actions while the next route is resolved. | Button label: `Continuing...` |
| Onboarding unavailable | Keep success status and offer sign-in. | `Email verified. Sign in to continue your access request.` |

## Screen 4: Expired Verification Link

Purpose: shown when the user opens a validly formed verification link whose token has expired. The screen must not verify the email and must offer a new email path.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Verification link expired                        |
| This link can no longer verify your email.       |
| Request a new verification email to continue.    |
|                                                  |
| jane@company.com                                 |
|                                                  |
| [ Send a new verification email ]                |
| Use a different email                            |
| Back to sign in                                  |
+--------------------------------------------------+
```

### Expired Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Verification link expired` |
| Primary action | `Send a new verification email` |
| Secondary action | `Use a different email` routes to the account-safe email correction path. |
| Return action | `Back to sign in` |
| Status behavior | Do not mark email verified. Keep status as `pending_email_verification`. |
| Blocked action | No full ERP access CTA appears in this state. |

### Expired States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Expired token | Warning summary above email and resend button. | `This link can no longer verify your email. Request a new verification email to continue.` |
| Resend loading | Disable primary action and keep layout stable. | Button label: `Sending...` |
| Resend accepted | Keep user on this screen or route to Email Sent state. | `Verification email sent. Check your inbox and spam folder.` |
| Resend rate limited | Inline warning near primary action. | `Please wait before requesting another verification email.` |

## Screen 5: Failed Verification Link

Purpose: shown when the token is invalid, already consumed without a verified account match, malformed, or otherwise cannot be used. Copy must avoid exposing token internals.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Verification link cannot be used                 |
| We could not verify your email with this link.   |
| Request a new email if you still need to verify. |
|                                                  |
| [ Send verification email ]                      |
| Back to sign in                                  |
| Contact support                                  |
+--------------------------------------------------+
```

### Failed Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Verification link cannot be used` |
| Primary action | `Send verification email` when an email or request context is known; otherwise collect email first. |
| Return action | `Back to sign in` lets authenticated or unauthenticated users restart safely. |
| Support action | `Contact support` is secondary and should not disclose token details. |
| Status behavior | Do not mark email verified. Keep existing request status unchanged. |
| Blocked action | No full ERP access CTA appears in this state. |

### Failed States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Known email or request context | Show primary resend action. | `Request a new email if you still need to verify.` |
| Unknown context | Show an email input before resend. | `Enter your business email and we will send a new verification link if a request is eligible.` |
| Consumed token but unverified context | Use same generic failed state. | `We could not verify your email with this link.` |
| Resend accepted | Route to Email Sent state or show inline confirmation. | `Verification email sent. Check your inbox and spam folder.` |

## Screen 6: Already Verified

Purpose: shown when the user opens a verification link after the email has already been verified, or when a consumed token maps to a request that is already verified. This state should be calm and route to the next status-aware destination.

```text
+--------------------------------------------------+
| abit.ai                                          |
| Email already verified                           |
| This email address is already confirmed.         |
|                                                  |
| jane@company.com                                 |
|                                                  |
| [ Continue access request ]                      |
| Back to sign in                                  |
+--------------------------------------------------+
```

### Already Verified Requirements

| Element | UX requirement |
| --- | --- |
| Title | `Email already verified` |
| Primary action | `Continue access request` routes by current request status. |
| Return action | `Back to sign in` |
| Routing behavior | If status is `onboarding_required`, continue to onboarding. If status is `pending_admin_review`, route to review pending. If status is `approved_for_mvp_access`, only then may the next status-aware destination expose product access. |
| Blocked action | The screen itself does not show full ERP access unless a fresh status check confirms approval. |

### Already Verified States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Already verified with onboarding required | Neutral confirmation with `Continue access request`. | `This email address is already confirmed.` |
| Already verified and pending review | Confirmation followed by review-pending route. | `This email address is already confirmed. Your access request is waiting for review.` |
| Already verified and approved | Confirmation may route through status-aware sign-in or approved landing. | `This email address is already confirmed.` |

## Resend Interaction Contract

```text
+--------------------------------------------------+
| Resend verification email                        |
|                                                  |
| Request submitted                                |
| [ Sending... ]                                   |
|                                                  |
| Success: Verification email sent. Check your     |
| inbox and spam folder.                           |
|                                                  |
| Rate limited: Please wait before requesting      |
| another verification email.                      |
+--------------------------------------------------+
```

| Interaction | UX behavior | System behavior |
| --- | --- | --- |
| Resend clicked | Disable resend action and keep surrounding content stable. | Check request eligibility and resend cooldown. |
| Resend accepted | Show success inline or route to Email Sent. | Create a new single-use token, invalidate or supersede previous unconsumed tokens according to backend policy, send email, emit resend analytics. |
| Resend rate limited | Show cooldown-safe warning without exact security internals. | Do not create a new token. |
| Unknown email resend | Use generic eligible-account wording. | Do not disclose whether the email exists. |
| Locked or blocked account | Show support-safe recovery message. | Do not bypass account lock or admin hold. |

## Copy Inventory

| State | Primary title | Primary action | Recovery or secondary action |
| --- | --- | --- | --- |
| Verification required | `Verify your email to continue` | `Resend verification email` | `Change email`, `Sign out` |
| Email sent | `Check your email` | `Open email app` | `Resend verification email`, `Change email`, `Sign in` |
| Success | `Email verified` | `Continue access request` | `Sign in with another account` |
| Expired | `Verification link expired` | `Send a new verification email` | `Use a different email`, `Back to sign in` |
| Failed | `Verification link cannot be used` | `Send verification email` | `Back to sign in`, `Contact support` |
| Already verified | `Email already verified` | `Continue access request` | `Back to sign in` |

## Acceptance Coverage Checklist

| ERP acceptance item | Covered by |
| --- | --- |
| Verification-required screen exists | Screen 1: Verification Required |
| Email-sent screen exists | Screen 2: Email Sent |
| Success screen exists | Screen 3: Verification Success |
| Expired screen exists | Screen 4: Expired Verification Link |
| Failed screen exists | Screen 5: Failed Verification Link |
| Already-verified screen exists | Screen 6: Already Verified |
| Each state has clear recovery action | Screen requirements and Copy Inventory primary and secondary actions |
| No full ERP access CTA before verification | Shared Rules, per-screen Blocked action rows, Success and Already Verified status-aware routing notes |
| Resend path is represented | Resend Interaction Contract plus resend states on required, sent, expired, and failed screens |
| Expired and failed tokens do not verify email | Expired Requirements and Failed Requirements status behavior rows |

## UX Review Notes

- Verification is an account-safe gate, not a product entry point.
- `Continue access request` is intentionally used instead of `Open ERP` because verification alone only advances the request to onboarding or the next status-aware step.
- Failed-token copy is generic so the UI does not expose whether a token was malformed, consumed, expired through a different path, or mismatched.
- Resend confirmation reuses the wording from `docs/signup-multi-step-wireframes.md` to keep the signup and verification surfaces consistent.
- Final headings, actions, helper text, and errors are approved in `docs/auth-onboarding-ux-copy.md`.
