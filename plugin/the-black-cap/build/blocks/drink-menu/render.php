<?php
/**
 * Drink Menu block — server-side render.
 */

$sections = $attributes['sections'] ?? [];
$menu_svg = esc_url( TBC_PLUGIN_URL . '/assets/svg/neon-menu.svg' );
?>
<section class="menuSection" id="menu">
	<div class="menuScene">
		<div class="menuSvgBlock">
			<img src="<?php echo $menu_svg; ?>" class="menuSvg" alt="" />
		</div>
		<div class="menuRight">
			<h2 class="menuHeadline">The Menu</h2>
			<?php if ( $sections ) : ?>
			<div class="menuList">
				<?php foreach ( $sections as $section ) :
					$category = esc_html( $section['category'] ?? '' );
					$items    = $section['items'] ?? [];
					if ( ! $category && ! $items ) continue;
				?>
				<div class="menuCategory">
					<?php if ( $category ) : ?>
					<p class="menuCategoryName"><?php echo $category; ?></p>
					<?php endif; ?>
					<?php foreach ( $items as $item ) :
						$name     = esc_html( $item['name']    ?? '' );
						$price    = esc_html( $item['price']   ?? '' );
						$image_id = (int) ( $item['imageId']   ?? 0 );
						if ( ! $name ) continue;
						$thumb_url = $image_id ? ( wp_get_attachment_image_url( $image_id, 'thumbnail' ) ?: wp_get_attachment_image_url( $image_id, 'full' ) ) : '';
						$full_url  = $image_id ? ( wp_get_attachment_image_url( $image_id, 'large' ) ?: wp_get_attachment_image_url( $image_id, 'full' ) ) : '';
					?>
					<div class="menuItem<?php echo $thumb_url ? ' menuItem--hasPhoto' : ''; ?>">
						<?php if ( $thumb_url ) : ?>
						<button class="menuItemThumb" type="button"
							data-full="<?php echo esc_url( $full_url ); ?>"
							data-alt="<?php echo esc_attr( $item['name'] ?? '' ); ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'View photo of %s', 'the-black-cap' ), $item['name'] ?? '' ) ); ?>"
						><img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy"></button>
						<?php endif; ?>
						<span class="menuItemName"><?php echo $name; ?></span>
						<?php if ( $price ) : ?>
						<span class="menuItemPrice"><?php echo $price; ?></span>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
