<?php
/**
 * Hero / Navigation block — server-side render.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    InnerBlocks content (unused).
 * @var WP_Block $block    Block instance.
 */

$address          = $attributes['address']  ?? '171 Camden High Street, London NW1 7JY';
$phone            = $attributes['phone']    ?? '020 7428 2721';
$email            = $attributes['email']    ?? 'Sassy@blackcapcamden.co.uk';
$menu_slug        = $attributes['menuSlug'] ?? 'primary';
$social_tiktok    = esc_url( get_option( 'tbc_social_tiktok',    '' ) );
$social_instagram = esc_url( get_option( 'tbc_social_instagram', '' ) );
$social_facebook  = esc_url( get_option( 'tbc_social_facebook',  '' ) );

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

	<button
		class="menuCue"
		type="button"
		id="tbc-menu-cue"
		aria-label="<?php esc_attr_e( 'Open menu', 'the-black-cap' ); ?>"
	>
		<svg class="menuCue__chevron" viewBox="0 0 22 13" fill="none" aria-hidden="true"><polyline points="1,12 11,1 21,12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<span class="menuCue__label"><?php esc_html_e( 'tap for menu', 'the-black-cap' ); ?></span>
		<svg class="menuCue__chevron" viewBox="0 0 22 13" fill="none" aria-hidden="true"><polyline points="1,12 11,1 21,12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>

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

	<?php if ( $social_tiktok || $social_instagram || $social_facebook ) : ?>
	<div class="heroSocial">
		<?php if ( $social_instagram ) : ?>
		<a href="<?php echo $social_instagram; ?>" class="shareBtn heroSocial__link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
			<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
		</a>
		<?php endif; ?>
		<?php if ( $social_tiktok ) : ?>
		<a href="<?php echo $social_tiktok; ?>" class="shareBtn heroSocial__link" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
			<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.79 1.54V6.79a4.85 4.85 0 0 1-1.02-.1z"/></svg>
		</a>
		<?php endif; ?>
		<?php if ( $social_facebook ) : ?>
		<a href="<?php echo $social_facebook; ?>" class="shareBtn heroSocial__link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
			<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>

</section>
