<?php
/**
 * Front page template for the AIERP launch message.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$page_title = "World's First AIERP";

add_filter(
	'pre_get_document_title',
	static function () use ( $page_title ) {
		return $page_title;
	},
	100
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		:root {
			--aierp-bg: #f7f8fb;
			--aierp-surface: #ffffff;
			--aierp-ink: #111827;
			--aierp-muted: #667085;
			--aierp-blue: #2490ef;
			--aierp-teal: #12b5cb;
			--aierp-gold: #f2b84b;
			--aierp-border: #e4e7ec;
		}

		* {
			box-sizing: border-box;
		}

		html,
		body {
			min-height: 100%;
			margin: 0;
		}

		body {
			overflow: hidden;
			background:
				linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(247, 248, 251, 0.96)),
				linear-gradient(90deg, rgba(36, 144, 239, 0.08) 1px, transparent 1px),
				linear-gradient(0deg, rgba(36, 144, 239, 0.08) 1px, transparent 1px),
				var(--aierp-bg);
			background-size: auto, 56px 56px, 56px 56px, auto;
			color: var(--aierp-ink);
			font-family: Inter, "Inter var", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}

		.aierp-stage {
			position: relative;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			padding: 24px;
			text-align: center;
		}

		.aierp-title {
			position: relative;
			margin: 0;
			max-width: 96vw;
			color: var(--aierp-ink);
			font-size: clamp(2.6rem, 8vw, 7.8rem);
			font-weight: 760;
			line-height: 0.98;
			letter-spacing: 0;
			text-transform: none;
			text-wrap: balance;
		}

		.aierp-lightline {
			width: min(760px, 76vw);
			height: 1px;
			margin: 30px auto 0;
			background: linear-gradient(90deg, transparent, var(--aierp-blue), var(--aierp-teal), var(--aierp-gold), transparent);
		}

		.aierp-kicker {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 22px;
			padding: 7px 12px;
			border: 1px solid var(--aierp-border);
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.82);
			color: var(--aierp-muted);
			font-size: 0.86rem;
			font-weight: 650;
		}

		.aierp-kicker::before {
			content: "";
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: var(--aierp-blue);
			box-shadow: 0 0 0 4px rgba(36, 144, 239, 0.12);
		}

		.aierp-subtitle {
			max-width: 680px;
			margin: 24px auto 0;
			color: var(--aierp-muted);
			font-size: clamp(1rem, 2vw, 1.25rem);
			line-height: 1.65;
		}

		@media (max-width: 520px) {
			.aierp-stage {
				padding: 18px;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'aierp-front-page' ); ?>>
	<main class="aierp-stage" aria-label="<?php echo esc_attr( $page_title ); ?>">
		<p class="aierp-kicker">ABiT AI ERP Platform</p>
		<h1 class="aierp-title" data-text="<?php echo esc_attr( $page_title ); ?>"><?php echo esc_html( $page_title ); ?></h1>
		<p class="aierp-subtitle">A clean enterprise workspace for intelligent ERP operations, automation, and decisions.</p>
		<div class="aierp-lightline" aria-hidden="true"></div>
	</main>
</body>
</html>
