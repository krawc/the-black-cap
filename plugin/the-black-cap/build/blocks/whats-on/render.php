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

	// Deduplicate by event ID.
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

/* ── Collapse recurring events ──────────────────────────────────── */
// Events that share a series_id are individual occurrences of the same
// recurring event. Group them into one card showing all dates so they
// don't flood the slider with duplicates.

$by_series = [];
$singles   = [];
$now       = time();

foreach ( $events as $ev ) {
	$sid = $ev['series_id'] ?? '';
	if ( $sid ) {
		$by_series[ $sid ][] = $ev;
	} else {
		$singles[] = $ev;
	}
}

$collapsed = $singles;

foreach ( $by_series as $sid => $occurrences ) {
	// Sort ascending so dates render chronologically.
	usort( $occurrences, static function ( $a, $b ) {
		return ( strtotime( $a['start']['local'] ?? '' ) ?: 0 )
			<=> ( strtotime( $b['start']['local'] ?? '' ) ?: 0 );
	} );

	// Representative = soonest future occurrence; fall back to the last one.
	$rep = end( $occurrences );
	foreach ( $occurrences as $occ ) {
		$ts = strtotime( $occ['start']['local'] ?? '' );
		if ( $ts && $ts >= $now ) {
			$rep = $occ;
			break;
		}
	}

	$rep_local = $rep['start']['local'] ?? '';

	// Other future occurrences (excluding the representative) — shown in the popup.
	$future_other = [];
	foreach ( $occurrences as $occ ) {
		$local = $occ['start']['local'] ?? '';
		$ts    = $local ? ( strtotime( $local ) ?: 0 ) : 0;
		if ( $ts >= $now && $local !== $rep_local ) {
			$future_other[] = $local;
		}
	}

	$rep['_recurring']     = true;
	$rep['_future_starts'] = $future_other;

	$collapsed[] = $rep;
}

// Sort: soonest upcoming first, then past events most-recent-first.
usort( $collapsed, static function ( $a, $b ) use ( $now ) {
	$ta = strtotime( $a['start']['local'] ?? '' ) ?: 0;
	$tb = strtotime( $b['start']['local'] ?? '' ) ?: 0;
	$af = $ta && $ta >= $now;
	$bf = $tb && $tb >= $now;
	if ( $af !== $bf ) return $af ? -1 : 1;
	return $af ? ( $ta <=> $tb ) : ( $tb <=> $ta );
} );

$events = array_slice( $collapsed, 0, $limit );
?>
<section class="whatsOn" id="whats-on">
	<div class="whatsOnInner">
		<h2 class="whatsOnTitle"><?php echo esc_html( $attributes['heading'] ?? "What's On" ); ?></h2>
	</div>
	<div class="posterSlider">
		<div class="posterTrack">
			<?php if ( $events ) :
				foreach ( $events as $ev ) :
					$title        = $ev['name']['text'] ?? '';
					$full_desc    = $ev['description']['text'] ?? $ev['summary'] ?? '';
					$excerpt      = wp_trim_words( $ev['summary'] ?? $full_desc, 18 );
					$url          = $ev['url'] ?? '#';
					$img          = $ev['logo']['original']['url'] ?? $ev['logo']['url'] ?? '';
					$date         = tbc_format_event_date( $ev['start']['local'] ?? '' );
					$short_desc   = substr( $full_desc, 0, 600 );
					$is_recurring = ! empty( $ev['_recurring'] );

					// Other future dates for the popup.
					$future_dates = [];
					if ( $is_recurring ) {
						foreach ( $ev['_future_starts'] ?? [] as $local ) {
							$fmt = tbc_format_event_date( $local );
							if ( $fmt ) $future_dates[] = $fmt;
						}
					}

					$end_utc = $ev['end']['utc'] ?? '';
					$is_past = $end_utc && ( strtotime( $end_utc ) < $now );

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
				data-recurring="<?php echo $is_recurring ? '1' : ''; ?>"
				data-dates="<?php echo $is_recurring ? esc_attr( wp_json_encode( $all_dates ) ) : ''; ?>"
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

					<?php if ( $is_recurring ) : ?>
					<div class="eventCard__dates">
						<span class="eventCard__dates-item"><?php echo esc_html( $date ); ?></span>
						<?php if ( $future_dates ) : ?>
						<button class="eventCard__dates-more" type="button" aria-expanded="false">+<?php echo count( $future_dates ); ?> more</button>
						<div class="eventCard__dates-bubble">
							<?php foreach ( $future_dates as $fd ) : ?>
							<span><?php echo esc_html( $fd ); ?></span>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
					<?php elseif ( $date ) : ?>
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
