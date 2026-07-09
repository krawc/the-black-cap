<?php
/**
 * Timeline block — server-side render.
 *
 * Content comes from block attributes (editable in the block editor).
 * Falls back to hardcoded defaults so the block is self-sufficient on a
 * fresh install before the editor is used.
 *
 * Image IDs are stored as `imageIds` arrays on each timestamp entry.
 * The setup runner pre-populates them from tbc_timeline_images.
 */

$img_map = (array) get_option( 'tbc_timeline_images', [] );
$img_id  = static fn( int $slot ): int => isset( $img_map[ $slot ] ) ? (int) $img_map[ $slot ] : 0;

// ── Content: prefer block attributes, fall back to hardcoded ─────────────
$intro_text     = ! empty( $attributes['introText'] )  ? (string) $attributes['introText']  : null;
$timestamps_raw = ! empty( $attributes['timestamps'] ) ? (array)  $attributes['timestamps'] : null;

if ( null === $intro_text ) {
	$intro_text = "For more than 250 years, The Black Cap has been at the heart of Camden. Known as one of London's most historic pubs and a cornerstone of LGBTQ+ culture, it has hosted legendary performers, launched careers and offered generations a safe and celebratory space.\n\nNow, at long last, the Cap is OPEN once more. It's been saved not just by law, but by love, by the thousands who stood up for it, sang for it, and believed in it. The Cap has always been more than bricks and mortar. It's drag and glitter, it's protest and power, it's the place where outsiders became insiders.";
}

if ( null === $timestamps_raw ) {
	$timestamps_raw = [
		[
			'years'       => '1751–1960',
			'title'       => 'WITCHES & THE START OF SOMETHING SPECIAL',
			'description' => "The Black Cap's story begins way back in 1751, when it first opened as the Mother Black Cap. Local Camden folklore says it was named after a witch – \"Mother Damnable\" – who was said to curse anyone who crossed her. By 1781, the pub had moved to its current spot on Camden High Street, and in 1889 it was rebuilt into the Victorian building you see today. If you look up, you'll spot her: a stone bust of Mother Black Cap, still watching over the door like she has for over a century.",
			'imageIds'    => array_values( array_filter( [ $img_id(1) ] ) ),
		],
		[
			'years'       => '1960s',
			'title'       => 'FROM LOCAL TO QUEER HEAVEN',
			'description' => "In the 1960s, long before it was legal to be openly gay in this country, the Black Cap became something more than a pub. It became a safe meeting place. By the mid-60s it had already built a reputation as one of London's very first \"gay pubs\" and by the 70s it had a new title too: the Palladium of Drag.\n\nLegends of British drag like Danny La Rue, Hinge & Bracket, and above all Mrs Shufflewick made this their stage. Shufflewick's Sunday shows were infamous – packed with everyone from local regulars to big names like Barry Humphries.",
			'imageIds'    => array_values( array_filter( [ $img_id(2) ] ) ),
		],
		[
			'years'       => '1970s–1980s',
			'title'       => 'THE GOLDEN YEARS',
			'description' => "Through the 70s and 80s the Cap wasn't just a pub – it was a lifeline. You came here to laugh with a drag queen tearing the house down, to flirt, to dance, to cry on someone's shoulder. For many, it was the first place they truly felt at home.\n\nActs like Regina Fong brought the house down night after night, with a fanbase who called themselves the \"Fongettes.\" The Cap also gave space to community groups: from trans support meetups to London Gay Symphonic Winds rehearsals. It wasn't just entertainment, it was solidarity.\n\nBy the 2000s, the Cap was still buzzing, with nights like The Meth Lab mixing drag, cabaret and surreal performance. Stars of RuPaul's Drag Race – Bianca Del Rio, Trixie Mattel, Raja, Adore Delano – all performed on the stage.",
			'imageIds'    => array_values( array_filter( [ $img_id(3) ] ) ),
		],
		[
			'years'       => '1990s–2010s',
			'title'       => 'A VENUE WITH COMMUNITY WEIGHT',
			'description' => "The Black Cap's importance has never been limited to nightlife. For many, it represented something rare: a public place where being openly LGBTQ+ felt normal, safe, and shared. Former staff and regulars have described it as a welcoming, mixed crowd across ages – a place to meet, talk, laugh, and feel part of something bigger than a night out. That community role was formally recognised when Camden Council granted Asset of Community Value (ACV) status – a protection designed to acknowledge places that contribute to local social and cultural life.\n\nIn more recent years, community work and campaigning continued beyond the building itself. Partnerships and grassroots groups helped keep the spirit of The Black Cap alive through organised meet-ups and advocacy driven by the belief that London needs queer spaces that aren't disposable.",
			'imageIds'    => array_values( array_filter( [ $img_id(4) ] ) ),
		],
		[
			'years'       => '2020s',
			'title'       => 'A NEW CHAPTER',
			'description' => "Now, at long last, the Cap is reopening. It's been saved not just by law, but by love – by the thousands who stood up for it, sang for it, and believed in it.\n\nThe Black Cap returns with the same rebellious spirit, inclusive heart, and unforgettable nights that made it a cornerstone of queer culture in London. Join us as we celebrate our past, and raise a glass to the future.",
			'imageIds'    => array_values( array_filter( [ $img_id(5) ] ) ),
		],
	];
}

// ── Build entries and lightbox data ──────────────────────────────────────
$entries       = [];
$lightbox_data = [];

foreach ( $timestamps_raw as $ts ) {
	$years  = (string) ( $ts['years']       ?? '' );
	$title  = (string) ( $ts['title']       ?? '' );
	$desc   = (string) ( $ts['description'] ?? '' );
	$images = [];

	foreach ( (array) ( $ts['imageIds'] ?? [] ) as $id ) {
		$id = (int) $id;
		if ( ! $id ) continue;
		$full_url = wp_get_attachment_image_url( $id, 'large' ) ?: wp_get_attachment_image_url( $id, 'full' );
		if ( ! $full_url ) continue;
		$thumb_url = wp_get_attachment_image_url( $id, 'thumbnail' ) ?: $full_url;
		$alt       = trim( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ?: $title;
		$lb_idx    = count( $lightbox_data );
		$images[]        = [ 'thumb' => $thumb_url, 'alt' => $alt, 'lb_idx' => $lb_idx ];
		$lightbox_data[] = [ 'url' => $full_url, 'alt' => $alt, 'years' => $years, 'title' => $title, 'desc' => $desc ];
	}

	$entries[] = compact( 'years', 'title', 'desc', 'images' );
}
?>
<section class="timelineSection" id="timeline">

	<div class="timelineIntro">
		<p><?php echo wp_kses_post( nl2br( esc_html( $intro_text ) ) ); ?></p>
	</div>

	<div class="timelineScroll">
		<div class="timelineTrack">

			<?php foreach ( $entries as $entry ) : ?>
			<?php
				$has_images = ! empty( $entry['images'] );
				$first_lb   = $has_images ? (int) $entry['images'][0]['lb_idx'] : null;

				$first_para   = $entry['desc'] ? ( strstr( $entry['desc'], "\n\n", true ) ?: $entry['desc'] ) : '';
				preg_match( '/^.+?[.!?](?=\s|$)/su', $first_para, $m );
				$preview_desc = $m[0] ?? $first_para;
			?>
			<div class="timelineItem">

				<div class="timelineDot" aria-hidden="true"></div>

				<div class="timelineItem__card<?php echo $has_images ? ' timelineItem__card--open' : ''; ?>"
					<?php if ( $has_images ) : ?>
					data-tl-index="<?php echo $first_lb; ?>"
					aria-label="<?php esc_attr_e( 'View photos', 'the-black-cap' ); ?>"
					role="button"
					tabindex="0"
					<?php endif; ?>>

					<span class="timelineItem__years"><?php echo esc_html( $entry['years'] ); ?></span>
					<h3 class="timelineItem__title"><?php echo esc_html( $entry['title'] ); ?></h3>

					<?php if ( $preview_desc ) : ?>
					<p class="timelineItem__desc">
						<?php echo esc_html( $preview_desc ); ?>
						<?php if ( $has_images ) : ?><span class="timelineItem__readMore"> Read More...</span><?php endif; ?>
					</p>
					<?php endif; ?>

					<?php if ( $has_images ) : ?>
					<div class="timelineItem__images">
						<?php foreach ( $entry['images'] as $img ) : ?>
						<button
							class="timelineThumb"
							data-index="<?php echo (int) $img['lb_idx']; ?>"
							type="button"
							aria-label="<?php echo esc_attr( sprintf( __( 'View photo: %s', 'the-black-cap' ), $img['alt'] ) ); ?>"
						>
							<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy">
						</button>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

				</div>

			</div>
			<?php endforeach; ?>

		</div>
	</div>

	<?php if ( $lightbox_data ) : ?>
	<script type="application/json" class="tbc-timeline-data">
	<?php echo wp_json_encode( $lightbox_data ); ?>
	</script>
	<?php endif; ?>

</section>
