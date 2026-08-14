<?php
/**
 * Site Footer block — server-side render.
 *
 * Ported from the legacy theme's footer.php: a legal-links nav plus a
 * copyright byline. Links come from the "Footer Links" nav menu created by
 * the setup runner (inc/setup/class-setup-runner.php, step_nav_menus());
 * hardcoded fallbacks are used if that menu doesn't exist yet.
 *
 * @var array $attributes Block attributes.
 */

$menu_slug = $attributes['menuSlug'] ?? 'footer-links';
$address   = $attributes['address']  ?? '171 Camden High Street, London NW1 7JY';

$menu_obj  = $menu_slug ? wp_get_nav_menu_object( $menu_slug ) : false;
$nav_items = $menu_obj ? wp_get_nav_menu_items( $menu_obj->term_id ) : [];
?>
<footer class="siteFooter">
	<nav class="footerLinks" aria-label="<?php esc_attr_e( 'Legal', 'the-black-cap' ); ?>">
		<?php if ( $nav_items ) :
			foreach ( $nav_items as $item ) : ?>
				<a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a>
			<?php endforeach;
		else : ?>
			<a href="<?php echo esc_url( home_url( '/privacy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/cookies' ) ); ?>"><?php esc_html_e( 'Cookie Policy', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/terms' ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/accessibility' ) ); ?>"><?php esc_html_e( 'Accessibility', 'the-black-cap' ); ?></a>
		<?php endif; ?>
	</nav>
	<p class="footerCopy">
		&copy; <?php echo esc_html( current_time( 'Y' ) ); ?>
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: site name, 2: venue address */
				__( '%1$s, %2$s. Registered in England & Wales.', 'the-black-cap' ),
				get_bloginfo( 'name' ),
				$address
			)
		);
		?>
	</p>
</footer>
