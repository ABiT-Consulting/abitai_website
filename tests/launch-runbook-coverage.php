<?php
/**
 * LCH-02 launch runbook and rollback plan coverage checks.
 *
 * Run from the repository root:
 * php tests/launch-runbook-coverage.php
 */

$root = dirname(__DIR__);
$doc_path = $root . '/docs/launch-runbook-rollback-plan.md';
$workflow_path = $root . '/.github/workflows/cpanel-deploy.yml';
$cpanel_path = $root . '/.cpanel.yml';

$doc = file_get_contents($doc_path);
$workflow = file_get_contents($workflow_path);
$cpanel = file_get_contents($cpanel_path);

if ($doc === false) {
    throw new RuntimeException('Could not read launch runbook.');
}

if ($workflow === false) {
    throw new RuntimeException('Could not read cPanel deployment workflow.');
}

if ($cpanel === false) {
    throw new RuntimeException('Could not read .cpanel.yml.');
}

function lch02_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

lch02_assert_contains($doc, 'Task: `TASK-2026-02033 / LCH-02`', 'Runbook must identify the ERP task.');
lch02_assert_contains($doc, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Runbook must reference the source task tree.');
lch02_assert_contains($doc, 'signup, email verification, login, and password reset', 'Runbook must state ERP acceptance coverage.');

$required_sections = [
    '## Pre-Launch Checklist',
    '## Deployment Procedure',
    '## Post-Deploy Technical Verification',
    '## Auth Smoke Test',
    '### Signup And Verification',
    '### Login Routing',
    '### Password Reset',
    '### Admin Review Smoke',
    '## Monitoring Window',
    '## Rollback Triggers',
    '## Rollback Plan',
    '## Support Escalation',
    '## Incident Response',
    '## Evidence Record',
];

foreach ($required_sections as $section) {
    lch02_assert_contains($doc, $section, "Runbook missing required section: {$section}");
}

$required_deploy_terms = [
    'Deploy to cPanel',
    'workflow_dispatch',
    'CPANEL_HOST',
    'CPANEL_USER',
    'CPANEL_TOKEN',
    'CPANEL_REPO_ROOT',
    'CPANEL_BRANCH',
    'VersionControlDeployment/create',
    'deploy_id',
    '${HOME}/public_html/abit.ai/',
    '${HOME}/abit.ai/',
    '${HOME}/public_html/',
    'wp-content/themes/astra/functions.php',
    'wp-content/themes/astra/front-page.php',
    'web.config',
];

foreach ($required_deploy_terms as $term) {
    lch02_assert_contains($doc, $term, "Runbook missing deployment detail: {$term}");
}

$required_smoke_terms = [
    'https://abit.ai/auth/signup',
    '/auth/verify?state=sent',
    'Verification token works once.',
    'https://abit.ai/auth/sign-in',
    '/api/auth/me',
    'https://abit.ai/auth/reset',
    'unknown email',
    'Reset request is non-disclosing',
    'Tools > ABiT Signup Review',
    'auth_admin_qualification_decision',
];

foreach ($required_smoke_terms as $term) {
    lch02_assert_contains($doc, $term, "Runbook missing smoke-test detail: {$term}");
}

$required_rollback_terms = [
    'P0',
    'P1',
    'Re-deploy previous good commit',
    'cPanel file restore',
    'Database restore',
    'Feature/config disable',
    'ABIT_SAAS_PROVISIONING_CAPACITY_READY=false',
    'No unapproved product access is possible.',
];

foreach ($required_rollback_terms as $term) {
    lch02_assert_contains($doc, $term, "Runbook missing rollback detail: {$term}");
}

$required_support_terms = [
    'Support lead',
    'Operations owner',
    'Security owner',
    'Do not ask for or collect the raw token.',
    'Never request the password.',
    'Treat as P0 security incident.',
];

foreach ($required_support_terms as $term) {
    lch02_assert_contains($doc, $term, "Runbook missing support/escalation detail: {$term}");
}

$workflow_terms = [
    'name: Deploy to cPanel',
    'VersionControlDeployment/create',
    'CPANEL_HOST',
    'CPANEL_USER',
    'CPANEL_TOKEN',
    'CPANEL_REPO_ROOT',
];

foreach ($workflow_terms as $term) {
    lch02_assert_contains($workflow, $term, "Workflow missing expected deployment contract: {$term}");
    lch02_assert_contains($doc, $term, "Runbook must document workflow contract: {$term}");
}

$cpanel_terms = [
    'DEPLOYPATH="${HOME}/public_html/"',
    'wp-content/themes/astra/functions.php',
    'wp-content/themes/astra/front-page.php',
    'web.config',
];

foreach ($cpanel_terms as $term) {
    lch02_assert_contains($cpanel, $term, ".cpanel.yml missing expected deployment contract: {$term}");
    lch02_assert_contains($doc, $term, "Runbook must document cPanel deployment contract: {$term}");
}

echo "Launch runbook coverage checks passed.\n";
