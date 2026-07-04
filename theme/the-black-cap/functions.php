<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/settings-page.php';
require_once __DIR__ . '/inc/block-patterns.php';
require_once __DIR__ . '/inc/cpt-room.php';
require_once __DIR__ . '/inc/cpt-venue.php';

/* ── Theme setup ──────────────────────────────────────────────── */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/frontend.css' );

	register_nav_menus( [
		'primary' => __( 'Primary Navigation (Hero orbit)', 'the-black-cap' ),
		'footer'  => __( 'Footer Legal Links', 'the-black-cap' ),
	] );
} );

/* ── Block registration ───────────────────────────────────────── */
add_action( 'init', function () {
	foreach ( glob( __DIR__ . '/build/blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
} );

/* ── Frontend assets ──────────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'tbc-google-fonts',
		'https://fonts.googleapis.com/css2?family=Train+One&family=Montserrat:wght@400;700;800;900&display=swap',
		[],
		null
	);
	wp_enqueue_style( 'tbc-frontend', get_template_directory_uri() . '/assets/css/frontend.css', [], '1.0.0' );
	wp_enqueue_script(
		'tbc-icons',
		get_template_directory_uri() . '/assets/js/icons.js',
		[],
		filemtime( get_template_directory() . '/assets/js/icons.js' ),
		true
	);
	wp_enqueue_script(
		'tbc-frontend',
		get_template_directory_uri() . '/assets/js/frontend.js',
		[ 'tbc-icons' ],
		'1.0.0',
		true
	);
} );

/* ── Editor assets ────────────────────────────────────────────── */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'tbc-google-fonts-editor',
		'https://fonts.googleapis.com/css2?family=Train+One&family=Montserrat:wght@400;700;800;900&display=swap',
		[],
		null
	);
	wp_enqueue_style( 'tbc-editor', get_template_directory_uri() . '/assets/css/frontend.css', [], '1.0.0' );
} );

/* ── Cache-busting AJAX for settings page ─────────────────────── */
add_action( 'wp_ajax_tbc_clear_cache', function () {
	check_ajax_referer( 'tbc_clear_cache' );
	delete_transient( 'tbc_eventbrite_events' );
	delete_transient( 'tbc_tiktok_videos' );
	wp_send_json_success( __( 'Cache cleared.', 'the-black-cap' ) );
} );
