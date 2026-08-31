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

// Drop events whose end time is known and already in the past.
$collapsed = array_values( array_filter( $collapsed, static function ( $ev ) use ( $now ) {
	$end_utc = $ev['end']['utc'] ?? '';
	return ! $end_utc || strtotime( $end_utc ) >= $now;
} ) );

// Sort soonest first.
usort( $collapsed, static function ( $a, $b ) {
	$ta = strtotime( $a['start']['local'] ?? '' ) ?: 0;
	$tb = strtotime( $b['start']['local'] ?? '' ) ?: 0;
	return $ta <=> $tb;
} );

// Show every upcoming event — no cap. The $limit attribute is no longer
// used to truncate the list now that events are laid out on a calendar
// rather than a horizontally-scrolling slider.
$events = $collapsed;

/* ── Build a day → occurrences map for the calendar ─────────────── */
// A recurring event contributes one chip per future occurrence date so
// the calendar shows it on every day it actually runs.

$by_day  = [];
$undated = []; // events with no resolvable start date (manual fallback IDs only)

foreach ( $events as $ev ) {
	$title        = $ev['name']['text'] ?? '';
	$full_desc    = $ev['description']['text'] ?? $ev['summary'] ?? '';
	$excerpt      = wp_trim_words( $ev['summary'] ?? $full_desc, 18 );
	$url          = $ev['url'] ?? '#';
	$img          = $ev['logo']['original']['url'] ?? $ev['logo']['url'] ?? '';
	$short_desc   = substr( $full_desc, 0, 600 );
	$is_recurring = ! empty( $ev['_recurring'] );
	$end_utc      = $ev['end']['utc'] ?? '';
	$is_past      = $end_utc && ( strtotime( $end_utc ) < $now );

	$locals = [ $ev['start']['local'] ?? '' ];
	if ( $is_recurring ) {
		foreach ( $ev['_future_starts'] ?? [] as $fl ) {
			$locals[] = $fl;
		}
	}
	$locals = array_values( array_unique( array_filter( $locals ) ) );

	// Full formatted date list, used in the popup for recurring events.
	$all_dates_fmt = [];
	foreach ( $locals as $local ) {
		$fmt = tbc_format_event_date( $local );
		if ( $fmt ) $all_dates_fmt[] = $fmt;
	}

	if ( ! $locals ) {
		// No resolvable date (manual fallback event IDs with no known start
		// time) — can't be placed on the calendar grid, so list separately.
		$undated[] = [
			'title'       => $title,
			'excerpt'     => $excerpt,
			'desc'        => $short_desc,
			'url'         => $url,
			'img'         => $img,
			'date'        => '',
			'is_past'     => $is_past,
			'is_recurring' => false,
			'dates_json'  => '',
		];
		continue;
	}

	foreach ( $locals as $local ) {
		$ts = strtotime( $local );
		if ( ! $ts ) continue;

		$by_day[ date( 'Y-m-d', $ts ) ][] = [
			'title'       => $title,
			'excerpt'     => $excerpt,
			'desc'        => $short_desc,
			'url'         => $url,
			'img'         => $img,
			'date'        => tbc_format_event_date( $local ),
			'is_past'     => $is_past,
			'is_recurring' => $is_recurring,
			'dates_json'  => $is_recurring ? wp_json_encode( $all_dates_fmt ) : '',
			'sort'        => $ts,
		];
	}
}

foreach ( $by_day as $key => $items ) {
	usort( $items, static function ( $a, $b ) { return $a['sort'] <=> $b['sort']; } );
	$by_day[ $key ] = $items;
}

/* ── 12 months forward, starting with the current month ──────────── */

$months     = [];
$month_base = strtotime( date( 'Y-m-01', $now ) );

for ( $i = 0; $i < 12; $i++ ) {
	$mts       = strtotime( "+{$i} month", $month_base );
	$months[]  = [
		'key'   => date( 'Y-m', $mts ),
		'label' => date_i18n( 'F Y', $mts ),
		'year'  => (int) date( 'Y', $mts ),
		'month' => (int) date( 'n', $mts ),
	];
}

$today_key = date( 'Y-m-d', $now );
$weekdays  = [ __( 'Mon', 'the-black-cap' ), __( 'Tue', 'the-black-cap' ), __( 'Wed', 'the-black-cap' ), __( 'Thu', 'the-black-cap' ), __( 'Fri', 'the-black-cap' ), __( 'Sat', 'the-black-cap' ), __( 'Sun', 'the-black-cap' ) ];
?>
<section class="whatsOn" id="whats-on">
	<div class="whatsOnInner">
		<h2 class="whatsOnTitle"><?php echo esc_html( $attributes['heading'] ?? "What's On" ); ?></h2>
	</div>

	<div class="tbcCalendar" data-tbc-calendar>
		<div class="tbcCalendar__nav">
			<button type="button" class="tbcCalendar__arrow tbcCalendar__arrow--prev" data-tbc-cal-prev aria-label="<?php esc_attr_e( 'Previous month', 'the-black-cap' ); ?>">&#10094;</button>
			<div class="tbcCalendar__months" data-tbc-cal-months>
				<?php foreach ( $months as $i => $m ) : ?>
				<button type="button" class="tbcCalendar__monthBtn<?php echo 0 === $i ? ' is-active' : ''; ?>" data-tbc-cal-btn data-month-index="<?php echo (int) $i; ?>"><?php echo esc_html( $m['label'] ); ?></button>
				<?php endforeach; ?>
			</div>
			<button type="button" class="tbcCalendar__arrow tbcCalendar__arrow--next" data-tbc-cal-next aria-label="<?php esc_attr_e( 'Next month', 'the-black-cap' ); ?>">&#10095;</button>
		</div>

		<div class="tbcCalendar__panels">
			<?php foreach ( $months as $i => $m ) :
				$first_ts      = mktime( 0, 0, 0, $m['month'], 1, $m['year'] );
				$days_in_month = (int) date( 't', $first_ts );
				$lead_blanks   = ( (int) date( 'N', $first_ts ) ) - 1; // 0 = Monday
			?>
			<div class="tbcCalendar__panel<?php echo 0 === $i ? ' is-active' : ''; ?>" data-tbc-cal-panel data-month-index="<?php echo (int) $i; ?>">
				<div class="tbcCalendar__weekdays">
					<?php foreach ( $weekdays as $wd ) : ?>
					<span class="tbcCalendar__weekday"><?php echo esc_html( $wd ); ?></span>
					<?php endforeach; ?>
				</div>
				<div class="tbcCalendar__grid">
					<?php for ( $b = 0; $b < $lead_blanks; $b++ ) : ?>
					<div class="tbcCalendar__day tbcCalendar__day--empty"></div>
					<?php endfor;

					for ( $d = 1; $d <= $days_in_month; $d++ ) :
						$day_key = sprintf( '%s-%02d', $m['key'], $d );
						$items   = $by_day[ $day_key ] ?? [];
						$is_today = $day_key === $today_key;
					?>
					<div class="tbcCalendar__day<?php echo $is_today ? ' tbcCalendar__day--today' : ''; ?><?php echo $items ? ' tbcCalendar__day--has-events' : ''; ?>">
						<span class="tbcCalendar__dayNum"><?php echo (int) $d; ?></span>
						<?php if ( $items ) : ?>
						<div class="tbcCalendar__events">
							<?php foreach ( $items as $it ) : ?>
							<button
								type="button"
								class="eventCard eventChip<?php echo $it['is_past'] ? ' eventCard--past' : ''; ?>"
								data-title="<?php echo esc_attr( $it['title'] ); ?>"
								data-desc="<?php echo esc_attr( $it['desc'] ); ?>"
								data-url="<?php echo esc_attr( $it['url'] ); ?>"
								data-img="<?php echo esc_attr( $it['img'] ); ?>"
								data-date="<?php echo esc_attr( $it['date'] ); ?>"
								data-past="<?php echo $it['is_past'] ? '1' : ''; ?>"
								data-recurring="<?php echo $it['is_recurring'] ? '1' : ''; ?>"
								data-dates="<?php echo $it['is_recurring'] ? esc_attr( $it['dates_json'] ) : ''; ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'View details for %s', 'the-black-cap' ), $it['title'] ) ); ?>"
							><?php echo esc_html( $it['title'] ); ?></button>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
					<?php endfor; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $undated ) : ?>
	<div class="tbcCalendar tbcCalendar--undated">
		<p class="tbcCalendar__undatedLabel"><?php esc_html_e( 'Date to be confirmed', 'the-black-cap' ); ?></p>
		<div class="tbcCalendar__events tbcCalendar__events--undated">
			<?php foreach ( $undated as $it ) : ?>
			<button
				type="button"
				class="eventCard eventChip<?php echo $it['is_past'] ? ' eventCard--past' : ''; ?>"
				data-title="<?php echo esc_attr( $it['title'] ); ?>"
				data-desc="<?php echo esc_attr( $it['desc'] ); ?>"
				data-url="<?php echo esc_attr( $it['url'] ); ?>"
				data-img="<?php echo esc_attr( $it['img'] ); ?>"
				data-date=""
				data-past="<?php echo $it['is_past'] ? '1' : ''; ?>"
				data-recurring=""
				data-dates=""
				aria-label="<?php echo esc_attr( sprintf( __( 'View details for %s', 'the-black-cap' ), $it['title'] ) ); ?>"
			><?php echo esc_html( $it['title'] ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
</section>
