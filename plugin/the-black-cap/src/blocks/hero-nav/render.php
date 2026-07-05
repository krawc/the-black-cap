<?php
/**
 * Hero / Navigation block — server-side render.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    InnerBlocks content (unused).
 * @var WP_Block $block    Block instance.
 */

$address   = $attributes['address']  ?? '171 Camden High Street, London NW1 7JY';
$phone     = $attributes['phone']    ?? '020 7428 2721';
$email     = $attributes['email']    ?? 'Sassy@blackcapcamden.co.uk';
$menu_slug = $attributes['menuSlug'] ?? 'primary';

// Colour / position presets — one per orbital slot (max 5).
$presets = [
	[ 'color' => '#D65CFF', 'x' => '-16rem',  'y' => '-10.4rem', 'mx' => '-7.2rem', 'my' => '-6.2rem'  ],
	[ 'color' => '#3F7CFF', 'x' => '-9.8rem', 'y' => '-14.6rem', 'mx' => '-4.1rem', 'my' => '-9.7rem'  ],
	[ 'color' => '#64F4FF', 'x' => '0rem',    'y' => '-16.8rem', 'mx' => '0rem',    'my' => '-13.2rem' ],
	[ 'color' => '#79FF5A', 'x' => '9.8rem',  'y' => '-14.6rem', 'mx' => '4.1rem',  'my' => '-9.7rem'  ],
	[ 'color' => '#FFF84A', 'x' => '16rem',   'y' => '-10.4rem', 'mx' => '7.2rem',  'my' => '-6.2rem'  ],
];

// Resolve menu slug → items.
$menu_obj = wp_get_nav_menu_object( $menu_slug );
$nav_items = $menu_obj ? wp_get_nav_menu_items( $menu_obj->term_id ) : [];
$nav_items = array_values( array_slice( $nav_items ?: [], 0, 5 ) );

$flame_url = esc_url( TBC_PLUGIN_URL . '/assets/svg/simple_flame_animated.svg' );
?>
<section class="hero" aria-labelledby="tbc-hero-title">

	<div class="logoOrbit" id="tbc-logo-orbit">
		<button
			class="logoButton"
			type="button"
			aria-expanded="false"
			aria-controls="rainbow-menu"
			id="tbc-logo-btn"
		>
			<object
				class="flame"
				data="<?php echo $flame_url; ?>"
				type="image/svg+xml"
				aria-label="<?php esc_attr_e( 'Animated neon Black Cap logo', 'the-black-cap' ); ?>"
			>
				<?php esc_html_e( 'Animated neon Black Cap logo', 'the-black-cap' ); ?>
			</object>
		</button>

		<?php if ( $nav_items ) : ?>
		<nav class="rainbowMenu" id="rainbow-menu" aria-label="<?php esc_attr_e( 'Main pages', 'the-black-cap' ); ?>">
			<?php foreach ( $nav_items as $i => $item ) :
				$p = $presets[ $i ];
			?>
			<a
				class="rainbowItem"
				data-index="<?php echo (int) $i; ?>"
				href="<?php echo esc_url( $item->url ); ?>"
				style="
					--item-color: <?php echo esc_attr( $p['color'] ); ?>;
					--item-index: <?php echo (int) $i; ?>;
					--item-x: <?php echo esc_attr( $p['x'] ); ?>;
					--item-y: <?php echo esc_attr( $p['y'] ); ?>;
					--mobile-item-x: <?php echo esc_attr( $p['mx'] ); ?>;
					--mobile-item-y: <?php echo esc_attr( $p['my'] ); ?>;
				"
			>
				<?php echo esc_html( $item->title ); ?>
			</a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	</div>

	<div class="venueInfo" aria-label="<?php esc_attr_e( 'Venue details', 'the-black-cap' ); ?>">
		<a href="https://maps.google.com/?q=<?php echo rawurlencode( $address ); ?>">
			<?php echo esc_html( $address ); ?>
		</a>
		<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
			<?php echo esc_html( $phone ); ?>
		</a>
		<a href="mailto:<?php echo esc_attr( $email ); ?>">
			<?php echo esc_html( $email ); ?>
		</a>
	</div>

</section>
