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
    private const SCHEMA_VERSION = '2026-06-14.7';
    private const REST_NAMESPACE = 'abit-ai/v1';
    private const APPROVED_SENDER_DOMAIN = 'abit.ai';
    private const REVIEW_STATUS_PENDING_EMAIL = 'pending_email_verification';
    private const REVIEW_STATUS_ONBOARDING_REQUIRED = 'onboarding_required';
    private const REVIEW_STATUS_PENDING_ADMIN_REVIEW = 'pending_admin_review';
    private const REVIEW_STATUS_APPROVED = 'approved_for_mvp_access';
    private const REVIEW_STATUS_REJECTED = 'rejected';
    private const REVIEW_STATUS_MORE_INFORMATION_REQUESTED = 'more_information_requested';
    private const PROVISIONING_STATUS_REQUESTED = 'requested';
    private const WORKSPACE_STATUS_ACTIVE = 'active';
    private const WORKSPACE_MEMBER_STATUS_ACTIVE = 'active';
    private const WORKSPACE_MEMBER_ROLE_OWNER = 'owner';
    private const WORKSPACE_MEMBER_ROLE_MEMBER = 'member';
    private const CONSENT_RETENTION_RULE = 'active_plus_7_years_after_closure';

    public static function bootstrap(): void
    {
        add_action('init', [__CLASS__, 'maybe_install_schema']);
        add_action('phpmailer_init', [__CLASS__, 'configure_transactional_mailer']);
        add_action('wp_mail_failed', [__CLASS__, 'log_wp_mail_failure']);
        add_action('wp_mail_succeeded', [__CLASS__, 'log_wp_mail_success']);
        add_filter('wp_mail_from', [__CLASS__, 'mail_from']);
        add_filter('wp_mail_from_name', [__CLASS__, 'mail_from_name']);
        add_filter('retrieve_password_title', [__CLASS__, 'password_reset_subject'], 10, 3);
        add_filter('retrieve_password_message', [__CLASS__, 'password_reset_message'], 10, 4);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('template_redirect', [__CLASS__, 'handle_pretty_api_route'], 0);
        add_action('admin_menu', [__CLASS__, 'register_email_observability_page']);
        add_action('abit_saas_auth_email_bounced', [__CLASS__, 'record_email_bounce'], 10, 1);
    }

    public static function configure_transactional_mailer($phpmailer): void
    {
        if (empty($phpmailer->AltBody) && !empty($phpmailer->Body) && strip_tags($phpmailer->Body) !== $phpmailer->Body) {
            $phpmailer->AltBody = trim(wp_strip_all_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $phpmailer->Body)));
        }

        $host = self::env_value('ABIT_TRANSACTIONAL_SMTP_HOST');
        if ($host === '') {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = (int) (self::env_value('ABIT_TRANSACTIONAL_SMTP_PORT') ?: 587);
        $phpmailer->SMTPAuth = filter_var(self::env_value('ABIT_TRANSACTIONAL_SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN);

        $username = self::env_value('ABIT_TRANSACTIONAL_SMTP_USERNAME');
        if ($username !== '') {
            $phpmailer->Username = $username;
        }

        $password = self::env_value('ABIT_TRANSACTIONAL_SMTP_PASSWORD');
        if ($password !== '') {
            $phpmailer->Password = $password;
        }

        $secure = strtolower(self::env_value('ABIT_TRANSACTIONAL_SMTP_SECURE') ?: 'tls');
        if (in_array($secure, ['ssl', 'tls'], true)) {
            $phpmailer->SMTPSecure = $secure;
        }
    }

    public static function log_wp_mail_failure(WP_Error $error): void
    {
        self::log_email_delivery(
            [
                'message_type' => 'wordpress_mail',
                'delivery_result' => 'failure',
                'failure_reason_category' => 'wp_mail_failed',
                'failure_message' => $error->get_error_message(),
            ]
        );
    }

    public static function log_wp_mail_success(array $mail_data): void
    {
        self::log_email_delivery(
            [
                'message_type' => 'wordpress_mail',
                'delivery_result' => 'accepted',
                'recipient_count' => isset($mail_data['to']) ? count((array) $mail_data['to']) : null,
                'subject_hash' => isset($mail_data['subject']) ? self::hmac((string) $mail_data['subject']) : '',
            ]
        );
    }

    public static function mail_from(string $from_email): string
    {
        return self::transactional_sender_email();
    }

    public static function mail_from_name(string $from_name): string
    {
        return self::transactional_sender_name();
    }

    public static function password_reset_subject(string $title, string $user_login, WP_User $user_data): string
    {
        return apply_filters('abit_saas_auth_password_reset_subject', 'Reset your abit.ai password', $title, $user_login, $user_data);
    }

    public static function password_reset_message(string $message, string $key, string $user_login, WP_User $user_data): string
    {
        $reset_url = add_query_arg(
            [
                'key' => $key,
                'login' => $user_login,
            ],
            home_url('/auth/reset-password/')
        );

        self::log_email_delivery(
            [
                'actor_user_id' => (int) $user_data->ID,
                'message_type' => 'password_reset',
                'recipient_domain_hash' => self::email_domain_hash((string) $user_data->user_email),
                'delivery_result' => 'prepared',
                'delivery_channel' => 'email',
                'email_delivery_provider' => self::email_delivery_provider(),
                'sender_email' => self::transactional_sender_email(),
                'branded_link_path' => '/auth/reset-password/',
                'has_plaintext_fallback' => false,
                'subject_key' => 'password_reset',
            ]
        );

        return sprintf(
            "Hi %s,\n\nWe received a request to reset your abit.ai password.\n\nUse this link to set a new password:\n\n%s\n\nIf you did not request a password reset, you can ignore this email.\n\nabit.ai",
            self::email_display_name((string) $user_data->display_name),
            $reset_url
        );
    }

    public static function register_email_observability_page(): void
    {
        add_management_page(
            'ABiT Email Observability',
            'ABiT Email Observability',
            'list_users',
            'abit-email-observability',
            [__CLASS__, 'render_email_observability_page']
        );
    }

    public static function render_email_observability_page(): void
    {
        if (!current_user_can('list_users')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'abit-saas-auth'));
        }

        self::maybe_install_schema();

        global $wpdb;
        $rows = $wpdb->get_results(
            'SELECT id, business_email, company_name, review_status, email_delivery_status, email_delivery_last_event, email_delivery_last_event_at, email_delivery_sent_count, email_delivery_failed_count, email_delivery_bounced_count, email_token_expired_count, email_resend_throttled_count FROM ' . self::table('access_requests') . ' ORDER BY COALESCE(email_delivery_last_event_at, updated_at, created_at) DESC LIMIT 100',
            ARRAY_A
        );

        echo '<div class="wrap"><h1>ABiT Email Observability</h1>';
        echo '<p>Delivery metadata for support review. Raw tokens, full message bodies, and provider payloads are not displayed.</p>';
        echo '<table class="widefat fixed striped"><thead><tr>';
        foreach (['Request', 'Email', 'Company', 'Review status', 'Delivery status', 'Last event', 'Last event at', 'Sent', 'Failed', 'Bounced', 'Token expired', 'Resend throttled'] as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="12">No email delivery metadata recorded yet.</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . esc_html((string) $row['id']) . '</td>';
                echo '<td>' . esc_html(self::masked_email((string) $row['business_email'])) . '</td>';
                echo '<td>' . esc_html((string) $row['company_name']) . '</td>';
                echo '<td>' . esc_html((string) $row['review_status']) . '</td>';
                echo '<td>' . esc_html((string) ($row['email_delivery_status'] ?: 'unknown')) . '</td>';
                echo '<td>' . esc_html((string) ($row['email_delivery_last_event'] ?: 'none')) . '</td>';
                echo '<td>' . esc_html((string) ($row['email_delivery_last_event_at'] ?: '')) . '</td>';
                echo '<td>' . esc_html((string) (int) $row['email_delivery_sent_count']) . '</td>';
                echo '<td>' . esc_html((string) (int) $row['email_delivery_failed_count']) . '</td>';
                echo '<td>' . esc_html((string) (int) $row['email_delivery_bounced_count']) . '</td>';
                echo '<td>' . esc_html((string) (int) $row['email_token_expired_count']) . '</td>';
                echo '<td>' . esc_html((string) (int) $row['email_resend_throttled_count']) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table></div>';
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
            '/auth/verify',
            [
                'methods' => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
                'callback' => [__CLASS__, 'verify_email'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/auth/resend-verification',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'resend_verification'],
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

        register_rest_route(
            self::REST_NAMESPACE,
            '/provisioning/request',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'request_provisioning'],
                'permission_callback' => [__CLASS__, 'require_authentication'],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/workspace/slug/validate',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'validate_workspace_slug'],
                'permission_callback' => [__CLASS__, 'require_admin_review_access'],
            ]
        );
    }

    public static function handle_pretty_api_route(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = untrailingslashit($path);

        if ('/auth/verify' === $path && 'GET' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '') && !empty($_GET['token'])) {
            $request = new WP_REST_Request('GET', '/' . self::REST_NAMESPACE . '/auth/verify');
            $request->set_query_params(wp_unslash($_GET));
            $response = rest_ensure_response(self::verify_email($request));
            $data = $response->get_data();

            wp_safe_redirect(
                add_query_arg(
                    [
                        'state' => self::verification_ui_state_from_code((string) ($data['code'] ?? 'verification_invalid')),
                        'email' => sanitize_email((string) ($data['email'] ?? ($_GET['email'] ?? ''))),
                    ],
                    home_url('/auth/verify')
                )
            );
            exit;
        }

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
            '/api/auth/verify' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/verify',
                'callback' => [__CLASS__, 'verify_email'],
                'method' => 'POST',
            ],
            '/api/auth/resend-verification' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/auth/resend-verification',
                'callback' => [__CLASS__, 'resend_verification'],
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
            '/api/provisioning/request' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/provisioning/request',
                'callback' => [__CLASS__, 'request_provisioning'],
                'method' => 'POST',
            ],
            '/api/workspace/slug/validate' => [
                'rest_path' => '/' . self::REST_NAMESPACE . '/workspace/slug/validate',
                'callback' => [__CLASS__, 'validate_workspace_slug'],
                'method' => 'POST',
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
        $email_events = self::table('email_delivery_events');
        $provisioning_requests = self::table('provisioning_requests');
        $workspaces = self::table('workspaces');
        $workspace_memberships = self::table('workspace_memberships');

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
                workspace_slug_override VARCHAR(96) NULL,
                persona VARCHAR(64) NULL,
                review_status VARCHAR(64) NOT NULL,
                email_verified_at DATETIME NULL,
                terms_privacy_accepted_at DATETIME NULL,
                terms_version VARCHAR(64) NULL,
                privacy_version VARCHAR(64) NULL,
                latest_consent_audit_record_id BIGINT UNSIGNED NULL,
                email_delivery_status VARCHAR(32) NULL,
                email_delivery_last_event VARCHAR(64) NULL,
                email_delivery_last_event_at DATETIME NULL,
                email_delivery_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
                email_delivery_failed_count INT UNSIGNED NOT NULL DEFAULT 0,
                email_delivery_bounced_count INT UNSIGNED NOT NULL DEFAULT 0,
                email_token_expired_count INT UNSIGNED NOT NULL DEFAULT 0,
                email_resend_throttled_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY business_email (business_email),
                KEY user_id (user_id),
                KEY company_id (company_id),
                KEY review_status (review_status),
                KEY email_delivery_status (email_delivery_status),
                KEY workspace_slug_override (workspace_slug_override),
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

        dbDelta("
            CREATE TABLE {$email_events} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                access_request_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                company_id BIGINT UNSIGNED NULL,
                email_verification_token_id BIGINT UNSIGNED NULL,
                message_type VARCHAR(64) NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                delivery_status VARCHAR(32) NOT NULL,
                delivery_channel VARCHAR(32) NOT NULL DEFAULT 'email',
                email_delivery_provider VARCHAR(64) NOT NULL,
                recipient_domain_hash CHAR(64) NULL,
                sender_domain VARCHAR(128) NOT NULL,
                subject_key VARCHAR(96) NULL,
                failure_reason_category VARCHAR(96) NULL,
                bounce_type VARCHAR(64) NULL,
                provider_event_id_hash CHAR(64) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY access_request_id (access_request_id),
                KEY user_id (user_id),
                KEY company_id (company_id),
                KEY email_verification_token_id (email_verification_token_id),
                KEY message_type (message_type),
                KEY delivery_status (delivery_status),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$provisioning_requests} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                access_request_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                company_id BIGINT UNSIGNED NOT NULL,
                request_status VARCHAR(64) NOT NULL,
                requested_at DATETIME NOT NULL,
                processed_at DATETIME NULL,
                erp_tenant_reference VARCHAR(160) NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY access_request_id (access_request_id),
                KEY user_id (user_id),
                KEY company_id (company_id),
                KEY request_status (request_status)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$workspaces} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                workspace_key VARCHAR(96) NOT NULL,
                display_name VARCHAR(160) NOT NULL,
                status VARCHAR(32) NOT NULL,
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                workspace_slug_overridden TINYINT(1) NOT NULL DEFAULT 0,
                workspace_slug_override_source VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY company_id (company_id),
                UNIQUE KEY workspace_key (workspace_key),
                KEY status (status),
                KEY created_by_user_id (created_by_user_id)
            ) {$charset_collate};
        ");

        dbDelta("
            CREATE TABLE {$workspace_memberships} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                workspace_id BIGINT UNSIGNED NOT NULL,
                company_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                access_request_id BIGINT UNSIGNED NOT NULL,
                role VARCHAR(32) NOT NULL,
                status VARCHAR(32) NOT NULL,
                joined_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY workspace_user (workspace_id, user_id),
                KEY company_id (company_id),
                KEY user_id (user_id),
                KEY access_request_id (access_request_id),
                KEY role (role),
                KEY status (status)
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

            $mail_sent = self::send_verification_email(
                $data['business_email'],
                $data['full_name'],
                $token,
                [
                    'actor_user_id' => $user_id,
                    'access_request_id' => $access_request_id,
                    'company_id' => $company_id,
                    'email_verification_token_id' => $token_id,
                    'token_expires_at' => $expires_at,
                    'verification_send_reason' => 'initial_signup',
                ]
            );
            if (!$mail_sent) {
                throw new RuntimeException('Verification email could not be sent.');
            }

            self::mark_token_sent($token_id);
            self::audit_event(
                'auth_signup_created',
                [
                    'actor_user_id' => $user_id,
                    'actor_type' => 'user',
                    'entity_type' => 'access_request',
                    'entity_id' => $access_request_id,
                    'access_request_id' => $access_request_id,
                    'company_id' => $company_id,
                    'event_data' => [
                        'email' => $data['business_email'],
                        'review_status' => self::REVIEW_STATUS_PENDING_EMAIL,
                        'signup_flow_version' => 'api_v1',
                        'account_creation_method' => 'email_password',
                    ],
                ]
            );
            self::audit_event(
                'auth_consent_accepted',
                [
                    'actor_user_id' => $user_id,
                    'actor_type' => 'user',
                    'entity_type' => 'consent',
                    'entity_id' => $consent_id,
                    'access_request_id' => $access_request_id,
                    'company_id' => $company_id,
                    'event_data' => [
                        'email' => $data['business_email'],
                        'consent_audit_record_id' => $consent_id,
                        'capture_source' => 'signup_registration',
                    ],
                ]
            );
            self::audit_event(
                'auth_verification_sent',
                [
                    'actor_user_id' => $user_id,
                    'actor_type' => 'system',
                    'entity_type' => 'email_verification_token',
                    'entity_id' => $token_id,
                    'access_request_id' => $access_request_id,
                    'company_id' => $company_id,
                    'event_data' => [
                        'email' => $data['business_email'],
                        'verification_send_reason' => 'initial_signup',
                        'verification_delivery_channel' => 'email',
                        'email_delivery_provider' => self::email_delivery_provider(),
                        'email_domain_hash' => self::email_domain_hash($data['business_email']),
                        'token_expires_at' => $expires_at,
                    ],
                ]
            );
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
            self::audit_event(
                'auth_login_failed',
                [
                    'actor_type' => 'anonymous',
                    'entity_type' => 'auth',
                    'event_data' => [
                        'email' => $email,
                        'auth_method' => 'email_password',
                        'login_attempt_result' => 'failure',
                        'failure_reason_category' => 'invalid_credentials',
                    ],
                ]
            );
            return self::login_failed_response();
        }

        $account_state = self::account_state_for_user($user);
        if (!empty($account_state['locked'])) {
            wp_clear_auth_cookie();
            self::audit_event(
                'auth_login_failed',
                [
                    'actor_user_id' => (int) $user->ID,
                    'actor_type' => 'user',
                    'entity_type' => 'user',
                    'entity_id' => (int) $user->ID,
                    'access_request_id' => $account_state['access_request_id'],
                    'company_id' => $account_state['company_id'],
                    'event_data' => [
                        'email' => $email,
                        'auth_method' => 'email_password',
                        'login_attempt_result' => 'failure',
                        'failure_reason_category' => 'blocked',
                        'review_status' => $account_state['review_status'],
                    ],
                ]
            );
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
        self::audit_event(
            'auth_login_succeeded',
            [
                'actor_user_id' => (int) $user->ID,
                'actor_type' => 'user',
                'entity_type' => 'user',
                'entity_id' => (int) $user->ID,
                'access_request_id' => $account_state['access_request_id'],
                'company_id' => $account_state['company_id'],
                'event_data' => [
                    'email' => $email,
                    'auth_method' => 'email_password',
                    'login_attempt_result' => 'success',
                    'review_status' => $account_state['review_status'],
                    'is_email_verified' => $account_state['email_verified'],
                    'is_admin_approved' => $account_state['review_status'] === self::REVIEW_STATUS_APPROVED,
                ],
            ]
        );

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

    public static function verify_email(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $payload = self::request_payload($request);
        $token = self::verification_token_from_request($payload, $request);

        if ($token === '') {
            return self::verification_response(
                'verification_invalid',
                'Verification link cannot be used.',
                400
            );
        }

        global $wpdb;

        $now = current_time('mysql', true);
        $token_hash = self::hmac($token);

        $wpdb->query('START TRANSACTION');

        try {
            $token_row = $wpdb->get_row(
                $wpdb->prepare(
                    'SELECT id, access_request_id, user_id, token_hash, expires_at, consumed_at, created_at FROM ' . self::table('email_verification_tokens') . ' WHERE token_hash = %s LIMIT 1 FOR UPDATE',
                    $token_hash
                ),
                ARRAY_A
            );

            if (!is_array($token_row)) {
                $wpdb->query('ROLLBACK');
                return self::verification_response(
                    'verification_invalid',
                    'Verification link cannot be used.',
                    404
                );
            }

            $access_request = $wpdb->get_row(
                $wpdb->prepare(
                    'SELECT id, user_id, company_id, business_email, company_name, review_status, email_verified_at, workspace_slug_override FROM ' . self::table('access_requests') . ' WHERE id = %d LIMIT 1 FOR UPDATE',
                    (int) $token_row['access_request_id']
                ),
                ARRAY_A
            );

            if (!is_array($access_request)) {
                $wpdb->query('ROLLBACK');
                return self::verification_response(
                    'verification_invalid',
                    'Verification link cannot be used.',
                    404
                );
            }

            if (!empty($access_request['email_verified_at'])) {
                if (empty($token_row['consumed_at'])) {
                    self::consume_verification_token((int) $token_row['id'], $now);
                }
                $workspace_result = self::ensure_workspace_for_verified_access_request($access_request, $now);
                $wpdb->query('COMMIT');

                return self::verification_response(
                    'already_verified',
                    'Email already verified.',
                    200,
                    array_merge($access_request, ['workspace' => $workspace_result]),
                    $token_row
                );
            }

            if (!empty($token_row['consumed_at'])) {
                $wpdb->query('ROLLBACK');
                return self::verification_response(
                    'verification_used',
                    'Verification link cannot be used.',
                    409,
                    $access_request,
                    $token_row
                );
            }

            if (strtotime((string) $token_row['expires_at']) <= time()) {
                $wpdb->query('ROLLBACK');
                self::log_email_delivery(
                    [
                        'actor_user_id' => (int) $access_request['user_id'],
                        'access_request_id' => (int) $access_request['id'],
                        'company_id' => (int) $access_request['company_id'],
                        'email_verification_token_id' => (int) $token_row['id'],
                        'message_type' => 'email_verification',
                        'event_type' => 'token_expired',
                        'delivery_status' => 'token_expired',
                        'recipient_domain_hash' => self::email_domain_hash((string) $access_request['business_email']),
                        'subject_key' => 'verification_access_request',
                    ]
                );
                return self::verification_response(
                    'verification_expired',
                    'Verification link expired.',
                    410,
                    $access_request,
                    $token_row
                );
            }

            self::consume_verification_token((int) $token_row['id'], $now);
            self::mark_access_request_verified($access_request, $now);
            $workspace_result = self::ensure_workspace_for_verified_access_request($access_request, $now);

            $wpdb->query('COMMIT');

            self::audit_event(
                'auth_email_verified',
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'actor_type' => 'user',
                    'entity_type' => 'email_verification_token',
                    'entity_id' => (int) $token_row['id'],
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'event_data' => [
                        'email' => (string) $access_request['business_email'],
                        'previous_review_status' => (string) $access_request['review_status'],
                        'review_status' => self::REVIEW_STATUS_ONBOARDING_REQUIRED,
                        'verification_age_seconds' => max(0, time() - strtotime((string) $token_row['created_at'])),
                        'verification_attempt_result' => 'success',
                        'workspace_status' => $workspace_result['status'],
                        'workspace_role' => $workspace_result['membership']['role'] ?? null,
                        'workspace_hold_reason' => $workspace_result['hold_reason'] ?? null,
                    ],
                ]
            );

            return self::verification_response(
                'verified',
                'Email verified.',
                200,
                array_merge($access_request, ['review_status' => self::REVIEW_STATUS_ONBOARDING_REQUIRED, 'email_verified_at' => $now, 'workspace' => $workspace_result]),
                array_merge($token_row, ['consumed_at' => $now])
            );
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');

            return self::verification_response(
                'verification_failed',
                'Verification could not be completed.',
                500
            );
        }
    }

    public static function resend_verification(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $payload = self::request_payload($request);
        $email = strtolower(trim((string) ($payload['business_email'] ?? $payload['email'] ?? '')));
        if (strlen($email) > 254 || !is_email($email)) {
            return self::field_error_response(['business_email' => 'Enter a valid business email address.']);
        }

        $generic = [
            'message' => 'If an eligible request exists, we will send a new verification link.',
            'status' => 'accepted',
            'sent' => false,
        ];

        $access_request = self::access_request_for_email($email);
        if (!is_array($access_request) || (string) $access_request['review_status'] !== self::REVIEW_STATUS_PENDING_EMAIL || !empty($access_request['email_verified_at'])) {
            return new WP_REST_Response($generic, 202);
        }

        $user = get_userdata((int) $access_request['user_id']);
        if ($user instanceof WP_User && self::is_user_locked($user)) {
            return new WP_REST_Response($generic, 202);
        }

        $retry_after = self::verification_resend_retry_after((int) $access_request['id']);
        if ($retry_after > 0) {
            self::log_email_delivery(
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'message_type' => 'email_verification',
                    'event_type' => 'resend_throttled',
                    'delivery_status' => 'resend_throttled',
                    'recipient_domain_hash' => self::email_domain_hash($email),
                    'subject_key' => 'verification_access_request',
                    'failure_reason_category' => 'resend_rate_limit',
                ]
            );

            return new WP_REST_Response(
                array_merge($generic, ['status' => 'rate_limited', 'retry_after' => $retry_after]),
                429
            );
        }

        global $wpdb;
        $now = current_time('mysql', true);
        $token = self::new_token();
        $token_hash = self::hmac($token);
        $expires_at = gmdate('Y-m-d H:i:s', time() + (int) apply_filters('abit_saas_auth_email_verification_ttl', DAY_IN_SECONDS));

        $wpdb->query('START TRANSACTION');

        try {
            $wpdb->query(
                $wpdb->prepare(
                    'UPDATE ' . self::table('email_verification_tokens') . ' SET consumed_at = %s WHERE access_request_id = %d AND consumed_at IS NULL',
                    $now,
                    (int) $access_request['id']
                )
            );

            $token_id = self::insert_verification_token((int) $access_request['user_id'], (int) $access_request['id'], $token_hash, $expires_at, $now);
            $sent = self::send_verification_email(
                $email,
                (string) $access_request['full_name'],
                $token,
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'email_verification_token_id' => $token_id,
                    'token_expires_at' => $expires_at,
                    'verification_send_reason' => 'resend',
                ]
            );
            if (!$sent) {
                throw new RuntimeException('Verification email could not be sent.');
            }

            self::mark_token_sent($token_id);
            self::audit_event(
                'auth_verification_sent',
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'actor_type' => 'system',
                    'entity_type' => 'email_verification_token',
                    'entity_id' => $token_id,
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'event_data' => [
                        'verification_send_reason' => 'resend',
                        'verification_delivery_channel' => 'email',
                        'email_delivery_provider' => self::email_delivery_provider(),
                        'email_domain_hash' => self::email_domain_hash($email),
                        'token_expires_at' => $expires_at,
                    ],
                ]
            );
            $wpdb->query('COMMIT');

            return new WP_REST_Response(
                array_merge($generic, ['sent' => true, 'email_verification_token_id' => $token_id]),
                202
            );
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');
            self::log_email_delivery(
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'message_type' => 'email_verification',
                    'event_type' => 'delivery_attempted',
                    'delivery_status' => 'failed',
                    'recipient_domain_hash' => self::email_domain_hash($email),
                    'subject_key' => 'verification_access_request',
                    'failure_reason_category' => 'send_failed',
                ]
            );

            return new WP_REST_Response($generic, 202);
        }
    }

    public static function record_email_bounce(array $event): void
    {
        self::log_email_delivery(
            [
                'actor_user_id' => isset($event['user_id']) ? (int) $event['user_id'] : null,
                'access_request_id' => isset($event['access_request_id']) ? (int) $event['access_request_id'] : null,
                'company_id' => isset($event['company_id']) ? (int) $event['company_id'] : null,
                'email_verification_token_id' => isset($event['email_verification_token_id']) ? (int) $event['email_verification_token_id'] : null,
                'message_type' => sanitize_key((string) ($event['message_type'] ?? 'email')),
                'event_type' => 'bounced',
                'delivery_status' => 'bounced',
                'recipient_domain_hash' => self::nullable_hash($event['recipient_domain_hash'] ?? null),
                'subject_key' => self::nullable_key($event['subject_key'] ?? null),
                'bounce_type' => self::nullable_key($event['bounce_type'] ?? null),
                'provider_event_id' => (string) ($event['provider_event_id'] ?? ''),
            ]
        );
    }

    public static function logout(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = self::current_authenticated_user_id();
        $session_token = wp_get_session_token();

        if ($user_id <= 0 || $session_token === '') {
            wp_clear_auth_cookie();
            wp_set_current_user(0);
            self::audit_event(
                'auth_logout',
                [
                    'actor_type' => 'anonymous',
                    'entity_type' => 'session',
                    'event_data' => [
                        'logout_result' => 'no_active_session',
                        'revoked' => false,
                    ],
                ]
            );

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
        $user = get_userdata($user_id);
        $account_state = $user instanceof WP_User ? self::account_state_for_user($user) : [];
        wp_clear_auth_cookie();
        wp_set_current_user(0);
        self::audit_event(
            'auth_logout',
            [
                'actor_user_id' => $user_id,
                'actor_type' => 'user',
                'entity_type' => 'session',
                'entity_id' => $user_id,
                'access_request_id' => $account_state['access_request_id'] ?? null,
                'company_id' => $account_state['company_id'] ?? null,
                'event_data' => [
                    'logout_result' => 'revoked',
                    'revoked' => true,
                ],
            ]
        );

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
        $provisioning = self::provisioning_payload($user, $access_request, $account_state, $onboarding);
        $workspace = self::workspace_payload($user, $access_request);

        return new WP_REST_Response(
            [
                'authenticated' => true,
                'user' => self::public_user_payload($user, $access_request),
                'verification' => [
                    'email_verified' => $account_state['email_verified'],
                    'status' => $account_state['email_verified'] ? 'verified' : 'pending',
                    'email_verified_at' => self::nullable_datetime($access_request['email_verified_at'] ?? null),
                    'delivery' => self::email_observability_payload($access_request),
                ],
                'company' => self::company_payload($user, $access_request),
                'workspace' => $workspace,
                'role' => $onboarding['role'],
                'onboarding' => $onboarding,
                'access_request' => [
                    'id' => is_array($access_request) ? (int) $access_request['id'] : null,
                    'status' => $account_state['review_status'],
                    'created_at' => self::nullable_datetime($access_request['created_at'] ?? null),
                    'updated_at' => self::nullable_datetime($access_request['updated_at'] ?? null),
                ],
                'provisioning' => $provisioning,
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

    public static function request_provisioning(WP_REST_Request $request): WP_REST_Response
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
        $eligibility = self::provisioning_eligibility($user, $access_request, $account_state, $onboarding);

        if (!$eligibility['eligible']) {
            return new WP_REST_Response(
                [
                    'message' => $eligibility['message'],
                    'code' => $eligibility['code'],
                    'eligible' => false,
                    'missing_requirements' => $eligibility['missing_requirements'],
                    'provisioning' => self::provisioning_payload($user, $access_request, $account_state, $onboarding),
                ],
                $eligibility['status']
            );
        }

        try {
            $provisioning_request = self::ensure_provisioning_request($access_request, $user);
        } catch (Throwable $exception) {
            return new WP_REST_Response(
                [
                    'message' => 'Provisioning request could not be recorded.',
                    'code' => 'provisioning_request_failed',
                ],
                500
            );
        }

        self::audit_event(
            'auth_provisioning_requested',
            [
                'actor_user_id' => (int) $user->ID,
                'actor_type' => 'user',
                'entity_type' => 'provisioning_request',
                'entity_id' => (int) $provisioning_request['id'],
                'access_request_id' => (int) $access_request['id'],
                'company_id' => (int) $access_request['company_id'],
                'event_data' => [
                    'request_status' => $provisioning_request['request_status'],
                    'review_status' => $account_state['review_status'],
                    'created' => !empty($provisioning_request['_created']),
                ],
            ]
        );

        return new WP_REST_Response(
            [
                'message' => 'Provisioning request recorded.',
                'eligible' => true,
                'provisioning' => self::format_provisioning_request($provisioning_request),
                'access_request' => [
                    'id' => (int) $access_request['id'],
                    'status' => $account_state['review_status'],
                ],
            ],
            !empty($provisioning_request['_created']) ? 201 : 200
        );
    }

    public static function validate_workspace_slug(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        if (!current_user_can('list_users')) {
            return new WP_REST_Response(
                [
                    'message' => 'Admin review access is required.',
                    'code' => 'rest_forbidden',
                ],
                403
            );
        }

        $payload = self::request_payload($request);
        $company_id = isset($payload['company_id']) ? max(0, (int) $payload['company_id']) : 0;
        $raw_slug = self::first_non_empty([$payload['slug'] ?? null, $payload['workspace_slug'] ?? null]);
        $company_name = self::clean_text($payload['company_name'] ?? '');
        $candidate = $raw_slug !== '' ? $raw_slug : $company_name;
        $result = self::workspace_slug_validation_result($candidate, $company_id);

        $status = !empty($result['valid']) ? 200 : 422;
        return new WP_REST_Response($result, $status);
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

    public static function require_admin_review_access()
    {
        if (current_user_can('list_users')) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            'Admin review access is required.',
            ['status' => 403]
        );
    }

    private static function audit_event(string $event_type, array $context): void
    {
        if (function_exists('abitai_auth_write_audit_log')) {
            abitai_auth_write_audit_log($event_type, $context);
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

    private static function verification_token_from_request(array $payload, WP_REST_Request $request): string
    {
        $token = (string) ($payload['token'] ?? $request->get_param('token') ?? '');
        $token = trim($token);

        if (!preg_match('/^[A-Za-z0-9_-]{32,256}$/', $token)) {
            return '';
        }

        return $token;
    }

    private static function verification_response(string $code, string $message, int $status, ?array $access_request = null, ?array $token_row = null): WP_REST_Response
    {
        $route = self::verification_ui_state_from_code($code);
        $email = is_array($access_request) ? (string) ($access_request['business_email'] ?? '') : '';
        $next_status = is_array($access_request) && !empty($access_request['review_status'])
            ? (string) $access_request['review_status']
            : self::REVIEW_STATUS_PENDING_EMAIL;

        $payload = [
            'message' => $message,
            'code' => $code,
            'status' => $route,
            'state' => $route,
            'route' => $route === 'success' || $route === 'already_verified' ? 'onboarding' : 'verify_email',
            'next_path' => $route === 'success' || $route === 'already_verified' ? '/auth/onboarding' : '/auth/verify',
            'resend_path' => '/auth/verify',
            'email' => $email,
            'email_verified' => $route === 'success' || $route === 'already_verified',
            'access_request_id' => is_array($access_request) ? (int) $access_request['id'] : null,
            'review_status' => $next_status,
        ];

        if (is_array($token_row)) {
            $payload['token'] = [
                'id' => (int) $token_row['id'],
                'expires_at' => self::nullable_datetime($token_row['expires_at'] ?? null),
                'consumed_at' => self::nullable_datetime($token_row['consumed_at'] ?? null),
            ];
        }

        if (is_array($access_request) && isset($access_request['workspace']) && is_array($access_request['workspace'])) {
            $payload['workspace'] = $access_request['workspace'];
        }

        return new WP_REST_Response($payload, $status);
    }

    private static function verification_ui_state_from_code(string $code): string
    {
        $states = [
            'verified' => 'success',
            'already_verified' => 'already_verified',
            'verification_expired' => 'expired',
            'verification_used' => 'failed',
            'verification_invalid' => 'failed',
            'verification_failed' => 'failed',
        ];

        return $states[$code] ?? 'failed';
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
                'SELECT ar.id, ar.user_id, ar.company_id, ar.full_name, ar.business_email, ar.company_name, ar.country_region, ar.intended_use_case, ar.role, ar.company_size, ar.industry, ar.primary_workflow, ar.erp_module_interest, ar.current_system, ar.timeline, ar.notes, ar.workspace_slug_override, ar.persona, ar.review_status, ar.email_verified_at, ar.terms_privacy_accepted_at, ar.terms_version, ar.privacy_version, ar.email_delivery_status, ar.email_delivery_last_event, ar.email_delivery_last_event_at, ar.email_delivery_sent_count, ar.email_delivery_failed_count, ar.email_delivery_bounced_count, ar.email_token_expired_count, ar.email_resend_throttled_count, ar.created_at, ar.updated_at, c.company_name AS company_record_name, c.country_region AS company_record_country_region, c.draft_status AS company_record_status FROM ' . self::table('access_requests') . ' ar LEFT JOIN ' . self::table('companies') . ' c ON c.id = ar.company_id WHERE ar.user_id = %d OR ar.business_email = %s ORDER BY ar.id DESC LIMIT 1',
                $user->ID,
                $user->user_email
            ),
            ARRAY_A
        );

        return is_array($access_request) ? $access_request : null;
    }

    private static function access_request_for_email(string $email): ?array
    {
        global $wpdb;

        $access_request = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, user_id, company_id, full_name, business_email, review_status, email_verified_at FROM ' . self::table('access_requests') . ' WHERE business_email = %s ORDER BY id DESC LIMIT 1',
                $email
            ),
            ARRAY_A
        );

        return is_array($access_request) ? $access_request : null;
    }

    private static function verification_resend_retry_after(int $access_request_id): int
    {
        global $wpdb;

        $last_event_at = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT created_at FROM ' . self::table('email_delivery_events') . ' WHERE access_request_id = %d AND message_type = %s AND event_type = %s ORDER BY created_at DESC LIMIT 1',
                $access_request_id,
                'email_verification',
                'delivery_attempted'
            )
        );

        if ($last_event_at) {
            $cooldown = (int) apply_filters('abit_saas_auth_verification_resend_cooldown', 60);
            $elapsed = time() - strtotime((string) $last_event_at);
            if ($elapsed < $cooldown) {
                return max(1, $cooldown - $elapsed);
            }
        }

        $hourly_limit = (int) apply_filters('abit_saas_auth_verification_resend_hourly_limit', 5);
        $hourly_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::table('email_delivery_events') . ' WHERE access_request_id = %d AND message_type = %s AND event_type = %s AND created_at >= %s',
                $access_request_id,
                'email_verification',
                'delivery_attempted',
                gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)
            )
        );

        return $hourly_count >= $hourly_limit ? 3600 : 0;
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

    private static function email_observability_payload(?array $access_request): array
    {
        if (!is_array($access_request)) {
            return [
                'status' => null,
                'last_event' => null,
                'last_event_at' => null,
                'sent_count' => 0,
                'failed_count' => 0,
                'bounced_count' => 0,
                'token_expired_count' => 0,
                'resend_throttled_count' => 0,
            ];
        }

        return [
            'status' => self::nullable_key($access_request['email_delivery_status'] ?? null),
            'last_event' => self::nullable_key($access_request['email_delivery_last_event'] ?? null),
            'last_event_at' => self::nullable_datetime($access_request['email_delivery_last_event_at'] ?? null),
            'sent_count' => (int) ($access_request['email_delivery_sent_count'] ?? 0),
            'failed_count' => (int) ($access_request['email_delivery_failed_count'] ?? 0),
            'bounced_count' => (int) ($access_request['email_delivery_bounced_count'] ?? 0),
            'token_expired_count' => (int) ($access_request['email_token_expired_count'] ?? 0),
            'resend_throttled_count' => (int) ($access_request['email_resend_throttled_count'] ?? 0),
        ];
    }

    private static function onboarding_payload(WP_User $user, ?array $access_request, array $account_state): array
    {
        $module_interest_value = $access_request['erp_module_interest'] ?? null;
        if (empty($module_interest_value)) {
            $module_interest_value = get_user_meta($user->ID, 'abitai_erp_module_interest', true);
        }
        $module_interest = self::normalize_erp_module_interests(self::decode_list_value($module_interest_value));
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

        $module_mapping = self::erp_onboarding_module_mapping($module_interest);
        $admin_review_fields = self::onboarding_admin_review_fields(
            $access_request,
            $role,
            $company_size,
            $industry,
            $primary_workflow,
            $module_interest,
            $module_mapping
        );

        return [
            'status' => self::onboarding_status($review_status, $required_fields_complete),
            'completed' => in_array($review_status, $complete_statuses, true),
            'required_fields_complete' => $required_fields_complete,
            'role' => $role,
            'company_size' => $company_size,
            'industry' => $industry,
            'primary_workflow_provided' => $primary_workflow !== '',
            'erp_module_interest' => $module_interest,
            'onboarding_templates' => $module_mapping['onboarding_templates'],
            'qualification_tags' => $module_mapping['qualification_tags'],
            'admin_review_fields' => $admin_review_fields,
            'current_system' => self::first_non_empty([
                $access_request['current_system'] ?? null,
            ]),
            'timeline' => self::first_non_empty([
                $access_request['timeline'] ?? null,
            ]),
        ];
    }

    private static function normalize_erp_module_interests(array $module_interest): array
    {
        $aliases = [
            'accounts' => 'accounting',
            'finance' => 'accounting',
            'financials' => 'accounting',
            'selling' => 'sales',
            'sales_crm' => 'sales',
            'purchase' => 'buying',
            'purchasing' => 'buying',
            'inventory' => 'stock',
            'inventory_valuation' => 'stock',
            'warehouse' => 'stock',
            'hr' => 'hr_payroll',
            'payroll' => 'hr_payroll',
            'support' => 'support_helpdesk',
            'helpdesk' => 'support_helpdesk',
            'portal' => 'website_portal',
            'website' => 'website_portal',
            'reports' => 'reports_analytics',
            'analytics' => 'reports_analytics',
            'full_erp' => 'full_erp_evaluation',
            'full_evaluation' => 'full_erp_evaluation',
            'unsure' => 'not_sure',
            'not-sure' => 'not_sure',
        ];
        $valid_modules = [
            'accounting',
            'crm',
            'sales',
            'buying',
            'stock',
            'manufacturing',
            'projects',
            'hr_payroll',
            'support_helpdesk',
            'website_portal',
            'reports_analytics',
            'integrations',
            'full_erp_evaluation',
            'not_sure',
        ];

        $normalized = [];
        foreach ($module_interest as $module) {
            $key = sanitize_key((string) $module);
            if ($key === '') {
                continue;
            }

            $key = $aliases[$key] ?? $key;
            if (!in_array($key, $valid_modules, true)) {
                continue;
            }

            $normalized[] = $key;
        }

        return array_values(array_unique($normalized));
    }

    private static function erp_onboarding_module_mapping(array $module_interest): array
    {
        $definitions = [
            'accounting' => [
                'template' => 'erpnext_finance_onboarding',
                'tags' => ['module:finance', 'workflow:financial_controls', 'qualification:finance_review'],
            ],
            'crm' => [
                'template' => 'erpnext_crm_onboarding',
                'tags' => ['module:crm', 'workflow:pipeline_management'],
            ],
            'sales' => [
                'template' => 'erpnext_sales_onboarding',
                'tags' => ['module:sales', 'workflow:order_to_cash'],
            ],
            'buying' => [
                'template' => 'erpnext_procurement_onboarding',
                'tags' => ['module:procurement', 'workflow:purchase_to_pay'],
            ],
            'stock' => [
                'template' => 'erpnext_inventory_onboarding',
                'tags' => ['module:inventory', 'workflow:stock_control', 'qualification:inventory_review'],
            ],
            'manufacturing' => [
                'template' => 'erpnext_manufacturing_onboarding',
                'tags' => ['module:manufacturing', 'workflow:production_planning'],
            ],
            'projects' => [
                'template' => 'erpnext_projects_onboarding',
                'tags' => ['module:projects', 'workflow:project_delivery'],
            ],
            'hr_payroll' => [
                'template' => 'erpnext_hr_payroll_onboarding',
                'tags' => ['module:hr_payroll', 'workflow:people_operations'],
            ],
            'support_helpdesk' => [
                'template' => 'erpnext_support_onboarding',
                'tags' => ['module:support', 'workflow:service_management'],
            ],
            'website_portal' => [
                'template' => 'erpnext_portal_onboarding',
                'tags' => ['module:portal', 'workflow:customer_self_service'],
            ],
            'reports_analytics' => [
                'template' => 'erpnext_reporting_onboarding',
                'tags' => ['module:reporting', 'workflow:management_reporting'],
            ],
            'integrations' => [
                'template' => 'erpnext_integrations_onboarding',
                'tags' => ['module:integrations', 'workflow:system_integration'],
            ],
            'full_erp_evaluation' => [
                'template' => 'erpnext_full_erp_evaluation_onboarding',
                'tags' => ['module:full_erp', 'qualification:multi_module_review'],
            ],
            'not_sure' => [
                'template' => 'erpnext_discovery_onboarding',
                'tags' => ['module:not_sure', 'qualification:discovery_required'],
            ],
        ];

        $templates = [];
        $tags = [];
        foreach ($module_interest as $module) {
            if (empty($definitions[$module])) {
                continue;
            }

            $templates[] = $definitions[$module]['template'];
            $tags = array_merge($tags, $definitions[$module]['tags']);
        }

        if (in_array('accounting', $module_interest, true) && in_array('stock', $module_interest, true)) {
            $tags[] = 'qualification:finance_inventory_review';
        }

        return [
            'onboarding_templates' => array_values(array_unique($templates)),
            'qualification_tags' => array_values(array_unique($tags)),
        ];
    }

    private static function onboarding_admin_review_fields(?array $access_request, string $role, string $company_size, string $industry, string $primary_workflow, array $module_interest, array $module_mapping): array
    {
        return [
            'persona' => self::first_non_empty([
                $access_request['persona'] ?? null,
                self::derive_persona($role, $primary_workflow, $module_interest),
            ]),
            'company_size' => $company_size,
            'industry' => $industry,
            'country_region' => self::first_non_empty([
                $access_request['country_region'] ?? null,
            ]),
            'erp_module_interest' => $module_interest,
            'onboarding_templates' => $module_mapping['onboarding_templates'],
            'qualification_tags' => $module_mapping['qualification_tags'],
        ];
    }

    private static function derive_persona(string $role, string $primary_workflow, array $module_interest): string
    {
        $text = strtolower($role . ' ' . $primary_workflow . ' ' . implode(' ', $module_interest));

        if (preg_match('/\b(owner|founder|ceo|co-founder|director|managing partner|executive)\b/', $text)) {
            return 'owner_executive';
        }

        if (preg_match('/\b(finance|accounting|accountant|cfo|controller|invoice|billing|accounts)\b/', $text)) {
            return 'finance_lead';
        }

        if (preg_match('/\b(operations|inventory|stock|warehouse|manufacturing|production|procurement|supply chain|project)\b/', $text)) {
            return 'operations_lead';
        }

        if (preg_match('/\b(it|technology|systems|admin|administrator|integration|portal|website)\b/', $text)) {
            return 'it_admin_buyer';
        }

        return 'other_business_user';
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

    private static function nullable_key($value): ?string
    {
        $value = sanitize_key((string) $value);
        return $value === '' ? null : $value;
    }

    private static function nullable_hash($value): ?string
    {
        $value = strtolower(trim((string) $value));
        return preg_match('/^[a-f0-9]{64}$/', $value) ? $value : null;
    }

    private static function workspace_payload(WP_User $user, ?array $access_request): array
    {
        if (!is_array($access_request) || empty($access_request['company_id'])) {
            return [
                'created' => false,
                'status' => 'not_available',
                'hold_reason' => 'missing_company_profile',
                'workspace' => null,
                'membership' => null,
            ];
        }

        $hold_reason = self::workspace_hold_reason($access_request);
        $workspace = self::workspace_for_company((int) $access_request['company_id']);
        $membership = is_array($workspace) ? self::workspace_membership_for_user((int) $workspace['id'], (int) $user->ID) : null;

        return [
            'created' => is_array($workspace),
            'status' => is_array($workspace) ? (string) $workspace['status'] : ($hold_reason === null ? 'not_created' : 'held'),
            'hold_reason' => $hold_reason,
            'workspace' => is_array($workspace) ? self::format_workspace($workspace) : null,
            'membership' => is_array($membership) ? self::format_workspace_membership($membership) : null,
        ];
    }

    private static function ensure_workspace_for_verified_access_request(array $access_request, string $now): array
    {
        $hold_reason = self::workspace_hold_reason($access_request);
        if ($hold_reason !== null) {
            self::audit_event(
                'auth_workspace_creation_held',
                [
                    'actor_user_id' => (int) $access_request['user_id'],
                    'actor_type' => 'system',
                    'entity_type' => 'company',
                    'entity_id' => (int) $access_request['company_id'],
                    'access_request_id' => (int) $access_request['id'],
                    'company_id' => (int) $access_request['company_id'],
                    'event_data' => [
                        'hold_reason' => $hold_reason,
                    ],
                ]
            );

            return [
                'created' => false,
                'status' => 'held',
                'hold_reason' => $hold_reason,
                'workspace' => null,
                'membership' => null,
            ];
        }

        $override = self::normalize_workspace_slug((string) ($access_request['workspace_slug_override'] ?? ''));
        if ($override !== '') {
            $override_validation = self::workspace_slug_validation_result($override, (int) $access_request['company_id']);
            if (empty($override_validation['valid'])) {
                self::audit_event(
                    'auth_workspace_slug_rejected',
                    [
                        'actor_user_id' => (int) $access_request['user_id'],
                        'actor_type' => 'system',
                        'entity_type' => 'company',
                        'entity_id' => (int) $access_request['company_id'],
                        'access_request_id' => (int) $access_request['id'],
                        'company_id' => (int) $access_request['company_id'],
                        'event_data' => [
                            'workspace_slug' => $override_validation['slug'],
                            'rejection_code' => $override_validation['code'],
                            'suggested_slug' => $override_validation['suggested_slug'],
                        ],
                    ]
                );

                return [
                    'created' => false,
                    'status' => 'held',
                    'hold_reason' => (string) $override_validation['code'],
                    'suggested_workspace_key' => (string) $override_validation['suggested_slug'],
                    'workspace' => null,
                    'membership' => null,
                ];
            }
        }

        $workspace = self::ensure_company_workspace($access_request, $now);
        $membership = self::ensure_workspace_membership($workspace, $access_request, $now);

        self::audit_event(
            'auth_workspace_membership_ready',
            [
                'actor_user_id' => (int) $access_request['user_id'],
                'actor_type' => 'system',
                'entity_type' => 'workspace_membership',
                'entity_id' => (int) $membership['id'],
                'access_request_id' => (int) $access_request['id'],
                'company_id' => (int) $access_request['company_id'],
                'event_data' => [
                    'workspace_id' => (int) $workspace['id'],
                    'membership_role' => (string) $membership['role'],
                    'workspace_created' => !empty($workspace['_created']),
                    'membership_created' => !empty($membership['_created']),
                ],
            ]
        );

        return [
            'created' => true,
            'status' => (string) $workspace['status'],
            'hold_reason' => null,
            'workspace' => self::format_workspace($workspace),
            'membership' => self::format_workspace_membership($membership),
        ];
    }

    private static function workspace_hold_reason(array $access_request): ?string
    {
        $user = get_userdata((int) $access_request['user_id']);
        if ($user instanceof WP_User && (int) $user->user_status !== 0) {
            return 'account_locked';
        }

        $hold_meta_keys = [
            'abit_saas_risk_hold' => 'risk_hold',
            'abit_saas_admin_hold' => 'admin_hold',
            'abit_saas_security_hold' => 'security_hold',
            'abit_saas_account_locked' => 'account_locked',
            'account_locked' => 'account_locked',
        ];

        foreach ($hold_meta_keys as $key => $reason) {
            $value = get_user_meta((int) $access_request['user_id'], $key, true);
            if (true === filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return $reason;
            }
        }

        $filtered = apply_filters('abit_saas_auth_workspace_hold_reason', null, $access_request, $user);
        if (is_string($filtered) && $filtered !== '') {
            return sanitize_key($filtered);
        }

        return null;
    }

    private static function workspace_for_company(int $company_id): ?array
    {
        global $wpdb;

        $workspace = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, company_id, workspace_key, display_name, status, created_by_user_id, workspace_slug_overridden, workspace_slug_override_source, created_at, updated_at FROM ' . self::table('workspaces') . ' WHERE company_id = %d LIMIT 1',
                $company_id
            ),
            ARRAY_A
        );

        return is_array($workspace) ? $workspace : null;
    }

    private static function workspace_membership_for_user(int $workspace_id, int $user_id): ?array
    {
        global $wpdb;

        $membership = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, workspace_id, company_id, user_id, access_request_id, role, status, joined_at, created_at, updated_at FROM ' . self::table('workspace_memberships') . ' WHERE workspace_id = %d AND user_id = %d LIMIT 1',
                $workspace_id,
                $user_id
            ),
            ARRAY_A
        );

        return is_array($membership) ? $membership : null;
    }

    private static function ensure_company_workspace(array $access_request, string $now): array
    {
        $existing = self::workspace_for_company((int) $access_request['company_id']);
        if (is_array($existing)) {
            $existing['_created'] = false;
            return $existing;
        }

        global $wpdb;
        $workspace_key = self::unique_workspace_key((int) $access_request['company_id'], (string) $access_request['company_name'], (string) ($access_request['workspace_slug_override'] ?? ''));
        $slug_overridden = self::normalize_workspace_slug((string) ($access_request['workspace_slug_override'] ?? '')) !== '';
        $inserted = $wpdb->insert(
            self::table('workspaces'),
            [
                'company_id' => (int) $access_request['company_id'],
                'workspace_key' => $workspace_key,
                'display_name' => (string) $access_request['company_name'],
                'status' => self::WORKSPACE_STATUS_ACTIVE,
                'created_by_user_id' => (int) $access_request['user_id'],
                'workspace_slug_overridden' => $slug_overridden ? 1 : 0,
                'workspace_slug_override_source' => $slug_overridden ? 'admin' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Workspace could not be created.');
        }

        $created = self::workspace_for_company((int) $access_request['company_id']);
        if (!is_array($created)) {
            throw new RuntimeException('Workspace could not be loaded.');
        }

        $created['_created'] = true;
        return $created;
    }

    private static function ensure_workspace_membership(array $workspace, array $access_request, string $now): array
    {
        $existing = self::workspace_membership_for_user((int) $workspace['id'], (int) $access_request['user_id']);
        if (is_array($existing)) {
            $existing['_created'] = false;
            return $existing;
        }

        global $wpdb;
        $active_members_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::table('workspace_memberships') . ' WHERE workspace_id = %d AND status = %s',
                (int) $workspace['id'],
                self::WORKSPACE_MEMBER_STATUS_ACTIVE
            )
        );
        $role = $active_members_count === 0 ? self::WORKSPACE_MEMBER_ROLE_OWNER : self::WORKSPACE_MEMBER_ROLE_MEMBER;

        $inserted = $wpdb->insert(
            self::table('workspace_memberships'),
            [
                'workspace_id' => (int) $workspace['id'],
                'company_id' => (int) $access_request['company_id'],
                'user_id' => (int) $access_request['user_id'],
                'access_request_id' => (int) $access_request['id'],
                'role' => $role,
                'status' => self::WORKSPACE_MEMBER_STATUS_ACTIVE,
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Workspace membership could not be created.');
        }

        $created = self::workspace_membership_for_user((int) $workspace['id'], (int) $access_request['user_id']);
        if (!is_array($created)) {
            throw new RuntimeException('Workspace membership could not be loaded.');
        }

        $created['_created'] = true;
        return $created;
    }

    private static function unique_workspace_key(int $company_id, string $company_name, string $admin_override = ''): string
    {
        $override = self::normalize_workspace_slug($admin_override);
        if ($override !== '') {
            $validation = self::workspace_slug_validation_result($override, $company_id);
            if (!empty($validation['valid'])) {
                return (string) $validation['slug'];
            }

            throw new RuntimeException('Workspace slug override is not available. Suggested alternative: ' . (string) $validation['suggested_slug']);
        }

        return self::suggest_workspace_slug($company_name, $company_id);
    }

    private static function workspace_slug_validation_result(string $candidate, int $exclude_company_id = 0): array
    {
        $slug = self::normalize_workspace_slug($candidate);
        $suggested_slug = self::suggest_workspace_slug($candidate, $exclude_company_id);

        if ($slug === '') {
            return [
                'valid' => false,
                'code' => 'workspace_slug_required',
                'message' => 'Workspace slug is required.',
                'slug' => '',
                'suggested_slug' => $suggested_slug,
                'reserved' => false,
                'available' => false,
            ];
        }

        if (strlen($slug) < 3) {
            return [
                'valid' => false,
                'code' => 'workspace_slug_too_short',
                'message' => 'Workspace slug must be at least 3 characters.',
                'slug' => $slug,
                'suggested_slug' => $suggested_slug,
                'reserved' => false,
                'available' => false,
            ];
        }

        if (self::workspace_slug_is_reserved($slug)) {
            return [
                'valid' => false,
                'code' => 'workspace_slug_reserved',
                'message' => 'This workspace slug is reserved.',
                'slug' => $slug,
                'suggested_slug' => $suggested_slug,
                'reserved' => true,
                'available' => false,
            ];
        }

        if (self::workspace_slug_exists($slug, $exclude_company_id)) {
            return [
                'valid' => false,
                'code' => 'workspace_slug_taken',
                'message' => 'This workspace slug is already in use.',
                'slug' => $slug,
                'suggested_slug' => $suggested_slug,
                'reserved' => false,
                'available' => false,
            ];
        }

        return [
            'valid' => true,
            'code' => 'workspace_slug_available',
            'message' => 'Workspace slug is available.',
            'slug' => $slug,
            'suggested_slug' => $slug,
            'reserved' => false,
            'available' => true,
        ];
    }

    private static function suggest_workspace_slug(string $value, int $exclude_company_id = 0): string
    {
        $base = self::normalize_workspace_slug($value);
        if ($base === '') {
            $base = 'workspace';
        }

        if (strlen($base) < 3) {
            $base .= '-workspace';
        }

        if (self::workspace_slug_is_reserved($base)) {
            $base .= '-workspace';
        }

        $base = self::trim_workspace_slug($base);
        $candidate = $base;
        $suffix = 2;

        while (self::workspace_slug_is_reserved($candidate) || self::workspace_slug_exists($candidate, $exclude_company_id)) {
            $suffix_text = '-' . $suffix;
            $candidate = self::trim_workspace_slug($base, strlen($suffix_text)) . $suffix_text;
            $suffix++;
        }

        return $candidate;
    }

    private static function normalize_workspace_slug(string $value): string
    {
        $slug = sanitize_title($value);
        $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower((string) $slug));
        $slug = preg_replace('/-+/', '-', (string) $slug);

        return self::trim_workspace_slug((string) $slug);
    }

    private static function trim_workspace_slug(string $slug, int $reserved_suffix_length = 0): string
    {
        $max_length = max(3, 63 - $reserved_suffix_length);
        $slug = trim(substr($slug, 0, $max_length), '-');

        return $slug;
    }

    private static function workspace_slug_is_reserved(string $slug): bool
    {
        $reserved = apply_filters(
            'abit_saas_auth_reserved_workspace_slugs',
            [
                'abit',
                'abit-ai',
                'abitai',
                'admin',
                'api',
                'app',
                'auth',
                'billing',
                'blog',
                'cdn',
                'dashboard',
                'docs',
                'erp',
                'help',
                'login',
                'logout',
                'mail',
                'marketing',
                'new',
                'onboarding',
                'pricing',
                'root',
                'signup',
                'static',
                'status',
                'support',
                'system',
                'test',
                'www',
            ]
        );

        $reserved = array_map([__CLASS__, 'normalize_workspace_slug'], array_map('strval', (array) $reserved));

        return in_array($slug, $reserved, true);
    }

    private static function workspace_slug_exists(string $slug, int $exclude_company_id = 0): bool
    {
        global $wpdb;

        if ($exclude_company_id > 0) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT id FROM ' . self::table('workspaces') . ' WHERE workspace_key = %s AND company_id <> %d LIMIT 1',
                    $slug,
                    $exclude_company_id
                )
            );
        } else {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT id FROM ' . self::table('workspaces') . ' WHERE workspace_key = %s LIMIT 1',
                    $slug
                )
            );
        }

        return !empty($existing);
    }

    private static function format_workspace(array $workspace): array
    {
        return [
            'id' => (int) $workspace['id'],
            'company_id' => (int) $workspace['company_id'],
            'key' => (string) $workspace['workspace_key'],
            'display_name' => (string) $workspace['display_name'],
            'status' => (string) $workspace['status'],
            'created_by_user_id' => (int) $workspace['created_by_user_id'],
            'slug_overridden' => !empty($workspace['workspace_slug_overridden']),
            'slug_override_source' => self::nullable_key($workspace['workspace_slug_override_source'] ?? null),
            'created_at' => self::nullable_datetime($workspace['created_at'] ?? null),
            'updated_at' => self::nullable_datetime($workspace['updated_at'] ?? null),
        ];
    }

    private static function format_workspace_membership(array $membership): array
    {
        return [
            'id' => (int) $membership['id'],
            'workspace_id' => (int) $membership['workspace_id'],
            'company_id' => (int) $membership['company_id'],
            'user_id' => (int) $membership['user_id'],
            'access_request_id' => (int) $membership['access_request_id'],
            'role' => (string) $membership['role'],
            'status' => (string) $membership['status'],
            'joined_at' => self::nullable_datetime($membership['joined_at'] ?? null),
        ];
    }

    private static function provisioning_payload(WP_User $user, ?array $access_request, array $account_state, array $onboarding): array
    {
        $eligibility = self::provisioning_eligibility($user, $access_request, $account_state, $onboarding);
        $request = is_array($access_request) ? self::provisioning_request_for_access_request((int) $access_request['id']) : null;

        return [
            'eligible' => $eligibility['eligible'],
            'missing_requirements' => $eligibility['missing_requirements'],
            'request' => is_array($request) ? self::format_provisioning_request($request) : null,
        ];
    }

    private static function provisioning_eligibility(WP_User $user, ?array $access_request, array $account_state, array $onboarding): array
    {
        $missing = [];

        if ($account_state['locked']) {
            $missing[] = 'account_available';
        }

        if (!is_array($access_request) || empty($access_request['id']) || empty($access_request['company_id'])) {
            $missing[] = 'access_request';
            $missing[] = 'company_profile';
        }

        if (!$account_state['email_verified']) {
            $missing[] = 'email_verified';
        }

        if (empty($onboarding['required_fields_complete'])) {
            $missing[] = 'required_onboarding_fields';
        }

        if ($account_state['review_status'] === self::REVIEW_STATUS_REJECTED) {
            $missing[] = 'request_not_rejected';
        }

        $missing = array_values(array_unique($missing));
        if (empty($missing)) {
            return [
                'eligible' => true,
                'code' => 'eligible',
                'message' => 'Provisioning can be requested.',
                'missing_requirements' => [],
                'status' => 200,
            ];
        }

        $status = in_array('email_verified', $missing, true) ? 403 : 422;
        if (in_array('account_available', $missing, true) || in_array('request_not_rejected', $missing, true)) {
            $status = 423;
        }

        return [
            'eligible' => false,
            'code' => 'provisioning_not_allowed',
            'message' => 'Provisioning can only be requested after email verification and required company onboarding are complete.',
            'missing_requirements' => $missing,
            'status' => $status,
        ];
    }

    private static function provisioning_request_for_access_request(int $access_request_id): ?array
    {
        global $wpdb;

        $request = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, access_request_id, user_id, company_id, request_status, requested_at, processed_at, erp_tenant_reference, notes, created_at, updated_at FROM ' . self::table('provisioning_requests') . ' WHERE access_request_id = %d LIMIT 1',
                $access_request_id
            ),
            ARRAY_A
        );

        return is_array($request) ? $request : null;
    }

    private static function ensure_provisioning_request(array $access_request, WP_User $user): array
    {
        $existing = self::provisioning_request_for_access_request((int) $access_request['id']);
        if (is_array($existing)) {
            $existing['_created'] = false;
            return $existing;
        }

        global $wpdb;
        $now = current_time('mysql', true);
        $inserted = $wpdb->insert(
            self::table('provisioning_requests'),
            [
                'access_request_id' => (int) $access_request['id'],
                'user_id' => (int) $user->ID,
                'company_id' => (int) $access_request['company_id'],
                'request_status' => self::PROVISIONING_STATUS_REQUESTED,
                'requested_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        if (false === $inserted || empty($wpdb->insert_id)) {
            throw new RuntimeException('Provisioning request could not be created.');
        }

        $created = self::provisioning_request_for_access_request((int) $access_request['id']);
        if (!is_array($created)) {
            throw new RuntimeException('Provisioning request could not be loaded.');
        }

        $created['_created'] = true;
        return $created;
    }

    private static function format_provisioning_request(array $request): array
    {
        return [
            'id' => (int) $request['id'],
            'access_request_id' => (int) $request['access_request_id'],
            'company_id' => (int) $request['company_id'],
            'status' => (string) $request['request_status'],
            'requested_at' => self::nullable_datetime($request['requested_at'] ?? null),
            'processed_at' => self::nullable_datetime($request['processed_at'] ?? null),
            'erp_tenant_reference' => self::first_non_empty([$request['erp_tenant_reference'] ?? null]),
        ];
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

    private static function consume_verification_token(int $token_id, string $now): void
    {
        global $wpdb;
        $updated = $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . self::table('email_verification_tokens') . ' SET consumed_at = %s WHERE id = %d AND consumed_at IS NULL',
                $now,
                $token_id
            )
        );

        if (false === $updated) {
            throw new RuntimeException('Verification token could not be consumed.');
        }
    }

    private static function mark_access_request_verified(array $access_request, string $now): void
    {
        global $wpdb;

        $updated = $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . self::table('access_requests') . ' SET review_status = %s, email_verified_at = %s, updated_at = %s WHERE id = %d AND email_verified_at IS NULL',
                self::REVIEW_STATUS_ONBOARDING_REQUIRED,
                $now,
                $now,
                (int) $access_request['id']
            )
        );

        if (false === $updated) {
            throw new RuntimeException('Access request could not be verified.');
        }

        update_user_meta((int) $access_request['user_id'], 'abit_saas_email_verified_at', $now);
        update_user_meta((int) $access_request['user_id'], 'abit_saas_review_status', self::REVIEW_STATUS_ONBOARDING_REQUIRED);
        update_user_meta((int) $access_request['user_id'], 'abitai_email_verified_at', $now);
        update_user_meta((int) $access_request['user_id'], 'abitai_access_request_status', self::REVIEW_STATUS_ONBOARDING_REQUIRED);
    }

    private static function send_verification_email(string $email, string $name, string $token, array $context = []): bool
    {
        $verification_url = add_query_arg(
            [
                'token' => $token,
                'email' => $email,
            ],
            home_url('/auth/verify/')
        );

        $subject = apply_filters('abit_saas_auth_verification_subject', 'Verify your abit.ai access request');
        $html_message = self::verification_email_html(self::email_display_name($name), $verification_url);
        $headers = self::transactional_email_headers();

        $sent = (bool) wp_mail($email, $subject, $html_message, $headers);
        self::log_email_delivery(
            array_merge(
                $context,
                [
                    'message_type' => 'email_verification',
                    'recipient_domain_hash' => self::email_domain_hash($email),
                    'delivery_result' => $sent ? 'accepted' : 'failure',
                    'delivery_channel' => 'email',
                    'email_delivery_provider' => self::email_delivery_provider(),
                    'sender_email' => self::transactional_sender_email(),
                    'branded_link_path' => '/auth/verify/',
                    'has_plaintext_fallback' => true,
                    'subject_key' => 'verification_access_request',
                ]
            )
        );

        return $sent;
    }

    private static function transactional_email_headers(): array
    {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::transactional_sender_name() . ' <' . self::transactional_sender_email() . '>',
        ];

        $reply_to = self::transactional_reply_to_email();
        if ($reply_to !== '') {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        return $headers;
    }

    private static function verification_email_html(string $name, string $verification_url): string
    {
        $safe_name = esc_html($name);
        $safe_url = esc_url($verification_url);

        return '<!doctype html><html><body style="margin:0;background:#f6f7f9;color:#17202a;font-family:Arial,Helvetica,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;padding:32px 20px;">'
            . '<div style="background:#ffffff;border:1px solid #e1e5ea;border-radius:8px;padding:28px;">'
            . '<div style="font-size:20px;font-weight:700;margin-bottom:24px;">abit.ai</div>'
            . '<h1 style="font-size:24px;line-height:1.25;margin:0 0 16px;">Verify your email to continue</h1>'
            . '<p style="font-size:15px;line-height:1.6;margin:0 0 16px;">Hi ' . $safe_name . ',</p>'
            . '<p style="font-size:15px;line-height:1.6;margin:0 0 24px;">Confirm your business email address to continue your abit.ai access request.</p>'
            . '<p style="margin:0 0 24px;"><a href="' . $safe_url . '" style="display:inline-block;background:#17202a;color:#ffffff;text-decoration:none;border-radius:6px;padding:12px 18px;font-size:15px;font-weight:700;">Verify email</a></p>'
            . '<p style="font-size:13px;line-height:1.6;margin:0 0 16px;color:#4b5563;">This link expires in 24 hours. If the button does not work, copy and paste this link into your browser:</p>'
            . '<p style="font-size:13px;line-height:1.6;margin:0;word-break:break-all;color:#374151;">' . $safe_url . '</p>'
            . '</div>'
            . '<p style="font-size:12px;line-height:1.5;margin:16px 0 0;color:#6b7280;">If you did not request access to abit.ai, you can ignore this email.</p>'
            . '</div></body></html>';
    }

    private static function log_email_delivery(array $data): void
    {
        self::record_email_observability_event($data);

        $event_data = array_merge(
            [
                'delivery_channel' => 'email',
                'email_delivery_provider' => self::email_delivery_provider(),
                'sender_domain' => self::APPROVED_SENDER_DOMAIN,
                'logged_at' => current_time('mysql', true),
            ],
            $data
        );

        $context = [
            'actor_type' => 'system',
            'entity_type' => (string) ($data['message_type'] ?? 'email'),
            'event_data' => $event_data,
        ];

        foreach (['actor_user_id', 'access_request_id', 'company_id'] as $key) {
            if (isset($data[$key])) {
                $context[$key] = (int) $data[$key];
            }
        }

        if (isset($data['email_verification_token_id'])) {
            $context['entity_id'] = (int) $data['email_verification_token_id'];
        }

        self::audit_event('auth_email_delivery_attempted', $context);

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('abit.ai auth email delivery: ' . wp_json_encode($event_data));
        }
    }

    private static function record_email_observability_event(array $data): void
    {
        global $wpdb;

        $event_type = sanitize_key((string) ($data['event_type'] ?? 'delivery_attempted'));
        $delivery_status = self::email_delivery_status((string) ($data['delivery_status'] ?? $data['delivery_result'] ?? 'unknown'));
        $message_type = sanitize_key((string) ($data['message_type'] ?? 'email'));
        $now = current_time('mysql', true);
        $access_request_id = isset($data['access_request_id']) ? (int) $data['access_request_id'] : null;

        $wpdb->insert(
            self::table('email_delivery_events'),
            [
                'access_request_id' => $access_request_id,
                'user_id' => isset($data['actor_user_id']) ? (int) $data['actor_user_id'] : null,
                'company_id' => isset($data['company_id']) ? (int) $data['company_id'] : null,
                'email_verification_token_id' => isset($data['email_verification_token_id']) ? (int) $data['email_verification_token_id'] : null,
                'message_type' => $message_type,
                'event_type' => $event_type,
                'delivery_status' => $delivery_status,
                'delivery_channel' => sanitize_key((string) ($data['delivery_channel'] ?? 'email')),
                'email_delivery_provider' => sanitize_key((string) ($data['email_delivery_provider'] ?? self::email_delivery_provider())),
                'recipient_domain_hash' => self::nullable_hash($data['recipient_domain_hash'] ?? null),
                'sender_domain' => self::APPROVED_SENDER_DOMAIN,
                'subject_key' => self::nullable_key($data['subject_key'] ?? null),
                'failure_reason_category' => self::nullable_key($data['failure_reason_category'] ?? null),
                'bounce_type' => self::nullable_key($data['bounce_type'] ?? null),
                'provider_event_id_hash' => !empty($data['provider_event_id']) ? self::hmac((string) $data['provider_event_id']) : null,
                'created_at' => $now,
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($access_request_id) {
            self::update_email_observability_summary($access_request_id, $event_type, $delivery_status, $now);
        }
    }

    private static function update_email_observability_summary(int $access_request_id, string $event_type, string $delivery_status, string $now): void
    {
        global $wpdb;

        $increments = [
            'sent' => 0,
            'failed' => 0,
            'bounced' => 0,
            'expired' => 0,
            'throttled' => 0,
        ];

        if (in_array($delivery_status, ['accepted', 'sent', 'prepared'], true)) {
            $increments['sent'] = 1;
        } elseif ($delivery_status === 'failed') {
            $increments['failed'] = 1;
        } elseif ($delivery_status === 'bounced') {
            $increments['bounced'] = 1;
        } elseif ($event_type === 'token_expired' || $delivery_status === 'token_expired') {
            $increments['expired'] = 1;
        } elseif ($event_type === 'resend_throttled' || $delivery_status === 'resend_throttled') {
            $increments['throttled'] = 1;
        }

        $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . self::table('access_requests') . ' SET email_delivery_status = %s, email_delivery_last_event = %s, email_delivery_last_event_at = %s, email_delivery_sent_count = email_delivery_sent_count + %d, email_delivery_failed_count = email_delivery_failed_count + %d, email_delivery_bounced_count = email_delivery_bounced_count + %d, email_token_expired_count = email_token_expired_count + %d, email_resend_throttled_count = email_resend_throttled_count + %d, updated_at = %s WHERE id = %d',
                $delivery_status,
                $event_type,
                $now,
                $increments['sent'],
                $increments['failed'],
                $increments['bounced'],
                $increments['expired'],
                $increments['throttled'],
                $now,
                $access_request_id
            )
        );
    }

    private static function email_delivery_status(string $status): string
    {
        $status = sanitize_key($status);
        $aliases = [
            'failure' => 'failed',
            'error' => 'failed',
            'expired' => 'token_expired',
            'throttled' => 'resend_throttled',
        ];

        return $aliases[$status] ?? ($status ?: 'unknown');
    }

    private static function transactional_sender_email(): string
    {
        $email = sanitize_email(self::env_value('ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL') ?: 'no-reply@abit.ai');
        if ($email === '' || !self::is_approved_sender_email($email)) {
            return 'no-reply@abit.ai';
        }

        return $email;
    }

    private static function transactional_sender_name(): string
    {
        $name = trim(self::env_value('ABIT_TRANSACTIONAL_MAIL_FROM_NAME') ?: 'abit.ai');
        return $name === '' ? 'abit.ai' : sanitize_text_field($name);
    }

    private static function transactional_reply_to_email(): string
    {
        $email = sanitize_email(self::env_value('ABIT_TRANSACTIONAL_MAIL_REPLY_TO') ?: 'support@abit.ai');
        return is_email($email) ? $email : '';
    }

    private static function email_delivery_provider(): string
    {
        $provider = sanitize_key(self::env_value('ABIT_TRANSACTIONAL_MAIL_PROVIDER') ?: 'wordpress');
        return $provider === '' ? 'wordpress' : $provider;
    }

    private static function email_display_name(string $name): string
    {
        $name = trim($name);
        return $name === '' ? 'there' : $name;
    }

    private static function email_domain_hash(string $email): string
    {
        $parts = explode('@', strtolower(trim($email)));
        $domain = count($parts) === 2 ? $parts[1] : '';
        return $domain === '' ? '' : self::hmac($domain);
    }

    private static function masked_email(string $email): string
    {
        $parts = explode('@', strtolower(trim($email)));
        if (count($parts) !== 2) {
            return '';
        }

        $local = $parts[0];
        $domain = $parts[1];
        $visible = substr($local, 0, 1);

        return $visible . str_repeat('*', max(2, strlen($local) - 1)) . '@' . $domain;
    }

    private static function is_approved_sender_email(string $email): bool
    {
        $parts = explode('@', strtolower($email));
        return count($parts) === 2 && $parts[1] === self::APPROVED_SENDER_DOMAIN;
    }

    private static function env_value(string $name): string
    {
        if (defined($name)) {
            return trim((string) constant($name));
        }

        $value = getenv($name);
        return false === $value ? '' : trim((string) $value);
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
