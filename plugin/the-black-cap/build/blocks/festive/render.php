<?php
/**
 * Festive (Christmas) block — server-side render.
 *
 * Neon animated tree intro, three-column info block, and a closing
 * Santa/contact strip. See src/main.jsx (React site) for the source
 * this was ported from — keep the two in sync when the campaign copy
 * changes.
 */

$heading        = $attributes['heading']       ?? 'Festive';
$subtitle       = $attributes['subtitle']      ?? 'Celebration at The Black Cap';
$intro          = $attributes['intro']         ?? '';
$close_headline = $attributes['closeHeadline'] ?? '';
$email          = $attributes['email']         ?? 'Sassy@blackcapcamden.co.uk';
$show_shimmer   = $attributes['showShimmer']   ?? true;

$svg_base = TBC_PLUGIN_URL . '/assets/svg/xmas';

// Strokes lifted from assets/svg/xmas/tree.svg (viewBox 0 0 105 144). The
// three green paths form the tree's own outline, not a light string — they
// stay solidly lit ('static') so their overlapping strokes never cross-fade
// into each other. Everything else is an ornament/light and flickers on its
// own randomised loop.
$festive_tree_strokes = [
	[ 'd' => 'M70.665 85L81.665 103.5C82.665 105.5 83.165 113.5 75.165 114.5C67.165 115.5 65.165 112 52.165 112C41.165 112 36.165 114.5 31.165 114.5C26.165 114.5 21.165 109 23.665 103.5C25.665 99.1 31.831 89.3333 34.6641 85', 'color' => '#5CFA00', 'static' => true ],
	[ 'd' => 'M42.1634 59L31.6634 77.5C30.83 79.5 30.2634 83.8 34.6634 85C40.1634 86.5 45.6641 84 52.1641 84C58.6641 84 68.1641 86 70.6641 85.5C73.1641 85 74.6641 80.5 73.1641 77.5C71.9641 75.1 65.6641 64.1667 62.6641 59', 'color' => '#5CFA00', 'static' => true ],
	[ 'd' => 'M47.6594 39.5C44.8261 44.1667 39.0594 53.8 38.6594 55C38.1594 56.5 39.6594 59.5 41.6594 59.5C43.6594 59.5 48.1602 58.5 52.1602 58.5C56.1602 58.5 59.6602 59.5 63.1602 59.5C65.9602 59.5 65.9935 56.5 65.6602 55L57.1602 39.5', 'color' => '#5CFA00', 'static' => true ],
	// Tree-topper star — stays solidly lit like the outline, not part of the
	// blinking light string (a flickering star faded low enough that the
	// always-on green outline showed through it, reading as "behind").
	[ 'd' => 'M51.6094 38.8698L47.6641 41.5L46.7348 42.1195C45.985 42.6194 45.0085 41.9502 45.204 41.0705L46.5339 35.0857C46.614 34.7252 46.4889 34.3499 46.2085 34.1095L41.4127 29.9988C40.7486 29.4296 41.0837 28.3423 41.953 28.2457L48.0806 27.5648C48.4409 27.5248 48.7512 27.293 48.8919 26.959L51.6804 20.3362C52.0325 19.5 53.2267 19.5272 53.5404 20.3786L55.9632 26.9548C56.0874 27.2919 56.3828 27.5365 56.7372 27.5955L62.9286 28.6274C63.7858 28.7703 64.0654 29.8632 63.3821 30.4001L58.6301 34.1339C58.3427 34.3596 58.2014 34.7241 58.2615 35.0846L59.2846 41.2232C59.4285 42.0867 58.4719 42.7052 57.7435 42.2196L52.7188 38.8698C52.3829 38.6459 51.9453 38.6459 51.6094 38.8698Z', 'color' => '#FFE101', 'static' => true ],
	[ 'd' => 'M29.5 106.5C33.1667 105.333 42.7 103 51.5 103C60.3 103 71.1667 105.333 75.5 106.5', 'color' => '#00EFEF', 'delay' => '1.8s', 'duration' => '6.5s' ],
	[ 'd' => 'M35 95C37.8696 94.3333 45.3304 93 52.2174 93C59.1043 93 67.6087 94.3333 71 95', 'color' => '#EF00C7', 'delay' => '0.7s', 'duration' => '5.8s' ],
	[ 'd' => 'M38 78C40.3116 77.3333 46.3217 76 51.8696 76C57.4174 76 64.2681 77.3333 67 78', 'color' => '#FF9532', 'delay' => '3.1s', 'duration' => '7.1s' ],
	[ 'd' => 'M42 69C43.6739 68.6667 48.0261 68 52.0435 68C56.0609 68 61.0217 68.6667 63 69', 'color' => '#0F9BFF', 'delay' => '2s', 'duration' => '4.9s' ],
	[ 'd' => 'M47 52C47.7971 51.6667 49.8696 51 51.7826 51C53.6957 51 56.058 51.6667 57 52', 'color' => '#FF1317', 'delay' => '0.9s', 'duration' => '6.1s' ],
];

$intro_paragraphs = array_filter( array_map( 'trim', preg_split( "/\n\s*\n/", $intro ) ) );
?>
<div class="festiveWrapper">
<?php if ( $show_shimmer ) : ?>
<canvas id="sparkle-canvas" class="festiveSparkleCanvas" aria-hidden="true"></canvas>
<?php endif; ?>

<section class="festive" id="festive">
	<div class="festiveScene">
		<div class="festiveTreeBlock">
			<svg class="festiveTree" viewBox="0 0 105 144" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="<?php esc_attr_e( 'Neon Christmas tree', 'the-black-cap' ); ?>">
				<?php foreach ( $festive_tree_strokes as $stroke ) :
					$is_fill = substr( $stroke['d'], -1 ) === 'Z';
				?>
				<path
					d="<?php echo esc_attr( $stroke['d'] ); ?>"
					fill="<?php echo $is_fill ? esc_attr( $stroke['color'] ) : 'none'; ?>"
					stroke="<?php echo esc_attr( $stroke['color'] ); ?>"
					stroke-width="3"
					stroke-linecap="round"
					class="festiveTreeStroke<?php echo ! empty( $stroke['static'] ) ? ' festiveTreeStroke--static' : ''; ?>"
					style="--glow: <?php echo esc_attr( $stroke['color'] ); ?>;<?php echo empty( $stroke['static'] ) ? ' animation-delay: ' . esc_attr( $stroke['delay'] ) . '; animation-duration: ' . esc_attr( $stroke['duration'] ) . ';' : ''; ?>"
				></path>
				<?php endforeach; ?>
			</svg>
		</div>
		<div class="festiveRight">
			<h2 class="festiveTitle"><?php echo esc_html( $heading ); ?></h2>
			<p class="festiveSubtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php foreach ( $intro_paragraphs as $paragraph ) : ?>
			<p class="festiveCopy"><?php echo wp_kses_post( nl2br( esc_html( $paragraph ) ) ); ?></p>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="festiveColumns" id="festive-info">
	<div class="festiveColumnsGrid">
		<div class="festiveColumn">
			<img src="<?php echo esc_url( "{$svg_base}/cane.svg" ); ?>" class="festiveCane" style="--cane-rotate: -32deg;" alt="" />
			<h3 class="festiveColumnTitle"><?php esc_html_e( 'Two Bars, Twice the Christmas Magic', 'the-black-cap' ); ?></h3>
			<p class="festiveColumnCopy"><?php esc_html_e( 'With two fabulous bars, there’s plenty of room to get merry, raise a glass and celebrate in true Christmas style.', 'the-black-cap' ); ?></p>
			<p class="festiveColumnCopy"><span class="festiveBarName"><?php esc_html_e( 'Lily’s Bar', 'the-black-cap' ); ?></span> <?php esc_html_e( 'brings the festive sparkle, with Christmas drinks, seasonal cheer and plenty of opportunities to get into the Christmas spirit.', 'the-black-cap' ); ?></p>
			<p class="festiveColumnCopy"><span class="festiveBarName"><?php esc_html_e( 'The Shufflewick Bar', 'the-black-cap' ); ?></span> <?php esc_html_e( 'offers a cosier festive escape, perfect for gathering your favourite people, sipping Christmas drinks and keeping the celebrations going.', 'the-black-cap' ); ?></p>
		</div>

		<div class="festiveColumn">
			<img src="<?php echo esc_url( "{$svg_base}/cane.svg" ); ?>" class="festiveCane" style="--cane-rotate: 24deg;" alt="" />
			<h3 class="festiveColumnTitle"><?php esc_html_e( 'Perfect for Your Christmas Celebration', 'the-black-cap' ); ?></h3>
			<ul class="festiveList">
				<li><?php esc_html_e( 'Christmas parties', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'Festive drinks', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'Christmas group bookings', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'Christmas gatherings', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'Christmas nights out', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'Festive celebrations', 'the-black-cap' ); ?></li>
				<li><?php esc_html_e( 'End-of-year Christmas celebrations', 'the-black-cap' ); ?></li>
			</ul>
			<p class="festiveColumnCopy"><?php esc_html_e( 'So grab your Christmas crew, dust off your most outrageous festive outfit, pile on the glitter and get ready to celebrate Christmas in the most fabulous way possible.', 'the-black-cap' ); ?></p>
		</div>

		<div class="festiveColumn">
			<img src="<?php echo esc_url( "{$svg_base}/cane.svg" ); ?>" class="festiveCane" style="--cane-rotate: -16deg;" alt="" />
			<h3 class="festiveColumnTitle"><?php esc_html_e( 'Merry, Camp & Fabulous', 'the-black-cap' ); ?></h3>
			<p class="festiveColumnCopy"><?php esc_html_e( 'Because this Christmas, we’re not just making it merry.', 'the-black-cap' ); ?></p>
			<p class="festiveColumnCopy festiveColumnCopyStrong"><?php esc_html_e( 'We’re making it MERRY, CAMP AND FABULOUS!', 'the-black-cap' ); ?></p>
			<ul class="festiveShoutList">
				<li><span><?php esc_html_e( 'Tinsel?', 'the-black-cap' ); ?></span> <?php esc_html_e( 'YES.', 'the-black-cap' ); ?></li>
				<li><span><?php esc_html_e( 'Glitter?', 'the-black-cap' ); ?></span> <?php esc_html_e( 'OBVIOUSLY.', 'the-black-cap' ); ?></li>
				<li><span><?php esc_html_e( 'Christmas spirit?', 'the-black-cap' ); ?></span> <?php esc_html_e( 'ABSOLUTELY.', 'the-black-cap' ); ?></li>
				<li><span><?php esc_html_e( 'Being subtle?', 'the-black-cap' ); ?></span> <?php esc_html_e( 'NEVER.', 'the-black-cap' ); ?></li>
			</ul>
		</div>
	</div>
</section>

<section class="festiveClose" id="festive-contact">
	<div class="festiveSantaRow">
		<img src="<?php echo esc_url( "{$svg_base}/santa.svg" ); ?>" class="festiveSanta" alt="" />
		<img src="<?php echo esc_url( "{$svg_base}/santa.svg" ); ?>" class="festiveSanta" alt="" />
		<img src="<?php echo esc_url( "{$svg_base}/santa.svg" ); ?>" class="festiveSanta" alt="" />
	</div>
	<p class="festiveCloseHeadline">
		<?php
		// Line break after every "Make it X." sentence.
		$sentences = array_filter( array_map( 'trim', explode( '.', $close_headline ) ) );
		echo wp_kses_post( implode( '.<br />', array_map( 'esc_html', $sentences ) ) . ( $sentences ? '.' : '' ) );
		?>
	</p>
	<p class="festiveCloseCopy">
		<?php esc_html_e( 'For Christmas booking enquiries, contact us here:', 'the-black-cap' ); ?>
		<a href="<?php echo esc_url( 'mailto:' . $email ); ?>" class="festiveCloseEmail"><?php echo esc_html( $email ); ?></a>
	</p>
</section>

</div><!-- .festiveWrapper -->
