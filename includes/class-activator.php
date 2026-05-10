<?php

namespace Line_Bridge_WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	public static function activate(): void {
		self::create_tables();
		self::seed_default_templates();
		self::set_default_options();
		flush_rewrite_rules();
	}

	private static function create_tables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$wpdb->prefix}line_bridge_users (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id      BIGINT(20) UNSIGNED NOT NULL,
			line_user_id    VARCHAR(64)         NOT NULL,
			display_name    VARCHAR(255)        DEFAULT NULL,
			picture_url     VARCHAR(512)        DEFAULT NULL,
			email           VARCHAR(255)        DEFAULT NULL,
			access_token    TEXT                DEFAULT NULL,
			refresh_token   TEXT                DEFAULT NULL,
			token_expires   DATETIME            DEFAULT NULL,
			linked_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY     (id),
			UNIQUE KEY      wp_user_id   (wp_user_id),
			UNIQUE KEY      line_user_id (line_user_id)
		) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}line_bridge_notifications (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			notification_type VARCHAR(64)         NOT NULL,
			recipient_id      VARCHAR(64)         NOT NULL,
			wp_user_id        BIGINT(20) UNSIGNED DEFAULT NULL,
			reference_id      BIGINT(20) UNSIGNED DEFAULT NULL,
			reference_type    VARCHAR(32)         DEFAULT NULL,
			payload           LONGTEXT            DEFAULT NULL,
			status            ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
			retry_count       TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			error_message     TEXT                DEFAULT NULL,
			sent_at           DATETIME            DEFAULT NULL,
			created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY notification_type (notification_type),
			KEY status            (status),
			KEY reference         (reference_id, reference_type)
		) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}line_bridge_message_templates (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			template_key VARCHAR(64)         NOT NULL,
			locale       VARCHAR(10)         NOT NULL DEFAULT 'default',
			message_type ENUM('text','flex','imagemap') NOT NULL DEFAULT 'text',
			subject      VARCHAR(255)        DEFAULT NULL,
			body         LONGTEXT            NOT NULL,
			is_active    TINYINT(1)          NOT NULL DEFAULT 1,
			updated_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   template_key_locale (template_key, locale)
		) $charset_collate;";
		dbDelta( $sql );

		update_option( 'line_bridge_db_version', LINE_BRIDGE_WP_DB_VERSION );
	}

	private static function seed_default_templates(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'line_bridge_message_templates';

		$defaults = [
			[
				'template_key' => 'welcome',
				'message_type' => 'text',
				'subject'      => 'ข้อความต้อนรับ',
				'body'         => "🎉 ยินดีต้อนรับ {{CUSTOMER_NAME}}!\n\nขอบคุณที่เชื่อมต่อบัญชี LINE กับ {{SITE_NAME}}\nตอนนี้คุณจะได้รับการแจ้งเตือนสถานะออเดอร์ผ่าน LINE โดยตรง 🛍️",
			],
			[
				'template_key' => 'order_placed',
				'message_type' => 'flex',
				'subject'      => 'ยืนยันคำสั่งซื้อ',
				'body'         => self::get_order_placed_flex(),
			],
			[
				'template_key' => 'order_processing',
				'message_type' => 'text',
				'subject'      => 'กำลังดำเนินการ',
				'body'         => "📦 คำสั่งซื้อ #{{ORDER_NUMBER}} กำลังดำเนินการแล้ว!\n\nเราได้รับคำสั่งซื้อของคุณและกำลังเตรียมสินค้า\nคุณจะได้รับการแจ้งเตือนอีกครั้งเมื่อสินค้าถูกจัดส่ง 🚚",
			],
			[
				'template_key' => 'order_shipped',
				'message_type' => 'text',
				'subject'      => 'จัดส่งแล้ว',
				'body'         => "🚚 คำสั่งซื้อ #{{ORDER_NUMBER}} ถูกจัดส่งแล้ว!\n\nสินค้าของคุณกำลังอยู่ระหว่างการจัดส่ง\n{{TRACKING_URL}}",
			],
			[
				'template_key' => 'order_completed',
				'message_type' => 'text',
				'subject'      => 'จัดส่งสำเร็จ',
				'body'         => "✅ คำสั่งซื้อ #{{ORDER_NUMBER}} เสร็จสมบูรณ์!\n\nขอบคุณที่ช้อปปิ้งกับ {{SITE_NAME}} 🙏\nหวังว่าจะได้พบกันอีกนะคะ/ครับ",
			],
			[
				'template_key' => 'order_cancelled',
				'message_type' => 'text',
				'subject'      => 'ยกเลิกคำสั่งซื้อ',
				'body'         => "❌ คำสั่งซื้อ #{{ORDER_NUMBER}} ถูกยกเลิกแล้ว\n\nหากมีข้อสงสัย กรุณาติดต่อเราได้ที่ {{SITE_NAME}}",
			],
			[
				'template_key' => 'order_refunded',
				'message_type' => 'text',
				'subject'      => 'คืนเงิน',
				'body'         => "💰 คำสั่งซื้อ #{{ORDER_NUMBER}} ได้รับการคืนเงินแล้ว\n\nยอดเงิน {{ORDER_TOTAL}} จะถูกคืนกลับภายใน 3-7 วันทำการ",
			],
			[
				'template_key' => 'admin_new_order',
				'message_type' => 'text',
				'subject'      => 'แจ้งเตือน Admin: ออเดอร์ใหม่',
				'body'         => "🛒 มีออเดอร์ใหม่!\n\nออเดอร์ #{{ORDER_NUMBER}}\nลูกค้า: {{CUSTOMER_NAME}}\nยอดรวม: {{ORDER_TOTAL}}\nวันที่: {{DATE_TIME}}\n\nดูรายละเอียด: {{ORDER_URL}}",
			],
			[
				'template_key' => 'admin_new_user',
				'message_type' => 'text',
				'subject'      => 'แจ้งเตือน Admin: ผู้ใช้ใหม่',
				'body'         => "👤 มีผู้ใช้ใหม่ลงทะเบียน!\n\nชื่อ: {{USER_DISPLAY_NAME}}\nอีเมล: {{USER_EMAIL}}\nวันที่: {{DATE_TIME}}",
			],
			[
				'template_key' => 'admin_low_stock',
				'message_type' => 'text',
				'subject'      => 'แจ้งเตือน: สินค้าเหลือน้อย',
				'body'         => "⚠️ สินค้าเหลือน้อย!\n\nสินค้า: {{PRODUCT_NAME}}\nจำนวนคงเหลือ: {{STOCK_QTY}} ชิ้น\n\nกรุณาเติมสต็อกโดยเร็ว",
			],
			[
				'template_key' => 'admin_out_of_stock',
				'message_type' => 'text',
				'subject'      => 'แจ้งเตือน: สินค้าหมด',
				'body'         => "🚫 สินค้าหมดแล้ว!\n\nสินค้า: {{PRODUCT_NAME}}\n\nกรุณาเติมสต็อกทันที",
			],
			[
				'template_key' => 'payment_slip',
				'message_type' => 'text',
				'subject'      => 'แจ้งการโอนเงิน',
				'body'         => "💳 ลูกค้าแจ้งชำระเงิน!\n\nออเดอร์ #{{ORDER_NUMBER}}\nลูกค้า: {{CUSTOMER_NAME}}\nยอดเงิน: {{PAYMENT_AMOUNT}}\nวันที่: {{DATE_TIME}}",
			],
		];

		foreach ( $defaults as $tpl ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $table WHERE template_key = %s AND locale = 'default'",
					$tpl['template_key']
				)
			);
			if ( ! $existing ) {
				$wpdb->insert(
					$table,
					[
						'template_key' => $tpl['template_key'],
						'locale'       => 'default',
						'message_type' => $tpl['message_type'],
						'subject'      => $tpl['subject'],
						'body'         => $tpl['body'],
						'is_active'    => 1,
					],
					[ '%s', '%s', '%s', '%s', '%s', '%d' ]
				);
			}
		}
	}

	private static function get_order_placed_flex(): string {
		return json_encode( [
			'type'   => 'flex',
			'altText' => 'ยืนยันคำสั่งซื้อ #{{ORDER_NUMBER}}',
			'contents' => [
				'type'   => 'bubble',
				'header' => [
					'type'     => 'box',
					'layout'   => 'vertical',
					'contents' => [
						[
							'type'  => 'text',
							'text'  => '{{SITE_NAME}}',
							'color' => '#ffffff',
							'size'  => 'sm',
						],
						[
							'type'   => 'text',
							'text'   => 'ยืนยันคำสั่งซื้อ',
							'color'  => '#ffffff',
							'size'   => 'xl',
							'weight' => 'bold',
						],
					],
					'backgroundColor' => '#06C755',
					'paddingAll'       => '20px',
				],
				'body' => [
					'type'     => 'box',
					'layout'   => 'vertical',
					'contents' => [
						[
							'type'     => 'box',
							'layout'   => 'horizontal',
							'contents' => [
								[ 'type' => 'text', 'text' => 'ออเดอร์', 'size' => 'sm', 'color' => '#555555', 'flex' => 0 ],
								[ 'type' => 'text', 'text' => '#{{ORDER_NUMBER}}', 'size' => 'sm', 'color' => '#111111', 'align' => 'end' ],
							],
						],
						[
							'type'     => 'box',
							'layout'   => 'horizontal',
							'contents' => [
								[ 'type' => 'text', 'text' => 'ลูกค้า', 'size' => 'sm', 'color' => '#555555', 'flex' => 0 ],
								[ 'type' => 'text', 'text' => '{{CUSTOMER_NAME}}', 'size' => 'sm', 'color' => '#111111', 'align' => 'end' ],
							],
							'margin' => 'md',
						],
						[
							'type'     => 'box',
							'layout'   => 'horizontal',
							'contents' => [
								[ 'type' => 'text', 'text' => 'ยอดรวม', 'size' => 'sm', 'color' => '#555555', 'flex' => 0 ],
								[ 'type' => 'text', 'text' => '{{ORDER_TOTAL}}', 'size' => 'sm', 'color' => '#06C755', 'align' => 'end', 'weight' => 'bold' ],
							],
							'margin' => 'md',
						],
						[
							'type'     => 'box',
							'layout'   => 'horizontal',
							'contents' => [
								[ 'type' => 'text', 'text' => 'สถานะ', 'size' => 'sm', 'color' => '#555555', 'flex' => 0 ],
								[ 'type' => 'text', 'text' => '{{ORDER_STATUS}}', 'size' => 'sm', 'color' => '#111111', 'align' => 'end' ],
							],
							'margin' => 'md',
						],
					],
					'spacing'    => 'sm',
					'paddingAll' => '20px',
				],
				'footer' => [
					'type'     => 'box',
					'layout'   => 'vertical',
					'contents' => [
						[
							'type'   => 'button',
							'style'  => 'primary',
							'color'  => '#06C755',
							'action' => [
								'type'  => 'uri',
								'label' => 'ดูรายละเอียดออเดอร์',
								'uri'   => '{{ORDER_URL}}',
							],
						],
					],
				],
			],
		], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	}

	private static function set_default_options(): void {
		$defaults = [
			'line_bridge_login_auto_register'    => '1',
			'line_bridge_send_welcome'            => '1',
			'line_bridge_admin_recipient_type'    => 'user',
			'line_bridge_low_stock_threshold'     => '5',
			'line_bridge_delete_data_on_uninstall' => '0',
			'line_bridge_notification_flags'      => serialize( [
				'new_user'        => true,
				'new_order'       => true,
				'order_placed'    => true,
				'order_status'    => true,
				'low_stock'       => true,
				'out_of_stock'    => true,
				'payment_slip'    => true,
			] ),
		];
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
