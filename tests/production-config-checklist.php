<?php
/**
 * LCH-01 production configuration checklist coverage checks.
 *
 * Run from the repository root:
 * php tests/production-config-checklist.php
 */

$doc_path = dirname(__DIR__) . '/docs/production-configuration-secrets-checklist.md';
$sample_config_path = dirname(__DIR__) . '/wp-config-sample.php';

$doc = file_get_contents($doc_path);
if ($doc === false) {
    throw new RuntimeException('Could not read production configuration checklist.');
}

$sample_config = file_get_contents($sample_config_path);
if ($sample_config === false) {
    throw new RuntimeException('Could not read wp-config-sample.php.');
}

function lch01_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function lch01_assert_not_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

lch01_assert_contains($doc, 'Task: `TASK-2026-02032 / LCH-01`', 'Checklist must identify the ERP task.');
lch01_assert_contains($doc, '## Required Production Variables', 'Checklist must include required production variables.');
lch01_assert_contains($doc, '## Domain and HTTPS Checklist', 'Checklist must include domain and HTTPS checks.');
lch01_assert_contains($doc, '## Transactional Email Checklist', 'Checklist must include email checks.');
lch01_assert_contains($doc, '## Consent Version Checklist', 'Checklist must include consent version checks.');
lch01_assert_contains($doc, '## Rate Limit and Edge Protection Checklist', 'Checklist must include rate-limit checks.');
lch01_assert_contains($doc, '## Monitoring Checklist', 'Checklist must include monitoring checks.');
lch01_assert_contains($doc, 'secret values intentionally redacted', 'Checklist must state that secret values are redacted.');
lch01_assert_contains($doc, 'No secret values in Git', 'Checklist must include no-secret-in-Git rule.');

$required_names = [
    'WP_HOME',
    'WP_SITEURL',
    'AUTH_KEY',
    'SECURE_AUTH_KEY',
    'LOGGED_IN_KEY',
    'NONCE_KEY',
    'AUTH_SALT',
    'SECURE_AUTH_SALT',
    'LOGGED_IN_SALT',
    'NONCE_SALT',
    'ABIT_SAAS_AUTH_HASH_KEY',
    'ABIT_SAAS_AUTH_HASH_KEY_VERSION',
    'ABIT_SAAS_TERMS_VERSION',
    'ABIT_SAAS_PRIVACY_VERSION',
    'ABIT_SAAS_CONSENT_TEXT_VERSION',
    'ABIT_SAAS_LEGAL_LOCALE',
    'ABIT_TRANSACTIONAL_MAIL_PROVIDER',
    'ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL',
    'ABIT_TRANSACTIONAL_MAIL_FROM_NAME',
    'ABIT_TRANSACTIONAL_MAIL_REPLY_TO',
    'ABIT_TRANSACTIONAL_SMTP_HOST',
    'ABIT_TRANSACTIONAL_SMTP_PORT',
    'ABIT_TRANSACTIONAL_SMTP_SECURE',
    'ABIT_TRANSACTIONAL_SMTP_AUTH',
    'ABIT_TRANSACTIONAL_SMTP_USERNAME',
    'ABIT_TRANSACTIONAL_SMTP_PASSWORD',
    'ABIT_SAAS_PROVISIONING_CAPACITY_READY',
    'CPANEL_HOST',
    'CPANEL_USER',
    'CPANEL_TOKEN',
    'CPANEL_REPO_ROOT',
];

foreach ($required_names as $name) {
    lch01_assert_contains($doc, $name, "Checklist missing required variable or setting: {$name}");
}

$sample_config_names = [
    'WP_HOME',
    'WP_SITEURL',
    'ABIT_SAAS_TERMS_VERSION',
    'ABIT_SAAS_PRIVACY_VERSION',
    'ABIT_SAAS_CONSENT_TEXT_VERSION',
    'ABIT_SAAS_LEGAL_LOCALE',
    'ABIT_SAAS_AUTH_HASH_KEY',
    'ABIT_SAAS_AUTH_HASH_KEY_VERSION',
    'ABIT_SAAS_PROVISIONING_CAPACITY_READY',
];

foreach ($sample_config_names as $name) {
    lch01_assert_contains($sample_config, "getenv( '{$name}' )", "wp-config-sample.php must expose {$name} from the environment.");
}

lch01_assert_contains($doc, '10 attempts per hour', 'Checklist must document signup app rate limit.');
lch01_assert_contains($doc, '20 attempts per 15 minutes', 'Checklist must document login app rate limit.');
lch01_assert_contains($doc, '5 attempts per 15 minutes', 'Checklist must document failed-login lockout threshold.');
lch01_assert_contains($doc, '30 attempts per 5 minutes', 'Checklist must document admin-sensitive app rate limit.');

$forbidden_secret_examples = [
    'put your unique phrase here',
    'smtp_password_here',
    'password123',
    'sk_live_',
    'AKIA',
];

foreach ($forbidden_secret_examples as $example) {
    lch01_assert_not_contains($doc, $example, "Checklist must not include example secret value: {$example}");
}

echo "Production configuration checklist checks passed.\n";
