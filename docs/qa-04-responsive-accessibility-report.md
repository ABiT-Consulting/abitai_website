# QA-04 Responsive and Accessibility QA Report

Task: `TASK-2026-02029 / QA-04`
Project: `PROJ-0130 abit_ai_website`
Evidence captured: `2026-06-22 01:05 Asia/Dubai`
Scope: SaaS auth routes served by `wp-content/themes/astra/template-auth.php`, `assets/css/abitai-frontend.css`, and `assets/js/abitai-auth.js`.

## Summary

Result: No critical accessibility or mobile-blocking defects remain.

One contrast defect was found and fixed during this QA pass: white text on `--auth-blue: #2490ef` was 3.33:1 before fix. The auth token now uses `--auth-blue: #0f75cf`, giving white primary-button text a 4.70:1 after fix ratio. The hover token now uses `#0b5fa8`, preserving stronger hover contrast.

Browser execution note: this repository snapshot does not include a runnable `wp-config.php` and database, so this pass used code-level responsive and accessibility inspection plus automated static guards instead of live WordPress screenshots. The route template, CSS, and JavaScript contain the production selectors and states under review.

## Desktop

Status: Pass.

- Auth routes use a two-column `.abit-auth-app-shell` with a brand/support panel and constrained route card.
- The card width is capped with `width: min(100%, 460px)`, preventing oversized line lengths.
- Dashboard cards use a two-column grid with profile and next-step cards spanning the full width.
- Text containers use `min-width: 0` and `overflow-wrap: anywhere` where user-provided or long status text can appear.

## Tablet

Status: Pass.

- `@media (max-width: 921px)` collapses the auth shell from two columns to one column.
- Sticky design-system preview sidebars become static at tablet width.
- The auth shell removes the fixed minimum height and allows visible overflow to avoid clipped content.
- Brand content expands to full width, keeping route content readable without horizontal scrolling.

## Mobile

Status: Pass.

- `@media (max-width: 600px)` reduces page padding and makes the route shell full width.
- Form grids, module selector grids, signup stepper, dashboard grids, and dashboard definition lists collapse to one column.
- Actions switch to a grid layout and `.abit-auth-button` becomes full width.
- Inputs and buttons keep `min-height: 46px`, which is acceptable for touch targets.
- `.abit-auth-route-page` includes `overflow-x: hidden`; no code-level mobile blocker or fixed-width child was found in the auth surface.

## Keyboard

Status: Pass.

- Native controls are used for inputs, selects, buttons, checkboxes, and links.
- JavaScript validation focuses the first invalid control on sign-in, signup, resend, and password reset flows.
- Multi-step signup moves focus to the first control of the next or previous step.
- Submit buttons expose disabled/loading state and `aria-busy="true"`.
- Navigation links locked during submit receive `aria-disabled="true"` and `tabindex="-1"`.

## Screen-Reader

Status: Pass.

- The auth shell is labelled with `aria-labelledby="abit-auth-route-title"`.
- The brand panel and dashboard region use explicit `aria-label` text.
- Inline form errors are connected with `aria-describedby`.
- Error messages use `role="alert"` and non-error status messages use `role="status"`.
- Status alerts that need immediate attention are focusable with `tabindex="-1"` and `data-auth-autofocus`.
- Signup progress uses `aria-current="step"` plus a screen-reader-only `aria-live="polite"` step status.
- The ERP module checkbox group uses `fieldset` and `legend`.

## Contrast

Status: Pass after scoped CSS fix.

Measured pairs:

| Pair | Ratio | Result |
| --- | ---: | --- |
| White text on old `#2490ef` primary blue | 3.33:1 | Failed before fix |
| White text on new `#0f75cf` primary blue | 4.70:1 | Pass |
| Info alert `#175cd3` on `#eff8ff` | 5.57:1 | Pass |
| Success alert `#067647` on `#ecfdf3` | 5.40:1 | Pass |
| Warning alert `#b54708` on `#fffaeb` | 5.20:1 | Pass |
| Error alert `#b42318` on `#fef3f2` | 6.05:1 | Pass |
| Body text `#344054` on white | 10.46:1 | Pass |
| Muted text `#667085` on white | 4.97:1 | Pass |

## Focus-State

Status: Pass.

- Inputs, selects, textareas, module options, checkboxes, icon buttons, buttons, and focusable alerts share a visible focus ring through `--auth-focus`.
- Inline links, the support link, and the brand link use `:focus-visible` outlines.
- Error state styling is tied to `.is-error` and `aria-invalid="true"`.
- Loading buttons keep stable height and add a spinner through `.abit-auth-button.is-loading::after`.

## Validation Commands

Run locally with `C:\xampp\php\php.exe`:

```text
C:\xampp\php\php.exe tests\auth-responsive-accessibility-qa.php
QA-04 responsive and accessibility checks passed.

C:\xampp\php\php.exe -l tests\auth-responsive-accessibility-qa.php
No syntax errors detected in tests\auth-responsive-accessibility-qa.php

C:\xampp\php\php.exe -l wp-content\themes\astra\template-auth.php
No syntax errors detected in wp-content\themes\astra\template-auth.php

git diff --check
No whitespace errors. Git printed the repository line-ending warning for `wp-content/themes/astra/assets/css/abitai-frontend.css`.
```

## Residual Risk

Live browser screenshots should still be captured in a provisioned WordPress QA environment with database content and the active Astra theme. Based on code-level QA, no critical accessibility or mobile-blocking defect remains in the auth templates, scoped CSS, or JavaScript.
