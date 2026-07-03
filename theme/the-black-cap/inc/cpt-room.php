<?php
defined( 'ABSPATH' ) || exit;

/* ── Register CPT ─────────────────────────────────────────────── */
add_action( 'init', function () {

	register_post_type( 'tbc_room', [
		'labels' => [
			'name'               => 'Rooms',
			'singular_name'      => 'Room',
			'add_new_item'       => 'Add New Room',
			'edit_item'          => 'Edit Room',
			'new_item'           => 'New Room',
			'not_found'          => 'No rooms found',
			'not_found_in_trash' => 'No rooms found in trash',
			'menu_name'          => 'Rooms',
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'rest_base'    => 'tbc_rooms',
		'supports'     => [ 'title' ],
		'menu_icon'    => 'dashicons-building',
	] );

	foreach ( [ 'tbc_room_description', 'tbc_room_booking_link' ] as $key ) {
		register_post_meta( 'tbc_room', $key, [
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => $key === 'tbc_room_booking_link'
				? 'esc_url_raw'
				: 'sanitize_textarea_field',
		] );
	}

	register_post_meta( 'tbc_room', 'tbc_room_image_ids', [
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
		'tbc_room_details',
		'Room Details',
		'tbc_room_meta_box_html',
		'tbc_room',
		'normal',
		'high'
	);
} );

function tbc_room_meta_box_html( WP_Post $post ): void {
	wp_nonce_field( 'tbc_room_save', 'tbc_room_nonce' );

	$desc = (string) ( get_post_meta( $post->ID, 'tbc_room_description',  true ) ?: '' );
	$link = (string) ( get_post_meta( $post->ID, 'tbc_room_booking_link', true ) ?: '' );
	$ids  = (array)  ( get_post_meta( $post->ID, 'tbc_room_image_ids',    true ) ?: [] );
	?>
	<style>
	#tbc-room-img-grid { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; min-height:20px; }
	#tbc-room-img-grid img { width:64px; height:64px; object-fit:cover; border-radius:4px; display:block; }
	</style>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="tbc_room_description">Description</label></th>
			<td>
				<textarea id="tbc_room_description" name="tbc_room_description"
					rows="5" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="tbc_room_booking_link">Booking Link</label></th>
			<td>
				<input type="url" id="tbc_room_booking_link" name="tbc_room_booking_link"
					value="<?php echo esc_attr( $link ); ?>" class="large-text">
			</td>
		</tr>
		<tr>
			<th scope="row">Images</th>
			<td>
				<div id="tbc-room-img-grid">
					<?php foreach ( $ids as $id ) :
						$thumb = wp_get_attachment_image_url( (int) $id, 'thumbnail' );
						if ( ! $thumb ) continue;
					?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="">
					<?php endforeach; ?>
				</div>
				<input type="hidden" id="tbc-room-image-ids" name="tbc_room_image_ids"
					value="<?php echo esc_attr( implode( ',', array_map( 'intval', $ids ) ) ); ?>">
				<button type="button" class="button" id="tbc-room-media-btn">
					<?php echo $ids ? 'Edit Images' : 'Add Images'; ?>
				</button>
				<span id="tbc-room-img-count" style="margin-left:8px;color:#666;font-size:13px;">
					<?php echo count( $ids ); ?> image(s)
				</span>
			</td>
		</tr>
	</table>
	<?php
}

/* ── Save meta ────────────────────────────────────────────────── */
add_action( 'save_post_tbc_room', function ( int $post_id ): void {
	if ( ! isset( $_POST['tbc_room_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['tbc_room_nonce'], 'tbc_room_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, 'tbc_room_description',
		sanitize_textarea_field( $_POST['tbc_room_description'] ?? '' ) );
	update_post_meta( $post_id, 'tbc_room_booking_link',
		esc_url_raw( $_POST['tbc_room_booking_link'] ?? '' ) );

	$raw = sanitize_text_field( $_POST['tbc_room_image_ids'] ?? '' );
	$ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );
	update_post_meta( $post_id, 'tbc_room_image_ids', $ids );
} );

/* ── Enqueue media uploader on Room edit screen ───────────────── */
add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
	global $post;
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
	if ( ! isset( $post ) || $post->post_type !== 'tbc_room' ) return;

	wp_enqueue_media();
	wp_enqueue_script(
		'tbc-room-admin',
		get_template_directory_uri() . '/assets/js/room-admin.js',
		[],
		filemtime( get_template_directory() . '/assets/js/room-admin.js' ),
		true
	);
} );
