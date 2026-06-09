<?php
/**
 * What's On block — server-side render.
 */

$limit      = max( 1, (int) ( $attributes['limit'] ?? 8 ) );
$shortcodes = [];

// Try live API fetch (cached for 1 hour).
$access_token = get_option( 'tbc_instagram_access_token' );
$user_id      = get_option( 'tbc_instagram_user_id' );

if ( $access_token && $user_id ) {
	$cached = get_transient( 'tbc_instagram_posts' );

	if ( false === $cached ) {
		$url = add_query_arg( [
			'fields'       => 'id,shortcode',
			'limit'        => $limit,
			'access_token' => $access_token,
		], "https://graph.instagram.com/{$user_id}/media" );

		$response = wp_remote_get( $url, [ 'timeout' => 8 ] );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body       = json_decode( wp_remote_retrieve_body( $response ), true );
			$shortcodes = array_column( $body['data'] ?? [], 'shortcode' );
			set_transient( 'tbc_instagram_posts', $shortcodes, HOUR_IN_SECONDS );
		}
	} else {
		$shortcodes = $cached;
	}
}

// Fall back to manually entered shortcodes.
if ( empty( $shortcodes ) && ! empty( $attributes['shortcodes'] ) ) {
	$shortcodes = array_values( array_filter( array_map( 'trim', explode( ',', $attributes['shortcodes'] ) ) ) );
}

$shortcodes = array_slice( $shortcodes, 0, $limit );
?>
<section class="whatsOn" id="whats-on">
	<div class="whatsOnInner">
		<h2 class="whatsOnTitle">What&rsquo;s On</h2>
	</div>
	<div class="posterSlider">
		<div class="posterTrack">
			<?php if ( $shortcodes ) :
				foreach ( $shortcodes as $code ) : ?>
				<div class="instaWrapper">
					<iframe
						class="instaEmbed"
						src="https://www.instagram.com/p/<?php echo esc_attr( $code ); ?>/embed/captioned/?utm_source=ig_embed"
						scrolling="no"
						allowtransparency="true"
						title="<?php esc_attr_e( 'Instagram post', 'the-black-cap' ); ?>"
					></iframe>
				</div>
			<?php endforeach;
			else :
				for ( $i = 0; $i < 5; $i++ ) : ?>
				<div class="posterCard"></div>
			<?php endfor;
			endif; ?>
		</div>
	</div>
</section>
