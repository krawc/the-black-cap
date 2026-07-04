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

/**
 * Upload a venue image to wp-content/uploads/tbc-venues/venue-{N}.{ext}.
 */
function tbc_upload_venue_image( string $src, int $slot ): int {
	$upload = wp_upload_dir();
	$dir    = $upload['basedir'] . '/tbc-venues';
	wp_mkdir_p( $dir );

	$ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
	$name = "venue-{$slot}.{$ext}";
	$dest = "{$dir}/{$name}";

	copy( $src, $dest );

	$mime = wp_check_filetype( $dest );
	$id   = wp_insert_attachment(
		[
			'guid'           => $upload['baseurl'] . "/tbc-venues/{$name}",
			'post_title'     => "Venue Image {$slot}",
			'post_name'      => "tbc-venue-img-{$slot}",
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

/* ══════════════════════════════════════════════════════════════════
   §3  ROOM DEFINITIONS
   ══════════════════════════════════════════════════════════════════ */

$paras_r = static function ( string ...$ps ): string {
	return implode( "\n\n", $ps );
};

$room_defs = [
	1 => [
		'name' => "Adrella's Dressing Room",
		'desc' => $paras_r(
			"Bathed in deep reds and soft golden light, Room One is a love letter to The Black Cap's unapologetic spirit. Plush bedding, playful artwork and theatrical touches bring a sense of drama to every corner. Fringe bedside lamps shimmer like flapper dresses, sunset-toned curtains frame the room, and a sculptural mannequin stands ready to strike a pose.",
			"It's bold, sultry and delightfully expressive — a room that celebrates individuality with a wink and a touch of glamour."
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/8127f737-ea3b-43ff-8040-b41900c266e5?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/a159a717-aee0-4b77-b06a-b41900c22ae3?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/5f4de961-be5a-4bda-83da-b41900c24529?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/fa99178d-f187-488d-b74d-b41900c24fbd?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/911fa27d-6045-4ae3-9bda-b41900c23311?quality=85&width=1366&height=768',
		],
	],
	2 => [
		'name' => "Maisie Trollette's Legacy Room",
		'desc' => $paras_r(
			"A little mischievous and undeniably luxurious, Room Two is designed to make an impression. Moody colours, deep velvet tones and statement artwork create a space that feels both intimate and dramatic.",
			"Golden accents and sculptural lighting bring a touch of glamour, while the rich textures feel indulgent and inviting. Stylish, seductive and a little bit provocative — this is a room that knows exactly how to set the mood."
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/a0da4a10-25f1-407d-9bfa-b41900c379a3?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/b6cf530a-3546-4775-8116-b41900c38779?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/b303fc2c-6288-4f9d-a156-b41900c39da9?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/a3a594e1-16c0-4714-b6b8-b41900c3a82b?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/4af52a61-c72c-4a7d-b04e-b41900c3c0f5?quality=85&width=1366&height=768',
		],
	],
	3 => [
		'name' => "Miss Jason's Miss Behave Room",
		'desc' => $paras_r(
			"Room Three brings big-top energy with bedroom swagger. Deep burgundy headboards, a warm golden glow and a striped ceiling inspired by vintage circus tents create a space made to steal the spotlight.",
			"Statement lighting and sculptural forms add playful flair, while warm wood finishes and rich textures keep the room cosy enough for a long encore. Confident, creative and delightfully theatrical — just the way we like it."
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/9a3722fd-39ce-44dd-b66e-b41900c43b6c?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/8aad7005-2f84-4d20-aece-b41900c4483f?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/e5a47790-82c1-4f92-8d22-b41900c451ad?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/6c8542cb-3d7f-434f-b001-b41900c45e33?quality=85&width=1366&height=768',
		],
	],
	4 => [
		'name' => 'The Harlequeens Masquerade',
		'desc' => $paras_r(
			"Room Four is pure theatre — bold, beautiful and full of personality. Wrapped in deep green panelling and blooming floral wallpaper, it feels like a stage set designed for indulgence.",
			"Jewel tones, velvet textures and flashes of neon drama create a feast for the senses. From parrot-shaped lights to fringe lamps and golden bedside tables, every detail has its moment. Cabaret glamour meets irresistible comfort in a room that was made for centre stage."
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/21e4021a-b9b0-47bd-9354-b41900c4d82d?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/5b74ab7f-cf09-401f-9a00-b41900c4ecc0?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/b0223819-3a47-4242-81ca-b41900c51bfc?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/31fec44d-dfbb-4091-a828-b41900c52fc7?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/37faab57-2293-4c3f-a21f-b41900c53bf3?quality=85&width=1366&height=768',
		],
	],
	5 => [
		'name' => "Danny La Rue's La Rue Luxe",
		'desc' => $paras_r(
			"Room Six blends vintage charm with a hint of wild glamour. Deep blues and warm amber tones create an atmosphere that feels like golden hour stretching into the night.",
			"Feathered lighting, curved shapes and layered textures bring a touch of theatre, while mid-century furniture adds timeless style. Relaxed yet charismatic, it's the kind of room that invites you to stay up late and enjoy the mood."
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/e8fe70e9-91df-4daa-a32c-b41900c6422a?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/ecdde622-0813-400a-a15b-b41900c650b1?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/d2c9114b-9246-4f8a-95f4-b41900c657db?quality=85&width=1366&height=768',
		],
	],
	6 => [
		'name' => 'Imperial Suite',
		'desc' => $paras_r(
			"The Imperial Suite blends outrageous styling with more space to create a blend of artistry, camp frolicking and decadence that will make you feel like the Queen you are!",
			"Subtle walls blend with bold design, and who doesn't love a neon queen above their bed?"
		),
		'imgs' => [
			'https://cdn.mews.com/media/image/24aad023-53ca-49e8-8ca9-b41900c74da3?quality=85&width=1920&height=1080',
			'https://cdn.mews.com/media/image/38e38470-8d4e-4d29-bed5-b41900c75766?quality=85&width=1366&height=768',
			'https://cdn.mews.com/media/image/3c6a5881-a7d9-469f-9454-b41900c76322?quality=85&width=1366&height=768',
		],
	],
	7 => [
		'name' => 'The Vivienne House of Vivienne',
		'desc' => 'Coming soon!',
		'imgs' => [
			'https://cdn.mews.com/media/image/42355982-bf18-46ed-9c03-b41900c5d876?quality=85&width=1920&height=1080',
		],
	],
];

/* ══════════════════════════════════════════════════════════════════
   §3a  SIDELOAD CDN IMAGES  (skips URLs already in the cache)
   ══════════════════════════════════════════════════════════════════ */

// tbc_room_cdn_images: url → attachment_id  (persists across runs)
$cdn_map     = (array) get_option( 'tbc_room_cdn_images', [] );
$cdn_changed = false;

WP_CLI::log( '' );
WP_CLI::log( '→ Sideloading room images from CDN…' );

foreach ( $room_defs as $slot => $def ) {
	foreach ( $def['imgs'] as $i => $url ) {
		if ( isset( $cdn_map[ $url ] ) ) {
			WP_CLI::log( "  Already imported: room {$slot} img " . ( $i + 1 ) . " → ID {$cdn_map[ $url ]}" );
			continue;
		}

		// media_sideload_image() requires a file extension in the URL path,
		// which Mews CDN URLs lack. Download manually and supply a filename.
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			WP_CLI::warning( "  Download failed: {$url} — " . $tmp->get_error_message() );
			continue;
		}

		$mime    = wp_get_image_mime( $tmp ) ?: 'image/jpeg';
		$ext_map = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif' ];
		$ext     = $ext_map[ $mime ] ?? 'jpg';

		$file_array = [
			'name'     => "room-{$slot}-" . ( $i + 1 ) . ".{$ext}",
			'tmp_name' => $tmp,
		];

		$att_id = media_handle_sideload( $file_array, 0, null );

		if ( is_wp_error( $att_id ) ) {
			@unlink( $tmp );
			WP_CLI::warning( "  Import failed: {$url} — " . $att_id->get_error_message() );
		} else {
			$cdn_map[ $url ] = (int) $att_id;
			$cdn_changed     = true;
			WP_CLI::success( "  Imported room {$slot} img " . ( $i + 1 ) . " → ID {$att_id}" );
		}
	}
}

if ( $cdn_changed ) {
	update_option( 'tbc_room_cdn_images', $cdn_map );
}

/* ══════════════════════════════════════════════════════════════════
   §3b  ENSURE ROOM CPT POSTS + ASSIGN IMAGES
   ══════════════════════════════════════════════════════════════════ */

WP_CLI::log( '' );
WP_CLI::log( '→ Ensuring Room posts…' );

$room_post_ids = [];  // slot → tbc_room post ID

foreach ( $room_defs as $slot => $def ) {
	// Use "room-{slot}" slug — different from "tbc-room-{slot}" used by §1 attachments.
	// Avoid get_page_by_path(): it silently appends 'attachment' to the type query,
	// which causes it to match the §1 gallery attachments instead of CPT posts.
	$slug   = "room-{$slot}";
	$found  = get_posts( [
		'post_type'   => 'tbc_room',
		'post_status' => 'any',
		'name'        => $slug,
		'numberposts' => 1,
	] );
	$existing = $found[0] ?? null;

	if ( $existing ) {
		$room_post_ids[ $slot ] = $existing->ID;
		wp_update_post( [ 'ID' => $existing->ID, 'post_title' => $def['name'] ] );
		WP_CLI::log( "  Slot {$slot} → updated (ID {$existing->ID}: {$def['name']})" );
	} else {
		$new_id = wp_insert_post( [
			'post_type'   => 'tbc_room',
			'post_title'  => $def['name'],
			'post_name'   => $slug,
			'post_status' => 'publish',
		] );
		$room_post_ids[ $slot ] = $new_id;
		WP_CLI::success( "  Slot {$slot} → created '{$def['name']}' (ID {$new_id})" );
	}

	$pid = $room_post_ids[ $slot ];
	update_post_meta( $pid, 'tbc_room_description', $def['desc'] );

	$att_ids = array_values( array_filter( array_map(
		static fn( string $u ): int => $cdn_map[ $u ] ?? 0,
		$def['imgs']
	) ) );
	update_post_meta( $pid, 'tbc_room_image_ids', $att_ids );
}

// Helper: get tbc_room post ID for a slot (0 if not found)
$room_id = static function ( int $slot ) use ( $room_post_ids ): int {
	return $room_post_ids[ $slot ] ?? 0;
};

/* ══════════════════════════════════════════════════════════════════
   §3c  VENUE DEFINITIONS
   ══════════════════════════════════════════════════════════════════ */

$venue_img_dir = get_template_directory() . '/assets/img/venues';

$venue_defs = [
	1 => [
		'name' => 'Regina Fong Terrace',
		'desc' => 'The outdoor rooftop space located at the rear of the first floor, accessed directly through the Shufflewick Bar.',
		'img'  => "{$venue_img_dir}/terrace.webp",
	],
	2 => [
		'name' => 'Ms Shufflewick Bar',
		'desc' => 'The first-floor bar area with seating booths, named after the pioneering 1950s drag performer.',
		'img'  => "{$venue_img_dir}/shufflewick.webp",
	],
	3 => [
		'name' => "Lily's Bar",
		'desc' => 'The ground-floor main showroom, performance stage, dance floor, and primary social hub, named in honor of Lily Savage.',
		'img'  => "{$venue_img_dir}/lilys.webp",
	],
];

/* ══════════════════════════════════════════════════════════════════
   §3d  VENUE IMAGE UPLOAD  (idempotent — skips already-uploaded)
   ══════════════════════════════════════════════════════════════════ */

$venue_img_mapping  = (array) get_option( 'tbc_venue_images', [] );
$venue_img_changed  = false;

WP_CLI::log( '' );
WP_CLI::log( '→ Ensuring venue images are in media library…' );

foreach ( $venue_defs as $slot => $def ) {
	$prev_id = isset( $venue_img_mapping[ $slot ] ) ? (int) $venue_img_mapping[ $slot ] : 0;
	$post    = $prev_id ? get_post( $prev_id ) : null;

	if ( $post && 'attachment' === $post->post_type ) {
		WP_CLI::log( "  Venue slot {$slot} → already uploaded (ID {$prev_id})" );
	} elseif ( ! file_exists( $def['img'] ) ) {
		WP_CLI::warning( "  Venue slot {$slot} → source not found: {$def['img']}" );
	} else {
		$new_id                    = tbc_upload_venue_image( $def['img'], $slot );
		$venue_img_mapping[ $slot ] = $new_id;
		$venue_img_changed          = true;
		WP_CLI::success( sprintf(
			'  Slot %d → uploaded   ID %-6d  %s',
			$slot, $new_id, basename( $def['img'] )
		) );
	}
}

if ( $venue_img_changed ) {
	update_option( 'tbc_venue_images', $venue_img_mapping );
}

/* ══════════════════════════════════════════════════════════════════
   §3e  ENSURE VENUE CPT POSTS + ASSIGN IMAGES
   ══════════════════════════════════════════════════════════════════ */

WP_CLI::log( '' );
WP_CLI::log( '→ Ensuring Venue posts…' );

$venue_post_ids = [];

foreach ( $venue_defs as $slot => $def ) {
	$slug    = "venue-{$slot}";
	$found   = get_posts( [
		'post_type'   => 'tbc_venue',
		'post_status' => 'any',
		'name'        => $slug,
		'numberposts' => 1,
	] );
	$existing = $found[0] ?? null;

	if ( $existing ) {
		$venue_post_ids[ $slot ] = $existing->ID;
		wp_update_post( [ 'ID' => $existing->ID, 'post_title' => $def['name'] ] );
		WP_CLI::log( "  Slot {$slot} → updated (ID {$existing->ID}: {$def['name']})" );
	} else {
		$new_id = wp_insert_post( [
			'post_type'   => 'tbc_venue',
			'post_title'  => $def['name'],
			'post_name'   => $slug,
			'post_status' => 'publish',
		] );
		$venue_post_ids[ $slot ] = $new_id;
		WP_CLI::success( "  Slot {$slot} → created '{$def['name']}' (ID {$new_id})" );
	}

	$pid     = $venue_post_ids[ $slot ];
	$att_id  = isset( $venue_img_mapping[ $slot ] ) ? (int) $venue_img_mapping[ $slot ] : 0;
	update_post_meta( $pid, 'tbc_venue_description', $def['desc'] );
	update_post_meta( $pid, 'tbc_venue_image_ids', $att_id ? [ $att_id ] : [] );
}

// Helper: get tbc_venue post ID for a slot (0 if not found)
$venue_id = static function ( int $slot ) use ( $venue_post_ids ): int {
	return $venue_post_ids[ $slot ] ?? 0;
};

// Venue-hire block slots: SVG paths are indexed bottom→top (0=ground, 1=first, 2=roof)
$venue_slots = [
	[ 'venueId' => $venue_id(3) ], // index 0 (bottom) → Lily's Bar (ground floor)
	[ 'venueId' => $venue_id(2) ], // index 1 (middle) → Ms Shufflewick Bar (first floor)
	[ 'venueId' => $venue_id(1) ], // index 2 (top)    → Regina Fong Terrace (rooftop)
];

/* ══════════════════════════════════════════════════════════════════
   §4  BUILD Our Rooms frame array
   ══════════════════════════════════════════════════════════════════ */

$frames = [
	[ 'svgFile' => 'Frame 1.svg', 'roomId' => $room_id(1), 'wide' => false ],
	[ 'svgFile' => 'Frame 2.svg', 'roomId' => $room_id(2), 'wide' => false ],
	[ 'svgFile' => 'Frame 3.svg', 'roomId' => $room_id(3), 'wide' => false ],
	[ 'svgFile' => 'Frame 4.svg', 'roomId' => $room_id(4), 'wide' => false ],
	[ 'svgFile' => 'Frame 5.svg', 'roomId' => $room_id(5), 'wide' => false ],
	[ 'svgFile' => 'Frame 6.svg', 'roomId' => $room_id(6), 'wide' => true  ],
	[ 'svgFile' => 'Frame 7.svg', 'roomId' => $room_id(7), 'wide' => false ],
	[ 'svgFile' => 'Frame 8.svg', 'roomId' => $room_id(1), 'wide' => true  ],
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
	$blocks            = parse_blocks( $front_page->post_content );
	$patched           = false;
	$has_timeline      = false;
	$has_venue_hire    = false;

	foreach ( $blocks as &$block ) {
		if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/our-rooms' ) {
			$block['attrs']['frames'] = $frames;
			$patched = true;
		}
		if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/timeline' ) {
			$has_timeline = true;
			$block['attrs'] = $timeline_attrs;
			$patched = true;
		}
		if ( ( $block['blockName'] ?? '' ) === 'the-black-cap/venue-hire' ) {
			$has_venue_hire = true;
			$block['attrs']['slots'] = $venue_slots;
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

	// Insert Venue Hire block after Our Rooms if it doesn't exist yet.
	if ( ! $has_venue_hire ) {
		$rooms_pos = null;
		foreach ( $blocks as $idx => $blk ) {
			if ( ( $blk['blockName'] ?? '' ) === 'the-black-cap/our-rooms' ) {
				$rooms_pos = $idx;
				break;
			}
		}
		$vh_parsed = parse_blocks( $b( 'venue-hire', [ 'slots' => $venue_slots ] ) );
		if ( ! empty( $vh_parsed[0] ) ) {
			$insert_at = $rooms_pos !== null ? $rooms_pos + 1 : count( $blocks );
			array_splice( $blocks, $insert_at, 0, [ $vh_parsed[0] ] );
			$patched = true;
			WP_CLI::success( 'Inserted Venue Hire block after Our Rooms.' );
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

		$b( 'venue-hire', [ 'slots' => $venue_slots ] ),

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
