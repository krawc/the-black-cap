<?php
$locations   = get_nav_menu_locations();
$menu_id     = $locations['footer'] ?? 0;
$footer_items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : false;
?>
<footer class="siteFooter">
	<nav class="footerLinks" aria-label="<?php esc_attr_e( 'Legal', 'the-black-cap' ); ?>">
		<?php if ( $footer_items ) :
			foreach ( $footer_items as $item ) : ?>
				<a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a>
			<?php endforeach;
		else : ?>
			<a href="<?php echo esc_url( home_url( '/privacy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/cookies' ) ); ?>"><?php esc_html_e( 'Cookie Policy', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/terms' ) ); ?>"><?php esc_html_e( 'Terms &amp; Conditions', 'the-black-cap' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/accessibility' ) ); ?>"><?php esc_html_e( 'Accessibility', 'the-black-cap' ); ?></a>
		<?php endif; ?>
	</nav>
	<p class="footerCopy">
		&copy; <?php echo esc_html( date( 'Y' ) ); ?>
		<?php bloginfo( 'name' ); ?>, 171 Camden High Street, London NW1 7JY.
		Registered in England &amp; Wales.
	</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
