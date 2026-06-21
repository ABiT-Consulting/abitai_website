<?php
/**
 * LCH-04 launch feature flag and staged rollout coverage checks.
 *
 * Run from the repository root:
 * php tests/launch-rollout-coverage.php
 */

$root = dirname(__DIR__);
$doc_path = $root . '/docs/launch-rollout-plan.md';
$plugin_path = $root . '/wp-content/mu-plugins/abit-saas-auth.php';
$sample_config_path = $root . '/wp-config-sample.php';
$api_contract_path = $root . '/docs/backend-auth-api-contract.md';

$doc = file_get_contents($doc_path);
$plugin = file_get_contents($plugin_path);
$sample_config = file_get_contents($sample_config_path);
$api_contract = file_get_contents($api_contract_path);

if ($doc === false) {
    throw new RuntimeException('Could not read launch rollout plan.');
}

if ($plugin === false) {
    throw new RuntimeException('Could not read ABiT SaaS auth plugin.');
}

if ($sample_config === false) {
    throw new RuntimeException('Could not read wp-config-sample.php.');
}

if ($api_contract === false) {
    throw new RuntimeException('Could not read backend auth API contract.');
}

function lch04_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function lch04_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

lch04_assert_contains($doc, 'Task: `TASK-2026-02035 / LCH-04`', 'Rollout plan must identify the ERP task.');
lch04_assert_contains($doc, 'PROJ-0130_abit_saas_auth_task_tree.md', 'Rollout plan must reference the source task tree.');
lch04_assert_contains($doc, 'internal beta completes signup-to-onboarding before broader rollout', 'Rollout plan must state the ERP acceptance test.');

$required_doc_sections = [
    '## Feature Flag Contract',
    '## Stages',
    '## Acceptance Test',
    '## Promotion Controls',
    '## API Contract',
    '## Validation Commands',
];

foreach ($required_doc_sections as $section) {
    lch04_assert_contains($doc, $section, "Rollout plan missing required section: {$section}");
}

$required_config_names = [
    'ABIT_SAAS_AUTH_LAUNCH_ENABLED',
    'ABIT_SAAS_AUTH_ROLLOUT_STAGE',
    'ABIT_SAAS_AUTH_INTERNAL_BETA_EMAILS',
    'ABIT_SAAS_AUTH_INTERNAL_BETA_DOMAINS',
    'ABIT_SAAS_AUTH_CUSTOMER_BETA_EMAILS',
    'ABIT_SAAS_AUTH_CUSTOMER_BETA_DOMAINS',
];

foreach ($required_config_names as $name) {
    lch04_assert_contains($doc, $name, "Rollout plan must document {$name}.");
    lch04_assert_contains($sample_config, "getenv( '{$name}' )", "wp-config-sample.php must expose {$name} from the environment.");
    lch04_assert_contains($plugin, $name, "Auth plugin must read {$name}.");
}

$required_stages = [
    'disabled',
    'internal_beta',
    'customer_beta',
    'public',
];

foreach ($required_stages as $stage) {
    lch04_assert_contains($doc, $stage, "Rollout plan must document {$stage} stage.");
    lch04_assert_contains($plugin, "'{$stage}'", "Auth plugin must implement {$stage} stage.");
}

$plugin_contract = [
    'private static function signup_rollout_access',
    'private static function product_access_rollout_access',
    'private static function launch_rollout_config',
    'private static function rollout_not_available_response',
    'auth_signup_rollout_blocked',
    'launch_rollout_not_available',
    "'launch_rollout' => self::public_rollout_payload",
    '\'product_access\' => !$account_state[\'locked\'] && $account_state[\'review_status\'] === self::REVIEW_STATUS_APPROVED && !empty($rollout_access[\'allowed\'])',
    "\$missing[] = 'launch_rollout';",
];

foreach ($plugin_contract as $needle) {
    lch04_assert_contains($plugin, $needle, "Auth plugin missing rollout contract: {$needle}");
}

lch04_assert_matches(
    $plugin,
    '/register\(WP_REST_Request \$request\)[\s\S]+signup_rollout_access\(\$data\[\'business_email\'\]\)[\s\S]+rollout_not_available_response/',
    'Registration must check rollout eligibility before creating access requests.'
);

lch04_assert_contains($api_contract, 'gate.launch_rollout', 'API contract must document authenticated rollout gate.');
lch04_assert_contains($api_contract, 'launch_rollout_not_available', 'API contract must document blocked signup response.');
lch04_assert_contains($api_contract, '`internal_beta`, `customer_beta`, and `public`', 'API contract must document rollout stages.');

echo "Launch rollout coverage checks passed.\n";
