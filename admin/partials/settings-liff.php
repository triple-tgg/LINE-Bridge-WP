<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$liff_id  = get_option( 'line_bridge_liff_id', '' );
$liff_url = $liff_id ? 'https://liff.line.me/' . $liff_id : '';
?>
<form method="post" action="options.php">
	<?php settings_fields( 'line_bridge_liff' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'LIFF App ID', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" name="line_bridge_liff_id"
					   value="<?php echo esc_attr( $liff_id ); ?>"
					   class="regular-text" placeholder="1234567890-XXXXXXXX">
				<p class="description">
					<?php esc_html_e( 'LIFF App ID จาก LINE Developers Console → LIFF Apps', 'line-bridge-wp' ); ?>
				</p>
			</td>
		</tr>
		<?php if ( $liff_url ) : ?>
		<tr>
			<th><?php esc_html_e( 'LIFF URL', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="text" value="<?php echo esc_url( $liff_url ); ?>" class="regular-text" readonly id="liff-base-url">
				<button type="button" class="button line-bridge-copy-btn" data-target="#liff-base-url">
					<?php esc_html_e( 'คัดลอก', 'line-bridge-wp' ); ?>
				</button>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'LIFF Endpoint URL', 'line-bridge-wp' ); ?></th>
			<td>
				<code><?php echo esc_url( home_url( '/liff-app/' ) ); ?></code>
				<p class="description">
					<?php esc_html_e( 'ตั้งค่า Endpoint URL นี้ใน LINE Developers Console → LIFF Apps → Endpoint URL', 'line-bridge-wp' ); ?>
				</p>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><?php esc_html_e( 'ฟีเจอร์ LIFF', 'line-bridge-wp' ); ?></th>
			<td>
				<ul>
					<li>✅ <?php esc_html_e( 'ดูสถานะออเดอร์ใน LINE โดยตรง', 'line-bridge-wp' ); ?></li>
					<li>✅ <?php esc_html_e( 'ปุ่ม "ดูออเดอร์" ใน Flex Message จะเปิด LIFF', 'line-bridge-wp' ); ?></li>
					<li>✅ <?php esc_html_e( 'ยืนยันตัวตนด้วย LINE User ID', 'line-bridge-wp' ); ?></li>
				</ul>
				<p class="description"><?php esc_html_e( 'หากไม่ได้ตั้งค่า LIFF ID ปุ่มในข้อความจะเปิดไปหน้าออเดอร์บนเว็บไซต์แทน', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'บันทึกการตั้งค่า', 'line-bridge-wp' ) ); ?>
</form>
