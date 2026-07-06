<?php
/**
 * Plugin Name: The Black Cap
 * Plugin URI:  https://blackcapcamden.co.uk
 * Description: Blocks, CPTs, and content setup for The Black Cap, Camden.
 * Version:     1.0.0
 * Author:      The Black Cap
 * License:     GPL-2.0-or-later
 * Text Domain: the-black-cap
 */

defined( 'ABSPATH' ) || exit;

define( 'TBC_PLUGIN_VERSION', '1.0.0' );
define( 'TBC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TBC_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once TBC_PLUGIN_DIR . 'inc/cpt-room.php';
require_once TBC_PLUGIN_DIR . 'inc/cpt-venue.php';
require_once TBC_PLUGIN_DIR . 'inc/settings-page.php';
require_once TBC_PLUGIN_DIR . 'inc/block-patterns.php';
require_once TBC_PLUGIN_DIR . 'inc/setup/class-setup-runner.php';
require_once TBC_PLUGIN_DIR . 'inc/setup/admin-page.php';
require_once TBC_PLUGIN_DIR . 'inc/setup/ajax-handlers.php';

/* ── Fullscreen page template ─────────────────────────────────────────── */

// Expose "Black Cap – Fullscreen" in the Page Attributes template dropdown.
add_filter( 'theme_page_templates', function ( array $templates ): array {
	$templates['tbc-fullscreen'] = __( 'Black Cap – Fullscreen', 'the-black-cap' );
	return $templates;
} );

// Serve the plugin's own template file when that template is selected.
add_filter( 'template_include', function ( string $template ): string {
	if ( ! is_singular( 'page' ) ) return $template;
	if ( get_page_template_slug() !== 'tbc-fullscreen' ) return $template;

	$plugin_tpl = TBC_PLUGIN_DIR . 'templates/tbc-fullscreen.php';
	return file_exists( $plugin_tpl ) ? $plugin_tpl : $template;
} );

/* ── Block registration ───────────────────────────────────────────────── */
add_action( 'init', function () {
	foreach ( glob( TBC_PLUGIN_DIR . 'build/blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
} );

/* ── Frontend assets (scoped to pages that contain our blocks) ────────── */
add_action( 'wp_enqueue_scripts', function () {
	// Only load on singular pages whose content contains at least one TBC block.
	// This covers both the production front page and any staging page automatically.
	global $post;
	if ( ! is_singular() || ! $post instanceof WP_Post ) return;
	if ( strpos( $post->post_content, '<!-- wp:the-black-cap/' ) === false ) return;

	wp_enqueue_style(
		'tbc-frontend',
		TBC_PLUGIN_URL . '/assets/css/frontend.css',
		[],
		TBC_PLUGIN_VERSION
	);
	wp_enqueue_script(
		'tbc-icons',
		TBC_PLUGIN_URL . '/assets/js/icons.js',
		[],
		filemtime( TBC_PLUGIN_DIR . 'assets/js/icons.js' ),
		true
	);
	wp_enqueue_script(
		'tbc-frontend',
		TBC_PLUGIN_URL . '/assets/js/frontend.js',
		[ 'tbc-icons' ],
		TBC_PLUGIN_VERSION,
		true
	);
} );

// Google Fonts loaded non-blocking (media="print" swap trick) to avoid
// render-blocking the LCP. preconnect hints go out at priority 1 so the
// TCP+TLS handshake starts as early as possible.
add_action( 'wp_head', function () {
	global $post;
	if ( ! is_singular() || ! $post instanceof WP_Post ) return;
	if ( strpos( $post->post_content, '<!-- wp:the-black-cap/' ) === false ) return;
	$url = 'https://fonts.googleapis.com/css2?family=Train+One&family=Montserrat:wght@400;700;800;900&display=swap';
	?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" media="print" onload="this.media='all'" href="<?php echo esc_url( $url ); ?>">
<noscript><link rel="stylesheet" href="<?php echo esc_url( $url ); ?>"></noscript>
	<?php
}, 1 );

/* ── Editor assets ────────────────────────────────────────────────────── */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'tbc-google-fonts-editor',
		'https://fonts.googleapis.com/css2?family=Train+One&family=Montserrat:wght@400;700;800;900&display=swap',
		[],
		null
	);
	wp_enqueue_style(
		'tbc-editor',
		TBC_PLUGIN_URL . '/assets/css/frontend.css',
		[],
		TBC_PLUGIN_VERSION
	);
} );

/* ── Cache-busting AJAX ───────────────────────────────────────────────── */
add_action( 'wp_ajax_tbc_clear_cache', function () {
	check_ajax_referer( 'tbc_clear_cache' );
	delete_transient( 'tbc_eventbrite_events' );
	delete_transient( 'tbc_tiktok_videos' );
	wp_send_json_success( __( 'Cache cleared.', 'the-black-cap' ) );
} );
