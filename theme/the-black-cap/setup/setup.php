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

/**
 * Upload a timeline image to wp-content/uploads/tbc-timeline/timeline-{N}.{ext}.
 */
function tbc_upload_timeline_image( string $src, int $slot ): int {
	$upload = wp_upload_dir();
	$dir    = $upload['basedir'] . '/tbc-timeline';
	wp_mkdir_p( $dir );

	$ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
	$name = "timeline-{$slot}.{$ext}";
	$dest = "{$dir}/{$name}";

	copy( $src, $dest );

	$mime = wp_check_filetype( $dest );
	$id   = wp_insert_attachment(
		[
			'guid'           => $upload['baseurl'] . "/tbc-timeline/{$name}",
			'post_title'     => "Timeline Image {$slot}",
			'post_name'      => "tbc-timeline-{$slot}",
			'post_mime_type' => $mime['type'],
			'post_status'    => 'inherit',
			'post_content'   => '',
		],
		$dest,
		0
	);

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	return $id;
}

// Set by run.sh via docker -e TBC_SKIP_ROOMS=1 to skip the room image import.
$skip_rooms = ! empty( getenv( 'TBC_SKIP_ROOMS' ) );

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

if ( $skip_rooms ) {
	WP_CLI::log( '  --skip-rooms flag set — skipping room image import.' );
}

if ( ! $skip_rooms && $staged ) {
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
   §1b  TIMELINE IMAGE UPLOAD
   ══════════════════════════════════════════════════════════════════ */

$tl_img_dir  = get_template_directory() . '/assets/img/timeline';
$tl_mapping  = (array) get_option( 'tbc_timeline_images', [] );
$tl_uploaded = false;

WP_CLI::log( '' );
WP_CLI::log( '→ Ensuring timeline images are in media library…' );

for ( $tl_slot = 1; $tl_slot <= 5; $tl_slot++ ) {
	$tl_src     = "{$tl_img_dir}/{$tl_slot}.webp";
	$tl_prev_id = isset( $tl_mapping[ $tl_slot ] ) ? (int) $tl_mapping[ $tl_slot ] : 0;
	$tl_post    = $tl_prev_id ? get_post( $tl_prev_id ) : null;

	if ( $tl_post && 'attachment' === $tl_post->post_type ) {
		WP_CLI::log( "  Timeline slot {$tl_slot} → already uploaded (ID {$tl_prev_id})" );
	} elseif ( ! file_exists( $tl_src ) ) {
		WP_CLI::warning( "  Timeline slot {$tl_slot} → source not found: {$tl_src}" );
	} else {
		$tl_new_id             = tbc_upload_timeline_image( $tl_src, $tl_slot );
		$tl_mapping[ $tl_slot ] = $tl_new_id;
		$tl_uploaded            = true;
		WP_CLI::success( sprintf(
			'  Slot %d → uploaded   ID %-6d  %s',
			$tl_slot, $tl_new_id, basename( $tl_src )
		) );
	}
}

if ( $tl_uploaded ) {
	update_option( 'tbc_timeline_images', $tl_mapping );
}

// Helper: get a timeline attachment ID (0 if the slot was not uploaded)
$tl_id = static function ( int $slot ) use ( $tl_mapping ): int {
	return isset( $tl_mapping[ $slot ] ) ? (int) $tl_mapping[ $slot ] : 0;
};

// Combine multiple paragraphs with a blank-line separator (preserved by pre-wrap in CSS)
$paras = static function ( string ...$ps ): string {
	return implode( "\n\n", $ps );
};

$timeline_attrs = [
	'introText'  => "For more than 250 years, The Black Cap has been at the heart of Camden. Known as one of London's most historic pubs and a cornerstone of LGBTQ+ culture, it has hosted legendary performers, launched careers and offered generations a safe and celebratory space.\n\nNow, at long last, the Cap is OPEN once more. It's been saved not just by law, but by love, by the thousands who stood up for it, sang for it, and believed in it. The Cap has always been more than bricks and mortar. It's drag and glitter, it's protest and power, it's the place where outsiders became insiders.",
	'timestamps' => [
		[
			'id'          => 'ts-1',
			'years'       => '1751–1960',
			'title'       => 'WITCHES & THE START OF SOMETHING SPECIAL',
			'description' => $paras(
				"The Black Cap's story begins way back in 1751, when it first opened as the Mother Black Cap. Local Camden folklore says it was named after a witch – “Mother Damnable” – who was said to curse anyone who crossed her. By 1781, the pub had moved to its current spot on Camden High Street, and in 1889 it was rebuilt into the Victorian building you see today. If you look up, you'll spot her: a stone bust of Mother Black Cap, still watching over the door like she has for over a century."
			),
			'imageIds'    => array_values( array_filter( [ $tl_id(1) ] ) ),
		],
		[
			'id'          => 'ts-2',
			'years'       => '1960s',
			'title'       => 'FROM LOCAL TO QUEER HEAVEN',
			'description' => $paras(
				"In the 1960s, long before it was legal to be openly gay in this country, the Black Cap became something more than a pub. It became a safe meeting place. By the mid-60s it had already built a reputation as one of London's very first “gay pubs” and by the 70s it had a new title too: the Palladium of Drag.",
				"Legends of British drag like Danny La Rue, Hinge & Bracket, and above all Mrs Shufflewick made this their stage. Shufflewick's Sunday shows were infamous – packed with everyone from local regulars to big names like Barry Humphries."
			),
			'imageIds'    => array_values( array_filter( [ $tl_id(2) ] ) ),
		],
		[
			'id'          => 'ts-3',
			'years'       => '1970s–1980s',
			'title'       => 'THE GOLDEN YEARS',
			'description' => $paras(
				"Through the 70s and 80s the Cap wasn't just a pub – it was a lifeline. You came here to laugh with a drag queen tearing the house down, to flirt, to dance, to cry on someone's shoulder. For many, it was the first place they truly felt at home.",
				"Acts like Regina Fong brought the house down night after night, with a fanbase who called themselves the “Fongettes.” The Cap also gave space to community groups: from trans support meetups to London Gay Symphonic Winds rehearsals. It wasn't just entertainment, it was solidarity.",
				"By the 2000s, the Cap was still buzzing, with nights like The Meth Lab mixing drag, cabaret and surreal performance. Stars of RuPaul's Drag Race – Bianca Del Rio, Trixie Mattel, Raja, Adore Delano – all performed on the stage."
			),
			'imageIds'    => array_values( array_filter( [ $tl_id(3) ] ) ),
		],
		[
			'id'          => 'ts-4',
			'years'       => '1990s–2010s',
			'title'       => 'A VENUE WITH COMMUNITY WEIGHT',
			'description' => $paras(
				"The Black Cap's importance has never been limited to nightlife. For many, it represented something rare: a public place where being openly LGBTQ+ felt normal, safe, and shared. Former staff and regulars have described it as a welcoming, mixed crowd across ages – a place to meet, talk, laugh, and feel part of something bigger than a night out. That community role was formally recognised when Camden Council granted Asset of Community Value (ACV) status – a protection designed to acknowledge places that contribute to local social and cultural life.",
				"In more recent years, community work and campaigning continued beyond the building itself. Partnerships and grassroots groups helped keep the spirit of The Black Cap alive through organised meet-ups and advocacy driven by the belief that London needs queer spaces that aren't disposable."
			),
			'imageIds'    => array_values( array_filter( [ $tl_id(4) ] ) ),
		],
		[
			'id'          => 'ts-5',
			'years'       => '2020s',
			'title'       => 'A NEW CHAPTER',
			'description' => $paras(
				"Now, at long last, the Cap is reopening. It's been saved not just by law, but by love – by the thousands who stood up for it, sang for it, and believed in it.",
				"The Black Cap returns with the same rebellious spirit, inclusive heart, and unforgettable nights that made it a cornerstone of queer culture in London. Join us as we celebrate our past, and raise a glass to the future."
			),
			'imageIds'    => array_values( array_filter( [ $tl_id(5) ] ) ),
		],
	],
];

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

	// Always parse so we can both patch Our Rooms and insert Timeline if missing.
	$blocks       = parse_blocks( $front_page->post_content );
	$patched      = false;
	$has_timeline = false;

	foreach ( $blocks as &$block ) {
		if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/our-rooms' && $staged ) {
			$block['attrs']['frames'] = $frames;
			$patched = true;
		}
		if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/timeline' ) {
			$has_timeline = true;
			$block['attrs'] = $timeline_attrs;
			$patched = true;
		}
	}
	unset( $block );

	// Insert Timeline block after Our Story if it doesn't exist yet.
	if ( ! $has_timeline ) {
		$story_pos = null;
		foreach ( $blocks as $idx => $blk ) {
			if ( ( $blk['blockName'] ?? '' ) === 'the-black-cap/story' ) {
				$story_pos = $idx;
				break;
			}
		}
		$tl_parsed = parse_blocks( $b( 'timeline', $timeline_attrs ) );
		if ( ! empty( $tl_parsed[0] ) ) {
			$insert_at = $story_pos !== null ? $story_pos + 1 : count( $blocks );
			array_splice( $blocks, $insert_at, 0, [ $tl_parsed[0] ] );
			$patched = true;
			WP_CLI::success( 'Inserted Timeline block after Our Story.' );
		}
	}

	if ( $patched ) {
		wp_update_post( [
			'ID'           => $front_page_id,
			'post_content' => serialize_blocks( $blocks ),
		] );
		WP_CLI::success( "Updated front page (ID {$front_page_id})." );
	} else {
		WP_CLI::log( "  Front page (ID {$front_page_id}) is already up to date." );
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
			'eventIds' => '',
			'limit'    => 8,
		] ),

		$b( 'story', [
			'title' => 'Legendary',
			'copy'  => "The Black Cap isn't just a venue with a famous name - it's a building, a stage, and a community landmark. From its historic façade on Camden High Street to the performance room that helped shape London cabaret, The Black Cap has long been a place where talent breaks through, audiences gather and queer culture is celebrated.",
			'photos' => [
				[ 'url' => $story_img(1), 'scale' => 1.3,  'driftX' =>  1.2,  'driftY' => -12.5 ],
				[ 'url' => $story_img(3), 'scale' => 2.2,  'driftX' =>  11.0, 'driftY' =>   3.0 ],
				[ 'url' => $story_img(4), 'scale' => 2.45, 'driftX' =>  -5.4, 'driftY' =>  -9.0 ],
				[ 'url' => $story_img(2), 'scale' => 1.1,  'driftX' => -11.6, 'driftY' =>  14.5 ],
			],
		] ),

		$b( 'timeline', $timeline_attrs ),

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
   §6  API DEFAULTS
   ══════════════════════════════════════════════════════════════════ */

// Pre-seed the Eventbrite org ID so the What's On block works as soon
// as an API token is added in Settings → Black Cap.
if ( ! get_option( 'tbc_eventbrite_org_id' ) ) {
	update_option( 'tbc_eventbrite_org_id', '3005226258349' );
	WP_CLI::success( "Set Eventbrite org ID → 3005226258349. Add your API token in Settings → Black Cap." );
} else {
	WP_CLI::log( '  Eventbrite org ID already set — skipping.' );
}

/* ══════════════════════════════════════════════════════════════════
   §7  ACTIVATE THEME
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
