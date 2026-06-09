<?php
/**
 * Our Rooms block — server-side render.
 *
 * Each frame outputs a .frameGallery div with data-svg and data-photos attributes.
 * The frontend JS (FrameGallery initialiser in assets/js/frontend.js) fetches the SVG,
 * identifies red-fill shapes, and clips photos to those shapes using SVG clipPath.
 */

$frames   = $attributes['frames'] ?? [];
$base_url = get_template_directory_uri() . '/assets/svg/frames/';
?>
<section class="roomsSection" id="our-rooms">
	<h2 class="roomsHeadline">Our Rooms</h2>
	<?php if ( $frames ) : ?>
	<div class="framesGrid">
		<?php foreach ( $frames as $frame ) :
			$svg_file = basename( $frame['svgFile'] ?? 'Frame 1.svg' );
			$svg_url  = esc_url( $base_url . $svg_file );
			$photos   = array_values( array_filter( array_map( 'esc_url', $frame['photos'] ?? [] ) ) );
			$wide     = ! empty( $frame['wide'] );
			$classes  = 'frameGallery' . ( $wide ? ' frameWide' : '' );
		?>
		<div
			class="<?php echo esc_attr( $classes ); ?>"
			data-svg="<?php echo $svg_url; ?>"
			data-photos="<?php echo esc_attr( wp_json_encode( $photos ) ); ?>"
		></div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<a href="#book" class="neonButton"><?php esc_html_e( 'See Availability', 'the-black-cap' ); ?></a>
</section>
