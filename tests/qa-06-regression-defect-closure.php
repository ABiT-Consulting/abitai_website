<?php
/**
 * QA-06 regression and defect closure evidence checks.
 *
 * Usage:
 * php tests/qa-06-regression-defect-closure.php
 */

$root = dirname(__DIR__);
$report_path = $root . '/docs/qa-06-regression-defect-closure-report.md';
$security_report_path = $root . '/docs/auth-threat-model-security-review.md';
$qa04_report_path = $root . '/docs/qa-04-responsive-accessibility-report.md';
$qa05_report_path = $root . '/docs/qa-05-admin-uat-report.md';

function qa06_read_file(string $path, string $label): string
{
    $contents = file_get_contents($path);

    if (false === $contents) {
        throw new RuntimeException("Could not read {$label}.");
    }

    return $contents;
}

function qa06_assert_contains(string $haystack, string $needle, string $message): void
{
    if (false === strpos($haystack, $needle)) {
        throw new RuntimeException($message);
    }
}

function qa06_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (1 !== preg_match($pattern, $haystack)) {
        throw new RuntimeException($message);
    }
}

$report = qa06_read_file($report_path, 'QA-06 regression and defect closure report');
$security_report = qa06_read_file($security_report_path, 'auth threat-model security review');
$qa04_report = qa06_read_file($qa04_report_path, 'QA-04 responsive and accessibility report');
$qa05_report = qa06_read_file($qa05_report_path, 'QA-05 admin UAT report');

qa06_assert_contains($report, 'Task: `TASK-2026-02031 / QA-06`', 'QA-06 report must identify the ERP task.');
qa06_assert_contains($report, 'PROJ-0130_abit_saas_auth_task_tree.md', 'QA-06 report must reference the source task tree brief.');
qa06_assert_contains($report, 'Critical/high launch gate: Pass.', 'QA-06 report must explicitly pass the critical/high launch gate.');
qa06_assert_contains($report, 'Open critical defects: 0.', 'QA-06 report must state zero open critical defects.');
qa06_assert_contains($report, 'Open high defects: 0.', 'QA-06 report must state zero open high defects.');
qa06_assert_contains($report, 'ERP acceptance test: Critical/high defects are closed or explicitly deferred with owner and mitigation.', 'QA-06 report must quote the ERP acceptance test.');

foreach (array(
    '## Regression Scope',
    '## Regression Result Matrix',
    '## Launch Defect Decision List',
    '## Acceptance Test Result',
    '## Required Release Smoke Checks',
    '## Validation Commands',
    '## Residual Risk',
) as $section) {
    qa06_assert_contains($report, $section, "QA-06 report must include {$section}.");
}

foreach (array(
    'tests\\auth-test-plan-coverage.php',
    'tests\\auth-e2e-coverage.php',
    'tests\\auth-token-edge-cases.php',
    'tests\\auth-responsive-accessibility-qa.php',
    'tests\\admin-uat.php',
    'tests\\auth-threat-model-signoff.php',
    'tests\\session-token-security.php',
    'tests\\tenant-isolation.php',
    'tests\\consent-privacy-audit.php',
    'tests\\qa-06-regression-defect-closure.php',
) as $command) {
    qa06_assert_contains($report, $command, "QA-06 report must list validation command {$command}.");
}

foreach (array(
    'QA-06-C0',
    'QA-06-H0',
    'QA-04-P1',
    'SEC-08-M1',
    'SEC-08-M2',
    'SEC-08-M3',
    'SEC-08-L1',
    'SEC-08-L2',
    'SEC-08-L3',
) as $defect_id) {
    qa06_assert_contains($report, $defect_id, "QA-06 defect decision list must include {$defect_id}.");
}

qa06_assert_matches(
    $report,
    '/\| QA-06-C0 \| Critical \|[^|]+\| Closed \| Engineering \|[^|]+No open critical defects were found/',
    'Critical defect gate row must be closed with Engineering owner and closure decision.'
);

qa06_assert_matches(
    $report,
    '/\| QA-06-H0 \| High \|[^|]+\| Closed \| Engineering \|[^|]+No open high defects were found/',
    'High defect gate row must be closed with Engineering owner and closure decision.'
);

qa06_assert_matches(
    $report,
    '/\| SEC-08-M1 \| Medium \|[^|]+\| Deferred \| Engineering \|[^|]+Mitigation:/',
    'Deferred SEC-08-M1 must include owner and mitigation.'
);

qa06_assert_matches(
    $report,
    '/\| SEC-08-M2 \| Medium \|[^|]+\| Deferred \| Operations \|[^|]+Mitigation:/',
    'Deferred SEC-08-M2 must include owner and mitigation.'
);

qa06_assert_matches(
    $report,
    '/\| SEC-08-M3 \| Medium \|[^|]+\| Deferred \| Engineering \|[^|]+Mitigation:/',
    'Deferred SEC-08-M3 must include owner and mitigation.'
);

qa06_assert_contains($security_report, 'Open critical findings: 0.', 'Security signoff must have zero open critical findings.');
qa06_assert_contains($security_report, 'Open high findings: 0.', 'Security signoff must have zero open high findings.');
qa06_assert_contains($security_report, 'SEC-08-M1', 'Security report must track SEC-08-M1.');
qa06_assert_contains($security_report, 'SEC-08-M2', 'Security report must track SEC-08-M2.');
qa06_assert_contains($security_report, 'SEC-08-M3', 'Security report must track SEC-08-M3.');

qa06_assert_contains($qa04_report, 'No critical accessibility or mobile-blocking defects remain.', 'QA-04 report must close accessibility/mobile blockers.');
qa06_assert_contains($qa04_report, '4.70:1 after fix', 'QA-04 report must document the contrast defect closure.');
qa06_assert_contains($qa05_report, 'Result: Pass with environment limitation.', 'QA-05 admin UAT report must pass with the documented limitation.');
qa06_assert_contains($qa05_report, 'approved_for_mvp_access', 'QA-05 report must cover admin approval status.');
qa06_assert_contains($qa05_report, 'on_hold', 'QA-05 report must cover admin hold status.');

echo "QA-06 regression and defect closure checks passed.\n";
