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
	'missing_fields'    => __( 'Complete this field to continue.', 'astra' ),
	'invalid_email'     => __( 'Enter a valid business email address.', 'astra' ),
	'password_mismatch' => __( 'Passwords do not match.', 'astra' ),
	'user_exists'       => __( 'A request may already exist for this email. Sign in or check your inbox for the next step.', 'astra' ),
	'create_failed'     => __( 'We could not start your access request right now. Please try again.', 'astra' ),
);
?>

<main id="primary" class="site-main abitai-signup-page">
	<section class="abitai-signup-card">
		<h1><?php esc_html_e( 'Start access request', 'astra' ); ?></h1>
		<p class="abitai-signup-subtitle"><?php esc_html_e( 'Tell us who should receive updates for this business request.', 'astra' ); ?></p>

		<?php if ( '1' === $signup_success ) : ?>
			<div class="abitai-signup-notice success"><?php esc_html_e( 'Request started. Check your email for the next step.', 'astra' ); ?></div>
		<?php elseif ( isset( $error_messages[ $signup_error ] ) ) : ?>
			<div class="abitai-signup-notice error"><?php echo esc_html( $error_messages[ $signup_error ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abitai-signup-form">
			<input type="hidden" name="action" value="abitai_user_signup" />
			<input type="hidden" name="abitai_redirect" value="<?php echo esc_url( get_permalink() ); ?>" />
			<?php wp_nonce_field( 'abitai_user_signup', 'abitai_signup_nonce' ); ?>

			<label for="signup-username"><?php esc_html_e( 'Full name', 'astra' ); ?></label>
			<input id="signup-username" type="text" name="username" placeholder="<?php echo esc_attr__( 'Jane Ahmed', 'astra' ); ?>" required />

			<label for="signup-email"><?php esc_html_e( 'Business email', 'astra' ); ?></label>
			<input id="signup-email" type="email" name="email" placeholder="<?php echo esc_attr__( 'jane@company.com', 'astra' ); ?>" required />

			<label for="signup-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
			<input id="signup-password" type="password" name="password" minlength="12" placeholder="<?php echo esc_attr__( 'Create a password', 'astra' ); ?>" required />

			<label for="signup-confirm-password"><?php esc_html_e( 'Confirm new password', 'astra' ); ?></label>
			<input id="signup-confirm-password" type="password" name="confirm_password" minlength="12" required />

			<button type="submit" class="abitai-primary-button"><?php esc_html_e( 'Continue', 'astra' ); ?></button>
		</form>

		<?php if ( '' !== $google_url || '' !== $facebook_url ) : ?>
			<div class="abitai-signup-divider"><span><?php esc_html_e( 'or continue with', 'astra' ); ?></span></div>
			<div class="abitai-social-buttons">
				<?php if ( '' !== $google_url ) : ?>
					<a href="<?php echo $google_url; ?>" class="abitai-social-button google"><?php esc_html_e( 'Continue with Google', 'astra' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $facebook_url ) : ?>
					<a href="<?php echo $facebook_url; ?>" class="abitai-social-button facebook"><?php esc_html_e( 'Continue with Facebook', 'astra' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
