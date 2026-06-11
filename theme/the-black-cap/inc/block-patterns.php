<?php
defined( 'ABSPATH' ) || exit;

/**
 * Build the complete front-page block markup.
 *
 * @param string|null $theme_uri  Override the theme URI (used by the setup CLI script).
 * @return string  Serialised block HTML ready for post_content.
 */
function tbc_front_page_pattern_content( $theme_uri = null ) {
	if ( ! $theme_uri ) {
		$theme_uri = get_template_directory_uri();
	}

	$s1 = $theme_uri . '/assets/img/story/1.webp';
	$s2 = $theme_uri . '/assets/img/story/2.webp';
	$s3 = $theme_uri . '/assets/img/story/3.webp';
	$s4 = $theme_uri . '/assets/img/story/4.webp';

	// Serialise a self-closing dynamic block comment.
	$b = function ( $name, array $attrs ) {
		$json = json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return "<!-- wp:the-black-cap/{$name} {$json} /-->";
	};

	$blocks = [

		$b( 'hero-nav', [
			'menuSlug' => 'primary',
			'address'  => '171 Camden High Street, London NW1 7JY',
			'phone'    => '020 7428 2721',
			'email'    => 'Sassy@blackcapcamden.co.uk',
		] ),

		$b( 'whats-on', [
			'shortcodes' => 'DX_5LwekcVa,DYzKkfHMC7z,DYxiCs7x4rm,DYxhePkxa1n,DYxgzTsRa8A,DYxgfJNR2pn,DYfNVZ_De71,DYfNPIKFP5e',
			'limit'      => 8,
		] ),

		$b( 'story', [
			'title' => 'Legendary',
			'copy'  => "The Black Cap has been Camden’s queer heartbeat since 1967. Two bars, a legendary terrace, and a performance room that’s seen more feather boas than the law should allow. Shufflewick’s is where the pints happen. The stage is where the magic does.",
			'photos' => [
				[ 'url' => $s1, 'scale' => 1.3,  'driftX' =>  1.2,  'driftY' => -12.5 ],
				[ 'url' => $s3, 'scale' => 2.2,  'driftX' =>  11.0, 'driftY' =>   3.0 ],
				[ 'url' => $s4, 'scale' => 2.45, 'driftX' =>  -5.4, 'driftY' =>  -9.0 ],
				[ 'url' => $s2, 'scale' => 1.1,  'driftX' => -11.6, 'driftY' =>  14.5 ],
			],
		] ),

		$b( 'highlights', [
			'videoIds' => '7644927884900961558,7642689026490912003,7640829274840190240,7640504644887776544,7640442725908712737,7640087100393606433,7639762417546824992,7639360399963360545',
			'limit'    => 8,
		] ),

		$b( 'drink-menu', [
			'sections' => [
				[ 'category' => 'Draught', 'items' => [
					[ 'name' => 'Guinness',              'price' => '£6.50' ],
					[ 'name' => 'Camden Hells Lager',    'price' => '£6.00' ],
					[ 'name' => 'Camden Pale Ale',       'price' => '£6.20' ],
					[ 'name' => 'Meantime London Lager', 'price' => '£6.20' ],
				] ],
				[ 'category' => 'Wine', 'items' => [
					[ 'name' => 'House glass',  'price' => '£7.00'  ],
					[ 'name' => 'House carafe', 'price' => '£22.00' ],
					[ 'name' => 'Prosecco',     'price' => '£9.00'  ],
				] ],
				[ 'category' => 'Cocktails', 'items' => [
					[ 'name' => 'Negroni',          'price' => '£12.00' ],
					[ 'name' => 'Aperol Spritz',    'price' => '£11.00' ],
					[ 'name' => 'Espresso Martini', 'price' => '£12.00' ],
					[ 'name' => 'Pornstar Martini', 'price' => '£12.00' ],
				] ],
				[ 'category' => 'Spirits & Mixers', 'items' => [
					[ 'name' => 'Single & mixer', 'price' => 'from £8'  ],
					[ 'name' => 'Double & mixer', 'price' => 'from £11' ],
					[ 'name' => 'Shot',           'price' => 'from £4'  ],
				] ],
				[ 'category' => 'Soft Drinks & Low/No', 'items' => [
					[ 'name' => 'Soft drinks',      'price' => 'from £3.50' ],
					[ 'name' => 'Low & no-alcohol', 'price' => 'from £4.50' ],
				] ],
			],
		] ),

		$b( 'our-rooms', [
			'frames' => [
				[ 'svgFile' => 'Frame 1.svg', 'photos' => [ $s1 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 2.svg', 'photos' => [ $s2 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 3.svg', 'photos' => [ $s3 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 4.svg', 'photos' => [ $s4 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 5.svg', 'photos' => [ $s1 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 6.svg', 'photos' => [ $s2, $s3, $s4 ], 'wide' => true  ],
				[ 'svgFile' => 'Frame 7.svg', 'photos' => [ $s2 ],           'wide' => false ],
				[ 'svgFile' => 'Frame 8.svg', 'photos' => [ $s1, $s3, $s4 ], 'wide' => true  ],
			],
		] ),

	];

	return implode( "\n\n", $blocks );
}

/* ── Block pattern registration ───────────────────────────────── */
add_action( 'init', function () {
	if ( ! function_exists( 'register_block_pattern' ) ) return;

	register_block_pattern_category( 'the-black-cap', [
		'label' => __( 'The Black Cap', 'the-black-cap' ),
	] );

	register_block_pattern( 'the-black-cap/front-page', [
		'title'       => __( 'Black Cap – Full Front Page', 'the-black-cap' ),
		'description' => __( 'Complete one-page layout: hero nav, Instagram, story, TikTok highlights, drinks menu, and rooms gallery.', 'the-black-cap' ),
		'categories'  => [ 'the-black-cap' ],
		'inserter'    => true,
		'content'     => tbc_front_page_pattern_content(),
	] );
} );
