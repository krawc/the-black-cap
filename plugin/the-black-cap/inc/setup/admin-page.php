<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_menu_page(
		__( 'Black Cap Setup', 'the-black-cap' ),
		__( 'Black Cap Setup', 'the-black-cap' ),
		'manage_options',
		'tbc-setup',
		'tbc_render_setup_page',
		'dashicons-store',
		3
	);
} );

add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
	if ( $hook !== 'toplevel_page_tbc-setup' ) return;

	wp_enqueue_style(
		'tbc-setup-admin',
		TBC_PLUGIN_URL . '/assets/css/setup-admin.css',
		[],
		TBC_PLUGIN_VERSION
	);
	wp_enqueue_script(
		'tbc-setup-admin',
		TBC_PLUGIN_URL . '/assets/js/setup-admin.js',
		[],
		TBC_PLUGIN_VERSION,
		true
	);
	wp_localize_script( 'tbc-setup-admin', 'tbcSetup', [
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'tbc_setup' ),
		'steps'   => TBC_Setup_Runner::STEPS,
		'labels'  => [
			'room_cdn_images' => 'Downloading room images from CDN',
			'room_cpt_posts'  => 'Creating Room posts',
			'timeline_images' => 'Uploading timeline images',
			'venue_images'    => 'Uploading venue images',
			'venue_cpt_posts' => 'Creating Venue posts',
			'front_page'      => 'Building front page',
			'nav_menus'       => 'Setting up navigation menus',
			'api_defaults'    => 'Seeding API defaults',
		],
	] );
} );

function tbc_render_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;
	?>
	<div class="wrap tbc-setup-wrap">
		<h1 class="tbc-setup-heading">
			<span class="tbc-setup-heading__icon">&#9679;</span>
			The Black Cap &mdash; Content Setup
		</h1>

		<div class="tbc-setup-card">
			<p class="tbc-setup-intro">
				This tool imports all content into your WordPress installation: room images,
				timeline photos, venue images, CPT posts, the front page with all blocks,
				navigation menus, and API defaults.
			</p>
			<p class="tbc-setup-intro">
				<strong>Safe to run multiple times</strong> &mdash; each step is idempotent
				and skips work already done.
			</p>

			<div class="tbc-setup-actions">
				<button id="tbc-setup-start" class="button button-primary button-hero">
					Run Setup Import
				</button>
				<button id="tbc-setup-reset" class="button button-secondary" style="display:none">
					Run Again
				</button>
			</div>

			<div id="tbc-setup-progress-wrap" style="display:none">
				<div class="tbc-progress-header">
					<span id="tbc-progress-label">Starting&hellip;</span>
					<span id="tbc-progress-fraction"></span>
				</div>
				<div class="tbc-progress-bar-track">
					<div class="tbc-progress-bar-fill" id="tbc-progress-fill"></div>
				</div>
			</div>

			<div id="tbc-setup-console" style="display:none" aria-live="polite" aria-label="Setup log output">
				<div class="tbc-console-inner" id="tbc-console-inner"></div>
			</div>

			<div id="tbc-setup-done" class="tbc-setup-done" style="display:none">
				<span class="tbc-setup-done__icon">&#10003;</span>
				Setup complete &mdash; all steps finished successfully.
			</div>

			<div id="tbc-setup-error" class="tbc-setup-error" style="display:none">
				<strong>Error:</strong> <span id="tbc-setup-error-msg"></span>
			</div>
		</div>
	</div>
	<?php
}
