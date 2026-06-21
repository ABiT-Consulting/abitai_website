<?php
/**
 * LCH-06 two-week post-launch review coverage checks.
 *
 * Run from the repository root:
 * php tests/two-week-post-launch-review.php
 */

$root = dirname(__DIR__);
$review_path = $root . '/docs/two-week-post-launch-review.md';
$analytics_path = $root . '/docs/auth-analytics-funnel-event-taxonomy.md';
$qa06_path = $root . '/docs/qa-06-regression-defect-closure-report.md';
$security_path = $root . '/docs/auth-threat-model-security-review.md';
$monitoring_test_path = $root . '/tests/launch-monitoring-dashboard-coverage.php';

$review = file_get_contents($review_path);
$analytics = file_get_contents($analytics_path);
$qa06 = file_get_contents($qa06_path);
$security = file_get_contents($security_path);
$monitoring_test = file_get_contents($monitoring_test_path);

if ($review === false) {
    throw new RuntimeException('Could not read two-week post-launch review.');
}

if ($analytics === false) {
    throw new RuntimeException('Could not read analytics funnel event taxonomy.');
}

if ($qa06 === false) {
    throw new RuntimeException('Could not read QA-06 regression report.');
}

if ($security === false) {
    throw new RuntimeException('Could not read security review.');
}

if ($monitoring_test === false) {
    throw new RuntimeException('Could not read launch monitoring coverage test.');
}

function lch06_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function lch06_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

lch06_assert_contains($review, 'Task: `TASK-2026-02037 / LCH-06`', 'Review must identify the ERP task.');
lch06_assert_contains($review, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Review must reference the source task tree brief.');
lch06_assert_contains($review, 'ERP acceptance test: Review creates prioritized backlog for conversion, onboarding, admin, and security improvements.', 'Review must quote the ERP acceptance test.');
lch06_assert_contains($review, 'Result: Pass.', 'Review must mark the acceptance test result as pass.');

foreach (array(
    '## Metrics Review',
    '## Defect Review',
    '## Conversion Analysis',
    '## Prioritized Backlog',
    '## Next Iteration Review Cadence',
    '## Validation Commands',
) as $section) {
    lch06_assert_contains($review, $section, "Review must include {$section}.");
}

foreach (array(
    'auth_signup_started',
    'auth_onboarding_completed',
    'Verification sent',
    'Login health',
    'Password reset',
    'Admin review',
    'Security and abuse',
) as $metric) {
    lch06_assert_contains($review, $metric, "Metrics review must include {$metric}.");
}

foreach (array(
    'QA-06-C0',
    'QA-06-H0',
    'QA-04-P1',
    'SEC-08-M1',
    'SEC-08-M2',
    'SEC-08-M3',
    'SEC-08-L3',
) as $defect_id) {
    lch06_assert_contains($review, $defect_id, "Defect review must include {$defect_id}.");
}

foreach (array(
    'LCH-06-CNV-01',
    'LCH-06-CNV-02',
    'LCH-06-CNV-03',
    'LCH-06-ONB-01',
    'LCH-06-ONB-02',
    'LCH-06-ADM-01',
    'LCH-06-ADM-02',
    'LCH-06-SEC-01',
    'LCH-06-SEC-02',
    'LCH-06-SEC-03',
) as $backlog_id) {
    lch06_assert_contains($review, $backlog_id, "Prioritized backlog must include {$backlog_id}.");
}

foreach (array(
    '| P0 | LCH-06-CNV-01 | Conversion |',
    '| P1 | LCH-06-ONB-01 | Onboarding |',
    '| P0 | LCH-06-ADM-01 | Admin |',
    '| P0 | LCH-06-SEC-01 | Security |',
) as $priority_row) {
    lch06_assert_contains($review, $priority_row, "Backlog missing required prioritized row {$priority_row}.");
}

lch06_assert_matches(
    $review,
    '/\| P0 \| LCH-06-CNV-01 \| Conversion \|[^|]+\| Product\/Engineering \|[^|]+deduplicated conversion rates/',
    'Top conversion backlog item must have owner and measurable acceptance criteria.'
);

lch06_assert_matches(
    $review,
    '/\| P0 \| LCH-06-SEC-01 \| Security \|[^|]+abit_saas_auth_review[^|]+\| Engineering\/Security \|/',
    'Top security backlog item must address the dedicated admin review capability.'
);

lch06_assert_contains($analytics, 'MVP funnel reports should calculate these conversions:', 'Analytics taxonomy must define funnel reporting.');
lch06_assert_contains($qa06, 'Open critical defects: 0.', 'QA-06 report must confirm zero open critical defects.');
lch06_assert_contains($qa06, 'Open high defects: 0.', 'QA-06 report must confirm zero open high defects.');
lch06_assert_contains($security, 'SEC-08-M1', 'Security review must track dedicated capability follow-up.');
lch06_assert_contains($monitoring_test, 'auth_monitoring_metrics', 'Monitoring coverage must require live launch metrics.');

echo "Two-week post-launch review checks passed.\n";
