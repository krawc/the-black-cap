<?php
/**
 * Venue Hire block — server-side render.
 */

$slots = $attributes['slots'] ?? [ [ 'venueId' => 0 ], [ 'venueId' => 0 ], [ 'venueId' => 0 ] ];

// Build venue data indexed by slot position (0, 1, 2).
$venue_data = [];
foreach ( $slots as $i => $slot ) {
	$vid = (int) ( $slot['venueId'] ?? 0 );
	if ( $vid ) {
		$title  = get_the_title( $vid );
		$desc   = (string) ( get_post_meta( $vid, 'tbc_venue_description', true ) ?: '' );
		$ids    = (array)  ( get_post_meta( $vid, 'tbc_venue_image_ids',   true ) ?: [] );
		$photos = [];
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_image_url( (int) $id, 'large' ) ?: wp_get_attachment_image_url( (int) $id, 'full' );
			if ( $url ) $photos[] = $url;
		}
		$venue_data[ $i ] = compact( 'title', 'desc', 'photos' );
	} else {
		$venue_data[ $i ] = [ 'title' => '', 'desc' => '', 'photos' => [] ];
	}
}

// Find the first slot that has a venue assigned to pre-populate the panel.
$initial_idx = 0;
foreach ( $venue_data as $idx => $v ) {
	if ( $v['title'] ) { $initial_idx = $idx; break; }
}
$initial = $venue_data[ $initial_idx ];

// Inline the venue SVG, injecting class + data attributes on each <path>.
$svg_path = TBC_PLUGIN_DIR . 'assets/svg/venue.svg';
$svg_raw  = file_exists( $svg_path ) ? file_get_contents( $svg_path ) : '';

if ( $svg_raw ) {
	// Phase 1: stamp class/data onto each visual path; collect d values for hit zones.
	$idx      = 0;
	$d_values = [];
	$svg_raw  = preg_replace_callback( '/<path\b([^>]*)\/>/s', function ( $m ) use ( &$idx, &$d_values ) {
		$attrs = $m[1];
		preg_match( '/\bd="([^"]*)"/', $attrs, $dm );
		$d_values[] = $dm[1] ?? '';
		$out = '<path' . $attrs
		     . ' class="venueShape"'
		     . ' tabindex="0"'
		     . ' role="button"'
		     . ' aria-label="' . esc_attr( 'Venue area ' . ( $idx + 1 ) ) . '"'
		     . ' data-venue-index="' . $idx . '"'
		     . '/>';
		$idx++;
		return $out;
	}, $svg_raw );

	// Phase 2: append invisible hit-zone paths (thick transparent stroke = large click target).
	// Same DOM order as visual paths (0→2) so Terrace's hit zone is topmost and wins overlaps.
	$hit_zones = '';
	foreach ( $d_values as $i => $d ) {
		$hit_zones .= "\n\t" . '<path class="venueHitZone" data-venue-index="' . $i . '" d="' . $d . '" aria-hidden="true"/>';
	}
	$svg_raw = str_replace( '</svg>', $hit_zones . "\n</svg>", $svg_raw );
}
?>
<section class="venueHireSection" id="venue-hire">
	<div class="venueHireInner">
		<h2 class="menuHeadline venueHireTitle">Venue Hire</h2>
		<div class="venueHireLayout"
			data-venues="<?php echo esc_attr( wp_json_encode( $venue_data ) ); ?>"
		>
			<div class="venueHireSvg">
				<?php echo $svg_raw; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<img class="venueTapCue" src="<?php echo esc_url( TBC_PLUGIN_URL . 'assets/svg/tap.svg' ); ?>" alt="" aria-hidden="true" width="44" height="44">
			</div>
			<div class="venueHirePanel" data-active-idx="<?php echo esc_attr( $initial_idx ); ?>">
				<h3 class="venueHirePanel__name"><?php echo esc_html( $initial['title'] ); ?></h3>
				<p class="venueHirePanel__desc"><?php echo esc_html( $initial['desc'] ); ?></p>
				<div class="venueThumbGrid"></div>
				<a class="neonButton venueHirePanel__cta"
					href="mailto:sassy@blackcapcamden.co.uk"
				>Book the Venue</a>
			</div>
		</div>
	</div>
</section>
