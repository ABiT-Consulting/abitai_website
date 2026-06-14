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
    private const SCHEMA_VERSION = '2026-06-14.2';
    private const REST_NAMESPACE = 'abit-ai/v1';
    private const REVIEW_STATUS_PENDING_EMAIL = 'pending_email_verification';
    private const REVIEW_STATUS_ONBOARDING_REQUIRED = 'onboarding_required';
    private const REVIEW_STATUS_PENDING_ADMIN_REVIEW = 'pending_admin_review';
    private const REVIEW_STATUS_APPROVED = 'approved_for_mvp_access';
    private const REVIEW_STATUS_REJECTED = 'rejected';
    private const REVIEW_STATUS_MORE_INFORMATION_REQUESTED = 'more_information_requested';
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

        register_rest_route(
            self::REST_NAMESPACE,
            '/auth/login',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'login'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/auth/logout',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'logout'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/auth/me',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'me'],
                'permission_callback' => [__CLASS__, 'require_authentication'],
            ]
        );
    }

    public static function handle_pretty_api_route(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = untrailingslashit($path);
        $routes = [
            '/api/auth/register' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/register',
                'callback' => [__CLASS__, 'register'],
                'method' => 'POST',
            ],
            '/api/auth/login' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/login',
                'callback' => [__CLASS__, 'login'],
                'method' => 'POST',
            ],
            '/api/auth/logout' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/logout',
                'callback' => [__CLASS__, 'logout'],
                'method' => 'POST',
            ],
            '/api/auth/me' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/me',
                'callback' => [__CLASS__, 'me'],
                'method' => 'GET',
            ],
        ];

        if (!isset($routes[$path])) {
            return;
        }

        $expected_method = $routes[$path]['method'];
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== $expected_method) {
            header('Allow: ' . $expected_method);
            wp_send_json(
                ['message' => 'Method not allowed.'],
                405
            );
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $request = new WP_REST_Request($expected_method, $routes[$path]['rest_path']);
        if ($expected_method === 'GET') {
            $request->set_query_params(wp_unslash($_GET));
        } else {
            $request->set_body_params(is_array($payload) ? $payload : []);
        }
        $request->set_header('content-type', $_SERVER['CONTENT_TYPE'] ?? 'application/json');

        $response = rest_ensure_response(call_user_func($routes[$path]['callback'], $request));
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
                role VARCHAR(120) NULL,
                company_size VARCHAR(32) NULL,
                industry VARCHAR(64) NULL,
                primary_workflow TEXT NULL,
                erp_module_interest LONGTEXT NULL,
                current_system VARCHAR(160) NULL,
                timeline VARCHAR(32) NULL,
                notes TEXT NULL,
                persona VARCHAR(64) NULL,
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
                KEY review_status (review_status),
                KEY company_size (company_size),
                KEY industry (industry)
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

    public static function login(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $payload = self::request_payload($request);
        $validated = self::validate_login_payload($payload);

        if (!empty($validated['field_errors'])) {
            return self::field_error_response($validated['field_errors']);
        }

        $email = $validated['data']['email'];
        $password = $validated['data']['password'];
        $remember = $validated['data']['remember'];
        $user = get_user_by('email', $email);

        if (!$user instanceof WP_User || !wp_check_password($password, $user->user_pass, $user->ID)) {
            return self::login_failed_response();
        }

        $account_state = self::account_state_for_user($user);
        if (!empty($account_state['locked'])) {
            wp_clear_auth_cookie();
            return new WP_REST_Response(
                [
                    'message' => 'This account cannot sign in right now. Contact support for help.',
                    'code' => 'account_locked',
                    'state' => 'account_locked',
                ],
                423
            );
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        return new WP_REST_Response(
            [
                'message' => 'Signed in. Redirecting...',
                'authenticated' => true,
                'user_id' => $user->ID,
                'access_request_id' => $account_state['access_request_id'],
                'company_id' => $account_state['company_id'],
                'status' => $account_state['review_status'],
                'state' => $account_state['state'],
                'route' => $account_state['route'],
                'email_verified' => $account_state['email_verified'],
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            200
        );
    }

    public static function logout(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = self::current_authenticated_user_id();
        $session_token = wp_get_session_token();

        if ($user_id <= 0 || $session_token === '') {
            wp_clear_auth_cookie();
            wp_set_current_user(0);

            return new WP_REST_Response(
                [
                    'message' => 'No active session to sign out.',
                    'authenticated' => false,
                    'revoked' => false,
                ],
                401
            );
        }

        WP_Session_Tokens::get_instance($user_id)->destroy($session_token);
        wp_clear_auth_cookie();
        wp_set_current_user(0);

        return new WP_REST_Response(
            [
                'message' => 'Signed out.',
                'authenticated' => false,
                'revoked' => true,
            ],
            200
        );
    }

    public static function me(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $user_id = self::current_authenticated_user_id();
        if ($user_id <= 0) {
            return self::not_authenticated_response();
        }

        $user = get_userdata($user_id);
        if (!$user instanceof WP_User) {
            wp_clear_auth_cookie();
            wp_set_current_user(0);
            return self::not_authenticated_response();
        }

        $access_request = self::access_request_for_user($user);
        $account_state = self::account_state_from_access_request($user, $access_request);
        $onboarding = self::onboarding_payload($user, $access_request, $account_state);

        return new WP_REST_Response(
            [
                'authenticated' => true,
                'user' => self::public_user_payload($user, $access_request),
                'verification' => [
                    'email_verified' => $account_state['email_verified'],
                    'status' => $account_state['email_verified'] ? 'verified' : 'pending',
                    'email_verified_at' => self::nullable_datetime($access_request['email_verified_at'] ?? null),
                ],
                'company' => self::company_payload($user, $access_request),
                'role' => $onboarding['role'],
                'onboarding' => $onboarding,
                'access_request' => [
                    'id' => is_array($access_request) ? (int) $access_request['id'] : null,
                    'status' => $account_state['review_status'],
                    'created_at' => self::nullable_datetime($access_request['created_at'] ?? null),
                    'updated_at' => self::nullable_datetime($access_request['updated_at'] ?? null),
                ],
                'gate' => [
                    'state' => $account_state['state'],
                    'route' => $account_state['route'],
                    'next_path' => self::path_for_review_status($account_state['review_status'], $account_state['locked']),
                    'product_access' => !$account_state['locked'] && $account_state['review_status'] === self::REVIEW_STATUS_APPROVED,
                    'locked' => $account_state['locked'],
                ],
            ],
            200
        );
    }

    public static function require_authentication()
    {
        if (self::current_authenticated_user_id() > 0) {
            return true;
        }

        return new WP_Error(
            'rest_not_logged_in',
            'Authentication is required.',
            ['status' => 401]
        );
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

    private static function validate_login_payload(array $payload): array
    {
        $errors = [];
        $data = [];

        $data['email'] = strtolower(trim((string) ($payload['email'] ?? $payload['business_email'] ?? '')));
        if (strlen($data['email']) > 254 || !is_email($data['email'])) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $data['password'] = (string) ($payload['password'] ?? '');
        if ($data['password'] === '') {
            $errors['password'] = 'Enter your password.';
        }

        $remember = $payload['remember'] ?? $payload['remember_me'] ?? false;
        $data['remember'] = true === filter_var($remember, FILTER_VALIDATE_BOOLEAN);

        return [
            'data' => $data,
            'field_errors' => $errors,
        ];
    }

    private static function login_failed_response(): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'message' => 'We could not sign you in with those details. Check your email and password, then try again.',
                'code' => 'invalid_login',
            ],
            401
        );
    }

    private static function not_authenticated_response(): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'message' => 'Authentication is required.',
                'code' => 'not_authenticated',
                'authenticated' => false,
            ],
            401
        );
    }

    private static function current_authenticated_user_id(): int
    {
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            return (int) $user_id;
        }

        $cookie_user_id = wp_validate_auth_cookie('', 'logged_in');
        if ($cookie_user_id) {
            return (int) $cookie_user_id;
        }

        $cookie_user_id = wp_validate_auth_cookie('', 'auth');
        return $cookie_user_id ? (int) $cookie_user_id : 0;
    }

    private static function account_state_for_user(WP_User $user): array
    {
        return self::account_state_from_access_request($user, self::access_request_for_user($user));
    }

    private static function account_state_from_access_request(WP_User $user, ?array $access_request): array
    {
        $review_status = is_array($access_request) && !empty($access_request['review_status'])
            ? (string) $access_request['review_status']
            : self::stored_review_status_for_user($user);

        if ($review_status === '') {
            $review_status = self::REVIEW_STATUS_PENDING_EMAIL;
        }

        $locked = self::is_user_locked($user);
        $state = $locked
            ? ['state' => 'account_locked', 'route' => 'locked']
            : self::state_for_review_status($review_status);

        return [
            'access_request_id' => is_array($access_request) ? (int) $access_request['id'] : null,
            'company_id' => is_array($access_request) ? (int) $access_request['company_id'] : null,
            'review_status' => $review_status,
            'email_verified' => self::email_verified_for_user($user, $access_request),
            'state' => $state['state'],
            'route' => $state['route'],
            'locked' => $locked,
        ];
    }

    private static function access_request_for_user(WP_User $user): ?array
    {
        global $wpdb;

        $access_request = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT ar.id, ar.user_id, ar.company_id, ar.full_name, ar.business_email, ar.company_name, ar.country_region, ar.intended_use_case, ar.role, ar.company_size, ar.industry, ar.primary_workflow, ar.erp_module_interest, ar.current_system, ar.timeline, ar.notes, ar.persona, ar.review_status, ar.email_verified_at, ar.terms_privacy_accepted_at, ar.terms_version, ar.privacy_version, ar.created_at, ar.updated_at, c.company_name AS company_record_name, c.country_region AS company_record_country_region, c.draft_status AS company_record_status FROM ' . self::table('access_requests') . ' ar LEFT JOIN ' . self::table('companies') . ' c ON c.id = ar.company_id WHERE ar.user_id = %d OR ar.business_email = %s ORDER BY ar.id DESC LIMIT 1',
                $user->ID,
                $user->user_email
            ),
            ARRAY_A
        );

        return is_array($access_request) ? $access_request : null;
    }

    private static function stored_review_status_for_user(WP_User $user): string
    {
        $status = (string) get_user_meta($user->ID, 'abit_saas_review_status', true);
        if ($status !== '') {
            return $status;
        }

        return (string) get_user_meta($user->ID, 'abitai_access_request_status', true);
    }

    private static function email_verified_for_user(WP_User $user, ?array $access_request): bool
    {
        if (is_array($access_request)) {
            return !empty($access_request['email_verified_at']);
        }

        return get_user_meta($user->ID, 'abitai_email_verified_at', true) !== '';
    }

    private static function public_user_payload(WP_User $user, ?array $access_request): array
    {
        return [
            'id' => (int) $user->ID,
            'email' => (string) $user->user_email,
            'display_name' => (string) $user->display_name,
            'full_name' => self::first_non_empty([
                $access_request['full_name'] ?? null,
                trim((string) $user->first_name . ' ' . (string) $user->last_name),
                $user->display_name,
            ]),
            'wp_roles' => array_values(array_map('strval', (array) $user->roles)),
        ];
    }

    private static function company_payload(WP_User $user, ?array $access_request): array
    {
        return [
            'id' => is_array($access_request) && !empty($access_request['company_id']) ? (int) $access_request['company_id'] : null,
            'name' => self::first_non_empty([
                $access_request['company_record_name'] ?? null,
                $access_request['company_name'] ?? null,
                get_user_meta($user->ID, 'abit_saas_company_name', true),
                get_user_meta($user->ID, 'abitai_company_name', true),
            ]),
            'country_region' => self::first_non_empty([
                $access_request['company_record_country_region'] ?? null,
                $access_request['country_region'] ?? null,
                get_user_meta($user->ID, 'abitai_country_region', true),
            ]),
            'company_size' => self::first_non_empty([
                $access_request['company_size'] ?? null,
                get_user_meta($user->ID, 'abitai_company_size', true),
            ]),
            'industry' => self::first_non_empty([
                $access_request['industry'] ?? null,
                get_user_meta($user->ID, 'abitai_industry', true),
            ]),
            'status' => self::first_non_empty([
                $access_request['company_record_status'] ?? null,
            ]),
        ];
    }

    private static function onboarding_payload(WP_User $user, ?array $access_request, array $account_state): array
    {
        $module_interest_value = $access_request['erp_module_interest'] ?? null;
        if (empty($module_interest_value)) {
            $module_interest_value = get_user_meta($user->ID, 'abitai_erp_module_interest', true);
        }
        $module_interest = self::decode_list_value($module_interest_value);
        $primary_workflow = self::first_non_empty([
            $access_request['primary_workflow'] ?? null,
            get_user_meta($user->ID, 'abitai_business_description', true),
        ]);
        $role = self::first_non_empty([
            $access_request['role'] ?? null,
            get_user_meta($user->ID, 'abitai_role', true),
            get_user_meta($user->ID, 'abitai_job_title', true),
        ]);
        $company_size = self::first_non_empty([
            $access_request['company_size'] ?? null,
            get_user_meta($user->ID, 'abitai_company_size', true),
        ]);
        $industry = self::first_non_empty([
            $access_request['industry'] ?? null,
            get_user_meta($user->ID, 'abitai_industry', true),
        ]);

        $required_fields_complete = $role !== ''
            && $company_size !== ''
            && $industry !== ''
            && $primary_workflow !== ''
            && !empty($module_interest);
        $review_status = $account_state['review_status'];
        $complete_statuses = [
            self::REVIEW_STATUS_PENDING_ADMIN_REVIEW,
            self::REVIEW_STATUS_APPROVED,
            self::REVIEW_STATUS_REJECTED,
        ];

        return [
            'status' => self::onboarding_status($review_status, $required_fields_complete),
            'completed' => in_array($review_status, $complete_statuses, true),
            'required_fields_complete' => $required_fields_complete,
            'role' => $role,
            'company_size' => $company_size,
            'industry' => $industry,
            'primary_workflow_provided' => $primary_workflow !== '',
            'erp_module_interest' => $module_interest,
            'current_system' => self::first_non_empty([
                $access_request['current_system'] ?? null,
            ]),
            'timeline' => self::first_non_empty([
                $access_request['timeline'] ?? null,
            ]),
        ];
    }

    private static function onboarding_status(string $review_status, bool $required_fields_complete): string
    {
        if ($review_status === self::REVIEW_STATUS_PENDING_EMAIL) {
            return 'blocked_until_email_verified';
        }

        if ($review_status === self::REVIEW_STATUS_ONBOARDING_REQUIRED || $review_status === self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED) {
            return $required_fields_complete ? 'ready_to_submit' : 'required';
        }

        if ($review_status === self::REVIEW_STATUS_PENDING_ADMIN_REVIEW) {
            return 'submitted_for_review';
        }

        if ($review_status === self::REVIEW_STATUS_APPROVED) {
            return 'approved';
        }

        if ($review_status === self::REVIEW_STATUS_REJECTED) {
            return 'closed';
        }

        return 'required';
    }

    private static function state_for_review_status(string $review_status): array
    {
        $states = [
            self::REVIEW_STATUS_PENDING_EMAIL => ['state' => 'verification_required', 'route' => 'verify_email'],
            self::REVIEW_STATUS_ONBOARDING_REQUIRED => ['state' => 'onboarding_required', 'route' => 'onboarding'],
            self::REVIEW_STATUS_PENDING_ADMIN_REVIEW => ['state' => 'review_pending', 'route' => 'review_pending'],
            self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED => ['state' => 'more_information_requested', 'route' => 'onboarding'],
            self::REVIEW_STATUS_APPROVED => ['state' => 'approved', 'route' => 'app'],
            self::REVIEW_STATUS_REJECTED => ['state' => 'rejected', 'route' => 'rejected'],
        ];

        return $states[$review_status] ?? ['state' => 'review_pending', 'route' => 'review_pending'];
    }

    private static function path_for_route(string $route): string
    {
        $paths = [
            'verify_email' => '/auth/verify',
            'onboarding' => '/auth/onboarding',
            'review_pending' => '/auth/review-pending',
            'app' => '/dashboard',
            'rejected' => '/auth/rejected',
            'locked' => '/auth/sign-in',
        ];

        return $paths[$route] ?? '/dashboard';
    }

    private static function path_for_review_status(string $review_status, bool $locked = false): string
    {
        if ($locked) {
            return self::path_for_route('locked');
        }

        $paths = [
            self::REVIEW_STATUS_PENDING_EMAIL => '/auth/verify',
            self::REVIEW_STATUS_ONBOARDING_REQUIRED => '/auth/onboarding',
            self::REVIEW_STATUS_PENDING_ADMIN_REVIEW => '/auth/review-pending',
            self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED => '/auth/more-information',
            self::REVIEW_STATUS_APPROVED => '/dashboard',
            self::REVIEW_STATUS_REJECTED => '/auth/rejected',
        ];

        return $paths[$review_status] ?? '/dashboard';
    }

    private static function first_non_empty(array $values): string
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function decode_list_value($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), 'strlen'));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), 'strlen'));
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    }

    private static function nullable_datetime($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private static function is_user_locked(WP_User $user): bool
    {
        if ((int) $user->user_status !== 0) {
            return true;
        }

        $lock_meta_keys = [
            'abit_saas_account_locked',
            'abit_saas_security_hold',
            'account_locked',
        ];

        foreach ($lock_meta_keys as $key) {
            $value = get_user_meta($user->ID, $key, true);
            if (true === filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        return false;
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
