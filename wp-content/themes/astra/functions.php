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
		#abitai-operator {
			scroll-margin-top: 96px;
		}
		.abitai-operator-section {
			--operator-accent: #a42593;
			--operator-dark: #0a2540;
			--operator-text: #1f2430;
			padding: 80px 0;
			background: linear-gradient(135deg, #f0fff9 0%, #f7f2ff 45%, #e9f2ff 100%);
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
			background: radial-gradient(circle, rgba(164, 37, 147, 0.22), transparent 70%);
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
			background: #7e1b6b;
			border-color: #7e1b6b;
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
			background: linear-gradient(145deg, rgba(164, 37, 147, 0.12), rgba(10, 37, 64, 0.12));
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
			background: rgba(164, 37, 147, 0.08);
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
		:root {
			--mint-0: #e7fff6;
			--mint-1: #d4fff2;
			--mint-2: #bff7e9;
			--mint-3: #9eead6;
			--teal-0: #00bfa5;
			--teal-1: #19c6b0;
			--teal-2: #48d8c4;
			--ink-0: #0f1f1b;
			--ink-1: #3c4b46;
			--card: rgba(255,255,255,0.85);
			--border: rgba(7,38,30,0.08);
			--shadow-soft: 0 18px 40px rgba(12,34,26,0.12);
			--shadow-btn: 0 12px 20px rgba(12,34,26,0.18);
		}
		.abitai-demo-chat {
			position: fixed;
			right: 20px;
			bottom: 20px;
			z-index: 9999;
			font-family: inherit;
		}
		.abitai-demo-chat__toggle {
			border: none;
			border-radius: 999px;
			padding: 12px 18px;
			font-weight: 700;
			cursor: pointer;
			background: linear-gradient(135deg, #00c4a7, #46e0c7);
			color: var(--ink-0);
			box-shadow: var(--shadow-btn);
			transition: box-shadow 0.2s ease, transform 0.2s ease;
		}
		.abitai-demo-chat__toggle:hover,
		.abitai-demo-chat__toggle:focus {
			box-shadow: 0 16px 28px rgba(12,34,26,0.2);
			transform: translateY(-1px);
		}
		.abitai-demo-chat__panel {
			position: absolute;
			right: 0;
			bottom: 62px;
			width: 380px;
			max-height: 560px;
			display: none;
		}
		.abitai-demo-chat__panel.is-open {
			display: block;
		}
		.abitai-demo-chat__frame {
			background: linear-gradient(135deg, var(--mint-1), #dffaf2 35%, #c6f2e4 70%, #baf0dc);
			border-radius: 28px;
			padding: 18px;
			box-shadow: var(--shadow-soft);
			border: 1px solid var(--border);
		}
		.abitai-demo-chat__card {
			background: var(--card);
			border-radius: 26px;
			border: 1px solid var(--border);
			padding: 22px;
			box-shadow: var(--shadow-soft);
			display: flex;
			flex-direction: column;
			gap: 16px;
			max-height: 520px;
		}
		.abitai-demo-chat__header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
		}
		.abitai-demo-chat__meta {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.abitai-demo-chat__brand {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.abitai-demo-chat__brand-circle {
			width: 40px;
			height: 40px;
			border-radius: 16px;
			background: linear-gradient(145deg, #84f0c6, #5cdac0, #2db7b0);
			box-shadow: 0 10px 18px rgba(12,34,26,0.18);
		}
		.abitai-demo-chat__title {
			font-weight: 700;
			color: var(--ink-0);
		}
		.abitai-demo-chat__subtitle {
			font-size: 0.8rem;
			color: var(--ink-1);
		}
		.abitai-demo-chat__badge {
			font-size: 0.7rem;
			padding: 6px 10px;
			border-radius: 999px;
			background: rgba(255,255,255,0.7);
			border: 1px solid var(--border);
			color: var(--ink-1);
			font-weight: 600;
		}
		.abitai-demo-chat__close {
			background: transparent;
			border: none;
			font-size: 1.1rem;
			color: var(--ink-1);
			cursor: pointer;
		}
		.abitai-demo-chat__messages {
			display: flex;
			flex-direction: column;
			gap: 12px;
			overflow-y: auto;
			padding-right: 4px;
			flex: 1;
			min-height: 160px;
		}
		.abitai-demo-chat__bubble {
			padding: 10px 12px;
			border-radius: 18px;
			font-size: 0.88rem;
			line-height: 1.45;
			box-shadow: 0 8px 18px rgba(12,34,26,0.08);
			max-width: 92%;
			overflow: hidden;
		}
		.abitai-demo-chat__bubble--user {
			align-self: flex-end;
			background: linear-gradient(135deg, var(--mint-2), var(--mint-1));
			border: 1px solid #7ee3c9;
			color: var(--ink-0);
		}
		.abitai-demo-chat__bubble--bot {
			align-self: flex-start;
			background: #ffffff;
			border: 1px solid #e9f6f1;
			color: var(--ink-0);
		}
		.abitai-demo-chat__cta {
			margin-top: 8px;
			font-weight: 600;
			color: var(--ink-1);
		}
		.abitai-demo-chat__quick {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}
		.abitai-demo-chat__quick button {
			border: 1px solid var(--border);
			background: rgba(255,255,255,0.8);
			padding: 6px 12px;
			border-radius: 999px;
			font-size: 0.72rem;
			color: var(--ink-0);
			cursor: pointer;
			box-shadow: 0 8px 14px rgba(12,34,26,0.08);
			transition: box-shadow 0.2s ease, transform 0.2s ease;
		}
		.abitai-demo-chat__quick button:hover,
		.abitai-demo-chat__quick button:focus {
			box-shadow: 0 12px 20px rgba(12,34,26,0.16);
			transform: translateY(-1px);
		}
		.abitai-demo-chat__input {
			display: flex;
			gap: 10px;
			align-items: center;
		}
		.abitai-demo-chat__input input {
			flex: 1;
			border: 1px solid var(--border);
			border-radius: 999px;
			padding: 8px 12px;
			font-size: 0.85rem;
			background: rgba(255,255,255,0.9);
			color: var(--ink-0);
		}
		.abitai-demo-chat__input button {
			border: none;
			border-radius: 999px;
			padding: 8px 14px;
			font-weight: 600;
			background: linear-gradient(135deg, #00c4a7, #46e0c7);
			color: var(--ink-0);
			cursor: pointer;
			box-shadow: var(--shadow-btn);
			transition: box-shadow 0.2s ease, transform 0.2s ease;
		}
		.abitai-demo-chat__input button:hover,
		.abitai-demo-chat__input button:focus {
			box-shadow: 0 16px 28px rgba(12,34,26,0.2);
			transform: translateY(-1px);
		}
		.abitai-demo-chat__table {
			width: 100%;
			border-collapse: collapse;
			font-size: 0.75rem;
			margin-top: 8px;
			table-layout: fixed;
			word-break: break-word;
			overflow-wrap: anywhere;
		}
		.abitai-demo-chat__table th,
		.abitai-demo-chat__table td {
			padding: 6px 8px;
			border-bottom: 1px solid rgba(7,38,30,0.08);
			text-align: left;
			word-break: break-word;
			overflow-wrap: anywhere;
		}
		.abitai-demo-chat__table th {
			background: rgba(15,31,27,0.06);
			font-weight: 600;
			color: var(--ink-1);
		}
		.abitai-demo-chat__table tr:last-child td {
			border-bottom: none;
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
			.abitai-demo-chat__panel {
				width: min(92vw, 380px);
				right: 0;
			}
			.abitai-demo-chat {
				right: 12px;
				bottom: 12px;
			}
			.abitai-whatsapp-button {
				right: 12px;
				bottom: 76px;
				padding: 9px 14px;
			}
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
	function abitai_whatsapp_button() {
		$phone_raw = '+971 52 520 2381';
		$phone     = preg_replace( '/\D+/', '', $phone_raw );
		$message   = rawurlencode( 'Hi, I want a demo of AbitAI Operator.' );
		$link      = 'https://wa.me/' . $phone . '?text=' . $message;
		?>
		<a class="abitai-whatsapp-button" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
			WhatsApp
		</a>
		<?php
	}
	add_action( 'wp_footer', 'abitai_whatsapp_button', 110 );
}

if ( ! function_exists( 'abitai_operator_chat_widget' ) ) {
	function abitai_operator_chat_widget() {
		if ( function_exists( 'abitai_operator_is_front_request' ) && ! abitai_operator_is_front_request() ) {
			return;
		}
		?>
		<div class="abitai-demo-chat" id="abitaiDemoChat">
			<button class="abitai-demo-chat__toggle" type="button" id="abitaiDemoChatToggle">Operator Demo</button>
			<div class="abitai-demo-chat__panel" id="abitaiDemoChatPanel" aria-hidden="true">
				<div class="abitai-demo-chat__frame">
					<div class="abitai-demo-chat__card">
						<div class="abitai-demo-chat__header">
							<div class="abitai-demo-chat__brand">
								<span class="abitai-demo-chat__brand-circle" aria-hidden="true"></span>
								<div>
									<div class="abitai-demo-chat__title">AbitAI Operator</div>
									<div class="abitai-demo-chat__subtitle">Quiet background intelligence</div>
								</div>
							</div>
							<div class="abitai-demo-chat__meta">
								<span class="abitai-demo-chat__badge">Demo data</span>
								<button class="abitai-demo-chat__close" type="button" id="abitaiDemoChatClose" aria-label="Close">&times;</button>
							</div>
						</div>
						<div class="abitai-demo-chat__messages" id="abitaiDemoChatMessages"></div>
						<div class="abitai-demo-chat__quick" id="abitaiDemoChatQuick">
							<button type="button" data-question="sales order list">Sales Orders</button>
							<button type="button" data-question="sales quotation list">Sales Quotations</button>
							<button type="button" data-question="purchase order list">Purchase Orders</button>
							<button type="button" data-question="purchase request list">Purchase Requests</button>
							<button type="button" data-question="inventory transfer request">Inventory Transfers</button>
							<button type="button" data-question="customers list">Customers</button>
						</div>
						<div class="abitai-demo-chat__input">
							<input type="text" id="abitaiDemoChatInput" placeholder="Ask about AbitAI Operator..." autocomplete="off">
							<button type="button" id="abitaiDemoChatSend">Send</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			(function () {
				var panel = document.getElementById('abitaiDemoChatPanel');
				var toggle = document.getElementById('abitaiDemoChatToggle');
				var closeBtn = document.getElementById('abitaiDemoChatClose');
				var messages = document.getElementById('abitaiDemoChatMessages');
				var input = document.getElementById('abitaiDemoChatInput');
				var sendBtn = document.getElementById('abitaiDemoChatSend');
				var quick = document.getElementById('abitaiDemoChatQuick');

				if (!panel || !toggle || !messages || !input || !sendBtn) {
					return;
				}

				var greetedKey = 'abitai_demo_chat_greeted_v1';
				var ctaText = 'Contact us for a demo.';

				function appendMessage(text, type, asHtml) {
					var msg = document.createElement('div');
					msg.className = 'abitai-demo-chat__bubble ' + (type === 'user' ? 'abitai-demo-chat__bubble--user' : 'abitai-demo-chat__bubble--bot');
					if (asHtml) {
						msg.innerHTML = text;
					} else {
						msg.textContent = text;
					}
					messages.appendChild(msg);
					messages.scrollTop = messages.scrollHeight;
				}

				function addGreetingIfNeeded() {
					if (localStorage.getItem(greetedKey)) {
						return;
					}
					appendMessage('May I help you?', 'bot', false);
					localStorage.setItem(greetedKey, '1');
				}

				function normalize(text) {
					return text.toLowerCase();
				}

				function buildTable(title, headers, rows) {
					var html = '<strong>' + title + '</strong>';
					html += '<table class="abitai-demo-chat__table"><thead><tr>';
					headers.forEach(function (header) {
						html += '<th>' + header + '</th>';
					});
					html += '</tr></thead><tbody>';
					rows.forEach(function (row) {
						html += '<tr>';
						row.forEach(function (cell) {
							html += '<td>' + cell + '</td>';
						});
						html += '</tr>';
					});
					html += '</tbody></table>';
					html += '<div class="abitai-demo-chat__cta">' + ctaText + '</div>';
					return html;
				}

				function answerFor(text) {
					var normalized = normalize(text);

					if (normalized.indexOf('grpo') !== -1 || normalized.indexOf('goods receipt po') !== -1) {
						return buildTable(
							'GRPO',
							['DocNum', 'Vendor', 'Date', 'Total'],
							[
								['GRPO-8801', 'Green Star Trading', '2026-01-13', '18,450'],
								['GRPO-8802', 'Ridan Supplies', '2026-01-19', '7,220'],
								['GRPO-8803', 'Nexa Systems', '2026-01-23', '3,980'],
								['GRPO-8804', 'Apex Tools', '2026-01-29', '21,300']
							]
						);
					}
					if (normalized.indexOf('grn') !== -1 || normalized.indexOf('goods receipt') !== -1) {
						return buildTable(
							'GRN',
							['DocNum', 'Vendor', 'Date', 'Total'],
							[
								['GRN-7801', 'Green Star Trading', '2026-01-12', '18,450'],
								['GRN-7802', 'Ridan Supplies', '2026-01-18', '7,220'],
								['GRN-7803', 'Nexa Systems', '2026-01-22', '3,980'],
								['GRN-7804', 'Apex Tools', '2026-01-28', '21,300']
							]
						);
					}
					if (normalized.indexOf('purchase order') !== -1) {
						return buildTable(
							'Purchase Orders',
							['DocNum', 'Vendor', 'Date', 'Total'],
							[
								['230145', 'Green Star Trading', '2026-01-12', '18,450'],
								['230146', 'Ridan Supplies', '2026-01-18', '7,220'],
								['230147', 'Nexa Systems', '2026-01-21', '3,980'],
								['230148', 'Apex Tools', '2026-01-28', '21,300']
							]
						);
					}
					if (normalized.indexOf('purchase quotation') !== -1) {
						return buildTable(
							'Purchase Quotations',
							['DocNum', 'Vendor', 'Date', 'Total'],
							[
								['44031', 'Green Star Trading', '2026-01-10', '17,900'],
								['44032', 'Ridan Supplies', '2026-01-16', '7,050'],
								['44033', 'Nexa Systems', '2026-01-20', '4,100'],
								['44034', 'Apex Tools', '2026-01-26', '20,900']
							]
						);
					}
					if (normalized.indexOf('purchase request') !== -1) {
						return buildTable(
							'Purchase Requests',
							['DocNum', 'Requester', 'Date', 'Status'],
							[
								['PR-901', 'Ayesha', '2026-01-08', 'Open'],
								['PR-902', 'Hasan', '2026-01-15', 'Approved'],
								['PR-903', 'Maryam', '2026-01-23', 'Open'],
								['PR-904', 'Saad', '2026-01-29', 'Closed']
							]
						);
					}
					if (normalized.indexOf('sales order') !== -1) {
						return buildTable(
							'Sales Orders',
							['DocNum', 'Customer', 'Date', 'Total'],
							[
								['540210', 'Maxitech', '2026-01-09', '9,840'],
								['540211', 'River Inc', '2026-01-14', '6,220'],
								['540212', 'Earthshaker', '2026-01-22', '14,300'],
								['540213', 'Aquent Sys', '2026-01-30', '2,950']
							]
						);
					}
					if (normalized.indexOf('sales quotation') !== -1) {
						return buildTable(
							'Sales Quotations',
							['DocNum', 'Customer', 'Date', 'Total'],
							[
								['SQ-1201', 'Maxitech', '2026-01-06', '9,200'],
								['SQ-1202', 'River Inc', '2026-01-13', '6,100'],
								['SQ-1203', 'Earthshaker', '2026-01-19', '13,950'],
								['SQ-1204', 'Aquent Sys', '2026-01-27', '2,800']
							]
						);
					}
					if (normalized.indexOf('inventory transfer') !== -1) {
						return buildTable(
							'Inventory Transfer Requests',
							['DocNum', 'From', 'To', 'Date'],
							[
								['ITR-310', 'WH-01', 'WH-03', '2026-01-11'],
								['ITR-311', 'WH-02', 'WH-01', '2026-01-17'],
								['ITR-312', 'WH-03', 'WH-02', '2026-01-24'],
								['ITR-313', 'WH-01', 'WH-04', '2026-01-31']
							]
						);
					}
					if (normalized.indexOf('customers') !== -1) {
						return buildTable(
							'Customers',
							['CardCode', 'CardName', 'Phone', 'Email'],
							[
								['C20000', 'Maxi Teq', '02 5894 9410', 'info@maxi-teq.com'],
								['C23900', 'Parameter Tech', '02 5894 9445', 'info@parameter.com.au'],
								['C25000', 'Star Company', '0042 582 5633', 'info@starcomp.eu'],
								['C26000', 'River Inc', '0044 161 869 9000', 'info@riveri.co.uk']
							]
						);
					}
					if (normalized.indexOf('items') !== -1) {
						return buildTable(
							'Items',
							['ItemCode', 'ItemName', 'UoM', 'Price'],
							[
								['A00001', 'Motherboard MicroATX', 'Each', '320'],
								['A00002', 'Quadcore CPU 3.4 GHz', 'Each', '480'],
								['A00003', '1TB SSD', 'Each', '210'],
								['A00004', '16GB RAM Kit', 'Each', '140']
							]
						);
					}

					if (normalized.indexOf('abitai') !== -1 || normalized.indexOf('abit ai') !== -1 || normalized.indexOf('operator') !== -1) {
						return 'AbitAI Operator connects to SAP/Odoo so teams can run operations through chat. Ask in plain language and get instant, consistent results. ' + ctaText;
					}

					return 'AbitAI Operator handles sales, purchasing, inventory, and reporting through a secure chat-based workflow. Connect SAP/Odoo and it returns results instantly. ' + ctaText;
				}

				function handleSend(text) {
					var trimmed = text.trim();
					if (!trimmed) {
						return;
					}
					appendMessage(trimmed, 'user', false);
					appendMessage(answerFor(trimmed), 'bot', true);
				}

				toggle.addEventListener('click', function () {
					var open = panel.classList.toggle('is-open');
					panel.setAttribute('aria-hidden', open ? 'false' : 'true');
					if (open) {
						addGreetingIfNeeded();
						setTimeout(function () { input.focus(); }, 50);
					}
				});

				if (closeBtn) {
					closeBtn.addEventListener('click', function () {
						panel.classList.remove('is-open');
						panel.setAttribute('aria-hidden', 'true');
					});
				}

				sendBtn.addEventListener('click', function () {
					handleSend(input.value);
					input.value = '';
				});

				input.addEventListener('keydown', function (event) {
					if (event.key === 'Enter') {
						event.preventDefault();
						handleSend(input.value);
						input.value = '';
					}
				});

				if (quick) {
					quick.addEventListener('click', function (event) {
						var button = event.target.closest('button[data-question]');
						if (!button) {
							return;
						}
						handleSend(button.getAttribute('data-question'));
					});
				}
			})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'abitai_operator_chat_widget', 120 );
}
