<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'The Black Cap Settings', 'the-black-cap' ),
		__( 'Black Cap', 'the-black-cap' ),
		'manage_options',
		'tbc-settings',
		'tbc_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'tbc_settings', 'tbc_instagram_access_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_instagram_user_id',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_tiktok_access_token',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
} );

function tbc_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'The Black Cap – API Settings', 'the-black-cap' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'tbc_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Instagram Access Token', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_instagram_access_token"
							value="<?php echo esc_attr( get_option( 'tbc_instagram_access_token' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Long-lived token from the Instagram Graph API (expires every ~60 days — use a token refresh service).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Instagram User ID', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_instagram_user_id"
							value="<?php echo esc_attr( get_option( 'tbc_instagram_user_id' ) ); ?>"
							class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Your numeric Instagram Business/Creator account ID (find it via GET /me with your token).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'TikTok Access Token', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_tiktok_access_token"
							value="<?php echo esc_attr( get_option( 'tbc_tiktok_access_token' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'OAuth access token from developers.tiktok.com (Content Posting API or Display API).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />
		<h2><?php esc_html_e( 'Cache', 'the-black-cap' ); ?></h2>
		<p><?php esc_html_e( 'Instagram and TikTok post lists are cached for 1 hour. Clear the cache to fetch fresh data immediately.', 'the-black-cap' ); ?></p>
		<button id="tbc-clear-cache" class="button button-secondary">
			<?php esc_html_e( 'Clear API cache', 'the-black-cap' ); ?>
		</button>
		<span id="tbc-cache-msg" style="margin-left:1rem;color:green;display:none"></span>

		<script>
		document.getElementById('tbc-clear-cache').addEventListener('click', function() {
			var btn = this;
			btn.disabled = true;
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=tbc_clear_cache&_ajax_nonce=<?php echo esc_js( wp_create_nonce( 'tbc_clear_cache' ) ); ?>',
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				var msg = document.getElementById('tbc-cache-msg');
				msg.textContent = data.data;
				msg.style.display = 'inline';
				btn.disabled = false;
				setTimeout(function() { msg.style.display = 'none'; }, 3000);
			});
		});
		</script>

		<hr />
		<h2><?php esc_html_e( 'How to obtain API credentials', 'the-black-cap' ); ?></h2>
		<h3><?php esc_html_e( 'Instagram (Meta Graph API)', 'the-black-cap' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to developers.facebook.com → My Apps → Create App (Business type).', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Add the "Instagram Graph API" product.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Under Instagram → API Setup, connect your Instagram Business/Creator account.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Generate a short-lived token and exchange it for a long-lived token (60-day expiry). Use a cron job or service like token.co to auto-refresh.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Find your User ID: GET https://graph.instagram.com/me?access_token=YOUR_TOKEN', 'the-black-cap' ); ?></li>
		</ol>
		<h3><?php esc_html_e( 'TikTok (Display API)', 'the-black-cap' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to developers.tiktok.com → Manage Apps → Create App.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Enable the "Video List" scope under the Display API product.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Complete the OAuth flow for your TikTok account to get an access token.', 'the-black-cap' ); ?></li>
		</ol>
	</div>
	<?php
}
