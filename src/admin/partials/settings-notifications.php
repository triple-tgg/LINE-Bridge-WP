<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$flags = maybe_unserialize( get_option( 'line_bridge_notification_flags', [] ) );
?>
<form method="post" action="options.php">
	<?php settings_fields( 'line_bridge_notifications' ); ?>
	<h2><?php esc_html_e( 'การแจ้งเตือน Admin', 'line-bridge-wp' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'ผู้ใช้ใหม่', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[new_user]" value="1"
				<?php checked( ! empty( $flags['new_user'] ) ); ?>>
				<?php esc_html_e( 'แจ้งเตือน Admin เมื่อมีผู้ใช้ลงทะเบียนใหม่', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'ออเดอร์ใหม่', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[new_order]" value="1"
				<?php checked( ! empty( $flags['new_order'] ) ); ?>>
				<?php esc_html_e( 'แจ้งเตือน Admin เมื่อมีออเดอร์ใหม่', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'สินค้าเหลือน้อย', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[low_stock]" value="1"
				<?php checked( ! empty( $flags['low_stock'] ) ); ?>>
				<?php esc_html_e( 'แจ้งเตือน Admin เมื่อสินค้าเหลือน้อยกว่า threshold', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'สินค้าหมด', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[out_of_stock]" value="1"
				<?php checked( ! empty( $flags['out_of_stock'] ) ); ?>>
				<?php esc_html_e( 'แจ้งเตือน Admin เมื่อสินค้าหมด', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Low Stock Threshold', 'line-bridge-wp' ); ?></th>
			<td>
				<input type="number" name="line_bridge_low_stock_threshold" min="1"
					   value="<?php echo esc_attr( get_option( 'line_bridge_low_stock_threshold', '5' ) ); ?>"
					   class="small-text"> <?php esc_html_e( 'ชิ้น', 'line-bridge-wp' ); ?>
				<p class="description"><?php esc_html_e( 'จำนวนสินค้าที่ถือว่าเหลือน้อย (0 = ใช้ค่า WooCommerce)', 'line-bridge-wp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'สลิปชำระเงิน', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[payment_slip]" value="1"
				<?php checked( ! empty( $flags['payment_slip'] ) ); ?>>
				<?php esc_html_e( 'แจ้งเตือน Admin เมื่อลูกค้าส่งสลิปชำระเงิน', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
	</table>
	<h2><?php esc_html_e( 'การแจ้งเตือนลูกค้า', 'line-bridge-wp' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'ยืนยันออเดอร์', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[order_placed]" value="1"
				<?php checked( ! empty( $flags['order_placed'] ) ); ?>>
				<?php esc_html_e( 'ส่งข้อความยืนยันเมื่อสั่งซื้อสำเร็จ', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'อัปเดตสถานะออเดอร์', 'line-bridge-wp' ); ?></th>
			<td><label><input type="checkbox" name="line_bridge_notification_flags[order_status]" value="1"
				<?php checked( ! empty( $flags['order_status'] ) ); ?>>
				<?php esc_html_e( 'ส่งข้อความเมื่อสถานะออเดอร์เปลี่ยนแปลง (กำลังดำเนินการ, จัดส่ง, สำเร็จ, ยกเลิก, คืนเงิน)', 'line-bridge-wp' ); ?>
			</label></td>
		</tr>
	</table>
	<?php submit_button( __( 'บันทึกการตั้งค่า', 'line-bridge-wp' ) ); ?>
</form>
