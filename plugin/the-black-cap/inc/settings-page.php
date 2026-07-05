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
	register_setting( 'tbc_settings', 'tbc_eventbrite_token',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_eventbrite_org_id',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_tiktok_access_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
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
					<th scope="row"><?php esc_html_e( 'Eventbrite API Token', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_eventbrite_token"
							value="<?php echo esc_attr( get_option( 'tbc_eventbrite_token' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Private API key from your Eventbrite account (Account Settings → Developer Links → API Keys).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Eventbrite Organisation ID', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_eventbrite_org_id"
							value="<?php echo esc_attr( get_option( 'tbc_eventbrite_org_id', '3005226258349' ) ); ?>"
							class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Your numeric Eventbrite organisation ID. Visible in the URL when you open your organisation dashboard on Eventbrite.', 'the-black-cap' ); ?>
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
							<?php esc_html_e( 'OAuth access token from developers.tiktok.com (Display API).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />
		<h2><?php esc_html_e( 'Cache', 'the-black-cap' ); ?></h2>
		<p><?php esc_html_e( 'Eventbrite events and TikTok videos are cached for 1 hour. Clear the cache to fetch fresh data immediately.', 'the-black-cap' ); ?></p>
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

		<h3><?php esc_html_e( 'Eventbrite', 'the-black-cap' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Log in at eventbrite.com and go to Account Settings (top-right avatar menu).', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Under Developer Links, click API Keys and create a new key. Copy the Private Token.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'To find your Organisation ID, open your organiser dashboard — the number at the end of the URL is your org ID (e.g. eventbrite.com/o/black-cap-3005226258349 → 3005226258349).', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Paste the token and org ID above and save. Events marked as "current or future" will appear automatically.', 'the-black-cap' ); ?></li>
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
