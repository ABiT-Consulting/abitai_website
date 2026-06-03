<?php
/**
 * Template Name: ABiT Signup
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$social_urls   = function_exists( 'abitai_get_social_signup_urls' ) ? abitai_get_social_signup_urls() : array();
$google_url    = isset( $social_urls['google'] ) ? esc_url( $social_urls['google'] ) : '';
$facebook_url  = isset( $social_urls['facebook'] ) ? esc_url( $social_urls['facebook'] ) : '';
$signup_error  = isset( $_GET['signup_error'] ) ? sanitize_key( wp_unslash( $_GET['signup_error'] ) ) : '';
$signup_success = isset( $_GET['signup_success'] ) ? sanitize_key( wp_unslash( $_GET['signup_success'] ) ) : '';

$error_messages = array(
	'invalid_nonce'     => __( 'Your session expired. Please try submitting again.', 'astra' ),
	'missing_fields'    => __( 'Please complete all required fields.', 'astra' ),
	'invalid_email'     => __( 'Please provide a valid email address.', 'astra' ),
	'password_mismatch' => __( 'Passwords do not match.', 'astra' ),
	'user_exists'       => __( 'An account with this username or email already exists.', 'astra' ),
	'create_failed'     => __( 'We could not create your account right now. Please try again later.', 'astra' ),
);
?>

<main id="primary" class="site-main abitai-signup-page">
	<section class="abitai-signup-card">
		<h1><?php esc_html_e( 'Create your account', 'astra' ); ?></h1>
		<p class="abitai-signup-subtitle"><?php esc_html_e( 'Sign up with email or continue with Google / Facebook.', 'astra' ); ?></p>

		<?php if ( '1' === $signup_success ) : ?>
			<div class="abitai-signup-notice success"><?php esc_html_e( 'Welcome! Your account was created and you are now logged in.', 'astra' ); ?></div>
		<?php elseif ( isset( $error_messages[ $signup_error ] ) ) : ?>
			<div class="abitai-signup-notice error"><?php echo esc_html( $error_messages[ $signup_error ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abitai-signup-form">
			<input type="hidden" name="action" value="abitai_user_signup" />
			<input type="hidden" name="abitai_redirect" value="<?php echo esc_url( get_permalink() ); ?>" />
			<?php wp_nonce_field( 'abitai_user_signup', 'abitai_signup_nonce' ); ?>

			<label for="signup-username"><?php esc_html_e( 'Username', 'astra' ); ?></label>
			<input id="signup-username" type="text" name="username" required />

			<label for="signup-email"><?php esc_html_e( 'Email', 'astra' ); ?></label>
			<input id="signup-email" type="email" name="email" required />

			<label for="signup-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
			<input id="signup-password" type="password" name="password" minlength="8" required />

			<label for="signup-confirm-password"><?php esc_html_e( 'Confirm password', 'astra' ); ?></label>
			<input id="signup-confirm-password" type="password" name="confirm_password" minlength="8" required />

			<button type="submit" class="abitai-primary-button"><?php esc_html_e( 'Sign Up', 'astra' ); ?></button>
		</form>

		<div class="abitai-signup-divider"><span><?php esc_html_e( 'or continue with', 'astra' ); ?></span></div>
		<div class="abitai-social-buttons">
			<?php if ( '' !== $google_url ) : ?>
				<a href="<?php echo $google_url; ?>" class="abitai-social-button google"><?php esc_html_e( 'Continue with Google', 'astra' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $facebook_url ) : ?>
				<a href="<?php echo $facebook_url; ?>" class="abitai-social-button facebook"><?php esc_html_e( 'Continue with Facebook', 'astra' ); ?></a>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
