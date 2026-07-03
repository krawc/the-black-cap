<?php
/**
 * Timeline block — server-side render.
 *
 * $attributes['introText']  string
 * $attributes['timestamps'] array of { id, years, title, description, imageIds: int[] }
 */

$intro_text = $attributes['introText'] ?? '';
$timestamps = $attributes['timestamps'] ?? [];

// ── Build flat lightbox dataset in one pass ───────────────────────────
$entries       = [];
$lightbox_data = [];

foreach ( $timestamps as $ts ) {
	$years  = trim( $ts['years']        ?? '' );
	$title  = trim( $ts['title']        ?? '' );
	$desc   = trim( $ts['description']  ?? '' );
	$images = [];

	foreach ( (array) ( $ts['imageIds'] ?? [] ) as $img_id ) {
		$img_id   = (int) $img_id;
		$full_url = wp_get_attachment_image_url( $img_id, 'large' );
		if ( ! $full_url ) {
			$full_url = wp_get_attachment_image_url( $img_id, 'full' );
		}
		if ( ! $full_url ) continue;

		$thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' ) ?: $full_url;
		$alt       = trim( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ?: ( $title ?: $years );

		$lb_idx   = count( $lightbox_data );
		$images[] = [
			'thumb'  => $thumb_url,
			'alt'    => $alt,
			'lb_idx' => $lb_idx,
		];
		$lightbox_data[] = [
			'url'   => $full_url,
			'alt'   => $alt,
			'years' => $years,
			'title' => $title,
			'desc'  => $desc,
		];
	}

	$entries[] = compact( 'years', 'title', 'desc', 'images' );
}
?>
<section class="timelineSection" id="timeline">

	<?php if ( $intro_text ) : ?>
	<div class="timelineIntro">
		<p><?php echo wp_kses_post( nl2br( esc_html( $intro_text ) ) ); ?></p>
	</div>
	<?php endif; ?>

	<?php if ( $entries ) : ?>
	<div class="timelineScroll">
		<div class="timelineTrack">

			<?php foreach ( $entries as $entry ) : ?>
			<?php
				$has_images  = ! empty( $entry['images'] );
				$first_lb    = $has_images ? (int) $entry['images'][0]['lb_idx'] : null;

				// First sentence of the description for the card preview
				$first_para     = $entry['desc'] ? ( strstr( $entry['desc'], "\n\n", true ) ?: $entry['desc'] ) : '';
				preg_match( '/^.+?[.!?](?=\s|$)/su', $first_para, $m );
				$preview_desc = $m[0] ?? $first_para;
			?>
			<div class="timelineItem">

				<div class="timelineDot" aria-hidden="true"></div>

				<div class="timelineItem__card<?php echo $has_images ? ' timelineItem__card--open' : ''; ?>"
					<?php if ( $has_images ) : ?>
					data-tl-index="<?php echo $first_lb; ?>"
					aria-label="<?php echo esc_attr( __( 'View photos', 'the-black-cap' ) ); ?>"
					role="button"
					tabindex="0"
					<?php endif; ?>>

					<?php if ( $entry['years'] ) : ?>
					<span class="timelineItem__years"><?php echo esc_html( $entry['years'] ); ?></span>
					<?php endif; ?>

					<?php if ( $entry['title'] ) : ?>
					<h3 class="timelineItem__title"><?php echo esc_html( $entry['title'] ); ?></h3>
					<?php endif; ?>

					<?php if ( $preview_desc ) : ?>
					<p class="timelineItem__desc">
						<?php echo esc_html( $preview_desc ); ?>
						<?php if ( $has_images ) : ?>
						<span class="timelineItem__readMore"> Read More...</span>
						<?php endif; ?>
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
	<?php endif; ?>

	<?php if ( $lightbox_data ) : ?>
	<script type="application/json" class="tbc-timeline-data">
	<?php echo json_encode( $lightbox_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>
	</script>
	<?php endif; ?>

</section>
