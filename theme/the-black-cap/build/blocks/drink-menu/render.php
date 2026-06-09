<?php
/**
 * Drink Menu block — server-side render.
 */

$sections = $attributes['sections'] ?? [];
$menu_svg = esc_url( get_template_directory_uri() . '/assets/svg/neon-menu.svg' );
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
						$name  = esc_html( $item['name']  ?? '' );
						$price = esc_html( $item['price'] ?? '' );
						if ( ! $name ) continue;
					?>
					<div class="menuItem">
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
