<?php
/**
 * Company profile REST API for the abit.ai SaaS access flow.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'abitai_company_profile_get_module_options' ) ) {
	/**
	 * Return ERP module keys accepted by the company profile API.
	 *
	 * @return string[]
	 */
	function abitai_company_profile_get_module_options() {
		return array(
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
			'finance',
			'hr',
			'inventory',
			'purchasing',
			'reporting',
		);
	}
}

if ( ! function_exists( 'abitai_company_profile_get_timeline_options' ) ) {
	/**
	 * Return optional implementation timeline keys.
	 *
	 * @return string[]
	 */
	function abitai_company_profile_get_timeline_options() {
		return array( 'immediate', '1_3_months', '3_6_months', '6_plus_months', 'exploring' );
	}
}

if ( ! function_exists( 'abitai_company_profile_api_error' ) ) {
	/**
	 * Build a REST API error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Human-readable message.
	 * @param int    $status HTTP status.
	 * @param array  $extra Extra error data.
	 * @return WP_Error
	 */
	function abitai_company_profile_api_error( $code, $message, $status, $extra = array() ) {
		return new WP_Error(
			$code,
			$message,
			array_merge(
				array(
					'status' => $status,
				),
				$extra
			)
		);
	}
}

if ( ! function_exists( 'abitai_company_profile_table_exists' ) ) {
	/**
	 * Check whether a custom auth table exists.
	 *
	 * @param string $table_name Table name.
	 * @return bool
	 */
	function abitai_company_profile_table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}
}

if ( ! function_exists( 'abitai_company_profile_validate_payload' ) ) {
	/**
	 * Validate and normalize a company profile request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>|WP_Error
	 */
	function abitai_company_profile_validate_payload( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		$payload = array(
			'company_name'        => isset( $params['company_name'] ) ? sanitize_text_field( wp_unslash( $params['company_name'] ) ) : '',
			'country_region'      => isset( $params['country_region'] ) ? strtoupper( sanitize_text_field( wp_unslash( $params['country_region'] ) ) ) : '',
			'role'                => isset( $params['role'] ) ? sanitize_text_field( wp_unslash( $params['role'] ) ) : '',
			'company_size'        => isset( $params['company_size'] ) ? sanitize_key( wp_unslash( $params['company_size'] ) ) : '',
			'industry'            => isset( $params['industry'] ) ? sanitize_key( wp_unslash( $params['industry'] ) ) : '',
			'primary_workflow'    => isset( $params['primary_workflow'] ) ? sanitize_textarea_field( wp_unslash( $params['primary_workflow'] ) ) : '',
			'current_system'      => isset( $params['current_system'] ) ? sanitize_text_field( wp_unslash( $params['current_system'] ) ) : '',
			'timeline'            => isset( $params['timeline'] ) ? sanitize_key( wp_unslash( $params['timeline'] ) ) : '',
			'notes'               => isset( $params['notes'] ) ? sanitize_textarea_field( wp_unslash( $params['notes'] ) ) : '',
			'erp_module_interest' => array(),
		);

		if ( isset( $params['erp_module_interest'] ) && is_array( $params['erp_module_interest'] ) ) {
			$payload['erp_module_interest'] = array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $params['erp_module_interest'] ) ) ) );
		}

		$errors           = array();
		$valid_sizes      = function_exists( 'abitai_get_company_size_options' ) ? array_keys( abitai_get_company_size_options() ) : array( '1_10', '11_50', '51_200', '201_500', '501_plus' );
		$valid_industries = function_exists( 'abitai_get_industry_options' ) ? array_keys( abitai_get_industry_options() ) : array( 'professional_services', 'trading_distribution', 'manufacturing', 'retail_ecommerce', 'construction_real_estate', 'healthcare', 'education', 'nonprofit', 'technology', 'other' );
		$valid_countries  = function_exists( 'abitai_get_country_region_options' ) ? array_keys( abitai_get_country_region_options() ) : array();
		$valid_modules    = abitai_company_profile_get_module_options();
		$valid_timelines  = abitai_company_profile_get_timeline_options();

		if ( strlen( $payload['company_name'] ) < 2 || strlen( $payload['company_name'] ) > 160 || abitai_text_has_unsafe_content( $payload['company_name'] ) ) {
			$errors['company_name'] = __( 'Enter a valid company name.', 'astra' );
		}

		if ( '' === $payload['country_region'] || ( ! empty( $valid_countries ) && ! in_array( $payload['country_region'], $valid_countries, true ) ) ) {
			$errors['country_region'] = __( 'Select a valid country or region.', 'astra' );
		}

		if ( strlen( $payload['role'] ) < 2 || strlen( $payload['role'] ) > 120 || abitai_text_has_unsafe_content( $payload['role'] ) ) {
			$errors['role'] = __( 'Enter a valid role.', 'astra' );
		}

		if ( ! in_array( $payload['company_size'], $valid_sizes, true ) ) {
			$errors['company_size'] = __( 'Select a valid company size.', 'astra' );
		}

		if ( ! in_array( $payload['industry'], $valid_industries, true ) ) {
			$errors['industry'] = __( 'Select a valid industry.', 'astra' );
		}

		if ( strlen( $payload['primary_workflow'] ) < 20 || strlen( $payload['primary_workflow'] ) > 1000 || abitai_text_has_unsafe_content( $payload['primary_workflow'] ) ) {
			$errors['primary_workflow'] = __( 'Describe a valid primary workflow.', 'astra' );
		}

		if ( empty( $payload['erp_module_interest'] ) || array_diff( $payload['erp_module_interest'], $valid_modules ) ) {
			$errors['erp_module_interest'] = __( 'Select at least one valid ERP module interest.', 'astra' );
		}

		if ( strlen( $payload['current_system'] ) > 160 || ( '' !== $payload['current_system'] && abitai_text_has_unsafe_content( $payload['current_system'] ) ) ) {
			$errors['current_system'] = __( 'Enter a valid current system.', 'astra' );
		}

		if ( '' !== $payload['timeline'] && ! in_array( $payload['timeline'], $valid_timelines, true ) ) {
			$errors['timeline'] = __( 'Select a valid timeline.', 'astra' );
		}

		if ( strlen( $payload['notes'] ) > 2000 || ( '' !== $payload['notes'] && abitai_text_has_unsafe_content( $payload['notes'] ) ) ) {
			$errors['notes'] = __( 'Enter valid notes.', 'astra' );
		}

		if ( ! empty( $errors ) ) {
			return abitai_company_profile_api_error(
				'abitai_company_profile_validation_failed',
				__( 'Company profile validation failed.', 'astra' ),
				400,
				array( 'fields' => $errors )
			);
		}

		return $payload;
	}
}

if ( ! function_exists( 'abitai_company_profile_get_owned_company_id' ) ) {
	/**
	 * Resolve the company ID owned by the current WordPress user.
	 *
	 * @param int $wp_user_id WordPress user ID.
	 * @return int
	 */
	function abitai_company_profile_get_owned_company_id( $wp_user_id ) {
		global $wpdb;

		$company_id = absint( get_user_meta( $wp_user_id, 'abitai_company_id', true ) );

		if ( $company_id > 0 || ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return $company_id;
		}

		$tables = abitai_auth_schema_table_names();

		if ( ! abitai_company_profile_table_exists( $tables['users'] ) || ! abitai_company_profile_table_exists( $tables['members'] ) ) {
			return 0;
		}

		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT m.company_id
					FROM {$tables['members']} m
					INNER JOIN {$tables['users']} u ON u.id = m.user_id
					WHERE u.wp_user_id = %d
					ORDER BY m.id ASC
					LIMIT 1",
					$wp_user_id
				)
			)
		);
	}
}

if ( ! function_exists( 'abitai_company_profile_audit_tenant_denial' ) ) {
	/**
	 * Audit a blocked cross-tenant company profile attempt without logging payload data.
	 *
	 * @param int    $wp_user_id Current WordPress user ID.
	 * @param string $reason Denial reason key.
	 * @param int    $requested_user_id Requested user ID.
	 * @param int    $requested_company_id Requested company ID.
	 * @param int    $owned_company_id Company ID owned by the current user.
	 */
	function abitai_company_profile_audit_tenant_denial( $wp_user_id, $reason, $requested_user_id = 0, $requested_company_id = 0, $owned_company_id = 0 ) {
		if ( ! function_exists( 'abitai_auth_write_audit_log' ) ) {
			return;
		}

		abitai_auth_write_audit_log(
			'auth_tenant_scope_denied',
			array(
				'actor_user_id' => absint( $wp_user_id ),
				'actor_type'    => 'user',
				'entity_type'   => 'company_profile',
				'entity_id'     => absint( $requested_company_id ),
				'company_id'    => absint( $owned_company_id ),
				'event_data'    => array(
					'api_surface'          => 'company_profile_update',
					'denial_reason'        => sanitize_key( $reason ),
					'requested_user_id'    => absint( $requested_user_id ),
					'requested_company_id' => absint( $requested_company_id ),
					'owned_company_id'     => absint( $owned_company_id ),
				),
			)
		);
	}
}

if ( ! function_exists( 'abitai_company_profile_upsert_tables' ) ) {
	/**
	 * Mirror company profile updates into the custom auth tables when present.
	 *
	 * @param int                 $wp_user_id WordPress user ID.
	 * @param array<string,mixed> $payload Validated payload.
	 * @return array<string,int|bool>
	 */
	function abitai_company_profile_upsert_tables( $wp_user_id, $payload ) {
		global $wpdb;

		$result = array(
			'tables_updated'     => false,
			'company_id'         => 0,
			'access_request_id'  => 0,
			'onboarding_id'      => 0,
		);

		if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return $result;
		}

		$tables = abitai_auth_schema_table_names();

		foreach ( array( 'access_requests', 'users', 'companies', 'members', 'onboarding' ) as $table_key ) {
			if ( ! abitai_company_profile_table_exists( $tables[ $table_key ] ) ) {
				return $result;
			}
		}

		$user = get_userdata( $wp_user_id );

		if ( ! $user ) {
			return $result;
		}

		$now             = current_time( 'mysql', true );
		$email           = strtolower( $user->user_email );
		$full_name       = '' !== trim( $user->display_name ) ? $user->display_name : $user->user_login;
		$module_json     = wp_json_encode( $payload['erp_module_interest'] );
		$auth_user_id    = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tables['users']} WHERE wp_user_id = %d OR email = %s ORDER BY id ASC LIMIT 1",
					$wp_user_id,
					$email
				)
			)
		);

		if ( 0 === $auth_user_id ) {
			$wpdb->insert(
				$tables['users'],
				array(
					'user_uuid'   => wp_generate_uuid4(),
					'wp_user_id'  => $wp_user_id,
					'email'       => $email,
					'full_name'   => $full_name,
					'auth_status' => 'onboarding_required',
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$auth_user_id = absint( $wpdb->insert_id );
		} else {
			$wpdb->update(
				$tables['users'],
				array(
					'wp_user_id'  => $wp_user_id,
					'full_name'   => $full_name,
					'auth_status' => 'pending_admin_review',
					'updated_at'  => $now,
				),
				array( 'id' => $auth_user_id ),
				array( '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		$company_id = abitai_company_profile_get_owned_company_id( $wp_user_id );

		if ( 0 === $company_id ) {
			$wpdb->insert(
				$tables['companies'],
				array(
					'company_uuid'       => wp_generate_uuid4(),
					'name'               => $payload['company_name'],
					'country_region'     => $payload['country_region'],
					'industry'           => $payload['industry'],
					'company_size'       => $payload['company_size'],
					'status'             => 'pending_review',
					'created_by_user_id' => $auth_user_id,
					'created_at'         => $now,
					'updated_at'         => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
			$company_id = absint( $wpdb->insert_id );
		} else {
			$wpdb->update(
				$tables['companies'],
				array(
					'name'           => $payload['company_name'],
					'country_region' => $payload['country_region'],
					'industry'       => $payload['industry'],
					'company_size'   => $payload['company_size'],
					'updated_at'     => $now,
				),
				array( 'id' => $company_id ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		$member_id = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tables['members']} WHERE company_id = %d AND user_id = %d LIMIT 1",
					$company_id,
					$auth_user_id
				)
			)
		);

		if ( 0 === $member_id ) {
			$wpdb->insert(
				$tables['members'],
				array(
					'company_id'  => $company_id,
					'user_id'     => $auth_user_id,
					'status'      => 'pending_review',
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%d', '%d', '%s', '%s', '%s' )
			);
		}

		$access_request_id = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tables['access_requests']} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
					$auth_user_id
				)
			)
		);

		$access_request_data = array(
			'user_id'             => $auth_user_id,
			'company_id'          => $company_id,
			'full_name'           => $full_name,
			'business_email'      => $email,
			'company_name'        => $payload['company_name'],
			'country_region'      => $payload['country_region'],
			'intended_use_case'   => $payload['primary_workflow'],
			'role'                => $payload['role'],
			'company_size'        => $payload['company_size'],
			'industry'            => $payload['industry'],
			'primary_workflow'    => $payload['primary_workflow'],
			'erp_module_interest' => $module_json,
			'current_system'      => $payload['current_system'],
			'timeline'            => $payload['timeline'],
			'notes'               => $payload['notes'],
			'review_status'       => 'pending_admin_review',
			'updated_at'          => $now,
		);

		if ( 0 === $access_request_id ) {
			$access_request_data['request_uuid'] = wp_generate_uuid4();
			$access_request_data['created_at']   = $now;
			$wpdb->insert(
				$tables['access_requests'],
				$access_request_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$access_request_id = absint( $wpdb->insert_id );
		} else {
			$wpdb->update(
				$tables['access_requests'],
				$access_request_data,
				array( 'id' => $access_request_id ),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		$wpdb->update(
			$tables['users'],
			array(
				'primary_access_request_id' => $access_request_id,
				'auth_status'               => 'pending_admin_review',
				'updated_at'                => $now,
			),
			array( 'id' => $auth_user_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		$onboarding_id = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tables['onboarding']} WHERE access_request_id = %d LIMIT 1",
					$access_request_id
				)
			)
		);

		$onboarding_data = array(
			'access_request_id'   => $access_request_id,
			'user_id'             => $auth_user_id,
			'company_id'          => $company_id,
			'role'                => $payload['role'],
			'company_size'        => $payload['company_size'],
			'industry'            => $payload['industry'],
			'primary_workflow'    => $payload['primary_workflow'],
			'erp_module_interest' => $module_json,
			'current_system'      => $payload['current_system'],
			'timeline'            => $payload['timeline'],
			'notes'               => $payload['notes'],
			'completed_step'      => 'erp_needs',
			'completed_at'        => $now,
			'updated_at'          => $now,
		);

		if ( 0 === $onboarding_id ) {
			$onboarding_data['created_at'] = $now;
			$wpdb->insert(
				$tables['onboarding'],
				$onboarding_data,
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$onboarding_id = absint( $wpdb->insert_id );
		} else {
			$wpdb->update(
				$tables['onboarding'],
				$onboarding_data,
				array( 'id' => $onboarding_id ),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		update_user_meta( $wp_user_id, 'abitai_company_id', $company_id );

		$result['tables_updated']    = true;
		$result['company_id']        = $company_id;
		$result['access_request_id'] = $access_request_id;
		$result['onboarding_id']     = $onboarding_id;

		return $result;
	}
}

if ( ! function_exists( 'abitai_company_profile_update_user_meta' ) ) {
	/**
	 * Persist company profile fields to WordPress user meta.
	 *
	 * @param int                 $wp_user_id WordPress user ID.
	 * @param array<string,mixed> $payload Validated payload.
	 */
	function abitai_company_profile_update_user_meta( $wp_user_id, $payload ) {
		update_user_meta( $wp_user_id, 'abitai_company_name', $payload['company_name'] );
		update_user_meta( $wp_user_id, 'abitai_country_region', $payload['country_region'] );
		update_user_meta( $wp_user_id, 'abitai_role', $payload['role'] );
		update_user_meta( $wp_user_id, 'abitai_job_title', $payload['role'] );
		update_user_meta( $wp_user_id, 'abitai_company_size', $payload['company_size'] );
		update_user_meta( $wp_user_id, 'abitai_industry', $payload['industry'] );
		update_user_meta( $wp_user_id, 'abitai_primary_workflow', $payload['primary_workflow'] );
		update_user_meta( $wp_user_id, 'abitai_business_description', $payload['primary_workflow'] );
		update_user_meta( $wp_user_id, 'abitai_erp_module_interest', $payload['erp_module_interest'] );
		update_user_meta( $wp_user_id, 'abitai_current_system', $payload['current_system'] );
		update_user_meta( $wp_user_id, 'abitai_timeline', $payload['timeline'] );
		update_user_meta( $wp_user_id, 'abitai_notes', $payload['notes'] );
		update_user_meta( $wp_user_id, 'abitai_access_request_status', 'pending_admin_review' );
		update_user_meta( $wp_user_id, 'abitai_company_profile_updated_at', current_time( 'mysql', true ) );
	}
}

if ( ! function_exists( 'abitai_company_profile_update' ) ) {
	/**
	 * Handle PUT /api/company/profile.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	function abitai_company_profile_update( WP_REST_Request $request ) {
		$wp_user_id = get_current_user_id();

		if ( $wp_user_id <= 0 ) {
			return abitai_company_profile_api_error( 'abitai_company_profile_unauthorized', __( 'Authentication is required.', 'astra' ), 401 );
		}

		$params             = $request->get_params();
		$requested_user_id  = isset( $params['user_id'] ) ? absint( $params['user_id'] ) : 0;
		$requested_company  = isset( $params['company_id'] ) ? absint( $params['company_id'] ) : 0;
		$owned_company_id   = abitai_company_profile_get_owned_company_id( $wp_user_id );

		if ( $requested_user_id > 0 && $requested_user_id !== $wp_user_id ) {
			abitai_company_profile_audit_tenant_denial( $wp_user_id, 'requested_user_mismatch', $requested_user_id, $requested_company, $owned_company_id );
			return abitai_company_profile_api_error( 'abitai_company_profile_forbidden_user', __( 'You can only update your own company profile.', 'astra' ), 403 );
		}

		if ( $requested_company > 0 && $requested_company !== $owned_company_id ) {
			abitai_company_profile_audit_tenant_denial( $wp_user_id, 'requested_company_mismatch', $requested_user_id, $requested_company, $owned_company_id );
			return abitai_company_profile_api_error( 'abitai_company_profile_forbidden_company', __( 'You cannot update another company profile.', 'astra' ), 403 );
		}

		$payload = abitai_company_profile_validate_payload( $request );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		abitai_company_profile_update_user_meta( $wp_user_id, $payload );
		$table_result = abitai_company_profile_upsert_tables( $wp_user_id, $payload );

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_onboarding_completed',
				array(
					'actor_user_id'     => $wp_user_id,
					'actor_type'        => 'user',
					'entity_type'       => 'access_request',
					'entity_id'         => $table_result['access_request_id'],
					'access_request_id' => $table_result['access_request_id'],
					'company_id'        => $table_result['company_id'] ? $table_result['company_id'] : $owned_company_id,
					'event_data'        => array(
						'review_status'              => 'pending_admin_review',
						'onboarding_completed_step'  => 'erp_needs',
						'company_size'               => $payload['company_size'],
						'industry'                   => $payload['industry'],
						'country_region'             => $payload['country_region'],
						'erp_module_interest_count'  => count( $payload['erp_module_interest'] ),
					),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success'       => true,
				'profile'       => array(
					'user_id'             => $wp_user_id,
					'company_id'          => $table_result['company_id'] ? $table_result['company_id'] : $owned_company_id,
					'company_name'        => $payload['company_name'],
					'country_region'      => $payload['country_region'],
					'role'                => $payload['role'],
					'company_size'        => $payload['company_size'],
					'industry'            => $payload['industry'],
					'primary_workflow'    => $payload['primary_workflow'],
					'erp_module_interest' => $payload['erp_module_interest'],
					'current_system'      => $payload['current_system'],
					'timeline'            => $payload['timeline'],
					'notes'               => $payload['notes'],
					'review_status'       => 'pending_admin_review',
				),
				'tables_updated' => (bool) $table_result['tables_updated'],
			)
		);
	}
}

if ( ! function_exists( 'abitai_company_profile_register_rest_routes' ) ) {
	/**
	 * Register company profile REST routes.
	 */
	function abitai_company_profile_register_rest_routes() {
		register_rest_route(
			'api',
			'/company/profile',
			array(
				'methods'             => 'PUT',
				'callback'            => 'abitai_company_profile_update',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}
	add_action( 'rest_api_init', 'abitai_company_profile_register_rest_routes' );
}

if ( ! function_exists( 'abitai_company_profile_add_rewrite_rule' ) ) {
	/**
	 * Allow the task-specified /api/company/profile path to reach the REST route.
	 */
	function abitai_company_profile_add_rewrite_rule() {
		add_rewrite_rule( '^api/company/profile/?$', 'index.php?rest_route=/api/company/profile', 'top' );
	}
	add_action( 'init', 'abitai_company_profile_add_rewrite_rule' );
}

if ( ! function_exists( 'abitai_company_profile_serve_direct_api_path' ) ) {
	/**
	 * Serve /api/company/profile without requiring a permalink flush.
	 */
	function abitai_company_profile_serve_direct_api_path() {
		$path = '/';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}

		if ( '/api/company/profile' !== rtrim( $path, '/' ) ) {
			return;
		}

		rest_get_server()->serve_request( '/api/company/profile' );
		exit;
	}
	add_action( 'template_redirect', 'abitai_company_profile_serve_direct_api_path', 0 );
}
