<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<form method="post" action="options.php">
	<?php settings_fields( 'line_bridge_advanced' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'ลบข้อมูลเมื่อถอนการติดตั้ง', 'line-bridge-wp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="line_bridge_delete_data_on_uninstall" value="1"
						   <?php checked( get_option( 'line_bridge_delete_data_on_uninstall', '0' ), '1' ); ?>>
					<?php esc_html_e( 'ลบ database tables และ options ทั้งหมดเมื่อถอนการติดตั้ง plugin', 'line-bridge-wp' ); ?>
				</label>
				<p class="description" style="color:#d63638">
					⚠️ <?php esc_html_e( 'หากเปิดใช้งาน ข้อมูลผู้ใช้ LINE และ logs ทั้งหมดจะถูกลบถาวร', 'line-bridge-wp' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'บันทึกการตั้งค่า', 'line-bridge-wp' ) ); ?>
</form>
<hr>
<h2><?php esc_html_e( 'ข้อมูล Plugin', 'line-bridge-wp' ); ?></h2>
<table class="form-table">
	<tr><th><?php esc_html_e( 'Plugin Version', 'line-bridge-wp' ); ?></th><td><?php echo esc_html( LINE_BRIDGE_WP_VERSION ); ?></td></tr>
	<tr><th><?php esc_html_e( 'DB Version', 'line-bridge-wp' ); ?></th><td><?php echo esc_html( get_option( 'line_bridge_db_version', '—' ) ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Webhook URL', 'line-bridge-wp' ); ?></th><td><code><?php echo esc_url( rest_url( 'line-bridge/v1/webhook' ) ); ?></code></td></tr>
	<tr><th><?php esc_html_e( 'Callback URL', 'line-bridge-wp' ); ?></th><td><code><?php echo esc_url( rest_url( 'line-bridge/v1/oauth/callback' ) ); ?></code></td></tr>
</table>
