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
 * abit.ai SaaS auth route shell.
 */
require_once ASTRA_THEME_DIR . 'inc/abitai-auth-schema.php';
require_once ASTRA_THEME_DIR . 'inc/abitai-erp-access-gate.php';
require_once ASTRA_THEME_DIR . 'inc/abitai-auth-routes.php';
require_once ASTRA_THEME_DIR . 'inc/abitai-auth-resend-verification-api.php';
require_once ASTRA_THEME_DIR . 'inc/abitai-auth-password-reset.php';
require_once ASTRA_THEME_DIR . 'inc/abitai-company-profile-api.php';

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
		$demo_link  = esc_url( 'https://operator.abit.ai/' );
		$sales_link = esc_url( home_url( '/#contactus' ) );

		ob_start();
		?>
		<section id="abitai-operator" class="abitai-operator-section">
			<div class="ast-container">
				<div class="abitai-operator-hero">
					<div class="abitai-operator-hero__media">
						<figure class="abitai-operator__mockup abitai-operator__mockup--primary">
							<div class="abitai-operator-app" role="img" aria-label="AbitAI Operator workspace with chat, image attachment, and task results">
								<div class="abitai-operator-app__sidebar">
									<span class="abitai-operator-app__logo">AI</span>
									<span></span>
									<span></span>
									<span></span>
								</div>
								<div class="abitai-operator-app__main">
									<div class="abitai-operator-app__topbar">
										<div>
											<strong>Sales Order Assistant</strong>
											<small>SAP B1 connected</small>
										</div>
										<span class="abitai-operator-app__status">Secure</span>
									</div>
									<div class="abitai-operator-app__conversation">
										<div class="abitai-operator-app__bubble abitai-operator-app__bubble--user">Create a sales order from this customer PO.</div>
										<div class="abitai-operator-app__attachment">
											<span class="abitai-operator-app__image-icon" aria-hidden="true"></span>
											<div>
												<strong>PO-image-0426.png</strong>
												<small>Image ready for extraction</small>
											</div>
										</div>
										<div class="abitai-operator-app__bubble abitai-operator-app__bubble--assistant">I found the customer, items, quantities, and delivery date. Draft order is ready for review.</div>
									</div>
									<div class="abitai-operator-app__composer">
										<button type="button" class="abitai-operator-app__attach" aria-label="Attach image" data-operator-image-trigger>
											<span aria-hidden="true"></span>
										</button>
										<input
											type="file"
											class="abitai-operator-app__attachment-input"
											accept="image/*"
											aria-label="Choose an image to attach"
											data-operator-image-input
										/>
										<span class="abitai-operator-app__placeholder">Ask in chat or attach an image</span>
										<button type="button" class="abitai-operator-app__send" aria-label="Send message">Send</button>
									</div>
								</div>
							</div>
							<figcaption class="abitai-operator__mockup-caption">Chat workspace with image attachments</figcaption>
						</figure>
						<div class="abitai-operator__video">
							<button class="abitai-operator__video-trigger" type="button" aria-label="Play AbitAI Operator demo video" data-video-id="0gi6xlinoZ8" style="--video-thumb: url('https://img.youtube.com/vi/0gi6xlinoZ8/hqdefault.jpg');">
								<span class="abitai-operator__video-play" aria-hidden="true"></span>
								<span class="abitai-operator__video-label">Watch demo video</span>
							</button>
						</div>
					</div>
					<div class="abitai-operator-hero__content">
						<p class="abitai-operator__eyebrow">Product</p>
						<span class="abitai-operator__tag">Enterprise-ready SAP operator</span>
						<h2 class="abitai-operator__title">AbitAI Operator</h2>
						<p class="abitai-operator__subtitle">SAP-wide operations for end-to-end SAP tasks and enterprise-wide workflows.</p>
						<p class="abitai-operator__description">AbitAI Operator is a desktop assistant that lets teams run SAP operations through secure chat, accelerating day-to-day work without changing core systems.</p>
						<p class="abitai-operator__description">Supports most SAP modules and workflows (Finance, Procurement, Inventory, Sales, HR, Reporting, etc.). Anything a user can do in the SAP UI, AbitAI can do via chat (permissions-based).</p>
						<div class="abitai-operator__ctas">
							<a class="ast-button abitai-operator__cta-primary" href="<?php echo $demo_link; ?>">Apply for Demo</a>
							<a class="ast-button abitai-operator__cta-secondary" href="<?php echo $sales_link; ?>">Contact Sales</a>
						</div>
						<div class="abitai-operator__panel">
							<h3>Examples include</h3>
							<ul class="abitai-operator__bullets">
								<li>Sales &amp; Customer Ops - sales orders, quotations, pricing updates</li>
								<li>Procurement &amp; Vendors - purchase orders, requests, vendor updates</li>
								<li>Inventory &amp; Logistics - transfers, GRN/GRPO, stock adjustments</li>
								<li>Finance &amp; Accounting - invoices, postings, reconciliations</li>
								<li>HR &amp; Admin - employee records, approvals, onboarding tasks</li>
								<li>Reporting &amp; Analytics - operational reports, exports, quick data lookups</li>
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

				<div class="abitai-operator__video-modal" aria-hidden="true">
					<div class="abitai-operator__video-backdrop" data-video-close></div>
					<div class="abitai-operator__video-dialog" role="dialog" aria-modal="true" aria-label="AbitAI Operator demo video">
						<button class="abitai-operator__video-close" type="button" data-video-close aria-label="Close video">×</button>
						<div class="abitai-operator__video-frame">
							<iframe title="AbitAI Operator Demo" allow="autoplay; encrypted-media" allowfullscreen></iframe>
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

if ( ! function_exists( 'abitai_operator_get_seo_subjects' ) ) {
	function abitai_operator_get_seo_subjects() {
		return array(
			'SAP automation',
			'SAP AI assistant',
			'SAP sales order automation',
			'SAP procurement automation',
			'SAP inventory operations',
			'SAP finance workflow automation',
			'enterprise workflow automation',
			'chat-based SAP operations',
		);
	}
}

if ( ! function_exists( 'abitai_operator_document_title_parts' ) ) {
	function abitai_operator_document_title_parts( $title_parts ) {
		if ( ! abitai_operator_is_front_request() ) {
			return $title_parts;
		}

		$title_parts['title'] = 'AbitAI Operator for SAP Automation';

		if ( ! isset( $title_parts['tagline'] ) || '' === trim( (string) $title_parts['tagline'] ) ) {
			$title_parts['tagline'] = 'Sales, Procurement, Inventory & Finance Workflows';
		}

		return $title_parts;
	}
	add_filter( 'document_title_parts', 'abitai_operator_document_title_parts', 20 );
}

if ( ! function_exists( 'abitai_operator_output_seo_meta' ) ) {
	function abitai_operator_output_seo_meta() {
		if ( ! abitai_operator_is_front_request() ) {
			return;
		}

		$subjects     = abitai_operator_get_seo_subjects();
		$subject_list = implode( ', ', $subjects );
		$page_url     = home_url( '/' );
		$site_name    = get_bloginfo( 'name' );
		$title        = 'AbitAI Operator for SAP Automation';
		$description  = 'AbitAI Operator helps teams run SAP tasks through secure chat across sales, procurement, inventory, finance, HR, and reporting workflows.';

		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'SoftwareApplication',
			'name'             => 'AbitAI Operator',
			'applicationCategory' => 'BusinessApplication',
			'operatingSystem'  => 'Windows, macOS',
			'url'              => esc_url_raw( $page_url . '#abitai-operator' ),
			'description'      => $description,
			'featureList'      => $subjects,
			'keywords'         => $subject_list,
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => $site_name,
			),
		);

		echo "\n";
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta name="keywords" content="' . esc_attr( $subject_list ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $page_url ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $page_url ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
	}
	add_action( 'wp_head', 'abitai_operator_output_seo_meta', 4 );
}

if ( ! function_exists( 'abitai_operator_enqueue_styles' ) ) {
	function abitai_operator_enqueue_styles() {
		wp_enqueue_style(
			'abitai-frontend',
			ASTRA_THEME_URI . 'assets/css/abitai-frontend.css',
			array( 'astra-theme-css' ),
			filemtime( ASTRA_THEME_DIR . 'assets/css/abitai-frontend.css' )
		);

		wp_enqueue_script(
			'abitai-auth',
			ASTRA_THEME_URI . 'assets/js/abitai-auth.js',
			array(),
			filemtime( ASTRA_THEME_DIR . 'assets/js/abitai-auth.js' ),
			true
		);
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

if ( ! function_exists( 'abitai_operator_video_modal_script' ) ) {
	function abitai_operator_video_modal_script() {
		?>
		<script>
			(function () {
				var modal = document.querySelector('.abitai-operator__video-modal');
				var mediaWrap = document.querySelector('.abitai-operator__video');
				var mockup = document.querySelector('.abitai-operator__mockup--primary');
				var mockupImg = mockup ? mockup.querySelector('img') : null;
				var resizeTimer;
				function syncVideoHeight() {
					if (!mediaWrap || !mockup) {
						return;
					}
					var height = mockup.offsetHeight;
					if (height) {
						mediaWrap.style.setProperty('--abitai-video-height', height + 'px');
					}
				}
				if (mockupImg && !mockupImg.complete) {
					mockupImg.addEventListener('load', syncVideoHeight);
				}
				if (window.ResizeObserver && mockup) {
					new ResizeObserver(syncVideoHeight).observe(mockup);
				}
				window.addEventListener('load', syncVideoHeight);
				window.addEventListener('resize', function () {
					clearTimeout(resizeTimer);
					resizeTimer = setTimeout(syncVideoHeight, 120);
				});
				syncVideoHeight();
				if (!modal) {
					return;
				}
				var iframe = modal.querySelector('iframe');
				var triggers = document.querySelectorAll('.abitai-operator__video-trigger');
				function openModal(videoId) {
					if (!iframe) {
						return;
					}
					var src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
					iframe.setAttribute('src', src);
					modal.classList.add('is-open');
					modal.setAttribute('aria-hidden', 'false');
					document.body.style.overflow = 'hidden';
				}
				function closeModal() {
					modal.classList.remove('is-open');
					modal.setAttribute('aria-hidden', 'true');
					if (iframe) {
						iframe.setAttribute('src', '');
					}
					document.body.style.overflow = '';
				}
				triggers.forEach(function (trigger) {
					trigger.addEventListener('click', function () {
						var videoId = trigger.getAttribute('data-video-id');
						if (videoId) {
							openModal(videoId);
						}
					});
				});
				modal.addEventListener('click', function (event) {
					if (event.target && event.target.hasAttribute('data-video-close')) {
						closeModal();
					}
				});
				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape' && modal.classList.contains('is-open')) {
						closeModal();
					}
				});
			})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'abitai_operator_video_modal_script', 105 );
}

if ( ! function_exists( 'abitai_operator_image_attachment_script' ) ) {
	function abitai_operator_image_attachment_script() {
		?>
		<script>
			(function () {
				var operatorSection = document.querySelector('.abitai-operator-section');
				if (!operatorSection) {
					return;
				}

				var composer = operatorSection.querySelector('.abitai-operator-app__composer');
				var attachButton = operatorSection.querySelector('[data-operator-image-trigger]');
				var attachInput = operatorSection.querySelector('[data-operator-image-input]');
				var placeholder = operatorSection.querySelector('.abitai-operator-app__placeholder');

				if (!composer || !attachButton || !attachInput || !placeholder) {
					return;
				}

				var defaultPlaceholder = (placeholder.textContent || '').trim();

				attachButton.addEventListener('click', function () {
					attachInput.click();
				});

				attachInput.addEventListener('change', function () {
					var file = attachInput.files && attachInput.files[0] ? attachInput.files[0] : null;
					if (!file) {
						placeholder.textContent = defaultPlaceholder;
						return;
					}

					if ('image/' !== file.type.substring(0, 6)) {
						attachInput.value = '';
						placeholder.textContent = 'Attach an image file (PNG, JPG, or WEBP)';
						return;
					}

					placeholder.textContent = 'Attached: ' + file.name;
				});
			})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'abitai_operator_image_attachment_script', 110 );
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

/**
 * Force ABiT footer copyright year to the current year (fixes old hardcoded year).
 */
if ( ! function_exists( 'abitai_override_footer_copyright_editor' ) ) {
	function abitai_override_footer_copyright_editor( $value, $option, $default ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		// Only touch our custom ABiT footer line.
		if ( false === stripos( $value, 'Powered by ABiT Consulting' ) ) {
			return $value;
		}

		$current_year = gmdate( 'Y' );

		// Replace the old hardcoded year (e.g., 2022) with the current year (e.g., 2026).
		$value = preg_replace( '/\\b2022\\b/', $current_year, $value, 1 );

		return $value;
	}
	add_filter( 'astra_get_option_footer-copyright-editor', 'abitai_override_footer_copyright_editor', 10, 3 );
}

/**
 * Elementor footer templates store the copyright line in DB content; patch it at render-time.
 */
if ( ! function_exists( 'abitai_elementor_update_footer_copyright_year' ) ) {
	function abitai_elementor_update_footer_copyright_year( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		if ( false === stripos( $content, 'Powered by ABiT Consulting (Pvt) Ltd.' ) ) {
			return $content;
		}

		if ( false === stripos( $content, 'Copyright' ) ) {
			return $content;
		}

		$current_year = gmdate( 'Y' );
		$updated      = preg_replace_callback(
			'/(Copyright\\s*[^0-9]*)(\\d{4})(\\s*ABiT\\s*\\|\\s*Powered by ABiT Consulting \\(Pvt\\) Ltd\\.)/i',
			static function ( $matches ) use ( $current_year ) {
				return $matches[1] . $current_year . $matches[3];
			},
			$content,
			1
		);

		// Fallback: replace the first 4-digit year that appears after the word "Copyright".
		if ( is_string( $updated ) && $updated !== $content ) {
			$content = $updated;
		} else {
			$content = preg_replace_callback(
				'/(Copyright\\s*[^0-9]*)(\\d{4})/i',
				static function ( $matches ) use ( $current_year ) {
					return $matches[1] . $current_year;
				},
				$content,
				1
			);
		}

		return $content;
	}
	add_filter( 'elementor/frontend/the_content', 'abitai_elementor_update_footer_copyright_year', 50 );
}

/**
 * Build social sign-up URLs with a filter for plugin compatibility.
 *
 * @return array<string,string>
 */
if ( ! function_exists( 'abitai_get_social_signup_urls' ) ) {
	function abitai_get_social_signup_urls() {
		$login_url = wp_login_url();

		$urls = array(
			'google'   => add_query_arg( 'loginSocial', 'google', $login_url ),
			'facebook' => add_query_arg( 'loginSocial', 'facebook', $login_url ),
		);

		/**
		 * Filters social sign-up URLs used on the ABiT sign-up template.
		 *
		 * @param array<string,string> $urls Social provider URLs.
		 */
		return apply_filters( 'abitai_social_signup_urls', $urls );
	}
}

/**
 * Handle custom front-end sign-up form submission.
 */
if ( ! function_exists( 'abitai_get_company_size_options' ) ) {
	function abitai_get_company_size_options() {
		return array(
			'1_10'     => __( '1-10 employees', 'astra' ),
			'11_50'    => __( '11-50 employees', 'astra' ),
			'51_200'   => __( '51-200 employees', 'astra' ),
			'201_500'  => __( '201-500 employees', 'astra' ),
			'501_plus' => __( '501+ employees', 'astra' ),
		);
	}
}

if ( ! function_exists( 'abitai_get_industry_options' ) ) {
	function abitai_get_industry_options() {
		return array(
			'professional_services'     => __( 'Professional services', 'astra' ),
			'trading_distribution'      => __( 'Trading and distribution', 'astra' ),
			'manufacturing'             => __( 'Manufacturing', 'astra' ),
			'retail_ecommerce'          => __( 'Retail or ecommerce', 'astra' ),
			'construction_real_estate'  => __( 'Construction or real estate', 'astra' ),
			'healthcare'                => __( 'Healthcare', 'astra' ),
			'education'                 => __( 'Education', 'astra' ),
			'nonprofit'                 => __( 'Nonprofit', 'astra' ),
			'technology'                => __( 'Technology', 'astra' ),
			'other'                     => __( 'Other', 'astra' ),
		);
	}
}

if ( ! function_exists( 'abitai_get_country_region_options' ) ) {
	function abitai_get_country_region_options() {
		return array(
			'AE' => __( 'United Arab Emirates', 'astra' ),
			'SA' => __( 'Saudi Arabia', 'astra' ),
			'QA' => __( 'Qatar', 'astra' ),
			'BH' => __( 'Bahrain', 'astra' ),
			'KW' => __( 'Kuwait', 'astra' ),
			'OM' => __( 'Oman', 'astra' ),
			'IN' => __( 'India', 'astra' ),
			'PK' => __( 'Pakistan', 'astra' ),
			'GB' => __( 'United Kingdom', 'astra' ),
			'US' => __( 'United States', 'astra' ),
			'CA' => __( 'Canada', 'astra' ),
			'AU' => __( 'Australia', 'astra' ),
			'DE' => __( 'Germany', 'astra' ),
			'FR' => __( 'France', 'astra' ),
			'NL' => __( 'Netherlands', 'astra' ),
			'SG' => __( 'Singapore', 'astra' ),
			'MY' => __( 'Malaysia', 'astra' ),
			'ZA' => __( 'South Africa', 'astra' ),
			'NG' => __( 'Nigeria', 'astra' ),
			'KE' => __( 'Kenya', 'astra' ),
			'EG' => __( 'Egypt', 'astra' ),
			'JO' => __( 'Jordan', 'astra' ),
			'LB' => __( 'Lebanon', 'astra' ),
			'TR' => __( 'Turkey', 'astra' ),
			'OTHER_REGION' => __( 'Other country or region', 'astra' ),
		);
	}
}

if ( ! function_exists( 'abitai_get_erp_module_interest_options' ) ) {
	function abitai_get_erp_module_interest_options() {
		return array(
			'finance'       => __( 'Finance', 'astra' ),
			'hr'            => __( 'HR', 'astra' ),
			'inventory'     => __( 'Inventory', 'astra' ),
			'sales'         => __( 'Sales', 'astra' ),
			'purchasing'    => __( 'Purchasing', 'astra' ),
			'crm'           => __( 'CRM', 'astra' ),
			'manufacturing' => __( 'Manufacturing', 'astra' ),
			'projects'      => __( 'Projects', 'astra' ),
			'reporting'     => __( 'Reporting', 'astra' ),
		);
	}
}

if ( ! function_exists( 'abitai_get_erp_module_interest_label' ) ) {
	/**
	 * Return a customer-facing label for stored ERP module interest keys.
	 *
	 * @param string $module Module key.
	 * @return string
	 */
	function abitai_get_erp_module_interest_label( $module ) {
		$labels = array_merge(
			abitai_get_erp_module_interest_options(),
			array(
				'accounting'           => __( 'Finance', 'astra' ),
				'buying'               => __( 'Purchasing', 'astra' ),
				'stock'                => __( 'Inventory', 'astra' ),
				'hr_payroll'           => __( 'HR and payroll', 'astra' ),
				'support_helpdesk'     => __( 'Support', 'astra' ),
				'website_portal'       => __( 'Customer portal', 'astra' ),
				'reports_analytics'    => __( 'Reporting', 'astra' ),
				'integrations'         => __( 'Integrations', 'astra' ),
				'full_erp_evaluation'  => __( 'Full ERP evaluation', 'astra' ),
				'not_sure'             => __( 'Not sure yet', 'astra' ),
			)
		);

		$module = sanitize_key( (string) $module );

		return isset( $labels[ $module ] ) ? $labels[ $module ] : ucwords( str_replace( '_', ' ', $module ) );
	}
}

if ( ! function_exists( 'abitai_text_has_unsafe_content' ) ) {
	function abitai_text_has_unsafe_content( $value ) {
		return preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F<>]/', $value ) || preg_match( '/https?:\/\/|www\./i', $value );
	}
}

if ( ! function_exists( 'abitai_handle_user_signup' ) ) {
	function abitai_handle_user_signup() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$redirect_url         = isset( $_POST['abitai_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['abitai_redirect'] ) ) : home_url( '/' );
		$success_redirect_url = isset( $_POST['abitai_success_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['abitai_success_redirect'] ) ) : $redirect_url;

		if ( ! isset( $_POST['abitai_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_signup_nonce'] ) ), 'abitai_user_signup' ) ) {
			wp_safe_redirect( add_query_arg( 'signup_error', 'invalid_nonce', $redirect_url ) );
			exit;
		}

		$full_name        = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$legacy_username  = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$full_name        = '' !== $full_name ? $full_name : $legacy_username;
		$email            = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password         = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';
		$has_consent      = isset( $_POST['terms_privacy_acceptance'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['terms_privacy_acceptance'] ) );
		$requires_company = isset( $_POST['abitai_signup_flow_version'] ) && 'company_step' === sanitize_key( wp_unslash( $_POST['abitai_signup_flow_version'] ) );
		$company_name     = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
		$industry         = isset( $_POST['industry'] ) ? sanitize_key( wp_unslash( $_POST['industry'] ) ) : '';
		$company_size     = isset( $_POST['company_size'] ) ? sanitize_key( wp_unslash( $_POST['company_size'] ) ) : '';
		$business_description = isset( $_POST['business_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['business_description'] ) ) : '';
		$job_title        = isset( $_POST['job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['job_title'] ) ) : '';
		$country_region   = isset( $_POST['country_region'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country_region'] ) ) ) : '';
		$phone            = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$erp_module_interest = array();

		if ( isset( $_POST['erp_module_interest'] ) && is_array( $_POST['erp_module_interest'] ) ) {
			$erp_module_interest = array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $_POST['erp_module_interest'] ) ) ) );
		}

		$error_args = array_filter(
			array(
				'full_name'            => $full_name,
				'email'                => $email,
				'company_name'         => $company_name,
				'industry'             => $industry,
				'company_size'         => $company_size,
				'business_description' => $business_description,
				'job_title'            => $job_title,
				'country_region'       => $country_region,
				'phone'                => $phone,
				'erp_module_interest'  => $erp_module_interest,
			)
		);

		if ( '' === $full_name || '' === $email || '' === $password || '' === $confirm_password ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'missing_fields' ) ), $redirect_url ) );
			exit;
		}

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'invalid_email' ) ), $redirect_url ) );
			exit;
		}

		if ( strlen( $password ) < 12 || strlen( $password ) > 128 || in_array( strtolower( $password ), array( 'password', 'password123', 'password123!', '123456789012', 'qwerty123456', 'admin1234567', 'welcome12345' ), true ) ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'weak_password' ) ), $redirect_url ) );
			exit;
		}

		if ( $password !== $confirm_password ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'password_mismatch' ) ), $redirect_url ) );
			exit;
		}

		if ( ! $has_consent ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'missing_consent' ) ), $redirect_url ) );
			exit;
		}

		if ( $requires_company ) {
			$valid_company_sizes = array_keys( abitai_get_company_size_options() );
			$valid_industries    = array_keys( abitai_get_industry_options() );
			$valid_countries     = array_keys( abitai_get_country_region_options() );
			$valid_modules       = array_keys( abitai_get_erp_module_interest_options() );

			if ( strlen( $company_name ) < 2 || strlen( $company_name ) > 160 || abitai_text_has_unsafe_content( $company_name ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'invalid_company' ) ), $redirect_url ) );
				exit;
			}

			if ( ! in_array( $industry, $valid_industries, true ) || ! in_array( $company_size, $valid_company_sizes, true ) || ! in_array( $country_region, $valid_countries, true ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'invalid_select' ) ), $redirect_url ) );
				exit;
			}

			if ( strlen( $job_title ) < 2 || strlen( $job_title ) > 120 || abitai_text_has_unsafe_content( $job_title ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'invalid_job_title' ) ), $redirect_url ) );
				exit;
			}

			if ( strlen( $business_description ) < 20 || strlen( $business_description ) > 1000 || abitai_text_has_unsafe_content( $business_description ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'invalid_business_description' ) ), $redirect_url ) );
				exit;
			}

			if ( '' !== $phone && ( strlen( $phone ) > 40 || ! preg_match( '/^[0-9+().\-\s]+$/', $phone ) ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'missing_fields' ) ), $redirect_url ) );
				exit;
			}

			if ( empty( $erp_module_interest ) || array_diff( $erp_module_interest, $valid_modules ) ) {
				wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'missing_module_interest' ) ), $redirect_url ) );
				exit;
			}
		}

		if ( email_exists( $email ) ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'user_exists' ) ), $redirect_url ) );
			exit;
		}

		$username      = sanitize_user( current( explode( '@', $email ) ), true );
		$username      = '' !== $username ? $username : 'abitai_user';
		$base_username = $username;
		$suffix        = 1;

		while ( username_exists( $username ) ) {
			$username = $base_username . $suffix;
			$suffix++;
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			wp_safe_redirect( add_query_arg( array_merge( $error_args, array( 'signup_error' => 'create_failed' ) ), $redirect_url ) );
			exit;
		}

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $full_name,
				'first_name'   => $full_name,
			)
		);
		update_user_meta( $user_id, 'abitai_terms_privacy_accepted_at', current_time( 'mysql', true ) );
		update_user_meta( $user_id, 'abitai_terms_privacy_acceptance_source', 'signup_account_step' );
		update_user_meta( $user_id, 'abitai_access_request_status', 'pending_email_verification' );

		if ( $requires_company ) {
			update_user_meta( $user_id, 'abitai_company_name', $company_name );
			update_user_meta( $user_id, 'abitai_industry', $industry );
			update_user_meta( $user_id, 'abitai_company_size', $company_size );
			update_user_meta( $user_id, 'abitai_business_description', $business_description );
			update_user_meta( $user_id, 'abitai_job_title', $job_title );
			update_user_meta( $user_id, 'abitai_role', $job_title );
			update_user_meta( $user_id, 'abitai_country_region', $country_region );
			update_user_meta( $user_id, 'abitai_phone', $phone );
			update_user_meta( $user_id, 'abitai_erp_module_interest', $erp_module_interest );
		}

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			$company_id = absint( get_user_meta( $user_id, 'abitai_company_id', true ) );
			abitai_auth_write_audit_log(
				'auth_signup_created',
				array(
					'actor_user_id' => $user_id,
					'actor_type'    => 'user',
					'entity_type'   => 'user',
					'entity_id'     => $user_id,
					'company_id'    => $company_id,
					'event_data'    => array(
						'email'                       => $email,
						'review_status'               => 'pending_email_verification',
						'signup_flow_version'         => $requires_company ? 'company_step' : 'account_step',
						'terms_privacy_acceptance'    => true,
						'company_profile_included'    => $requires_company,
						'erp_module_interest_count'   => count( $erp_module_interest ),
					),
				)
			);
			abitai_auth_write_audit_log(
				'auth_consent_accepted',
				array(
					'actor_user_id' => $user_id,
					'actor_type'    => 'user',
					'entity_type'   => 'user',
					'entity_id'     => $user_id,
					'company_id'    => $company_id,
					'event_data'    => array(
						'email'          => $email,
						'capture_source' => 'signup_account_step',
						'accepted_at'    => current_time( 'mysql', true ),
					),
				)
			);
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		wp_safe_redirect( add_query_arg( array( 'signup_success' => '1', 'email' => $email ), $success_redirect_url ) );
		exit;
	}
	add_action( 'admin_post_nopriv_abitai_user_signup', 'abitai_handle_user_signup' );
	add_action( 'admin_post_abitai_user_signup', 'abitai_handle_user_signup' );
}

/**
 * Return deterministic mock users for the front-end auth MVP.
 *
 * @return array<string,array<string,string>>
 */
if ( ! function_exists( 'abitai_get_mock_auth_users' ) ) {
	function abitai_get_mock_auth_users() {
		return apply_filters(
			'abitai_mock_auth_users',
			array(
				'valid@abit.ai'      => array(
					'password' => 'Password123!',
					'status'   => 'approved_for_mvp_access',
				),
				'approved@abit.ai'   => array(
					'password' => 'Password123!',
					'status'   => 'approved_for_mvp_access',
				),
				'access-approved@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'approved',
				),
				'unverified@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'pending_email_verification',
				),
				'onboarding@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'onboarding_required',
				),
				'incomplete@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'onboarding_required',
				),
				'review@abit.ai'     => array(
					'password' => 'Password123!',
					'status'   => 'pending_admin_review',
				),
				'info@abit.ai'       => array(
					'password' => 'Password123!',
					'status'   => 'more_information_requested',
				),
				'rejected@abit.ai'   => array(
					'password' => 'Password123!',
					'status'   => 'rejected',
				),
				'profile-incomplete@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'profile_incomplete',
				),
				'ready-for-review@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'ready_for_review',
				),
				'provisioning@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'provisioning',
				),
				'provisioned@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'provisioned',
				),
				'blocked@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'blocked',
				),
				'live@abit.ai' => array(
					'password' => 'Password123!',
					'status'   => 'live',
				),
			)
		);
	}
}

/**
 * Map an access request status to the mocked next front-end route.
 *
 * @param string $status Access request status.
 * @param string $email User email address.
 * @return string
 */
if ( ! function_exists( 'abitai_get_mock_auth_redirect_url' ) ) {
	function abitai_get_mock_auth_redirect_url( $status, $email ) {
		$args = array(
			'status' => $status,
			'email'  => $email,
		);

		switch ( $status ) {
			case 'pending_email_verification':
				return add_query_arg( array( 'state' => 'required', 'email' => $email ), home_url( '/auth/verify' ) );
			case 'onboarding_required':
			case 'pending_admin_review':
			case 'more_information_requested':
			case 'rejected':
			case 'approved_for_mvp_access':
			case 'profile_incomplete':
			case 'ready_for_review':
			case 'approved':
			case 'provisioning':
			case 'provisioned':
			case 'blocked':
			case 'live':
			default:
				return add_query_arg( $args, home_url( '/dashboard' ) );
		}
	}
}

if ( ! function_exists( 'abitai_get_access_request_statuses' ) ) {
	/**
	 * Return status labels used by the front-end dashboard gate.
	 *
	 * @return array<string,string>
	 */
	function abitai_get_access_request_statuses() {
		return array(
			'pending_email_verification' => __( 'Email verification required', 'astra' ),
			'onboarding_required'        => __( 'Company profile incomplete', 'astra' ),
			'pending_admin_review'       => __( 'Pending admin review', 'astra' ),
			'more_information_requested' => __( 'More information requested', 'astra' ),
			'on_hold'                    => __( 'On hold', 'astra' ),
			'approved_for_mvp_access'    => __( 'Approved for MVP access', 'astra' ),
			'rejected'                   => __( 'Request rejected', 'astra' ),
			'profile_incomplete'         => __( 'Profile incomplete', 'astra' ),
			'ready_for_review'           => __( 'Ready for review', 'astra' ),
			'approved'                   => __( 'Approved', 'astra' ),
			'provisioning'               => __( 'Provisioning', 'astra' ),
			'provisioned'                => __( 'Provisioned', 'astra' ),
			'blocked'                    => __( 'Blocked', 'astra' ),
			'live'                       => __( 'Live', 'astra' ),
		);
	}
}

if ( ! function_exists( 'abitai_onboarding_state_aliases' ) ) {
	/**
	 * Map legacy review statuses and fixture aliases to canonical onboarding states.
	 *
	 * @return array<string,string>
	 */
	function abitai_onboarding_state_aliases() {
		return array(
			'onboarding_required'        => 'profile_incomplete',
			'pending_admin_review'       => 'ready_for_review',
			'more_information_requested' => 'profile_incomplete',
			'on_hold'                    => 'on_hold',
			'approved_for_mvp_access'    => 'live',
			'rejected'                   => 'blocked',
			'profile-incomplete'         => 'profile_incomplete',
			'ready-for-review'           => 'ready_for_review',
		);
	}
}

if ( ! function_exists( 'abitai_normalize_onboarding_state' ) ) {
	/**
	 * Resolve a stored status to the canonical verified-user onboarding state.
	 *
	 * @param string $status Stored status or fixture key.
	 * @return string
	 */
	function abitai_normalize_onboarding_state( $status ) {
		$status = sanitize_key( (string) $status );
		$states = array(
			'profile_incomplete',
			'ready_for_review',
			'approved',
			'provisioning',
			'provisioned',
			'on_hold',
			'blocked',
			'live',
		);

		if ( in_array( $status, $states, true ) ) {
			return $status;
		}

		$aliases = abitai_onboarding_state_aliases();

		return isset( $aliases[ $status ] ) ? $aliases[ $status ] : 'profile_incomplete';
	}
}

if ( ! function_exists( 'abitai_auth_handoff_label' ) ) {
	/**
	 * Display label for handoff metadata stored on the current access request.
	 *
	 * @param string $value Handoff key.
	 * @return string
	 */
	function abitai_auth_handoff_label( $value ) {
		$value  = sanitize_key( (string) $value );
		$labels = array(
			'sales'         => __( 'Sales', 'astra' ),
			'support'       => __( 'Support', 'astra' ),
			'low'           => __( 'Low', 'astra' ),
			'normal'        => __( 'Normal', 'astra' ),
			'high'          => __( 'High', 'astra' ),
			'urgent'        => __( 'Urgent', 'astra' ),
			'unassigned'    => __( 'Unassigned', 'astra' ),
			'assigned'      => __( 'Assigned', 'astra' ),
			'held'          => __( 'Held', 'astra' ),
			'completed'     => __( 'Completed', 'astra' ),
			'follow_up_due' => __( 'Follow-up due', 'astra' ),
		);

		return isset( $labels[ $value ] ) ? $labels[ $value ] : '';
	}
}

if ( ! function_exists( 'abitai_auth_get_dashboard_request' ) ) {
	/**
	 * Resolve the current dashboard gate request from query args or the signed-in user.
	 *
	 * @return array<string,mixed>
	 */
	function abitai_auth_get_dashboard_request() {
		$statuses = abitai_get_access_request_statuses();
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$email    = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';

		if ( '' === $status && isset( $_GET['onboarding_state'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['onboarding_state'] ) );
		}

		if ( '' === $status && isset( $_GET['state'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['state'] ) );
		}

		if ( ! isset( $statuses[ $status ] ) ) {
			$aliases = function_exists( 'abitai_onboarding_state_aliases' ) ? abitai_onboarding_state_aliases() : array();
			$status  = isset( $aliases[ $status ], $statuses[ $aliases[ $status ] ] ) ? $aliases[ $status ] : '';
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user    = get_userdata( $user_id );

			if ( '' === $email && $user ) {
				$email = $user->user_email;
			}

			if ( '' === $status ) {
				$stored_status = sanitize_key( (string) get_user_meta( $user_id, 'abitai_access_request_status', true ) );
				$status        = isset( $statuses[ $stored_status ] ) ? $stored_status : '';
			}

			if ( '' === $status ) {
				$status = get_user_meta( $user_id, 'abitai_email_verified_at', true ) ? 'pending_admin_review' : 'pending_email_verification';
			}
		}

		if ( '' === $status ) {
			$status = 'pending_email_verification';
		}

		$company_name          = isset( $_GET['company_name'] ) ? sanitize_text_field( wp_unslash( $_GET['company_name'] ) ) : '';
		$role                  = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';
		$industry              = isset( $_GET['industry'] ) ? sanitize_key( wp_unslash( $_GET['industry'] ) ) : '';
		$company_size          = isset( $_GET['company_size'] ) ? sanitize_key( wp_unslash( $_GET['company_size'] ) ) : '';
		$primary_workflow      = isset( $_GET['primary_workflow'] ) ? sanitize_text_field( wp_unslash( $_GET['primary_workflow'] ) ) : '';
		$selected_modules      = array();
		$handoff_team          = '';
		$handoff_priority      = '';
		$handoff_next_action   = '';
		$handoff_follow_up     = '';
		$handoff_queue_status  = '';
		$product_access       = ( 'live' === $status && ! is_user_logged_in() );
		$missing_requirements = array();
		$gate_checks          = array();

		if ( isset( $_GET['erp_module_interest'] ) ) {
			$raw_modules = wp_unslash( $_GET['erp_module_interest'] );
			$raw_modules = is_array( $raw_modules ) ? $raw_modules : explode( ',', (string) $raw_modules );
			$selected_modules = array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw_modules ) ) ) );
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			$company_name = '' !== $company_name ? $company_name : sanitize_text_field( (string) get_user_meta( $user_id, 'abitai_company_name', true ) );
			$role         = '' !== $role ? $role : sanitize_text_field( (string) get_user_meta( $user_id, 'abitai_role', true ) );
			$industry     = '' !== $industry ? $industry : sanitize_key( (string) get_user_meta( $user_id, 'abitai_industry', true ) );
			$company_size = '' !== $company_size ? $company_size : sanitize_key( (string) get_user_meta( $user_id, 'abitai_company_size', true ) );
			$primary_workflow = '' !== $primary_workflow ? $primary_workflow : sanitize_text_field( (string) get_user_meta( $user_id, 'abitai_business_description', true ) );
			$handoff_team = sanitize_key( (string) get_user_meta( $user_id, 'abitai_handoff_team', true ) );
			$handoff_priority = sanitize_key( (string) get_user_meta( $user_id, 'abitai_handoff_priority', true ) );
			$handoff_next_action = sanitize_text_field( (string) get_user_meta( $user_id, 'abitai_handoff_next_action', true ) );
			$handoff_follow_up = sanitize_text_field( (string) get_user_meta( $user_id, 'abitai_handoff_follow_up_date', true ) );
			$handoff_queue_status = sanitize_key( (string) get_user_meta( $user_id, 'abitai_handoff_queue_status', true ) );

			if ( empty( $selected_modules ) ) {
				$stored_modules = get_user_meta( $user_id, 'abitai_erp_module_interest', true );
				$stored_modules = is_array( $stored_modules ) ? $stored_modules : explode( ',', (string) $stored_modules );
				$selected_modules = array_values( array_unique( array_filter( array_map( 'sanitize_key', $stored_modules ) ) ) );
			}
		}

		if ( is_user_logged_in() && function_exists( 'abitai_erp_access_gate_get_user_context' ) && function_exists( 'abitai_erp_access_gate_evaluate' ) ) {
			$gate_context = abitai_erp_access_gate_get_user_context( get_current_user_id() );
			$gate_result  = abitai_erp_access_gate_evaluate( $gate_context );

			$product_access       = ! empty( $gate_result['product_access'] );
			$missing_requirements = isset( $gate_result['missing_requirements'] ) ? (array) $gate_result['missing_requirements'] : array();
			$gate_checks          = isset( $gate_result['checks'] ) ? (array) $gate_result['checks'] : array();

			if ( isset( $gate_context['status'], $statuses[ $gate_context['status'] ] ) ) {
				$status = $gate_context['status'];
			}

			if ( '' === $company_name && ! empty( $gate_context['company_name'] ) ) {
				$company_name = $gate_context['company_name'];
			}
		}

		$industry_options     = function_exists( 'abitai_get_industry_options' ) ? abitai_get_industry_options() : array();
		$company_size_options = function_exists( 'abitai_get_company_size_options' ) ? abitai_get_company_size_options() : array();
		$module_labels        = array();

		foreach ( $selected_modules as $module ) {
			$module_labels[] = abitai_get_erp_module_interest_label( $module );
		}

		$onboarding_state = ( 'pending_email_verification' === $status ) ? 'blocked' : abitai_normalize_onboarding_state( $status );

		return array(
			'status'               => $status,
			'status_label'         => $statuses[ $status ],
			'onboarding_state'     => $onboarding_state,
			'state_label'          => $statuses[ $onboarding_state ],
			'email'                => $email,
			'company_name'         => $company_name,
			'role'                 => $role,
			'industry'             => isset( $industry_options[ $industry ] ) ? $industry_options[ $industry ] : '',
			'company_size'         => isset( $company_size_options[ $company_size ] ) ? $company_size_options[ $company_size ] : '',
			'primary_workflow'     => $primary_workflow,
			'selected_modules'     => $module_labels,
			'handoff_team'         => $handoff_team,
			'handoff_team_label'   => abitai_auth_handoff_label( $handoff_team ),
			'handoff_priority'     => $handoff_priority,
			'handoff_priority_label' => abitai_auth_handoff_label( $handoff_priority ),
			'handoff_next_action'  => $handoff_next_action,
			'handoff_follow_up_date' => $handoff_follow_up,
			'handoff_queue_status' => $handoff_queue_status,
			'handoff_queue_label'  => abitai_auth_handoff_label( $handoff_queue_status ),
			'product_access'       => $product_access,
			'missing_requirements' => $missing_requirements,
			'gate_checks'          => $gate_checks,
		);
	}
}

if ( ! function_exists( 'abitai_auth_get_dashboard_gate' ) ) {
	/**
	 * Build the dashboard gate display model.
	 *
	 * @param array<string,mixed> $request Request state.
	 * @return array<string,mixed>
	 */
	function abitai_auth_get_dashboard_gate( $request ) {
		$status = isset( $request['status'] ) ? (string) $request['status'] : 'pending_email_verification';
		$state  = isset( $request['onboarding_state'] ) ? (string) $request['onboarding_state'] : abitai_normalize_onboarding_state( $status );

		$gate = array(
			'onboarding_state'          => $state,
			'product_access'            => ! empty( $request['product_access'] ),
			'missing_requirements'      => isset( $request['missing_requirements'] ) ? (array) $request['missing_requirements'] : array(),
			'profile_state'             => __( 'Incomplete', 'astra' ),
			'profile_variant'           => 'pending',
			'profile_description'       => __( 'Complete the required company profile fields before admin review can begin.', 'astra' ),
			'provisioning_state'        => __( 'Blocked', 'astra' ),
			'provisioning_variant'      => 'rejected',
			'provisioning_description'  => __( 'Workspace access is blocked until profile completion, review, and approval are complete.', 'astra' ),
			'next_action'               => __( 'Complete company profile', 'astra' ),
			'next_href'                 => home_url( '/auth/onboarding' ),
			'next_variant'              => 'primary',
			'alert_variant'             => 'info',
			'alert_summary'             => __( 'Company profile completion required.', 'astra' ),
			'alert_body'                => __( 'Your email is verified. Complete your company profile so the review team can continue.', 'astra' ),
		);

		switch ( $state ) {
			case 'profile_incomplete':
				if ( 'more_information_requested' === $status ) {
					$gate['profile_state']       = __( 'Needs update', 'astra' );
					$gate['profile_variant']     = 'review';
					$gate['profile_description'] = __( 'The review team needs updated company context before proceeding.', 'astra' );
					$gate['provisioning_state']  = __( 'Paused', 'astra' );
					$gate['provisioning_variant']= 'review';
					$gate['provisioning_description'] = __( 'Workspace access remains paused until the requested details are updated.', 'astra' );
					$gate['next_action']         = __( 'Update company profile', 'astra' );
					$gate['next_href']           = home_url( '/auth/more-information' );
					$gate['alert_variant']       = 'warning';
					$gate['alert_summary']       = __( 'More information is needed.', 'astra' );
					$gate['alert_body']          = __( 'Update the requested details before the review can continue.', 'astra' );
				}
				break;
			case 'ready_for_review':
				$gate['profile_state']       = __( 'Complete', 'astra' );
				$gate['profile_variant']     = 'approved';
				$gate['profile_description'] = __( 'Required company profile details have been submitted for review.', 'astra' );
				$gate['provisioning_state']  = __( 'Waiting for approval', 'astra' );
				$gate['provisioning_variant']= 'review';
				$gate['provisioning_description'] = __( 'Workspace access remains blocked while the review team checks the request.', 'astra' );
				$gate['next_action']         = __( 'Review in progress', 'astra' );
				$gate['next_href']           = home_url( '/auth/review-pending' );
				$gate['next_variant']        = 'secondary';
				$gate['alert_variant']       = 'warning';
				$gate['alert_summary']       = __( 'Request submitted for review.', 'astra' );
				$gate['alert_body']          = __( 'Product access remains blocked until admin approval.', 'astra' );
				break;
			case 'on_hold':
				$gate['profile_state']       = __( 'Held', 'astra' );
				$gate['profile_variant']     = 'review';
				$gate['profile_description'] = __( 'The review team has paused this request for sales or support follow-up.', 'astra' );
				$gate['provisioning_state']  = __( 'Paused', 'astra' );
				$gate['provisioning_variant']= 'review';
				$gate['provisioning_description'] = __( 'Workspace access remains paused while the assigned team completes follow-up.', 'astra' );
				$gate['next_action']         = __( 'Follow-up in progress', 'astra' );
				$gate['next_href']           = home_url( '/dashboard' );
				$gate['next_variant']        = 'secondary';
				$gate['alert_variant']       = 'warning';
				$gate['alert_summary']       = __( 'Request is on hold.', 'astra' );
				$gate['alert_body']          = __( 'The assigned team is reviewing the next step for this request.', 'astra' );
				break;
			case 'approved':
				$gate['profile_state']       = __( 'Approved', 'astra' );
				$gate['profile_variant']     = 'approved';
				$gate['profile_description'] = __( 'The access request is approved and ready for workspace preparation.', 'astra' );
				$gate['provisioning_state']  = __( 'Ready to request', 'astra' );
				$gate['provisioning_variant']= 'review';
				$gate['provisioning_description'] = __( 'Request workspace setup when you are ready for the abit.ai team to prepare access.', 'astra' );
				$gate['next_action']         = __( 'Request provisioning', 'astra' );
				$gate['next_href']           = home_url( '/api/provisioning/request' );
				$gate['alert_variant']       = 'success';
				$gate['alert_summary']       = __( 'Access approved.', 'astra' );
				$gate['alert_body']          = __( 'Your request is approved. Workspace preparation can now be requested.', 'astra' );
				break;
			case 'provisioning':
				$gate['profile_state']       = __( 'Approved', 'astra' );
				$gate['profile_variant']     = 'approved';
				$gate['profile_description'] = __( 'The access request is approved and workspace setup is underway.', 'astra' );
				$gate['provisioning_state']  = __( 'Provisioning', 'astra' );
				$gate['provisioning_variant']= 'review';
				$gate['provisioning_description'] = __( 'The workspace is being prepared. No user action is needed right now.', 'astra' );
				$gate['next_action']         = __( 'Provisioning in progress', 'astra' );
				$gate['next_href']           = home_url( '/dashboard' );
				$gate['next_variant']        = 'secondary';
				$gate['alert_variant']       = 'warning';
				$gate['alert_summary']       = __( 'Workspace preparation is in progress.', 'astra' );
				$gate['alert_body']          = __( 'The workspace is being prepared. We will update this dashboard when access is ready.', 'astra' );
				break;
			case 'provisioned':
				$gate['profile_state']       = __( 'Approved', 'astra' );
				$gate['profile_variant']     = 'approved';
				$gate['profile_description'] = __( 'The access request is approved and the workspace has been prepared.', 'astra' );
				$gate['provisioning_state']  = __( 'Provisioned', 'astra' );
				$gate['provisioning_variant']= 'approved';
				$gate['provisioning_description'] = __( 'Workspace setup is complete and awaiting final activation.', 'astra' );
				$gate['next_action']         = __( 'View workspace status', 'astra' );
				$gate['next_href']           = home_url( '/dashboard' );
				$gate['next_variant']        = 'secondary';
				$gate['alert_variant']       = 'success';
				$gate['alert_summary']       = __( 'Workspace provisioned.', 'astra' );
				$gate['alert_body']          = __( 'Workspace setup is complete. Final activation is the remaining step before access opens.', 'astra' );
				break;
			case 'blocked':
				$gate['profile_state']       = __( 'Blocked', 'astra' );
				$gate['profile_variant']     = 'rejected';
				$gate['profile_description'] = __( 'This account cannot continue right now. Contact support for help.', 'astra' );
				$gate['provisioning_state']  = __( 'Unavailable', 'astra' );
				$gate['provisioning_variant']= 'rejected';
				$gate['provisioning_description'] = __( 'Workspace access is unavailable while the account is blocked.', 'astra' );
				$gate['next_action']         = __( 'Contact support', 'astra' );
				$gate['next_href']           = 'mailto:support@abit.ai';
				$gate['alert_variant']       = 'error';
				$gate['alert_summary']       = __( 'Access unavailable.', 'astra' );
				$gate['alert_body']          = __( 'This account cannot continue right now. Contact support for help.', 'astra' );
				break;
			case 'live':
				$gate['profile_state']       = __( 'Approved', 'astra' );
				$gate['profile_variant']     = 'approved';
				$gate['profile_description'] = __( 'The access request is approved and workspace access is active.', 'astra' );
				$gate['provisioning_state']  = __( 'Live', 'astra' );
				$gate['provisioning_variant']= 'approved';
				$gate['provisioning_description'] = __( 'Workspace access is active for this account.', 'astra' );
				$gate['next_action']         = __( 'Open workspace', 'astra' );
				$gate['next_href']           = home_url( '/' );
				$gate['alert_variant']       = 'success';
				$gate['alert_summary']       = __( 'Workspace access ready.', 'astra' );
				$gate['alert_body']          = __( 'Your workspace access is ready.', 'astra' );
				break;
		}

		if ( 'live' === $state && empty( $gate['product_access'] ) ) {
			$gate['profile_state']       = __( 'Approved', 'astra' );
			$gate['profile_variant']     = 'approved';
			$gate['profile_description'] = __( 'The access request is approved, but workspace access is still waiting on final readiness checks.', 'astra' );
			$gate['provisioning_state']  = __( 'Blocked', 'astra' );
			$gate['provisioning_variant'] = 'rejected';
			$gate['provisioning_description'] = __( 'Workspace access requires verified account details, complete onboarding, and final activation by the abit.ai team.', 'astra' );
			$gate['next_action']         = __( 'View access status', 'astra' );
			$gate['next_href']           = home_url( '/dashboard' );
			$gate['next_variant']        = 'secondary';
			$gate['alert_variant']       = 'warning';
			$gate['alert_summary']       = __( 'ERP access is not ready.', 'astra' );
			$gate['alert_body']          = __( 'One or more ERP access requirements are still incomplete.', 'astra' );
		}

		return $gate;
	}
}

if ( ! function_exists( 'abitai_auth_maybe_redirect_dashboard_gate' ) ) {
	/**
	 * Prevent unverified users from accessing the dashboard gate.
	 */
	function abitai_auth_maybe_redirect_dashboard_gate() {
		$request = abitai_auth_get_dashboard_request();

		if ( 'pending_email_verification' !== $request['status'] ) {
			return;
		}

		$args = array( 'state' => 'required' );

		if ( ! empty( $request['email'] ) ) {
			$args['email'] = $request['email'];
		}

		wp_safe_redirect( add_query_arg( $args, home_url( '/auth/verify' ) ) );
		exit;
	}
}

/**
 * Handle front-end mocked sign-in form submission.
 */
if ( ! function_exists( 'abitai_handle_mock_sign_in' ) ) {
	function abitai_handle_mock_sign_in() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/sign-in' ) );
			exit;
		}

		$redirect_url = home_url( '/auth/sign-in' );
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

		if ( ! isset( $_POST['abitai_signin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_signin_nonce'] ) ), 'abitai_mock_sign_in' ) ) {
			wp_safe_redirect( add_query_arg( 'signin_error', 'invalid', $redirect_url ) );
			exit;
		}

		$users = abitai_get_mock_auth_users();
		$key   = strtolower( $email );

		if ( '' === $email || '' === $password || ! isset( $users[ $key ] ) || ! hash_equals( $users[ $key ]['password'], $password ) ) {
			if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
				abitai_auth_write_audit_log(
					'auth_login_failed',
					array(
						'actor_type'  => 'anonymous',
						'entity_type' => 'auth',
						'event_data'  => array(
							'email'                   => $email,
							'auth_method'             => 'email_password',
							'login_attempt_result'    => 'failure',
							'failure_reason_category' => 'invalid_credentials',
							'source'                  => 'frontend_mock',
						),
					)
				);
			}
			wp_safe_redirect(
				add_query_arg(
					array(
						'signin_error' => 'invalid',
						'email'        => $email,
					),
					$redirect_url
				)
			);
			exit;
		}

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_login_succeeded',
				array(
					'actor_type'  => 'mock_user',
					'entity_type' => 'auth',
					'event_data'  => array(
						'email'                => $email,
						'auth_method'          => 'email_password',
						'login_attempt_result' => 'success',
						'review_status'        => $users[ $key ]['status'],
						'source'               => 'frontend_mock',
					),
				)
			);
		}

		wp_safe_redirect( abitai_get_mock_auth_redirect_url( $users[ $key ]['status'], $email ) );
		exit;
	}
	add_action( 'admin_post_nopriv_abitai_mock_sign_in', 'abitai_handle_mock_sign_in' );
	add_action( 'admin_post_abitai_mock_sign_in', 'abitai_handle_mock_sign_in' );
}

/**
 * Handle mocked verification resend responses for the front-end auth MVP.
 */
if ( ! function_exists( 'abitai_handle_mock_resend_verification' ) ) {
	function abitai_handle_mock_resend_verification() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/verify' ) );
			exit;
		}

		$email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$mock_response = isset( $_POST['mock_response'] ) ? sanitize_key( wp_unslash( $_POST['mock_response'] ) ) : '';

		if ( ! isset( $_POST['abitai_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_resend_nonce'] ) ), 'abitai_mock_resend_verification' ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'state' => 'failed',
						'email' => $email,
					),
					home_url( '/auth/verify' )
				)
			);
			exit;
		}

		if ( 'rate_limited' === $mock_response || 'cooldown' === $mock_response || false !== strpos( strtolower( $email ), 'cooldown' ) ) {
			if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
				abitai_auth_write_audit_log(
					'auth_verification_resend',
					array(
						'actor_type'  => is_user_logged_in() ? 'user' : 'anonymous',
						'entity_type' => 'email_verification',
						'event_data'  => array(
							'email'                    => $email,
							'verification_send_reason' => 'resend',
							'result'                   => 'rate_limited',
							'source'                   => 'frontend_mock',
						),
					)
				);
			}
			wp_safe_redirect(
				add_query_arg(
					array(
						'state' => 'cooldown',
						'email' => $email,
					),
					home_url( '/auth/verify' )
				)
			);
			exit;
		}

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_verification_resend',
				array(
					'actor_type'  => is_user_logged_in() ? 'user' : 'anonymous',
					'entity_type' => 'email_verification',
					'event_data'  => array(
						'email'                    => $email,
						'verification_send_reason' => 'resend',
						'result'                   => 'accepted',
						'source'                   => 'frontend_mock',
					),
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'state'  => 'sent',
					'email'  => $email,
					'resend' => 'accepted',
				),
				home_url( '/auth/verify' )
			)
		);
		exit;
	}
	add_action( 'admin_post_nopriv_abitai_mock_resend_verification', 'abitai_handle_mock_resend_verification' );
	add_action( 'admin_post_abitai_mock_resend_verification', 'abitai_handle_mock_resend_verification' );
}

/**
 * Handle mocked password reset request responses for the front-end auth MVP.
 */
if ( ! function_exists( 'abitai_handle_mock_password_reset_request' ) ) {
	function abitai_handle_mock_password_reset_request() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/reset' ) );
			exit;
		}

		$email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$mock_response = isset( $_POST['mock_response'] ) ? sanitize_key( wp_unslash( $_POST['mock_response'] ) ) : '';
		$redirect_url  = home_url( '/auth/reset' );

		if ( ! isset( $_POST['abitai_reset_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_reset_request_nonce'] ) ), 'abitai_mock_password_reset_request' ) ) {
			wp_safe_redirect( add_query_arg( 'state', 'invalid', $redirect_url ) );
			exit;
		}

		if ( 'rate_limited' === $mock_response || 'cooldown' === $mock_response || false !== strpos( strtolower( $email ), 'cooldown' ) ) {
			if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
				abitai_auth_write_audit_log(
					'auth_password_reset_requested',
					array(
						'actor_type'  => 'anonymous',
						'entity_type' => 'password_reset',
						'event_data'  => array(
							'email'                => $email,
							'reset_request_result' => 'rate_limited',
							'source'               => 'frontend_mock',
						),
					)
				);
			}
			wp_safe_redirect( add_query_arg( 'reset_error', 'rate_limited', $redirect_url ) );
			exit;
		}

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_password_reset_requested',
				array(
					'actor_type'  => 'anonymous',
					'entity_type' => 'password_reset',
					'event_data'  => array(
						'email'                => $email,
						'reset_request_result' => 'accepted_generic_response',
						'source'               => 'frontend_mock',
					),
				)
			);
		}

		wp_safe_redirect( add_query_arg( 'state', 'accepted', $redirect_url ) );
		exit;
	}
	add_action( 'admin_post_nopriv_abitai_mock_password_reset_request', 'abitai_handle_mock_password_reset_request' );
	add_action( 'admin_post_abitai_mock_password_reset_request', 'abitai_handle_mock_password_reset_request' );
}

/**
 * Handle mocked password reset submission responses for the front-end auth MVP.
 */
if ( ! function_exists( 'abitai_handle_mock_password_reset_submit' ) ) {
	function abitai_handle_mock_password_reset_submit() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_safe_redirect( home_url( '/auth/reset' ) );
			exit;
		}

		$token            = isset( $_POST['token'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', sanitize_text_field( wp_unslash( $_POST['token'] ) ) ) : '';
		$password         = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';
		$set_url          = add_query_arg(
			array(
				'state' => 'set',
				'token' => $token,
			),
			home_url( '/auth/reset-password' )
		);

		if ( ! isset( $_POST['abitai_reset_submit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abitai_reset_submit_nonce'] ) ), 'abitai_mock_password_reset_submit' ) ) {
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		if ( 'expired-reset-token' === $token || 'expired' === $token ) {
			if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
				abitai_auth_write_audit_log(
					'auth_password_reset_completed',
					array(
						'actor_type'  => 'anonymous',
						'entity_type' => 'password_reset',
						'event_data'  => array(
							'reset_attempt_result' => 'expired_token',
							'source'               => 'frontend_mock',
						),
					)
				);
			}
			wp_safe_redirect( add_query_arg( 'state', 'expired', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		if ( '' === $token || in_array( $token, array( 'used-reset-token', 'reused-reset-token', 'consumed-reset-token', 'already-used-reset-token', 'invalid-reset-token', 'invalid' ), true ) ) {
			if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
				abitai_auth_write_audit_log(
					'auth_password_reset_completed',
					array(
						'actor_type'  => 'anonymous',
						'entity_type' => 'password_reset',
						'event_data'  => array(
							'reset_attempt_result' => 'invalid_token',
							'source'               => 'frontend_mock',
						),
					)
				);
			}
			wp_safe_redirect( add_query_arg( 'state', 'invalid', home_url( '/auth/reset-password' ) ) );
			exit;
		}

		if ( '' === $password || '' === $confirm_password ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'missing_fields', $set_url ) );
			exit;
		}

		$common_passwords = array(
			'password',
			'password123',
			'password123!',
			'123456789012',
			'qwerty123456',
			'admin1234567',
			'welcome12345',
		);

		if ( strlen( $password ) < 12 || strlen( $password ) > 128 || in_array( strtolower( $password ), $common_passwords, true ) ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'weak_password', $set_url ) );
			exit;
		}

		if ( ! hash_equals( $password, $confirm_password ) ) {
			wp_safe_redirect( add_query_arg( 'reset_error', 'mismatch', $set_url ) );
			exit;
		}

		if ( function_exists( 'abitai_auth_write_audit_log' ) ) {
			abitai_auth_write_audit_log(
				'auth_password_reset_completed',
				array(
					'actor_type'  => 'anonymous',
					'entity_type' => 'password_reset',
					'event_data'  => array(
						'reset_attempt_result' => 'success',
						'source'               => 'frontend_mock',
					),
				)
			);
		}

		wp_safe_redirect( add_query_arg( 'state', 'success', home_url( '/auth/reset-password' ) ) );
		exit;
	}
	add_action( 'admin_post_nopriv_abitai_mock_password_reset_submit', 'abitai_handle_mock_password_reset_submit' );
	add_action( 'admin_post_abitai_mock_password_reset_submit', 'abitai_handle_mock_password_reset_submit' );
}

/**
 * Show the primary AIERP message on the main page.
 */
if ( ! function_exists( 'abitai_front_page_aierp_message' ) ) {
	function abitai_front_page_aierp_message() {
		if ( ! is_front_page() ) {
			return;
		}
		?>
		<section class="abitai-front-page-aierp-message" aria-label="<?php echo esc_attr__( 'World\'s First AIERP', 'astra' ); ?>">
			<div class="ast-container">
				<h1><?php echo esc_html__( 'World\'s First AIERP', 'astra' ); ?></h1>
			</div>
		</section>
		<?php
	}
	add_action( 'astra_content_before', 'abitai_front_page_aierp_message', 5 );
}
