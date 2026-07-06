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
	register_setting( 'tbc_settings', 'tbc_eventbrite_token',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_eventbrite_org_id',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_tiktok_client_key',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_tiktok_client_secret',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_tiktok_refresh_token',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
	register_setting( 'tbc_settings', 'tbc_social_tiktok',         [ 'sanitize_callback' => 'esc_url_raw' ] );
	register_setting( 'tbc_settings', 'tbc_social_instagram',      [ 'sanitize_callback' => 'esc_url_raw' ] );
	register_setting( 'tbc_settings', 'tbc_social_facebook',       [ 'sanitize_callback' => 'esc_url_raw' ] );
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
					<th scope="row"><?php esc_html_e( 'TikTok Client Key', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_tiktok_client_key"
							value="<?php echo esc_attr( get_option( 'tbc_tiktok_client_key' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'App Key from developers.tiktok.com → your app → App info.', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'TikTok Client Secret', 'the-black-cap' ); ?></th>
					<td>
						<input type="password" name="tbc_tiktok_client_secret"
							value="<?php echo esc_attr( get_option( 'tbc_tiktok_client_secret' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'App Secret from the same page. Keep this private.', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'TikTok Refresh Token', 'the-black-cap' ); ?></th>
					<td>
						<input type="text" name="tbc_tiktok_refresh_token"
							value="<?php echo esc_attr( get_option( 'tbc_tiktok_refresh_token' ) ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Refresh token from the initial OAuth flow (valid 365 days; auto-rotated on each use).', 'the-black-cap' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'TikTok Profile URL', 'the-black-cap' ); ?></th>
					<td>
						<input type="url" name="tbc_social_tiktok"
							value="<?php echo esc_attr( get_option( 'tbc_social_tiktok' ) ); ?>"
							class="regular-text" placeholder="https://www.tiktok.com/@theblackcapcamden" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Instagram Profile URL', 'the-black-cap' ); ?></th>
					<td>
						<input type="url" name="tbc_social_instagram"
							value="<?php echo esc_attr( get_option( 'tbc_social_instagram' ) ); ?>"
							class="regular-text" placeholder="https://www.instagram.com/theblackcapcamden" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Facebook Page URL', 'the-black-cap' ); ?></th>
					<td>
						<input type="url" name="tbc_social_facebook"
							value="<?php echo esc_attr( get_option( 'tbc_social_facebook' ) ); ?>"
							class="regular-text" placeholder="https://www.facebook.com/theblackcapcamden" />
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
			<li><?php esc_html_e( 'Go to developers.tiktok.com → Manage Apps → Create App (web app).', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Under Products, add "Display API" and enable the video.list scope.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Copy the Client Key and Client Secret from App info and paste them above.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'Do the one-time OAuth authorisation flow to obtain the initial refresh token: redirect the TikTok account owner to the authorise URL, exchange the returned code at /v2/oauth/token/ (grant_type=authorization_code), and paste the refresh_token value above.', 'the-black-cap' ); ?></li>
			<li><?php esc_html_e( 'The plugin will automatically exchange the refresh token for a short-lived access token and rotate the refresh token on every use — no manual updates needed after the initial setup.', 'the-black-cap' ); ?></li>
		</ol>
	</div>
	<?php
}
