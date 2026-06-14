# Sign-In Wireframe and States

Task: TASK-2026-01968 / UX-02
Project: PROJ-0130 abit_ai_website
Status: Draft for UX review

## Objective

Define the sign-in wireframe and required interaction states for the abit.ai SaaS authentication MVP. The design must support returning users who sign in with email and password, users who need password recovery, and visitors who should start the signup access request flow instead.

## Source Context

- `docs/saas-auth-mvp-scope.md`
- `docs/auth-user-flow-map.md`
- Source brief named in ERP task: `PROJ-0130_abit_saas_auth_task_tree.md`

## Base Sign-In Wireframe

```text
+--------------------------------------------------------------+
| abit.ai                                                      |
| Sign in to your workspace                                    |
| Access your ERP advisory workspace and onboarding status.    |
|                                                              |
| [ Email address                                          ]    |
| [ Password                                               ]    |
|                                                              |
| ( ) Remember this device                 Forgot password?    |
|                                                              |
| [ Sign in ]                                                  |
|                                                              |
| New to abit.ai? Start SaaS access request                    |
+--------------------------------------------------------------+
```

### Layout Notes

| Element | UX requirement |
| --- | --- |
| Page title | Use `Sign in to your workspace` as the primary heading. |
| Email field | Required email input with browser email keyboard support on mobile. |
| Password field | Required password input. Password text remains hidden by default. |
| Forgot password link | Secondary text link aligned with the password helper row. |
| Primary button | Full-width `Sign in` button. |
| Signup CTA | Text CTA below the form: `New to abit.ai? Start SaaS access request`. |
| Support copy | Keep copy short and operational. Avoid implying approval before admin review. |

## Field-Level States

| State | Wireframe treatment | Copy |
| --- | --- | --- |
| Empty initial state | Blank email and password fields. Primary button enabled only when required fields pass client-side presence checks. | None. |
| Email format error | Email input gets error border and inline helper below the field. | `Enter a valid email address.` |
| Missing password | Password input gets error border and inline helper below the field. | `Enter your password.` |
| Keyboard focus | Focused input uses a clear focus ring and keeps label visible. | No extra copy. |

## Loading State

```text
+--------------------------------------------------------------+
| Sign in to your workspace                                    |
|                                                              |
| [ user@company.com                                      ]    |
| [ ************                                         ]    |
|                                                              |
| [ Signing in...  spinner ]                                  |
+--------------------------------------------------------------+
```

Requirements:

- Disable email, password, forgot password, signup CTA, and primary submit while the sign-in request is in flight.
- Replace the button label with `Signing in...`.
- Show a spinner inside the button without changing button height.
- Preserve the typed values during loading.
- Prevent duplicate submissions.

## Error State

```text
+--------------------------------------------------------------+
| Sign in to your workspace                                    |
|                                                              |
| [!] We could not sign you in with those details. Check your  |
|     email and password, then try again.                      |
|                                                              |
| [ Email address                                          ]    |
| [ Password                                               ]    |
|                                                              |
| Forgot password?                                             |
| [ Sign in ]                                                  |
+--------------------------------------------------------------+
```

Requirements:

- Use a generic credential error so the UI does not disclose whether the email exists.
- Keep the email value after failure.
- Clear the password field after failure.
- Place the error summary above the fields and move focus to it for screen readers.
- Keep the forgot password link visible as the recovery path.

Generic error copy:

`We could not sign you in with those details. Check your email and password, then try again.`

## Success State

```text
+--------------------------------------------------------------+
| Sign in to your workspace                                    |
|                                                              |
| [check] Signed in. Redirecting...                            |
+--------------------------------------------------------------+
```

Requirements:

- Show the success confirmation only briefly before routing.
- Disable all form controls during routing.
- Route by the authenticated user's request status:

| Request status | Destination after successful sign-in |
| --- | --- |
| `pending_email_verification` | Verify-email prompt with resend option. |
| `onboarding_required` | Onboarding gate. |
| `pending_admin_review` | Review-pending state. |
| `more_information_requested` | Editable onboarding context with admin message. |
| `approved_for_mvp_access` | MVP app shell or approved landing state. |
| `rejected` | Decision or unavailable state without product access. |

Success copy:

`Signed in. Redirecting...`

## Forgot Password State

The forgot password link opens a reset request panel or page. This state must not reveal whether an account exists.

```text
+--------------------------------------------------------------+
| Reset your password                                          |
| Enter your email and we will send reset instructions if the  |
| account is eligible.                                         |
|                                                              |
| [ Email address                                          ]    |
|                                                              |
| [ Send reset instructions ]                                  |
| Back to sign in                                              |
+--------------------------------------------------------------+
```

After submission:

```text
+--------------------------------------------------------------+
| Check your email                                             |
| If an eligible account exists, reset instructions will be    |
| sent to that address.                                        |
|                                                              |
| Back to sign in                                              |
+--------------------------------------------------------------+
```

Requirements:

- Use the same confirmation for known emails, unknown emails, and locked accounts.
- Keep copy generic: `If an eligible account exists, reset instructions will be sent to that address.`
- Provide a `Back to sign in` link.
- Do not auto-create a signup request from password reset.

## Signup CTA State

The signup CTA sends new users to the SaaS access request flow.

```text
New to abit.ai? Start SaaS access request
```

Requirements:

- Keep the CTA below the sign-in form and visually secondary to `Sign in`.
- Route to the approved signup access request entry point.
- Use the language `Start SaaS access request` so the user understands access is reviewed before product entry.

## Acceptance Coverage Checklist

| Required deliverable item | Represented by |
| --- | --- |
| Email/password sign-in | Base Sign-In Wireframe, Field-Level States |
| Forgot password | Forgot Password State |
| Signup CTA | Signup CTA State |
| Loading state | Loading State |
| Error state | Error State |
| Success state | Success State |

## UX Review Notes

- The sign-in screen should be compact and operational, matching the existing signup template direction in `wp-content/themes/astra/template-signup.php`.
- Error and reset copy intentionally avoids account enumeration.
- Successful sign-in is not always product access; routing must honor the MVP request statuses from `docs/auth-user-flow-map.md`.
