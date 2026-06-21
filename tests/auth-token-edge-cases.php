<?php
/**
 * QA-03 verification and password-reset token edge-case checks.
 *
 * Task: TASK-2026-02028 / QA-03
 *
 * Run from the repository root:
 * php tests/auth-token-edge-cases.php
 */

$root = dirname(__DIR__);
$template_path = $root . '/wp-content/themes/astra/template-auth.php';
$functions_path = $root . '/wp-content/themes/astra/functions.php';
$plugin_path = $root . '/wp-content/mu-plugins/abit-saas-auth.php';
$password_reset_path = $root . '/wp-content/themes/astra/inc/abitai-auth-password-reset.php';
$test_plan_path = $root . '/docs/auth-test-plan.md';
$e2e_path = $root . '/docs/auth-e2e-test-scripts.md';

$template = file_get_contents($template_path);
$functions = file_get_contents($functions_path);
$plugin = file_get_contents($plugin_path);
$password_reset = file_get_contents($password_reset_path);
$test_plan = file_get_contents($test_plan_path);
$e2e = file_get_contents($e2e_path);

foreach (
    array(
        'auth template' => $template,
        'theme functions' => $functions,
        'SaaS auth API plugin' => $plugin,
        'password reset lifecycle' => $password_reset,
        'auth test plan' => $test_plan,
        'auth E2E scripts' => $e2e,
    ) as $label => $contents
) {
    if (false === $contents) {
        throw new RuntimeException("Could not read {$label}.");
    }
}

function qa03_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function qa03_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

function qa03_assert_not_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

qa03_assert_contains($test_plan, 'QA-AUTH-VERIFY-002', 'Test plan must cover expired verification links.');
qa03_assert_contains($test_plan, 'QA-AUTH-VERIFY-003', 'Test plan must cover invalid and consumed verification links.');
qa03_assert_contains($test_plan, 'QA-AUTH-VERIFY-004', 'Test plan must cover already-verified verification links.');
qa03_assert_contains($test_plan, 'QA-AUTH-RESET-004', 'Test plan must cover expired reset tokens.');
qa03_assert_contains($test_plan, 'QA-AUTH-RESET-005', 'Test plan must cover invalid and consumed reset tokens.');
qa03_assert_contains($test_plan, 'QA-AUTH-SEC-002', 'Test plan must cover single-use and expiring token security.');
qa03_assert_contains($e2e, '/auth/verify?state=expired', 'E2E scripts must exercise expired verification UI.');
qa03_assert_contains($e2e, '/auth/verify?state=failed', 'E2E scripts must exercise invalid verification UI.');
qa03_assert_contains($e2e, '/auth/reset-password?token=expired-reset-token', 'E2E scripts must exercise expired reset UI.');
qa03_assert_contains($e2e, '/auth/reset-password?token=invalid-reset-token', 'E2E scripts must exercise invalid reset UI.');

$verification_aliases = array(
    "'expired_link'     => 'expired'",
    "'invalid'          => 'failed'",
    "'used'             => 'failed'",
    "'reused'           => 'failed'",
    "'consumed'         => 'failed'",
    "'already_used'     => 'failed'",
    "'already-used'     => 'failed'",
    "'already-verified' => 'already_verified'",
);

foreach ($verification_aliases as $alias) {
    qa03_assert_contains($template, $alias, "Verification UI must normalize token edge-case alias {$alias}.");
}

qa03_assert_contains($template, "\$allowed_states = array( 'required', 'sent', 'success', 'expired', 'failed', 'cooldown', 'already_verified' );", 'Verification UI must only expose approved safe states.');
qa03_assert_contains($template, "'Token details are not shown for security.'", 'Invalid verification UI must not reveal token details.');
qa03_assert_contains($template, "'resend'            => true", 'Expired or failed verification states must expose resend recovery.');
qa03_assert_contains($template, "'already_verified' => array(", 'Verification UI must provide an already-verified safe state.');

qa03_assert_contains($plugin, "if (\$token === '')", 'Verification API must reject missing tokens.');
qa03_assert_matches($plugin, "/'verification_invalid',\\s+'Verification link cannot be used\\.',\\s+400/", 'Missing verification tokens must return safe invalid API behavior.');
qa03_assert_contains($plugin, "if (!preg_match('/^[A-Za-z0-9_-]{32,256}$/', \$token))", 'Verification API must reject malformed token shapes before lookup.');
qa03_assert_contains($plugin, "WHERE token_hash = %s LIMIT 1 FOR UPDATE", 'Verification API must lock token rows before lifecycle checks.');
qa03_assert_contains($plugin, "if (!empty(\$access_request['email_verified_at']))", 'Verification API must handle already-verified requests safely.');
qa03_assert_matches($plugin, "/'already_verified',\\s+'Email already verified\\.',\\s+200/", 'Already-verified requests must return a safe already-verified response.');
qa03_assert_contains($plugin, "if (!empty(\$token_row['consumed_at']))", 'Verification API must detect consumed tokens.');
qa03_assert_matches($plugin, "/'verification_used',\\s+'Verification link cannot be used\\.',\\s+409/", 'Consumed verification tokens must return safe used-token API behavior.');
qa03_assert_contains($plugin, "if (strtotime((string) \$token_row['expires_at']) <= time())", 'Verification API must detect expired tokens.');
qa03_assert_matches($plugin, "/'verification_expired',\\s+'Verification link expired\\.',\\s+410/", 'Expired verification tokens must return safe expired-token API behavior.');
qa03_assert_contains($plugin, "'verification_used' => 'failed'", 'Consumed verification tokens must map to the generic failed UI state.');
qa03_assert_contains($plugin, "'verification_invalid' => 'failed'", 'Invalid verification tokens must map to the generic failed UI state.');
qa03_assert_contains($plugin, "'verification_expired' => 'expired'", 'Expired verification tokens must map to expired UI state.');
qa03_assert_contains($plugin, "'already_verified' => 'already_verified'", 'Already-verified tokens must map to already-verified UI state.');

$reset_aliases = array(
    "'used'             => 'invalid'",
    "'reused'           => 'invalid'",
    "'consumed'         => 'invalid'",
    "'already_used'     => 'invalid'",
    "'already-used'     => 'invalid'",
    "'expired_link'     => 'expired'",
    "'used-reset-token'    => 'invalid'",
    "'reused-reset-token'  => 'invalid'",
    "'consumed-reset-token' => 'invalid'",
    "'already-used-reset-token' => 'invalid'",
    "'expired-reset-token' => 'expired'",
);

foreach ($reset_aliases as $alias) {
    qa03_assert_contains($template, $alias, "Password reset UI must normalize token edge-case alias {$alias}.");
}

qa03_assert_contains($template, "\$allowed_states = array( 'request', 'accepted', 'checking', 'set', 'expired', 'invalid', 'success' );", 'Password reset UI must only expose approved safe states.');
qa03_assert_contains($template, "'invalid'  => array(", 'Password reset UI must provide an invalid-link state.');
qa03_assert_contains($template, "'expired'  => array(", 'Password reset UI must provide an expired-link state.');
qa03_assert_contains($template, "if ( 'accepted' === \$state || 'success' === \$state || 'expired' === \$state || 'invalid' === \$state )", 'Password fields must not render for accepted, success, expired, or invalid reset states.');
qa03_assert_contains($template, "if ( 'set' === \$state )", 'Password fields must render only after a reset token reaches the set state.');

qa03_assert_contains($password_reset, "if ( '' === \$token || ! function_exists( 'abitai_auth_schema_table_names' ) )", 'Password reset lookup must reject missing tokens safely.');
qa03_assert_contains($password_reset, "return array( 'state' => 'invalid' );", 'Password reset lookup must return invalid state for missing or unknown tokens.');
qa03_assert_contains($password_reset, "if ( ! empty( \$row['consumed_at'] ) )", 'Password reset lookup must detect used tokens.');
qa03_assert_contains($password_reset, "if ( strtotime( (string) \$row['expires_at'] . ' UTC' ) <= time() )", 'Password reset lookup must detect expired tokens.');
qa03_assert_contains($password_reset, 'UPDATE {$tables[\'tokens\']} SET consumed_at = %s WHERE id = %d AND token_type = %s AND consumed_at IS NULL AND expires_at > %s', 'Password reset submit must atomically consume only unused, unexpired tokens.');
qa03_assert_contains($password_reset, "'expired_token'", 'Password reset submit must audit expired-token attempts.');
qa03_assert_contains($password_reset, "'invalid_token'", 'Password reset submit must audit invalid or reused token attempts.');
qa03_assert_contains($password_reset, "wp_safe_redirect( add_query_arg( 'state', 'expired', home_url( '/auth/reset-password' ) ) );", 'Expired reset submit must redirect to safe expired UI.');
qa03_assert_contains($password_reset, "wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );", 'Invalid or reused reset submit must redirect to safe invalid UI.');

qa03_assert_contains($functions, "array( 'used-reset-token', 'reused-reset-token', 'consumed-reset-token', 'already-used-reset-token', 'invalid-reset-token', 'invalid' )", 'Front-end mock reset submit must treat used, reused, consumed, already-used, and invalid reset tokens safely.');
qa03_assert_matches($template, "/'invalid'\\s+=> array\\([\\s\\S]+?Reset link cannot be used[\\s\\S]+?Send reset email/", 'Invalid reset copy must be generic and offer reset recovery.');
qa03_assert_matches($template, "/'expired'\\s+=> array\\([\\s\\S]+?Reset link expired[\\s\\S]+?Send a new reset email/", 'Expired reset copy must offer reset recovery.');
qa03_assert_matches($template, "/'failed'\\s+=> array\\([\\s\\S]+?Verification link cannot be used[\\s\\S]+?Send verification email/", 'Invalid verification copy must be generic and offer resend recovery.');
qa03_assert_matches($template, "/'expired'\\s+=> array\\([\\s\\S]+?Verification link expired[\\s\\S]+?Send a new verification email/", 'Expired verification copy must offer resend recovery.');

qa03_assert_not_contains($template, 'raw token', 'Auth UI copy must not ask users to inspect raw tokens.');
qa03_assert_contains($plugin, "\$payload['token'] = [", 'Verification API response must expose only token metadata when a token row is present.');
qa03_assert_contains($plugin, "'id' => (int) \$token_row['id']", 'Verification API token metadata must use token row ID.');
qa03_assert_contains($plugin, "'expires_at' => self::nullable_datetime(\$token_row['expires_at'] ?? null)", 'Verification API token metadata must expose normalized expiry metadata.');
qa03_assert_contains($plugin, "'consumed_at' => self::nullable_datetime(\$token_row['consumed_at'] ?? null)", 'Verification API token metadata must expose normalized consumed metadata.');

echo "Auth token edge-case checks passed.\n";
