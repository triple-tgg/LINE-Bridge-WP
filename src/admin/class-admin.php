<?php

namespace Line_Bridge_WP\Admin;

use Line_Bridge_WP\Database\DB_Notifications;
use Line_Bridge_WP\Database\DB_Templates;
use Line_Bridge_WP\Database\DB_Users;
use Line_Bridge_WP\Messaging\Line_API_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public function __construct(
		private DB_Users $db_users,
		private DB_Notifications $db_notifications,
		private DB_Templates $db_templates,
		private Line_API_Client $api_client
	) {
		add_action( 'admin_menu',             [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init',             [ $this, 'register_settings' ] );
		add_action( 'wp_ajax_line_bridge_test_message',  [ $this, 'ajax_test_message' ] );
		add_action( 'wp_ajax_line_bridge_unlink_user',   [ $this, 'ajax_unlink_user' ] );
		add_action( 'wp_ajax_line_bridge_save_template', [ $this, 'ajax_save_template' ] );
	}

	public function register_menus(): void {
		add_options_page(
			__( 'LINE Bridge WP', 'line-bridge-wp' ),
			__( 'LINE Bridge WP', 'line-bridge-wp' ),
			'manage_options',
			'line-bridge-wp',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'options-general.php',
			__( 'ผู้ใช้ที่เชื่อมต่อ LINE', 'line-bridge-wp' ),
			__( 'LINE ผู้ใช้', 'line-bridge-wp' ),
			'manage_options',
			'line-bridge-users',
			[ $this, 'render_users_page' ]
		);

		add_submenu_page(
			'options-general.php',
			__( 'บันทึกการแจ้งเตือน LINE', 'line-bridge-wp' ),
			__( 'LINE บันทึก', 'line-bridge-wp' ),
			'manage_options',
			'line-bridge-logs',
			[ $this, 'render_logs_page' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [
			'settings_page_line-bridge-wp',
			'settings_page_line-bridge-users',
			'settings_page_line-bridge-logs',
		], true ) ) {
			return;
		}

		wp_enqueue_style(
			'line-bridge-admin',
			LINE_BRIDGE_WP_URL . 'assets/css/admin.css',
			[],
			LINE_BRIDGE_WP_VERSION
		);

		wp_enqueue_script(
			'line-bridge-admin',
			LINE_BRIDGE_WP_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			LINE_BRIDGE_WP_VERSION,
			true
		);

		wp_localize_script( 'line-bridge-admin', 'lineBridgeAdmin', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'line_bridge_admin' ),
			'restUrl'   => rest_url( 'line-bridge/v1/' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'i18n'      => [
				'testSuccess'   => __( 'ส่งข้อความสำเร็จ!', 'line-bridge-wp' ),
				'testFailed'    => __( 'ส่งข้อความไม่สำเร็จ', 'line-bridge-wp' ),
				'unlinkConfirm' => __( 'ยืนยันการยกเลิกเชื่อมต่อ LINE ของผู้ใช้นี้?', 'line-bridge-wp' ),
				'unlinkSuccess' => __( 'ยกเลิกการเชื่อมต่อสำเร็จ', 'line-bridge-wp' ),
				'saveSuccess'   => __( 'บันทึก template สำเร็จ', 'line-bridge-wp' ),
			],
		] );
	}

	public function register_settings(): void {
		$settings_page = new Settings_Page( $this->db_templates );
		$settings_page->register();
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'login';
		$tabs = [
			'login'         => __( 'LINE Login', 'line-bridge-wp' ),
			'messaging'     => __( 'Messaging API', 'line-bridge-wp' ),
			'notifications' => __( 'การแจ้งเตือน', 'line-bridge-wp' ),
			'templates'     => __( 'ข้อความ Templates', 'line-bridge-wp' ),
			'liff'          => __( 'LIFF App', 'line-bridge-wp' ),
			'advanced'      => __( 'ขั้นสูง', 'line-bridge-wp' ),
		];
		?>
		<div class="wrap line-bridge-wrap">
			<h1><img src="<?php echo esc_url( LINE_BRIDGE_WP_URL . 'assets/images/line-logo.svg' ); ?>" alt="LINE" width="24"> <?php esc_html_e( 'LINE Bridge WP', 'line-bridge-wp' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=line-bridge-wp&tab=' . $tab_id ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<div class="line-bridge-tab-content">
				<?php
				$partial = LINE_BRIDGE_WP_PATH . "admin/partials/settings-{$active_tab}.php";
				if ( file_exists( $partial ) ) {
					include $partial;
				}
				?>
			</div>
		</div>
		<?php
	}

	public function render_users_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$table = new Users_List_Table( $this->db_users );
		$table->prepare_items();
		?>
		<div class="wrap line-bridge-wrap">
			<h1><?php esc_html_e( 'ผู้ใช้ที่เชื่อมต่อ LINE', 'line-bridge-wp' ); ?></h1>
			<p><?php printf( esc_html__( 'ทั้งหมด %d บัญชี', 'line-bridge-wp' ), esc_html( $this->db_users->count() ) ); ?></p>
			<form method="get">
				<input type="hidden" name="page" value="line-bridge-users">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	public function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$type   = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$table  = new Logs_List_Table( $this->db_notifications, $type, $status );
		$table->prepare_items();
		$total = $this->db_notifications->count();
		?>
		<div class="wrap line-bridge-wrap">
			<h1><?php esc_html_e( 'บันทึกการแจ้งเตือน LINE', 'line-bridge-wp' ); ?></h1>
			<p><?php printf( esc_html__( 'ทั้งหมด %d รายการ', 'line-bridge-wp' ), esc_html( $total ) ); ?></p>
			<form method="get">
				<input type="hidden" name="page" value="line-bridge-logs">
				<select name="type">
					<option value=""><?php esc_html_e( 'ทุกประเภท', 'line-bridge-wp' ); ?></option>
					<?php foreach ( [ 'welcome', 'order_placed', 'order_processing', 'order_completed', 'order_cancelled', 'admin_new_order', 'admin_new_user', 'admin_low_stock', 'admin_out_of_stock', 'payment_slip' ] as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type, $t ); ?>><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="status">
					<option value=""><?php esc_html_e( 'ทุกสถานะ', 'line-bridge-wp' ); ?></option>
					<option value="sent" <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'สำเร็จ', 'line-bridge-wp' ); ?></option>
					<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'ล้มเหลว', 'line-bridge-wp' ); ?></option>
					<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'รอ', 'line-bridge-wp' ); ?></option>
				</select>
				<?php submit_button( __( 'กรอง', 'line-bridge-wp' ), 'secondary', '', false ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	public function ajax_test_message(): void {
		check_ajax_referer( 'line_bridge_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		$recipient = get_option( 'line_bridge_admin_line_id', '' );
		if ( empty( $recipient ) ) {
			wp_send_json_error( __( 'กรุณาตั้งค่า LINE ID ของ Admin ก่อน', 'line-bridge-wp' ) );
		}
		$result = $this->api_client->push_message( $recipient, [
			[
				'type' => 'text',
				'text' => '✅ ทดสอบการเชื่อมต่อ LINE Bridge WP สำเร็จ! — ' . get_bloginfo( 'name' ),
			],
		] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success( __( 'ส่งข้อความสำเร็จ!', 'line-bridge-wp' ) );
	}

	public function ajax_unlink_user(): void {
		check_ajax_referer( 'line_bridge_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		$wp_user_id = absint( $_POST['user_id'] ?? 0 );
		if ( ! $wp_user_id ) {
			wp_send_json_error( 'Invalid user ID' );
		}
		$this->db_users->delete( $wp_user_id );
		wp_send_json_success( __( 'ยกเลิกการเชื่อมต่อสำเร็จ', 'line-bridge-wp' ) );
	}

	public function ajax_save_template(): void {
		check_ajax_referer( 'line_bridge_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		$key  = sanitize_text_field( $_POST['template_key'] ?? '' );
		$type = sanitize_text_field( $_POST['message_type'] ?? 'text' );
		$body = wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) );

		if ( ! $key || ! $body ) {
			wp_send_json_error( 'Missing fields' );
		}

		$this->db_templates->upsert( $key, 'default', [
			'message_type' => $type,
			'body'         => $body,
		] );

		wp_send_json_success( __( 'บันทึก template สำเร็จ', 'line-bridge-wp' ) );
	}
}
