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
	define( 'ABITAI_AUTH_SCHEMA_VERSION', '2026.06.14.2' );
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

if ( ! function_exists( 'abitai_auth_hash_value' ) ) {
	/**
	 * Hash sensitive audit values before persistence.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function abitai_auth_hash_value( $value ) {
		$value = (string) $value;

		if ( defined( 'ABIT_SAAS_AUTH_HASH_KEY' ) && ABIT_SAAS_AUTH_HASH_KEY ) {
			$key = ABIT_SAAS_AUTH_HASH_KEY;
		} else {
			$key = wp_salt( 'auth' );
		}

		return hash_hmac( 'sha256', $value, $key );
	}
}

if ( ! function_exists( 'abitai_auth_hash_key_version' ) ) {
	/**
	 * Return the configured hash key version for audit rows.
	 *
	 * @return string
	 */
	function abitai_auth_hash_key_version() {
		return defined( 'ABIT_SAAS_AUTH_HASH_KEY_VERSION' ) ? ABIT_SAAS_AUTH_HASH_KEY_VERSION : 'wp-auth-salt-v1';
	}
}

if ( ! function_exists( 'abitai_auth_request_ip' ) ) {
	/**
	 * Resolve the request IP without storing the raw value.
	 *
	 * @return string
	 */
	function abitai_auth_request_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = trim( explode( ',', (string) $_SERVER[ $header ] )[0] );
			if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
				return $value;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'abitai_auth_redact_audit_data' ) ) {
	/**
	 * Normalize event metadata and remove raw sensitive values.
	 *
	 * @param array<string,mixed> $data Event metadata.
	 * @return array<string,mixed>
	 */
	function abitai_auth_redact_audit_data( $data ) {
		$redacted = array();

		foreach ( (array) $data as $key => $value ) {
			$key = sanitize_key( $key );

			if ( '' === $key || in_array( $key, array( 'password', 'token', 'raw_token', 'session_token', 'ip', 'user_agent' ), true ) ) {
				continue;
			}

			if ( in_array( $key, array( 'email', 'business_email' ), true ) ) {
				$email = strtolower( sanitize_email( (string) $value ) );
				if ( '' !== $email ) {
					$redacted[ $key . '_hash' ] = abitai_auth_hash_value( $email );
					$parts                     = explode( '@', $email );
					if ( isset( $parts[1] ) ) {
						$redacted['email_domain_hash'] = abitai_auth_hash_value( strtolower( $parts[1] ) );
					}
				}
				continue;
			}

			if ( is_bool( $value ) ) {
				$redacted[ $key ] = $value;
				continue;
			}

			if ( is_int( $value ) || is_float( $value ) ) {
				$redacted[ $key ] = $value;
				continue;
			}

			if ( is_array( $value ) ) {
				$redacted[ $key ] = array_map( 'sanitize_text_field', array_map( 'strval', array_values( $value ) ) );
				continue;
			}

			$redacted[ $key ] = sanitize_text_field( (string) $value );
		}

		return $redacted;
	}
}

if ( ! function_exists( 'abitai_auth_write_audit_log' ) ) {
	/**
	 * Write a redacted auth audit event row.
	 *
	 * @param string              $event_type Audit event type.
	 * @param array<string,mixed> $context Event context.
	 * @return int Inserted audit row ID, or 0 when unavailable.
	 */
	function abitai_auth_write_audit_log( $event_type, $context = array() ) {
		if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return 0;
		}

		global $wpdb;

		$tables = abitai_auth_schema_table_names();
		if ( empty( $tables['audit_logs'] ) ) {
			return 0;
		}

		$table_exists = $tables['audit_logs'] === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tables['audit_logs'] ) );
		if ( ! $table_exists ) {
			abitai_auth_maybe_install_schema();
		}

		$event_type        = sanitize_key( $event_type );
		$actor_user_id     = isset( $context['actor_user_id'] ) ? absint( $context['actor_user_id'] ) : get_current_user_id();
		$actor_type        = isset( $context['actor_type'] ) ? sanitize_key( $context['actor_type'] ) : ( $actor_user_id > 0 ? 'user' : 'anonymous' );
		$entity_type       = isset( $context['entity_type'] ) ? sanitize_key( $context['entity_type'] ) : 'auth';
		$entity_id         = isset( $context['entity_id'] ) ? absint( $context['entity_id'] ) : 0;
		$access_request_id = isset( $context['access_request_id'] ) ? absint( $context['access_request_id'] ) : 0;
		$company_id        = isset( $context['company_id'] ) ? absint( $context['company_id'] ) : 0;
		$session_id        = isset( $context['session_id'] ) ? absint( $context['session_id'] ) : 0;
		$event_data        = isset( $context['event_data'] ) && is_array( $context['event_data'] ) ? $context['event_data'] : array();

		$inserted = $wpdb->insert(
			$tables['audit_logs'],
			array(
				'audit_uuid'        => wp_generate_uuid4(),
				'actor_user_id'     => $actor_user_id > 0 ? $actor_user_id : null,
				'actor_type'        => $actor_type,
				'event_type'        => $event_type,
				'entity_type'       => $entity_type,
				'entity_id'         => $entity_id > 0 ? $entity_id : null,
				'access_request_id' => $access_request_id > 0 ? $access_request_id : null,
				'company_id'        => $company_id > 0 ? $company_id : null,
				'session_id'        => $session_id > 0 ? $session_id : null,
				'ip_hash'           => abitai_auth_hash_value( abitai_auth_request_ip() ),
				'user_agent_hash'   => abitai_auth_hash_value( isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '' ),
				'hash_key_version'  => abitai_auth_hash_key_version(),
				'event_data'        => wp_json_encode( abitai_auth_redact_audit_data( $event_data ) ),
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted ? 0 : absint( $wpdb->insert_id );
	}
}

if ( ! function_exists( 'abitai_auth_audit_status_meta_change' ) ) {
	/**
	 * Audit admin review decisions and email verification state changes persisted via user meta.
	 *
	 * @param int    $meta_id Meta row ID.
	 * @param int    $user_id User ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value New value.
	 */
	function abitai_auth_audit_status_meta_change( $meta_id, $user_id, $meta_key, $meta_value ) {
		if ( in_array( $meta_key, array( 'abitai_email_verified_at', 'abit_saas_email_verified_at' ), true ) && '' !== (string) $meta_value ) {
			abitai_auth_write_audit_log(
				'auth_email_verified',
				array(
					'actor_user_id' => absint( $user_id ),
					'actor_type'    => 'user',
					'entity_type'   => 'user',
					'entity_id'     => absint( $user_id ),
					'event_data'    => array(
						'result'      => 'success',
						'verified_at' => (string) $meta_value,
					),
				)
			);
			return;
		}

		if ( ! in_array( $meta_key, array( 'abitai_access_request_status', 'abit_saas_review_status' ), true ) ) {
			return;
		}

		$new_status        = sanitize_key( (string) $meta_value );
		$decision_statuses = array( 'approved_for_mvp_access', 'rejected', 'more_information_requested' );
		if ( ! in_array( $new_status, $decision_statuses, true ) ) {
			return;
		}

		abitai_auth_write_audit_log(
			'auth_admin_decision',
			array(
				'actor_user_id' => get_current_user_id(),
				'actor_type'    => current_user_can( 'manage_options' ) ? 'admin' : 'system',
				'entity_type'   => 'user',
				'entity_id'     => absint( $user_id ),
				'company_id'    => absint( get_user_meta( $user_id, 'abitai_company_id', true ) ),
				'event_data'    => array(
					'decision_status' => $new_status,
					'meta_key'        => $meta_key,
				),
			)
		);
	}
	add_action( 'added_user_meta', 'abitai_auth_audit_status_meta_change', 10, 4 );
	add_action( 'updated_user_meta', 'abitai_auth_audit_status_meta_change', 10, 4 );
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
