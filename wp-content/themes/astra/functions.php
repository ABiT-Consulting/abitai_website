<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Constants
 */
define( 'ASTRA_THEME_VERSION', '3.9.4' );
define( 'ASTRA_THEME_SETTINGS', 'astra-settings' );
define( 'ASTRA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'ASTRA_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );
define( 'ASTRA_PRO_UPGRADE_URL', 'https://wpastra.com/pro/' );

/**
 * Minimum Version requirement of the Astra Pro addon.
 * This constant will be used to display the notice asking user to update the Astra addon to the version defined below.
 */
define( 'ASTRA_EXT_MIN_VER', '3.9.2' );

/**
 * Setup helper functions of Astra.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-theme-options.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-theme-strings.php';
require_once ASTRA_THEME_DIR . 'inc/core/common-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-icons.php';

/**
 * Update theme
 */
require_once ASTRA_THEME_DIR . 'inc/theme-update/astra-update-functions.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-background-updater.php';

/**
 * Fonts Files
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-font-families.php';
if ( is_admin() ) {
	require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts-data.php';
}

require_once ASTRA_THEME_DIR . 'inc/lib/webfont/class-astra-webfont-loader.php';
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts.php';

require_once ASTRA_THEME_DIR . 'inc/dynamic-css/custom-menu-old-header.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/container-layouts.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/astra-icons.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-walker-page.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-enqueue-scripts.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-gutenberg-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-wp-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/block-editor-compatibility.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/inline-on-mobile.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/content-background.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-dynamic-css.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-global-palette.php';

/**
 * Custom template tags for this theme.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-attr.php';
require_once ASTRA_THEME_DIR . 'inc/template-tags.php';

require_once ASTRA_THEME_DIR . 'inc/widgets.php';
require_once ASTRA_THEME_DIR . 'inc/core/theme-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/admin-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/sidebar-manager.php';

/**
 * Markup Functions
 */
require_once ASTRA_THEME_DIR . 'inc/markup-extras.php';
require_once ASTRA_THEME_DIR . 'inc/extras.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog-config.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog.php';
require_once ASTRA_THEME_DIR . 'inc/blog/single-blog.php';

/**
 * Markup Files
 */
require_once ASTRA_THEME_DIR . 'inc/template-parts.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-loop.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-mobile-header.php';

/**
 * Functions and definitions.
 */
require_once ASTRA_THEME_DIR . 'inc/class-astra-after-setup-theme.php';

// Required files.
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-helper.php';

require_once ASTRA_THEME_DIR . 'inc/schema/class-astra-schema.php';

if ( is_admin() ) {
	/**
	 * Admin Menu Settings
	 */
	require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-settings.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
}

/**
 * Metabox additions.
 */
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-boxes.php';

require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-box-operations.php';

/**
 * Customizer additions.
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-customizer.php';

/**
 * Astra Modules.
 */
require_once ASTRA_THEME_DIR . 'inc/modules/related-posts/class-astra-related-posts.php';

/**
 * Compatibility
 */
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gutenberg.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-jetpack.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/woocommerce/class-astra-woocommerce.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/edd/class-astra-edd.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/lifterlms/class-astra-lifterlms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/learndash/class-astra-learndash.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bb-ultimate-addon.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-contact-form-7.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-visual-composer.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-site-origin.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gravity-forms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bne-flyout.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-ubermeu.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-divi-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-amp.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-yoast-seo.php';
require_once ASTRA_THEME_DIR . 'inc/addons/transparent-header/class-astra-ext-transparent-header.php';
require_once ASTRA_THEME_DIR . 'inc/addons/breadcrumbs/class-astra-breadcrumbs.php';
require_once ASTRA_THEME_DIR . 'inc/addons/heading-colors/class-astra-heading-colors.php';
require_once ASTRA_THEME_DIR . 'inc/builder/class-astra-builder-loader.php';

// Elementor Compatibility requires PHP 5.4 for namespaces.
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor-pro.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-web-stories.php';
}

// Beaver Themer compatibility requires PHP 5.3 for anonymus functions.
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-themer.php';
}

require_once ASTRA_THEME_DIR . 'inc/core/markup/class-astra-markup.php';

/**
 * Load deprecated functions
 */
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-filters.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-functions.php';

/**
 * AbitAI Operator homepage section and navigation link.
 */
if ( ! function_exists( 'abitai_operator_render_section' ) ) {
	function abitai_operator_is_front_request() {
		if ( is_front_page() || is_home() ) {
			return true;
		}

		$path = '/';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}
		$path = rtrim( $path, '/' );

		return '' === $path || '/index.php' === $path;
	}

	function abitai_operator_get_section_html() {
		if ( ! abitai_operator_is_front_request() || ! empty( $GLOBALS['abitai_operator_rendered'] ) ) {
			return '';
		}

		$GLOBALS['abitai_operator_rendered'] = true;
		$demo_link  = esc_url( home_url( '/#contactus' ) );
		$sales_link = esc_url( home_url( '/#contactus' ) );

		ob_start();
		?>
		<section id="abitai-operator" class="abitai-operator-section">
			<div class="ast-container">
				<div class="abitai-operator-hero">
					<div class="abitai-operator-hero__media">
						<figure class="abitai-operator__mockup abitai-operator__mockup--primary">
							<img src="<?php echo esc_url( home_url( '/wp-content/uploads/2026/02/abitai-operator-ui.png' ) ); ?>" alt="AbitAI Operator workspace" loading="lazy" decoding="async">
							<figcaption class="abitai-operator__mockup-caption">Live operator workspace</figcaption>
						</figure>
					</div>
					<div class="abitai-operator-hero__content">
						<p class="abitai-operator__eyebrow">Product</p>
						<span class="abitai-operator__tag">Enterprise-ready desktop operator</span>
						<h2 class="abitai-operator__title">AbitAI Operator</h2>
						<p class="abitai-operator__subtitle">Quiet background intelligence for SAP &amp; Odoo operations.</p>
						<p class="abitai-operator__description">A desktop assistant that lets teams create, list, and update SAP/Odoo operations using plain-language chat.</p>
						<div class="abitai-operator__ctas">
							<a class="ast-button abitai-operator__cta-primary" href="<?php echo $demo_link; ?>">Request a Demo</a>
							<a class="ast-button abitai-operator__cta-secondary" href="<?php echo $sales_link; ?>">Contact Sales</a>
						</div>
						<div class="abitai-operator__panel">
							<h3>What it does</h3>
							<ul class="abitai-operator__bullets">
								<li>Create and update Sales Orders, Quotations, Purchase Orders/Requests</li>
								<li>Inventory Transfer Requests, GRN/GRPO lists</li>
								<li>Customer &amp; Item lists with export (Excel/PDF)</li>
								<li>SQL query results (read-only reporting)</li>
								<li>Non-technical staff can operate via chat</li>
								<li>Fast, consistent outputs</li>
							</ul>
						</div>
					</div>
				</div>

				<div class="abitai-operator__use-cases">
					<h3>Use cases</h3>
					<div class="abitai-operator__grid">
						<div class="abitai-operator__card">Sales Orders &amp; Quotations</div>
						<div class="abitai-operator__card">Purchases &amp; Requests</div>
						<div class="abitai-operator__card">Inventory Transfers</div>
						<div class="abitai-operator__card">Customer/Item Lists</div>
						<div class="abitai-operator__card">Reports &amp; Exports</div>
						<div class="abitai-operator__card">Quick Data Lookups</div>
					</div>
				</div>

				<p class="abitai-operator__security"><strong>Security &amp; Privacy:</strong> Data stays within your infrastructure. Access is controlled and auditable.</p>

				<div class="abitai-operator__how">
					<h3>How it works</h3>
					<div class="abitai-operator__steps">
						<div class="abitai-operator__step">
							<span class="abitai-operator__step-number">1</span>
							<span class="abitai-operator__step-text">Connect SAP/Odoo</span>
						</div>
						<div class="abitai-operator__step">
							<span class="abitai-operator__step-number">2</span>
							<span class="abitai-operator__step-text">Ask in chat</span>
						</div>
						<div class="abitai-operator__step">
							<span class="abitai-operator__step-number">3</span>
							<span class="abitai-operator__step-text">Get results instantly</span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	function abitai_operator_render_section() {
		$html = abitai_operator_get_section_html();
		if ( '' !== $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	add_action( 'astra_primary_content_bottom', 'abitai_operator_render_section', 99 );
	add_action( 'astra_content_bottom', 'abitai_operator_render_section', 99 );
	add_action( 'get_footer', 'abitai_operator_render_section', 5 );
}

if ( ! function_exists( 'abitai_operator_append_to_content' ) ) {
	function abitai_operator_append_to_content( $content ) {
		if ( ! abitai_operator_is_front_request() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( false !== strpos( $content, 'id="abitai-operator"' ) ) {
			return $content;
		}
		$section = abitai_operator_get_section_html();
		if ( '' === $section ) {
			return $content;
		}
		return $content . $section;
	}
	add_filter( 'the_content', 'abitai_operator_append_to_content', 50 );
}

if ( ! function_exists( 'abitai_operator_head_marker' ) ) {
	function abitai_operator_head_marker() {
		if ( ! abitai_operator_is_front_request() ) {
			return;
		}
		echo "\n<!-- AbitAI Operator active -->\n";
	}
	add_action( 'wp_head', 'abitai_operator_head_marker', 1 );
}

if ( ! function_exists( 'abitai_operator_enqueue_styles' ) ) {
	function abitai_operator_enqueue_styles() {
		$css = '
		:root {
			--abit-navy: #0b1b2b;
			--abit-navy-2: #102c44;
			--abit-ink: #0e1b2a;
			--abit-teal: #00b6b1;
			--abit-teal-soft: #c6f5f1;
			--abit-magenta: #0aaea8;
			--abit-magenta-dark: #087f79;
			--abit-surface: #ffffff;
			--abit-surface-alt: #f3f6fb;
			--abit-border: rgba(15,31,46,0.12);
			--abit-shadow: 0 18px 40px rgba(10,18,30,0.12);
			--ast-global-color-0: var(--abit-teal);
			--ast-global-color-1: var(--abit-magenta);
			--ast-global-color-2: var(--abit-navy-2);
			--ast-global-color-3: var(--abit-ink);
			--ast-global-color-4: #f5f7fb;
		}
		body {
			background: linear-gradient(180deg, #f5f7fb 0%, #ffffff 40%, #f1f5fb 100%);
			color: var(--abit-ink);
		}
		a {
			color: var(--abit-teal);
		}
		a:hover,
		a:focus {
			color: #0aa097;
		}
		h1, h2, h3, h4, h5, h6,
		.entry-title,
		.ast-archive-title {
			color: var(--abit-navy);
		}
		.main-header-bar {
			background: #ffffff;
			border-bottom: 1px solid var(--abit-border);
			box-shadow: 0 10px 24px rgba(10,18,30,0.06);
		}
		.main-header-menu a,
		.ast-header-break-point .main-header-menu a {
			color: var(--abit-ink);
			font-weight: 600;
		}
		.main-header-menu .current-menu-item > a,
		.main-header-menu a:hover,
		.main-header-menu a:focus {
			color: var(--abit-teal);
		}
		.ast-button,
		button,
		input[type="button"],
		input[type="submit"],
		input[type="reset"] {
			background: linear-gradient(135deg, var(--abit-teal), #4ee4d5);
			color: #082423;
			border: 1px solid var(--abit-teal);
			box-shadow: 0 12px 24px rgba(0,182,177,0.25);
		}
		.ast-button:hover,
		.ast-button:focus,
		button:hover,
		button:focus,
		input[type="button"]:hover,
		input[type="button"]:focus,
		input[type="submit"]:hover,
		input[type="submit"]:focus,
		input[type="reset"]:hover,
		input[type="reset"]:focus {
			background: linear-gradient(135deg, #0aaea8, #35cfc0);
			border-color: #0aaea8;
			color: #041c1b;
		}
		.ast-outline-button,
		.ast-button.ast-outline {
			background: transparent;
			color: var(--abit-navy);
			border: 1px solid var(--abit-border);
			box-shadow: none;
		}
		input[type="text"],
		input[type="email"],
		input[type="tel"],
		input[type="url"],
		input[type="password"],
		textarea,
		select {
			background: #ffffff;
			border: 1px solid var(--abit-border);
			box-shadow: inset 0 1px 2px rgba(10,18,30,0.04);
			color: var(--abit-ink);
		}
		.ast-article-post,
		.ast-article-single,
		.ast-separate-container .ast-article-post,
		.ast-separate-container .ast-article-single {
			background: var(--abit-surface);
			border: 1px solid var(--abit-border);
			box-shadow: var(--abit-shadow);
			border-radius: 16px;
		}
		.widget,
		.ast-single-post-order {
			background: var(--abit-surface);
			border: 1px solid var(--abit-border);
			box-shadow: 0 12px 24px rgba(10,18,30,0.06);
			border-radius: 14px;
		}
		.site-footer {
			background: var(--abit-navy);
			color: #d8e3f0;
		}
		.site-footer a {
			color: var(--abit-teal);
		}
		.site-footer a:hover,
		.site-footer a:focus {
			color: #ffffff;
		}
		#abitai-operator {
			scroll-margin-top: 96px;
		}
		.abitai-operator-section {
			--operator-accent: #00b6b1;
			--operator-dark: #0a2540;
			--operator-text: #1f2430;
			padding: 80px 0;
			background: linear-gradient(135deg, #f0fff9 0%, #f0fbf9 45%, #e9f2ff 100%);
			border-top: 1px solid var(--ast-border-color);
			border-bottom: 1px solid var(--ast-border-color);
			position: relative;
			overflow: hidden;
			color: var(--operator-text);
		}
		.abitai-operator-section::before,
		.abitai-operator-section::after {
			content: "";
			position: absolute;
			border-radius: 50%;
			filter: blur(0px);
			opacity: 0.22;
			pointer-events: none;
		}
		.abitai-operator-section::before {
			width: 420px;
			height: 420px;
			background: radial-gradient(circle, rgba(20, 184, 166, 0.28), transparent 65%);
			top: -120px;
			right: -120px;
		}
		.abitai-operator-section::after {
			width: 320px;
			height: 320px;
			background: radial-gradient(circle, rgba(0, 182, 177, 0.22), transparent 70%);
			bottom: -120px;
			left: -120px;
		}
		.abitai-operator-section .ast-container {
			position: relative;
			z-index: 1;
		}
		.abitai-operator-hero {
			display: grid;
			grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
			gap: 40px;
			align-items: start;
			margin-bottom: 48px;
		}
		.abitai-operator-hero__media {
			align-self: start;
		}
		.abitai-operator__eyebrow {
			text-transform: uppercase;
			letter-spacing: 0.14em;
			font-size: 0.75rem;
			color: var(--operator-accent);
			margin-bottom: 12px;
		}
		.abitai-operator__title {
			margin: 0 0 12px;
			text-transform: none;
			color: var(--operator-dark);
		}
		.abitai-operator__tag {
			display: inline-flex;
			align-items: center;
			padding: 6px 12px;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 600;
			letter-spacing: 0.01em;
			background: rgba(20, 184, 166, 0.12);
			color: #0f766e;
			border: 1px solid rgba(20, 184, 166, 0.35);
			margin-bottom: 14px;
		}
		.abitai-operator__subtitle {
			font-size: 1.25rem;
			font-weight: 600;
			color: var(--operator-text);
			margin-bottom: 12px;
		}
		.abitai-operator__description {
			max-width: 560px;
			margin-bottom: 24px;
		}
		.abitai-operator__ctas {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
		}
		.abitai-operator__cta-primary {
			background: var(--operator-accent);
			color: #ffffff;
			border: 1px solid var(--operator-accent);
			text-transform: none;
			font-weight: 700;
			letter-spacing: 0.02em;
		}
		.abitai-operator__cta-primary:hover,
		.abitai-operator__cta-primary:focus {
			background: #0aa097;
			border-color: #0aa097;
			color: #ffffff;
		}
		.abitai-operator__cta-secondary {
			background: #ffffff;
			border: 1px solid rgba(10, 37, 64, 0.15);
			color: var(--operator-dark);
			text-transform: none;
			font-weight: 600;
		}
		.abitai-operator__cta-secondary:hover,
		.abitai-operator__cta-secondary:focus {
			background: rgba(10, 37, 64, 0.1);
			color: var(--operator-dark);
		}
		.abitai-operator__mockup {
			background: linear-gradient(145deg, rgba(0, 182, 177, 0.12), rgba(10, 37, 64, 0.12));
			border: 1px solid rgba(10, 37, 64, 0.1);
			border-radius: 18px;
			padding: 16px;
			box-shadow: 0 20px 40px rgba(10, 37, 64, 0.12);
			text-align: center;
			margin: 0;
			max-width: 560px;
		}
		.abitai-operator__mockup--secondary {
			background: #ffffff;
			box-shadow: 0 12px 24px rgba(10, 37, 64, 0.08);
		}
		.abitai-operator__mockup img {
			width: 100%;
			height: auto;
			border-radius: 12px;
			display: block;
		}
		.abitai-operator__mockup-caption {
			display: inline-block;
			margin-top: 10px;
			font-size: 0.85rem;
			color: rgba(31, 36, 48, 0.7);
		}
		.abitai-operator__panel {
			background: #ffffff;
			border: 1px solid rgba(10, 37, 64, 0.1);
			border-radius: 18px;
			padding: 24px;
			box-shadow: 0 16px 32px rgba(10, 37, 64, 0.08);
		}
		.abitai-operator__panel h3 {
			margin-top: 0;
			margin-bottom: 16px;
			text-transform: none;
			color: var(--operator-dark);
		}
		.abitai-operator__bullets {
			list-style: none;
			margin: 0;
			padding: 0;
			display: grid;
			gap: 12px;
		}
		.abitai-operator__bullets li {
			padding-left: 20px;
			position: relative;
		}
		.abitai-operator__bullets li::before {
			content: "";
			position: absolute;
			left: 0;
			top: 0.55em;
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: var(--operator-accent);
		}
		.abitai-operator__use-cases {
			margin-bottom: 32px;
		}
		.abitai-operator__grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 16px;
			margin-top: 16px;
		}
		.abitai-operator__card {
			background: #ffffff;
			border: 1px solid rgba(10, 37, 64, 0.12);
			border-radius: 16px;
			padding: 18px 20px;
			font-weight: 600;
			color: var(--operator-dark);
			box-shadow: 0 12px 24px rgba(10, 37, 64, 0.05);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}
		.abitai-operator__card:hover {
			transform: translateY(-2px);
			box-shadow: 0 16px 30px rgba(10, 37, 64, 0.12);
		}
		.abitai-operator__security {
			background: rgba(0, 182, 177, 0.12);
			border-left: 4px solid var(--operator-accent);
			padding: 16px 20px;
			border-radius: 10px;
			margin-bottom: 36px;
		}
		.abitai-operator__how h3 {
			margin-bottom: 16px;
			text-transform: none;
			color: var(--operator-dark);
		}
		.abitai-operator__steps {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 16px;
		}
		.abitai-operator__step {
			background: #ffffff;
			border: 1px solid rgba(10, 37, 64, 0.12);
			border-radius: 16px;
			padding: 16px 18px;
			display: flex;
			align-items: center;
			gap: 12px;
			font-weight: 600;
			color: var(--operator-dark);
		}
		.abitai-operator__step-number {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: var(--operator-accent);
			color: #ffffff;
			font-weight: 700;
		}
		.elementor-540 .elementor-element-cdeaa5e {
			display: none;
		}
		@media (max-width: 921px) {
			.abitai-operator-hero {
				grid-template-columns: minmax(0, 1fr);
			}
			.abitai-operator__mockup {
				max-width: 100%;
			}
			.abitai-operator__grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
			.abitai-operator__steps {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}
		@media (max-width: 600px) {
			.abitai-operator-section {
				padding: 56px 0;
			}
			.abitai-operator__grid,
			.abitai-operator__steps {
				grid-template-columns: minmax(0, 1fr);
			}
			.abitai-operator__ctas {
				flex-direction: column;
				align-items: flex-start;
			}
		}
		.abitai-whatsapp-button {
			position: fixed;
			right: 20px;
			bottom: 92px;
			background: #25D366;
			color: #ffffff;
			border-radius: 999px;
			padding: 10px 16px;
			font-weight: 700;
			box-shadow: 0 12px 24px rgba(10, 37, 64, 0.18);
			text-decoration: none;
			z-index: 9999;
		}
		.abitai-whatsapp-button:hover,
		.abitai-whatsapp-button:focus {
			background: #1da851;
			color: #ffffff;
			text-decoration: none;
		}
		@media (max-width: 600px) {
			.abitai-whatsapp-button {
				right: 12px;
				bottom: 76px;
				padding: 9px 14px;
			}
		}
		body .elementor-button,
		body .elementor-button:visited,
		body .elementor-widget-button .elementor-button {
			background: linear-gradient(135deg, var(--abit-teal), #4fe3d5) !important;
			color: #082a27 !important;
			border-color: var(--abit-teal) !important;
			box-shadow: 0 14px 28px rgba(0, 182, 177, 0.25) !important;
		}
		body .elementor-button:hover,
		body .elementor-button:focus,
		body .elementor-widget-button .elementor-button:hover,
		body .elementor-widget-button .elementor-button:focus {
			background: linear-gradient(135deg, #0aa097, #33d6c7) !important;
			color: #041f1c !important;
		}
		body .hfe-nav-menu .menu-item a.hfe-menu-item,
		body .hfe-nav-menu .menu-item a.hfe-sub-menu-item {
			color: var(--abit-ink) !important;
		}
		body .hfe-nav-menu .menu-item a.hfe-menu-item:hover,
		body .hfe-nav-menu .menu-item a.hfe-menu-item:focus,
		body .hfe-nav-menu .menu-item.current-menu-item a.hfe-menu-item {
			color: var(--abit-teal) !important;
		}
		body .hfe-nav-menu .menu-item a.hfe-menu-item.elementor-button,
		body .hfe-nav-menu .menu-item a.hfe-menu-item.elementor-button:hover,
		body .hfe-nav-menu .menu-item a.hfe-menu-item.elementor-button:focus {
			color: #082a27 !important;
		}
		body .hfe-nav-menu-layout:not(.hfe-pointer__framed) .menu-item.parent a.hfe-menu-item:before,
		body .hfe-nav-menu-layout:not(.hfe-pointer__framed) .menu-item.parent a.hfe-menu-item:after,
		body .hfe-pointer__framed .menu-item.parent a.hfe-menu-item:before,
		body .hfe-pointer__framed .menu-item.parent a.hfe-menu-item:after {
			background-color: var(--abit-teal) !important;
			border-color: var(--abit-teal) !important;
		}
		body .elementor-widget-icon-box .elementor-icon,
		body .elementor-widget-icon-list .elementor-icon-list-icon i,
		body .elementor-widget-icon-list .elementor-icon-list-icon svg,
		body .elementor-widget-icon .elementor-icon,
		body .elementor-widget-icon-box .elementor-icon-box-icon i,
		body .elementor-widget-icon-box .elementor-icon-box-icon svg {
			color: var(--abit-teal) !important;
			fill: var(--abit-teal) !important;
			border-color: var(--abit-teal) !important;
		}
		body .elementor-widget-icon-box.elementor-view-stacked .elementor-icon {
			background-color: var(--abit-teal) !important;
		}
		body .elementor-widget-icon-box.elementor-view-framed .elementor-icon {
			border-color: var(--abit-teal) !important;
		}
		body .elementor-element.elementor-element-04954ae > .elementor-background-overlay {
			background: linear-gradient(135deg, rgba(11, 27, 43, 0.92), rgba(0, 182, 177, 0.25)) !important;
		}
		';

		wp_add_inline_style( 'astra-theme-css', $css );
	}
	add_action( 'wp_enqueue_scripts', 'abitai_operator_enqueue_styles' );
}

if ( ! function_exists( 'abitai_operator_add_menu_item' ) ) {
	function abitai_operator_add_menu_item( $items, $args ) {
		if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
			return $items;
		}

		$target = home_url( '/#abitai-operator' );
		if ( false !== strpos( $items, $target ) || false !== strpos( $items, '#abitai-operator' ) ) {
			return $items;
		}

		$items .= sprintf(
			'<li class="menu-item"><a href="%s">%s</a></li>',
			esc_url( $target ),
			esc_html( 'AbitAI Operator' )
		);

		return $items;
	}
	add_filter( 'wp_nav_menu_items', 'abitai_operator_add_menu_item', 10, 2 );
}

if ( ! function_exists( 'abitai_operator_hfe_menu_script' ) ) {
	function abitai_operator_hfe_menu_script() {
		$target = esc_url( home_url( '/#abitai-operator' ) );
		$label  = esc_html( 'AbitAI Operator' );
		?>
		<script>
			(function () {
				var targetHref = <?php echo wp_json_encode( $target ); ?>;
				var label = <?php echo wp_json_encode( $label ); ?>;
				var scrollOffset = 90;
				function normalizePath(path) {
					return path.replace(/\/$/, '');
				}
				function scrollToOperator() {
					var section = document.getElementById('abitai-operator');
					if (!section) {
						return;
					}
					section.scrollIntoView({ behavior: 'smooth', block: 'start' });
					window.scrollBy(0, -scrollOffset);
				}
				function handleHashOnLoad() {
					if (window.location.hash === '#abitai-operator') {
						setTimeout(scrollToOperator, 120);
					}
				}
				window.addEventListener('load', handleHashOnLoad);
				document.addEventListener('click', function (event) {
					var link = event.target.closest('a');
					if (!link) {
						return;
					}
					var isTarget = link.hash === '#abitai-operator' || link.getAttribute('href') === '#abitai-operator' || link.getAttribute('href') === targetHref;
					if (!isTarget) {
						return;
					}
					var samePage = link.origin === window.location.origin && normalizePath(link.pathname) === normalizePath(window.location.pathname);
					if (!samePage) {
						return;
					}
					event.preventDefault();
					history.pushState(null, '', '#abitai-operator');
					scrollToOperator();
				});
				var selector = 'a.hfe-menu-item';
				var existing = document.querySelector('a.hfe-menu-item[href="' + targetHref + '"], a.hfe-menu-item[href="#abitai-operator"]');
				if (existing) {
					return;
				}
				var firstLink = document.querySelector(selector);
				if (!firstLink) {
					return;
				}
				var list = firstLink.closest('ul');
				if (!list) {
					return;
				}
				var item = document.createElement('li');
				item.className = 'menu-item';
				var link = document.createElement('a');
				link.className = 'hfe-menu-item';
				link.href = targetHref;
				link.textContent = label;
				item.appendChild(link);
				list.appendChild(item);
			})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'abitai_operator_hfe_menu_script', 100 );
}

if ( ! function_exists( 'abitai_whatsapp_button' ) ) {
	function abitai_get_whatsapp_link() {
		$phone_raw = '+971 52 520 2381';
		$phone     = preg_replace( '/\D+/', '', $phone_raw );
		$message   = rawurlencode( 'Hi, I want a demo of AbitAI Operator.' );

		return 'https://wa.me/' . $phone . '?text=' . $message;
	}

	function abitai_whatsapp_button() {
		$link = abitai_get_whatsapp_link();
		?>
		<a class="abitai-whatsapp-button" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
			WhatsApp
		</a>
		<?php
	}
	add_action( 'wp_footer', 'abitai_whatsapp_button', 110 );
}

if ( ! function_exists( 'abitai_update_contact_cta_link' ) ) {
	function abitai_update_contact_cta_link() {
		$link = esc_url( abitai_get_whatsapp_link() );
		?>
		<script>
			(function () {
				var waLink = <?php echo wp_json_encode( $link ); ?>;
				var buttons = document.querySelectorAll('.elementor-widget-button a.elementor-button-link, .elementor-widget-button a.elementor-button');
				buttons.forEach(function (btn) {
					var text = (btn.textContent || '').trim().toLowerCase();
					if (text === 'contact us online') {
						btn.setAttribute('href', waLink);
						btn.setAttribute('target', '_blank');
						btn.setAttribute('rel', 'noopener noreferrer');
					}
				});
			})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'abitai_update_contact_cta_link', 120 );
}

