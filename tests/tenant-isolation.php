<?php
/**
 * Tenant isolation checks for user/company scoped auth APIs.
 *
 * Run from the repository root:
 * php tests/tenant-isolation.php
 */

define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$GLOBALS['abitai_test_current_user_id'] = 101;
$GLOBALS['abitai_test_user_meta'] = [
    101 => [
        'abitai_company_id' => 501,
    ],
];
$GLOBALS['abitai_test_audit_events'] = [];

class WP_Error
{
    private string $code;
    private string $message;
    private array $data;

    public function __construct(string $code, string $message = '', array $data = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }

    public function get_error_data(): array
    {
        return $this->data;
    }
}

class WP_REST_Request
{
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function get_params(): array
    {
        return $this->params;
    }

    public function get_json_params(): array
    {
        return $this->params;
    }

    public function get_body_params(): array
    {
        return $this->params;
    }
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function add_rewrite_rule($regex, $query, $after = 'bottom'): void {}
function register_rest_route($namespace, $route, $args = []): void {}
function is_user_logged_in(): bool { return get_current_user_id() > 0; }
function get_current_user_id(): int { return (int) $GLOBALS['abitai_test_current_user_id']; }
function get_user_meta($user_id, $key, $single = false)
{
    return $GLOBALS['abitai_test_user_meta'][$user_id][$key] ?? '';
}
function absint($value): int { return max(0, (int) $value); }
function sanitize_key($key): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string) $key)); }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_textarea_field($value): string { return sanitize_text_field($value); }
function wp_unslash($value) { return $value; }
function __($text, $domain = 'default'): string { return (string) $text; }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function rest_ensure_response($value) { return $value; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function abitai_text_has_unsafe_content($value): bool { return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/', (string) $value) === 1; }
function abitai_auth_write_audit_log($event_type, $context = []): int
{
    $GLOBALS['abitai_test_audit_events'][] = [
        'event_type' => $event_type,
        'context' => $context,
    ];

    return count($GLOBALS['abitai_test_audit_events']);
}

function assert_true($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
    }
}

require dirname(__DIR__) . '/wp-content/themes/astra/inc/abitai-company-profile-api.php';

$foreign_company_response = abitai_company_profile_update(
    new WP_REST_Request(
        [
            'company_id' => 999,
            'company_name' => 'Foreign Company LLC',
            'primary_workflow' => 'This payload must not be persisted or written into denial audit metadata.',
        ]
    )
);

assert_true($foreign_company_response instanceof WP_Error, 'Foreign company update should return WP_Error.');
assert_same('abitai_company_profile_forbidden_company', $foreign_company_response->get_error_code(), 'Foreign company update should use the forbidden company code.');
assert_same(403, $foreign_company_response->get_error_data()['status'] ?? 0, 'Foreign company update should return HTTP 403.');
assert_same(1, count($GLOBALS['abitai_test_audit_events']), 'Foreign company denial should write one audit event.');
assert_same('auth_tenant_scope_denied', $GLOBALS['abitai_test_audit_events'][0]['event_type'], 'Foreign company denial should use tenant denial audit type.');

$foreign_company_context = $GLOBALS['abitai_test_audit_events'][0]['context'];
assert_same(101, $foreign_company_context['actor_user_id'] ?? 0, 'Audit should identify the current actor.');
assert_same(501, $foreign_company_context['company_id'] ?? 0, 'Audit should keep the actor owned company scope.');
assert_same('company_profile_update', $foreign_company_context['event_data']['api_surface'] ?? '', 'Audit should identify the API surface.');
assert_same('requested_company_mismatch', $foreign_company_context['event_data']['denial_reason'] ?? '', 'Audit should record a safe denial reason.');
assert_same(999, $foreign_company_context['event_data']['requested_company_id'] ?? 0, 'Audit should record the requested company ID.');
assert_true(!array_key_exists('company_name', $foreign_company_context['event_data']), 'Audit event data should not include submitted company profile fields.');
assert_true(!array_key_exists('primary_workflow', $foreign_company_context['event_data']), 'Audit event data should not include submitted workflow text.');

$GLOBALS['abitai_test_audit_events'] = [];
$foreign_user_response = abitai_company_profile_update(new WP_REST_Request(['user_id' => 202]));

assert_true($foreign_user_response instanceof WP_Error, 'Foreign user update should return WP_Error.');
assert_same('abitai_company_profile_forbidden_user', $foreign_user_response->get_error_code(), 'Foreign user update should use the forbidden user code.');
assert_same(403, $foreign_user_response->get_error_data()['status'] ?? 0, 'Foreign user update should return HTTP 403.');
assert_same('auth_tenant_scope_denied', $GLOBALS['abitai_test_audit_events'][0]['event_type'] ?? '', 'Foreign user denial should write a tenant denial audit event.');
assert_same('requested_user_mismatch', $GLOBALS['abitai_test_audit_events'][0]['context']['event_data']['denial_reason'] ?? '', 'Foreign user denial should record a safe reason.');

$mu_plugin = file_get_contents(dirname(__DIR__) . '/wp-content/mu-plugins/abit-saas-auth.php');
assert_true(strpos($mu_plugin, 'tenant_scope_denial_response($request, $user, $access_request, \'auth_me\')') !== false, '/auth/me should reject mismatched requested tenant scope.');
assert_true(strpos($mu_plugin, 'tenant_scope_denial_response($request, $user, $access_request, \'provisioning_request\')') !== false, '/provisioning/request should reject mismatched requested tenant scope.');
assert_true(strpos($mu_plugin, "'code' => 'tenant_scope_denied'") !== false, 'Tenant scope denials should return a stable error code.');
assert_true(strpos($mu_plugin, "'auth_tenant_scope_denied'") !== false, 'Tenant scope denials should create audit entries.');
assert_true(strpos($mu_plugin, 'requested_access_request_id') !== false, 'Tenant scope denial audit should include requested access request ID only, not raw payload.');
assert_true(strpos($mu_plugin, 'requested_company_id') !== false, 'Tenant scope denial audit should include requested company ID only, not raw payload.');

echo "Tenant isolation tests passed.\n";
