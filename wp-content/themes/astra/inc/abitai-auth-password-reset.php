<?php
/**
 * Forgot-password token lifecycle for the abit.ai SaaS auth flow.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ABITAI_AUTH_PASSWORD_RESET_TOKEN_TTL_SECONDS' ) ) {
	define( 'ABITAI_AUTH_PASSWORD_RESET_TOKEN_TTL_SECONDS', 30 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'ABITAI_AUTH_PASSWORD_RESET_COOLDOWN_SECONDS' ) ) {
	define( 'ABITAI_AUTH_PASSWORD_RESET_COOLDOWN_SECONDS', 60 );
}

if ( ! defined( 'ABITAI_AUTH_PASSWORD_RESET_HOURLY_LIMIT' ) ) {
	define( 'ABITAI_AUTH_PASSWORD_RESET_HOURLY_LIMIT', 5 );
}

if ( ! function_exists( 'abitai_auth_password_reset_table_exists' ) ) {
	function abitai_auth_password_reset_table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_generate_token' ) ) {
	function abitai_auth_password_reset_generate_token() {
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_sanitize_token' ) ) {
	function abitai_auth_password_reset_sanitize_token( $token ) {
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', sanitize_text_field( (string) $token ) );
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_is_weak_password' ) ) {
	function abitai_auth_password_reset_is_weak_password( $password ) {
		$common_passwords = array(
			'password',
			'password123',
			'password123!',
			'123456789012',
			'qwerty123456',
			'admin1234567',
			'welcome12345',
		);

		return strlen( $password ) < 12 || strlen( $password ) > 128 || in_array( strtolower( $password ), $common_passwords, true );
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_sender_email' ) ) {
	function abitai_auth_password_reset_sender_email() {
		$from = defined( 'ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL' ) ? sanitize_email( ABIT_TRANSACTIONAL_MAIL_FROM_EMAIL ) : '';

		return is_email( $from ) && preg_match( '/@abit\.ai$/i', $from ) ? $from : 'no-reply@abit.ai';
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_sender_name' ) ) {
	function abitai_auth_password_reset_sender_name() {
		$name = defined( 'ABIT_TRANSACTIONAL_MAIL_FROM_NAME' ) ? sanitize_text_field( ABIT_TRANSACTIONAL_MAIL_FROM_NAME ) : '';

		return '' !== $name ? $name : 'abit.ai';
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_reply_to' ) ) {
	function abitai_auth_password_reset_reply_to() {
		$reply_to = defined( 'ABIT_TRANSACTIONAL_MAIL_REPLY_TO' ) ? sanitize_email( ABIT_TRANSACTIONAL_MAIL_REPLY_TO ) : '';

		return is_email( $reply_to ) ? $reply_to : 'support@abit.ai';
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_find_account' ) ) {
	function abitai_auth_password_reset_find_account( $email ) {
		global $wpdb;

		$context = array(
			'eligible'     => false,
			'email'        => $email,
			'wp_user_id'   => 0,
			'auth_user_id' => 0,
			'company_id'   => 0,
			'source'       => 'none',
		);

		$wp_user = get_user_by( 'email', $email );
		if ( $wp_user ) {
			$is_locked = (int) $wp_user->user_status > 0 || get_user_meta( $wp_user->ID, 'abit_saas_account_locked', true ) || get_user_meta( $wp_user->ID, 'abit_saas_security_hold', true ) || get_user_meta( $wp_user->ID, 'account_locked', true );

			$context['eligible']   = ! $is_locked;
			$context['wp_user_id'] = absint( $wp_user->ID );
			$context['source']     = 'wp_user';
		}

		if ( function_exists( 'abitai_auth_schema_table_names' ) ) {
			$tables = abitai_auth_schema_table_names();
			if ( abitai_auth_password_reset_table_exists( $tables['users'] ) ) {
				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id, wp_user_id FROM {$tables['users']} WHERE email = %s ORDER BY id ASC LIMIT 1",
						$email
					),
					ARRAY_A
				);

				if ( is_array( $row ) ) {
					$context['auth_user_id'] = absint( $row['id'] );
					if ( 0 === $context['wp_user_id'] && ! empty( $row['wp_user_id'] ) ) {
						$context['wp_user_id'] = absint( $row['wp_user_id'] );
					}
					$context['source'] = $context['eligible'] ? 'wp_user_custom_tables' : 'custom_tables';
				}
			}

			if ( $context['auth_user_id'] > 0 && abitai_auth_password_reset_table_exists( $tables['members'] ) ) {
				$context['company_id'] = absint(
					$wpdb->get_var(
						$wpdb->prepare(
							"SELECT company_id FROM {$tables['members']} WHERE user_id = %d ORDER BY id ASC LIMIT 1",
							$context['auth_user_id']
						)
					)
				);
			}
		}

		$context['eligible'] = $context['eligible'] && $context['wp_user_id'] > 0;

		return $context;
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_check_limits' ) ) {
	function abitai_auth_password_reset_check_limits( $context ) {
		global $wpdb;

		if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
		}

		$tables = abitai_auth_schema_table_names();
		if ( ! abitai_auth_password_reset_table_exists( $tables['tokens'] ) ) {
			return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
		}

		$where  = array();
		$params = array();
		$now_ts = time();

		if ( ! empty( $context['auth_user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $context['auth_user_id'] );
		}

		if ( empty( $where ) ) {
			return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
		}

		$where_sql = '(' . implode( ' OR ', $where ) . ') AND token_type = %s';
		$params[]  = 'password_reset';

		$latest_created_at = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$tables['tokens']} WHERE {$where_sql} ORDER BY created_at DESC LIMIT 1", $params ) );
		if ( $latest_created_at ) {
			$elapsed = $now_ts - strtotime( (string) $latest_created_at . ' UTC' );
			if ( $elapsed < ABITAI_AUTH_PASSWORD_RESET_COOLDOWN_SECONDS ) {
				return array(
					'limited'     => true,
					'retry_after' => ABITAI_AUTH_PASSWORD_RESET_COOLDOWN_SECONDS - max( 0, $elapsed ),
					'reason'      => 'cooldown',
				);
			}
		}

		$count_params   = $params;
		$count_params[] = gmdate( 'Y-m-d H:i:s', $now_ts - HOUR_IN_SECONDS );
		$sent_last_hour = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['tokens']} WHERE {$where_sql} AND created_at >= %s", $count_params ) ) );

		if ( $sent_last_hour >= ABITAI_AUTH_PASSWORD_RESET_HOURLY_LIMIT ) {
			return array( 'limited' => true, 'retry_after' => HOUR_IN_SECONDS, 'reason' => 'hourly_limit' );
		}

		return array( 'limited' => false, 'retry_after' => 0, 'reason' => '' );
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_create_token' ) ) {
	function abitai_auth_password_reset_create_token( $context ) {
		global $wpdb;

		if ( ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return array( 'token' => '', 'token_id' => 0 );
		}

		$tables = abitai_auth_schema_table_names();
		if ( ! abitai_auth_password_reset_table_exists( $tables['tokens'] ) || ! abitai_auth_password_reset_table_exists( $tables['users'] ) ) {
			abitai_auth_maybe_install_schema();
		}

		if ( ! abitai_auth_password_reset_table_exists( $tables['tokens'] ) || ! abitai_auth_password_reset_table_exists( $tables['users'] ) ) {
			return array( 'token' => '', 'token_id' => 0 );
		}

		$now    = current_time( 'mysql', true );
		$token  = abitai_auth_password_reset_generate_token();
		$user_id = absint( $context['auth_user_id'] );

		if ( 0 === $user_id && ! empty( $context['wp_user_id'] ) ) {
			$wp_user = get_userdata( absint( $context['wp_user_id'] ) );
			if ( $wp_user ) {
				$email     = strtolower( $wp_user->user_email );
				$full_name = '' !== trim( $wp_user->display_name ) ? $wp_user->display_name : $wp_user->user_login;
				$status    = sanitize_key( (string) get_user_meta( $wp_user->ID, 'abitai_access_request_status', true ) );
				$status    = '' !== $status ? $status : ( get_user_meta( $wp_user->ID, 'abitai_email_verified_at', true ) ? 'pending_admin_review' : 'pending_email_verification' );

				$wpdb->insert(
					$tables['users'],
					array(
						'user_uuid'     => wp_generate_uuid4(),
						'wp_user_id'    => absint( $wp_user->ID ),
						'email'         => $email,
						'email_hash'    => abitai_auth_hash_value( $email ),
						'password_hash' => $wp_user->user_pass,
						'full_name'     => $full_name,
						'auth_status'   => $status,
						'created_at'    => $now,
						'updated_at'    => $now,
					),
					array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
				$user_id = absint( $wpdb->insert_id );
			}
		}

		if ( 0 === $user_id ) {
			return array( 'token' => '', 'token_id' => 0 );
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$tables['tokens']} SET consumed_at = %s WHERE user_id = %d AND token_type = %s AND consumed_at IS NULL",
				$now,
				$user_id,
				'password_reset'
			)
		);

		$wpdb->insert(
			$tables['tokens'],
			array(
				'token_uuid'              => wp_generate_uuid4(),
				'user_id'                 => $user_id,
				'token_type'              => 'password_reset',
				'token_hash'              => abitai_auth_hash_value( $token ),
				'delivery_channel'        => 'email',
				'issue_reason'            => 'forgot_password',
				'expires_at'              => gmdate( 'Y-m-d H:i:s', time() + ABITAI_AUTH_PASSWORD_RESET_TOKEN_TTL_SECONDS ),
				'created_ip_hash'         => abitai_auth_hash_value( abitai_auth_request_ip() ),
				'created_user_agent_hash' => abitai_auth_hash_value( isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '' ),
				'hash_key_version'        => abitai_auth_hash_key_version(),
				'created_at'              => $now,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return array(
			'token'    => $token,
			'token_id' => absint( $wpdb->insert_id ),
		);
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_send_email' ) ) {
	function abitai_auth_password_reset_send_email( $email, $token, $context ) {
		$link    = add_query_arg( array( 'token' => $token ), home_url( '/auth/reset-password/' ) );
		$subject = __( 'Reset your abit.ai password', 'astra' );
		$message = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p><p>%s</p>',
			esc_html__( 'Use this link to reset your abit.ai password. It expires shortly and can be used once.', 'astra' ),
			esc_url( $link ),
			esc_html__( 'Reset password', 'astra' ),
			esc_html__( 'If you did not request this email, you can ignore it.', 'astra' )
		);
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . abitai_auth_password_reset_sender_name() . ' <' . abitai_auth_password_reset_sender_email() . '>',
			'Reply-To: ' . abitai_auth_password_reset_reply_to(),
		);
		$sent    = wp_mail( $email, $subject, $message, $headers );

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_email_delivery_attempted',
				array(
					'actor_type'  => 'system',
					'entity_type' => 'password_reset',
					'entity_id'   => isset( $context['token_id'] ) ? absint( $context['token_id'] ) : 0,
					'company_id'  => isset( $context['company_id'] ) ? absint( $context['company_id'] ) : 0,
					'event_data'  => array(
						'email'             => $email,
						'message_type'      => 'password_reset',
						'provider_key'      => defined( 'ABIT_TRANSACTIONAL_MAIL_PROVIDER' ) ? sanitize_key( ABIT_TRANSACTIONAL_MAIL_PROVIDER ) : 'wordpress',
						'from_domain'       => 'abit.ai',
						'token_id'          => isset( $context['token_id'] ) ? absint( $context['token_id'] ) : 0,
						'branded_link_path' => '/auth/reset-password/',
						'delivery_result'   => $sent ? 'sent' : 'failed',
					),
				)
			);
		}

		return (bool) $sent;
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_audit' ) ) {
	function abitai_auth_password_reset_audit( $event_type, $result, $context = array(), $extra = array() ) {
		if ( ! function_exists( 'abitai_auth_write_audit_log' ) ) {
			return;
		}

		abitai_auth_write_audit_log(
			$event_type,
			array(
				'actor_user_id' => 0,
				'actor_type'    => 'anonymous',
				'entity_type'   => 'password_reset',
				'entity_id'     => isset( $context['token_id'] ) ? absint( $context['token_id'] ) : 0,
				'company_id'    => isset( $context['company_id'] ) ? absint( $context['company_id'] ) : 0,
				'event_data'    => array_merge(
					array(
						'email'  => isset( $context['email'] ) ? (string) $context['email'] : '',
						'result' => sanitize_key( $result ),
						'source' => 'forgot_password_lifecycle',
					),
					$extra
				),
			)
		);
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_lookup_token' ) ) {
	function abitai_auth_password_reset_lookup_token( $token ) {
		global $wpdb;

		$token = abitai_auth_password_reset_sanitize_token( $token );
		if ( '' === $token || ! function_exists( 'abitai_auth_schema_table_names' ) ) {
			return array( 'state' => 'invalid' );
		}

		$tables = abitai_auth_schema_table_names();
		if ( ! abitai_auth_password_reset_table_exists( $tables['tokens'] ) || ! abitai_auth_password_reset_table_exists( $tables['users'] ) ) {
			return array( 'state' => 'invalid' );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT t.id AS token_id, t.user_id AS auth_user_id, t.expires_at, t.consumed_at, u.wp_user_id, u.email
				FROM {$tables['tokens']} t
				INNER JOIN {$tables['users']} u ON u.id = t.user_id
				WHERE t.token_hash = %s AND t.token_type = %s
				LIMIT 1",
				abitai_auth_hash_value( $token ),
				'password_reset'
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return array( 'state' => 'invalid' );
		}

		if ( ! empty( $row['consumed_at'] ) ) {
			$row['state'] = 'invalid';
			return $row;
		}

		if ( strtotime( (string) $row['expires_at'] . ' UTC' ) <= time() ) {
			$row['state'] = 'expired';
			return $row;
		}

		$row['state'] = 'set';
		return $row;
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_token_state' ) ) {
	function abitai_auth_password_reset_token_state( $token ) {
		$lookup = abitai_auth_password_reset_lookup_token( $token );

		return isset( $lookup['state'] ) ? sanitize_key( $lookup['state'] ) : 'invalid';
	}
}

if ( ! function_exists( 'abitai_auth_password_reset_revoke_sessions' ) ) {
	function abitai_auth_password_reset_revoke_sessions( $wp_user_id, $auth_user_id ) {
		global $wpdb;

		if ( $wp_user_id > 0 && class_exists( 'WP_Session_Tokens' ) ) {
			WP_Session_Tokens::get_instance( $wp_user_id )->destroy_all();
		}

		if ( is_user_logged_in() && get_current_user_id() === $wp_user_id ) {
			wp_clear_auth_cookie();
		}

		if ( function_exists( 'abitai_auth_schema_table_names' ) ) {
			$tables = abitai_auth_schema_table_names();
			if ( $auth_user_id > 0 && abitai_auth_password_reset_table_exists( $tables['sessions'] ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$tables['sessions']} SET revoked_at = %s WHERE user_id = %d AND revoked_at IS NULL",
						current_time( 'mysql', true ),
						$auth_user_id
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'abitai_handle_mock_password_reset_request' ) ) {
	function abitai_handle_mock_password_reset_request() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/reset' ) );
			exit;
		}

		$email        = isset( $_POST['email'] ) ? sanitize_email( strtolower( wp_unslash( $_POST['email'] ) ) ) : '';
		$redirect_url = home_url( '/auth/reset' );

		if ( ! isset( $_POST['abitai_reset_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_reset_request_nonce'] ) ), 'abitai_mock_password_reset_request' ) ) {
			wp_safe_redirect( add_query_arg( 'state', 'invalid', $redirect_url ) );
			exit;
		}

		if ( '' === $email || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'state', 'accepted', $redirect_url ) );
			exit;
		}

		$context = abitai_auth_password_reset_find_account( $email );
		if ( empty( $context['eligible'] ) ) {
			abitai_auth_password_reset_audit( 'auth_password_reset_requested', 'accepted_ineligible_safe_response', $context );
			wp_safe_redirect( add_query_arg( 'state', 'accepted', $redirect_url ) );
			exit;
		}

		$limits = abitai_auth_password_reset_check_limits( $context );
		if ( ! empty( $limits['limited'] ) ) {
			abitai_auth_password_reset_audit(
				'auth_password_reset_requested',
				'rate_limited',
				$context,
				array(
					'limit_reason' => sanitize_key( (string) $limits['reason'] ),
					'retry_after'  => absint( $limits['retry_after'] ),
				)
			);
			wp_safe_redirect( add_query_arg( 'reset_error', 'rate_limited', $redirect_url ) );
			exit;
		}

		$token_data          = abitai_auth_password_reset_create_token( $context );
		$context['token_id'] = absint( $token_data['token_id'] );
		$sent                = '' !== $token_data['token'] ? abitai_auth_password_reset_send_email( $email, $token_data['token'], $context ) : false;

		abitai_auth_password_reset_audit(
			'auth_password_reset_requested',
			$sent ? 'accepted' : 'email_failed',
			$context,
			array(
				'token_id'                  => $context['token_id'],
				'delivery_result'           => $sent ? 'sent' : 'failed',
				'token_invalidation_policy' => 'supersede_previous_unconsumed',
			)
		);

		wp_safe_redirect( add_query_arg( 'state', 'accepted', $redirect_url ) );
		exit;
	}
}

if ( ! function_exists( 'abitai_handle_mock_password_reset_submit' ) ) {
	function abitai_handle_mock_password_reset_submit() {
		global $wpdb;

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/reset' ) );
			exit;
		}

		$token            = isset( $_POST['token'] ) ? abitai_auth_password_reset_sanitize_token( wp_unslash( $_POST['token'] ) ) : '';
		$password         = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';
		$set_url          = add_query_arg( array( 'state' => 'set', 'token' => $token ), home_url( '/auth/reset-password' ) );

		if ( ! isset( $_POST['abitai_reset_submit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_reset_submit_nonce'] ) ), 'abitai_mock_password_reset_submit' ) ) {
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		$lookup = abitai_auth_password_reset_lookup_token( $token );
		if ( 'expired' === $lookup['state'] ) {
			abitai_auth_password_reset_audit( 'auth_password_reset_completed', 'expired_token', $lookup );
			wp_safe_redirect( add_query_arg( 'state', 'expired', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		if ( 'set' !== $lookup['state'] ) {
			abitai_auth_password_reset_audit( 'auth_password_reset_completed', 'invalid_token', $lookup );
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		if ( '' === $password || '' === $confirm_password ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'missing_fields', $set_url ) );
			exit;
		}

		if ( abitai_auth_password_reset_is_weak_password( $password ) ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'weak_password', $set_url ) );
			exit;
		}

		if ( ! hash_equals( $password, $confirm_password ) ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'mismatch', $set_url ) );
			exit;
		}

		$wp_user_id   = absint( $lookup['wp_user_id'] );
		$auth_user_id = absint( $lookup['auth_user_id'] );
		if ( 0 === $wp_user_id ) {
			abitai_auth_password_reset_audit( 'auth_password_reset_completed', 'invalid_user', $lookup );
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		$tables = abitai_auth_schema_table_names();
		$now    = current_time( 'mysql', true );
		$used   = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$tables['tokens']} SET consumed_at = %s WHERE id = %d AND token_type = %s AND consumed_at IS NULL AND expires_at > %s",
				$now,
				absint( $lookup['token_id'] ),
				'password_reset',
				$now
			)
		);

		if ( 1 !== absint( $used ) ) {
			abitai_auth_password_reset_audit( 'auth_password_reset_completed', 'invalid_token', $lookup );
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		wp_set_password( $password, $wp_user_id );

		if ( $auth_user_id > 0 && abitai_auth_password_reset_table_exists( $tables['users'] ) ) {
			$wpdb->update(
				$tables['users'],
				array(
					'password_hash' => wp_hash_password( $password ),
					'updated_at'    => $now,
				),
				array( 'id' => $auth_user_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		abitai_auth_password_reset_revoke_sessions( $wp_user_id, $auth_user_id );
		abitai_auth_password_reset_audit( 'auth_password_reset_completed', 'success', $lookup );

		wp_safe_redirect( add_query_arg( 'state', 'success', home_url( '/auth/reset-password' ) ) );
		exit;
	}
}

add_action( 'admin_post_nopriv_abitai_mock_password_reset_request', 'abitai_handle_mock_password_reset_request' );
add_action( 'admin_post_abitai_mock_password_reset_request', 'abitai_handle_mock_password_reset_request' );
add_action( 'admin_post_nopriv_abitai_mock_password_reset_submit', 'abitai_handle_mock_password_reset_submit' );
add_action( 'admin_post_abitai_mock_password_reset_submit', 'abitai_handle_mock_password_reset_submit' );
