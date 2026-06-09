<?php
/**
 * Highlights block — server-side render.
 */

$limit     = max( 1, (int) ( $attributes['limit'] ?? 8 ) );
$video_ids = [];

// Try live TikTok Display API fetch (cached for 1 hour).
$access_token = get_option( 'tbc_tiktok_access_token' );

if ( $access_token ) {
	$cached = get_transient( 'tbc_tiktok_videos' );

	if ( false === $cached ) {
		$response = wp_remote_post(
			'https://open.tiktokapis.com/v2/video/list/',
			[
				'timeout' => 8,
				'headers' => [
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json; charset=UTF-8',
				],
				'body' => wp_json_encode( [ 'max_count' => $limit ] ),
			]
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$video_ids = array_column( $body['data']['videos'] ?? [], 'id' );
			set_transient( 'tbc_tiktok_videos', $video_ids, HOUR_IN_SECONDS );
		}
	} else {
		$video_ids = $cached;
	}
}

// Fall back to manually entered IDs.
if ( empty( $video_ids ) && ! empty( $attributes['videoIds'] ) ) {
	$video_ids = array_values( array_filter( array_map( 'trim', explode( ',', $attributes['videoIds'] ) ) ) );
}

$video_ids = array_slice( $video_ids, 0, $limit );
?>
<section class="highlights" id="highlights">
	<div class="highlightsInner">
		<h2 class="highlightsTitle">The Highlights</h2>
	</div>
	<div class="highlightSlider">
		<div class="highlightTrack">
			<?php if ( $video_ids ) :
				foreach ( $video_ids as $vid ) : ?>
				<div class="tiktokSlide">
					<iframe
						class="tiktokEmbed"
						src="https://www.tiktok.com/embed/v2/<?php echo esc_attr( $vid ); ?>?theme=dark"
						allowfullscreen
						allow="encrypted-media"
						title="<?php esc_attr_e( 'The Black Cap on TikTok', 'the-black-cap' ); ?>"
					></iframe>
				</div>
			<?php endforeach;
			else :
				for ( $i = 0; $i < 5; $i++ ) : ?>
				<div class="highlightCard"></div>
			<?php endfor;
			endif; ?>
		</div>
	</div>
</section>
