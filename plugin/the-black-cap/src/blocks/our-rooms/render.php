<?php
/**
 * Our Rooms block — server-side render.
 *
 * Desktop: SVG frame grid with hover tooltip + room lightbox on click.
 * Mobile:  Plain image slider (one card per unique room, same lightbox on tap).
 *
 * The Mews Booking Engine is injected into wp_footer once per page load.
 */

static $mews_enqueued = false;
if ( ! $mews_enqueued ) {
	$mews_enqueued = true;
	add_action( 'wp_footer', static function (): void {
		?>
<script>(function(m,e,w,s){c=m.createElement(e);c.onload=function(){Mews.D(s[0],function(api){window.MewsApi=api;})};c.async=1;c.src=w;t=m.getElementsByTagName(e)[0];t.parentNode.insertBefore(c,t)})(document,'script','https://app.mews.com/distributor/distributor.min.js',[['8a6e6542-af3c-4a36-b19e-b36b00a8c958']]);</script>
		<?php
	}, 20 );
}

$frames   = $attributes['frames'] ?? [];
$base_url = TBC_PLUGIN_URL . '/assets/svg/frames/';

/* Build per-frame data and collect unique rooms for the mobile slider */
$frame_data   = [];
$unique_rooms = [];  // roomId → room array (first occurrence wins)

foreach ( $frames as $frame ) {
	$svg_file = basename( $frame['svgFile'] ?? 'Frame 1.svg' );
	$svg_url  = esc_url( $base_url . $svg_file );
	$room_id  = (int) ( $frame['roomId'] ?? 0 );
	$wide     = ! empty( $frame['wide'] );

	$photos     = [];
	$room_title = '';
	$room_desc  = '';

	if ( $room_id ) {
		$room_title = get_the_title( $room_id );
		$room_desc  = (string) ( get_post_meta( $room_id, 'tbc_room_description', true ) ?: '' );
		$ids        = (array) ( get_post_meta( $room_id, 'tbc_room_image_ids', true ) ?: [] );

		foreach ( $ids as $id ) {
			$url = wp_get_attachment_image_url( (int) $id, 'large' )
				?: wp_get_attachment_image_url( (int) $id, 'full' );
			if ( $url ) {
				$photos[] = esc_url( $url );
			}
		}

		if ( ! isset( $unique_rooms[ $room_id ] ) ) {
			$unique_rooms[ $room_id ] = [
				'title'       => $room_title,
				'desc'        => $room_desc,
				'photos'      => $photos,
				'first_photo' => $photos[0] ?? '',
			];
		}
	}

	$frame_data[] = compact( 'svg_url', 'room_id', 'wide', 'photos', 'room_title', 'room_desc' );
}
?>
<section class="roomsSection" id="our-rooms">
	<h2 class="roomsHeadline">Our Rooms</h2>

	<?php if ( $frame_data ) : ?>

	<!-- Desktop: SVG frame grid -->
	<div class="framesGrid">
		<?php foreach ( $frame_data as $fd ) : ?>
		<div
			class="frameGallery<?php echo $fd['wide'] ? ' frameWide' : ''; ?>"
			data-svg="<?php echo $fd['svg_url']; ?>"
			data-photos="<?php echo esc_attr( wp_json_encode( $fd['photos'] ) ); ?>"
			data-room-name="<?php echo esc_attr( $fd['room_title'] ); ?>"
			data-room-desc="<?php echo esc_attr( $fd['room_desc'] ); ?>"
		></div>
		<?php endforeach; ?>
	</div>

	<!-- Mobile: plain image slider (one card per unique room) -->
	<div class="roomsSlider posterSlider">
		<div class="posterTrack">
			<?php foreach ( $unique_rooms as $room ) :
				$photos_json = esc_attr( wp_json_encode( $room['photos'] ) );
			?>
			<div
				class="roomSlideCard"
				data-photos="<?php echo $photos_json; ?>"
				data-room-name="<?php echo esc_attr( $room['title'] ); ?>"
				data-room-desc="<?php echo esc_attr( $room['desc'] ); ?>"
				role="button"
				tabindex="0"
				aria-label="<?php echo esc_attr( $room['title'] ); ?>"
			>
				<?php if ( $room['first_photo'] ) : ?>
				<img
					class="roomSlideCard__img"
					src="<?php echo esc_url( $room['first_photo'] ); ?>"
					alt="<?php echo esc_attr( $room['title'] ); ?>"
					loading="lazy"
				>
				<?php else : ?>
				<div class="roomSlideCard__img roomSlideCard__img--empty"></div>
				<?php endif; ?>
				<div class="roomSlideCard__label"><?php echo esc_html( $room['title'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php endif; ?>

	<a href="#book" class="neonButton"><?php esc_html_e( 'See Availability', 'the-black-cap' ); ?></a>
</section>
