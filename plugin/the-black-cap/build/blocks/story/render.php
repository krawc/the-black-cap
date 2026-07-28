<?php
/**
 * Story block — server-side render.
 */

$title = $attributes['title'] ?? 'Legendary';
$copy  = $attributes['copy']  ?? '';

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
		</div>
	</div>
</section>
