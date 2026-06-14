<?php
/**
 * Plugin Name: ABiT SaaS Auth API
 * Description: Public SaaS authentication endpoints and storage for abit.ai access requests.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ABiT_SaaS_Auth_API
{
    private const SCHEMA_VERSION = '2026-06-14.1';
    private const REST_NAMESPACE = 'abit-ai/v1';
    private const REVIEW_STATUS_PENDING_EMAIL = 'pending_email_verification';
    private const CONSENT_RETENTION_RULE = 'active_plus_7_years_after_closure';

    public static function bootstrap(): void
    {
        add_action('init', [__CLASS__, 'maybe_install_schema']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('template_redirect', [__CLASS__, 'handle_pretty_api_route'], 0);
    }

    public static function register_routes(): void
    {
        register_rest_route(
            self::REST_NAMESPACE,
            '/auth/register',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'register'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public static function handle_pretty_api_route(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if ('/api/auth/register' !== untrailingslashit($path)) {
            return;
        }

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            wp_send_json(
                ['message' => 'Method not allowed.'],
                405
            );
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $request = new WP_REST_Request('POST', '/' . self::REST_NAMESPACE . '/auth/register');
        $request->set_body_params(is_array($payload) ? $payload : []);
        $request->set_header('content-type', $_SERVER['CONTENT_TYPE'] ?? 'application/json');

        $response = rest_ensure_response(self::register($request));
        wp_send_json($response->get_data(), $response->get_status());
    }

    public static function maybe_install_schema(): void
    {
        $installed_version = get_option('abit_saas_auth_schema_version');
        if ($installed_version === self::SCHEMA_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $access_requests = self::table('access_requests');
        $companies = self::table('companies');
        $consents = self::table('consent_audit_records');
        $tokens = self::table('email_verification_tokens');

        dbDelta("
            CREATE TABLE {$companies} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_name VARCHAR(160) NOT NULL,
                country_region VARCHAR(16) NOT NULL,
                draft_status VARCHAR(32) NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY draft_status (draft_status)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$access_requests} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                company_id BIGINT UNSIGNED NOT NULL,
                full_name VARCHAR(120) NOT NULL,
                business_email VARCHAR(254) NOT NULL,
                company_name VARCHAR(160) NOT NULL,
                country_region VARCHAR(16) NOT NULL,
                intended_use_case TEXT NOT NULL,
                review_status VARCHAR(64) NOT NULL,
                email_verified_at DATETIME NULL,
                terms_privacy_accepted_at DATETIME NULL,
                terms_version VARCHAR(64) NULL,
                privacy_version VARCHAR(64) NULL,
                latest_consent_audit_record_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY business_email (business_email),
                KEY user_id (user_id),
                KEY company_id (company_id),
                KEY review_status (review_status)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$consents} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                access_request_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                business_email_hash CHAR(64) NOT NULL,
                terms_version VARCHAR(64) NOT NULL,
                privacy_version VARCHAR(64) NOT NULL,
                consent_text_version VARCHAR(64) NOT NULL,
                legal_locale VARCHAR(16) NOT NULL,
                accepted_at DATETIME NOT NULL,
                ip_hash CHAR(64) NOT NULL,
                user_agent_hash CHAR(64) NOT NULL,
                hash_key_version VARCHAR(64) NOT NULL,
                capture_source VARCHAR(64) NOT NULL,
                retention_rule VARCHAR(96) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY access_request_id (access_request_id),
                KEY user_id (user_id),
                KEY business_email_hash (business_email_hash)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$tokens} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                access_request_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                consumed_at DATETIME NULL,
                sent_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY token_hash (token_hash),
                KEY access_request_id (access_request_id),
                KEY user_id (user_id),
                KEY expires_at (expires_at)
            ) {$charset_collate};
        ");

        update_option('abit_saas_auth_schema_version', self::SCHEMA_VERSION, false);
    }

    public static function register(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $payload = self::request_payload($request);
        $validated = self::validate_registration_payload($payload);

        if (!empty($validated['field_errors'])) {
            return self::field_error_response($validated['field_errors']);
        }

        $data = $validated['data'];
        $duplicate_errors = self::duplicate_email_errors($data['business_email']);
        if (!empty($duplicate_errors)) {
            return self::field_error_response($duplicate_errors, 409);
        }

        global $wpdb;
        $now = current_time('mysql', true);
        $token = self::new_token();
        $token_hash = self::hmac($token);
        $expires_at = gmdate('Y-m-d H:i:s', time() + (int) apply_filters('abit_saas_auth_email_verification_ttl', DAY_IN_SECONDS));

        $wpdb->query('START TRANSACTION');

        try {
            $user_id = self::create_user($data);
            if (is_wp_error($user_id)) {
                $wpdb->query('ROLLBACK');
                return self::field_error_response(self::user_error_to_field_errors($user_id), 409);
            }

            $company_id = self::insert_company($data, $now);
            $access_request_id = self::insert_access_request($data, $user_id, $company_id, $now);
            $consent_id = self::insert_consent($data, $user_id, $access_request_id, $now);
            self::link_latest_consent($access_request_id, $consent_id);
            $token_id = self::insert_verification_token($user_id, $access_request_id, $token_hash, $expires_at, $now);

            $mail_sent = self::send_verification_email($data['business_email'], $data['full_name'], $token);
            if (!$mail_sent) {
                throw new RuntimeException('Verification email could not be sent.');
            }

            self::mark_token_sent($token_id);
            $wpdb->query('COMMIT');

            return new WP_REST_Response(
                [
                    'user_id' => $user_id,
                    'company_id' => $company_id,
                    'access_request_id' => $access_request_id,
                    'consent_audit_record_id' => $consent_id,
                    'email_verification_token_id' => $token_id,
                    'status' => self::REVIEW_STATUS_PENDING_EMAIL,
                    'verification_email_sent' => true,
                ],
                201
            );
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');

            return new WP_REST_Response(
                [
                    'message' => 'Registration could not be completed.',
                    'code' => 'registration_failed',
                ],
                500
            );
        }
    }

    private static function request_payload(WP_REST_Request $request): array
    {
        $params = $request->get_json_params();
        if (is_array($params)) {
            return $params;
        }

        $params = $request->get_body_params();
        return is_array($params) ? $params : [];
    }

    private static function validate_registration_payload(array $payload): array
    {
        $errors = [];
        $data = [];

        $data['full_name'] = self::clean_text($payload['full_name'] ?? '');
        if (!preg_match("/^[A-Za-z][A-Za-z .'\\-]{1,119}$/", $data['full_name']) || self::has_unsafe_text($data['full_name'])) {
            $errors['full_name'] = 'Enter a valid full name using 2 to 120 letters and name punctuation characters.';
        }

        $data['business_email'] = strtolower(trim((string) ($payload['business_email'] ?? $payload['email'] ?? '')));
        if (strlen($data['business_email']) > 254 || !is_email($data['business_email'])) {
            $errors['business_email'] = 'Enter a valid business email address.';
        }

        $data['company_name'] = self::clean_text($payload['company_name'] ?? '');
        if (strlen($data['company_name']) < 2 || strlen($data['company_name']) > 160 || self::has_unsafe_text($data['company_name']) || self::is_links_only($data['company_name'])) {
            $errors['company_name'] = 'Enter a valid company name using 2 to 160 characters.';
        }

        $data['country_region'] = strtoupper(trim((string) ($payload['country_region'] ?? '')));
        if (!preg_match('/^([A-Z]{2}|EU|GCC|MENA)$/', $data['country_region'])) {
            $errors['country_region'] = 'Select a valid country or approved region.';
        }

        $data['intended_use_case'] = self::clean_textarea($payload['intended_use_case'] ?? '');
        if (strlen($data['intended_use_case']) < 20 || strlen($data['intended_use_case']) > 1000 || self::has_unsafe_text($data['intended_use_case']) || self::is_links_only($data['intended_use_case'])) {
            $errors['intended_use_case'] = 'Describe the intended use case in 20 to 1000 characters.';
        }

        $data['password'] = (string) ($payload['password'] ?? '');
        $password_error = self::password_error($data['password']);
        if ($password_error) {
            $errors['password'] = $password_error;
        }

        $accepted = $payload['terms_privacy_acceptance'] ?? $payload['terms_accepted'] ?? null;
        $data['terms_privacy_acceptance'] = filter_var($accepted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (true !== $data['terms_privacy_acceptance']) {
            $errors['terms_privacy_acceptance'] = 'You must accept the current terms and privacy notices.';
        }

        return [
            'data' => $data,
            'field_errors' => $errors,
        ];
    }

    private static function password_error(string $password): ?string
    {
        if (strlen($password) < 12 || strlen($password) > 128) {
            return 'Password must be 12 to 128 characters.';
        }

        $classes = 0;
        $classes += preg_match('/[a-z]/', $password) ? 1 : 0;
        $classes += preg_match('/[A-Z]/', $password) ? 1 : 0;
        $classes += preg_match('/[0-9]/', $password) ? 1 : 0;
        $classes += preg_match('/[^A-Za-z0-9]/', $password) ? 1 : 0;
        if ($classes < 3) {
            return 'Password must include at least three character types.';
        }

        $common = [
            'password1234',
            'password123!',
            'qwerty123456',
            'letmein12345',
            'admin123456',
            'welcome12345',
        ];
        if (in_array(strtolower($password), $common, true)) {
            return 'Choose a less common password.';
        }

        return null;
    }

    private static function duplicate_email_errors(string $email): array
    {
        global $wpdb;

        if (get_user_by('email', $email)) {
            return ['business_email' => 'An access request already exists for this email address.'];
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT id FROM ' . self::table('access_requests') . ' WHERE business_email = %s LIMIT 1',
                $email
            )
        );

        return $exists ? ['business_email' => 'An access request already exists for this email address.'] : [];
    }

    private static function create_user(array $data)
    {
        $email_parts = explode('@', $data['business_email']);
        $username_base = sanitize_user($email_parts[0] ?? '', true);
        $username = $username_base ?: 'abit-user';
        $candidate = $username;
        $suffix = 1;

        while (username_exists($candidate)) {
            $suffix++;
            $candidate = $username . '-' . $suffix;
        }

        return wp_insert_user(
            [
                'user_login' => $candidate,
                'user_pass' => $data['password'],
                'user_email' => $data['business_email'],
                'display_name' => $data['full_name'],
                'first_name' => $data['full_name'],
                'role' => 'subscriber',
                'meta_input' => [
                    'abit_saas_review_status' => self::REVIEW_STATUS_PENDING_EMAIL,
                    'abit_saas_company_name' => $data['company_name'],
                ],
            ]
        );
    }

    private static function user_error_to_field_errors(WP_Error $error): array
    {
        $code = $error->get_error_code();
        if (in_array($code, ['existing_user_email', 'email_exists'], true)) {
            return ['business_email' => 'An access request already exists for this email address.'];
        }

        if (in_array($code, ['existing_user_login', 'invalid_username'], true)) {
            return ['business_email' => 'This email address cannot be used for registration.'];
        }

        return ['business_email' => 'Registration could not create an account for this email address.'];
    }

    private static function insert_company(array $data, string $now): int
    {
        global $wpdb;
        $inserted = $wpdb->insert(
            self::table('companies'),
            [
                'company_name' => $data['company_name'],
                'country_region' => $data['country_region'],
                'draft_status' => 'draft',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Company draft could not be created.');
        }

        return (int) $wpdb->insert_id;
    }

    private static function insert_access_request(array $data, int $user_id, int $company_id, string $now): int
    {
        global $wpdb;
        $legal = self::current_legal_versions();
        $inserted = $wpdb->insert(
            self::table('access_requests'),
            [
                'user_id' => $user_id,
                'company_id' => $company_id,
                'full_name' => $data['full_name'],
                'business_email' => $data['business_email'],
                'company_name' => $data['company_name'],
                'country_region' => $data['country_region'],
                'intended_use_case' => $data['intended_use_case'],
                'review_status' => self::REVIEW_STATUS_PENDING_EMAIL,
                'terms_privacy_accepted_at' => $now,
                'terms_version' => $legal['terms_version'],
                'privacy_version' => $legal['privacy_version'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Access request could not be created.');
        }

        return (int) $wpdb->insert_id;
    }

    private static function insert_consent(array $data, int $user_id, int $access_request_id, string $now): int
    {
        global $wpdb;
        $legal = self::current_legal_versions();
        $inserted = $wpdb->insert(
            self::table('consent_audit_records'),
            [
                'access_request_id' => $access_request_id,
                'user_id' => $user_id,
                'business_email_hash' => self::hmac($data['business_email']),
                'terms_version' => $legal['terms_version'],
                'privacy_version' => $legal['privacy_version'],
                'consent_text_version' => $legal['consent_text_version'],
                'legal_locale' => $legal['legal_locale'],
                'accepted_at' => $now,
                'ip_hash' => self::hmac(self::request_ip()),
                'user_agent_hash' => self::hmac($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'hash_key_version' => self::hash_key_version(),
                'capture_source' => 'signup_registration',
                'retention_rule' => self::CONSENT_RETENTION_RULE,
                'created_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Consent audit record could not be created.');
        }

        return (int) $wpdb->insert_id;
    }

    private static function link_latest_consent(int $access_request_id, int $consent_id): void
    {
        global $wpdb;
        $wpdb->update(
            self::table('access_requests'),
            ['latest_consent_audit_record_id' => $consent_id],
            ['id' => $access_request_id],
            ['%d'],
            ['%d']
        );
    }

    private static function insert_verification_token(int $user_id, int $access_request_id, string $token_hash, string $expires_at, string $now): int
    {
        global $wpdb;
        $inserted = $wpdb->insert(
            self::table('email_verification_tokens'),
            [
                'access_request_id' => $access_request_id,
                'user_id' => $user_id,
                'token_hash' => $token_hash,
                'expires_at' => $expires_at,
                'created_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Verification token could not be created.');
        }

        return (int) $wpdb->insert_id;
    }

    private static function mark_token_sent(int $token_id): void
    {
        global $wpdb;
        $wpdb->update(
            self::table('email_verification_tokens'),
            ['sent_at' => current_time('mysql', true)],
            ['id' => $token_id],
            ['%s'],
            ['%d']
        );
    }

    private static function send_verification_email(string $email, string $name, string $token): bool
    {
        $verification_url = add_query_arg(
            [
                'token' => rawurlencode($token),
                'email' => rawurlencode($email),
            ],
            home_url('/verify-email/')
        );

        $subject = apply_filters('abit_saas_auth_verification_subject', 'Verify your abit.ai access request');
        $message = sprintf(
            "Hi %s,\n\nVerify your email address to continue your abit.ai SaaS access request:\n\n%s\n\nThis link expires in 24 hours.",
            $name,
            $verification_url
        );

        return (bool) wp_mail($email, $subject, $message);
    }

    private static function field_error_response(array $field_errors, int $status = 422): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'message' => 'Please correct the highlighted fields.',
                'code' => 'validation_failed',
                'field_errors' => $field_errors,
            ],
            $status
        );
    }

    private static function current_legal_versions(): array
    {
        return apply_filters(
            'abit_saas_auth_current_legal_versions',
            [
                'terms_version' => defined('ABIT_SAAS_TERMS_VERSION') ? ABIT_SAAS_TERMS_VERSION : 'terms-2026-06-14',
                'privacy_version' => defined('ABIT_SAAS_PRIVACY_VERSION') ? ABIT_SAAS_PRIVACY_VERSION : 'privacy-2026-06-14',
                'consent_text_version' => defined('ABIT_SAAS_CONSENT_TEXT_VERSION') ? ABIT_SAAS_CONSENT_TEXT_VERSION : 'signup-consent-2026-06-14',
                'legal_locale' => defined('ABIT_SAAS_LEGAL_LOCALE') ? ABIT_SAAS_LEGAL_LOCALE : 'en',
            ]
        );
    }

    private static function clean_text($value): string
    {
        return trim(sanitize_text_field((string) $value));
    }

    private static function clean_textarea($value): string
    {
        return trim(sanitize_textarea_field((string) $value));
    }

    private static function has_unsafe_text(string $value): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/', $value) === 1;
    }

    private static function is_links_only(string $value): bool
    {
        $stripped = trim(preg_replace('/https?:\/\/\S+|www\.\S+/i', '', $value));
        return $stripped === '';
    }

    private static function new_token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private static function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, self::hash_key());
    }

    private static function hash_key(): string
    {
        if (defined('ABIT_SAAS_AUTH_HASH_KEY') && ABIT_SAAS_AUTH_HASH_KEY) {
            return ABIT_SAAS_AUTH_HASH_KEY;
        }

        return wp_salt('auth');
    }

    private static function hash_key_version(): string
    {
        return defined('ABIT_SAAS_AUTH_HASH_KEY_VERSION') ? ABIT_SAAS_AUTH_HASH_KEY_VERSION : 'wp-auth-salt-v1';
    }

    private static function request_ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }
            $value = trim(explode(',', (string) $_SERVER[$header])[0]);
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '';
    }

    private static function table(string $name): string
    {
        global $wpdb;
        return $wpdb->prefix . 'abit_saas_' . $name;
    }
}

ABiT_SaaS_Auth_API::bootstrap();
