<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$redirect_uri = rest_url( 'line-bridge/v1/oauth/callback' );
?>
<form method="post" action="options.php">
	<?php settings_fields( 'line_bridge_login' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Channel ID', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" name="line_bridge_login_channel_id"
					   value="<?php echo esc_attr( get_option( 'line_bridge_login_channel_id' ) ); ?>"
					   class="regular-text" autocomplete="off">
				<p class="description"><?php esc_html_e( 'Channel ID จาก LINE Developers Console (LINE Login Channel)', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Channel Secret', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="password" name="line_bridge_login_channel_secret"
					   value="<?php echo esc_attr( get_option( 'line_bridge_login_channel_secret' ) ); ?>"
					   class="regular-text" autocomplete="off">
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Callback URL', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" value="<?php echo esc_url( $redirect_uri ); ?>" class="large-text" readonly id="line-callback-url">
				<button type="button" class="button line-bridge-copy-btn" data-target="#line-callback-url">
					<?php esc_html_e( 'คัดลอก', 'line-bridge-wp' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'คัดลอก URL นี้ไปวางใน LINE Developers Console → LINE Login → Callback URL', 'line-bridge-wp' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Auto Register', 'line-bridge-wp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="line_bridge_login_auto_register" value="1"
						   <?php checked( get_option( 'line_bridge_login_auto_register', '1' ), '1' ); ?>>
					<?php esc_html_e( 'สร้างบัญชี WordPress ใหม่อัตโนมัติเมื่อล็อกอินด้วย LINE ครั้งแรก', 'line-bridge-wp' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'บันทึกการตั้งค่า', 'line-bridge-wp' ) ); ?>
</form>
