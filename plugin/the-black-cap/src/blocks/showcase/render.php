<?php
/**
 * Photo Showcase block — infinite auto-scrolling strip.
 */

$image_ids = $attributes['imageIds'] ?? [];
if ( empty( $image_ids ) ) return;

$urls = [];
foreach ( $image_ids as $id ) {
	$url = wp_get_attachment_image_url( (int) $id, 'large' );
	if ( $url ) $urls[] = $url;
}
if ( empty( $urls ) ) return;
?>
<section class="showcase">
	<div class="showcaseTrack" aria-hidden="true">
		<div class="showcaseInner">
			<?php /*
			 * loading="lazy" deliberately omitted: these images are only ever
			 * brought into view by a CSS transform animation (see .showcaseInner
			 * in frontend.css), never by real scrolling. Safari/WebKit's native
			 * lazy-load only re-checks an image's intersection on scroll/resize,
			 * not on transform-driven animation frames, so images whose static
			 * layout position starts off-screen — which is most of this row,
			 * including the entire duplicated loop set below — never trigger
			 * and stay unloaded (or load very late). Chrome is lenient enough
			 * to fetch them anyway, which is why this only shows up in Safari.
			 */ ?>
			<?php foreach ( $urls as $url ) : ?>
			<img class="showcaseImg" src="<?php echo esc_url( $url ); ?>" alt="" loading="eager" decoding="async">
			<?php endforeach; ?>
			<?php foreach ( $urls as $url ) : ?>
			<img class="showcaseImg" src="<?php echo esc_url( $url ); ?>" alt="" loading="eager" decoding="async">
			<?php endforeach; ?>
		</div>
	</div>
</section>
