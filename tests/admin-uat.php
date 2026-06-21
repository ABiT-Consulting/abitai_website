<?php
/**
 * QA-05 admin UAT evidence checks.
 *
 * Usage:
 * php tests/admin-uat.php
 */

$root = dirname(__DIR__);
$report_path = $root . '/docs/qa-05-admin-uat-report.md';
$plugin_path = $root . '/wp-content/mu-plugins/abit-saas-auth.php';

function qa05_read_file(string $path, string $label): string
{
    $contents = file_get_contents($path);

    if (false === $contents) {
        throw new RuntimeException("Could not read {$label}.");
    }

    return $contents;
}

function qa05_assert_contains(string $haystack, string $needle, string $message): void
{
    if (false === strpos($haystack, $needle)) {
        throw new RuntimeException($message);
    }
}

function qa05_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (1 !== preg_match($pattern, $haystack)) {
        throw new RuntimeException($message);
    }
}

$report = qa05_read_file($report_path, 'QA-05 admin UAT report');
$plugin = qa05_read_file($plugin_path, 'ABiT SaaS auth MU plugin');

qa05_assert_contains($report, 'Task: `TASK-2026-02030 / QA-05`', 'Admin UAT report must identify the ERP task.');
qa05_assert_contains($report, 'ERP acceptance test: Admin can process a new signup from ready-for-review to approved/held with audit evidence.', 'Admin UAT report must quote the ERP acceptance test.');
qa05_assert_contains($report, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Admin UAT report must reference the source task tree brief.');

foreach (array(
    '## Acceptance Test',
    '## Admin Queue Review',
    '## Profile Review',
    '## Decision UAT',
    '## Handoff Fields',
    '## Audit Evidence',
    '## Residual Risk',
    '## Validation Commands',
) as $section) {
    qa05_assert_contains($report, $section, "Admin UAT report must include {$section}.");
}

foreach (array(
    'ABiT Signup Review',
    'pending_admin_review',
    'approved_for_mvp_access',
    'on_hold',
    'auth_admin_qualification_decision',
    'Consent Evidence',
    'Provisioning Readiness',
    'Handoff notes',
) as $needle) {
    qa05_assert_contains($report, $needle, "Admin UAT report must document {$needle}.");
}

qa05_assert_contains($plugin, "add_management_page(\n            'ABiT Signup Review'", 'Admin signup review page must be registered.');
qa05_assert_contains($plugin, "current_user_can('list_users')", 'Admin review surface must require list_users capability.');
qa05_assert_contains($plugin, 'render_signup_review_page', 'Admin review queue renderer must exist.');
qa05_assert_contains($plugin, 'render_signup_review_detail_page', 'Admin review detail renderer must exist.');
qa05_assert_contains($plugin, 'render_signup_review_decision_panel', 'Admin decision panel must exist.');
qa05_assert_contains($plugin, "wp_nonce_field('abit_saas_auth_admin_decision_'", 'Admin decision form must include a nonce.');
qa05_assert_contains($plugin, "name=\"action\" value=\"abit_saas_auth_admin_decision\"", 'Admin decision form must post the expected action.');
qa05_assert_contains($plugin, "add_action('admin_post_abit_saas_auth_admin_decision'", 'Admin decision post handler must be registered.');

foreach (array(
    "'approve' => self::REVIEW_STATUS_APPROVED",
    "'hold' => self::REVIEW_STATUS_ON_HOLD",
    "'reject' => self::REVIEW_STATUS_REJECTED",
    "'request_info' => self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED",
) as $transition) {
    qa05_assert_contains($plugin, $transition, "Admin decision transition missing: {$transition}.");
}

foreach (array(
    'admin_owner_user_id',
    'handoff_team',
    'handoff_priority',
    'handoff_next_action',
    'handoff_follow_up_date',
    'source_campaign',
    'handoff_notes',
    'handoff_queue_status',
) as $field) {
    qa05_assert_contains($plugin, $field, "Admin UAT implementation must include {$field}.");
}

foreach (array(
    'Signup Data',
    'Company Profile',
    'Module Interests',
    'Risk Indicators',
    'Consent Evidence',
    'Provisioning Readiness',
    'Audit Trail',
) as $panel) {
    qa05_assert_contains($plugin, $panel, "Admin detail page must include the {$panel} panel.");
}

qa05_assert_matches(
    $plugin,
    "/self::audit_event\\(\\s*'auth_admin_qualification_decision'[\\s\\S]+?'previous_review_status'[\\s\\S]+?'review_status'[\\s\\S]+?'handoff_queue_status'/",
    'Admin decisions must write audit evidence for status changes and handoff state.'
);

qa05_assert_matches(
    $plugin,
    "/private static function handoff_queue_status[\\s\\S]+REVIEW_STATUS_APPROVED[\\s\\S]+return 'completed';[\\s\\S]+REVIEW_STATUS_ON_HOLD[\\s\\S]+return 'held';/",
    'Handoff queue status must map approved/rejected decisions to completed and held decisions to held.'
);

echo "QA-05 admin UAT checks passed.\n";
