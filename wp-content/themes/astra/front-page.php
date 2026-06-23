<?php
/**
 * Front page template for the temporary coming-soon page.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>

<main class="abitai-coming-soon" style="display:flex;align-items:center;justify-content:center;min-height:70vh;padding:2rem;text-align:center;">
	<div style="max-width:42rem;">
		<div style="color:#2563eb;font-size:.875rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;">ABiT AI</div>
		<h1 style="margin:1rem 0 0;font-size:clamp(3rem, 11vw, 6.5rem);line-height:1;">Coming Soon</h1>
		<p style="margin:1rem auto 0;max-width:34rem;color:#4b5563;font-size:1.125rem;line-height:1.6;">We are preparing a new website experience. Please check back soon.</p>
	</div>
</main>

<?php
get_footer();
