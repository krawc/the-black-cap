<?php
/**
 * Highlights block — server-side render.
 *
 * Two display modes:
 *   thumbnail (default) — cover images with a dark overlay + play button → opens TikTok in new tab
 *   embed               — TikTok /embed/v2/ iframes
 */

if ( ! function_exists( 'tbc_tiktok_get_access_token' ) ) :
function tbc_tiktok_get_access_token(): string {
	$cached = get_transient( 'tbc_tiktok_access_token_cache' );
	if ( $cached ) return (string) $cached;

	$refresh_token = get_option( 'tbc_tiktok_refresh_token' );
	$client_key    = get_option( 'tbc_tiktok_client_key' );
	$client_secret = get_option( 'tbc_tiktok_client_secret' );

	if ( ! $refresh_token || ! $client_key || ! $client_secret ) return '';

	$response = wp_remote_post( 'https://open.tiktokapis.com/v2/oauth/token/', [
		'timeout' => 8,
		'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
		'body'    => [
			'client_key'    => $client_key,
			'client_secret' => $client_secret,
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refresh_token,
		],
	] );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) return '';

	$data              = json_decode( wp_remote_retrieve_body( $response ), true );
	$new_access_token  = $data['access_token']  ?? '';
	$new_refresh_token = $data['refresh_token'] ?? '';
	$expires_in        = max( 3600, (int) ( $data['expires_in'] ?? 86400 ) );

	if ( ! $new_access_token ) return '';

	set_transient( 'tbc_tiktok_access_token_cache', $new_access_token, $expires_in - 300 );

	if ( $new_refresh_token && $new_refresh_token !== $refresh_token ) {
		update_option( 'tbc_tiktok_refresh_token', $new_refresh_token, false );
	}

	return $new_access_token;
}
endif;

$mode          = $attributes['mode']          ?? 'thumbnail';
$limit         = max( 1, (int) ( $attributes['limit'] ?? 8 ) );
$profile_url   = esc_url( $attributes['profileUrl'] ?: get_option( 'tbc_social_tiktok', '' ) );
$profile_label = esc_html( $attributes['profileLabel'] ?? __( 'View Profile', 'the-black-cap' ) );

/* ── Fetch video data ─────────────────────────────────────────────── */
$videos = []; // each item: ['id' => '', 'thumb' => '', 'url' => '']

$access_token = tbc_tiktok_get_access_token();

if ( $access_token ) {
	$cached = get_transient( 'tbc_tiktok_videos' );

	// Accept cached data only if it's the new array-of-objects format.
	if ( is_array( $cached ) && isset( $cached[0]['id'] ) ) {
		$videos = $cached;
	} else {
		$response = wp_remote_post(
			'https://open.tiktokapis.com/v2/video/list/?fields=id,cover_image_url,title,share_url',
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
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $body['data']['videos'] ?? [] as $v ) {
				$videos[] = [
					'id'    => (string) ( $v['id']              ?? '' ),
					'thumb' => (string) ( $v['cover_image_url'] ?? '' ),
					'url'   => (string) ( $v['share_url']       ?? '' ),
				];
			}
			set_transient( 'tbc_tiktok_videos', $videos, HOUR_IN_SECONDS );
		}
	}
}

// Fallback: manual IDs (embed mode only — thumbnails need the API).
if ( empty( $videos ) && ! empty( $attributes['videoIds'] ) ) {
	foreach ( array_filter( array_map( 'trim', explode( ',', $attributes['videoIds'] ) ) ) as $id ) {
		$videos[] = [ 'id' => $id, 'thumb' => '', 'url' => '' ];
	}
}

$videos = array_slice( $videos, 0, $limit );
?>
<section class="highlights" id="highlights">
	<div class="highlightsInner">
		<h2 class="highlightsTitle"><?php echo esc_html( $attributes['heading'] ?? 'Highlights' ); ?></h2>
	</div>

	<?php if ( $videos ) : ?>
	<div class="highlightSlider">
		<div class="highlightTrack">

			<?php if ( 'thumbnail' === $mode ) : ?>

				<?php foreach ( $videos as $v ) :
					if ( empty( $v['thumb'] ) ) continue; // skip if no thumbnail
					$href  = ! empty( $v['url'] ) ? esc_url( $v['url'] ) : $profile_url;
				?>
				<a class="tiktokSlide tiktokSlide--thumb" href="<?php echo $href; ?>" target="_blank" rel="noopener noreferrer">
					<img class="tiktokSlide__img" src="<?php echo esc_url( $v['thumb'] ); ?>" alt="<?php esc_attr_e( 'The Black Cap on TikTok', 'the-black-cap' ); ?>" loading="lazy">
					<div class="tiktokSlide__play" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
							<polygon points="6,3 20,12 6,21"/>
						</svg>
					</div>
				</a>
				<?php endforeach; ?>

			<?php else : // embed mode ?>

				<?php foreach ( $videos as $v ) : ?>
				<div class="tiktokSlide tiktokSlide--embed">
					<iframe
						class="tiktokEmbed"
						src="https://www.tiktok.com/embed/v2/<?php echo esc_attr( $v['id'] ); ?>?theme=dark&rel=0"
						allowfullscreen
						allow="encrypted-media"
						title="<?php esc_attr_e( 'The Black Cap on TikTok', 'the-black-cap' ); ?>"
						loading="lazy"
						scrolling="no"
					></iframe>
				</div>
				<?php endforeach; ?>

			<?php endif; ?>

		</div>
	</div>
	<?php else : ?>
	<div class="highlightSlider">
		<div class="highlightTrack">
			<?php for ( $i = 0; $i < 5; $i++ ) : ?>
			<div class="highlightCard"></div>
			<?php endfor; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $profile_url ) : ?>
	<div class="highlightsProfile">
		<a href="<?php echo $profile_url; ?>" class="neonButton highlightsProfileBtn" target="_blank" rel="noopener noreferrer">
			<svg class="highlightsProfileBtn__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.79 1.54V6.79a4.85 4.85 0 0 1-1.02-.1z"/></svg>
			<?php echo $profile_label; ?>
		</a>
	</div>
	<?php endif; ?>
</section>
