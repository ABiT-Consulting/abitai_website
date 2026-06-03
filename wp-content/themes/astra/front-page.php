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
			--aierp-bg: #050914;
			--aierp-cyan: #28f8ff;
			--aierp-green: #39ff88;
			--aierp-amber: #ffd166;
			--aierp-pink: #ff4fd8;
			--aierp-white: #f8fbff;
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
				linear-gradient(120deg, rgba(40, 248, 255, 0.12), transparent 30%),
				linear-gradient(300deg, rgba(255, 79, 216, 0.12), transparent 34%),
				var(--aierp-bg);
			color: var(--aierp-white);
			font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}

		.aierp-stage {
			position: relative;
			display: grid;
			place-items: center;
			min-height: 100vh;
			padding: 24px;
			isolation: isolate;
		}

		.aierp-stage::before,
		.aierp-stage::after {
			position: absolute;
			inset: -18vmax;
			content: "";
			z-index: -2;
			pointer-events: none;
		}

		.aierp-stage::before {
			background:
				linear-gradient(90deg, transparent 0 48%, rgba(40, 248, 255, 0.35) 49% 51%, transparent 52%),
				linear-gradient(0deg, transparent 0 48%, rgba(57, 255, 136, 0.22) 49% 51%, transparent 52%);
			background-size: 86px 86px;
			transform: perspective(900px) rotateX(64deg) translateY(18vh);
			transform-origin: center bottom;
			animation: aierp-grid 7s linear infinite;
			-webkit-mask-image: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent 78%);
			mask-image: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent 78%);
		}

		.aierp-stage::after {
			background:
				conic-gradient(from 90deg at 50% 50%, transparent 0 11%, rgba(40, 248, 255, 0.5) 12% 13%, transparent 14% 36%, rgba(255, 209, 102, 0.42) 37% 38%, transparent 39% 62%, rgba(255, 79, 216, 0.42) 63% 64%, transparent 65% 100%),
				repeating-linear-gradient(115deg, transparent 0 42px, rgba(248, 251, 255, 0.05) 43px 44px);
			animation: aierp-scan 14s linear infinite;
			opacity: 0.9;
		}

		.aierp-title {
			position: relative;
			margin: 0;
			max-width: 96vw;
			white-space: nowrap;
			color: var(--aierp-white);
			font-size: clamp(1.65rem, 8.5vw, 8.5rem);
			font-weight: 900;
			line-height: 0.95;
			letter-spacing: 0;
			text-align: center;
			text-transform: none;
			text-shadow:
				0 0 18px rgba(40, 248, 255, 0.75),
				0 0 42px rgba(255, 79, 216, 0.5),
				0 18px 80px rgba(0, 0, 0, 0.9);
		}

		.aierp-title::before,
		.aierp-title::after {
			position: absolute;
			inset: -0.13em -0.08em;
			content: attr(data-text);
			z-index: -1;
			color: transparent;
			-webkit-text-stroke: 1px rgba(40, 248, 255, 0.72);
			clip-path: polygon(0 0, 100% 0, 100% 38%, 0 54%);
			transform: translate3d(-0.035em, -0.02em, 0);
			animation: aierp-glitch-a 2.8s steps(2, end) infinite;
		}

		.aierp-title::after {
			-webkit-text-stroke-color: rgba(255, 79, 216, 0.72);
			clip-path: polygon(0 53%, 100% 39%, 100% 100%, 0 100%);
			transform: translate3d(0.035em, 0.025em, 0);
			animation-name: aierp-glitch-b;
		}

		.aierp-lightline {
			position: absolute;
			left: 50%;
			top: 50%;
			width: min(1200px, 92vw);
			height: 2px;
			z-index: -1;
			background: linear-gradient(90deg, transparent, var(--aierp-cyan), var(--aierp-green), var(--aierp-amber), transparent);
			box-shadow: 0 0 28px rgba(40, 248, 255, 0.8);
			transform: translate(-50%, 4.8em);
			animation: aierp-pulse 2.4s ease-in-out infinite;
		}

		@keyframes aierp-grid {
			to {
				background-position: 0 86px, 0 86px;
			}
		}

		@keyframes aierp-scan {
			to {
				transform: rotate(360deg);
			}
		}

		@keyframes aierp-glitch-a {
			0%, 80%, 100% {
				transform: translate3d(-0.035em, -0.02em, 0);
			}

			82% {
				transform: translate3d(-0.065em, 0.015em, 0);
			}

			84% {
				transform: translate3d(0.02em, -0.035em, 0);
			}
		}

		@keyframes aierp-glitch-b {
			0%, 78%, 100% {
				transform: translate3d(0.035em, 0.025em, 0);
			}

			80% {
				transform: translate3d(0.07em, -0.01em, 0);
			}

			83% {
				transform: translate3d(-0.025em, 0.04em, 0);
			}
		}

		@keyframes aierp-pulse {
			0%, 100% {
				opacity: 0.35;
				transform: translate(-50%, 4.8em) scaleX(0.84);
			}

			50% {
				opacity: 1;
				transform: translate(-50%, 4.8em) scaleX(1);
			}
		}

		@media (prefers-reduced-motion: reduce) {
			.aierp-stage::before,
			.aierp-stage::after,
			.aierp-title::before,
			.aierp-title::after,
			.aierp-lightline {
				animation: none;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'aierp-front-page' ); ?>>
	<main class="aierp-stage" aria-label="<?php echo esc_attr( $page_title ); ?>">
		<h1 class="aierp-title" data-text="<?php echo esc_attr( $page_title ); ?>"><?php echo esc_html( $page_title ); ?></h1>
		<div class="aierp-lightline" aria-hidden="true"></div>
	</main>
</body>
</html>
