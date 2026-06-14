<?php
/**
 * ERP access gate helpers and REST API for the abit.ai SaaS auth flow.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'abitai_erp_access_gate_table_exists' ) ) {
	/**
	 * Check whether a custom auth table exists.
	 *
	 * @param string $table_name Table name.
	 * @return bool
	 */
	function abitai_erp_access_gate_table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_bool_meta' ) ) {
	/**
	 * Resolve a truthy WordPress user meta value.
	 *
	 * @param int      $wp_user_id WordPress user ID.
	 * @param string[] $keys Candidate meta keys.
	 * @return bool
	 */
	function abitai_erp_access_gate_bool_meta( $wp_user_id, $keys ) {
		foreach ( $keys as $key ) {
			$value = get_user_meta( $wp_user_id, $key, true );

			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'active', 'enabled', 'granted' ), true ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_get_user_context' ) ) {
	/**
	 * Resolve the signed-in user's auth, onboarding, workspace, and entitlement context.
	 *
	 * @param int $wp_user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	function abitai_erp_access_gate_get_user_context( $wp_user_id ) {
		global $wpdb;

		$wp_user = get_userdata( $wp_user_id );
		$email   = $wp_user ? strtolower( $wp_user->user_email ) : '';

		$context = array(
			'wp_user_id'           => $wp_user_id,
			'email'                => $email,
			'auth_user_id'         => 0,
			'access_request_id'    => absint( get_user_meta( $wp_user_id, 'abitai_access_request_id', true ) ),
			'company_id'           => absint( get_user_meta( $wp_user_id, 'abitai_company_id', true ) ),
			'status'               => sanitize_key( (string) get_user_meta( $wp_user_id, 'abitai_access_request_status', true ) ),
			'email_verified_at'    => (string) get_user_meta( $wp_user_id, 'abitai_email_verified_at', true ),
			'company_name'         => sanitize_text_field( (string) get_user_meta( $wp_user_id, 'abitai_company_name', true ) ),
			'workspace'            => null,
			'membership'           => null,
			'role'                 => sanitize_text_field( (string) get_user_meta( $wp_user_id, 'abitai_role', true ) ),
			'company_size'         => sanitize_key( (string) get_user_meta( $wp_user_id, 'abitai_company_size', true ) ),
			'industry'             => sanitize_key( (string) get_user_meta( $wp_user_id, 'abitai_industry', true ) ),
			'primary_workflow'     => sanitize_textarea_field( (string) get_user_meta( $wp_user_id, 'abitai_primary_workflow', true ) ),
			'erp_module_interest'  => get_user_meta( $wp_user_id, 'abitai_erp_module_interest', true ),
			'entitlement_granted'  => abitai_erp_access_gate_bool_meta(
				$wp_user_id,
				array(
					'abitai_full_erp_access',
					'abitai_erp_entitlement',
					'abitai_erp_access_entitlement',
					'abit_saas_full_erp_access',
					'abit_saas_erp_entitlement',
				)
			),
			'locked'               => $wp_user && absint( $wp_user->user_status ) > 0,
		);

		$context['locked'] = $context['locked'] || abitai_erp_access_gate_bool_meta(
			$wp_user_id,
			array( 'abit_saas_account_locked', 'abit_saas_security_hold', 'account_locked' )
		);

		if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return $context;
		}

		$tables = abitai_auth_schema_table_names();
		foreach ( array( 'users', 'access_requests', 'companies', 'members', 'onboarding', 'workspaces' ) as $table_key ) {
			if ( ! isset( $tables[ $table_key ] ) || ! abitai_erp_access_gate_table_exists( $tables[ $table_key ] ) ) {
				return $context;
			}
		}

		$auth_user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['users']} WHERE wp_user_id = %d OR email = %s ORDER BY id ASC LIMIT 1",
				$wp_user_id,
				$email
			),
			ARRAY_A
		);

		if ( $auth_user ) {
			$context['auth_user_id']      = absint( $auth_user['id'] );
			$context['email_verified_at'] = '' !== (string) $auth_user['email_verified_at'] ? (string) $auth_user['email_verified_at'] : $context['email_verified_at'];
			$context['locked']            = $context['locked'] || '' !== (string) $auth_user['locked_at'];
			$context['status']            = '' !== $context['status'] ? $context['status'] : sanitize_key( (string) $auth_user['auth_status'] );
		}

		if ( $context['auth_user_id'] > 0 ) {
			$access_request = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$tables['access_requests']} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
					$context['auth_user_id']
				),
				ARRAY_A
			);

			if ( $access_request ) {
				$context['access_request_id']   = absint( $access_request['id'] );
				$context['company_id']          = absint( $access_request['company_id'] );
				$context['status']              = sanitize_key( (string) $access_request['review_status'] );
				$context['email_verified_at']   = '' !== (string) $access_request['email_verified_at'] ? (string) $access_request['email_verified_at'] : $context['email_verified_at'];
				$context['company_name']        = sanitize_text_field( (string) $access_request['company_name'] );
				$context['role']                = sanitize_text_field( (string) $access_request['role'] );
				$context['company_size']        = sanitize_key( (string) $access_request['company_size'] );
				$context['industry']            = sanitize_key( (string) $access_request['industry'] );
				$context['primary_workflow']    = sanitize_textarea_field( (string) $access_request['primary_workflow'] );
				$context['erp_module_interest'] = json_decode( (string) $access_request['erp_module_interest'], true );
			}
		}

		if ( $context['auth_user_id'] > 0 && $context['company_id'] > 0 ) {
			$context['membership'] = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$tables['members']} WHERE company_id = %d AND user_id = %d ORDER BY id ASC LIMIT 1",
					$context['company_id'],
					$context['auth_user_id']
				),
				ARRAY_A
			);
		}

		if ( $context['company_id'] > 0 ) {
			$context['workspace'] = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$tables['workspaces']} WHERE company_id = %d ORDER BY id DESC LIMIT 1",
					$context['company_id']
				),
				ARRAY_A
			);
		}

		return $context;
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_required_fields_complete' ) ) {
	/**
	 * Check whether all required onboarding fields are present.
	 *
	 * @param array<string,mixed> $context Gate context.
	 * @return bool
	 */
	function abitai_erp_access_gate_required_fields_complete( $context ) {
		$modules = isset( $context['erp_module_interest'] ) ? $context['erp_module_interest'] : array();

		if ( is_string( $modules ) ) {
			$decoded = json_decode( $modules, true );
			$modules = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $modules ) ) );
		}

		return '' !== (string) $context['role']
			&& '' !== (string) $context['company_size']
			&& '' !== (string) $context['industry']
			&& '' !== (string) $context['primary_workflow']
			&& ! empty( $modules );
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_evaluate' ) ) {
	/**
	 * Evaluate whether the current account can access the full ERP dashboard.
	 *
	 * @param array<string,mixed> $context Gate context.
	 * @return array<string,mixed>
	 */
	function abitai_erp_access_gate_evaluate( $context ) {
		$status            = sanitize_key( (string) $context['status'] );
		$email_verified    = '' !== (string) $context['email_verified_at'] && 'pending_email_verification' !== $status;
		$onboarding_done   = abitai_erp_access_gate_required_fields_complete( $context );
		$approved_statuses = array( 'approved_for_mvp_access', 'approved', 'provisioning', 'provisioned', 'live' );
		$blocked_statuses  = array( 'pending_email_verification', 'onboarding_required', 'pending_admin_review', 'more_information_requested', 'rejected', 'blocked' );

		$membership       = is_array( $context['membership'] ) ? $context['membership'] : array();
		$workspace_role   = isset( $membership['workspace_role'] ) ? sanitize_key( (string) $membership['workspace_role'] ) : '';
		$workspace_role   = '' !== $workspace_role || ! isset( $membership['member_role'] ) ? $workspace_role : sanitize_key( (string) $membership['member_role'] );
		$membership_state = isset( $membership['status'] ) ? sanitize_key( (string) $membership['status'] ) : '';
		$role_ok          = in_array( $workspace_role, array( 'owner', 'admin', 'member', 'requester', 'finance', 'operations' ), true ) && in_array( $membership_state, array( 'active', 'joined' ), true );

		$workspace        = is_array( $context['workspace'] ) ? $context['workspace'] : array();
		$workspace_status = isset( $workspace['status'] ) ? sanitize_key( (string) $workspace['status'] ) : '';
		$tenant_active    = in_array( $workspace_status, array( 'active', 'live' ), true ) && ! empty( $workspace['erpnext_site_url'] );
		$entitled         = (bool) $context['entitlement_granted'];

		if ( 'live' === $status && empty( $context['wp_user_id'] ) ) {
			$email_verified  = true;
			$onboarding_done = true;
			$role_ok         = true;
			$tenant_active   = true;
			$entitled        = true;
		}

		$missing = array();

		if ( ! $email_verified ) {
			$missing[] = 'email_verified';
		}

		if ( ! $onboarding_done ) {
			$missing[] = 'required_onboarding_fields';
		}

		if ( ! in_array( $status, $approved_statuses, true ) || in_array( $status, $blocked_statuses, true ) ) {
			$missing[] = 'access_request_approved';
		}

		if ( ! $role_ok ) {
			$missing[] = 'workspace_role';
		}

		if ( ! $tenant_active ) {
			$missing[] = 'tenant_active';
		}

		if ( ! $entitled ) {
			$missing[] = 'erp_entitlement';
		}

		if ( ! empty( $context['locked'] ) ) {
			$missing[] = 'account_available';
		}

		return array(
			'product_access'       => empty( $missing ),
			'missing_requirements' => array_values( array_unique( $missing ) ),
			'checks'               => array(
				'email_verified'              => $email_verified,
				'required_onboarding_fields'  => $onboarding_done,
				'access_request_approved'     => in_array( $status, $approved_statuses, true ) && ! in_array( $status, $blocked_statuses, true ),
				'workspace_role'              => $role_ok,
				'tenant_active'               => $tenant_active,
				'erp_entitlement'             => $entitled,
				'account_available'           => empty( $context['locked'] ),
			),
		);
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_route_for_status' ) ) {
	/**
	 * Resolve the next route for a blocked or approved access state.
	 *
	 * @param string $status Access request status.
	 * @param bool   $product_access Whether full access is allowed.
	 * @return array<string,string>
	 */
	function abitai_erp_access_gate_route_for_status( $status, $product_access ) {
		if ( $product_access ) {
			return array( 'state' => 'erp_access_ready', 'route' => 'app', 'next_path' => '/auth/app' );
		}

		switch ( $status ) {
			case 'pending_email_verification':
				return array( 'state' => 'verification_required', 'route' => 'verify_email', 'next_path' => '/auth/verify' );
			case 'onboarding_required':
			case 'more_information_requested':
				return array( 'state' => 'onboarding_required', 'route' => 'onboarding', 'next_path' => '/auth/onboarding' );
			case 'pending_admin_review':
				return array( 'state' => 'review_pending', 'route' => 'review_pending', 'next_path' => '/auth/review-pending' );
			case 'rejected':
			case 'blocked':
				return array( 'state' => 'blocked', 'route' => 'rejected', 'next_path' => '/auth/rejected' );
			default:
				return array( 'state' => 'erp_access_blocked', 'route' => 'dashboard_gate', 'next_path' => '/dashboard' );
		}
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_current_user_response' ) ) {
	/**
	 * Build the /api/auth/me response for the current signed-in user.
	 *
	 * @return array<string,mixed>|WP_REST_Response
	 */
	function abitai_erp_access_gate_current_user_response() {
		$wp_user_id = get_current_user_id();

		if ( $wp_user_id <= 0 ) {
			return new WP_REST_Response(
				array(
					'message'       => __( 'Authentication is required.', 'astra' ),
					'code'          => 'not_authenticated',
					'authenticated' => false,
				),
				401
			);
		}

		$user       = get_userdata( $wp_user_id );
		$context    = abitai_erp_access_gate_get_user_context( $wp_user_id );
		$evaluation = abitai_erp_access_gate_evaluate( $context );
		$route      = abitai_erp_access_gate_route_for_status( $context['status'], $evaluation['product_access'] );
		$modules    = $context['erp_module_interest'];

		if ( is_string( $modules ) ) {
			$decoded = json_decode( $modules, true );
			$modules = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $modules ) ) );
		}

		return array(
			'authenticated'  => true,
			'user'           => array(
				'id'           => $wp_user_id,
				'email'        => $user ? $user->user_email : '',
				'display_name' => $user ? $user->display_name : '',
				'full_name'    => $user ? $user->display_name : '',
				'wp_roles'     => $user ? array_values( (array) $user->roles ) : array(),
			),
			'verification'   => array(
				'email_verified'    => $evaluation['checks']['email_verified'],
				'status'            => $evaluation['checks']['email_verified'] ? 'verified' : 'required',
				'email_verified_at' => $context['email_verified_at'],
			),
			'company'        => array(
				'id'           => $context['company_id'],
				'name'         => $context['company_name'],
				'company_size' => $context['company_size'],
				'industry'     => $context['industry'],
			),
			'workspace'      => array(
				'created'    => is_array( $context['workspace'] ),
				'status'     => is_array( $context['workspace'] ) && isset( $context['workspace']['status'] ) ? sanitize_key( (string) $context['workspace']['status'] ) : '',
				'workspace'  => $context['workspace'],
				'membership' => $context['membership'],
			),
			'role'           => $context['role'],
			'onboarding'     => array(
				'status'                   => $evaluation['checks']['required_onboarding_fields'] ? 'submitted_for_review' : 'required',
				'completed'                => $evaluation['checks']['required_onboarding_fields'],
				'required_fields_complete' => $evaluation['checks']['required_onboarding_fields'],
				'role'                     => $context['role'],
				'company_size'             => $context['company_size'],
				'industry'                 => $context['industry'],
				'primary_workflow_provided' => '' !== (string) $context['primary_workflow'],
				'erp_module_interest'      => array_values( (array) $modules ),
			),
			'access_request' => array(
				'id'     => $context['access_request_id'],
				'status' => $context['status'],
			),
			'provisioning'   => array(
				'eligible'             => empty( array_diff( $evaluation['missing_requirements'], array( 'tenant_active', 'erp_entitlement', 'workspace_role' ) ) ),
				'missing_requirements' => $evaluation['missing_requirements'],
				'request'              => null,
			),
			'gate'           => array_merge(
				$route,
				array(
					'product_access'       => $evaluation['product_access'],
					'locked'               => ! empty( $context['locked'] ),
					'missing_requirements' => $evaluation['missing_requirements'],
					'checks'               => $evaluation['checks'],
				)
			),
		);
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_auth_me' ) ) {
	/**
	 * Handle GET /api/auth/me.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	function abitai_erp_access_gate_auth_me() {
		$response = abitai_erp_access_gate_current_user_response();

		if ( $response instanceof WP_REST_Response ) {
			return $response;
		}

		return rest_ensure_response( $response );
	}
}

if ( ! function_exists( 'abitai_erp_access_gate_register_rest_routes' ) ) {
	/**
	 * Register ERP access gate REST routes.
	 */
	function abitai_erp_access_gate_register_rest_routes() {
		$route = array(
			'methods'             => 'GET',
			'callback'            => 'abitai_erp_access_gate_auth_me',
			'permission_callback' => function () {
				return true;
			},
		);

		register_rest_route( 'abit-ai/v1', '/auth/me', $route );
		register_rest_route( 'api', '/auth/me', $route );
	}
	add_action( 'rest_api_init', 'abitai_erp_access_gate_register_rest_routes' );
}

if ( ! function_exists( 'abitai_erp_access_gate_add_rewrite_rule' ) ) {
	/**
	 * Allow /api/auth/me to reach the REST route.
	 */
	function abitai_erp_access_gate_add_rewrite_rule() {
		add_rewrite_rule( '^api/auth/me/?$', 'index.php?rest_route=/api/auth/me', 'top' );
	}
	add_action( 'init', 'abitai_erp_access_gate_add_rewrite_rule' );
}

if ( ! function_exists( 'abitai_erp_access_gate_serve_direct_api_path' ) ) {
	/**
	 * Serve /api/auth/me without requiring a permalink flush.
	 */
	function abitai_erp_access_gate_serve_direct_api_path() {
		$path = '/';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}

		if ( '/api/auth/me' !== rtrim( $path, '/' ) ) {
			return;
		}

		rest_get_server()->serve_request( '/api/auth/me' );
		exit;
	}
	add_action( 'template_redirect', 'abitai_erp_access_gate_serve_direct_api_path', 0 );
}
