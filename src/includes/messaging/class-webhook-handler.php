<?php

namespace Line_Bridge_WP\Messaging;

use Line_Bridge_WP\Notifications\Notification_Manager;
use Line_Bridge_WP\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webhook_Handler {

	public function __construct( private Notification_Manager $notif_manager ) {}

	public function handle( array $payload ): void {
		$events = $payload['events'] ?? [];
		foreach ( $events as $event ) {
			$type = $event['type'] ?? '';
			match ( $type ) {
				'follow'   => $this->on_follow( $event ),
				'message'  => $this->on_message( $event ),
				'postback' => $this->on_postback( $event ),
				default    => Logger::info( 'Unhandled webhook event type', [ 'type' => $type ] ),
			};
		}
	}

	private function on_follow( array $event ): void {
		$line_user_id = $event['source']['userId'] ?? '';
		if ( ! $line_user_id ) {
			return;
		}
		do_action( 'line_bridge_webhook_follow', $line_user_id, $event );
		Logger::info( 'User followed bot', [ 'line_user_id' => $line_user_id ] );
	}

	private function on_message( array $event ): void {
		$line_user_id = $event['source']['userId'] ?? '';
		$message      = $event['message'] ?? [];
		$msg_type     = $message['type'] ?? '';
		$text         = strtolower( trim( $message['text'] ?? '' ) );

		if ( ! $line_user_id ) {
			return;
		}

		do_action( 'line_bridge_webhook_message', $line_user_id, $event );

		if ( $msg_type === 'text' ) {
			$this->handle_text_command( $line_user_id, $text, $event );
		}
	}

	private function handle_text_command( string $line_user_id, string $text, array $event ): void {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT wp_user_id FROM {$wpdb->prefix}line_bridge_users WHERE line_user_id = %s",
			$line_user_id
		) );

		if ( ! $row ) {
			return;
		}

		$wp_user_id = (int) $row->wp_user_id;

		if ( in_array( $text, [ 'order', 'orders', 'ออเดอร์' ], true ) ) {
			$this->reply_recent_orders( $line_user_id, $wp_user_id );
		} elseif ( in_array( $text, [ 'help', 'ช่วยเหลือ', 'menu', 'เมนู' ], true ) ) {
			$this->reply_help( $line_user_id );
		}
	}

	private function reply_recent_orders( string $line_user_id, int $wp_user_id ): void {
		$orders = wc_get_orders( [
			'customer_id' => $wp_user_id,
			'limit'       => 3,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		if ( empty( $orders ) ) {
			$this->notif_manager->push_text( $line_user_id, '📦 คุณยังไม่มีคำสั่งซื้อ' );
			return;
		}

		$lines = [ '📦 คำสั่งซื้อล่าสุด:' ];
		foreach ( $orders as $order ) {
			/** @var \WC_Order $order */
			$lines[] = sprintf(
				'• #%s — %s — %s',
				$order->get_order_number(),
				$order->get_status(),
				$order->get_formatted_order_total()
			);
		}
		$this->notif_manager->push_text( $line_user_id, implode( "\n", $lines ) );
	}

	private function reply_help( string $line_user_id ): void {
		$site = get_bloginfo( 'name' );
		$this->notif_manager->push_text(
			$line_user_id,
			"🤖 {$site} LINE Bot\n\nพิมพ์คำสั่งต่อไปนี้:\n• ออเดอร์ — ดูคำสั่งซื้อล่าสุด\n• ช่วยเหลือ — แสดงเมนูนี้"
		);
	}

	private function on_postback( array $event ): void {
		do_action( 'line_bridge_webhook_postback', $event['source']['userId'] ?? '', $event );
	}
}
