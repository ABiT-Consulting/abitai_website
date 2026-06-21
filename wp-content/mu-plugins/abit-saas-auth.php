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
    private const SCHEMA_VERSION = '2026-06-21.02';
    private const REST_NAMESPACE = 'abit-ai/v1';
    private const APPROVED_SENDER_DOMAIN = 'abit.ai';
    private const REVIEW_STATUS_PENDING_EMAIL = 'pending_email_verification';
    private const REVIEW_STATUS_ONBOARDING_REQUIRED = 'onboarding_required';
    private const REVIEW_STATUS_PENDING_ADMIN_REVIEW = 'pending_admin_review';
    private const REVIEW_STATUS_APPROVED = 'approved_for_mvp_access';
    private const REVIEW_STATUS_REJECTED = 'rejected';
    private const REVIEW_STATUS_MORE_INFORMATION_REQUESTED = 'more_information_requested';
    private const REVIEW_STATUS_ON_HOLD = 'on_hold';
    private const PROVISIONING_STATUS_REQUESTED = 'requested';
    private const PROVISIONING_READINESS_READY = 'ready';
    private const PROVISIONING_READINESS_NOT_READY = 'not_ready';
    private const PROVISIONING_READINESS_BLOCKED = 'blocked';
    private const WORKSPACE_STATUS_ACTIVE = 'active';
    private const WORKSPACE_MEMBER_STATUS_ACTIVE = 'active';
    private const WORKSPACE_MEMBER_ROLE_OWNER = 'owner';
    private const WORKSPACE_MEMBER_ROLE_MEMBER = 'member';
    private const CONSENT_RETENTION_RULE = 'active_plus_7_years_after_closure';
    private const HIGH_RESEND_SENT_THRESHOLD = 4;
    private const HIGH_RESEND_THROTTLED_THRESHOLD = 2;
    private const HIGH_FAILED_LOGIN_THRESHOLD = 5;
    private const SHARED_IP_REQUEST_THRESHOLD = 3;
    private const SHARED_DOMAIN_REQUEST_THRESHOLD = 3;
    private const AUTH_LOCKOUT_META_KEY = 'abit_saas_auth_locked_until';
    private const SIGNUP_RISK_CHALLENGE_THRESHOLD = 45;
    private const SIGNUP_RISK_HOLD_THRESHOLD = 80;
    private const SIGNUP_IP_VELOCITY_THRESHOLD = 5;

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
        add_filter('wp_hash_password_algorithm', [__CLASS__, 'password_hash_algorithm'], 10, 1);
        add_filter('wp_hash_password_options', [__CLASS__, 'password_hash_options'], 10, 2);
        add_action('login_form_lostpassword', [__CLASS__, 'maybe_accept_unknown_lost_password_request'], 0);
        add_action('login_form_retrievepassword', [__CLASS__, 'maybe_accept_unknown_lost_password_request'], 0);
        add_filter('login_errors', [__CLASS__, 'generic_login_errors']);
        add_filter('authenticate', [__CLASS__, 'rate_limit_wordpress_login'], 5, 3);
        add_action('wp_login_failed', [__CLASS__, 'record_wordpress_login_failure'], 10, 2);
        add_action('lostpassword_post', [__CLASS__, 'rate_limit_lost_password_request'], 10, 2);
        add_action('validate_password_reset', [__CLASS__, 'validate_password_reset_policy'], 10, 2);
        add_action('user_profile_update_errors', [__CLASS__, 'validate_user_profile_password_policy'], 10, 3);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('template_redirect', [__CLASS__, 'handle_pretty_api_route'], 0);
        add_action('admin_menu', [__CLASS__, 'register_email_observability_page']);
        add_action('admin_post_abit_saas_auth_admin_decision', [__CLASS__, 'handle_admin_decision_post']);
        add_action('abit_saas_auth_email_bounced', [__CLASS__, 'record_email_bounce'], 10, 1);
    }

    public static function password_hash_algorithm($algorithm)
    {
        if (self::argon2id_available()) {
            return PASSWORD_ARGON2ID;
        }

        return defined('PASSWORD_BCRYPT') ? PASSWORD_BCRYPT : $algorithm;
    }

    public static function password_hash_options(array $options, $algorithm): array
    {
        if (defined('PASSWORD_ARGON2ID') && $algorithm === PASSWORD_ARGON2ID) {
            $minimum_memory_cost = defined('PASSWORD_ARGON2_DEFAULT_MEMORY_COST') ? PASSWORD_ARGON2_DEFAULT_MEMORY_COST : 65536;
            $minimum_time_cost = defined('PASSWORD_ARGON2_DEFAULT_TIME_COST') ? max(4, PASSWORD_ARGON2_DEFAULT_TIME_COST) : 4;
            $minimum_threads = defined('PASSWORD_ARGON2_DEFAULT_THREADS') ? PASSWORD_ARGON2_DEFAULT_THREADS : 1;
            $options = array_merge(
                ['memory_cost' => $minimum_memory_cost, 'time_cost' => $minimum_time_cost, 'threads' => $minimum_threads],
                $options
            );
            $options['memory_cost'] = max($minimum_memory_cost, (int) $options['memory_cost']);
            $options['time_cost'] = max($minimum_time_cost, (int) $options['time_cost']);
            $options['threads'] = max($minimum_threads, (int) $options['threads']);

            return $options;
        }

        if (defined('PASSWORD_BCRYPT') && $algorithm === PASSWORD_BCRYPT) {
            $options = array_merge(['cost' => 12], $options);
            $options['cost'] = max(12, (int) $options['cost']);

            return $options;
        }

        return $options;
    }

    public static function maybe_accept_unknown_lost_password_request(): void
    {
        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
            return;
        }

        $identifier = isset($_POST['user_login']) && is_string($_POST['user_login'])
            ? trim((string) wp_unslash($_POST['user_login']))
            : '';
        if ($identifier === '') {
            return;
        }

        $user = self::user_for_login_identifier(strtolower($identifier));
        if ($user instanceof WP_User) {
            return;
        }

        self::maybe_install_schema();
        $limited = self::check_auth_rate_limit('forgot_password', strtolower($identifier), 0);
        if (!empty($limited['limited'])) {
            self::record_auth_rate_limit_event('forgot_password', 'throttled_unknown', strtolower($identifier), 0, null, null, (int) $limited['retry_after']);
            self::audit_auth_rate_limit('forgot_password', 'password_reset_request', (int) $limited['retry_after'], 0);
            self::redirect_to_lost_password_confirmation();
        }

        self::record_auth_rate_limit_event('forgot_password', 'accepted_unknown', strtolower($identifier), 0);
        self::audit_event(
            'auth_password_reset_requested',
            [
                'actor_type' => 'anonymous',
                'entity_type' => 'auth',
                'event_data' => [
                    'reset_request_result' => 'accepted_generic_response',
                    'email_domain_hash' => self::email_domain_hash($identifier),
                    'reset_delivery_channel' => 'email',
                    'reset_token_created' => false,
                    'password_reset_flow_version' => 'wordpress_native_v1',
                ],
            ]
        );

        self::redirect_to_lost_password_confirmation();
    }

    public static function generic_login_errors(string $errors): string
    {
        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : 'login';
        if (!in_array($action, ['login', ''], true)) {
            return $errors;
        }

        return '<p>' . esc_html__('We could not sign you in with those details. Check your email and password, then try again.', 'abit-saas-auth') . '</p>';
    }

    private static function redirect_to_lost_password_confirmation(): void
    {
        $redirect_to = !empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])
            ? (string) wp_unslash($_REQUEST['redirect_to'])
            : 'wp-login.php?checkemail=confirm';
        wp_safe_redirect($redirect_to);
        exit;
    }

    public static function validate_password_reset_policy(WP_Error $errors, WP_User $user): void
    {
        self::maybe_install_schema();

        $limited = self::check_auth_rate_limit('reset_password', (string) $user->user_email, (int) $user->ID);
        if (!empty($limited['limited'])) {
            self::record_auth_rate_limit_event('reset_password', 'throttled', (string) $user->user_email, (int) $user->ID, null, null, (int) $limited['retry_after']);
            self::audit_auth_rate_limit('reset_password', 'password_reset_submit', (int) $limited['retry_after'], (int) $user->ID);
            $errors->add('abit_saas_auth_rate_limited', self::rate_limited_message((int) $limited['retry_after']));
            return;
        }

        self::record_auth_rate_limit_event('reset_password', 'allowed', (string) $user->user_email, (int) $user->ID);

        $password = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : '';
        if ($password === '') {
            return;
        }

        $password_error = self::password_error($password);
        if ($password_error) {
            $errors->add('abit_saas_password_policy', $password_error);
        }
    }

    public static function rate_limit_wordpress_login($user, string $username, string $password)
    {
        if ($user instanceof WP_User || is_wp_error($user)) {
            return $user;
        }

        $identifier = strtolower(trim($username));
        if ($identifier === '' || trim($password) === '') {
            return $user;
        }

        self::maybe_install_schema();

        $matched_user = self::user_for_login_identifier($identifier);
        if ($matched_user instanceof WP_User && self::is_user_locked($matched_user)) {
            return new WP_Error('account_locked', __('This account cannot sign in right now. Contact support for help.', 'abit-saas-auth'));
        }

        $limited = self::check_auth_rate_limit('login', $identifier, $matched_user instanceof WP_User ? (int) $matched_user->ID : 0);
        if (!empty($limited['limited'])) {
            $user_id = $matched_user instanceof WP_User ? (int) $matched_user->ID : 0;
            self::record_auth_rate_limit_event('login', 'throttled', $identifier, $user_id, null, null, (int) $limited['retry_after']);
            self::audit_auth_rate_limit('login', 'wordpress_login', (int) $limited['retry_after'], $user_id);

            return new WP_Error('rate_limited', self::rate_limited_message((int) $limited['retry_after']));
        }

        self::record_auth_rate_limit_event('login', 'allowed', $identifier, $matched_user instanceof WP_User ? (int) $matched_user->ID : 0);
        return $user;
    }

    public static function record_wordpress_login_failure(string $username, WP_Error $error): void
    {
        $code = $error->get_error_code();
        if (in_array($code, ['rate_limited', 'account_locked', 'empty_username', 'empty_password'], true)) {
            return;
        }

        self::maybe_install_schema();

        $identifier = strtolower(trim($username));
        $user = self::user_for_login_identifier($identifier);
        $user_id = $user instanceof WP_User ? (int) $user->ID : 0;
        $access_request = $user instanceof WP_User ? self::access_request_for_user($user) : self::access_request_for_email($identifier);
        $access_request_id = is_array($access_request) ? (int) $access_request['id'] : null;
        $company_id = is_array($access_request) ? (int) $access_request['company_id'] : null;

        self::record_failed_login_attempt($identifier);
        self::record_auth_rate_limit_event('login_failed', 'failed', $identifier, $user_id, $access_request_id, $company_id);
        if ($user instanceof WP_User) {
            self::maybe_lock_user_after_failed_login($user, $identifier, $access_request);
        }
    }

    public static function rate_limit_lost_password_request(WP_Error $errors, $user_data = null): void
    {
        self::maybe_install_schema();

        $identifier = isset($_POST['user_login']) ? strtolower(trim((string) wp_unslash($_POST['user_login']))) : '';
        $user_id = $user_data instanceof WP_User ? (int) $user_data->ID : 0;
        if ($identifier === '' && $user_data instanceof WP_User) {
            $identifier = (string) $user_data->user_email;
        }

        $limited = self::check_auth_rate_limit('forgot_password', $identifier, $user_id);
        if (!empty($limited['limited'])) {
            self::record_auth_rate_limit_event('forgot_password', 'throttled', $identifier, $user_id, null, null, (int) $limited['retry_after']);
            self::audit_auth_rate_limit('forgot_password', 'password_reset_request', (int) $limited['retry_after'], $user_id);
            $errors->add('abit_saas_auth_rate_limited', self::rate_limited_message((int) $limited['retry_after']));
            return;
        }

        self::record_auth_rate_limit_event('forgot_password', 'allowed', $identifier, $user_id);
    }

    public static function validate_user_profile_password_policy(WP_Error $errors, bool $update, $user): void
    {
        $password = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : '';
        if ($password === '') {
            return;
        }

        $password_error = self::password_error($password);
        if ($password_error) {
            $errors->add('abit_saas_password_policy', $password_error);
        }
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

        add_management_page(
            'ABiT Signup Review',
            'ABiT Signup Review',
            'list_users',
            'abit-signup-review',
            [__CLASS__, 'render_signup_review_page']
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

    public static function render_signup_review_page(): void
    {
        if (!current_user_can('list_users')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'abit-saas-auth'));
        }

        self::maybe_install_schema();

        $detail_id = self::signup_review_detail_id_from_request();
        if ($detail_id > 0) {
            self::render_signup_review_detail_page($detail_id);
            return;
        }

        $filters = self::signup_review_filters_from_request();
        $rows = self::signup_review_rows($filters);
        $countries = self::signup_review_country_options();

        echo '<div class="wrap"><h1>ABiT Signup Review</h1>';
        echo '<p>Internal access request queue for admin review and lead qualification.</p>';
        self::render_signup_review_filters($filters, $countries);
        self::render_signup_review_table($rows);
        echo '</div>';
    }

    private static function signup_review_detail_id_from_request(): int
    {
        $request = wp_unslash($_GET);
        return isset($request['access_request_id']) ? max(0, (int) $request['access_request_id']) : 0;
    }

    private static function signup_review_filters_from_request(): array
    {
        $request = wp_unslash($_GET);
        $status = isset($request['review_status']) ? sanitize_key((string) $request['review_status']) : '';
        $country = isset($request['country_region']) ? strtoupper(sanitize_text_field((string) $request['country_region'])) : '';
        $module = isset($request['erp_module']) ? sanitize_key((string) $request['erp_module']) : '';
        $verification = isset($request['verification_state']) ? sanitize_key((string) $request['verification_state']) : '';

        if (!in_array($status, self::review_status_options(), true)) {
            $status = '';
        }

        if (!in_array($module, self::erp_module_options(), true)) {
            $module = '';
        }

        if (!in_array($verification, ['verified', 'unverified'], true)) {
            $verification = '';
        }

        return [
            'review_status' => $status,
            'country_region' => $country,
            'erp_module' => $module,
            'verification_state' => $verification,
        ];
    }

    private static function signup_review_rows(array $filters): array
    {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if ($filters['review_status'] !== '') {
            $where[] = 'ar.review_status = %s';
            $params[] = $filters['review_status'];
        }

        if ($filters['country_region'] !== '') {
            $where[] = 'ar.country_region = %s';
            $params[] = $filters['country_region'];
        }

        if ($filters['erp_module'] !== '') {
            $where[] = 'ar.erp_module_interest LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['erp_module']) . '%';
        }

        if ($filters['verification_state'] === 'verified') {
            $where[] = 'ar.email_verified_at IS NOT NULL';
        } elseif ($filters['verification_state'] === 'unverified') {
            $where[] = 'ar.email_verified_at IS NULL';
        }

        $sql = 'SELECT ar.id, ar.user_id, ar.admin_owner_user_id, ar.handoff_team, ar.handoff_priority, ar.handoff_next_action, ar.handoff_follow_up_date, ar.handoff_queue_status, ar.full_name, ar.business_email, ar.company_name, ar.country_region, ar.role, ar.company_size, ar.industry, ar.primary_workflow, ar.erp_module_interest, ar.persona, ar.review_status, ar.email_verified_at, ar.email_delivery_sent_count, ar.email_resend_throttled_count, ar.failed_login_count, ar.last_failed_login_at, ar.created_at, ar.updated_at, u.display_name AS owner_name, u.user_email AS owner_email, au.display_name AS admin_owner_name, au.user_email AS admin_owner_email, (SELECT COUNT(*) FROM ' . self::table('access_requests') . ' ar2 WHERE ar2.id <> ar.id AND LOWER(TRIM(ar2.company_name)) = LOWER(TRIM(ar.company_name))) AS duplicate_company_count, (SELECT COUNT(*) FROM ' . self::table('access_requests') . ' ar3 WHERE ar3.id <> ar.id AND SUBSTRING_INDEX(LOWER(ar3.business_email), \'@\', -1) = SUBSTRING_INDEX(LOWER(ar.business_email), \'@\', -1)) AS same_domain_request_count, (SELECT COUNT(DISTINCT c2.access_request_id) FROM ' . self::table('consent_audit_records') . ' c1 INNER JOIN ' . self::table('consent_audit_records') . ' c2 ON c2.ip_hash = c1.ip_hash AND c2.access_request_id <> c1.access_request_id WHERE c1.access_request_id = ar.id AND c1.ip_hash <> \'\') AS shared_ip_request_count FROM ' . self::table('access_requests') . ' ar LEFT JOIN ' . $wpdb->users . ' u ON u.ID = ar.user_id LEFT JOIN ' . $wpdb->users . ' au ON au.ID = ar.admin_owner_user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY ar.handoff_follow_up_date IS NULL ASC, ar.handoff_follow_up_date ASC, ar.updated_at DESC, ar.created_at DESC LIMIT 200';

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function signup_review_country_options(): array
    {
        global $wpdb;
        $countries = $wpdb->get_col('SELECT DISTINCT country_region FROM ' . self::table('access_requests') . " WHERE country_region <> '' ORDER BY country_region ASC");

        return is_array($countries) ? array_values(array_filter(array_map('strval', $countries))) : [];
    }

    private static function render_signup_review_filters(array $filters, array $countries): void
    {
        echo '<form method="get" class="abit-signup-review-filters" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin:16px 0;">';
        echo '<input type="hidden" name="page" value="abit-signup-review" />';
        self::render_signup_review_select('review_status', 'Status', $filters['review_status'], self::review_status_options(), true);
        self::render_signup_review_select('country_region', 'Country', $filters['country_region'], $countries, true);
        self::render_signup_review_select('erp_module', 'Module', $filters['erp_module'], self::erp_module_options(), true);
        self::render_signup_review_select('verification_state', 'Verification', $filters['verification_state'], ['verified', 'unverified'], true);
        submit_button('Filter', 'primary', '', false);
        echo '<a class="button" href="' . esc_url(admin_url('tools.php?page=abit-signup-review')) . '">Clear</a>';
        echo '</form>';
    }

    private static function render_signup_review_select(string $name, string $label, string $selected, array $options, bool $include_all): void
    {
        echo '<label for="' . esc_attr($name) . '"><span style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html($label) . '</span>';
        echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
        if ($include_all) {
            echo '<option value="">All</option>';
        }

        foreach ($options as $option) {
            $option = (string) $option;
            echo '<option value="' . esc_attr($option) . '"' . selected($selected, $option, false) . '>' . esc_html(self::signup_review_label($option)) . '</option>';
        }

        echo '</select></label>';
    }

    private static function render_signup_review_table(array $rows): void
    {
        echo '<table class="widefat fixed striped"><thead><tr>';
        foreach (['Request', 'User', 'Company', 'Verification', 'Industry', 'Size', 'Country', 'Modules', 'Risk', 'Handoff', 'Status'] as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="11">No signup access requests match these filters.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $modules = self::normalize_erp_module_interests(self::decode_list_value($row['erp_module_interest'] ?? null));
                $owner = (int) ($row['admin_owner_user_id'] ?? 0) > 0
                    ? self::first_non_empty([$row['admin_owner_name'] ?? null, $row['admin_owner_email'] ?? null, 'Admin #' . (int) $row['admin_owner_user_id']])
                    : self::first_non_empty([$row['owner_name'] ?? null, $row['owner_email'] ?? null, 'User #' . (int) ($row['user_id'] ?? 0)]);

                echo '<tr>';
                $detail_url = add_query_arg(
                    [
                        'page' => 'abit-signup-review',
                        'access_request_id' => (int) $row['id'],
                    ],
                    admin_url('tools.php')
                );

                echo '<td><a href="' . esc_url($detail_url) . '">' . esc_html('#' . (int) $row['id']) . '</a><br><small>' . esc_html((string) ($row['created_at'] ?? '')) . '</small></td>';
                echo '<td>' . esc_html((string) ($row['full_name'] ?? '')) . '<br><small>' . esc_html(self::masked_email((string) ($row['business_email'] ?? ''))) . '</small></td>';
                echo '<td>' . esc_html((string) ($row['company_name'] ?? '')) . '<br><small>' . esc_html((string) ($row['role'] ?? '')) . '</small></td>';
                echo '<td>' . esc_html(empty($row['email_verified_at']) ? 'Unverified' : 'Verified') . '<br><small>' . esc_html((string) ($row['email_verified_at'] ?? '')) . '</small></td>';
                echo '<td>' . esc_html(self::signup_review_label((string) ($row['industry'] ?? ''))) . '</td>';
                echo '<td>' . esc_html(self::signup_review_label((string) ($row['company_size'] ?? ''))) . '</td>';
                echo '<td>' . esc_html((string) ($row['country_region'] ?? '')) . '</td>';
                echo '<td>' . esc_html(self::signup_review_module_summary($modules)) . '</td>';
                echo '<td>' . esc_html(self::signup_review_risk_summary($row, $modules)) . '</td>';
                echo '<td>' . esc_html(self::signup_review_handoff_summary($row, $owner)) . '</td>';
                echo '<td>' . esc_html(self::signup_review_label((string) ($row['review_status'] ?? ''))) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    private static function render_signup_review_detail_page(int $access_request_id): void
    {
        $access_request = self::signup_review_detail($access_request_id);
        $back_url = admin_url('tools.php?page=abit-signup-review');

        echo '<div class="wrap"><h1>ABiT Customer Profile</h1>';
        echo '<p><a class="button" href="' . esc_url($back_url) . '">Back to signup review</a></p>';
        self::render_signup_review_admin_notice();

        if (!is_array($access_request)) {
            echo '<div class="notice notice-error"><p>Access request not found.</p></div></div>';
            return;
        }

        $user = get_userdata((int) $access_request['user_id']);
        $modules = self::normalize_erp_module_interests(self::decode_list_value($access_request['erp_module_interest'] ?? null));
        $module_mapping = self::erp_onboarding_module_mapping($modules);
        $account_state = $user instanceof WP_User
            ? self::account_state_from_access_request($user, $access_request)
            : [
                'access_request_id' => (int) $access_request['id'],
                'company_id' => (int) $access_request['company_id'],
                'review_status' => (string) $access_request['review_status'],
                'email_verified' => !empty($access_request['email_verified_at']),
                'state' => 'review_pending',
                'route' => 'review_pending',
                'locked' => false,
            ];
        $onboarding = $user instanceof WP_User ? self::onboarding_payload($user, $access_request, $account_state) : [];
        $provisioning = $user instanceof WP_User ? self::provisioning_payload($user, $access_request, $account_state, $onboarding) : null;
        $workspace = $user instanceof WP_User ? self::workspace_payload($user, $access_request) : null;
        $consent = self::signup_review_consent_audit((int) $access_request['id']);
        $email_events = self::signup_review_email_events((int) $access_request['id']);
        $provisioning_request = self::provisioning_request_for_access_request((int) $access_request['id']);
        $risk_indicators = self::signup_review_risk_indicators($access_request, $modules);

        echo '<p>Internal customer context for admin review. Passwords, raw tokens, cookies, sessions, and verification token hashes are not displayed.</p>';
        echo '<style>.abit-profile-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px}.abit-profile-panel{background:#fff;border:1px solid #c3c4c7;padding:16px}.abit-profile-panel h2{margin-top:0}.abit-profile-panel dl{display:grid;grid-template-columns:150px 1fr;gap:8px 12px;margin:0}.abit-profile-panel dt{font-weight:600}.abit-profile-panel dd{margin:0;overflow-wrap:anywhere}.abit-profile-list{margin:0}.abit-profile-list li{margin-bottom:6px}.abit-readiness-pass{color:#008a20;font-weight:600}.abit-readiness-fail{color:#b32d2e;font-weight:600}.abit-profile-notes{white-space:pre-wrap}</style>';

        echo '<div class="abit-profile-grid">';
        self::render_signup_profile_panel(
            'Signup Data',
            [
                'Request ID' => '#' . (int) $access_request['id'],
                'Review status' => self::signup_review_label((string) $access_request['review_status']),
                'Queue status' => self::signup_review_label((string) ($access_request['handoff_queue_status'] ?? '')),
                'Admin owner' => self::signup_review_admin_owner_summary($access_request),
                'Handoff team' => self::signup_review_label((string) ($access_request['handoff_team'] ?? '')),
                'Priority' => self::signup_review_label((string) ($access_request['handoff_priority'] ?? '')),
                'Next action' => (string) ($access_request['handoff_next_action'] ?? ''),
                'Follow-up date' => (string) ($access_request['handoff_follow_up_date'] ?? ''),
                'Source campaign' => (string) ($access_request['source_campaign'] ?? ''),
                'Last decision' => self::signup_review_label((string) ($access_request['admin_decision'] ?? '')),
                'Decision reason' => (string) ($access_request['admin_decision_reason'] ?? ''),
                'Reviewed by' => self::signup_review_reviewer_summary($access_request),
                'Reviewed at' => self::nullable_datetime($access_request['admin_reviewed_at'] ?? null),
                'Signup created' => self::nullable_datetime($access_request['created_at'] ?? null),
                'Last updated' => self::nullable_datetime($access_request['updated_at'] ?? null),
                'Full name' => (string) $access_request['full_name'],
                'Business email' => (string) $access_request['business_email'],
                'Email verification' => !empty($access_request['email_verified_at']) ? 'Verified at ' . (string) $access_request['email_verified_at'] : 'Unverified',
                'WordPress user' => self::signup_review_user_summary($access_request, $user),
            ]
        );
        self::render_signup_profile_panel(
            'Company Profile',
            [
                'Company' => self::first_non_empty([$access_request['company_record_name'] ?? null, $access_request['company_name'] ?? null]),
                'Company ID' => (int) $access_request['company_id'] > 0 ? '#' . (int) $access_request['company_id'] : '',
                'Country or region' => self::first_non_empty([$access_request['company_record_country_region'] ?? null, $access_request['country_region'] ?? null]),
                'Company status' => self::signup_review_label((string) ($access_request['company_record_status'] ?? '')),
                'Company size' => self::signup_review_label((string) ($access_request['company_size'] ?? '')),
                'Industry' => self::signup_review_label((string) ($access_request['industry'] ?? '')),
                'Role' => (string) ($access_request['role'] ?? ''),
                'Persona' => self::signup_review_label((string) ($onboarding['admin_review_fields']['persona'] ?? $access_request['persona'] ?? '')),
            ]
        );
        self::render_signup_profile_panel(
            'Module Interests',
            [
                'ERP modules' => self::signup_review_module_summary($modules),
                'Templates' => implode(', ', array_map('strval', $module_mapping['onboarding_templates'])),
                'Qualification tags' => implode(', ', array_map('strval', $module_mapping['qualification_tags'])),
                'Current system' => (string) ($access_request['current_system'] ?? ''),
                'Timeline' => self::signup_review_label((string) ($access_request['timeline'] ?? '')),
            ]
        );
        self::render_signup_profile_panel(
            'Risk Indicators',
            [
                'Risk level' => self::signup_review_risk_level($risk_indicators),
                'Indicators' => empty($risk_indicators) ? 'None' : implode('; ', $risk_indicators),
                'Email domain' => self::email_domain((string) ($access_request['business_email'] ?? '')),
                'Failed logins' => (int) ($access_request['failed_login_count'] ?? 0) > 0
                    ? (int) $access_request['failed_login_count'] . ' total; last at ' . self::nullable_datetime($access_request['last_failed_login_at'] ?? null)
                    : 'None recorded',
            ]
        );
        self::render_signup_profile_panel(
            'Provisioning Readiness',
            [
                'Eligible' => is_array($provisioning) && !empty($provisioning['eligible']) ? 'Yes' : 'No',
                'Missing requirements' => is_array($provisioning) ? implode(', ', array_map([__CLASS__, 'signup_review_label'], $provisioning['missing_requirements'])) : 'Request owner missing',
                'Provisioning request' => is_array($provisioning_request) ? '#' . (int) $provisioning_request['id'] . ' - ' . self::signup_review_label((string) $provisioning_request['request_status']) : 'Not requested',
                'Readiness override' => self::signup_review_label((string) ($access_request['provisioning_readiness_status'] ?? '')),
                'Readiness reason' => (string) ($access_request['provisioning_readiness_reason'] ?? ''),
                'Workspace' => is_array($workspace) ? self::signup_review_workspace_summary($workspace) : 'Unavailable',
                'Workspace slug override' => self::first_non_empty([$access_request['workspace_slug_override'] ?? null]) ?: 'None',
            ]
        );
        echo '</div>';

        self::render_signup_review_decision_panel($access_request);
        self::render_signup_review_workflow_panel($access_request);
        self::render_signup_review_readiness_panel($provisioning);
        self::render_signup_review_notes_panel($access_request, $provisioning_request);
        self::render_signup_review_audit_panel($access_request, $consent, $email_events, $provisioning_request, $workspace);
        echo '</div>';
    }

    private static function signup_review_detail(int $access_request_id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT ar.id, ar.user_id, ar.company_id, ar.full_name, ar.business_email, ar.company_name, ar.country_region, ar.intended_use_case, ar.role, ar.company_size, ar.industry, ar.primary_workflow, ar.erp_module_interest, ar.current_system, ar.timeline, ar.notes, ar.workspace_slug_override, ar.persona, ar.review_status, ar.admin_owner_user_id, ar.handoff_team, ar.handoff_priority, ar.handoff_next_action, ar.handoff_follow_up_date, ar.source_campaign, ar.handoff_notes, ar.handoff_queue_status, ar.admin_decision, ar.admin_decision_reason, ar.admin_reviewed_by, ar.admin_reviewed_at, ar.provisioning_readiness_status, ar.provisioning_readiness_reason, ar.provisioning_readiness_updated_by, ar.provisioning_readiness_updated_at, ar.email_verified_at, ar.terms_privacy_accepted_at, ar.terms_version, ar.privacy_version, ar.latest_consent_audit_record_id, ar.email_delivery_status, ar.email_delivery_last_event, ar.email_delivery_last_event_at, ar.email_delivery_sent_count, ar.email_delivery_failed_count, ar.email_delivery_bounced_count, ar.email_token_expired_count, ar.email_resend_throttled_count, ar.failed_login_count, ar.last_failed_login_at, ar.created_at, ar.updated_at, c.company_name AS company_record_name, c.country_region AS company_record_country_region, c.draft_status AS company_record_status, u.user_login, u.user_email AS owner_email, u.display_name AS owner_name, u.user_registered, u.user_status, au.display_name AS admin_owner_name, au.user_email AS admin_owner_email, ru.display_name AS reviewer_name, ru.user_email AS reviewer_email, (SELECT COUNT(*) FROM ' . self::table('access_requests') . ' ar2 WHERE ar2.id <> ar.id AND LOWER(TRIM(ar2.company_name)) = LOWER(TRIM(ar.company_name))) AS duplicate_company_count, (SELECT COUNT(*) FROM ' . self::table('access_requests') . ' ar3 WHERE ar3.id <> ar.id AND SUBSTRING_INDEX(LOWER(ar3.business_email), \'@\', -1) = SUBSTRING_INDEX(LOWER(ar.business_email), \'@\', -1)) AS same_domain_request_count, (SELECT COUNT(DISTINCT c2.access_request_id) FROM ' . self::table('consent_audit_records') . ' c1 INNER JOIN ' . self::table('consent_audit_records') . ' c2 ON c2.ip_hash = c1.ip_hash AND c2.access_request_id <> c1.access_request_id WHERE c1.access_request_id = ar.id AND c1.ip_hash <> \'\') AS shared_ip_request_count FROM ' . self::table('access_requests') . ' ar LEFT JOIN ' . self::table('companies') . ' c ON c.id = ar.company_id LEFT JOIN ' . $wpdb->users . ' u ON u.ID = ar.user_id LEFT JOIN ' . $wpdb->users . ' au ON au.ID = ar.admin_owner_user_id LEFT JOIN ' . $wpdb->users . ' ru ON ru.ID = ar.admin_reviewed_by WHERE ar.id = %d LIMIT 1',
                $access_request_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    private static function signup_review_consent_audit(int $access_request_id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, access_request_id, user_id, terms_version, privacy_version, consent_text_version, legal_locale, accepted_at, hash_key_version, capture_source, retention_rule, created_at FROM ' . self::table('consent_audit_records') . ' WHERE access_request_id = %d ORDER BY accepted_at DESC, id DESC LIMIT 1',
                $access_request_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    private static function signup_review_email_events(int $access_request_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, message_type, event_type, delivery_status, delivery_channel, email_delivery_provider, sender_domain, subject_key, failure_reason_category, bounce_type, created_at FROM ' . self::table('email_delivery_events') . ' WHERE access_request_id = %d ORDER BY created_at DESC, id DESC LIMIT 25',
                $access_request_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    private static function render_signup_review_admin_notice(): void
    {
        $request = wp_unslash($_GET);
        if (!empty($request['decision_updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Admin qualification decision saved and audit event recorded.</p></div>';
        } elseif (!empty($request['decision_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(self::signup_review_decision_error_message((string) $request['decision_error'])) . '</p></div>';
        }
    }

    private static function signup_review_decision_error_message(string $code): string
    {
        $messages = [
            'permission_denied' => 'Admin review access is required.',
            'invalid_nonce' => 'Decision security check failed. Refresh the page and try again.',
            'not_found' => 'Access request not found.',
            'invalid_decision' => 'Select a valid qualification decision.',
            'invalid_reason' => 'Enter a decision reason between 10 and 1000 characters.',
            'invalid_owner' => 'Select a valid admin owner.',
            'invalid_handoff_team' => 'Select sales, support, or unassigned for the handoff queue.',
            'invalid_handoff_priority' => 'Select a valid handoff priority.',
            'invalid_handoff_next_action' => 'Enter a next action of 180 characters or fewer.',
            'invalid_handoff_follow_up_date' => 'Enter a valid follow-up date.',
            'invalid_source_campaign' => 'Enter a source campaign of 120 characters or fewer.',
            'invalid_handoff_notes' => 'Enter handoff notes of 2000 characters or fewer.',
            'invalid_readiness' => 'Select a valid provisioning readiness value.',
            'rate_limited' => 'Too many admin review attempts. Wait briefly and try again.',
            'update_failed' => 'Decision could not be saved.',
        ];

        return $messages[$code] ?? 'Decision could not be saved.';
    }

    private static function render_signup_review_decision_panel(array $access_request): void
    {
        if (!current_user_can('list_users')) {
            return;
        }

        $action_url = admin_url('admin-post.php');
        $current_owner = (int) ($access_request['admin_owner_user_id'] ?? 0);
        $current_readiness = (string) ($access_request['provisioning_readiness_status'] ?? '');
        $current_team = (string) ($access_request['handoff_team'] ?? '');
        $current_priority = (string) ($access_request['handoff_priority'] ?? 'normal');

        echo '<section class="abit-profile-panel" style="margin-top:16px;"><h2>Admin Qualification Decision</h2>';
        echo '<form method="post" action="' . esc_url($action_url) . '">';
        wp_nonce_field('abit_saas_auth_admin_decision_' . (int) $access_request['id']);
        echo '<input type="hidden" name="action" value="abit_saas_auth_admin_decision" />';
        echo '<input type="hidden" name="access_request_id" value="' . esc_attr((string) (int) $access_request['id']) . '" />';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="abit_admin_decision">Decision</label></th><td><select id="abit_admin_decision" name="decision" required>';
        foreach (self::admin_decision_options() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="abit_admin_owner_user_id">Assign owner</label></th><td><select id="abit_admin_owner_user_id" name="admin_owner_user_id">';
        echo '<option value="0">No admin owner</option>';
        foreach (self::admin_owner_options() as $owner) {
            $owner_id = (int) $owner['id'];
            echo '<option value="' . esc_attr((string) $owner_id) . '"' . selected($current_owner, $owner_id, false) . '>' . esc_html($owner['label']) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="abit_handoff_team">Assign queue</label></th><td><select id="abit_handoff_team" name="handoff_team">';
        foreach (self::handoff_team_options() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($current_team, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="abit_handoff_priority">Priority</label></th><td><select id="abit_handoff_priority" name="handoff_priority">';
        foreach (self::handoff_priority_options() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($current_priority, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="abit_handoff_next_action">Next action</label></th><td><input id="abit_handoff_next_action" name="handoff_next_action" type="text" class="regular-text" maxlength="180" value="' . esc_attr((string) ($access_request['handoff_next_action'] ?? '')) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="abit_handoff_follow_up_date">Follow-up date</label></th><td><input id="abit_handoff_follow_up_date" name="handoff_follow_up_date" type="date" value="' . esc_attr((string) ($access_request['handoff_follow_up_date'] ?? '')) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="abit_source_campaign">Source campaign</label></th><td><input id="abit_source_campaign" name="source_campaign" type="text" class="regular-text" maxlength="120" value="' . esc_attr((string) ($access_request['source_campaign'] ?? '')) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="abit_handoff_notes">Handoff notes</label></th><td><textarea id="abit_handoff_notes" name="handoff_notes" rows="4" class="large-text">' . esc_textarea((string) ($access_request['handoff_notes'] ?? '')) . '</textarea><p class="description">Sales/support handoff notes. Signup notes are preserved separately below.</p></td></tr>';

        echo '<tr><th scope="row"><label for="abit_provisioning_readiness_status">Provisioning readiness</label></th><td><select id="abit_provisioning_readiness_status" name="provisioning_readiness_status">';
        foreach (self::provisioning_readiness_options() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($current_readiness, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="abit_admin_decision_reason">Reason</label></th><td><textarea id="abit_admin_decision_reason" name="reason" rows="5" class="large-text" required></textarea><p class="description">Required for every admin decision. Stored on the access request and in the audit event.</p></td></tr>';
        echo '</tbody></table>';
        submit_button('Save decision');
        echo '</form></section>';
    }

    private static function admin_decision_options(): array
    {
        return [
            'approve' => 'Approve',
            'hold' => 'Hold',
            'reject' => 'Reject',
            'request_info' => 'Request information',
            'assign_owner' => 'Assign owner only',
            'update_provisioning_readiness' => 'Update provisioning readiness only',
        ];
    }

    private static function handoff_team_options(): array
    {
        return [
            '' => 'Unassigned',
            'sales' => 'Sales',
            'support' => 'Support',
        ];
    }

    private static function handoff_priority_options(): array
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    private static function provisioning_readiness_options(): array
    {
        return [
            '' => 'Use global capacity setting',
            self::PROVISIONING_READINESS_NOT_READY => 'Not ready',
            self::PROVISIONING_READINESS_READY => 'Ready',
            self::PROVISIONING_READINESS_BLOCKED => 'Blocked',
        ];
    }

    private static function admin_owner_options(): array
    {
        $users = get_users(
            [
                'orderby' => 'display_name',
                'order' => 'ASC',
            ]
        );

        $owners = [];
        foreach ($users as $user) {
            if (!$user instanceof WP_User || !user_can($user, 'list_users')) {
                continue;
            }

            $owners[] = [
                'id' => (int) $user->ID,
                'label' => self::first_non_empty([$user->display_name, $user->user_email, 'User #' . (int) $user->ID]) . ' <' . $user->user_email . '>',
            ];
        }

        return $owners;
    }

    private static function render_signup_profile_panel(string $title, array $rows): void
    {
        echo '<section class="abit-profile-panel"><h2>' . esc_html($title) . '</h2><dl>';
        foreach ($rows as $label => $value) {
            $value = self::signup_review_display_value($value);
            echo '<dt>' . esc_html((string) $label) . '</dt><dd>' . esc_html($value) . '</dd>';
        }
        echo '</dl></section>';
    }

    private static function render_signup_review_workflow_panel(array $access_request): void
    {
        echo '<section class="abit-profile-panel" style="margin-top:16px;"><h2>Workflow Context</h2><dl>';
        foreach ([
            'Intended use case' => $access_request['intended_use_case'] ?? '',
            'Primary workflow' => $access_request['primary_workflow'] ?? '',
        ] as $label => $value) {
            echo '<dt>' . esc_html($label) . '</dt><dd class="abit-profile-notes">' . esc_html(self::signup_review_display_value($value)) . '</dd>';
        }
        echo '</dl></section>';
    }

    private static function render_signup_review_readiness_panel($provisioning): void
    {
        echo '<section class="abit-profile-panel" style="margin-top:16px;"><h2>Readiness Checklist</h2>';
        if (!is_array($provisioning) || empty($provisioning['preflight']['checks'])) {
            echo '<p>Readiness checklist is unavailable because the request owner user record could not be loaded.</p></section>';
            return;
        }

        echo '<ul class="abit-profile-list">';
        foreach ($provisioning['preflight']['checks'] as $check) {
            $passed = !empty($check['passed']);
            echo '<li><span class="' . esc_attr($passed ? 'abit-readiness-pass' : 'abit-readiness-fail') . '">' . esc_html($passed ? 'Pass' : 'Missing') . '</span> - ' . esc_html((string) $check['label']) . '</li>';
        }
        echo '</ul></section>';
    }

    private static function render_signup_review_notes_panel(array $access_request, ?array $provisioning_request): void
    {
        echo '<section class="abit-profile-panel" style="margin-top:16px;"><h2>Notes</h2><dl>';
        echo '<dt>Signup notes</dt><dd class="abit-profile-notes">' . esc_html(self::signup_review_display_value($access_request['notes'] ?? '')) . '</dd>';
        echo '<dt>Handoff notes</dt><dd class="abit-profile-notes">' . esc_html(self::signup_review_display_value($access_request['handoff_notes'] ?? '')) . '</dd>';
        echo '<dt>Provisioning notes</dt><dd class="abit-profile-notes">' . esc_html(self::signup_review_display_value($provisioning_request['notes'] ?? '')) . '</dd>';
        echo '</dl></section>';
    }

    private static function render_signup_review_audit_panel(array $access_request, ?array $consent, array $email_events, ?array $provisioning_request, $workspace): void
    {
        $events = [
            [
                'created_at' => (string) ($access_request['created_at'] ?? ''),
                'source' => 'Signup',
                'event' => 'Access request created',
                'status' => self::signup_review_label((string) ($access_request['review_status'] ?? '')),
                'details' => 'Request #' . (int) $access_request['id'],
            ],
        ];

        if (is_array($consent)) {
            $events[] = [
                'created_at' => (string) ($consent['accepted_at'] ?? $consent['created_at'] ?? ''),
                'source' => 'Consent',
                'event' => 'Terms and privacy accepted',
                'status' => 'Captured',
                'details' => 'Audit #' . (int) $consent['id'] . '; terms ' . (string) $consent['terms_version'] . '; privacy ' . (string) $consent['privacy_version'] . '; locale ' . (string) $consent['legal_locale'],
            ];
        }

        if (!empty($access_request['admin_reviewed_at']) && !empty($access_request['admin_decision'])) {
            $events[] = [
                'created_at' => (string) $access_request['admin_reviewed_at'],
                'source' => 'Admin',
                'event' => 'Qualification decision',
                'status' => self::signup_review_label((string) $access_request['admin_decision']),
                'details' => self::signup_review_display_value($access_request['admin_decision_reason'] ?? ''),
            ];
        }

        foreach ($email_events as $event) {
            $details = array_filter([
                self::signup_review_label((string) ($event['subject_key'] ?? '')),
                self::signup_review_label((string) ($event['failure_reason_category'] ?? '')),
                self::signup_review_label((string) ($event['bounce_type'] ?? '')),
                'provider: ' . (string) ($event['email_delivery_provider'] ?? ''),
            ]);
            $events[] = [
                'created_at' => (string) ($event['created_at'] ?? ''),
                'source' => 'Email',
                'event' => self::signup_review_label((string) ($event['message_type'] ?? '')) . ' / ' . self::signup_review_label((string) ($event['event_type'] ?? '')),
                'status' => self::signup_review_label((string) ($event['delivery_status'] ?? '')),
                'details' => implode('; ', $details),
            ];
        }

        if (is_array($provisioning_request)) {
            $events[] = [
                'created_at' => (string) ($provisioning_request['requested_at'] ?? $provisioning_request['created_at'] ?? ''),
                'source' => 'Provisioning',
                'event' => 'Provisioning requested',
                'status' => self::signup_review_label((string) $provisioning_request['request_status']),
                'details' => 'Request #' . (int) $provisioning_request['id'],
            ];
        }

        if (is_array($workspace) && !empty($workspace['workspace'])) {
            $events[] = [
                'created_at' => (string) ($workspace['workspace']['created_at'] ?? ''),
                'source' => 'Workspace',
                'event' => 'Workspace available',
                'status' => self::signup_review_label((string) ($workspace['status'] ?? '')),
                'details' => (string) ($workspace['workspace']['key'] ?? ''),
            ];
        }

        usort(
            $events,
            static function (array $a, array $b): int {
                return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            }
        );

        echo '<section class="abit-profile-panel" style="margin-top:16px;"><h2>Audit Trail</h2>';
        echo '<table class="widefat fixed striped"><thead><tr>';
        foreach (['When', 'Source', 'Event', 'Status', 'Details'] as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($events as $event) {
            echo '<tr>';
            echo '<td>' . esc_html(self::signup_review_display_value($event['created_at'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($event['source'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($event['event'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($event['status'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($event['details'] ?? '')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></section>';
    }

    private static function signup_review_user_summary(array $access_request, $user): string
    {
        if ($user instanceof WP_User) {
            return '#' . (int) $user->ID . ' - ' . self::first_non_empty([$user->display_name, $user->user_email, $user->user_login]);
        }

        return (int) ($access_request['user_id'] ?? 0) > 0 ? 'Missing user #' . (int) $access_request['user_id'] : 'No user linked';
    }

    private static function signup_review_handoff_summary(array $row, string $owner): string
    {
        $parts = array_filter(
            [
                self::signup_review_label((string) ($row['handoff_queue_status'] ?? '')),
                self::signup_review_label((string) ($row['handoff_team'] ?? '')),
                self::signup_review_label((string) ($row['handoff_priority'] ?? '')),
                $owner,
                !empty($row['handoff_follow_up_date']) ? 'Follow-up ' . (string) $row['handoff_follow_up_date'] : '',
                (string) ($row['handoff_next_action'] ?? ''),
            ]
        );

        return empty($parts) ? 'Unassigned' : implode(' / ', $parts);
    }

    private static function signup_review_admin_owner_summary(array $access_request): string
    {
        $owner_id = (int) ($access_request['admin_owner_user_id'] ?? 0);
        if ($owner_id <= 0) {
            return 'Unassigned';
        }

        return '#' . $owner_id . ' - ' . self::first_non_empty([
            $access_request['admin_owner_name'] ?? null,
            $access_request['admin_owner_email'] ?? null,
            'Missing user',
        ]);
    }

    private static function signup_review_reviewer_summary(array $access_request): string
    {
        $reviewer_id = (int) ($access_request['admin_reviewed_by'] ?? 0);
        if ($reviewer_id <= 0) {
            return '';
        }

        return '#' . $reviewer_id . ' - ' . self::first_non_empty([
            $access_request['reviewer_name'] ?? null,
            $access_request['reviewer_email'] ?? null,
            'Missing user',
        ]);
    }

    private static function signup_review_workspace_summary($workspace): string
    {
        if (!is_array($workspace)) {
            return 'Unavailable';
        }

        if (!empty($workspace['workspace'])) {
            return (string) $workspace['workspace']['key'] . ' - ' . self::signup_review_label((string) ($workspace['status'] ?? ''));
        }

        return self::signup_review_label((string) ($workspace['status'] ?? 'not_created')) . ' - ' . self::signup_review_label((string) ($workspace['hold_reason'] ?? ''));
    }

    private static function signup_review_display_value($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }

        $value = trim((string) $value);
        return $value === '' ? 'None' : $value;
    }

    private static function signup_review_module_summary(array $modules): string
    {
        if (empty($modules)) {
            return 'None';
        }

        $labels = [];
        foreach ($modules as $module) {
            $labels[] = self::signup_review_label((string) $module);
        }

        return implode(', ', $labels);
    }

    private static function signup_review_risk_summary(array $row, array $modules): string
    {
        $indicators = self::signup_review_risk_indicators($row, $modules);
        if (empty($indicators)) {
            return self::signup_review_risk_baseline($row, $modules);
        }

        return self::signup_review_risk_level($indicators) . ': ' . implode('; ', array_slice($indicators, 0, 3));
    }

    private static function signup_review_risk_baseline(array $row, array $modules): string
    {
        $status = (string) ($row['review_status'] ?? '');
        if ($status === self::REVIEW_STATUS_REJECTED) {
            return 'High';
        }

        if (empty($row['email_verified_at'])) {
            return 'Medium';
        }

        if (in_array('not_sure', $modules, true) || count($modules) >= 4 || (string) ($row['company_size'] ?? '') === '501_plus') {
            return 'Review';
        }

        return 'Standard';
    }

    private static function signup_review_risk_level(array $indicators): string
    {
        if (empty($indicators)) {
            return 'Standard';
        }

        foreach ($indicators as $indicator) {
            if (preg_match('/^(High|Disposable|Suspicious)/', $indicator)) {
                return 'High';
            }
        }

        return 'Review';
    }

    private static function signup_review_risk_indicators(array $row, array $modules): array
    {
        $indicators = [];
        $email = (string) ($row['business_email'] ?? '');
        $domain = self::email_domain($email);

        if ($domain !== '' && self::is_disposable_email_domain($domain)) {
            $indicators[] = 'Disposable email domain: ' . $domain;
        }

        if ($domain !== '' && self::is_suspicious_email_domain($domain)) {
            $indicators[] = 'Suspicious email domain: ' . $domain;
        }

        $sent_count = (int) ($row['email_delivery_sent_count'] ?? 0);
        $throttled_count = (int) ($row['email_resend_throttled_count'] ?? 0);
        if ($sent_count >= self::HIGH_RESEND_SENT_THRESHOLD || $throttled_count >= self::HIGH_RESEND_THROTTLED_THRESHOLD) {
            $indicators[] = 'High resend activity: ' . $sent_count . ' sent, ' . $throttled_count . ' throttled';
        }

        $failed_login_count = (int) ($row['failed_login_count'] ?? 0);
        if ($failed_login_count >= self::HIGH_FAILED_LOGIN_THRESHOLD) {
            $indicators[] = 'High failed login count: ' . $failed_login_count;
        }

        $shared_ip_count = (int) ($row['shared_ip_request_count'] ?? 0);
        if ($shared_ip_count >= self::SHARED_IP_REQUEST_THRESHOLD) {
            $indicators[] = 'Suspicious signup IP reuse: ' . $shared_ip_count . ' other requests';
        }

        $same_domain_count = (int) ($row['same_domain_request_count'] ?? 0);
        if ($same_domain_count >= self::SHARED_DOMAIN_REQUEST_THRESHOLD) {
            $indicators[] = 'Repeated email domain: ' . $same_domain_count . ' other requests';
        }

        $duplicate_company_count = (int) ($row['duplicate_company_count'] ?? 0);
        if ($duplicate_company_count > 0) {
            $indicators[] = 'Duplicate company hint: ' . $duplicate_company_count . ' matching request' . ($duplicate_company_count === 1 ? '' : 's');
        }

        if (empty($row['email_verified_at'])) {
            $indicators[] = 'Unverified email';
        }

        if (in_array('not_sure', $modules, true) || count($modules) >= 4) {
            $indicators[] = 'Broad or unclear module interest';
        }

        return $indicators;
    }

    private static function signup_review_label(string $value): string
    {
        $labels = [
            self::REVIEW_STATUS_PENDING_EMAIL => 'Pending email verification',
            self::REVIEW_STATUS_ONBOARDING_REQUIRED => 'Onboarding required',
            self::REVIEW_STATUS_PENDING_ADMIN_REVIEW => 'Pending admin review',
            self::REVIEW_STATUS_APPROVED => 'Approved for MVP access',
            self::REVIEW_STATUS_REJECTED => 'Rejected',
            self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED => 'More information requested',
            self::REVIEW_STATUS_ON_HOLD => 'On hold',
            'approve' => 'Approve',
            'hold' => 'Hold',
            'reject' => 'Reject',
            'request_info' => 'Request information',
            'assign_owner' => 'Assign owner',
            'update_provisioning_readiness' => 'Update provisioning readiness',
            'sales' => 'Sales',
            'support' => 'Support',
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            'unassigned' => 'Unassigned',
            'assigned' => 'Assigned',
            'held' => 'Held',
            'completed' => 'Completed',
            'follow_up_due' => 'Follow-up due',
            self::PROVISIONING_READINESS_READY => 'Ready',
            self::PROVISIONING_READINESS_NOT_READY => 'Not ready',
            self::PROVISIONING_READINESS_BLOCKED => 'Blocked',
            'verified' => 'Verified',
            'unverified' => 'Unverified',
            '1_10' => '1-10',
            '11_50' => '11-50',
            '51_200' => '51-200',
            '201_500' => '201-500',
            '501_plus' => '501+',
            'professional_services' => 'Professional services',
            'trading_distribution' => 'Trading and distribution',
            'retail_ecommerce' => 'Retail and ecommerce',
            'construction_real_estate' => 'Construction and real estate',
            'hr_payroll' => 'HR and payroll',
            'support_helpdesk' => 'Support or helpdesk',
            'website_portal' => 'Website or portal',
            'reports_analytics' => 'Reports and analytics',
            'full_erp_evaluation' => 'Full ERP evaluation',
            'not_sure' => 'Not sure',
        ];

        if ($value === '') {
            return '';
        }

        return $labels[$value] ?? ucwords(str_replace('_', ' ', $value));
    }

    private static function review_status_options(): array
    {
        return [
            self::REVIEW_STATUS_PENDING_EMAIL,
            self::REVIEW_STATUS_ONBOARDING_REQUIRED,
            self::REVIEW_STATUS_PENDING_ADMIN_REVIEW,
            self::REVIEW_STATUS_APPROVED,
            self::REVIEW_STATUS_REJECTED,
            self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED,
            self::REVIEW_STATUS_ON_HOLD,
        ];
    }

    private static function erp_module_options(): array
    {
        return [
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

        register_rest_route(
            self::REST_NAMESPACE,
            '/admin/access-requests/(?P<id>\d+)/qualification-decision',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'admin_qualification_decision'],
                'permission_callback' => [__CLASS__, 'require_admin_review_access'],
                'args' => [
                    'id' => [
                        'validate_callback' => static function ($value): bool {
                            return (int) $value > 0;
                        },
                    ],
                ],
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
        $rate_limit_events = self::table('auth_rate_limit_events');
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
                admin_owner_user_id BIGINT UNSIGNED NULL,
                handoff_team VARCHAR(32) NULL,
                handoff_priority VARCHAR(16) NULL DEFAULT 'normal',
                handoff_next_action VARCHAR(180) NULL,
                handoff_follow_up_date DATE NULL,
                source_campaign VARCHAR(120) NULL,
                handoff_notes TEXT NULL,
                handoff_queue_status VARCHAR(32) NULL DEFAULT 'unassigned',
                admin_decision VARCHAR(64) NULL,
                admin_decision_reason TEXT NULL,
                admin_reviewed_by BIGINT UNSIGNED NULL,
                admin_reviewed_at DATETIME NULL,
                provisioning_readiness_status VARCHAR(32) NULL,
                provisioning_readiness_reason TEXT NULL,
                provisioning_readiness_updated_by BIGINT UNSIGNED NULL,
                provisioning_readiness_updated_at DATETIME NULL,
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
                failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
                last_failed_login_at DATETIME NULL,
                signup_risk_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                signup_risk_level VARCHAR(16) NULL,
                signup_risk_action VARCHAR(32) NULL,
                signup_risk_reasons TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY business_email (business_email),
                KEY user_id (user_id),
                KEY company_id (company_id),
                KEY review_status (review_status),
                KEY admin_owner_user_id (admin_owner_user_id),
                KEY handoff_team (handoff_team),
                KEY handoff_priority (handoff_priority),
                KEY handoff_follow_up_date (handoff_follow_up_date),
                KEY handoff_queue_status (handoff_queue_status),
                KEY admin_decision (admin_decision),
                KEY provisioning_readiness_status (provisioning_readiness_status),
                KEY email_delivery_status (email_delivery_status),
                KEY failed_login_count (failed_login_count),
                KEY last_failed_login_at (last_failed_login_at),
                KEY signup_risk_level (signup_risk_level),
                KEY signup_risk_action (signup_risk_action),
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
            CREATE TABLE {$rate_limit_events} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                action VARCHAR(64) NOT NULL,
                identifier_hash CHAR(64) NULL,
                ip_hash CHAR(64) NULL,
                user_id BIGINT UNSIGNED NULL,
                access_request_id BIGINT UNSIGNED NULL,
                company_id BIGINT UNSIGNED NULL,
                result VARCHAR(32) NOT NULL,
                retry_after INT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY action (action),
                KEY identifier_hash (identifier_hash),
                KEY ip_hash (ip_hash),
                KEY user_id (user_id),
                KEY access_request_id (access_request_id),
                KEY result (result),
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
        $rate_identifier = strtolower(trim((string) ($payload['business_email'] ?? $payload['email'] ?? '')));
        $rate_limit = self::enforce_auth_rate_limit('signup', $rate_identifier);
        if ($rate_limit instanceof WP_REST_Response) {
            return $rate_limit;
        }

        $validated = self::validate_registration_payload($payload);

        if (!empty($validated['field_errors'])) {
            return self::field_error_response($validated['field_errors']);
        }

        $data = $validated['data'];
        $duplicate_errors = self::duplicate_email_errors($data['business_email']);
        if (!empty($duplicate_errors)) {
            self::audit_duplicate_signup_suppressed($data['business_email']);

            return self::registration_accepted_response();
        }

        $signup_risk = self::evaluate_signup_risk($data, $payload);
        if ($signup_risk['action'] === 'challenge' && !self::signup_challenge_passed($payload, $data)) {
            self::audit_signup_risk_event('auth_signup_challenge_required', $signup_risk, $data);

            return self::signup_challenge_response($data, $signup_risk);
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
                if (self::is_duplicate_user_email_error($user_id)) {
                    self::audit_duplicate_signup_suppressed($data['business_email']);
                    return self::registration_accepted_response();
                }

                return self::field_error_response(self::user_error_to_field_errors($user_id), 409);
            }

            $company_id = self::insert_company($data, $now);
            $access_request_id = self::insert_access_request($data, $user_id, $company_id, $now, $signup_risk);
            $consent_id = self::insert_consent($data, $user_id, $access_request_id, $now);
            self::link_latest_consent($access_request_id, $consent_id);

            if ($signup_risk['action'] === 'hold') {
                update_user_meta($user_id, 'abit_saas_risk_hold', 'signup_risk');
                update_user_meta($user_id, 'abit_saas_signup_risk_score', (int) $signup_risk['score']);
                update_user_meta($user_id, 'abit_saas_signup_risk_reasons', implode(', ', $signup_risk['reasons']));
                self::audit_signup_risk_event('auth_signup_risk_held', $signup_risk, $data, $user_id, $access_request_id, $company_id);
                $wpdb->query('COMMIT');

                return self::registration_accepted_response();
            }

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

            return self::registration_accepted_response();
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
        $rate_identifier = strtolower(trim((string) ($payload['email'] ?? $payload['business_email'] ?? '')));
        $rate_limit = self::enforce_auth_rate_limit('login', $rate_identifier);
        if ($rate_limit instanceof WP_REST_Response) {
            return $rate_limit;
        }

        $validated = self::validate_login_payload($payload);

        if (!empty($validated['field_errors'])) {
            return self::field_error_response($validated['field_errors']);
        }

        $email = $validated['data']['email'];
        $password = $validated['data']['password'];
        $remember = $validated['data']['remember'];
        $user = get_user_by('email', $email);
        $user_id = $user instanceof WP_User ? (int) $user->ID : 0;
        $access_request = self::access_request_for_email($email);
        $access_request_id = is_array($access_request) ? (int) $access_request['id'] : null;
        $company_id = is_array($access_request) ? (int) $access_request['company_id'] : null;

        if (!$user instanceof WP_User || !wp_check_password($password, $user->user_pass, $user->ID)) {
            self::record_failed_login_attempt($email);
            self::record_auth_rate_limit_event('login_failed', 'failed', $email, $user_id, $access_request_id, $company_id);
            if ($user instanceof WP_User) {
                self::maybe_lock_user_after_failed_login($user, $email, $access_request);
            }
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
            self::record_failed_login_attempt($email);
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
        self::record_auth_rate_limit_event('login', 'succeeded', $email, (int) $user->ID, $account_state['access_request_id'], $account_state['company_id']);

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
        $rate_limit = self::enforce_auth_rate_limit('resend_verification', $email);
        if ($rate_limit instanceof WP_REST_Response) {
            return $rate_limit;
        }

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
        $scope_denial = self::tenant_scope_denial_response($request, $user, $access_request, 'auth_me');
        if ($scope_denial instanceof WP_REST_Response) {
            return $scope_denial;
        }

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

        $admin_rate_limit = self::enforce_auth_rate_limit('admin_sensitive', 'provisioning_request', (int) $user->ID);
        if ($admin_rate_limit instanceof WP_REST_Response) {
            return $admin_rate_limit;
        }

        $access_request = self::access_request_for_user($user);
        $account_state = self::account_state_from_access_request($user, $access_request);
        $scope_denial = self::tenant_scope_denial_response($request, $user, $access_request, 'provisioning_request');
        if ($scope_denial instanceof WP_REST_Response) {
            return $scope_denial;
        }

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
                'preflight' => $eligibility['preflight'],
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

        $admin_rate_limit = self::enforce_auth_rate_limit('admin_sensitive', 'workspace_slug_validate', self::current_authenticated_user_id());
        if ($admin_rate_limit instanceof WP_REST_Response) {
            return $admin_rate_limit;
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

    public static function admin_qualification_decision(WP_REST_Request $request): WP_REST_Response
    {
        self::maybe_install_schema();

        $admin_rate_limit = self::enforce_auth_rate_limit('admin_sensitive', 'admin_qualification_decision', self::current_authenticated_user_id());
        if ($admin_rate_limit instanceof WP_REST_Response) {
            return $admin_rate_limit;
        }

        $result = self::apply_admin_qualification_decision(
            (int) $request['id'],
            self::request_payload($request),
            self::current_authenticated_user_id()
        );

        if (is_wp_error($result)) {
            $error_data = $result->get_error_data();
            return new WP_REST_Response(
                [
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code(),
                ],
                is_array($error_data) ? (int) ($error_data['status'] ?? 422) : 422
            );
        }

        return new WP_REST_Response(
            [
                'message' => 'Admin qualification decision saved.',
                'access_request' => $result,
            ],
            200
        );
    }

    public static function handle_admin_decision_post(): void
    {
        self::maybe_install_schema();

        $post = wp_unslash($_POST);
        $access_request_id = isset($post['access_request_id']) ? (int) $post['access_request_id'] : 0;
        $redirect_url = add_query_arg(
            [
                'page' => 'abit-signup-review',
                'access_request_id' => $access_request_id,
            ],
            admin_url('tools.php')
        );

        if (!current_user_can('list_users')) {
            wp_safe_redirect(add_query_arg('decision_error', 'permission_denied', $redirect_url));
            exit;
        }

        if (!check_admin_referer('abit_saas_auth_admin_decision_' . $access_request_id, '_wpnonce', false)) {
            wp_safe_redirect(add_query_arg('decision_error', 'invalid_nonce', $redirect_url));
            exit;
        }

        $admin_rate_limit = self::enforce_auth_rate_limit('admin_sensitive', 'admin_decision_post', get_current_user_id());
        if ($admin_rate_limit instanceof WP_REST_Response) {
            wp_safe_redirect(add_query_arg('decision_error', 'rate_limited', $redirect_url));
            exit;
        }

        $result = self::apply_admin_qualification_decision($access_request_id, $post, get_current_user_id());
        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('decision_error', $result->get_error_code(), $redirect_url));
            exit;
        }

        wp_safe_redirect(add_query_arg('decision_updated', '1', $redirect_url));
        exit;
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

    private static function tenant_scope_denial_response(WP_REST_Request $request, WP_User $user, ?array $access_request, string $surface): ?WP_REST_Response
    {
        $requested_company_id = self::requested_positive_int($request, 'company_id');
        $requested_access_request_id = self::requested_positive_int($request, 'access_request_id');
        $owned_company_id = is_array($access_request) ? (int) ($access_request['company_id'] ?? 0) : 0;
        $owned_access_request_id = is_array($access_request) ? (int) ($access_request['id'] ?? 0) : 0;

        $company_mismatch = $requested_company_id > 0 && $requested_company_id !== $owned_company_id;
        $access_request_mismatch = $requested_access_request_id > 0 && $requested_access_request_id !== $owned_access_request_id;
        if (!$company_mismatch && !$access_request_mismatch) {
            return null;
        }

        $reason = $company_mismatch ? 'requested_company_mismatch' : 'requested_access_request_mismatch';
        self::audit_tenant_scope_denied(
            $surface,
            (int) $user->ID,
            $reason,
            $requested_company_id,
            $owned_company_id,
            $requested_access_request_id,
            $owned_access_request_id
        );

        return new WP_REST_Response(
            [
                'message' => 'Access to the requested company scope is denied.',
                'code' => 'tenant_scope_denied',
            ],
            403
        );
    }

    private static function requested_positive_int(WP_REST_Request $request, string $key): int
    {
        $value = $request->get_param($key);
        return $value === null ? 0 : max(0, (int) $value);
    }

    private static function enforce_auth_rate_limit(string $action, string $identifier = '', int $user_id = 0, ?int $access_request_id = null, ?int $company_id = null): ?WP_REST_Response
    {
        $limited = self::check_auth_rate_limit($action, $identifier, $user_id);
        if (!empty($limited['limited'])) {
            $retry_after = (int) $limited['retry_after'];
            self::record_auth_rate_limit_event($action, 'throttled', $identifier, $user_id, $access_request_id, $company_id, $retry_after);
            self::audit_auth_rate_limit($action, 'api_request', $retry_after, $user_id, $access_request_id, $company_id);

            return self::rate_limited_response($retry_after);
        }

        self::record_auth_rate_limit_event($action, 'allowed', $identifier, $user_id, $access_request_id, $company_id);
        return null;
    }

    private static function check_auth_rate_limit(string $action, string $identifier = '', int $user_id = 0): array
    {
        $policy = self::auth_rate_limit_policy($action);
        $limit = max(1, (int) $policy['limit']);
        $window = max(1, (int) $policy['window']);
        $since = gmdate('Y-m-d H:i:s', time() - $window);
        $identifier_hash = self::rate_limit_identifier_hash($identifier);
        $ip_hash = self::rate_limit_ip_hash();

        $count = self::auth_rate_limit_event_count($action, $since, $identifier_hash, $ip_hash, $user_id);
        if ($count < $limit) {
            return [
                'limited' => false,
                'retry_after' => 0,
                'count' => $count,
                'limit' => $limit,
            ];
        }

        return [
            'limited' => true,
            'retry_after' => self::auth_rate_limit_retry_after($action, $since, $identifier_hash, $ip_hash, $user_id, $window),
            'count' => $count,
            'limit' => $limit,
        ];
    }

    private static function auth_rate_limit_policy(string $action): array
    {
        $defaults = [
            'signup' => ['limit' => 10, 'window' => HOUR_IN_SECONDS],
            'login' => ['limit' => 20, 'window' => 15 * MINUTE_IN_SECONDS],
            'login_failed' => ['limit' => 5, 'window' => 15 * MINUTE_IN_SECONDS, 'lockout' => 15 * MINUTE_IN_SECONDS],
            'resend_verification' => ['limit' => 10, 'window' => HOUR_IN_SECONDS],
            'forgot_password' => ['limit' => 5, 'window' => HOUR_IN_SECONDS],
            'reset_password' => ['limit' => 5, 'window' => HOUR_IN_SECONDS],
            'admin_sensitive' => ['limit' => 30, 'window' => 5 * MINUTE_IN_SECONDS],
        ];

        $policies = apply_filters('abit_saas_auth_rate_limit_policies', $defaults);
        $policy = is_array($policies) && isset($policies[$action]) && is_array($policies[$action])
            ? $policies[$action]
            : ($defaults[$action] ?? ['limit' => 20, 'window' => HOUR_IN_SECONDS]);

        return array_merge(['limit' => 20, 'window' => HOUR_IN_SECONDS, 'lockout' => 0], $policy);
    }

    private static function auth_rate_limit_event_count(string $action, string $since, string $identifier_hash, string $ip_hash, int $user_id): int
    {
        global $wpdb;

        $where = ['action = %s', 'created_at >= %s'];
        $params = [$action, $since];
        $scopes = [];

        if ($identifier_hash !== '') {
            $scopes[] = 'identifier_hash = %s';
            $params[] = $identifier_hash;
        }

        if ($ip_hash !== '') {
            $scopes[] = 'ip_hash = %s';
            $params[] = $ip_hash;
        }

        if ($user_id > 0) {
            $scopes[] = 'user_id = %d';
            $params[] = $user_id;
        }

        if (empty($scopes)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM ' . self::table('auth_rate_limit_events') . ' WHERE ' . implode(' AND ', $where) . ' AND (' . implode(' OR ', $scopes) . ')';
        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    private static function auth_rate_limit_retry_after(string $action, string $since, string $identifier_hash, string $ip_hash, int $user_id, int $window): int
    {
        global $wpdb;

        $where = ['action = %s', 'created_at >= %s'];
        $params = [$action, $since];
        $scopes = [];

        if ($identifier_hash !== '') {
            $scopes[] = 'identifier_hash = %s';
            $params[] = $identifier_hash;
        }

        if ($ip_hash !== '') {
            $scopes[] = 'ip_hash = %s';
            $params[] = $ip_hash;
        }

        if ($user_id > 0) {
            $scopes[] = 'user_id = %d';
            $params[] = $user_id;
        }

        if (empty($scopes)) {
            return $window;
        }

        $sql = 'SELECT created_at FROM ' . self::table('auth_rate_limit_events') . ' WHERE ' . implode(' AND ', $where) . ' AND (' . implode(' OR ', $scopes) . ') ORDER BY created_at ASC LIMIT 1';
        $oldest = $wpdb->get_var($wpdb->prepare($sql, $params));
        if (!$oldest) {
            return $window;
        }

        return max(1, $window - max(0, time() - strtotime((string) $oldest)));
    }

    private static function record_auth_rate_limit_event(string $action, string $result, string $identifier = '', int $user_id = 0, ?int $access_request_id = null, ?int $company_id = null, ?int $retry_after = null): void
    {
        global $wpdb;

        $wpdb->insert(
            self::table('auth_rate_limit_events'),
            [
                'action' => sanitize_key($action),
                'identifier_hash' => self::rate_limit_identifier_hash($identifier) ?: null,
                'ip_hash' => self::rate_limit_ip_hash() ?: null,
                'user_id' => $user_id > 0 ? $user_id : null,
                'access_request_id' => $access_request_id,
                'company_id' => $company_id,
                'result' => sanitize_key($result),
                'retry_after' => $retry_after,
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s']
        );
    }

    private static function maybe_lock_user_after_failed_login(WP_User $user, string $email, ?array $access_request): void
    {
        $limited = self::check_auth_rate_limit('login_failed', $email, (int) $user->ID);
        if (empty($limited['limited'])) {
            return;
        }

        $policy = self::auth_rate_limit_policy('login_failed');
        $lockout = max(60, (int) $policy['lockout']);
        $locked_until = time() + $lockout;
        update_user_meta((int) $user->ID, self::AUTH_LOCKOUT_META_KEY, $locked_until);

        $access_request_id = is_array($access_request) ? (int) $access_request['id'] : null;
        $company_id = is_array($access_request) ? (int) $access_request['company_id'] : null;
        self::audit_event(
            'auth_account_lockout_started',
            [
                'actor_user_id' => (int) $user->ID,
                'actor_type' => 'system',
                'entity_type' => 'user',
                'entity_id' => (int) $user->ID,
                'access_request_id' => $access_request_id,
                'company_id' => $company_id,
                'event_data' => [
                    'auth_method' => 'email_password',
                    'failure_reason_category' => 'too_many_failed_logins',
                    'lockout_seconds' => $lockout,
                    'locked_until' => gmdate('Y-m-d H:i:s', $locked_until),
                    'email_domain_hash' => self::email_domain_hash($email),
                ],
            ]
        );
    }

    private static function audit_auth_rate_limit(string $action, string $surface, int $retry_after, int $user_id = 0, ?int $access_request_id = null, ?int $company_id = null): void
    {
        self::audit_event(
            'auth_rate_limit_throttled',
            [
                'actor_user_id' => $user_id > 0 ? $user_id : null,
                'actor_type' => $user_id > 0 ? 'user' : 'anonymous',
                'entity_type' => 'auth_rate_limit',
                'access_request_id' => $access_request_id,
                'company_id' => $company_id,
                'event_data' => [
                    'action' => sanitize_key($action),
                    'surface' => sanitize_key($surface),
                    'retry_after' => $retry_after,
                    'ip_hash' => self::rate_limit_ip_hash(),
                ],
            ]
        );
    }

    private static function audit_tenant_scope_denied(string $surface, int $user_id, string $reason, int $requested_company_id, int $owned_company_id, int $requested_access_request_id, int $owned_access_request_id): void
    {
        self::audit_event(
            'auth_tenant_scope_denied',
            [
                'actor_user_id' => $user_id,
                'actor_type' => 'user',
                'entity_type' => 'tenant_scope',
                'entity_id' => $requested_company_id > 0 ? $requested_company_id : $requested_access_request_id,
                'company_id' => $owned_company_id > 0 ? $owned_company_id : null,
                'access_request_id' => $owned_access_request_id > 0 ? $owned_access_request_id : null,
                'event_data' => [
                    'api_surface' => sanitize_key($surface),
                    'denial_reason' => sanitize_key($reason),
                    'requested_company_id' => $requested_company_id,
                    'owned_company_id' => $owned_company_id,
                    'requested_access_request_id' => $requested_access_request_id,
                    'owned_access_request_id' => $owned_access_request_id,
                ],
            ]
        );
    }

    private static function rate_limited_response(int $retry_after): WP_REST_Response
    {
        $response = new WP_REST_Response(
            [
                'message' => self::rate_limited_message($retry_after),
                'code' => 'rate_limited',
                'retry_after' => $retry_after,
            ],
            429
        );
        $response->header('Retry-After', (string) max(1, $retry_after));

        return $response;
    }

    private static function rate_limited_message(int $retry_after): string
    {
        return sprintf('Too many attempts. Try again in %d seconds.', max(1, $retry_after));
    }

    private static function rate_limit_identifier_hash(string $identifier): string
    {
        $identifier = strtolower(trim($identifier));
        return $identifier === '' ? '' : self::hmac($identifier);
    }

    private static function rate_limit_ip_hash(): string
    {
        $ip = self::request_ip();
        return $ip === '' ? '' : self::hmac($ip);
    }

    private static function audit_event(string $event_type, array $context): void
    {
        if (function_exists('abitai_auth_write_audit_log')) {
            abitai_auth_write_audit_log($event_type, self::redact_sensitive_log_data($context));
        }
    }

    private static function redact_sensitive_log_data(array $data): array
    {
        $redacted = [];
        foreach ($data as $key => $value) {
            $key_string = (string) $key;
            if (self::is_sensitive_log_key($key_string)) {
                $redacted[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = self::redact_sensitive_log_data($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private static function is_sensitive_log_key(string $key): bool
    {
        $key = strtolower($key);
        $sensitive_keys = [
            'password',
            'pass',
            'pass1',
            'pass2',
            'pwd',
            'user_pass',
            'current_password',
            'new_password',
            'confirm_password',
            'password_confirmation',
            'smtp_password',
            'secret',
            'token',
            'key',
        ];

        if (in_array($key, $sensitive_keys, true)) {
            return true;
        }

        return preg_match('/(password|passwd|pwd|secret|token|reset_key|verification_key)/', $key) === 1;
    }

    private static function apply_admin_qualification_decision(int $access_request_id, array $payload, int $actor_user_id)
    {
        if (!current_user_can('list_users')) {
            return new WP_Error('permission_denied', 'Admin review access is required.', ['status' => 403]);
        }

        if ($access_request_id <= 0) {
            return new WP_Error('not_found', 'Access request not found.', ['status' => 404]);
        }

        $decision = sanitize_key((string) ($payload['decision'] ?? ''));
        if (!array_key_exists($decision, self::admin_decision_options())) {
            return new WP_Error('invalid_decision', 'Select a valid qualification decision.', ['status' => 422]);
        }

        $reason = self::clean_textarea($payload['reason'] ?? $payload['admin_decision_reason'] ?? '');
        if (strlen($reason) < 10 || strlen($reason) > 1000 || self::has_unsafe_text($reason) || self::is_links_only($reason)) {
            return new WP_Error('invalid_reason', 'Enter a decision reason between 10 and 1000 characters.', ['status' => 422]);
        }

        $admin_owner_user_id = isset($payload['admin_owner_user_id']) ? max(0, (int) $payload['admin_owner_user_id']) : 0;
        if ($admin_owner_user_id > 0) {
            $admin_owner = get_userdata($admin_owner_user_id);
            if (!$admin_owner instanceof WP_User || !user_can($admin_owner, 'list_users')) {
                return new WP_Error('invalid_owner', 'Select a valid admin owner.', ['status' => 422]);
            }
        }

        $readiness_status = sanitize_key((string) ($payload['provisioning_readiness_status'] ?? ''));
        if (!array_key_exists($readiness_status, self::provisioning_readiness_options())) {
            return new WP_Error('invalid_readiness', 'Select a valid provisioning readiness value.', ['status' => 422]);
        }

        $handoff_team = sanitize_key((string) ($payload['handoff_team'] ?? ''));
        if (!array_key_exists($handoff_team, self::handoff_team_options())) {
            return new WP_Error('invalid_handoff_team', 'Select sales, support, or unassigned for the handoff queue.', ['status' => 422]);
        }

        $handoff_priority = sanitize_key((string) ($payload['handoff_priority'] ?? 'normal'));
        if (!array_key_exists($handoff_priority, self::handoff_priority_options())) {
            return new WP_Error('invalid_handoff_priority', 'Select a valid handoff priority.', ['status' => 422]);
        }

        $handoff_next_action = self::clean_text($payload['handoff_next_action'] ?? '');
        if (strlen($handoff_next_action) > 180 || self::has_unsafe_text($handoff_next_action)) {
            return new WP_Error('invalid_handoff_next_action', 'Enter a next action of 180 characters or fewer.', ['status' => 422]);
        }

        $handoff_follow_up_date = self::normalize_date_value((string) ($payload['handoff_follow_up_date'] ?? ''));
        if (is_wp_error($handoff_follow_up_date)) {
            return new WP_Error('invalid_handoff_follow_up_date', 'Enter a valid follow-up date.', ['status' => 422]);
        }

        $source_campaign = self::clean_text($payload['source_campaign'] ?? '');
        if (strlen($source_campaign) > 120 || self::has_unsafe_text($source_campaign)) {
            return new WP_Error('invalid_source_campaign', 'Enter a source campaign of 120 characters or fewer.', ['status' => 422]);
        }

        $handoff_notes = self::clean_textarea($payload['handoff_notes'] ?? '');
        if (strlen($handoff_notes) > 2000 || self::has_unsafe_text($handoff_notes)) {
            return new WP_Error('invalid_handoff_notes', 'Enter handoff notes of 2000 characters or fewer.', ['status' => 422]);
        }

        global $wpdb;
        $access_request = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id, user_id, company_id, review_status, admin_owner_user_id, handoff_team, handoff_priority, handoff_next_action, handoff_follow_up_date, source_campaign, handoff_queue_status, provisioning_readiness_status FROM ' . self::table('access_requests') . ' WHERE id = %d LIMIT 1',
                $access_request_id
            ),
            ARRAY_A
        );

        if (!is_array($access_request)) {
            return new WP_Error('not_found', 'Access request not found.', ['status' => 404]);
        }

        $status_by_decision = [
            'approve' => self::REVIEW_STATUS_APPROVED,
            'hold' => self::REVIEW_STATUS_ON_HOLD,
            'reject' => self::REVIEW_STATUS_REJECTED,
            'request_info' => self::REVIEW_STATUS_MORE_INFORMATION_REQUESTED,
        ];
        $old_status = (string) $access_request['review_status'];
        $new_status = $status_by_decision[$decision] ?? $old_status;
        $now = current_time('mysql', true);
        $handoff_queue_status = self::handoff_queue_status($new_status, $admin_owner_user_id, $handoff_team, (string) $handoff_follow_up_date);

        $update = [
            'review_status' => $new_status,
            'admin_owner_user_id' => $admin_owner_user_id > 0 ? $admin_owner_user_id : null,
            'handoff_team' => $handoff_team !== '' ? $handoff_team : null,
            'handoff_priority' => $handoff_priority,
            'handoff_next_action' => $handoff_next_action !== '' ? $handoff_next_action : null,
            'handoff_follow_up_date' => $handoff_follow_up_date !== '' ? $handoff_follow_up_date : null,
            'source_campaign' => $source_campaign !== '' ? $source_campaign : null,
            'handoff_notes' => $handoff_notes !== '' ? $handoff_notes : null,
            'handoff_queue_status' => $handoff_queue_status,
            'admin_decision' => $decision,
            'admin_decision_reason' => $reason,
            'admin_reviewed_by' => $actor_user_id > 0 ? $actor_user_id : null,
            'admin_reviewed_at' => $now,
            'provisioning_readiness_status' => $readiness_status !== '' ? $readiness_status : null,
            'provisioning_readiness_reason' => $readiness_status !== '' ? $reason : null,
            'provisioning_readiness_updated_by' => $actor_user_id > 0 ? $actor_user_id : null,
            'provisioning_readiness_updated_at' => $readiness_status !== '' ? $now : null,
            'updated_at' => $now,
        ];

        $updated = $wpdb->update(
            self::table('access_requests'),
            $update,
            ['id' => $access_request_id],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );

        if (false === $updated) {
            return new WP_Error('update_failed', 'Decision could not be saved.', ['status' => 500]);
        }

        update_user_meta((int) $access_request['user_id'], 'abit_saas_review_status', $new_status);
        update_user_meta((int) $access_request['user_id'], 'abitai_access_request_status', $new_status);
        update_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_priority', $handoff_priority);
        update_user_meta((int) $access_request['user_id'], 'abitai_handoff_priority', $handoff_priority);
        update_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_queue_status', $handoff_queue_status);
        update_user_meta((int) $access_request['user_id'], 'abitai_handoff_queue_status', $handoff_queue_status);
        update_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_team', $handoff_team);
        update_user_meta((int) $access_request['user_id'], 'abitai_handoff_team', $handoff_team);
        if ($handoff_follow_up_date !== '') {
            update_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_follow_up_date', $handoff_follow_up_date);
            update_user_meta((int) $access_request['user_id'], 'abitai_handoff_follow_up_date', $handoff_follow_up_date);
        } else {
            delete_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_follow_up_date');
            delete_user_meta((int) $access_request['user_id'], 'abitai_handoff_follow_up_date');
        }
        if ($handoff_next_action !== '') {
            update_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_next_action', $handoff_next_action);
            update_user_meta((int) $access_request['user_id'], 'abitai_handoff_next_action', $handoff_next_action);
        } else {
            delete_user_meta((int) $access_request['user_id'], 'abit_saas_handoff_next_action');
            delete_user_meta((int) $access_request['user_id'], 'abitai_handoff_next_action');
        }
        if ($admin_owner_user_id > 0) {
            update_user_meta((int) $access_request['user_id'], 'abit_saas_admin_owner_user_id', $admin_owner_user_id);
        } else {
            delete_user_meta((int) $access_request['user_id'], 'abit_saas_admin_owner_user_id');
        }

        self::audit_event(
            'auth_admin_qualification_decision',
            [
                'actor_user_id' => $actor_user_id,
                'actor_type' => 'admin',
                'entity_type' => 'access_request',
                'entity_id' => $access_request_id,
                'access_request_id' => $access_request_id,
                'company_id' => (int) $access_request['company_id'],
                'event_data' => [
                    'decision' => $decision,
                    'reason' => $reason,
                    'previous_review_status' => $old_status,
                    'review_status' => $new_status,
                    'admin_owner_user_id' => $admin_owner_user_id > 0 ? $admin_owner_user_id : null,
                    'previous_admin_owner_user_id' => !empty($access_request['admin_owner_user_id']) ? (int) $access_request['admin_owner_user_id'] : null,
                    'handoff_team' => $handoff_team !== '' ? $handoff_team : null,
                    'previous_handoff_team' => self::nullable_key($access_request['handoff_team'] ?? null),
                    'handoff_priority' => $handoff_priority,
                    'previous_handoff_priority' => self::nullable_key($access_request['handoff_priority'] ?? null),
                    'handoff_next_action' => $handoff_next_action !== '' ? $handoff_next_action : null,
                    'handoff_follow_up_date' => $handoff_follow_up_date !== '' ? $handoff_follow_up_date : null,
                    'source_campaign' => $source_campaign !== '' ? $source_campaign : null,
                    'handoff_queue_status' => $handoff_queue_status,
                    'previous_handoff_queue_status' => self::nullable_key($access_request['handoff_queue_status'] ?? null),
                    'provisioning_readiness_status' => $readiness_status !== '' ? $readiness_status : null,
                    'previous_provisioning_readiness_status' => self::nullable_key($access_request['provisioning_readiness_status'] ?? null),
                ],
            ]
        );

        return [
            'id' => $access_request_id,
            'status' => $new_status,
            'decision' => $decision,
            'admin_owner_user_id' => $admin_owner_user_id > 0 ? $admin_owner_user_id : null,
            'handoff_team' => $handoff_team !== '' ? $handoff_team : null,
            'handoff_priority' => $handoff_priority,
            'handoff_follow_up_date' => $handoff_follow_up_date !== '' ? $handoff_follow_up_date : null,
            'handoff_queue_status' => $handoff_queue_status,
            'provisioning_readiness_status' => $readiness_status !== '' ? $readiness_status : null,
            'reviewed_by' => $actor_user_id > 0 ? $actor_user_id : null,
            'reviewed_at' => $now,
        ];
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

    private static function registration_accepted_response(): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'message' => 'If this email is eligible, verification instructions will be sent to that address.',
                'status' => 'accepted',
            ],
            202
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

    private static function user_for_login_identifier(string $identifier): ?WP_User
    {
        $identifier = strtolower(trim($identifier));
        if ($identifier === '') {
            return null;
        }

        $user = is_email($identifier) ? get_user_by('email', $identifier) : get_user_by('login', $identifier);
        return $user instanceof WP_User ? $user : null;
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
                'SELECT ar.id, ar.user_id, ar.company_id, ar.full_name, ar.business_email, ar.company_name, ar.country_region, ar.intended_use_case, ar.role, ar.company_size, ar.industry, ar.primary_workflow, ar.erp_module_interest, ar.current_system, ar.timeline, ar.notes, ar.workspace_slug_override, ar.persona, ar.review_status, ar.admin_owner_user_id, ar.handoff_team, ar.handoff_priority, ar.handoff_next_action, ar.handoff_follow_up_date, ar.source_campaign, ar.handoff_notes, ar.handoff_queue_status, ar.admin_decision, ar.admin_decision_reason, ar.admin_reviewed_by, ar.admin_reviewed_at, ar.provisioning_readiness_status, ar.provisioning_readiness_reason, ar.provisioning_readiness_updated_by, ar.provisioning_readiness_updated_at, ar.email_verified_at, ar.terms_privacy_accepted_at, ar.terms_version, ar.privacy_version, ar.latest_consent_audit_record_id, ar.email_delivery_status, ar.email_delivery_last_event, ar.email_delivery_last_event_at, ar.email_delivery_sent_count, ar.email_delivery_failed_count, ar.email_delivery_bounced_count, ar.email_token_expired_count, ar.email_resend_throttled_count, ar.created_at, ar.updated_at, c.company_name AS company_record_name, c.country_region AS company_record_country_region, c.draft_status AS company_record_status FROM ' . self::table('access_requests') . ' ar LEFT JOIN ' . self::table('companies') . ' c ON c.id = ar.company_id WHERE ar.user_id = %d OR ar.business_email = %s ORDER BY ar.id DESC LIMIT 1',
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
            self::REVIEW_STATUS_ON_HOLD => ['state' => 'on_hold', 'route' => 'review_pending'],
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
            self::REVIEW_STATUS_ON_HOLD => '/auth/review-pending',
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
            'preflight' => $eligibility['preflight'],
            'request' => is_array($request) ? self::format_provisioning_request($request) : null,
        ];
    }

    private static function provisioning_eligibility(WP_User $user, ?array $access_request, array $account_state, array $onboarding): array
    {
        $preflight = self::provisioning_preflight_checklist($user, $access_request, $account_state, $onboarding);
        $missing = $preflight['missing_requirements'];

        if ($account_state['locked']) {
            $missing[] = 'account_available';
        }

        if (!$account_state['email_verified']) {
            $missing[] = 'email_verified';
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
                'preflight' => $preflight,
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
            'message' => 'Provisioning can only be requested after email verification, admin approval, and all required preflight checks are complete.',
            'missing_requirements' => $missing,
            'preflight' => $preflight,
            'status' => $status,
        ];
    }

    private static function provisioning_preflight_checklist(WP_User $user, ?array $access_request, array $account_state, array $onboarding): array
    {
        $company_name = self::first_non_empty([
            $access_request['company_record_name'] ?? null,
            $access_request['company_name'] ?? null,
        ]);
        $country_region = self::first_non_empty([
            $access_request['company_record_country_region'] ?? null,
            $access_request['country_region'] ?? null,
        ]);
        $module_interest = (array) ($onboarding['erp_module_interest'] ?? []);
        $owner_user_id = is_array($access_request) ? (int) ($access_request['user_id'] ?? 0) : 0;
        $owner_email = self::first_non_empty([
            $access_request['business_email'] ?? null,
            $user->user_email,
        ]);
        $owner_name = self::first_non_empty([
            $access_request['full_name'] ?? null,
            $user->display_name,
        ]);
        $consent_audit_record_id = is_array($access_request) ? (int) ($access_request['latest_consent_audit_record_id'] ?? 0) : 0;
        $consent_ready = is_array($access_request)
            && !empty($access_request['terms_privacy_accepted_at'])
            && self::first_non_empty([$access_request['terms_version'] ?? null]) !== ''
            && self::first_non_empty([$access_request['privacy_version'] ?? null]) !== ''
            && $consent_audit_record_id > 0;
        $capacity_ready = self::provisioning_capacity_ready($user, $access_request);

        $checks = [
            [
                'key' => 'company_profile',
                'label' => 'Company profile',
                'passed' => is_array($access_request)
                    && !empty($access_request['id'])
                    && !empty($access_request['company_id'])
                    && $company_name !== ''
                    && self::first_non_empty([$onboarding['company_size'] ?? null]) !== ''
                    && self::first_non_empty([$onboarding['industry'] ?? null]) !== '',
                'missing_requirement' => is_array($access_request) && !empty($access_request['id']) ? 'company_profile' : 'access_request',
            ],
            [
                'key' => 'country',
                'label' => 'Country or region',
                'passed' => $country_region !== '',
                'missing_requirement' => 'country_region',
            ],
            [
                'key' => 'modules',
                'label' => 'ERP modules',
                'passed' => !empty($module_interest),
                'missing_requirement' => 'erp_module_interest',
            ],
            [
                'key' => 'owner',
                'label' => 'Request owner',
                'passed' => $owner_user_id > 0 && $owner_email !== '' && $owner_name !== '',
                'missing_requirement' => 'request_owner',
            ],
            [
                'key' => 'consent',
                'label' => 'Consent audit',
                'passed' => $consent_ready,
                'missing_requirement' => 'consent_audit',
            ],
            [
                'key' => 'admin_approval',
                'label' => 'Admin approval',
                'passed' => $account_state['review_status'] === self::REVIEW_STATUS_APPROVED,
                'missing_requirement' => 'admin_approval',
            ],
            [
                'key' => 'capacity_readiness',
                'label' => 'Capacity readiness',
                'passed' => $capacity_ready,
                'missing_requirement' => 'capacity_readiness',
            ],
        ];

        $missing = [];
        foreach ($checks as $check) {
            if (empty($check['passed'])) {
                $missing[] = (string) $check['missing_requirement'];
            }
        }

        $checks = array_map(
            static function (array $check): array {
                unset($check['missing_requirement']);
                return $check;
            },
            $checks
        );

        return [
            'completed' => empty($missing),
            'missing_requirements' => array_values(array_unique($missing)),
            'checks' => $checks,
            'fields' => [
                'company_profile' => [
                    'access_request_id' => is_array($access_request) ? (int) ($access_request['id'] ?? 0) : null,
                    'company_id' => is_array($access_request) ? (int) ($access_request['company_id'] ?? 0) : null,
                    'company_name' => $company_name,
                    'company_size' => self::first_non_empty([$onboarding['company_size'] ?? null]),
                    'industry' => self::first_non_empty([$onboarding['industry'] ?? null]),
                ],
                'country_region' => $country_region,
                'erp_module_interest' => $module_interest,
                'owner' => [
                    'user_id' => $owner_user_id > 0 ? $owner_user_id : null,
                    'name' => $owner_name,
                    'email' => $owner_email,
                ],
                'consent' => [
                    'accepted_at' => self::nullable_datetime($access_request['terms_privacy_accepted_at'] ?? null),
                    'terms_version' => self::nullable_key($access_request['terms_version'] ?? null),
                    'privacy_version' => self::nullable_key($access_request['privacy_version'] ?? null),
                    'audit_record_id' => $consent_audit_record_id > 0 ? $consent_audit_record_id : null,
                ],
                'admin_approval' => [
                    'approved' => $account_state['review_status'] === self::REVIEW_STATUS_APPROVED,
                    'review_status' => $account_state['review_status'],
                ],
                'capacity_readiness' => [
                    'ready' => $capacity_ready,
                    'source' => self::provisioning_capacity_ready_source($access_request),
                    'reason' => self::first_non_empty([$access_request['provisioning_readiness_reason'] ?? null]),
                ],
            ],
        ];
    }

    private static function provisioning_capacity_ready(WP_User $user, ?array $access_request): bool
    {
        if (is_array($access_request)) {
            $readiness_status = (string) ($access_request['provisioning_readiness_status'] ?? '');
            if ($readiness_status === self::PROVISIONING_READINESS_READY) {
                return true;
            }
            if (in_array($readiness_status, [self::PROVISIONING_READINESS_NOT_READY, self::PROVISIONING_READINESS_BLOCKED], true)) {
                return false;
            }
        }

        $configured = self::env_value('ABIT_SAAS_PROVISIONING_CAPACITY_READY');
        if ($configured === '') {
            $configured = (string) get_option('abit_saas_provisioning_capacity_ready', '');
        }

        $ready = filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        return (bool) apply_filters('abit_saas_auth_provisioning_capacity_ready', $ready, $access_request, $user);
    }

    private static function provisioning_capacity_ready_source(?array $access_request = null): string
    {
        if (is_array($access_request) && self::first_non_empty([$access_request['provisioning_readiness_status'] ?? null]) !== '') {
            return 'access_request';
        }

        if (self::env_value('ABIT_SAAS_PROVISIONING_CAPACITY_READY') !== '') {
            return 'env';
        }

        if (get_option('abit_saas_provisioning_capacity_ready', '') !== '') {
            return 'option';
        }

        return 'default';
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

        $locked_until = (int) get_user_meta($user->ID, self::AUTH_LOCKOUT_META_KEY, true);
        if ($locked_until > time()) {
            return true;
        }

        if ($locked_until > 0) {
            delete_user_meta($user->ID, self::AUTH_LOCKOUT_META_KEY);
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

        if (self::is_common_password($password)) {
            return 'Choose a less common password.';
        }

        return null;
    }

    private static function is_common_password(string $password): bool
    {
        $raw = strtolower(trim($password));
        $common = [
            '123456789012',
            '111111111111',
            'admin123456',
            'administrator',
            'abitpassword',
            'changeme123',
            'default1234',
            'letmein12345',
            'passw0rd123',
            'password',
            'password1',
            'password12',
            'password123',
            'password1234',
            'password12345',
            'password123456',
            'password123!',
            'qwerty123456',
            'qwertyuiop12',
            'summer2026!',
            'welcome123',
            'welcome1234',
            'welcome12345',
            'welcome2026!',
        ];

        if (in_array($raw, $common, true)) {
            return true;
        }

        $stripped = preg_replace('/[^a-z0-9]/', '', $raw);
        if (in_array($stripped, $common, true)) {
            return true;
        }

        return preg_match('/^(p[a@]ssw[o0]rd|qwerty|welcome|letmein|admin|administrator|changeme|default)[0-9!@#$%^&*._-]*$/', $raw) === 1;
    }

    private static function evaluate_signup_risk(array $data, array $payload): array
    {
        $score = 0;
        $reasons = [];
        $email = (string) ($data['business_email'] ?? '');
        $domain = self::email_domain($email);

        if ($domain !== '' && self::is_disposable_email_domain($domain)) {
            $score += 80;
            $reasons[] = 'disposable_email_domain';
        } elseif ($domain !== '' && self::is_suspicious_email_domain($domain)) {
            $score += 25;
            $reasons[] = 'suspicious_email_domain';
        }

        $ip_velocity = self::signup_ip_velocity_count();
        if ($ip_velocity >= self::SIGNUP_IP_VELOCITY_THRESHOLD) {
            $score += 45;
            $reasons[] = 'high_ip_signup_velocity';
        }

        if (self::is_suspicious_signup_user_agent((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
            $score += 45;
            $reasons[] = 'suspicious_user_agent';
        }

        if (self::signup_honeypot_filled($payload)) {
            $score += 90;
            $reasons[] = 'honeypot_field_filled';
        }

        $score = max(0, min(100, (int) apply_filters('abit_saas_auth_signup_risk_score', $score, $data, $payload, $reasons)));
        $reasons = array_values(array_unique(array_map('sanitize_key', $reasons)));
        $hold_threshold = (int) apply_filters('abit_saas_auth_signup_hold_threshold', self::SIGNUP_RISK_HOLD_THRESHOLD, $data, $payload);
        $challenge_threshold = (int) apply_filters('abit_saas_auth_signup_challenge_threshold', self::SIGNUP_RISK_CHALLENGE_THRESHOLD, $data, $payload);

        $action = 'allow';
        if ($score >= $hold_threshold) {
            $action = 'hold';
        } elseif ($score >= $challenge_threshold) {
            $action = 'challenge';
        }

        return [
            'score' => $score,
            'level' => $score >= $hold_threshold ? 'high' : ($score >= $challenge_threshold ? 'medium' : 'low'),
            'action' => $action,
            'reasons' => $reasons,
            'ip_velocity_count' => $ip_velocity,
        ];
    }

    private static function signup_ip_velocity_count(): int
    {
        $ip_hash = self::rate_limit_ip_hash();
        if ($ip_hash === '') {
            return 0;
        }

        return self::auth_rate_limit_event_count(
            'signup',
            gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS),
            '',
            $ip_hash,
            0
        );
    }

    private static function is_suspicious_signup_user_agent(string $user_agent): bool
    {
        $user_agent = strtolower(trim($user_agent));
        if ($user_agent === '') {
            return true;
        }

        if (strlen($user_agent) < 12) {
            return true;
        }

        return preg_match('/\b(bot|crawler|curl|headlesschrome|httpclient|libwww|masscan|python-requests|scrapy|spider|wget)\b/', $user_agent) === 1;
    }

    private static function signup_honeypot_filled(array $payload): bool
    {
        foreach (['website', 'url', 'homepage', 'confirm_email_address', 'company_website_optional'] as $key) {
            if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function signup_challenge_passed(array $payload, array $data): bool
    {
        $token = trim((string) ($payload['bot_challenge_token'] ?? $payload['challenge_token'] ?? ''));
        $response = strtolower(trim((string) ($payload['bot_challenge_response'] ?? $payload['challenge_response'] ?? '')));

        return $response === 'confirm_business_signup' && self::valid_signup_challenge_token($token, $data);
    }

    private static function signup_challenge_response(array $data, array $risk): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'message' => 'Please complete the additional signup check.',
                'code' => 'signup_challenge_required',
                'status' => 'challenge_required',
                'challenge' => [
                    'type' => 'text_confirmation',
                    'token' => self::signup_challenge_token($data),
                    'response_field' => 'bot_challenge_response',
                ],
                'risk' => [
                    'level' => $risk['level'],
                    'reasons' => $risk['reasons'],
                ],
            ],
            202
        );
    }

    private static function signup_challenge_token(array $data, ?int $bucket = null): string
    {
        $bucket = $bucket ?? (int) floor(time() / (15 * MINUTE_IN_SECONDS));
        return self::hmac(strtolower((string) ($data['business_email'] ?? '')) . '|' . self::request_ip() . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . $bucket);
    }

    private static function valid_signup_challenge_token(string $token, array $data): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $bucket = (int) floor(time() / (15 * MINUTE_IN_SECONDS));
        return hash_equals(self::signup_challenge_token($data, $bucket), $token)
            || hash_equals(self::signup_challenge_token($data, $bucket - 1), $token);
    }

    private static function audit_signup_risk_event(string $event_type, array $risk, array $data, int $user_id = 0, ?int $access_request_id = null, ?int $company_id = null): void
    {
        self::audit_event(
            $event_type,
            [
                'actor_user_id' => $user_id > 0 ? $user_id : null,
                'actor_type' => $user_id > 0 ? 'user' : 'anonymous',
                'entity_type' => 'access_request',
                'entity_id' => $access_request_id,
                'access_request_id' => $access_request_id,
                'company_id' => $company_id,
                'event_data' => [
                    'signup_flow_version' => 'api_v1',
                    'risk_score' => (int) ($risk['score'] ?? 0),
                    'risk_level' => (string) ($risk['level'] ?? ''),
                    'risk_action' => (string) ($risk['action'] ?? ''),
                    'risk_reasons' => (array) ($risk['reasons'] ?? []),
                    'ip_velocity_count' => (int) ($risk['ip_velocity_count'] ?? 0),
                    'email_domain_hash' => self::email_domain_hash((string) ($data['business_email'] ?? '')),
                ],
            ]
        );
    }

    private static function argon2id_available(): bool
    {
        if (!defined('PASSWORD_ARGON2ID') || !function_exists('password_algos')) {
            return false;
        }

        return in_array(PASSWORD_ARGON2ID, password_algos(), true);
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

    private static function audit_duplicate_signup_suppressed(string $email): void
    {
        self::audit_event(
            'auth_signup_duplicate_suppressed',
            [
                'actor_type' => 'anonymous',
                'entity_type' => 'access_request',
                'event_data' => [
                    'email_domain_hash' => self::email_domain_hash($email),
                    'signup_flow_version' => 'api_v1',
                    'response' => 'accepted_generic_response',
                ],
            ]
        );
    }

    private static function is_duplicate_user_email_error(WP_Error $error): bool
    {
        return in_array($error->get_error_code(), ['existing_user_email', 'email_exists'], true);
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

    private static function insert_access_request(array $data, int $user_id, int $company_id, string $now, array $signup_risk = []): int
    {
        global $wpdb;
        $legal = self::current_legal_versions();
        $risk_score = max(0, min(100, (int) ($signup_risk['score'] ?? 0)));
        $risk_action = sanitize_key((string) ($signup_risk['action'] ?? 'allow'));
        $risk_level = sanitize_key((string) ($signup_risk['level'] ?? 'low'));
        $risk_reasons = array_values(array_filter(array_map('sanitize_text_field', (array) ($signup_risk['reasons'] ?? []))));
        $review_status = $risk_action === 'hold' ? self::REVIEW_STATUS_ON_HOLD : self::REVIEW_STATUS_PENDING_EMAIL;
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
                'review_status' => $review_status,
                'handoff_priority' => 'normal',
                'handoff_queue_status' => 'unassigned',
                'terms_privacy_accepted_at' => $now,
                'terms_version' => $legal['terms_version'],
                'privacy_version' => $legal['privacy_version'],
                'signup_risk_score' => $risk_score,
                'signup_risk_level' => $risk_level,
                'signup_risk_action' => $risk_action,
                'signup_risk_reasons' => empty($risk_reasons) ? null : implode("\n", $risk_reasons),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
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

        $event_data = self::redact_sensitive_log_data(array_merge(
            [
                'delivery_channel' => 'email',
                'email_delivery_provider' => self::email_delivery_provider(),
                'sender_domain' => self::APPROVED_SENDER_DOMAIN,
                'logged_at' => current_time('mysql', true),
            ],
            $data
        ));

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

    private static function record_failed_login_attempt(string $email): void
    {
        $email = strtolower(trim($email));
        if ($email === '' || !is_email($email)) {
            return;
        }

        global $wpdb;
        $now = current_time('mysql', true);
        $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . self::table('access_requests') . ' SET failed_login_count = failed_login_count + 1, last_failed_login_at = %s, updated_at = %s WHERE business_email = %s',
                $now,
                $now,
                $email
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
        $domain = self::email_domain($email);
        return $domain === '' ? '' : self::hmac($domain);
    }

    private static function email_domain(string $email): string
    {
        $parts = explode('@', strtolower(trim($email)));
        if (count($parts) !== 2) {
            return '';
        }

        $domain = trim($parts[1], " \t\n\r\0\x0B.");
        return preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain) ? $domain : '';
    }

    private static function is_disposable_email_domain(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        $disposable_domains = [
            '10minutemail.com',
            '33mail.com',
            'dispostable.com',
            'emailondeck.com',
            'guerrillamail.com',
            'mailinator.com',
            'maildrop.cc',
            'moakt.com',
            'sharklasers.com',
            'tempmail.com',
            'temp-mail.org',
            'throwawaymail.com',
            'trashmail.com',
            'yopmail.com',
        ];

        return in_array($domain, $disposable_domains, true);
    }

    private static function is_suspicious_email_domain(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return false;
        }

        $free_domains = [
            'aol.com',
            'gmail.com',
            'hotmail.com',
            'icloud.com',
            'live.com',
            'mail.com',
            'outlook.com',
            'proton.me',
            'protonmail.com',
            'yahoo.com',
        ];
        if (in_array($domain, $free_domains, true)) {
            return true;
        }

        return preg_match('/\.(click|country|download|gq|icu|rest|ru|tk|top|work|xyz)$/', $domain) === 1;
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

    private static function normalize_date_value(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return new WP_Error('invalid_date', 'Invalid date.');
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));
        if (!checkdate($month, $day, $year)) {
            return new WP_Error('invalid_date', 'Invalid date.');
        }

        return $value;
    }

    private static function handoff_queue_status(string $review_status, int $admin_owner_user_id, string $handoff_team, string $follow_up_date): string
    {
        if ($review_status === self::REVIEW_STATUS_APPROVED || $review_status === self::REVIEW_STATUS_REJECTED) {
            return 'completed';
        }

        if ($review_status === self::REVIEW_STATUS_ON_HOLD) {
            return 'held';
        }

        if ($follow_up_date !== '' && strtotime($follow_up_date . ' 23:59:59 UTC') < time()) {
            return 'follow_up_due';
        }

        if ($admin_owner_user_id > 0 || $handoff_team !== '') {
            return 'assigned';
        }

        return 'unassigned';
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
