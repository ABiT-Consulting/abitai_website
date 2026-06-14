# Auth Responsive UI Design System

Task: TASK-2026-01974 / UX-06
Project: PROJ-0130 abit_ai_website
Status: Draft for UX review

## Objective

Define and implement the responsive component layer for the abit.ai SaaS authentication experience. The system covers sign-in, signup, verification, forgot/reset password, and onboarding gate surfaces.

## Implemented Assets

| Asset | Purpose |
| --- | --- |
| `wp-content/themes/astra/template-auth-design-system.php` | WordPress preview template showing all auth components and variants. |
| `wp-content/themes/astra/assets/css/abitai-frontend.css` | Scoped `abit-auth-*` CSS tokens, responsive layout, and component states. |

## Component Inventory

| Component | CSS/API class | Required variants covered |
| --- | --- | --- |
| Text input | `.abit-auth-field`, `.abit-auth-input` | Default, hover, focus, disabled, error. |
| Password field | `.abit-auth-password`, `.abit-auth-meter`, `.abit-auth-icon-button` | Hidden text default, reveal button, strength meter, focus, error. |
| Select | `.abit-auth-select` | Default, hover, focus, disabled, error. |
| Checkbox | `.abit-auth-checkbox` | Checked, unchecked, hover, focus, disabled, wrapping legal copy. |
| Module selector | `.abit-auth-module-selector`, `.abit-auth-module-grid`, `.abit-auth-module-option` | Multi-select, selected, hover, focus, disabled, one-column mobile. |
| Alert | `.abit-auth-alert` | Info, success, warning, error. |
| Button | `.abit-auth-button` | Primary, secondary, hover, focus, disabled, loading. |
| Stepper | `.abit-auth-stepper`, `.abit-auth-stepper__item` | Complete, active, upcoming, stacked mobile. |
| Status badge | `.abit-auth-status-badge` | Pending email, admin review, approved, rejected. |

## Responsive Rules

| Viewport | Behavior |
| --- | --- |
| Desktop | Two-column documentation/preview layout; auth card remains constrained and centered in its column. |
| Tablet, `max-width: 921px` | Preview sections collapse to a single column while keeping card spacing stable. |
| Mobile, `max-width: 600px` | Component grids, module selector, stepper, and action buttons become one column with full-width buttons. |

## Interaction State Rules

- Focus uses a visible blue ring through `box-shadow` and preserves the label position.
- Error fields use `aria-invalid="true"` or `.is-error` with red border and inline helper copy.
- Loading buttons use `.is-loading` and `aria-busy="true"` with an inline spinner that does not change button height.
- Disabled controls use native `disabled` where possible and keep stable dimensions.
- Module options are real checkboxes inside styled labels so keyboard and screen-reader behavior remains native.

## Acceptance Coverage

| ERP acceptance item | Covered by |
| --- | --- |
| Desktop components | Preview template default layout and auth CSS. |
| Tablet components | `@media (max-width: 921px)` responsive rules. |
| Mobile components | `@media (max-width: 600px)` responsive rules. |
| Hover variants | Input, select, checkbox, module option, and button hover rules. |
| Focus variants | Input, select, checkbox, module option, icon button, and button focus rules. |
| Disabled variants | Native disabled controls and disabled component examples in the preview template. |
| Loading variants | `.abit-auth-button.is-loading` preview example. |
| Error variants | `.abit-auth-field.is-error`, `aria-invalid`, `.abit-auth-alert--error`. |

## Usage Notes

Create a WordPress page using the `ABiT Auth Design System` template to review the component system in-browser. Production auth templates can adopt the `abit-auth-*` classes without changing their form actions or server-side handlers.
