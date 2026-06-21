<?php
/**
 * LCH-05 post-launch monitoring dashboard coverage checks.
 *
 * Run from the repository root:
 * php tests/launch-monitoring-dashboard-coverage.php
 */

$root = dirname(__DIR__);
$plugin_path = $root . '/wp-content/mu-plugins/abit-saas-auth.php';

$plugin = file_get_contents($plugin_path);
if ($plugin === false) {
    throw new RuntimeException('Could not read ABiT SaaS auth plugin.');
}

function lch05_assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function lch05_assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

lch05_assert_contains($plugin, 'ABiT Auth Monitoring', 'Monitoring dashboard must be registered in WordPress admin.');
lch05_assert_contains($plugin, 'abit-auth-monitoring', 'Monitoring dashboard must have a stable admin slug.');
lch05_assert_contains($plugin, 'render_auth_monitoring_page', 'Monitoring dashboard must render a dedicated admin page.');
lch05_assert_contains($plugin, 'auth_monitoring_metrics', 'Monitoring dashboard must calculate live launch metrics.');
lch05_assert_contains($plugin, 'auth_monitoring_alerts', 'Monitoring dashboard must alert on abnormal failures.');
lch05_assert_contains($plugin, 'auth_monitoring_recent_events', 'Monitoring dashboard must show recent production events.');

$required_metrics = [
    'Signup starts',
    'Account created',
    'Verification sent',
    'Verification completed',
    'Login errors',
    'Reset requests',
    'Email failures',
    'Rate-limit events',
];

foreach ($required_metrics as $metric) {
    lch05_assert_contains($plugin, $metric, "Monitoring dashboard missing metric: {$metric}");
}

$required_tables = [
    "self::table('access_requests')",
    "self::table('email_delivery_events')",
    "self::table('auth_rate_limit_events')",
];

foreach ($required_tables as $table) {
    lch05_assert_contains($plugin, $table, "Monitoring dashboard must read live production table: {$table}");
}

$alert_contract = [
    'MONITORING_FAILURE_RATE_ALERT_THRESHOLD',
    'MONITORING_EMAIL_FAILURE_ALERT_THRESHOLD',
    'MONITORING_LOGIN_ERROR_ALERT_THRESHOLD',
    'MONITORING_RATE_LIMIT_ALERT_THRESHOLD',
    'Abnormal Failure Alerts',
    'No abnormal auth failure patterns detected',
];

foreach ($alert_contract as $needle) {
    lch05_assert_contains($plugin, $needle, "Monitoring dashboard missing alert contract: {$needle}");
}

lch05_assert_matches(
    $plugin,
    "/delivery_status IN \\('failed', 'bounced'\\)/",
    'Monitoring dashboard must count failed and bounced email events.'
);

lch05_assert_matches(
    $plugin,
    "/result LIKE 'throttled%'[\\s\\S]+DATE_SUB\\(UTC_TIMESTAMP\\(\\), INTERVAL 1 HOUR\\)/",
    'Monitoring dashboard must alert from current rate-limit events.'
);

echo "Launch monitoring dashboard coverage checks passed.\n";
