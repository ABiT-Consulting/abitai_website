<?php
/**
 * QA-02 auth E2E documentation and selector coverage checks.
 *
 * Run from the repository root:
 * php tests/auth-e2e-coverage.php
 */

$root = dirname(__DIR__);
$doc_path = $root . '/docs/auth-e2e-test-scripts.md';
$template_path = $root . '/wp-content/themes/astra/template-auth.php';
$functions_path = $root . '/wp-content/themes/astra/functions.php';

$doc = file_get_contents($doc_path);
$template = file_get_contents($template_path);
$functions = file_get_contents($functions_path);

if ($doc === false) {
    throw new RuntimeException('Could not read auth E2E test scripts.');
}

if ($template === false) {
    throw new RuntimeException('Could not read auth template.');
}

if ($functions === false) {
    throw new RuntimeException('Could not read Astra functions.');
}

function qa02_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function qa02_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

qa02_assert_contains($doc, 'Task: TASK-2026-02027 / QA-02', 'E2E scripts must identify the ERP task.');
qa02_assert_contains($doc, 'ERP acceptance test', 'E2E scripts must call out the ERP acceptance test.');
qa02_assert_contains($doc, 'PROJ-0130_abit_saas_auth_task_tree.md', 'E2E scripts must reference the source task tree brief.');
qa02_assert_contains($doc, '## Evidence Required', 'E2E scripts must define evidence requirements.');
qa02_assert_contains($doc, 'AUTH_E2E_BASE_URL', 'E2E scripts must define target environment configuration.');

$required_tests = [
    '## E2E-AUTH-001 Signup To Verified Onboarding',
    '## E2E-AUTH-002 Status-Aware Login Routing',
    '## E2E-AUTH-003 Signup Validation Failures',
    '## E2E-AUTH-004 Verification Resend And Token Failures',
    '## E2E-AUTH-005 Password Reset Happy And Failure Paths',
    '## E2E-AUTH-006 Product Access Gates',
];

foreach ($required_tests as $section) {
    qa02_assert_contains($doc, $section, "Missing required E2E test section: {$section}");
}

$required_trace_ids = [
    'QA-AUTH-SIGNUP-001',
    'QA-AUTH-VERIFY-001',
    'QA-AUTH-ONBOARD-001',
    'QA-AUTH-LOGIN-001',
    'QA-AUTH-LOGIN-004',
    'QA-AUTH-RESET-001',
    'QA-AUTH-SEC-001',
];

foreach ($required_trace_ids as $id) {
    qa02_assert_contains($doc, $id, "Missing traceability to {$id}.");
}

$required_selectors = [
    '#abit-auth-signup-name',
    '#abit-auth-signup-email',
    '#abit-auth-signup-password',
    '#abit-auth-signup-confirm-password',
    '#abit-auth-signup-consent',
    '[data-auth-next-step]',
    '#abit-auth-company-name',
    '#abit-auth-country-region',
    '#abit-auth-job-title',
    '#abit-auth-company-size',
    '#abit-auth-industry',
    '#abit-auth-business-description',
    '[data-auth-next-company-step]',
    'input[name="erp_module_interest[]"]',
    '[data-auth-submit]',
    '#abit-auth-email',
    '#abit-auth-password',
    '#abit-auth-reset-email',
    '#abit-auth-new-password',
    '#abit-auth-confirm-new-password',
];

foreach ($required_selectors as $selector) {
    qa02_assert_contains($doc, $selector, "E2E scripts must reference selector {$selector}.");
}

$required_routes = [
    '/auth/signup',
    '/auth/sign-in',
    '/auth/verify',
    '/auth/reset',
    '/auth/reset-password',
    '/dashboard',
    '/api/auth/me',
    '/api/provisioning/request',
];

foreach ($required_routes as $route) {
    qa02_assert_contains($doc, $route, "E2E scripts must cover route {$route}.");
}

$required_fixture_users = [
    'unverified@abit.ai',
    'onboarding@abit.ai',
    'review@abit.ai',
    'approved@abit.ai',
    'info@abit.ai',
    'rejected@abit.ai',
    'blocked@abit.ai',
];

foreach ($required_fixture_users as $email) {
    qa02_assert_contains($doc, $email, "E2E scripts must document fixture user {$email}.");
    qa02_assert_contains($functions, "'{$email}'", "Fixture user {$email} must exist in mock auth users.");
}

$template_contract = [
    'data-auth-signup-form',
    'data-auth-signin-form',
    'data-auth-reset-request-form',
    'data-auth-reset-password-form',
    'data-auth-stepper-item="account"',
    'data-auth-stepper-item="company"',
    'data-auth-stepper-item="modules"',
    'name="action" value="abitai_user_signup"',
    'name="action" value="abitai_mock_sign_in"',
    'name="action" value="abitai_mock_password_reset_request"',
    'name="action" value="abitai_mock_password_reset_submit"',
    'abit-auth-verification-status',
];

foreach ($template_contract as $needle) {
    qa02_assert_contains($template, $needle, "Auth template must expose testable contract: {$needle}");
}

qa02_assert_contains($functions, "wp_safe_redirect( add_query_arg( array( 'signup_success' => '1', 'email' => \$email ), \$success_redirect_url ) );", 'Signup handler must redirect to success path with email for verify prompt.');
qa02_assert_contains($functions, "case 'pending_email_verification':", 'Mock login routing must include pending email verification.');
qa02_assert_contains($functions, "home_url( '/auth/verify' )", 'Mock login routing must route unverified users to verify page.');
qa02_assert_contains($functions, "home_url( '/dashboard' )", 'Mock login routing must route status-aware users to dashboard gate.');

qa02_assert_matches(
    $doc,
    '/E2E-AUTH-001[\s\S]+\/auth\/verify\?state=sent[\s\S]+\/auth\/verify\?state=success[\s\S]+\/dashboard\?status=onboarding_required/',
    'Signup acceptance script must cover submitted verify prompt, verified state, and onboarding dashboard gate.'
);

qa02_assert_matches(
    $doc,
    '/E2E-AUTH-002[\s\S]+unverified@abit\.ai[\s\S]+onboarding@abit\.ai[\s\S]+review@abit\.ai[\s\S]+approved@abit\.ai[\s\S]+rejected@abit\.ai[\s\S]+blocked@abit\.ai/',
    'Status-aware login script must cover core success and blocked states.'
);

qa02_assert_contains($doc, 'Product access is not available until the request becomes approved.', 'E2E scripts must assert product-access gating.');
qa02_assert_contains($doc, 'Reset requests are non-disclosing.', 'E2E scripts must assert reset non-disclosure.');
qa02_assert_contains($doc, 'Duplicate or existing email runs should produce the same neutral verify/check-email experience', 'E2E scripts must assert duplicate signup non-disclosure.');

echo "Auth E2E coverage checks passed.\n";
