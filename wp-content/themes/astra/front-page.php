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
