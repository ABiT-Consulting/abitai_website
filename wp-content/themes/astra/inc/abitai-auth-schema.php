<?php
/**
 * Database schema for the abit.ai SaaS auth and company access model.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ABITAI_AUTH_SCHEMA_VERSION' ) ) {
	define( 'ABITAI_AUTH_SCHEMA_VERSION', '2026.06.14.1' );
}

if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
	/**
	 * Return all auth schema table names using the active WordPress DB prefix.
	 *
	 * @return array<string,string>
	 */
	function abitai_auth_schema_table_names() {
		global $wpdb;

		return array(
			'access_requests' => $wpdb->prefix . 'abitai_access_requests',
			'users'           => $wpdb->prefix . 'abitai_auth_users',
			'companies'       => $wpdb->prefix . 'abitai_companies',
			'members'         => $wpdb->prefix . 'abitai_company_members',
			'tokens'          => $wpdb->prefix . 'abitai_auth_tokens',
			'sessions'        => $wpdb->prefix . 'abitai_auth_sessions',
			'consent'         => $wpdb->prefix . 'abitai_consent_audit_records',
			'onboarding'      => $wpdb->prefix . 'abitai_onboarding_profiles',
			'workspaces'      => $wpdb->prefix . 'abitai_tenant_workspaces',
			'audit_logs'      => $wpdb->prefix . 'abitai_audit_logs',
		);
	}
}

if ( ! function_exists( 'abitai_auth_schema_definitions' ) ) {
	/**
	 * Build CREATE TABLE statements for auth schema migration.
	 *
	 * @return string[]
	 */
	function abitai_auth_schema_definitions() {
		global $wpdb;

		$tables          = abitai_auth_schema_table_names();
		$charset_collate = $wpdb->get_charset_collate();

		return array(
			"CREATE TABLE {$tables['access_requests']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				request_uuid char(36) NOT NULL,
				user_id bigint(20) unsigned DEFAULT NULL,
				company_id bigint(20) unsigned DEFAULT NULL,
				full_name varchar(120) NOT NULL,
				business_email varchar(254) NOT NULL,
				business_email_hash varchar(128) DEFAULT NULL,
				company_name varchar(160) NOT NULL,
				country_region varchar(32) NOT NULL,
				intended_use_case text NOT NULL,
				role varchar(120) DEFAULT NULL,
				company_size varchar(32) DEFAULT NULL,
				industry varchar(64) DEFAULT NULL,
				primary_workflow text,
				erp_module_interest longtext,
				current_system varchar(160) DEFAULT NULL,
				timeline varchar(32) DEFAULT NULL,
				notes text,
				persona varchar(64) DEFAULT NULL,
				review_status varchar(64) NOT NULL DEFAULT 'pending_email_verification',
				email_verified_at datetime DEFAULT NULL,
				terms_privacy_accepted_at datetime DEFAULT NULL,
				terms_version varchar(64) DEFAULT NULL,
				privacy_version varchar(64) DEFAULT NULL,
				latest_consent_audit_record_id bigint(20) unsigned DEFAULT NULL,
				admin_decision_reason text,
				admin_reviewed_by bigint(20) unsigned DEFAULT NULL,
				admin_reviewed_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY request_uuid (request_uuid),
				KEY business_email (business_email(191)),
				KEY user_id (user_id),
				KEY company_id (company_id),
				KEY review_status (review_status),
				KEY email_verified_at (email_verified_at),
				KEY latest_consent_audit_record_id (latest_consent_audit_record_id),
				KEY created_at (created_at)
			) $charset_collate",
			"CREATE TABLE {$tables['users']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_uuid char(36) NOT NULL,
				wp_user_id bigint(20) unsigned DEFAULT NULL,
				primary_access_request_id bigint(20) unsigned DEFAULT NULL,
				email varchar(254) NOT NULL,
				email_hash varchar(128) DEFAULT NULL,
				password_hash varchar(255) DEFAULT NULL,
				full_name varchar(120) NOT NULL,
				auth_status varchar(64) NOT NULL DEFAULT 'pending_email_verification',
				email_verified_at datetime DEFAULT NULL,
				last_login_at datetime DEFAULT NULL,
				locked_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_uuid (user_uuid),
				UNIQUE KEY email (email(191)),
				KEY wp_user_id (wp_user_id),
				KEY primary_access_request_id (primary_access_request_id),
				KEY auth_status (auth_status),
				KEY email_verified_at (email_verified_at)
			) $charset_collate",
			"CREATE TABLE {$tables['companies']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_uuid char(36) NOT NULL,
				name varchar(160) NOT NULL,
				country_region varchar(32) NOT NULL,
				industry varchar(64) DEFAULT NULL,
				company_size varchar(32) DEFAULT NULL,
				website_url varchar(255) DEFAULT NULL,
				status varchar(64) NOT NULL DEFAULT 'pending_review',
				created_by_user_id bigint(20) unsigned DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY company_uuid (company_uuid),
				KEY name (name(120)),
				KEY country_region (country_region),
				KEY industry (industry),
				KEY company_size (company_size),
				KEY status (status),
				KEY created_by_user_id (created_by_user_id)
			) $charset_collate",
			"CREATE TABLE {$tables['members']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				access_request_id bigint(20) unsigned DEFAULT NULL,
				member_role varchar(64) NOT NULL DEFAULT 'requester',
				workspace_role varchar(64) DEFAULT NULL,
				status varchar(64) NOT NULL DEFAULT 'pending_review',
				invited_by_user_id bigint(20) unsigned DEFAULT NULL,
				joined_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY company_user (company_id,user_id),
				KEY user_id (user_id),
				KEY access_request_id (access_request_id),
				KEY member_role (member_role),
				KEY workspace_role (workspace_role),
				KEY status (status),
				KEY invited_by_user_id (invited_by_user_id)
			) $charset_collate",
			"CREATE TABLE {$tables['tokens']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				token_uuid char(36) NOT NULL,
				user_id bigint(20) unsigned DEFAULT NULL,
				access_request_id bigint(20) unsigned DEFAULT NULL,
				token_type varchar(64) NOT NULL,
				token_hash varchar(255) NOT NULL,
				delivery_channel varchar(32) NOT NULL DEFAULT 'email',
				issue_reason varchar(64) DEFAULT NULL,
				expires_at datetime NOT NULL,
				consumed_at datetime DEFAULT NULL,
				consumed_by_session_id bigint(20) unsigned DEFAULT NULL,
				created_ip_hash varchar(128) DEFAULT NULL,
				created_user_agent_hash varchar(128) DEFAULT NULL,
				hash_key_version varchar(64) DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY token_uuid (token_uuid),
				UNIQUE KEY token_hash (token_hash(191)),
				KEY user_id (user_id),
				KEY access_request_id (access_request_id),
				KEY token_type (token_type),
				KEY expires_at (expires_at),
				KEY consumed_at (consumed_at)
			) $charset_collate",
			"CREATE TABLE {$tables['sessions']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_uuid char(36) NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				company_id bigint(20) unsigned DEFAULT NULL,
				session_token_hash varchar(255) NOT NULL,
				auth_method varchar(64) NOT NULL DEFAULT 'email_password',
				ip_hash varchar(128) DEFAULT NULL,
				user_agent_hash varchar(128) DEFAULT NULL,
				hash_key_version varchar(64) DEFAULT NULL,
				started_at datetime NOT NULL,
				last_seen_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				revoked_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY session_uuid (session_uuid),
				UNIQUE KEY session_token_hash (session_token_hash(191)),
				KEY user_id (user_id),
				KEY company_id (company_id),
				KEY expires_at (expires_at),
				KEY revoked_at (revoked_at),
				KEY last_seen_at (last_seen_at)
			) $charset_collate",
			"CREATE TABLE {$tables['consent']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				consent_uuid char(36) NOT NULL,
				access_request_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned DEFAULT NULL,
				business_email_hash varchar(128) NOT NULL,
				terms_version varchar(64) NOT NULL,
				privacy_version varchar(64) NOT NULL,
				consent_text_version varchar(64) NOT NULL,
				legal_locale varchar(16) NOT NULL,
				accepted_at datetime NOT NULL,
				ip_hash varchar(128) NOT NULL,
				user_agent_hash varchar(128) NOT NULL,
				hash_key_version varchar(64) NOT NULL,
				capture_source varchar(64) NOT NULL,
				retention_rule varchar(128) NOT NULL DEFAULT 'active_plus_7_years',
				legal_hold_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY consent_uuid (consent_uuid),
				KEY access_request_id (access_request_id),
				KEY user_id (user_id),
				KEY business_email_hash (business_email_hash),
				KEY accepted_at (accepted_at),
				KEY terms_privacy_versions (terms_version,privacy_version),
				KEY capture_source (capture_source),
				KEY legal_hold_at (legal_hold_at)
			) $charset_collate",
			"CREATE TABLE {$tables['onboarding']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				access_request_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				company_id bigint(20) unsigned DEFAULT NULL,
				role varchar(120) NOT NULL,
				company_size varchar(32) NOT NULL,
				industry varchar(64) NOT NULL,
				primary_workflow text NOT NULL,
				erp_module_interest longtext NOT NULL,
				current_system varchar(160) DEFAULT NULL,
				timeline varchar(32) DEFAULT NULL,
				notes text,
				completed_step varchar(64) NOT NULL DEFAULT 'erp_needs',
				completed_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY access_request_id (access_request_id),
				KEY user_id (user_id),
				KEY company_id (company_id),
				KEY company_size (company_size),
				KEY industry (industry),
				KEY completed_at (completed_at)
			) $charset_collate",
			"CREATE TABLE {$tables['workspaces']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				workspace_uuid char(36) NOT NULL,
				company_id bigint(20) unsigned NOT NULL,
				approved_access_request_id bigint(20) unsigned DEFAULT NULL,
				slug varchar(120) NOT NULL,
				display_name varchar(160) NOT NULL,
				status varchar(64) NOT NULL DEFAULT 'manual_pending',
				provisioning_mode varchar(64) NOT NULL DEFAULT 'manual',
				erpnext_site_name varchar(190) DEFAULT NULL,
				erpnext_site_url varchar(255) DEFAULT NULL,
				approved_by_user_id bigint(20) unsigned DEFAULT NULL,
				approved_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY workspace_uuid (workspace_uuid),
				UNIQUE KEY slug (slug),
				KEY company_id (company_id),
				KEY approved_access_request_id (approved_access_request_id),
				KEY status (status),
				KEY provisioning_mode (provisioning_mode),
				KEY erpnext_site_name (erpnext_site_name),
				KEY approved_by_user_id (approved_by_user_id)
			) $charset_collate",
			"CREATE TABLE {$tables['audit_logs']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				audit_uuid char(36) NOT NULL,
				actor_user_id bigint(20) unsigned DEFAULT NULL,
				actor_type varchar(64) NOT NULL DEFAULT 'system',
				event_type varchar(96) NOT NULL,
				entity_type varchar(64) NOT NULL,
				entity_id bigint(20) unsigned DEFAULT NULL,
				access_request_id bigint(20) unsigned DEFAULT NULL,
				company_id bigint(20) unsigned DEFAULT NULL,
				session_id bigint(20) unsigned DEFAULT NULL,
				ip_hash varchar(128) DEFAULT NULL,
				user_agent_hash varchar(128) DEFAULT NULL,
				hash_key_version varchar(64) DEFAULT NULL,
				event_data longtext,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY audit_uuid (audit_uuid),
				KEY actor_user_id (actor_user_id),
				KEY event_type (event_type),
				KEY entity (entity_type,entity_id),
				KEY access_request_id (access_request_id),
				KEY company_id (company_id),
				KEY session_id (session_id),
				KEY created_at (created_at)
			) $charset_collate",
		);
	}
}

if ( ! function_exists( 'abitai_auth_install_schema' ) ) {
	/**
	 * Create or update all auth schema tables and indexes.
	 *
	 * @return array<int,string> dbDelta change messages.
	 */
	function abitai_auth_install_schema() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$results = dbDelta( abitai_auth_schema_definitions() );

		update_option( 'abitai_auth_schema_version', ABITAI_AUTH_SCHEMA_VERSION, false );

		return $results;
	}
}

if ( ! function_exists( 'abitai_auth_maybe_install_schema' ) ) {
	/**
	 * Run schema migration when the deployed schema version changes.
	 */
	function abitai_auth_maybe_install_schema() {
		$installed_version = (string) get_option( 'abitai_auth_schema_version', '' );

		if ( ABITAI_AUTH_SCHEMA_VERSION !== $installed_version ) {
			abitai_auth_install_schema();
		}
	}
	add_action( 'after_switch_theme', 'abitai_auth_maybe_install_schema' );
	add_action( 'admin_init', 'abitai_auth_maybe_install_schema' );
	add_action( 'init', 'abitai_auth_maybe_install_schema', 5 );
}
