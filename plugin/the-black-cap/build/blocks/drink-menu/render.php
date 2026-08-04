<?php
/**
 * Drink Menu block — server-side render.
 */

$heading  = $attributes['heading'] ?? 'The Menu';
$tabs     = $attributes['tabs']    ?? [];
$menu_svg = esc_url( TBC_PLUGIN_URL . '/assets/svg/neon-menu.svg' );
?>
<section class="menuSection" id="menu">
	<div class="menuScene">
		<div class="menuSvgBlock">
			<img src="<?php echo $menu_svg; ?>" class="menuSvg" alt="" />
		</div>
		<div class="menuRight">
			<h2 class="menuHeadline"><?php echo esc_html( $heading ); ?></h2>

			<?php if ( count( $tabs ) > 1 ) : ?>
			<div class="menuTabs" role="tablist">
				<?php foreach ( $tabs as $t_idx => $tab ) :
					$tab_id    = esc_attr( $tab['id']    ?? ( 'tab-' . $t_idx ) );
					$tab_label = esc_html( $tab['label'] ?? '' );
				?>
				<button
					class="menuTab<?php echo $t_idx === 0 ? ' menuTab--active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo $t_idx === 0 ? 'true' : 'false'; ?>"
					aria-controls="menuPanel-<?php echo $tab_id; ?>"
					data-tab="<?php echo $tab_id; ?>"
				><?php echo $tab_label; ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php foreach ( $tabs as $t_idx => $tab ) :
				$tab_id   = esc_attr( $tab['id'] ?? ( 'tab-' . $t_idx ) );
				$sections = $tab['sections'] ?? [];
			?>
			<div
				class="menuPanel"
				id="menuPanel-<?php echo $tab_id; ?>"
				role="tabpanel"
				<?php echo $t_idx > 0 ? 'hidden' : ''; ?>
			>
				<?php foreach ( $sections as $s_idx => $section ) :
					$category = esc_html( $section['category'] ?? '' );
					$note     = $section['note'] ?? '';
					$items    = $section['items'] ?? [];
					if ( ! $category && ! $items ) continue;

					$first   = reset( $items );
					$is_wine = $first && ! empty( $first['prices'] );
					$price_headers = [];
					if ( $is_wine ) {
						$price_headers = array_column( $first['prices'], 'label' );
					}
					$col_count = max( 1, count( $price_headers ) );

					$acc_id = 'menuAcc-' . $t_idx . '-' . $s_idx;
				?>
				<div class="menuAccordion">
					<button
						class="menuAccordion__trigger"
						type="button"
						aria-expanded="false"
						aria-controls="<?php echo $acc_id; ?>"
					><?php echo $category; ?><span class="menuAccordion__icon" aria-hidden="true"></span></button>
					<div class="menuAccordion__body" id="<?php echo $acc_id; ?>" hidden>

						<?php if ( $note ) : ?>
						<p class="menuCategoryNote"><?php echo esc_html( $note ); ?></p>
						<?php endif; ?>

						<?php if ( $is_wine ) : ?>
						<div class="menuWineGrid" style="--wine-cols:<?php echo $col_count; ?>" role="table" aria-label="<?php echo esc_attr( $category ); ?>">
							<div class="menuWineGrid__row" role="row">
								<span class="menuWineGrid__hname" role="columnheader"><span class="tbc-visually-hidden"><?php esc_html_e( 'Wine', 'the-black-cap' ); ?></span></span>
								<?php foreach ( $price_headers as $h ) : ?>
								<span class="menuWineGrid__hprice" role="columnheader"><?php echo esc_html( $h ); ?></span>
								<?php endforeach; ?>
							</div>
							<?php foreach ( $items as $item ) : ?>
							<div class="menuWineGrid__row" role="row">
								<div class="menuWineItem__info" role="rowheader">
									<span class="menuWineItem__name"><?php echo esc_html( $item['name'] ?? '' ); ?></span>
								</div>
								<?php foreach ( $item['prices'] ?? [] as $p ) : ?>
								<span class="menuWineItem__price" role="cell"><?php echo esc_html( $p['value'] ?? '' ); ?></span>
								<?php endforeach; ?>
							</div>
							<?php endforeach; ?>
						</div>
						<?php else : ?>
						<?php foreach ( $items as $item ) :
							$name  = esc_html( $item['name']  ?? '' );
							$price = esc_html( $item['price'] ?? '' );
							$deal  = esc_html( $item['deal']  ?? '' );
							if ( ! $name ) continue;
						?>
						<div class="menuItem">
							<span class="menuItemName"><?php echo $name; ?></span>
							<span class="menuItemPrice">
								<?php echo $price; ?>
								<?php if ( $deal ) : ?><span class="menuItemDeal"><?php echo $deal; ?></span><?php endif; ?>
							</span>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>

					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>

		</div>
	</div>
</section>
