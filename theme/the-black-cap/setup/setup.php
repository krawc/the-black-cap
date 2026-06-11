<?php
/**
 * One-shot setup script — run via WP-CLI:
 *
 *   wp-env run cli wp eval-file /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
 *
 * What it does:
 *   1. Creates (or updates) a "Home" page pre-filled with all six blocks and their content.
 *   2. Sets that page as the static front page.
 *   3. Creates and assigns the Primary Navigation and Footer Links menus.
 *   4. Activates the theme if not already active.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	die( 'This script must be run via wp eval-file.' . PHP_EOL );
}

// ── 1. Build page content ─────────────────────────────────────────

// Use the raw siteurl option so this works regardless of which theme is active.
$theme_uri = rtrim( get_option( 'siteurl' ), '/' ) . '/wp-content/themes/the-black-cap';

require_once dirname( __DIR__ ) . '/inc/block-patterns.php';

$content = tbc_front_page_pattern_content( $theme_uri );

// ── 2. Create or update the front page ───────────────────────────

$existing = get_page_by_path( 'home' );
$page_data = [
	'post_title'     => 'Home',
	'post_name'      => 'home',
	'post_content'   => $content,
	'post_status'    => 'publish',
	'post_type'      => 'page',
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
];

if ( $existing ) {
	$page_data['ID'] = $existing->ID;
	$page_id = wp_update_post( $page_data, true );
	WP_CLI::success( "Updated existing front page (ID {$page_id})." );
} else {
	$page_id = wp_insert_post( $page_data, true );
	WP_CLI::success( "Created front page (ID {$page_id})." );
}

if ( is_wp_error( $page_id ) ) {
	WP_CLI::error( 'Could not create page: ' . $page_id->get_error_message() );
}

// ── 3. Set as static front page ──────────────────────────────────

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );
WP_CLI::success( 'Set as static front page.' );

// ── 4. Nav menus ─────────────────────────────────────────────────

$primary_items = [
	[ 'title' => 'The Cap Story', 'url' => '#story'    ],
	[ 'title' => "What's On",     'url' => '#whats-on' ],
	[ 'title' => 'Menu',          'url' => '#menu'     ],
	[ 'title' => 'Our Rooms',     'url' => '#our-rooms'],
	[ 'title' => 'Book a Table',  'url' => '#'         ],
];

$footer_items = [
	[ 'title' => 'Privacy Policy',     'url' => '/privacy'      ],
	[ 'title' => 'Cookie Policy',      'url' => '/cookies'      ],
	[ 'title' => 'Terms & Conditions', 'url' => '/terms'        ],
	[ 'title' => 'Accessibility',      'url' => '/accessibility'],
];

function tbc_setup_menu( $menu_name, $location, array $items ) {
	$existing_menu = wp_get_nav_menu_object( $menu_name );

	if ( $existing_menu ) {
		WP_CLI::log( "  Menu '{$menu_name}' already exists — skipping creation." );
		$menu_id = $existing_menu->term_id;
	} else {
		$menu_id = wp_create_nav_menu( $menu_name );
		foreach ( $items as $item ) {
			wp_update_nav_menu_item( $menu_id, 0, [
				'menu-item-title'  => $item['title'],
				'menu-item-url'    => $item['url'],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			] );
		}
		WP_CLI::success( "Created menu '{$menu_name}' with " . count( $items ) . ' items.' );
	}

	$locations             = get_theme_mod( 'nav_menu_locations', [] );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

tbc_setup_menu( 'Primary Navigation', 'primary', $primary_items );
tbc_setup_menu( 'Footer Links',       'footer',  $footer_items  );

// ── 5. Activate the theme if needed ──────────────────────────────

$active = get_option( 'stylesheet' );
if ( 'the-black-cap' !== $active ) {
	switch_theme( 'the-black-cap' );
	WP_CLI::success( 'Activated theme the-black-cap.' );
} else {
	WP_CLI::log( '  Theme the-black-cap is already active.' );
}

WP_CLI::success( '✓ Setup complete. Visit ' . get_option( 'siteurl' ) . ' to see the site.' );
