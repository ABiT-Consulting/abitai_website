<?php
/**
 * Shared abit.ai auth shell route template.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$routes    = function_exists( 'abitai_auth_get_routes' ) ? abitai_auth_get_routes() : array();
$route_key = isset( $GLOBALS['abitai_auth_route_key'] ) ? sanitize_key( $GLOBALS['abitai_auth_route_key'] ) : 'sign-in';

if ( ! isset( $routes[ $route_key ] ) ) {
	$route_key = 'sign-in';
}

$route        = $routes[ $route_key ];
$support_href = 'mailto:support@abit.ai';

status_header( 200 );
nocache_headers();

get_header();
?>

<main id="primary" class="site-main abit-auth-page abit-auth-route-page">
	<section class="abit-auth-app-shell" aria-labelledby="abit-auth-route-title">
		<aside class="abit-auth-brand-panel" aria-label="<?php echo esc_attr__( 'abit.ai access request support', 'astra' ); ?>">
			<a class="abit-auth-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="abit-auth-brand__mark" aria-hidden="true">ai</span>
				<span class="abit-auth-brand__name"><?php esc_html_e( 'abit.ai', 'astra' ); ?></span>
			</a>
			<div class="abit-auth-brand-panel__content">
				<p class="abit-auth-kicker"><?php esc_html_e( 'ERPNext access portal', 'astra' ); ?></p>
				<h1><?php esc_html_e( 'Business software access, reviewed by the abit.ai team.', 'astra' ); ?></h1>
				<p><?php esc_html_e( 'Request access, verify your business email, and return to the right next step from one secure auth surface.', 'astra' ); ?></p>
			</div>
			<a class="abit-auth-support-link" href="<?php echo esc_url( $support_href ); ?>"><?php esc_html_e( 'Need help? Contact support', 'astra' ); ?></a>
		</aside>

		<section class="abit-auth-route-panel">
			<div class="abit-auth-route-card" data-auth-route="<?php echo esc_attr( $route_key ); ?>">
				<header class="abit-auth-route-header">
					<p class="abit-auth-kicker"><?php echo esc_html( $route['eyebrow'] ); ?></p>
					<h2 id="abit-auth-route-title"><?php echo esc_html( $route['title'] ); ?></h2>
					<p><?php echo esc_html( $route['description'] ); ?></p>
				</header>

				<?php
				switch ( $route_key ) :
					case 'signup':
						abitai_auth_render_signup_slot();
						break;
					case 'verify':
						abitai_auth_render_verify_slot();
						break;
					case 'reset':
						abitai_auth_render_reset_slot();
						break;
					case 'sign-in':
					default:
						abitai_auth_render_sign_in_slot();
						break;
				endswitch;
				?>
			</div>
		</section>
	</section>
</main>

<?php
get_footer();

function abitai_auth_render_sign_in_slot() {
	?>
	<form class="abit-auth-form" action="<?php echo esc_url( wp_login_url() ); ?>" method="post">
		<div class="abit-auth-field">
			<label for="abit-auth-email"><?php esc_html_e( 'Email address', 'astra' ); ?></label>
			<input id="abit-auth-email" class="abit-auth-input" type="email" name="log" autocomplete="email" required />
		</div>
		<div class="abit-auth-field">
			<label for="abit-auth-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
			<input id="abit-auth-password" class="abit-auth-input" type="password" name="pwd" autocomplete="current-password" required />
		</div>
		<div class="abit-auth-inline-row">
			<label class="abit-auth-checkbox">
				<input type="checkbox" name="rememberme" value="forever" />
				<span><?php esc_html_e( 'Remember this device', 'astra' ); ?></span>
			</label>
			<a href="<?php echo esc_url( home_url( '/auth/reset' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'astra' ); ?></a>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Sign in', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<?php esc_html_e( 'New to abit.ai?', 'astra' ); ?>
		<a href="<?php echo esc_url( home_url( '/auth/signup' ) ); ?>"><?php esc_html_e( 'Start access request', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_signup_slot() {
	?>
	<div class="abit-auth-stepper" aria-label="<?php echo esc_attr__( 'Signup progress', 'astra' ); ?>">
		<div class="abit-auth-stepper__item is-active" aria-current="step"><span class="abit-auth-stepper__marker">1</span><span><?php esc_html_e( 'Account', 'astra' ); ?></span></div>
		<div class="abit-auth-stepper__item"><span class="abit-auth-stepper__marker">2</span><span><?php esc_html_e( 'Company', 'astra' ); ?></span></div>
		<div class="abit-auth-stepper__item"><span class="abit-auth-stepper__marker">3</span><span><?php esc_html_e( 'ERP needs', 'astra' ); ?></span></div>
	</div>
	<form class="abit-auth-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="abitai_user_signup" />
		<input type="hidden" name="abitai_redirect" value="<?php echo esc_url( home_url( '/auth/verify?state=sent' ) ); ?>" />
		<?php wp_nonce_field( 'abitai_user_signup', 'abitai_signup_nonce' ); ?>
		<div class="abit-auth-field">
			<label for="abit-auth-signup-name"><?php esc_html_e( 'Full name', 'astra' ); ?></label>
			<input id="abit-auth-signup-name" class="abit-auth-input" type="text" name="username" autocomplete="name" placeholder="<?php echo esc_attr__( 'Jane Ahmed', 'astra' ); ?>" required />
		</div>
		<div class="abit-auth-field">
			<label for="abit-auth-signup-email"><?php esc_html_e( 'Business email', 'astra' ); ?></label>
			<input id="abit-auth-signup-email" class="abit-auth-input" type="email" name="email" autocomplete="email" placeholder="<?php echo esc_attr__( 'jane@company.com', 'astra' ); ?>" required />
		</div>
		<div class="abit-auth-field">
			<label for="abit-auth-signup-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
			<input id="abit-auth-signup-password" class="abit-auth-input" type="password" name="password" autocomplete="new-password" minlength="12" required />
			<p class="abit-auth-help"><?php esc_html_e( 'Minimum 12 characters.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-field">
			<label for="abit-auth-signup-confirm-password"><?php esc_html_e( 'Confirm new password', 'astra' ); ?></label>
			<input id="abit-auth-signup-confirm-password" class="abit-auth-input" type="password" name="confirm_password" autocomplete="new-password" minlength="12" required />
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Continue', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<?php esc_html_e( 'Already requested access?', 'astra' ); ?>
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Sign in', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_verify_slot() {
	?>
	<div class="abit-auth-alert abit-auth-alert--info" role="status">
		<strong><?php esc_html_e( 'Verification required.', 'astra' ); ?></strong>
		<span><?php esc_html_e( 'Open the link in your email to verify your address before product access.', 'astra' ); ?></span>
	</div>
	<div class="abit-auth-status-box">
		<span><?php esc_html_e( 'Verification email sent to:', 'astra' ); ?></span>
		<strong><?php esc_html_e( 'your business email', 'astra' ); ?></strong>
	</div>
	<div class="abit-auth-actions">
		<a class="abit-auth-button abit-auth-button--primary" href="mailto:"><?php esc_html_e( 'Open email app', 'astra' ); ?></a>
		<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( home_url( '/auth/signup' ) ); ?>"><?php esc_html_e( 'Change email', 'astra' ); ?></a>
	</div>
	<p class="abit-auth-route-footer">
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
		<span aria-hidden="true">|</span>
		<a href="mailto:support@abit.ai"><?php esc_html_e( 'Contact support', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_reset_slot() {
	?>
	<form class="abit-auth-form" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
		<div class="abit-auth-field">
			<label for="abit-auth-reset-email"><?php esc_html_e( 'Email address', 'astra' ); ?></label>
			<input id="abit-auth-reset-email" class="abit-auth-input" type="email" name="user_login" autocomplete="email" required />
			<p class="abit-auth-help"><?php esc_html_e( 'If an eligible account exists, reset instructions will be sent to that address.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Send reset instructions', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
	</p>
	<?php
}
