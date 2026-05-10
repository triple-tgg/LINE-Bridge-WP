<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$webhook_url = rest_url( 'line-bridge/v1/webhook' );
?>
<form method="post" action="options.php">
	<?php settings_fields( 'line_bridge_messaging' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Channel Access Token', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="password" name="line_bridge_messaging_channel_token"
					   value="<?php echo esc_attr( get_option( 'line_bridge_messaging_channel_token' ) ); ?>"
					   class="large-text" autocomplete="off">
				<p class="description"><?php esc_html_e( 'Channel Access Token จาก LINE Developers Console (Messaging API Channel)', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Channel Secret', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="password" name="line_bridge_messaging_channel_secret"
					   value="<?php echo esc_attr( get_option( 'line_bridge_messaging_channel_secret' ) ); ?>"
					   class="regular-text" autocomplete="off">
				<p class="description"><?php esc_html_e( 'ใช้สำหรับตรวจสอบ webhook signature', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Webhook URL', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" value="<?php echo esc_url( $webhook_url ); ?>" class="large-text" readonly id="line-webhook-url">
				<button type="button" class="button line-bridge-copy-btn" data-target="#line-webhook-url">
					<?php esc_html_e( 'คัดลอก', 'line-bridge-wp' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'คัดลอก URL นี้ไปวางใน LINE Developers Console → Messaging API → Webhook URL', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Admin LINE ID', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" name="line_bridge_admin_line_id"
					   value="<?php echo esc_attr( get_option( 'line_bridge_admin_line_id' ) ); ?>"
					   class="regular-text" placeholder="U... หรือ C...">
				<p class="description"><?php esc_html_e( 'LINE User ID (ขึ้นต้นด้วย U) หรือ Group ID (ขึ้นต้นด้วย C) สำหรับแจ้งเตือน Admin', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'ประเภทผู้รับ Admin', 'line-bridge-wp' ); ?></th>
			<td>
				<label>
					<input type="radio" name="line_bridge_admin_recipient_type" value="user"
						   <?php checked( get_option( 'line_bridge_admin_recipient_type', 'user' ), 'user' ); ?>>
					<?php esc_html_e( 'ส่วนตัว (Personal)', 'line-bridge-wp' ); ?>
				</label>
				&nbsp;&nbsp;
				<label>
					<input type="radio" name="line_bridge_admin_recipient_type" value="group"
						   <?php checked( get_option( 'line_bridge_admin_recipient_type', 'user' ), 'group' ); ?>>
					<?php esc_html_e( 'กลุ่มแชท (Group Chat)', 'line-bridge-wp' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'ข้อความต้อนรับ', 'line-bridge-wp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="line_bridge_send_welcome" value="1"
						   <?php checked( get_option( 'line_bridge_send_welcome', '1' ), '1' ); ?>>
					<?php esc_html_e( 'ส่งข้อความต้อนรับเมื่อล็อกอินด้วย LINE ครั้งแรก', 'line-bridge-wp' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'บันทึกการตั้งค่า', 'line-bridge-wp' ) ); ?>
</form>
<hr>
<h2><?php esc_html_e( 'ทดสอบการเชื่อมต่อ', 'line-bridge-wp' ); ?></h2>
<p><?php esc_html_e( 'ส่งข้อความทดสอบไปยัง Admin LINE ID ที่ตั้งค่าไว้', 'line-bridge-wp' ); ?></p>
<button type="button" id="line-bridge-test-btn" class="button button-secondary">
	📨 <?php esc_html_e( 'ส่งข้อความทดสอบ', 'line-bridge-wp' ); ?>
</button>
<span id="line-bridge-test-result" style="margin-left:12px"></span>
