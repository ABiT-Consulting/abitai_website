<?php
/**
 * Consent and privacy audit checks for the SaaS auth API.
 *
 * Run from the repository root:
 * php tests/consent-privacy-audit.php
 */

$plugin = file_get_contents(dirname(__DIR__) . '/wp-content/mu-plugins/abit-saas-auth.php');
if ($plugin === false) {
    throw new RuntimeException('Could not read auth plugin.');
}

function assert_contains_text(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function assert_not_contains_text(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

assert_contains_text($plugin, "\$errors['terms_privacy_acceptance']", 'Signup must reject missing terms/privacy consent.');
assert_contains_text($plugin, "'terms_privacy_accepted_at' => \$now", 'Access requests must mirror the accepted timestamp.');
assert_contains_text($plugin, "'terms_version' => \$legal['terms_version']", 'Access requests and audit records must store server-resolved terms version.');
assert_contains_text($plugin, "'privacy_version' => \$legal['privacy_version']", 'Access requests and audit records must store server-resolved privacy version.');
assert_contains_text($plugin, "'consent_text_version' => \$legal['consent_text_version']", 'Consent audit must store consent text version.');
assert_contains_text($plugin, "'ip_hash' => self::hmac(self::request_ip())", 'Consent audit must store a keyed IP hash.');
assert_contains_text($plugin, "'user_agent_hash' => self::hmac(\$_SERVER['HTTP_USER_AGENT'] ?? '')", 'Consent audit must store a keyed user-agent hash.');
assert_contains_text($plugin, "'hash_key_version' => self::hash_key_version()", 'Consent audit must store hash key version.');
assert_contains_text($plugin, "private const CONSENT_CAPTURE_SOURCE_SIGNUP = 'signup_onboarding'", 'Consent capture source should identify signup onboarding.');
assert_contains_text($plugin, "'capture_source' => self::CONSENT_CAPTURE_SOURCE_SIGNUP", 'Consent audit events and records should use the signup onboarding source.');
assert_contains_text($plugin, "private const CONSENT_RETENTION_RULE = 'active_plus_7_years_after_closure'", 'Consent retention rule must be defined in code.');
assert_contains_text($plugin, "'retention_rule' => self::CONSENT_RETENTION_RULE", 'Consent audit records must store the retention rule.');
assert_contains_text($plugin, "self::redacted_hash((string) (\$consent['ip_hash'] ?? ''))", 'Admin consent evidence must display redacted IP hash evidence.');
assert_contains_text($plugin, "self::redacted_hash((string) (\$consent['user_agent_hash'] ?? ''))", 'Admin consent evidence must display redacted user-agent hash evidence.');
assert_not_contains_text($plugin, "'ip_address'", 'Consent audit implementation must not persist raw IP address fields.');
assert_not_contains_text($plugin, "'user_agent'", 'Consent audit implementation must not persist raw user-agent fields.');

echo "Consent privacy audit tests passed.\n";
