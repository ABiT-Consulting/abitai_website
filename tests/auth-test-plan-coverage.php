<?php
/**
 * QA-01 auth test-plan coverage checks.
 *
 * Run from the repository root:
 * php tests/auth-test-plan-coverage.php
 */

$doc_path = dirname(__DIR__) . '/docs/auth-test-plan.md';
$doc = file_get_contents($doc_path);
if ($doc === false) {
    throw new RuntimeException('Could not read auth test plan.');
}

function qa01_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function qa01_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

qa01_assert_contains($doc, 'Task: TASK-2026-02026 / QA-01', 'Test plan must identify the ERP task.');
qa01_assert_contains($doc, '## Acceptance Criteria Traceability', 'Test plan must include traceability matrix.');
qa01_assert_contains($doc, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Test plan must reference the source task tree brief.');

$required_sections = [
    '## Signup Test Cases',
    '## Login Test Cases',
    '## Verification Test Cases',
    '## Resend Test Cases',
    '## Password Reset Test Cases',
    '## Onboarding Test Cases',
    '## Admin Test Cases',
    '## Accessibility Test Cases',
    '## Security Test Cases',
];

foreach ($required_sections as $section) {
    qa01_assert_contains($doc, $section, "Missing required section: {$section}");
}

$required_ids = [
    'QA-AUTH-SIGNUP-001',
    'QA-AUTH-LOGIN-001',
    'QA-AUTH-VERIFY-001',
    'QA-AUTH-RESEND-001',
    'QA-AUTH-RESET-001',
    'QA-AUTH-ONBOARD-001',
    'QA-AUTH-ADMIN-001',
    'QA-AUTH-A11Y-001',
    'QA-AUTH-SEC-001',
];

foreach ($required_ids as $id) {
    qa01_assert_contains($doc, $id, "Missing required baseline test ID: {$id}");
}

qa01_assert_matches(
    $doc,
    '/\| Public signup entry point is available from the website\. \| [^|]*QA-AUTH-SIGNUP-\d{3}[^|]*\|/',
    'Signup acceptance criterion must map to at least one signup test.'
);
qa01_assert_matches(
    $doc,
    '/\| Failed login attempts return a generic error that does not disclose whether the email exists\. \| [^|]*QA-AUTH-LOGIN-\d{3}[^|]*\|/',
    'Login non-disclosure acceptance criterion must map to at least one login test.'
);
qa01_assert_matches(
    $doc,
    '/\| Verification links are single-use and expire\. \| [^|]*QA-AUTH-VERIFY-\d{3}[^|]*\|/',
    'Verification token acceptance criterion must map to at least one verification test.'
);
qa01_assert_matches(
    $doc,
    '/\| Expired or consumed verification links offer a resend path\. \| [^|]*QA-AUTH-RESEND-\d{3}[^|]*\|/',
    'Resend acceptance criterion must map to at least one resend test.'
);
qa01_assert_matches(
    $doc,
    '/\| Users can request a password reset by email\. \| [^|]*QA-AUTH-RESET-\d{3}[^|]*\|/',
    'Reset acceptance criterion must map to at least one reset test.'
);
qa01_assert_matches(
    $doc,
    '/\| Verified users must complete required onboarding fields before reaching the SaaS application\. \| [^|]*QA-AUTH-ONBOARD-\d{3}[^|]*\|/',
    'Onboarding acceptance criterion must map to at least one onboarding test.'
);
qa01_assert_matches(
    $doc,
    '/\| Admins can approve, reject, or request more information\. \| [^|]*QA-AUTH-ADMIN-\d{3}[^|]*\|/',
    'Admin acceptance criterion must map to at least one admin test.'
);
qa01_assert_matches(
    $doc,
    '/\| Auth UI states meet accessibility requirements[^|]*\| [^|]*QA-AUTH-A11Y-\d{3}[^|]*\|/',
    'Accessibility acceptance criterion must map to at least one accessibility test.'
);
qa01_assert_matches(
    $doc,
    '/\| Auth flows meet security requirements[^|]*\| [^|]*QA-AUTH-SEC-\d{3}[^|]*\|/',
    'Security acceptance criterion must map to at least one security test.'
);

echo "Auth test-plan coverage checks passed.\n";

