<?php
defined( 'ABSPATH' ) || exit;

/**
 * Step-based import runner. Each public run_step() call handles one import
 * step and returns structured log data. Safe to call multiple times (idempotent).
 *
 * Steps run in order; the client drives sequencing via AJAX.
 */
class TBC_Setup_Runner {

	const STEPS = [
		'room_cdn_images',
		'room_cpt_posts',
		'timeline_images',
		'venue_images',
		'venue_cpt_posts',
		'front_page',
		'nav_menus',
		'api_defaults',
	];

	/** @var string[] */
	private array $logs = [];

	/** 'production' | 'staging' — set at the start of each run_step() call */
	private string $mode = 'production';

	/* ─────────────────────────────────────────────────────────────
	   Public API
	   ───────────────────────────────────────────────────────────── */

	/**
	 * Run a single step. Returns:
	 *   logs      => string[]  — log lines produced this step
	 *   error     => string    — non-empty if a fatal error occurred
	 *   next_step => string|'' — name of next step, or '' if done
	 *   page_url  => string    — set by the front_page step in staging mode
	 */
	public function run_step( string $step, string $mode = 'production' ): array {
		$this->logs = [];
		$this->mode = ( $mode === 'staging' ) ? 'staging' : 'production';

		if ( ! in_array( $step, self::STEPS, true ) ) {
			return $this->result( 'Unknown step: ' . $step );
		}

		// Ensure WP upload helpers are available.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Generous time limit per step — CDN downloads can be slow.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}

		try {
			$method = 'step_' . $step;
			$this->$method();
		} catch ( Throwable $e ) {
			return $this->result( $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
		}

		$idx       = array_search( $step, self::STEPS, true );
		$next      = self::STEPS[ $idx + 1 ] ?? '';

		return $this->result( '', $next );
	}

	/* ─────────────────────────────────────────────────────────────
	   Step implementations
	   ───────────────────────────────────────────────────────────── */

	/** §1  Sideload room images from Mews CDN */
	private function step_room_cdn_images(): void {
		$room_defs = $this->room_definitions();
		$cdn_map   = (array) get_option( 'tbc_room_cdn_images', [] );
		$changed   = false;

		$this->log( '→ Sideloading room images from CDN…' );

		foreach ( $room_defs as $slot => $def ) {
			foreach ( $def['imgs'] as $i => $url ) {
				if ( isset( $cdn_map[ $url ] ) ) {
					$this->log( "  Already imported: room {$slot} img " . ( $i + 1 ) . " → ID {$cdn_map[$url]}" );
					continue;
				}

				try {
					$tmp = download_url( $url );
					if ( is_wp_error( $tmp ) ) {
						$this->log( '  [warn] Download failed: ' . $url . ' — ' . $tmp->get_error_message() );
						continue;
					}

					$mime    = wp_get_image_mime( $tmp ) ?: 'image/jpeg';
					$ext_map = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif' ];
					$ext     = $ext_map[ $mime ] ?? 'jpg';

					$att_id = media_handle_sideload(
						[ 'name' => "room-{$slot}-" . ( $i + 1 ) . ".{$ext}", 'tmp_name' => $tmp ],
						0,
						null
					);

					if ( is_wp_error( $att_id ) ) {
						@unlink( $tmp );
						$this->log( '  [warn] Import failed: ' . $url . ' — ' . $att_id->get_error_message() );
					} else {
						$cdn_map[ $url ] = (int) $att_id;
						$changed         = true;
						$this->log( "  [ok] Imported room {$slot} img " . ( $i + 1 ) . " → ID {$att_id}" );
					}
				} catch ( Throwable $e ) {
					$this->log( '  [warn] Exception for ' . $url . ': ' . $e->getMessage() );
				}
			}
		}

		if ( $changed ) {
			update_option( 'tbc_room_cdn_images', $cdn_map );
		}
		$this->log( '  Done.' );
	}

	/** §2  Create / update Room CPT posts */
	private function step_room_cpt_posts(): void {
		$room_defs = $this->room_definitions();
		$cdn_map   = (array) get_option( 'tbc_room_cdn_images', [] );

		$this->log( '→ Ensuring Room posts…' );

		foreach ( $room_defs as $slot => $def ) {
			try {
				$slug  = "room-{$slot}";
				$found = get_posts( [ 'post_type' => 'tbc_room', 'post_status' => 'any', 'name' => $slug, 'numberposts' => 1 ] );
				$existing = $found[0] ?? null;

				if ( $existing ) {
					wp_update_post( [ 'ID' => $existing->ID, 'post_title' => $def['name'] ] );
					$pid = $existing->ID;
					$this->log( "  Slot {$slot} → updated (ID {$pid}: {$def['name']})" );
				} else {
					$pid = wp_insert_post( [
						'post_type'   => 'tbc_room',
						'post_title'  => $def['name'],
						'post_name'   => $slug,
						'post_status' => 'publish',
					] );
					if ( is_wp_error( $pid ) ) {
						$this->log( "  [warn] Slot {$slot} → could not create: " . $pid->get_error_message() );
						continue;
					}
					$this->log( "  [ok] Slot {$slot} → created '{$def['name']}' (ID {$pid})" );
				}

				update_post_meta( $pid, 'tbc_room_description', $def['desc'] );

				$att_ids = array_values( array_filter( array_map(
					static fn( string $u ): int => $cdn_map[ $u ] ?? 0,
					$def['imgs']
				) ) );
				update_post_meta( $pid, 'tbc_room_image_ids', $att_ids );
			} catch ( Throwable $e ) {
				$this->log( "  [warn] Slot {$slot} exception: " . $e->getMessage() );
			}
		}
		$this->log( '  Done.' );
	}

	/** §3  Upload timeline images from plugin assets */
	private function step_timeline_images(): void {
		$img_dir = TBC_PLUGIN_DIR . 'assets/img/timeline';
		$mapping = (array) get_option( 'tbc_timeline_images', [] );
		$changed = false;

		$this->log( '→ Ensuring timeline images are in media library…' );

		for ( $slot = 1; $slot <= 5; $slot++ ) {
			try {
				$prev_id = isset( $mapping[ $slot ] ) ? (int) $mapping[ $slot ] : 0;
				$post    = $prev_id ? get_post( $prev_id ) : null;

				if ( $post && 'attachment' === $post->post_type ) {
					$this->log( "  Slot {$slot} → already uploaded (ID {$prev_id})" );
					continue;
				}

				$src = "{$img_dir}/{$slot}.webp";
				if ( ! file_exists( $src ) ) {
					$this->log( "  [warn] Slot {$slot} → source not found: {$src}" );
					continue;
				}

				$new_id = $this->upload_image( $src, 'tbc-timeline', "timeline-{$slot}", "Timeline Image {$slot}" );
				$mapping[ $slot ] = $new_id;
				$changed          = true;
				$this->log( "  [ok] Slot {$slot} → uploaded ID {$new_id} (" . basename( $src ) . ')' );
			} catch ( Throwable $e ) {
				$this->log( "  [warn] Slot {$slot} exception: " . $e->getMessage() );
			}
		}

		if ( $changed ) {
			update_option( 'tbc_timeline_images', $mapping );
		}
		$this->log( '  Done.' );
	}

	/** §4  Upload venue images from plugin assets */
	private function step_venue_images(): void {
		$venue_defs = $this->venue_definitions();
		$mapping    = (array) get_option( 'tbc_venue_images', [] );
		$changed    = false;

		$this->log( '→ Ensuring venue images are in media library…' );

		foreach ( $venue_defs as $slot => $def ) {
			try {
				$prev_id = isset( $mapping[ $slot ] ) ? (int) $mapping[ $slot ] : 0;
				$post    = $prev_id ? get_post( $prev_id ) : null;

				if ( $post && 'attachment' === $post->post_type ) {
					$this->log( "  Slot {$slot} → already uploaded (ID {$prev_id})" );
					continue;
				}

				if ( ! file_exists( $def['img'] ) ) {
					$this->log( "  [warn] Slot {$slot} → source not found: {$def['img']}" );
					continue;
				}

				$new_id = $this->upload_image( $def['img'], 'tbc-venues', "venue-img-{$slot}", "Venue Image {$slot}" );
				$mapping[ $slot ] = $new_id;
				$changed          = true;
				$this->log( "  [ok] Slot {$slot} → uploaded ID {$new_id} (" . basename( $def['img'] ) . ')' );
			} catch ( Throwable $e ) {
				$this->log( "  [warn] Slot {$slot} exception: " . $e->getMessage() );
			}
		}

		if ( $changed ) {
			update_option( 'tbc_venue_images', $mapping );
		}
		$this->log( '  Done.' );
	}

	/** §5  Create / update Venue CPT posts */
	private function step_venue_cpt_posts(): void {
		$venue_defs  = $this->venue_definitions();
		$img_mapping = (array) get_option( 'tbc_venue_images', [] );

		$this->log( '→ Ensuring Venue posts…' );

		foreach ( $venue_defs as $slot => $def ) {
			try {
				$slug     = "venue-{$slot}";
				$found    = get_posts( [ 'post_type' => 'tbc_venue', 'post_status' => 'any', 'name' => $slug, 'numberposts' => 1 ] );
				$existing = $found[0] ?? null;

				if ( $existing ) {
					wp_update_post( [ 'ID' => $existing->ID, 'post_title' => $def['name'] ] );
					$pid = $existing->ID;
					$this->log( "  Slot {$slot} → updated (ID {$pid}: {$def['name']})" );
				} else {
					$pid = wp_insert_post( [
						'post_type'   => 'tbc_venue',
						'post_title'  => $def['name'],
						'post_name'   => $slug,
						'post_status' => 'publish',
					] );
					if ( is_wp_error( $pid ) ) {
						$this->log( "  [warn] Slot {$slot} → could not create: " . $pid->get_error_message() );
						continue;
					}
					$this->log( "  [ok] Slot {$slot} → created '{$def['name']}' (ID {$pid})" );
				}

				$att_id = isset( $img_mapping[ $slot ] ) ? (int) $img_mapping[ $slot ] : 0;
				update_post_meta( $pid, 'tbc_venue_description', $def['desc'] );
				update_post_meta( $pid, 'tbc_venue_image_ids', $att_id ? [ $att_id ] : [] );
			} catch ( Throwable $e ) {
				$this->log( "  [warn] Slot {$slot} exception: " . $e->getMessage() );
			}
		}
		$this->log( '  Done.' );
	}

	/** §6  Create / update the target page with all blocks */
	private function step_front_page(): void {
		$label = $this->mode === 'staging' ? 'staging page' : 'front page';
		$this->log( "→ Setting up {$label}…" );

		try {
			$room_ids  = $this->get_cpt_post_ids( 'tbc_room',  $this->room_definitions(),  'room' );
			$venue_ids = $this->get_cpt_post_ids( 'tbc_venue', $this->venue_definitions(), 'venue' );

			$room_id  = static fn( int $s ): int => $room_ids[ $s ]  ?? 0;
			$venue_id = static fn( int $s ): int => $venue_ids[ $s ] ?? 0;

			$frames = [
				[ 'svgFile' => 'Frame 1.svg', 'roomId' => $room_id(1), 'wide' => false ], // Adrella's
				[ 'svgFile' => 'Frame 2.svg', 'roomId' => $room_id(2), 'wide' => false ],
				[ 'svgFile' => 'Frame 3.svg', 'roomId' => $room_id(3), 'wide' => false ],
				[ 'svgFile' => 'Frame 4.svg', 'roomId' => $room_id(4), 'wide' => false ],
				[ 'svgFile' => 'Frame 7.svg', 'roomId' => $room_id(5), 'wide' => false ], // Frame 5↔7 swap
				[ 'svgFile' => 'Frame 6.svg', 'roomId' => $room_id(1), 'wide' => true  ], // Adrella's
				[ 'svgFile' => 'Frame 5.svg', 'roomId' => $room_id(7), 'wide' => false ], // Frame 7↔5 swap
				[ 'svgFile' => 'Frame 8.svg', 'roomId' => $room_id(6), 'wide' => true  ], // Imperial Suite wide
			];

			$venue_slots = [
				[ 'venueId' => $venue_id(3) ], // index 0 (bottom) → Lily's Bar
				[ 'venueId' => $venue_id(2) ], // index 1 (middle) → Ms Shufflewick Bar
				[ 'venueId' => $venue_id(1) ], // index 2 (top)    → Regina Fong Terrace
			];

			$story_img = static fn( int $n ): string => TBC_PLUGIN_URL . "/assets/img/story/{$n}.webp";

			$b = static function ( string $name, array $attrs ): string {
				return '<!-- wp:the-black-cap/' . $name . ' '
					. json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
					. ' /-->';
			};

			// Timeline images were uploaded in step_timeline_images(); read the mapping now.
			$tl_map   = (array) get_option( 'tbc_timeline_images', [] );
			$tl_img   = static fn( int $slot ): ?int => isset( $tl_map[ $slot ] ) ? (int) $tl_map[ $slot ] : null;
			$tl_ids   = static fn( int $slot ): array => array_values( array_filter( [ $tl_img( $slot ) ] ) );

			$timeline_attrs = [
				'introText'  => "For more than 250 years, The Black Cap has been at the heart of Camden. Known as one of London's most historic pubs and a cornerstone of LGBTQ+ culture, it has hosted legendary performers, launched careers and offered generations a safe and celebratory space.\n\nNow, at long last, the Cap is OPEN once more. It's been saved not just by law, but by love, by the thousands who stood up for it, sang for it, and believed in it. The Cap has always been more than bricks and mortar. It's drag and glitter, it's protest and power, it's the place where outsiders became insiders.",
				'timestamps' => [
					[
						'id'          => 'tl-1',
						'years'       => '1751–1960',
						'title'       => 'WITCHES & THE START OF SOMETHING SPECIAL',
						'description' => "The Black Cap's story begins way back in 1751, when it first opened as the Mother Black Cap. Local Camden folklore says it was named after a witch – \"Mother Damnable\" – who was said to curse anyone who crossed her. By 1781, the pub had moved to its current spot on Camden High Street, and in 1889 it was rebuilt into the Victorian building you see today. If you look up, you'll spot her: a stone bust of Mother Black Cap, still watching over the door like she has for over a century.",
						'imageIds'    => $tl_ids(1),
					],
					[
						'id'          => 'tl-2',
						'years'       => '1960s',
						'title'       => 'FROM LOCAL TO QUEER HEAVEN',
						'description' => "In the 1960s, long before it was legal to be openly gay in this country, the Black Cap became something more than a pub. It became a safe meeting place. By the mid-60s it had already built a reputation as one of London's very first \"gay pubs\" and by the 70s it had a new title too: the Palladium of Drag.\n\nLegends of British drag like Danny La Rue, Hinge & Bracket, and above all Mrs Shufflewick made this their stage. Shufflewick's Sunday shows were infamous – packed with everyone from local regulars to big names like Barry Humphries.",
						'imageIds'    => $tl_ids(2),
					],
					[
						'id'          => 'tl-3',
						'years'       => '1970s–1980s',
						'title'       => 'THE GOLDEN YEARS',
						'description' => "Through the 70s and 80s the Cap wasn't just a pub – it was a lifeline. You came here to laugh with a drag queen tearing the house down, to flirt, to dance, to cry on someone's shoulder. For many, it was the first place they truly felt at home.\n\nActs like Regina Fong brought the house down night after night, with a fanbase who called themselves the \"Fongettes.\" The Cap also gave space to community groups: from trans support meetups to London Gay Symphonic Winds rehearsals. It wasn't just entertainment, it was solidarity.\n\nBy the 2000s, the Cap was still buzzing, with nights like The Meth Lab mixing drag, cabaret and surreal performance. Stars of RuPaul's Drag Race – Bianca Del Rio, Trixie Mattel, Raja, Adore Delano – all performed on the stage.",
						'imageIds'    => $tl_ids(3),
					],
					[
						'id'          => 'tl-4',
						'years'       => '1990s–2010s',
						'title'       => 'A VENUE WITH COMMUNITY WEIGHT',
						'description' => "The Black Cap's importance has never been limited to nightlife. For many, it represented something rare: a public place where being openly LGBTQ+ felt normal, safe, and shared. Former staff and regulars have described it as a welcoming, mixed crowd across ages – a place to meet, talk, laugh, and feel part of something bigger than a night out. That community role was formally recognised when Camden Council granted Asset of Community Value (ACV) status – a protection designed to acknowledge places that contribute to local social and cultural life.\n\nIn more recent years, community work and campaigning continued beyond the building itself. Partnerships and grassroots groups helped keep the spirit of The Black Cap alive through organised meet-ups and advocacy driven by the belief that London needs queer spaces that aren't disposable.",
						'imageIds'    => $tl_ids(4),
					],
					[
						'id'          => 'tl-5',
						'years'       => '2020s',
						'title'       => 'A NEW CHAPTER',
						'description' => "Now, at long last, the Cap is reopening. It's been saved not just by law, but by love – by the thousands who stood up for it, sang for it, and believed in it.\n\nThe Black Cap returns with the same rebellious spirit, inclusive heart, and unforgettable nights that made it a cornerstone of queer culture in London. Join us as we celebrate our past, and raise a glass to the future.",
						'imageIds'    => $tl_ids(5),
					],
				],
			];

			// Full block content — identical for both modes.
			$full_content = implode( "\n\n", [
				$b( 'hero-nav', [
					'menuSlug' => 'tbc-nav',
					'address'  => '171 Camden High Street, London NW1 7JY',
					'phone'    => '020 7428 2721',
					'email'    => 'Sassy@blackcapcamden.co.uk',
				] ),
				$b( 'whats-on', [ 'eventIds' => '', 'limit' => 8 ] ),
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
				$b( 'drink-menu', [ 'sections' => $this->drink_menu_sections() ] ),
				$b( 'our-rooms',  [ 'frames'   => $frames ] ),
				$b( 'venue-hire', [ 'slots'    => $venue_slots ] ),
			] );

			if ( $this->mode === 'staging' ) {
				$this->apply_staging_page( $full_content, $b );
			} else {
				$this->apply_production_page( $full_content );
			}

		} catch ( Throwable $e ) {
			$this->log( '  [error] ' . $e->getMessage() );
			throw $e;
		}
		$this->log( '  Done.' );
	}

	/**
	 * Production mode: create or update the WordPress front page.
	 * Always writes the full canonical content — no surgical patching.
	 */
	private function apply_production_page( string $full_content ): void {
		$front_page_id = (int) get_option( 'page_on_front' );
		$front_page    = $front_page_id ? get_post( $front_page_id ) : null;

		if ( $front_page && 'page' === $front_page->post_type ) {
			wp_update_post( [ 'ID' => $front_page_id, 'post_content' => $full_content ] );
			update_post_meta( $front_page_id, '_wp_page_template', 'tbc-fullscreen' );
			$this->log( "  [ok] Updated front page (ID {$front_page_id}) with fresh content." );
		} else {
			$page_id = wp_insert_post( [
				'post_title'     => 'Home',
				'post_name'      => 'home',
				'post_content'   => $full_content,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			], true );

			if ( is_wp_error( $page_id ) ) {
				throw new \RuntimeException( 'Could not create page: ' . $page_id->get_error_message() );
			}

			update_post_meta( $page_id, '_wp_page_template', 'tbc-fullscreen' );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $page_id );
			$this->log( "  [ok] Created front page (ID {$page_id}) and set as static homepage." );
		}

		update_option( 'show_on_front', 'page' );
		if ( ! (int) get_option( 'page_on_front' ) ) {
			$p = get_page_by_path( 'home' );
			if ( $p ) update_option( 'page_on_front', $p->ID );
		}
	}

	/**
	 * Staging mode: create or update a separate preview page (slug: tbc-staging).
	 * Never touches show_on_front / page_on_front.
	 * Sets $this->page_url so the UI can show a direct link.
	 */
	private function apply_staging_page( string $full_content, callable $b ): void {
		$staging_id = (int) get_option( 'tbc_staging_page_id' );
		$existing   = $staging_id ? get_post( $staging_id ) : null;

		// Also look up by slug in case the option was lost.
		if ( ! $existing || 'page' !== $existing->post_type ) {
			$found    = get_posts( [ 'post_type' => 'page', 'post_status' => 'any', 'name' => 'tbc-staging', 'numberposts' => 1 ] );
			$existing = $found[0] ?? null;
		}

		if ( $existing ) {
			// Update the existing staging page with fresh block content.
			wp_update_post( [ 'ID' => $existing->ID, 'post_content' => $full_content ] );
			update_post_meta( $existing->ID, '_wp_page_template', 'tbc-fullscreen' );
			update_option( 'tbc_staging_page_id', $existing->ID );
			$this->page_url = get_permalink( $existing->ID );
			$this->log( "  [ok] Updated staging page (ID {$existing->ID})." );
		} else {
			$page_id = wp_insert_post( [
				'post_title'     => 'Black Cap — Staging',
				'post_name'      => 'tbc-staging',
				'post_content'   => $full_content,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			], true );

			if ( is_wp_error( $page_id ) ) {
				throw new \RuntimeException( 'Could not create staging page: ' . $page_id->get_error_message() );
			}

			update_post_meta( $page_id, '_wp_page_template', 'tbc-fullscreen' );
			update_option( 'tbc_staging_page_id', $page_id );
			$this->page_url = get_permalink( $page_id );
			$this->log( "  [ok] Created staging page (ID {$page_id})." );
		}

		$this->log( "  Preview URL: {$this->page_url}" );
	}

	/** §7  Create nav menus */
	private function step_nav_menus(): void {
		$this->log( '→ Setting up navigation menus…' );

		$setup_menu = function ( string $name, string $location, array $items ): void {
			try {
				$existing = wp_get_nav_menu_object( $name );

				if ( $existing ) {
					$this->log( "  Menu '{$name}' already exists — skipping creation." );
					$mid = $existing->term_id;
				} else {
					$mid = wp_create_nav_menu( $name );
					if ( is_wp_error( $mid ) ) {
						$this->log( "  [warn] Could not create menu '{$name}': " . $mid->get_error_message() );
						return;
					}
					foreach ( $items as [ $title, $url ] ) {
						wp_update_nav_menu_item( $mid, 0, [
							'menu-item-title'  => $title,
							'menu-item-url'    => $url,
							'menu-item-status' => 'publish',
							'menu-item-type'   => 'custom',
						] );
					}
					$this->log( "  [ok] Created menu '{$name}' (" . count( $items ) . ' items).' );
				}

				$locs              = get_theme_mod( 'nav_menu_locations', [] );
				$locs[ $location ] = $mid;
				set_theme_mod( 'nav_menu_locations', $locs );
			} catch ( Throwable $e ) {
				$this->log( "  [warn] Menu '{$name}' exception: " . $e->getMessage() );
			}
		};

		$setup_menu( 'TBC Nav', 'tbc-nav', [
			[ 'The Cap Story', '#story'      ],
			[ "What's On",     '#whats-on'   ],
			[ 'Menu',          '#menu'        ],
			[ 'Our Rooms',     '#our-rooms'   ],
			[ 'Venue Hire',    '#venue-hire'  ],
		] );

		$setup_menu( 'Footer Links', 'footer', [
			[ 'Privacy Policy',     '/privacy'       ],
			[ 'Cookie Policy',      '/cookies'       ],
			[ 'Terms & Conditions', '/terms'         ],
			[ 'Accessibility',      '/accessibility' ],
		] );

		$this->log( '  Done.' );
	}

	/** §8  Seed API defaults */
	private function step_api_defaults(): void {
		$this->log( '→ Setting API defaults…' );

		try {
			if ( ! get_option( 'tbc_eventbrite_org_id' ) ) {
				update_option( 'tbc_eventbrite_org_id', '3005226258349' );
				$this->log( '  [ok] Set Eventbrite org ID → 3005226258349. Add your API token in Settings → Black Cap.' );
			} else {
				$this->log( '  Eventbrite org ID already set — skipping.' );
			}
		} catch ( Throwable $e ) {
			$this->log( '  [warn] ' . $e->getMessage() );
		}

		$this->log( '  Done.' );
	}

	/* ─────────────────────────────────────────────────────────────
	   Data definitions (single source of truth)
	   ───────────────────────────────────────────────────────────── */

	private function room_definitions(): array {
		$paras = static fn( string ...$ps ): string => implode( "\n\n", $ps );

		return [
			1 => [
				'name' => "Adrella's Dressing Room",
				'desc' => $paras(
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
				'desc' => $paras(
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
				'desc' => $paras(
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
				'desc' => $paras(
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
				'desc' => $paras(
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
				'desc' => $paras(
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
	}

	private function venue_definitions(): array {
		$img_dir = TBC_PLUGIN_DIR . 'assets/img/venues';
		return [
			1 => [
				'name' => 'Regina Fong Terrace',
				'desc' => 'The outdoor rooftop space located at the rear of the first floor, accessed directly through the Shufflewick Bar.',
				'img'  => "{$img_dir}/terrace.webp",
			],
			2 => [
				'name' => 'Shufflewick Bar',
				'desc' => 'The first-floor bar area with seating booths, named after the pioneering 1950s drag performer.',
				'img'  => "{$img_dir}/shufflewick.webp",
			],
			3 => [
				'name' => "Lily's Bar",
				'desc' => 'The ground-floor main showroom, performance stage, dance floor, and primary social hub, named in honor of Lily Savage.',
				'img'  => "{$img_dir}/lilys.webp",
			],
		];
	}

	private function drink_menu_sections(): array {
		return [
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
		];
	}

	/* ─────────────────────────────────────────────────────────────
	   Helpers
	   ───────────────────────────────────────────────────────────── */

	/**
	 * Copy a local file into the WP uploads folder and register it as an attachment.
	 */
	private function upload_image( string $src, string $sub_dir, string $slug, string $title ): int {
		$upload = wp_upload_dir();
		$dir    = $upload['basedir'] . '/' . $sub_dir;
		wp_mkdir_p( $dir );

		$ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
		$name = "{$slug}.{$ext}";
		$dest = "{$dir}/{$name}";

		if ( ! copy( $src, $dest ) ) {
			throw new \RuntimeException( "Failed to copy {$src} to {$dest}" );
		}

		$mime = wp_check_filetype( $dest );
		$id   = wp_insert_attachment( [
			'guid'           => $upload['baseurl'] . "/{$sub_dir}/{$name}",
			'post_title'     => $title,
			'post_name'      => $slug,
			'post_mime_type' => $mime['type'],
			'post_status'    => 'inherit',
			'post_content'   => '',
		], $dest, 0 );

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
		return (int) $id;
	}

	/**
	 * Look up post IDs for CPT posts by their "type-slot" slug pattern.
	 */
	private function get_cpt_post_ids( string $post_type, array $defs, string $prefix ): array {
		$ids = [];
		foreach ( array_keys( $defs ) as $slot ) {
			$found = get_posts( [
				'post_type'   => $post_type,
				'post_status' => 'any',
				'name'        => "{$prefix}-{$slot}",
				'numberposts' => 1,
			] );
			if ( $found ) {
				$ids[ $slot ] = $found[0]->ID;
			}
		}
		return $ids;
	}

	private function log( string $line ): void {
		$this->logs[] = $line;
	}

	/** @var string URL to surface to the UI after the front_page step (staging mode) */
	private string $page_url = '';

	private function result( string $error = '', string $next_step = '' ): array {
		return [
			'logs'      => $this->logs,
			'error'     => $error,
			'next_step' => $next_step,
			'page_url'  => $this->page_url,
		];
	}
}
