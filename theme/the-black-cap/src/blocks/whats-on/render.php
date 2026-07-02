<?php
/**
 * What's On block — Eventbrite events, server-side render.
 */

if ( ! function_exists( 'tbc_format_event_date' ) ) {
	function tbc_format_event_date( string $local ): string {
		if ( ! $local ) return '';
		$ts = strtotime( $local );
		return $ts ? date_i18n( 'D j M · g:ia', $ts ) : '';
	}
}

$limit  = max( 1, (int) ( $attributes['limit'] ?? 10 ) );
$events = [];

$token  = get_option( 'tbc_eventbrite_token' );
$org_id = get_option( 'tbc_eventbrite_org_id', '3005226258349' );

if ( $token && $org_id ) {
	$cached = get_transient( 'tbc_eventbrite_events' );

	if ( false === $cached ) {
		// Fetch all events (no time_filter), most recent first.
		// Request double the limit so deduplication never undershoots.
		$url = add_query_arg( [
			'expand'    => 'logo',
			'order_by'  => 'start_desc',
			'page_size' => 50,
		], "https://www.eventbriteapi.com/v3/organizations/{$org_id}/events/" );

		$response = wp_remote_get( $url, [
			'timeout' => 10,
			'headers' => [ 'Authorization' => 'Bearer ' . $token ],
		] );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body   = json_decode( wp_remote_retrieve_body( $response ), true );
			$cached = $body['events'] ?? [];
			set_transient( 'tbc_eventbrite_events', $cached, HOUR_IN_SECONDS );
		} else {
			$cached = [];
		}
	}

	// Deduplicate by event ID (guards against the API returning repeats).
	$seen = [];
	foreach ( $cached ?: [] as $ev ) {
		$id = $ev['id'] ?? '';
		if ( $id === '' || isset( $seen[ $id ] ) ) continue;
		$seen[ $id ] = true;
		$events[]    = $ev;
	}
}

// Fallback to manually entered Eventbrite event IDs.
if ( empty( $events ) && ! empty( $attributes['eventIds'] ) ) {
	foreach ( array_filter( array_map( 'trim', explode( ',', $attributes['eventIds'] ) ) ) as $eid ) {
		$events[] = [
			'id'          => $eid,
			'name'        => [ 'text' => 'Event #' . $eid ],
			'description' => [ 'text' => '' ],
			'summary'     => '',
			'url'         => 'https://www.eventbrite.co.uk/e/' . $eid,
			'start'       => [ 'local' => '', 'utc' => '' ],
			'end'         => [ 'utc' => '' ],
			'logo'        => null,
		];
	}
}

$events = array_slice( $events, 0, $limit );
?>
<section class="whatsOn" id="whats-on">
	<div class="whatsOnInner">
		<h2 class="whatsOnTitle">What&rsquo;s On</h2>
	</div>
	<div class="posterSlider">
		<div class="posterTrack">
			<?php if ( $events ) :
				foreach ( $events as $ev ) :
					$title      = $ev['name']['text'] ?? '';
					$full_desc  = $ev['description']['text'] ?? $ev['summary'] ?? '';
					$excerpt    = wp_trim_words( $ev['summary'] ?? $full_desc, 18 );
					$url        = $ev['url'] ?? '#';
					$img        = $ev['logo']['original']['url'] ?? $ev['logo']['url'] ?? '';
					$date       = tbc_format_event_date( $ev['start']['local'] ?? '' );
					$short_desc = substr( $full_desc, 0, 600 );

					// A past event = end time has already passed (UTC comparison).
					$end_utc = $ev['end']['utc'] ?? '';
					$is_past = $end_utc && ( strtotime( $end_utc ) < time() );

					$btn_label = $is_past
						? esc_html__( 'View Event', 'the-black-cap' )
						: esc_html__( 'Get Tickets', 'the-black-cap' );
			?>
			<article class="eventCard<?php echo $is_past ? ' eventCard--past' : ''; ?>"
				data-title="<?php echo esc_attr( $title ); ?>"
				data-desc="<?php echo esc_attr( $short_desc ); ?>"
				data-url="<?php echo esc_attr( $url ); ?>"
				data-img="<?php echo esc_attr( $img ); ?>"
				data-date="<?php echo esc_attr( $date ); ?>"
				data-past="<?php echo $is_past ? '1' : ''; ?>"
				tabindex="0"
				role="button"
				aria-label="<?php echo esc_attr( sprintf( __( 'View details for %s', 'the-black-cap' ), $title ) ); ?>"
			>
				<div class="eventCard__img<?php echo $img ? '' : ' eventCard__img--empty'; ?>">
					<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
					<?php endif; ?>
				</div>
				<div class="eventCard__body">
					<?php if ( $date ) : ?>
					<time class="eventCard__date"><?php echo esc_html( $date ); ?></time>
					<?php endif; ?>
					<h3 class="eventCard__title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( $excerpt ) : ?>
					<p class="eventCard__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<a class="eventCard__tickets<?php echo $is_past ? ' eventCard__tickets--past' : ''; ?>"
						href="<?php echo esc_url( $url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						onclick="event.stopPropagation()"
					><?php echo $btn_label; ?></a>
				</div>
			</article>
			<?php endforeach;
			else :
				for ( $i = 0; $i < 4; $i++ ) : ?>
				<div class="posterCard"></div>
			<?php endfor;
			endif; ?>
		</div>
	</div>
</section>
