<?php
/**
 * Session, token, CSRF, and CORS security checks for the SaaS auth API.
 *
 * Run from the repository root:
 * php tests/session-token-security.php
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

assert_contains_text($plugin, "add_filter('send_auth_cookies', [__CLASS__, 'send_hardened_auth_cookies'], 10, 6)", 'Auth cookies must be emitted through the hardened cookie sender.');
assert_contains_text($plugin, "'secure' => \$secure", 'Hardened auth cookies must set the Secure flag.');
assert_contains_text($plugin, "'httponly' => true", 'Hardened auth cookies must set HttpOnly.');
assert_contains_text($plugin, "'samesite' => self::AUTH_COOKIE_SAMESITE", 'Hardened auth cookies must set SameSite.');
assert_contains_text($plugin, "private const AUTH_COOKIE_SAMESITE = 'Lax'", 'SameSite policy must be explicitly defined.');
assert_contains_text($plugin, "private const SESSION_IDLE_SECONDS = 43200", 'Non-remembered sessions should have a bounded idle lifetime.');
assert_contains_text($plugin, "private const SESSION_REMEMBER_SECONDS = 604800", 'Remembered sessions should have a bounded persistent lifetime.');

assert_contains_text($plugin, "add_action('after_password_reset', [__CLASS__, 'revoke_sessions_after_password_reset'], 10, 2)", 'Password resets must trigger session revocation.');
assert_contains_text($plugin, "WP_Session_Tokens::get_instance((int) \$user->ID)->destroy_all()", 'Password reset must revoke all existing sessions.');
assert_contains_text($plugin, "'auth_sessions_revoked_after_password_reset'", 'Password reset session revocation must be audited.');

assert_contains_text($plugin, "add_filter('rest_pre_dispatch', [__CLASS__, 'enforce_rest_request_security'], 10, 3)", 'REST auth routes must pass through security enforcement.');
assert_contains_text($plugin, "private static function enforce_pretty_request_security", 'Pretty API routes must pass through security enforcement.');
assert_contains_text($plugin, "'csrf_check_failed'", 'Unsafe authenticated requests must have a stable CSRF failure code.');
assert_contains_text($plugin, "'cors_origin_denied'", 'Disallowed origins must have a stable CORS failure code.');
assert_contains_text($plugin, "'/' . self::REST_NAMESPACE . '/auth/logout'", 'Logout must require CSRF protection.');
assert_contains_text($plugin, "'/' . self::REST_NAMESPACE . '/provisioning/request'", 'Provisioning request must require CSRF protection.');
assert_contains_text($plugin, "'/' . self::REST_NAMESPACE . '/workspace/slug/validate'", 'Workspace slug validation must require CSRF protection.');
assert_contains_text($plugin, "preg_match('#^/' . preg_quote(self::REST_NAMESPACE, '#') . '/admin/#'", 'Admin auth API routes must require CSRF protection.');
assert_contains_text($plugin, "is_allowed_http_origin(\$origin)", 'CORS enforcement must use the WordPress allowed-origin policy.');
assert_contains_text($plugin, "remove_filter('rest_pre_serve_request', 'rest_send_cors_headers')", 'Default permissive REST CORS headers must be disabled for auth routes.');

assert_contains_text($plugin, "\$token_hash = self::hmac(\$token)", 'Email verification tokens must be stored as keyed hashes.');
assert_contains_text($plugin, "private static function insert_verification_token(int \$user_id, int \$access_request_id, string \$token_hash", 'Verification token inserts must accept a token hash, not a raw token.');
assert_contains_text($plugin, "'token_hash' => \$token_hash", 'Verification token persistence must store hashes only.');

echo "Session and token security tests passed.\n";
