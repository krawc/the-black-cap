<?php
/**
 * Template Name: Black Cap – Fullscreen
 * Template Post Type: page
 *
 * Bare-bones template owned by the plugin. Strips all active-theme styles and
 * scripts so the plugin's own CSS/JS is the only stylesheet on the page.
 * No header, footer, sidebar, or page title from the active theme.
 */

defined( 'ABSPATH' ) || exit;

// Runs at priority 999 inside wp_head(), after every theme/plugin has enqueued.
// Keeps only our styles and WordPress core block styles; dequeues everything else.
add_action( 'wp_enqueue_scripts', function (): void {
	global $wp_styles, $wp_scripts;

	foreach ( $wp_styles->queue as $handle ) {
		if ( strpos( $handle, 'tbc-' )     === 0 ) continue; // our styles
		if ( strpos( $handle, 'wp-block' ) === 0 ) continue; // core block CSS
		if ( $handle === 'dashicons' )             continue; // admin bar needs it
		wp_dequeue_style( $handle );
	}

	foreach ( $wp_scripts->queue as $handle ) {
		if ( strpos( $handle, 'tbc-' ) === 0 ) continue; // our scripts
		if ( strpos( $handle, 'wp-' )  === 0 ) continue; // WP core (admin bar etc.)
		if ( $handle === 'jquery' )             continue;
		wp_dequeue_script( $handle );
	}
}, 999 );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		/* Hard reset — overrides any theme base styles that slipped through */
		html { background: #000; }
		body { margin: 0; padding: 0; background: #000; overflow-x: hidden; }
	</style>
</head>
<body <?php body_class( 'tbc-fullscreen-page' ); ?>>
<?php wp_body_open(); ?>

<?php
if ( have_posts() ) :
	the_post();
	the_content();
endif;
?>

<?php wp_footer(); ?>
</body>
</html>
