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
			<?php foreach ( $urls as $url ) : ?>
			<img class="showcaseImg" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy">
			<?php endforeach; ?>
			<?php foreach ( $urls as $url ) : ?>
			<img class="showcaseImg" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy">
			<?php endforeach; ?>
		</div>
	</div>
</section>
