<?php
/**
 * wp eval-file /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
 *
 * What it does
 * ─────────────
 * 1. Uploads any images found in setup/import-images/ to the media library
 *    under wp-content/uploads/tbc-rooms/room-{N}.{ext}.  On re-runs the same
 *    attachment IDs are kept — only the file is replaced in-place.
 *    The slot→ID mapping is stored in the 'tbc_room_images' WP option.
 *
 * 2a. First run  – creates a "Home" page with all six blocks pre-filled.
 * 2b. Re-run     – if images were staged, surgically patches only the
 *    Our Rooms block's frames attribute; everything else is left untouched.
 *
 * 3. Sets the page as the static front page.
 * 4. Creates Primary Navigation and Footer Links menus (skips if present).
 * 5. Activates the theme if not already active.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	die( 'Run via: wp eval-file setup.php' . PHP_EOL );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/* ══════════════════════════════════════════════════════════════════
   §1  ROOM IMAGE UPLOAD
   ══════════════════════════════════════════════════════════════════ */

/**
 * Upload a new room image to wp-content/uploads/tbc-rooms/room-{N}.{ext}.
 * The post_name is set to "tbc-room-{N}" for easy lookup.
 */
function tbc_upload_room_image( string $src, int $slot ): int {
	$upload = wp_upload_dir();
	$dir    = $upload['basedir'] . '/tbc-rooms';
	wp_mkdir_p( $dir );

	$ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
	$name = "room-{$slot}.{$ext}";
	$dest = "{$dir}/{$name}";

	copy( $src, $dest );

	$mime = wp_check_filetype( $dest );
	$id   = wp_insert_attachment(
		[
			'guid'           => $upload['baseurl'] . "/tbc-rooms/{$name}",
			'post_title'     => "Room Image {$slot}",
			'post_name'      => "tbc-room-{$slot}",
			'post_mime_type' => $mime['type'],
			'post_status'    => 'inherit',
			'post_content'   => '',
		],
		$dest,
		0   // not attached to any post
	);

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	return $id;
}

/**
 * Replace an existing attachment's file while keeping the same ID.
 * Handles extension changes (e.g. .jpg → .webp) and cleans up old thumbnails.
 */
function tbc_replace_attachment_file( int $id, string $new_src ): void {
	$old_file = get_attached_file( $id );
	$old_dir  = dirname( $old_file );
	$old_ext  = strtolower( pathinfo( $old_file, PATHINFO_EXTENSION ) );
	$new_ext  = strtolower( pathinfo( $new_src,  PATHINFO_EXTENSION ) );

	// Delete existing thumbnail derivatives
	foreach ( ( wp_get_attachment_metadata( $id )['sizes'] ?? [] ) as $sz ) {
		$thumb = "{$old_dir}/{$sz['file']}";
		if ( file_exists( $thumb ) ) {
			unlink( $thumb );
		}
	}

	// Destination path — rename only if the extension changed
	$dest = ( $new_ext === $old_ext )
		? $old_file
		: preg_replace( '/\.' . preg_quote( $old_ext, '/' ) . '$/', ".{$new_ext}", $old_file );

	copy( $new_src, $dest );

	if ( $dest !== $old_file && file_exists( $old_file ) ) {
		unlink( $old_file );
	}

	$mime = wp_check_filetype( $dest );
	wp_update_post( [ 'ID' => $id, 'post_mime_type' => $mime['type'] ] );
	update_attached_file( $id, $dest );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
}

// Collect staged images (alphabetical → deterministic slot order)
$import_dir = __DIR__ . '/import-images';
$staged     = [];

if ( is_dir( $import_dir ) ) {
	foreach ( scandir( $import_dir ) ?: [] as $f ) {
		if ( preg_match( '/\.(jpe?g|png|webp|gif)$/i', $f ) ) {
			$p = $import_dir . '/' . $f;
			if ( is_file( $p ) ) {
				$staged[] = $p;
			}
		}
	}
	sort( $staged );
}

// Load persisted slot→attachment-ID mapping
$mapping = (array) get_option( 'tbc_room_images', [] );

if ( $staged ) {
	WP_CLI::log( '' );
	WP_CLI::log( '→ Uploading ' . count( $staged ) . ' room image(s) to media library…' );
	WP_CLI::log( '  Stored in:  wp-content/uploads/tbc-rooms/' );
	WP_CLI::log( '  WP option:  tbc_room_images  (slot → attachment ID)' );
	WP_CLI::log( '' );

	foreach ( $staged as $i => $src ) {
		$slot    = $i + 1;
		$prev_id = isset( $mapping[ $slot ] ) ? (int) $mapping[ $slot ] : 0;
		$post    = $prev_id ? get_post( $prev_id ) : null;

		if ( $post && 'attachment' === $post->post_type ) {
			// Keep the same attachment ID — just swap the file
			tbc_replace_attachment_file( $prev_id, $src );
			$mapping[ $slot ] = $prev_id;
			WP_CLI::success( sprintf(
				'  Slot %d → replaced   ID %-6d  %s',
				$slot, $prev_id, basename( $src )
			) );
		} else {
			// First upload for this slot
			$new_id           = tbc_upload_room_image( $src, $slot );
			$mapping[ $slot ] = $new_id;
			WP_CLI::success( sprintf(
				'  Slot %d → uploaded   ID %-6d  %s',
				$slot, $new_id, basename( $src )
			) );
		}
	}

	// Drop slots whose images are no longer in the folder
	foreach ( array_keys( $mapping ) as $k ) {
		if ( (int) $k > count( $staged ) ) {
			unset( $mapping[ $k ] );
		}
	}

	update_option( 'tbc_room_images', $mapping );
}

/* ══════════════════════════════════════════════════════════════════
   §2  RESOLVE IMAGE URLS
   ══════════════════════════════════════════════════════════════════ */

$theme_base = rtrim( get_option( 'siteurl' ), '/' ) . '/wp-content/themes/the-black-cap';
$story_img  = static fn( int $n ): string => "{$theme_base}/assets/img/story/{$n}.webp";

// For a given slot, prefer the uploaded media URL; fall back to theme asset
$room_url = static function ( int $slot ) use ( $mapping, $story_img ): string {
	if ( isset( $mapping[ $slot ] ) ) {
		$url = wp_get_attachment_url( $mapping[ $slot ] );
		if ( $url ) return $url;
	}
	return $story_img( ( ( $slot - 1 ) % 4 ) + 1 );
};

/* ══════════════════════════════════════════════════════════════════
   §3  BUILD Our Rooms frame array
   ══════════════════════════════════════════════════════════════════ */

$frames = [
	[ 'svgFile' => 'Frame 1.svg', 'photos' => [ $room_url(1) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 2.svg', 'photos' => [ $room_url(2) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 3.svg', 'photos' => [ $room_url(3) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 4.svg', 'photos' => [ $room_url(4) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 5.svg', 'photos' => [ $room_url(1) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 6.svg', 'photos' => [ $room_url(2), $room_url(3), $room_url(4) ],   'wide' => true  ],
	[ 'svgFile' => 'Frame 7.svg', 'photos' => [ $room_url(2) ],                               'wide' => false ],
	[ 'svgFile' => 'Frame 8.svg', 'photos' => [ $room_url(1), $room_url(3), $room_url(4) ],   'wide' => true  ],
];

/* ══════════════════════════════════════════════════════════════════
   §4  FRONT PAGE  —  create on first run, patch on re-runs
   ══════════════════════════════════════════════════════════════════ */

$b = static function ( string $name, array $attrs ): string {
	return '<!-- wp:the-black-cap/' . $name . ' '
		. json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		. ' /-->';
};

$front_page_id = (int) get_option( 'page_on_front' );
$front_page    = $front_page_id ? get_post( $front_page_id ) : null;

if ( $front_page && 'page' === $front_page->post_type ) {

	// Page already exists — only patch Our Rooms if fresh images were staged
	if ( $staged ) {
		$blocks  = parse_blocks( $front_page->post_content );
		$patched = false;

		foreach ( $blocks as &$block ) {
			if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/our-rooms' ) {
				$block['attrs']['frames'] = $frames;
				$patched = true;
				break;
			}
		}
		unset( $block );

		if ( $patched ) {
			wp_update_post( [
				'ID'           => $front_page_id,
				'post_content' => serialize_blocks( $blocks ),
			] );
			WP_CLI::success( "Patched Our Rooms block in front page (ID {$front_page_id})." );
		} else {
			WP_CLI::warning( 'Our Rooms block not found — page content unchanged.' );
		}
	} else {
		WP_CLI::log( "  Front page (ID {$front_page_id}) already exists — skipping content." );
	}

} else {

	// ── First run: build the full page from scratch ──────────────
	$content = implode( "\n\n", [

		$b( 'hero-nav', [
			'menuSlug' => 'primary',
			'address'  => '171 Camden High Street, London NW1 7JY',
			'phone'    => '020 7428 2721',
			'email'    => 'Sassy@blackcapcamden.co.uk',
		] ),

		$b( 'whats-on', [
			'shortcodes' => 'DX_5LwekcVa,DYzKkfHMC7z,DYxiCs7x4rm,DYxhePkxa1n,DYxgzTsRa8A,DYxgfJNR2pn,DYfNVZ_De71,DYfNPIKFP5e',
			'limit'      => 8,
		] ),

		$b( 'story', [
			'title' => 'Legendary',
			'copy'  => "The Black Cap has been Camden’s queer heartbeat since 1967. Two bars, a legendary terrace, and a performance room that’s seen more feather boas than the law should allow. Shufflewick’s is where the pints happen. The stage is where the magic does.",
			'photos' => [
				[ 'url' => $story_img(1), 'scale' => 1.3,  'driftX' =>  1.2,  'driftY' => -12.5 ],
				[ 'url' => $story_img(3), 'scale' => 2.2,  'driftX' =>  11.0, 'driftY' =>   3.0 ],
				[ 'url' => $story_img(4), 'scale' => 2.45, 'driftX' =>  -5.4, 'driftY' =>  -9.0 ],
				[ 'url' => $story_img(2), 'scale' => 1.1,  'driftX' => -11.6, 'driftY' =>  14.5 ],
			],
		] ),

		$b( 'highlights', [
			'videoIds' => '7644927884900961558,7642689026490912003,7640829274840190240,7640504644887776544,7640442725908712737,7640087100393606433,7639762417546824992,7639360399963360545',
			'limit'    => 8,
		] ),

		$b( 'drink-menu', [
			'sections' => [
				[ 'category' => 'Draught', 'items' => [
					[ 'name' => 'Guinness',              'price' => '£6.50' ],
					[ 'name' => 'Camden Hells Lager',    'price' => '£6.00' ],
					[ 'name' => 'Camden Pale Ale',       'price' => '£6.20' ],
					[ 'name' => 'Meantime London Lager', 'price' => '£6.20' ],
				] ],
				[ 'category' => 'Wine', 'items' => [
					[ 'name' => 'House glass',  'price' => '£7.00'  ],
					[ 'name' => 'House carafe', 'price' => '£22.00' ],
					[ 'name' => 'Prosecco',     'price' => '£9.00'  ],
				] ],
				[ 'category' => 'Cocktails', 'items' => [
					[ 'name' => 'Negroni',          'price' => '£12.00' ],
					[ 'name' => 'Aperol Spritz',    'price' => '£11.00' ],
					[ 'name' => 'Espresso Martini', 'price' => '£12.00' ],
					[ 'name' => 'Pornstar Martini', 'price' => '£12.00' ],
				] ],
				[ 'category' => 'Spirits & Mixers', 'items' => [
					[ 'name' => 'Single & mixer', 'price' => 'from £8'  ],
					[ 'name' => 'Double & mixer', 'price' => 'from £11' ],
					[ 'name' => 'Shot',           'price' => 'from £4'  ],
				] ],
				[ 'category' => 'Soft Drinks & Low/No', 'items' => [
					[ 'name' => 'Soft drinks',      'price' => 'from £3.50' ],
					[ 'name' => 'Low & no-alcohol', 'price' => 'from £4.50' ],
				] ],
			],
		] ),

		$b( 'our-rooms', [ 'frames' => $frames ] ),

	] );

	$page_id = wp_insert_post(
		[
			'post_title'     => 'Home',
			'post_name'      => 'home',
			'post_content'   => $content,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		],
		true
	);

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( 'Could not create page: ' . $page_id->get_error_message() );
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_id );
	WP_CLI::success( "Created front page (ID {$page_id}) and set as static homepage." );
}

/* ══════════════════════════════════════════════════════════════════
   §5  NAV MENUS
   ══════════════════════════════════════════════════════════════════ */

$setup_menu = static function ( string $name, string $location, array $items ): void {
	$existing = wp_get_nav_menu_object( $name );

	if ( $existing ) {
		WP_CLI::log( "  Menu '{$name}' already exists — skipping." );
		$mid = $existing->term_id;
	} else {
		$mid = wp_create_nav_menu( $name );
		foreach ( $items as [ $title, $url ] ) {
			wp_update_nav_menu_item( $mid, 0, [
				'menu-item-title'  => $title,
				'menu-item-url'    => $url,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			] );
		}
		WP_CLI::success( "Created menu '{$name}' (" . count( $items ) . ' items).' );
	}

	$locs              = get_theme_mod( 'nav_menu_locations', [] );
	$locs[ $location ] = $mid;
	set_theme_mod( 'nav_menu_locations', $locs );
};

$setup_menu( 'Primary Navigation', 'primary', [
	[ 'The Cap Story', '#story'      ],
	[ "What's On",     '#whats-on'   ],
	[ 'Menu',          '#menu'       ],
	[ 'Our Rooms',     '#our-rooms'  ],
	[ 'Book a Table',  '#'           ],
] );

$setup_menu( 'Footer Links', 'footer', [
	[ 'Privacy Policy',     '/privacy'       ],
	[ 'Cookie Policy',      '/cookies'       ],
	[ 'Terms & Conditions', '/terms'         ],
	[ 'Accessibility',      '/accessibility' ],
] );

/* ══════════════════════════════════════════════════════════════════
   §6  ACTIVATE THEME
   ══════════════════════════════════════════════════════════════════ */

if ( 'the-black-cap' !== get_option( 'stylesheet' ) ) {
	switch_theme( 'the-black-cap' );
	WP_CLI::success( 'Activated theme the-black-cap.' );
} else {
	WP_CLI::log( '  Theme the-black-cap already active.' );
}

// Ensure static front page is set (in case page existed from a previous partial run)
update_option( 'show_on_front', 'page' );
if ( ! (int) get_option( 'page_on_front' ) ) {
	$p = get_page_by_path( 'home' );
	if ( $p ) {
		update_option( 'page_on_front', $p->ID );
	}
}

WP_CLI::success( '✓ Done.  ' . get_option( 'siteurl' ) );
