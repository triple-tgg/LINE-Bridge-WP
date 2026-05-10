<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$templates  = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}line_bridge_message_templates ORDER BY template_key"
);
$selected = isset( $_GET['tpl'] ) ? sanitize_text_field( wp_unslash( $_GET['tpl'] ) ) : ( $templates[0]->template_key ?? '' );
$current  = null;
foreach ( $templates as $t ) {
	if ( $t->template_key === $selected ) {
		$current = $t;
		break;
	}
}
$placeholders = [
	'{{SITE_NAME}}' => __( 'ชื่อเว็บไซต์', 'line-bridge-wp' ),
	'{{CUSTOMER_NAME}}' => __( 'ชื่อลูกค้า', 'line-bridge-wp' ),
	'{{ORDER_NUMBER}}' => __( 'เลขออเดอร์', 'line-bridge-wp' ),
	'{{ORDER_TOTAL}}' => __( 'ยอดรวม', 'line-bridge-wp' ),
	'{{ORDER_STATUS}}' => __( 'สถานะออเดอร์', 'line-bridge-wp' ),
	'{{ORDER_URL}}' => __( 'ลิงก์ออเดอร์', 'line-bridge-wp' ),
	'{{PRODUCT_NAME}}' => __( 'ชื่อสินค้า', 'line-bridge-wp' ),
	'{{STOCK_QTY}}' => __( 'จำนวนสต็อก', 'line-bridge-wp' ),
	'{{USER_DISPLAY_NAME}}' => __( 'ชื่อผู้ใช้', 'line-bridge-wp' ),
	'{{USER_EMAIL}}' => __( 'อีเมลผู้ใช้', 'line-bridge-wp' ),
	'{{DATE_TIME}}' => __( 'วันที่และเวลา', 'line-bridge-wp' ),
	'{{SLIP_IMAGE_URL}}' => __( 'URL รูปสลิป', 'line-bridge-wp' ),
	'{{PAYMENT_AMOUNT}}' => __( 'ยอดชำระ', 'line-bridge-wp' ),
];
?>
<div class="line-bridge-templates">
	<div class="line-bridge-template-selector">
		<label><strong><?php esc_html_e( 'เลือก Template:', 'line-bridge-wp' ); ?></strong></label>
		<select id="line-bridge-tpl-select">
			<?php foreach ( $templates as $t ) : ?>
				<option value="<?php echo esc_attr( admin_url( 'options-general.php?page=line-bridge-wp&tab=templates&tpl=' . $t->template_key ) ); ?>"
					<?php selected( $t->template_key, $selected ); ?>>
					<?php echo esc_html( $t->subject ?: $t->template_key ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<?php if ( $current ) : ?>
	<div class="line-bridge-template-editor">
		<h3><?php echo esc_html( $current->subject ?: $current->template_key ); ?></h3>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'ประเภทข้อความ', 'line-bridge-wp' ); ?></th>
				<td>
					<select id="tpl-message-type">
						<option value="text" <?php selected( $current->message_type, 'text' ); ?>><?php esc_html_e( 'Text', 'line-bridge-wp' ); ?></option>
						<option value="flex" <?php selected( $current->message_type, 'flex' ); ?>><?php esc_html_e( 'Flex Message (JSON)', 'line-bridge-wp' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'เนื้อหา', 'line-bridge-wp' ); ?></th>
				<td>
					<textarea id="tpl-body" rows="12" class="large-text code"><?php echo esc_textarea( $current->body ); ?></textarea>
				</td>
			</tr>
		</table>
		<input type="hidden" id="tpl-key" value="<?php echo esc_attr( $current->template_key ); ?>">
		<button type="button" id="line-bridge-save-tpl" class="button button-primary">
			<?php esc_html_e( 'บันทึก Template', 'line-bridge-wp' ); ?>
		</button>
		<span id="tpl-save-result" style="margin-left:12px"></span>
	</div>
	<?php endif; ?>

	<div class="line-bridge-placeholders">
		<h3><?php esc_html_e( 'Placeholder ที่ใช้ได้', 'line-bridge-wp' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Placeholder', 'line-bridge-wp' ); ?></th><th><?php esc_html_e( 'ความหมาย', 'line-bridge-wp' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $placeholders as $ph => $label ) : ?>
				<tr>
					<td><code><?php echo esc_html( $ph ); ?></code></td>
					<td><?php echo esc_html( $label ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<script>
document.getElementById('line-bridge-tpl-select')?.addEventListener('change', function(){
	window.location.href = this.value;
});
</script>
