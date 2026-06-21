# Launch Feature Flag and Staged Rollout Plan

Task: `TASK-2026-02035 / LCH-04`
Project: `PROJ-0130 abit_ai_website`
Milestone: `10. Launch and Post-launch Monitoring`
Status: Launch rollout controls prepared for internal beta, selected customer beta, and public release.
Source: `PROJ-0130_abit_saas_auth_task_tree.md`

## Objective

Launch the abit.ai SaaS auth MVP behind server-owned controls so signup-to-onboarding can be proven with internal users before broader exposure. The rollout gate applies to new signup requests and product access gates returned from `/api/auth/me`; provisioning remains blocked unless launch rollout, admin approval, consent, email verification, and provisioning capacity are all ready.

## Feature Flag Contract

Set these values only in hosting configuration, environment variables, or deployment secrets. Do not expose customer beta lists in repository files.

| Setting | Default | Purpose |
| --- | --- | --- |
| `ABIT_SAAS_AUTH_LAUNCH_ENABLED` | `false` | Master feature flag. When false, signup returns `launch_rollout_not_available` and product access remains false. |
| `ABIT_SAAS_AUTH_ROLLOUT_STAGE` | `internal_beta` | Active stage: `internal_beta`, `customer_beta`, or `public`. |
| `ABIT_SAAS_AUTH_INTERNAL_BETA_EMAILS` | empty | Optional comma/space-separated internal email allowlist. |
| `ABIT_SAAS_AUTH_INTERNAL_BETA_DOMAINS` | `abit.ai` | Internal beta domain allowlist. |
| `ABIT_SAAS_AUTH_CUSTOMER_BETA_EMAILS` | empty | Selected customer beta email allowlist. |
| `ABIT_SAAS_AUTH_CUSTOMER_BETA_DOMAINS` | empty | Selected customer beta domain allowlist. |

## Stages

| Stage | Configuration | Eligible signup traffic | Exit criteria |
| --- | --- | --- | --- |
| Disabled (`disabled`) | `ABIT_SAAS_AUTH_LAUNCH_ENABLED=false` | None | Launch owner approves internal beta start. |
| Internal beta | `ABIT_SAAS_AUTH_LAUNCH_ENABLED=true`, `ABIT_SAAS_AUTH_ROLLOUT_STAGE=internal_beta` | `@abit.ai` and optional internal email allowlist | ERP acceptance test passes: internal beta completes signup-to-onboarding before broader rollout. |
| Selected customer beta | `ABIT_SAAS_AUTH_ROLLOUT_STAGE=customer_beta` | Internal beta users plus configured customer emails/domains | Support, delivery, admin review, and product-access gates pass for selected customers. |
| Public rollout | `ABIT_SAAS_AUTH_ROLLOUT_STAGE=public` | All valid business signup traffic | Launch monitoring remains green and rollback owner approves public exposure. |

## Acceptance Test

Run this before moving beyond `internal_beta`:

1. Confirm `ABIT_SAAS_AUTH_LAUNCH_ENABLED=true` and `ABIT_SAAS_AUTH_ROLLOUT_STAGE=internal_beta`.
2. Submit `/auth/signup` or `POST /api/auth/register` with a disposable internal alias such as `launch+YYYYMMDDHHMM@abit.ai`.
3. Confirm the response reaches the verification-sent state.
4. Verify the email and sign in.
5. Confirm `/api/auth/me` returns `gate.launch_rollout.stage=internal_beta`, `gate.launch_rollout.allowed=true`, and `onboarding.status` is `required`, `ready_to_submit`, or `submitted_for_review`.
6. Confirm product access is still false until admin approval and provisioning readiness are also satisfied.
7. Submit a non-allowlisted external email and confirm it is blocked with `code=launch_rollout_not_available`.

Record the tested email alias, timestamp, release commit, API responses with secrets redacted, and admin review evidence in the launch ticket.

## Promotion Controls

Promotion is config-only:

1. Keep `ABIT_SAAS_AUTH_LAUNCH_ENABLED=true`.
2. Move from `internal_beta` to `customer_beta` only after the internal beta acceptance test passes.
3. Add selected customer emails or domains to the customer beta allowlists.
4. Move from `customer_beta` to `public` only after support and launch monitoring remain green.

Rollback is config-only unless a code defect is identified:

| Failure | Immediate action |
| --- | --- |
| Unsafe product access | Set `ABIT_SAAS_AUTH_LAUNCH_ENABLED=false` and `ABIT_SAAS_PROVISIONING_CAPACITY_READY=false`. |
| Bad customer beta cohort | Remove the customer email/domain or return to `internal_beta`. |
| Broad signup failure | Return to `internal_beta` or disable the launch flag while preserving existing records. |

## API Contract

Blocked signup returns:

```json
{
  "message": "abit.ai access is currently limited to the active beta group.",
  "code": "launch_rollout_not_available",
  "launch_rollout": {
    "enabled": true,
    "stage": "internal_beta",
    "allowed": false,
    "reason": "internal_beta_not_eligible"
  }
}
```

Authenticated status includes `gate.launch_rollout`:

```json
{
  "gate": {
    "product_access": false,
    "launch_rollout": {
      "enabled": true,
      "stage": "internal_beta",
      "allowed": true,
      "reason": "domain_allowlist"
    }
  }
}
```

## Validation Commands

Run from the repository root:

```text
php tests/launch-rollout-coverage.php
php -l tests/launch-rollout-coverage.php
php -l wp-content/mu-plugins/abit-saas-auth.php
```
