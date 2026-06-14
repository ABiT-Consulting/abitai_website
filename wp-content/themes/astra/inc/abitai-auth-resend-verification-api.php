<?php
/**
 * Resend verification REST API for the abit.ai SaaS auth flow.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS' ) ) {
	define( 'ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS', 60 );
}

if ( ! defined( 'ABITAI_AUTH_VERIFICATION_RESEND_HOURLY_LIMIT' ) ) {
	define( 'ABITAI_AUTH_VERIFICATION_RESEND_HOURLY_LIMIT', 5 );
}

if ( ! defined( 'ABITAI_AUTH_VERIFICATION_TOKEN_TTL_SECONDS' ) ) {
	define( 'ABITAI_AUTH_VERIFICATION_TOKEN_TTL_SECONDS', DAY_IN_SECONDS );
}

if ( ! function_exists( 'abitai_auth_resend_api_error' ) ) {
	/**
	 * Build a REST API error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Human-readable message.
	 * @param int    $status HTTP status.
	 * @param array  $extra Extra error data.
	 * @return WP_Error
	 */
	function abitai_auth_resend_api_error( $code, $message, $status, $extra = array() ) {
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

if ( ! function_exists( 'abitai_auth_resend_generic_message' ) ) {
	/**
	 * Return the safe resend response message.
	 *
	 * @return string
	 */
	function abitai_auth_resend_generic_message() {
		return __( 'If an eligible request exists, we will send a new verification link.', 'astra' );
	}
}

if ( ! function_exists( 'abitai_auth_resend_table_exists' ) ) {
	/**
	 * Check whether a custom auth table exists.
	 *
	 * @param string $table_name Table name.
	 * @return bool
	 */
	function abitai_auth_resend_table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}
}

if ( ! function_exists( 'abitai_auth_resend_get_payload' ) ) {
	/**
	 * Normalize resend request input.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,string>|WP_Error
	 */
	function abitai_auth_resend_get_payload( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		$email = '';
		if ( isset( $params['business_email'] ) ) {
			$email = sanitize_email( strtolower( (string) $params['business_email'] ) );
		} elseif ( isset( $params['email'] ) ) {
			$email = sanitize_email( strtolower( (string) $params['email'] ) );
		}

		if ( '' === $email || ! is_email( $email ) || strlen( $email ) > 254 ) {
			return abitai_auth_resend_api_error(
				'validation_failed',
				__( 'Please correct the highlighted fields.', 'astra' ),
				422,
				array(
					'field_errors' => array(
						'business_email' => __( 'Enter a valid business email address.', 'astra' ),
					),
				)
			);
		}

		return array(
			'email' => $email,
		);
	}
}

if ( ! function_exists( 'abitai_auth_resend_safe_response' ) ) {
	/**
	 * Build a safe resend API response.
	 *
	 * @param string $status Response status.
	 * @param bool   $sent Whether an email was sent.
	 * @param int    $http_status HTTP status.
	 * @param int    $retry_after Retry-after seconds.
	 * @param int    $token_id Token row ID.
	 * @return WP_REST_Response
	 */
	function abitai_auth_resend_safe_response( $status = 'accepted', $sent = false, $http_status = 202, $retry_after = 0, $token_id = 0 ) {
		$body = array(
			'message' => abitai_auth_resend_generic_message(),
			'status'  => sanitize_key( $status ),
			'sent'    => (bool) $sent,
		);

		if ( $retry_after > 0 ) {
			$body['retry_after'] = absint( $retry_after );
		}

		if ( $token_id > 0 ) {
			$body['email_verification_token_id'] = absint( $token_id );
		}

		$response = rest_ensure_response( $body );
		$response->set_status( $http_status );

		if ( $retry_after > 0 ) {
			$response->header( 'Retry-After', (string) absint( $retry_after ) );
		}

		return $response;
	}
}

if ( ! function_exists( 'abitai_auth_resend_get_sender_email' ) ) {
	/**
	 * Return an approved abit.ai sender address.
	 *
	 * @return string
	 */
	function abitai_auth_resend_get_sender_email() {
		$from = defined( 'ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL' ) ? sanitize_email( ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL ) : '';

		if ( ! is_email( $from ) || ! preg_match( '/@abit\.ai$/i', $from ) ) {
			$from = 'no-reply@abit.ai';
		}

		return $from;
	}
}

if ( ! function_exists( 'abitai_auth_resend_get_sender_name' ) ) {
	/**
	 * Return the transactional sender display name.
	 *
	 * @return string
	 */
	function abitai_auth_resend_get_sender_name() {
		$name = defined( 'ABIT_TRANSACTIONAL_MAIL_FROM_NAME' ) ? sanitize_text_field( ABIT_TRANSACTIONAL_MAIL_FROM_NAME ) : '';

		return '' !== $name ? $name : 'abit.ai';
	}
}

if ( ! function_exists( 'abitai_auth_resend_get_reply_to' ) ) {
	/**
	 * Return a safe Reply-To address.
	 *
	 * @return string
	 */
	function abitai_auth_resend_get_reply_to() {
		$reply_to = defined( 'ABIT_TRANSACTIONAL_MAIL_REPLY_TO' ) ? sanitize_email( ABIT_TRANSACTIONAL_MAIL_REPLY_TO ) : '';

		return is_email( $reply_to ) ? $reply_to : 'support@abit.ai';
	}
}

if ( ! function_exists( 'abitai_auth_resend_generate_token' ) ) {
	/**
	 * Generate a URL-safe verification token.
	 *
	 * @return string
	 */
	function abitai_auth_resend_generate_token() {
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}
}

if ( ! function_exists( 'abitai_auth_resend_build_verification_link' ) ) {
	/**
	 * Build the branded verification link.
	 *
	 * @param string $token Raw token.
	 * @param string $email Business email.
	 * @return string
	 */
	function abitai_auth_resend_build_verification_link( $token, $email ) {
		return add_query_arg(
			array(
				'token' => $token,
				'email' => $email,
			),
			home_url( '/auth/verify/' )
		);
	}
}

if ( ! function_exists( 'abitai_auth_resend_send_email' ) ) {
	/**
	 * Send the verification email and audit delivery metadata.
	 *
	 * @param string              $email Recipient email.
	 * @param string              $token Raw token.
	 * @param array<string,mixed> $context Token context.
	 * @return bool
	 */
	function abitai_auth_resend_send_email( $email, $token, $context ) {
		$link       = abitai_auth_resend_build_verification_link( $token, $email );
		$from_email = abitai_auth_resend_get_sender_email();
		$from_name  = abitai_auth_resend_get_sender_name();
		$reply_to   = abitai_auth_resend_get_reply_to();
		$subject    = __( 'Verify your abit.ai access request', 'astra' );
		$message    = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p><p>%s</p>',
			esc_html__( 'Use this link to verify your business email and continue your abit.ai access request.', 'astra' ),
			esc_url( $link ),
			esc_html__( 'Verify email', 'astra' ),
			esc_html__( 'If you did not request this email, you can ignore it.', 'astra' )
		);
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
			'Reply-To: ' . $reply_to,
		);

		$sent = wp_mail( $email, $subject, $message, $headers );

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_email_delivery_attempted',
				array(
					'actor_type'        => 'system',
					'entity_type'       => 'email_verification',
					'entity_id'         => isset( $context['token_id'] ) ? absint( $context['token_id'] ) : 0,
					'access_request_id' => isset( $context['access_request_id'] ) ? absint( $context['access_request_id'] ) : 0,
					'company_id'        => isset( $context['company_id'] ) ? absint( $context['company_id'] ) : 0,
					'event_data'        => array(
						'email'                    => $email,
						'message_type'             => 'email_verification',
						'provider_key'             => defined( 'ABIT_TRANSACTIONAL_MAIL_PROVIDER' ) ? sanitize_key( ABIT_TRANSACTIONAL_MAIL_PROVIDER ) : 'wordpress',
						'from_domain'              => 'abit.ai',
						'token_id'                 => isset( $context['token_id'] ) ? absint( $context['token_id'] ) : 0,
						'branded_link_path'        => '/auth/verify/',
						'delivery_result'          => $sent ? 'sent' : 'failed',
						'verification_send_reason' => 'resend',
					),
				)
			);
		}

		return (bool) $sent;
	}
}

if ( ! function_exists( 'abitai_auth_resend_find_context' ) ) {
	/**
	 * Find the eligible verification context for an email.
	 *
	 * @param string $email Business email.
	 * @return array<string,mixed>
	 */
	function abitai_auth_resend_find_context( $email ) {
		global $wpdb;

		$context = array(
			'eligible'          => false,
			'source'            => 'none',
			'wp_user_id'        => 0,
			'auth_user_id'      => 0,
			'access_request_id' => 0,
			'company_id'        => 0,
			'email'             => $email,
		);

		if ( function_exists( 'abitai_auth_schema_table_names' ) ) {
			$tables = abitai_auth_schema_table_names();

			if (
				abitai_auth_resend_table_exists( $tables['access_requests'] ) &&
				abitai_auth_resend_table_exists( $tables['users'] ) &&
				abitai_auth_resend_table_exists( $tables['tokens'] )
			) {
				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT ar.id AS access_request_id, ar.company_id, ar.review_status, ar.email_verified_at, u.id AS auth_user_id, u.wp_user_id, u.auth_status, u.email_verified_at AS user_email_verified_at
						FROM {$tables['access_requests']} ar
						LEFT JOIN {$tables['users']} u ON u.id = ar.user_id
						WHERE ar.business_email = %s OR u.email = %s
						ORDER BY ar.id DESC
						LIMIT 1",
						$email,
						$email
					),
					ARRAY_A
				);

				if ( is_array( $row ) ) {
					$wp_user_id        = absint( $row['wp_user_id'] );
					$verified_at       = (string) $row['email_verified_at'];
					$user_verified_at  = (string) $row['user_email_verified_at'];
					$review_status     = sanitize_key( (string) $row['review_status'] );
					$user_auth_status  = sanitize_key( (string) $row['auth_status'] );
					$wp_verified_at    = $wp_user_id > 0 ? (string) get_user_meta( $wp_user_id, 'abitai_email_verified_at', true ) : '';
					$is_pending_status = in_array( 'pending_email_verification', array( $review_status, $user_auth_status ), true );

					$context['source']            = 'custom_tables';
					$context['wp_user_id']        = $wp_user_id;
					$context['auth_user_id']      = absint( $row['auth_user_id'] );
					$context['access_request_id'] = absint( $row['access_request_id'] );
					$context['company_id']        = absint( $row['company_id'] );
					$context['eligible']          = $is_pending_status && '' === $verified_at && '' === $user_verified_at && '' === $wp_verified_at;

					return $context;
				}
			}
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return $context;
		}

		$status      = sanitize_key( (string) get_user_meta( $user->ID, 'abitai_access_request_status', true ) );
		$verified_at = (string) get_user_meta( $user->ID, 'abitai_email_verified_at', true );
		$is_locked   = (int) $user->user_status > 0 || get_user_meta( $user->ID, 'abit_saas_account_locked', true ) || get_user_meta( $user->ID, 'abit_saas_security_hold', true ) || get_user_meta( $user->ID, 'account_locked', true );

		$context['source']     = 'user_meta';
		$context['wp_user_id'] = absint( $user->ID );
		$context['eligible']   = ! $is_locked && 'pending_email_verification' === $status && '' === $verified_at;

		return $context;
	}
}

if ( ! function_exists( 'abitai_auth_resend_check_custom_limits' ) ) {
	/**
	 * Check token-table cooldown and hourly rate limits.
	 *
	 * @param array<string,mixed> $context Token context.
	 * @return array<string,mixed>
	 */
	function abitai_auth_resend_check_custom_limits( $context ) {
		global $wpdb;

		$tables   = abitai_auth_schema_table_names();
		$where    = array();
		$params   = array();
		$now_ts   = time();
		$hour_ago = gmdate( 'Y-m-d H:i:s', $now_ts - HOUR_IN_SECONDS );

		if ( ! empty( $context['access_request_id'] ) ) {
			$where[]  = 'access_request_id = %d';
			$params[] = absint( $context['access_request_id'] );
		}

		if ( ! empty( $context['auth_user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $context['auth_user_id'] );
		}

		if ( empty( $where ) ) {
			return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
		}

		$where_sql = '(' . implode( ' OR ', $where ) . ') AND token_type = %s';
		$params[]  = 'email_verification';

		$latest_created_at = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$tables['tokens']} WHERE {$where_sql} ORDER BY created_at DESC LIMIT 1", $params ) );

		if ( $latest_created_at ) {
			$elapsed = $now_ts - strtotime( (string) $latest_created_at . ' UTC' );
			if ( $elapsed < ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS ) {
				return array(
					'limited'     => true,
					'retry_after' => ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS - max( 0, $elapsed ),
					'reason'      => 'cooldown',
				);
			}
		}

		$count_params   = $params;
		$count_params[] = $hour_ago;
		$sent_last_hour = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['tokens']} WHERE {$where_sql} AND created_at >= %s", $count_params ) ) );

		if ( $sent_last_hour >= ABITAI_AUTH_VERIFICATION_RESEND_HOURLY_LIMIT ) {
			return array(
				'limited'     => true,
				'retry_after' => HOUR_IN_SECONDS,
				'reason'      => 'hourly_limit',
			);
		}

		return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
	}
}

if ( ! function_exists( 'abitai_auth_resend_create_custom_token' ) ) {
	/**
	 * Invalidate old token rows and create a fresh email verification token.
	 *
	 * @param array<string,mixed> $context Token context.
	 * @return array<string,mixed>
	 */
	function abitai_auth_resend_create_custom_token( $context ) {
		global $wpdb;

		$tables = abitai_auth_schema_table_names();
		$now    = current_time( 'mysql', true );
		$token  = abitai_auth_resend_generate_token();
		$where  = array();
		$params = array( $now );

		if ( ! empty( $context['access_request_id'] ) ) {
			$where[]  = 'access_request_id = %d';
			$params[] = absint( $context['access_request_id'] );
		}

		if ( ! empty( $context['auth_user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $context['auth_user_id'] );
		}

		if ( ! empty( $where ) ) {
			$params[] = 'email_verification';
			$wpdb->query( $wpdb->prepare( "UPDATE {$tables['tokens']} SET consumed_at = %s WHERE (" . implode( ' OR ', $where ) . ') AND token_type = %s AND consumed_at IS NULL', $params ) );
		}

		$wpdb->insert(
			$tables['tokens'],
			array(
				'token_uuid'              => wp_generate_uuid4(),
				'user_id'                 => ! empty( $context['auth_user_id'] ) ? absint( $context['auth_user_id'] ) : null,
				'access_request_id'       => ! empty( $context['access_request_id'] ) ? absint( $context['access_request_id'] ) : null,
				'token_type'              => 'email_verification',
				'token_hash'              => abitai_auth_hash_value( $token ),
				'delivery_channel'        => 'email',
				'issue_reason'            => 'resend',
				'expires_at'              => gmdate( 'Y-m-d H:i:s', time() + ABITAI_AUTH_VERIFICATION_TOKEN_TTL_SECONDS ),
				'created_ip_hash'         => abitai_auth_hash_value( abitai_auth_request_ip() ),
				'created_user_agent_hash' => abitai_auth_hash_value( isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '' ),
				'hash_key_version'        => abitai_auth_hash_key_version(),
				'created_at'              => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return array(
			'token'    => $token,
			'token_id' => absint( $wpdb->insert_id ),
		);
	}
}

if ( ! function_exists( 'abitai_auth_resend_check_meta_limits' ) ) {
	/**
	 * Check legacy user-meta cooldown and hourly rate limits.
	 *
	 * @param int $wp_user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	function abitai_auth_resend_check_meta_limits( $wp_user_id ) {
		$now_ts     = time();
		$last_sent  = absint( get_user_meta( $wp_user_id, 'abitai_verification_resend_last_sent_at', true ) );
		$timestamps = get_user_meta( $wp_user_id, 'abitai_verification_resend_timestamps', true );
		$timestamps = is_array( $timestamps ) ? array_map( 'absint', $timestamps ) : array();
		$timestamps = array_values( array_filter( $timestamps, static function ( $timestamp ) use ( $now_ts ) {
			return $timestamp >= ( $now_ts - HOUR_IN_SECONDS );
		} ) );

		if ( $last_sent > 0 && ( $now_ts - $last_sent ) < ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS ) {
			return array(
				'limited'     => true,
				'retry_after' => ABITAI_AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS - max( 0, $now_ts - $last_sent ),
				'reason'      => 'cooldown',
			);
		}

		if ( count( $timestamps ) >= ABITAI_AUTH_VERIFICATION_RESEND_HOURLY_LIMIT ) {
			return array(
				'limited'     => true,
				'retry_after' => HOUR_IN_SECONDS,
				'reason'      => 'hourly_limit',
			);
		}

		return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
	}
}

if ( ! function_exists( 'abitai_auth_resend_create_meta_token' ) ) {
	/**
	 * Create a legacy user-meta verification token, replacing previous valid token metadata.
	 *
	 * @param int $wp_user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	function abitai_auth_resend_create_meta_token( $wp_user_id ) {
		$now_ts     = time();
		$token      = abitai_auth_resend_generate_token();
		$timestamps = get_user_meta( $wp_user_id, 'abitai_verification_resend_timestamps', true );
		$timestamps = is_array( $timestamps ) ? array_map( 'absint', $timestamps ) : array();
		$timestamps = array_values( array_filter( $timestamps, static function ( $timestamp ) use ( $now_ts ) {
			return $timestamp >= ( $now_ts - HOUR_IN_SECONDS );
		} ) );
		$timestamps[] = $now_ts;

		update_user_meta( $wp_user_id, 'abitai_email_verification_token_hash', abitai_auth_hash_value( $token ) );
		update_user_meta( $wp_user_id, 'abitai_email_verification_token_issued_at', gmdate( 'Y-m-d H:i:s', $now_ts ) );
		update_user_meta( $wp_user_id, 'abitai_email_verification_token_expires_at', gmdate( 'Y-m-d H:i:s', $now_ts + ABITAI_AUTH_VERIFICATION_TOKEN_TTL_SECONDS ) );
		update_user_meta( $wp_user_id, 'abitai_email_verification_token_issue_reason', 'resend' );
		update_user_meta( $wp_user_id, 'abitai_verification_resend_last_sent_at', $now_ts );
		update_user_meta( $wp_user_id, 'abitai_verification_resend_timestamps', $timestamps );

		return array(
			'token'    => $token,
			'token_id' => 0,
		);
	}
}

if ( ! function_exists( 'abitai_auth_resend_audit' ) ) {
	/**
	 * Write resend audit metadata without storing raw tokens.
	 *
	 * @param string              $result Resend result.
	 * @param string              $email Business email.
	 * @param array<string,mixed> $context Token context.
	 * @param array<string,mixed> $extra Extra event data.
	 */
	function abitai_auth_resend_audit( $result, $email, $context = array(), $extra = array() ) {
		if ( ! function_exists( 'abitai_auth_write_audit_log' ) ) {
			return;
		}

		abitai_auth_write_audit_log(
			'auth_verification_resend',
			array(
				'actor_user_id'     => isset( $context['wp_user_id'] ) ? absint( $context['wp_user_id'] ) : 0,
				'actor_type'        => is_user_logged_in() ? 'user' : 'anonymous',
				'entity_type'       => 'email_verification',
				'access_request_id' => isset( $context['access_request_id'] ) ? absint( $context['access_request_id'] ) : 0,
				'company_id'        => isset( $context['company_id'] ) ? absint( $context['company_id'] ) : 0,
				'event_data'        => array_merge(
					array(
						'email'                    => $email,
						'verification_send_reason' => 'resend',
						'result'                   => sanitize_key( $result ),
						'source'                   => isset( $context['source'] ) ? sanitize_key( (string) $context['source'] ) : 'unknown',
					),
					$extra
				),
			)
		);
	}
}

if ( ! function_exists( 'abitai_auth_resend_verification' ) ) {
	/**
	 * Handle POST /api/auth/resend-verification.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	function abitai_auth_resend_verification( WP_REST_Request $request ) {
		$payload = abitai_auth_resend_get_payload( $request );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$email   = $payload['email'];
		$context = abitai_auth_resend_find_context( $email );

		if ( empty( $context['eligible'] ) ) {
			abitai_auth_resend_audit( 'accepted_ineligible_safe_response', $email, $context );
			return abitai_auth_resend_safe_response();
		}

		$limits = 'custom_tables' === $context['source'] ? abitai_auth_resend_check_custom_limits( $context ) : abitai_auth_resend_check_meta_limits( absint( $context['wp_user_id'] ) );

		if ( ! empty( $limits['limited'] ) ) {
			abitai_auth_resend_audit(
				'rate_limited',
				$email,
				$context,
				array(
					'limit_reason' => sanitize_key( (string) $limits['reason'] ),
					'retry_after'  => absint( $limits['retry_after'] ),
				)
			);

			return abitai_auth_resend_safe_response( 'rate_limited', false, 429, absint( $limits['retry_after'] ) );
		}

		$token_data = 'custom_tables' === $context['source'] ? abitai_auth_resend_create_custom_token( $context ) : abitai_auth_resend_create_meta_token( absint( $context['wp_user_id'] ) );
		$token_id   = isset( $token_data['token_id'] ) ? absint( $token_data['token_id'] ) : 0;
		$sent       = abitai_auth_resend_send_email(
			$email,
			(string) $token_data['token'],
			array(
				'token_id'          => $token_id,
				'access_request_id' => $context['access_request_id'],
				'company_id'        => $context['company_id'],
			)
		);

		abitai_auth_resend_audit(
			$sent ? 'accepted' : 'email_failed',
			$email,
			$context,
			array(
				'token_id'                 => $token_id,
				'delivery_result'          => $sent ? 'sent' : 'failed',
				'token_invalidation_policy' => 'supersede_previous_unconsumed',
			)
		);

		return abitai_auth_resend_safe_response( 'accepted', $sent, 202, 0, $token_id );
	}
}

if ( ! function_exists( 'abitai_auth_resend_register_rest_routes' ) ) {
	/**
	 * Register resend verification REST routes.
	 */
	function abitai_auth_resend_register_rest_routes() {
		$route = array(
			'methods'             => 'POST',
			'callback'            => 'abitai_auth_resend_verification',
			'permission_callback' => '__return_true',
		);

		register_rest_route( 'abit-ai/v1', '/auth/resend-verification', $route );
		register_rest_route( 'api', '/auth/resend-verification', $route );
	}
	add_action( 'rest_api_init', 'abitai_auth_resend_register_rest_routes' );
}

if ( ! function_exists( 'abitai_auth_resend_add_rewrite_rule' ) ) {
	/**
	 * Allow /api/auth/resend-verification to reach the REST route.
	 */
	function abitai_auth_resend_add_rewrite_rule() {
		add_rewrite_rule( '^api/auth/resend-verification/?$', 'index.php?rest_route=/abit-ai/v1/auth/resend-verification', 'top' );
	}
	add_action( 'init', 'abitai_auth_resend_add_rewrite_rule' );
}

if ( ! function_exists( 'abitai_auth_resend_serve_direct_api_path' ) ) {
	/**
	 * Serve the pretty resend endpoint without requiring a permalink flush.
	 */
	function abitai_auth_resend_serve_direct_api_path() {
		$path = '/';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}

		if ( '/api/auth/resend-verification' !== rtrim( $path, '/' ) ) {
			return;
		}

		rest_get_server()->serve_request( '/abit-ai/v1/auth/resend-verification' );
		exit;
	}
	add_action( 'template_redirect', 'abitai_auth_resend_serve_direct_api_path', 0 );
}
