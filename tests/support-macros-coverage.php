<?php
/**
 * LCH-03 support macros and help copy coverage checks.
 *
 * Run from the repository root:
 * php tests/support-macros-coverage.php
 */

$root = dirname(__DIR__);
$doc_path = $root . '/docs/support-macros-help-copy.md';
$ux_copy_path = $root . '/docs/auth-onboarding-ux-copy.md';
$runbook_path = $root . '/docs/launch-runbook-rollback-plan.md';

function lch03_read_file(string $path, string $label): string
{
    $contents = file_get_contents($path);

    if (false === $contents) {
        throw new RuntimeException("Could not read {$label}.");
    }

    return $contents;
}

function lch03_assert_contains(string $haystack, string $needle, string $message): void
{
    if (false === strpos($haystack, $needle)) {
        throw new RuntimeException($message);
    }
}

function lch03_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (1 !== preg_match($pattern, $haystack)) {
        throw new RuntimeException($message);
    }
}

$doc = lch03_read_file($doc_path, 'LCH-03 support macros document');
$ux_copy = lch03_read_file($ux_copy_path, 'auth onboarding UX copy');
$runbook = lch03_read_file($runbook_path, 'launch runbook');

lch03_assert_contains($doc, 'Task: `TASK-2026-02034 / LCH-03`', 'Support macros document must identify the ERP task.');
lch03_assert_contains($doc, 'Project: `PROJ-0130 abit_ai_website`', 'Support macros document must identify the project.');
lch03_assert_contains($doc, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Support macros document must reference the source task tree.');
lch03_assert_contains($doc, 'without engineering', 'Support macros document must state the normal-case support goal.');

foreach (array(
    '## Support Rules',
    '## Triage Checklist',
    '## Verification Macros',
    '## Login Macros',
    '## Password Reset Macros',
    '## Admin Hold Macro',
    '## Company Profile Macros',
    '## Internal Notes Template',
    '## Acceptance Checklist',
) as $section) {
    lch03_assert_contains($doc, $section, "Support macros document missing {$section}.");
}

foreach (array(
    'Verification Email Missing',
    'Verification Link Expired',
    'Verification Link Cannot Be Used',
    'Generic Sign-In Failure',
    'Email Verification Required',
    'Review Pending',
    'Access Unavailable Or Locked',
    'Reset Instructions Sent',
    'Reset Link Expired',
    'Reset Link Cannot Be Used',
    'Missing Or Incomplete Company Profile',
    'Company Profile Correction',
) as $macro) {
    lch03_assert_contains($doc, $macro, "Support macros document missing macro: {$macro}.");
}

foreach (array(
    'Do not send passwords',
    'Do not send the reset link or key',
    'Please do not forward the verification link or token',
    'without asking for raw tokens',
    'without account enumeration',
    'Do not send passwords, private credentials, or confidential system exports',
    'screenshot of the visible page without secrets',
) as $safe_handling) {
    lch03_assert_contains($doc, $safe_handling, "Support macros document missing safe-handling rule: {$safe_handling}.");
}

foreach (array(
    'Tools > ABiT Signup Review',
    'Tools > ABiT Email Observability',
    'Verification token works once.',
    'Reset request is non-disclosing',
    'Do not ask for or collect the raw token.',
    'Never request the password.',
    'Treat as P0 security incident.',
) as $runbook_term) {
    lch03_assert_contains($runbook, $runbook_term, "Launch runbook missing support escalation source term: {$runbook_term}.");
}

foreach (array(
    'Tools > ABiT Signup Review',
    'Tools > ABiT Email Observability',
    'raw verification token',
    'raw reset key',
    'without disclosing account existence',
    'Never ask for or collect a password',
    'P0 security incident',
) as $support_alignment_term) {
    lch03_assert_contains($doc, $support_alignment_term, "Support macros should align with runbook safety term: {$support_alignment_term}.");
}

foreach (array(
    'Resend verification email',
    'Send verification email',
    'Send reset email',
    'This account cannot sign in right now. Contact support for help.',
    'Workspace access is not available until',
) as $copy_term) {
    lch03_assert_contains($doc . $ux_copy, $copy_term, "Support macros and approved UX copy should cover: {$copy_term}.");
}

lch03_assert_matches(
    $doc,
    '/Verification Email Missing[\s\S]+If the business email you entered is eligible/',
    'Missing verification macro must avoid confirming account existence.'
);

lch03_assert_matches(
    $doc,
    '/Reset Instructions Sent[\s\S]+If an eligible account exists/',
    'Password reset macro must avoid confirming account existence.'
);

lch03_assert_matches(
    $doc,
    '/Admin Hold Macro[\s\S]+\{\{safe_hold_reason_or_next_step\}\}/',
    'Admin hold macro must include safe hold reason placeholder.'
);

lch03_assert_matches(
    $doc,
    '/Company Profile Macros[\s\S]+\{\{missing_company_fields\}\}/',
    'Company profile macro must include missing field placeholder.'
);

echo "LCH-03 support macros coverage checks passed.\n";
