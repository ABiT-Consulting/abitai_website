<?php
/**
 * Frontend auth route helpers for the abit.ai SaaS access flow.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'abitai_auth_get_routes' ) ) {
	/**
	 * Return the public auth routes that share the auth shell.
	 *
	 * @return array<string,array<string,string>>
	 */
	function abitai_auth_get_routes() {
		return array(
			'sign-in' => array(
				'path'        => '/auth/sign-in',
				'title'       => __( 'Sign in to your workspace', 'astra' ),
				'eyebrow'     => __( 'Secure access', 'astra' ),
				'description' => __( 'View your access request, onboarding steps, and workspace status.', 'astra' ),
			),
			'signup'  => array(
				'path'        => '/auth/signup',
				'title'       => __( 'Start access request', 'astra' ),
				'eyebrow'     => __( 'Step 1 of 3', 'astra' ),
				'description' => __( 'Tell us who should receive updates for this business request.', 'astra' ),
			),
			'verify'  => array(
				'path'        => '/auth/verify',
				'title'       => __( 'Verify your email to continue', 'astra' ),
				'eyebrow'     => __( 'Email verification', 'astra' ),
				'description' => __( 'Confirm your business email before continuing the access request.', 'astra' ),
			),
			'reset'   => array(
				'path'        => '/auth/reset',
				'title'       => __( 'Reset your password', 'astra' ),
				'eyebrow'     => __( 'Account recovery', 'astra' ),
				'description' => __( 'Enter your email and we will send reset instructions if the account is eligible.', 'astra' ),
			),
			'onboarding' => array(
				'path'        => '/auth/onboarding',
				'title'       => __( 'Continue your access request', 'astra' ),
				'eyebrow'     => __( 'Onboarding required', 'astra' ),
				'description' => __( 'Complete the required business details before admin review.', 'astra' ),
			),
			'review'  => array(
				'path'        => '/auth/review-pending',
				'title'       => __( 'Your request is waiting for review', 'astra' ),
				'eyebrow'     => __( 'Admin review', 'astra' ),
				'description' => __( 'The abit.ai team is reviewing your access request.', 'astra' ),
			),
			'more-info' => array(
				'path'        => '/auth/more-information',
				'title'       => __( 'More information requested', 'astra' ),
				'eyebrow'     => __( 'Action needed', 'astra' ),
				'description' => __( 'Update your access request with the details requested by the review team.', 'astra' ),
			),
			'rejected' => array(
				'path'        => '/auth/rejected',
				'title'       => __( 'Access request unavailable', 'astra' ),
				'eyebrow'     => __( 'Request decision', 'astra' ),
				'description' => __( 'This account does not currently have product access.', 'astra' ),
			),
			'app'     => array(
				'path'        => '/auth/app',
				'title'       => __( 'Workspace access ready', 'astra' ),
				'eyebrow'     => __( 'Approved', 'astra' ),
				'description' => __( 'Your mocked sign-in succeeded and the account is approved for MVP access.', 'astra' ),
			),
		);
	}
}

if ( ! function_exists( 'abitai_auth_get_current_route_key' ) ) {
	/**
	 * Resolve the current request path to a supported auth route key.
	 *
	 * @return string
	 */
	function abitai_auth_get_current_route_key() {
		$path = '/';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}

		$path = '/' . trim( $path, '/' );

		if ( '/auth' === $path || '/auth/' === $path ) {
			return 'sign-in';
		}

		$aliases = array(
			'/auth/login'          => 'sign-in',
			'/auth/signin'         => 'sign-in',
			'/auth/sign-in'        => 'sign-in',
			'/auth/signup'         => 'signup',
			'/auth/sign-up'        => 'signup',
			'/auth/verify'         => 'verify',
			'/auth/verification'   => 'verify',
			'/auth/reset'          => 'reset',
			'/auth/reset-password' => 'reset',
			'/auth/forgot-password' => 'reset',
			'/auth/onboarding'     => 'onboarding',
			'/auth/review-pending' => 'review',
			'/auth/more-information' => 'more-info',
			'/auth/rejected'       => 'rejected',
			'/auth/app'            => 'app',
		);

		return isset( $aliases[ $path ] ) ? $aliases[ $path ] : '';
	}
}

if ( ! function_exists( 'abitai_auth_template_include' ) ) {
	/**
	 * Serve shared auth shell routes without requiring WordPress pages.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	function abitai_auth_template_include( $template ) {
		$route_key = abitai_auth_get_current_route_key();

		if ( '' === $route_key ) {
			return $template;
		}

		$GLOBALS['abitai_auth_route_key'] = $route_key;

		return ASTRA_THEME_DIR . 'template-auth.php';
	}
	add_filter( 'template_include', 'abitai_auth_template_include', 20 );
}

if ( ! function_exists( 'abitai_auth_document_title' ) ) {
	/**
	 * Use route-specific document titles for virtual auth routes.
	 *
	 * @param array<string,string> $title Document title parts.
	 * @return array<string,string>
	 */
	function abitai_auth_document_title( $title ) {
		$route_key = abitai_auth_get_current_route_key();
		$routes    = abitai_auth_get_routes();

		if ( '' !== $route_key && isset( $routes[ $route_key ] ) ) {
			$title['title'] = $routes[ $route_key ]['title'];
		}

		return $title;
	}
	add_filter( 'document_title_parts', 'abitai_auth_document_title' );
}
