<?php
defined( 'ABSPATH' ) || exit;

/* ── Register CPT ─────────────────────────────────────────────── */
add_action( 'init', function () {

	register_post_type( 'tbc_venue', [
		'labels' => [
			'name'               => 'Venues',
			'singular_name'      => 'Venue',
			'add_new_item'       => 'Add New Venue',
			'edit_item'          => 'Edit Venue',
			'new_item'           => 'New Venue',
			'not_found'          => 'No venues found',
			'not_found_in_trash' => 'No venues found in trash',
			'menu_name'          => 'Venues',
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'rest_base'    => 'tbc_venues',
		'supports'     => [ 'title' ],
		'menu_icon'    => 'dashicons-location-alt',
	] );

	register_post_meta( 'tbc_venue', 'tbc_venue_description', [
		'type'              => 'string',
		'single'            => true,
		'default'           => '',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_textarea_field',
	] );

	register_post_meta( 'tbc_venue', 'tbc_venue_image_ids', [
		'type'         => 'array',
		'single'       => true,
		'default'      => [],
		'show_in_rest' => [
			'schema' => [
				'type'  => 'array',
				'items' => [ 'type' => 'integer' ],
			],
		],
	] );
} );

/* ── Meta box ─────────────────────────────────────────────────── */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'tbc_venue_details',
		'Venue Details',
		'tbc_venue_meta_box_html',
		'tbc_venue',
		'normal',
		'high'
	);
} );

function tbc_venue_meta_box_html( WP_Post $post ): void {
	wp_nonce_field( 'tbc_venue_save', 'tbc_venue_nonce' );

	$desc = (string) ( get_post_meta( $post->ID, 'tbc_venue_description', true ) ?: '' );
	$ids  = (array)  ( get_post_meta( $post->ID, 'tbc_venue_image_ids',   true ) ?: [] );
	?>
	<style>
	#tbc-venue-img-grid { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; min-height:20px; }
	#tbc-venue-img-grid img { width:64px; height:64px; object-fit:cover; border-radius:4px; display:block; }
	</style>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="tbc_venue_description">Description</label></th>
			<td>
				<textarea id="tbc_venue_description" name="tbc_venue_description"
					rows="5" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">Images</th>
			<td>
				<div id="tbc-venue-img-grid">
					<?php foreach ( $ids as $id ) :
						$thumb = wp_get_attachment_image_url( (int) $id, 'thumbnail' );
						if ( ! $thumb ) continue;
					?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="">
					<?php endforeach; ?>
				</div>
				<input type="hidden" id="tbc-venue-image-ids" name="tbc_venue_image_ids"
					value="<?php echo esc_attr( implode( ',', array_map( 'intval', $ids ) ) ); ?>">
				<button type="button" class="button" id="tbc-venue-media-btn">
					<?php echo $ids ? 'Edit Images' : 'Add Images'; ?>
				</button>
				<span id="tbc-venue-img-count" style="margin-left:8px;color:#666;font-size:13px;">
					<?php echo count( $ids ); ?> image(s)
				</span>
			</td>
		</tr>
	</table>
	<?php
}

/* ── Save meta ────────────────────────────────────────────────── */
add_action( 'save_post_tbc_venue', function ( int $post_id ): void {
	if ( ! isset( $_POST['tbc_venue_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['tbc_venue_nonce'], 'tbc_venue_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, 'tbc_venue_description',
		sanitize_textarea_field( $_POST['tbc_venue_description'] ?? '' ) );

	$raw = sanitize_text_field( $_POST['tbc_venue_image_ids'] ?? '' );
	$ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );
	update_post_meta( $post_id, 'tbc_venue_image_ids', $ids );
} );

/* ── Enqueue media uploader on Venue edit screen ─────────────── */
add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
	global $post;
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
	if ( ! isset( $post ) || $post->post_type !== 'tbc_venue' ) return;

	wp_enqueue_media();
	wp_enqueue_script(
		'tbc-venue-admin',
		TBC_PLUGIN_URL . '/assets/js/venue-admin.js',
		[],
		filemtime( TBC_PLUGIN_DIR . 'assets/js/venue-admin.js' ),
		true
	);
} );
