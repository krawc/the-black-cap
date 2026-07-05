<?php
/**
 * Story block — server-side render.
 */

$title    = $attributes['title']    ?? 'Legendary';
$copy     = $attributes['copy']     ?? '';
$photos   = $attributes['photos']   ?? [];
$parallax = $attributes['parallax'] ?? true;

$regina_url = esc_url( TBC_PLUGIN_URL . '/assets/svg/regina.svg' );
?>
<section class="content" id="story">
	<div class="legendaryScene">
		<div class="reginaBlock">
			<img src="<?php echo $regina_url; ?>" class="reginaSvg" alt="Regina Fong" />
		</div>
		<div class="legendaryRight">
			<h2 class="legendaryTitle"><?php echo wp_kses_post( $title ); ?></h2>
			<?php if ( $copy ) : ?>
			<p class="legendaryCopy"><?php echo wp_kses_post( $copy ); ?></p>
			<?php endif; ?>

			<?php if ( $photos ) : ?>
			<div class="photoRow<?php echo $parallax ? '' : ' photoRow--fade'; ?>" id="tbc-photo-row" data-story-mode="<?php echo $parallax ? 'parallax' : 'fade'; ?>">
				<?php foreach ( $photos as $photo ) :
					$scale  = (float) ( $photo['scale']  ?? 1 );
					$driftX = (float) ( $photo['driftX'] ?? 0 );
					$driftY = (float) ( $photo['driftY'] ?? 0 );
					$url    = esc_url( $photo['url'] ?? '' );
					if ( ! $url ) continue;
				?>
				<div
					class="photoPlaceholder"
					style="--h-scale: <?php echo $scale; ?>"
					data-drift-x="<?php echo $driftX; ?>"
					data-drift-y="<?php echo $driftY; ?>"
				>
					<img src="<?php echo $url; ?>" alt="" class="photoImg" />
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
