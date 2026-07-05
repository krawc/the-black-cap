<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( ! function_exists( 'register_block_pattern' ) ) return;

	register_block_pattern_category( 'the-black-cap', [
		'label' => __( 'The Black Cap', 'the-black-cap' ),
	] );

	$base = TBC_PLUGIN_URL . '/assets/img/story';

	$b = static function ( string $name, array $attrs ): string {
		return '<!-- wp:the-black-cap/' . $name . ' '
			. json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. ' /-->';
	};

	$blocks = [
		$b( 'hero-nav', [
			'menuSlug' => 'primary',
			'address'  => '171 Camden High Street, London NW1 7JY',
			'phone'    => '020 7428 2721',
			'email'    => 'Sassy@blackcapcamden.co.uk',
		] ),
		$b( 'whats-on',   [ 'eventIds' => '', 'limit' => 8 ] ),
		$b( 'story', [
			'title' => 'Legendary',
			'copy'  => "The Black Cap isn't just a venue with a famous name - it's a building, a stage, and a community landmark.",
			'photos' => [
				[ 'url' => "{$base}/1.webp", 'scale' => 1.3,  'driftX' =>  1.2,  'driftY' => -12.5 ],
				[ 'url' => "{$base}/3.webp", 'scale' => 2.2,  'driftX' =>  11.0, 'driftY' =>   3.0 ],
				[ 'url' => "{$base}/4.webp", 'scale' => 2.45, 'driftX' =>  -5.4, 'driftY' =>  -9.0 ],
				[ 'url' => "{$base}/2.webp", 'scale' => 1.1,  'driftX' => -11.6, 'driftY' =>  14.5 ],
			],
		] ),
		$b( 'highlights', [
			'videoIds' => '7644927884900961558,7642689026490912003,7640829274840190240',
			'limit'    => 8,
		] ),
		$b( 'drink-menu', [ 'sections' => [] ] ),
		$b( 'our-rooms',  [ 'frames'   => [] ] ),
		$b( 'venue-hire', [ 'slots'    => [ [ 'venueId' => 0 ], [ 'venueId' => 0 ], [ 'venueId' => 0 ] ] ] ),
	];

	register_block_pattern( 'the-black-cap/front-page', [
		'title'       => __( 'Black Cap – Full Front Page', 'the-black-cap' ),
		'description' => __( 'Complete one-page layout. Run the Setup Import to populate all content.', 'the-black-cap' ),
		'categories'  => [ 'the-black-cap' ],
		'inserter'    => true,
		'content'     => implode( "\n\n", $blocks ),
	] );
} );
