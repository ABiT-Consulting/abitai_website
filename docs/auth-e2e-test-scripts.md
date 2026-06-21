# Auth E2E Test Scripts

Task: TASK-2026-02027 / QA-02
Project: PROJ-0130 abit_ai_website
Source brief: `PROJ-0130_abit_saas_auth_task_tree.md`
Status: Documented E2E tests for QA and UAT execution

## Objective

Validate the abit.ai SaaS auth happy paths and common failure paths in a browser against a controlled WordPress test environment. These scripts are written so they can be executed manually by QA or translated directly into Playwright/Cypress once the project has a browser test runner.

The ERP acceptance test is covered by E2E-AUTH-001: signup reaches the verification prompt, a verified account routes to onboarding, and the onboarding/dashboard gate blocks product access until admin review and approval.

## Environment

Use an isolated test site with the Astra auth shell active and outbound email captured by a test mailbox or mail log. Do not use production customer accounts or production inboxes.

| Setting | Value |
| --- | --- |
| Base URL | `AUTH_E2E_BASE_URL`, for example `https://test.abit.ai` |
| Signup route | `/auth/signup` |
| Sign-in route | `/auth/sign-in` |
| Verify route | `/auth/verify` |
| Onboarding/dashboard route | `/dashboard` |
| Reset route | `/auth/reset` and `/auth/reset-password` |
| Mock password | `Password123!` for built-in fixture users |

## Test Data

| Purpose | Email | Password | Expected status |
| --- | --- | --- | --- |
| Unverified login | `unverified@abit.ai` | `Password123!` | `pending_email_verification` |
| Onboarding-required login | `onboarding@abit.ai` | `Password123!` | `onboarding_required` |
| Review-pending login | `review@abit.ai` | `Password123!` | `pending_admin_review` |
| Approved login | `approved@abit.ai` | `Password123!` | `approved_for_mvp_access` |
| More-information login | `info@abit.ai` | `Password123!` | `more_information_requested` |
| Rejected login | `rejected@abit.ai` | `Password123!` | `rejected` |
| Blocked login | `blocked@abit.ai` | `Password123!` | `blocked` |

Use a unique signup email for each new-user run, such as `qa02+{timestamp}@example.test`.

## Evidence Required

For each run, capture:

| Evidence | Requirement |
| --- | --- |
| Browser screenshots | Signup step 1, signup submitted verify prompt, verified/onboarding gate, and each failed state tested. |
| Network or server log | Signup POST accepted, verification or mock verified state, and sign-in redirects by status. |
| Email evidence | Verification email was sent to the test mailbox or captured in mail logs for E2E-AUTH-001. |
| Product-access gate | `/dashboard` shows blocked/review/onboarding gate for non-approved users and product access only for approved users. |
| Console | No uncaught browser errors during the auth run. |

## E2E-AUTH-001 Signup To Verified Onboarding

Priority: P0
Coverage: `QA-AUTH-SIGNUP-001`, `QA-AUTH-VERIFY-001`, `QA-AUTH-ONBOARD-001`, ERP acceptance test

Preconditions:

- Test email alias is unique.
- Mail capture can show the verification email.
- The auth shell JavaScript `abitai-auth.js` is loaded.

Steps:

1. Open `/auth/signup`.
2. Confirm the stepper contains `Account`, `Company`, and `ERP needs`.
3. Fill Account:
   - `#abit-auth-signup-name`: `QA Two Tester`
   - `#abit-auth-signup-email`: unique test email
   - `#abit-auth-signup-password`: `StrongerPass123!`
   - `#abit-auth-signup-confirm-password`: `StrongerPass123!`
   - `#abit-auth-signup-consent`: checked
4. Click `[data-auth-next-step]`.
5. Fill Company:
   - `#abit-auth-company-name`: `QA Two Trading LLC`
   - `#abit-auth-country-region`: `AE`
   - `#abit-auth-job-title`: `Operations Manager`
   - `#abit-auth-company-size`: any valid non-empty value
   - `#abit-auth-industry`: any valid non-empty value
   - `#abit-auth-business-description`: `Evaluating ERPNext for purchasing, inventory, and finance workflow approval.`
6. Click `[data-auth-next-company-step]`.
7. Select at least one `input[name="erp_module_interest[]"]`.
8. Submit the form.
9. Expect navigation to `/auth/verify?state=sent`.
10. Confirm the page shows `Verification email sent.` and the signed-up email.
11. Confirm the test mailbox or mail log has one verification email for the address.
12. Open the captured verification link, or use `/auth/verify?state=success&email={email}` when running against the documented mock state.
13. Expect `Email verified.` and a primary action to continue the access request.
14. Open `/dashboard?status=onboarding_required&email={email}&company_name=QA%20Two%20Trading%20LLC&company_size=11_50&industry=trading_distribution&country_region=AE&erp_module_interest[]=buying`.
15. Confirm dashboard/onboarding gate shows email verification completed, profile still requiring completion or review, and workspace/product access blocked.

Expected result:

- Signup creates a pending email verification path and shows the verify prompt.
- Verification success routes to onboarding/dashboard gate.
- Product access is not available until the request becomes approved.

## E2E-AUTH-002 Status-Aware Login Routing

Priority: P0
Coverage: `QA-AUTH-LOGIN-001`, `QA-AUTH-LOGIN-002`, `QA-AUTH-LOGIN-003`, `QA-AUTH-LOGIN-004`, `QA-AUTH-LOGIN-005`

Run the sign-in form for each fixture in Test Data.

Steps:

1. Open `/auth/sign-in`.
2. Fill `#abit-auth-email` and `#abit-auth-password`.
3. Submit the form.
4. Verify the destination and copy:

| Fixture | Expected route or state |
| --- | --- |
| `unverified@abit.ai` | `/auth/verify` with verification-required copy and no product access. |
| `onboarding@abit.ai` | `/dashboard?status=onboarding_required...` with onboarding/profile incomplete gate. |
| `review@abit.ai` | `/dashboard?status=pending_admin_review...` with review-pending copy and blocked workspace status. |
| `approved@abit.ai` | `/dashboard?status=approved_for_mvp_access...` with approved or workspace-ready state. |
| `info@abit.ai` | `/dashboard?status=more_information_requested...` with action-needed copy. |
| `rejected@abit.ai` | `/dashboard?status=rejected...` with rejected/unavailable copy and blocked workspace status. |
| `blocked@abit.ai` | `/dashboard?status=blocked...` with blocked/support state and blocked workspace status. |

Expected result:

- Login success does not imply product access.
- Every user routes by durable status.
- Only approved users can reach an approved workspace state.

## E2E-AUTH-003 Signup Validation Failures

Priority: P1
Coverage: `QA-AUTH-SIGNUP-003`, `QA-AUTH-SIGNUP-004`, `QA-AUTH-SIGNUP-005`

Steps:

1. Open `/auth/signup`.
2. Try to continue with blank Account fields.
3. Expect focus to move to the first invalid field and inline errors to appear.
4. Enter malformed email `not-an-email`, weak password `password123`, mismatched confirmation, and leave consent unchecked.
5. Expect the form to remain on the Account step with `aria-invalid` applied to invalid inputs.
6. Complete Account and move to Company.
7. Submit invalid company data: one-character company name, blank country, blank size, blank industry, a short business description, and a phone containing letters.
8. Expect inline errors and no request submission.
9. Complete Company and move to ERP needs.
10. Submit without selecting an ERP module.
11. Expect `Select at least one ERP module interest.` and no request submission.

Expected result:

- Required validation blocks progress or submission.
- Passwords and token values are not printed in the UI.
- Duplicate or existing email runs should produce the same neutral verify/check-email experience as a new request, without account-existence disclosure.

## E2E-AUTH-004 Verification Resend And Token Failures

Priority: P1
Coverage: `QA-AUTH-VERIFY-002`, `QA-AUTH-VERIFY-003`, `QA-AUTH-RESEND-001`, `QA-AUTH-RESEND-003`

Steps:

1. Open `/auth/verify?state=expired&email=unverified@abit.ai`.
2. Confirm expired-link copy and a resend action are visible.
3. Submit the resend form with `unverified@abit.ai`.
4. Expect a generic confirmation or cooldown-safe response.
5. Open `/auth/verify?state=failed&email=unverified@abit.ai`.
6. Confirm invalid-link copy is generic and raw token details are not shown.
7. Repeat resend requests until the environment policy returns cooldown/rate-limit behavior, or document why the target environment cannot throttle deterministic test traffic.

Expected result:

- Expired and invalid links do not verify the email.
- Resend is available for recovery.
- Cooldown/rate-limit copy does not disclose account existence or token internals.

## E2E-AUTH-005 Password Reset Happy And Failure Paths

Priority: P1
Coverage: `QA-AUTH-RESET-001`, `QA-AUTH-RESET-002`, `QA-AUTH-RESET-003`, `QA-AUTH-RESET-004`, `QA-AUTH-RESET-005`, `QA-AUTH-RESET-006`, `QA-AUTH-RESET-007`

Steps:

1. Open `/auth/reset`.
2. Fill `#abit-auth-reset-email` with `approved@abit.ai` and submit the form.
3. Expect generic `Check your email.` confirmation.
4. Submit an unknown email and confirm the same generic confirmation.
5. Open `/auth/reset-password?token=valid-reset-token`.
6. After the checking state advances, fill `#abit-auth-new-password` and `#abit-auth-confirm-new-password` with `NewStrongerPass123!`.
7. Expect successful reset copy, then sign in normally.
8. Open `/auth/reset-password?token=expired-reset-token` and confirm expired-link copy with new reset request action.
9. Open `/auth/reset-password?token=invalid-reset-token` and confirm invalid-link copy without raw token detail.
10. On a valid reset form, submit a weak password and mismatched confirmation to verify inline errors.

Expected result:

- Reset requests are non-disclosing.
- Reset success returns to sign-in and does not bypass status-aware routing.
- Expired, invalid, weak, and mismatched paths do not change the password.

## E2E-AUTH-006 Product Access Gates

Priority: P0
Coverage: `QA-AUTH-ONBOARD-004`, `QA-AUTH-SEC-001`, `QA-AUTH-SEC-005`

Steps:

1. For each fixture status, open `/dashboard?status={status}&email={email}`.
2. Confirm non-approved users show the current blocker and no app/product CTA.
3. Confirm rejected and blocked users show unavailable/support states.
4. Confirm approved users are the only fixture users that show an approved/workspace-ready state.
5. If API auth is enabled in the target environment, call `GET /api/auth/me` after login and confirm `gate.product_access` matches the UI.
6. If API auth is enabled, call `POST /api/provisioning/request` before approval and confirm `403`, `422`, or `423` with `provisioning_not_allowed`.

Expected result:

- Product access remains gated by status and `/api/auth/me`, not by form submission alone.
- Premature provisioning is blocked.

## Automation Notes

When a browser test runner is added, translate the P0 tests first:

```ts
test('E2E-AUTH-001 signup reaches verified onboarding gate', async ({ page }) => {
  await page.goto(`${baseURL}/auth/signup`);
  await expect(page.getByText('Account')).toBeVisible();
  await page.fill('#abit-auth-signup-name', 'QA Two Tester');
  await page.fill('#abit-auth-signup-email', uniqueEmail);
  await page.fill('#abit-auth-signup-password', 'StrongerPass123!');
  await page.fill('#abit-auth-signup-confirm-password', 'StrongerPass123!');
  await page.check('#abit-auth-signup-consent');
  await page.click('[data-auth-next-step]');
  await page.fill('#abit-auth-company-name', 'QA Two Trading LLC');
  await page.selectOption('#abit-auth-country-region', 'AE');
  await page.fill('#abit-auth-job-title', 'Operations Manager');
  await page.selectOption('#abit-auth-company-size', { index: 1 });
  await page.selectOption('#abit-auth-industry', { index: 1 });
  await page.fill('#abit-auth-business-description', 'Evaluating ERPNext for purchasing, inventory, and finance workflow approval.');
  await page.click('[data-auth-next-company-step]');
  await page.locator('input[name="erp_module_interest[]"]').first().check();
  await page.click('[data-auth-submit]');
  await expect(page).toHaveURL(/\/auth\/verify/);
  await expect(page.getByText('Verification email sent.')).toBeVisible();
});
```

Keep screenshots, traces, and mail-capture artifacts attached to QA/UAT evidence for any failed run and its retest.
