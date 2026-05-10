<?php

namespace Line_Bridge_WP\Notifications;

use Line_Bridge_WP\Database\DB_Users;
use Line_Bridge_WP\Database\DB_Notifications;
use Line_Bridge_WP\Utilities\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Order_Notification {

	public function __construct(
		private Notification_Manager $notif_manager,
		private DB_Users $db_users,
		private DB_Notifications $db_notifications
	) {
		$this->hooks();
	}

	private function hooks(): void {
		add_action( 'woocommerce_thankyou',              [ $this, 'on_order_placed' ], 10, 1 );
		add_action( 'woocommerce_order_status_changed',  [ $this, 'on_status_changed' ], 10, 3 );
	}

	public function on_order_placed( int $order_id ): void {
		if ( ! $this->notif_manager->flag_enabled( 'order_placed' ) ) {
			return;
		}
		if ( $this->db_notifications->already_sent( $order_id, 'order', 'order_placed' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$line_row = $this->get_line_row_for_order( $order );
		if ( ! $line_row ) {
			return;
		}

		$this->notif_manager->send_order_flex_to_customer(
			$line_row->line_user_id,
			(int) $order->get_customer_id(),
			$order
		);

		if ( $this->notif_manager->flag_enabled( 'new_order' ) ) {
			$vars = Template_Renderer::get_order_vars( $order );
			$this->notif_manager->send_to_admin( 'admin_new_order', $vars, [
				'reference_id'   => $order_id,
				'reference_type' => 'order',
			] );
		}
	}

	public function on_status_changed( int $order_id, string $old_status, string $new_status ): void {
		if ( ! $this->notif_manager->flag_enabled( 'order_status' ) ) {
			return;
		}

		$template_map = [
			'processing' => 'order_processing',
			'shipped'    => 'order_shipped',
			'completed'  => 'order_completed',
			'cancelled'  => 'order_cancelled',
			'refunded'   => 'order_refunded',
		];

		if ( ! isset( $template_map[ $new_status ] ) ) {
			return;
		}

		$template_key = $template_map[ $new_status ];
		if ( $this->db_notifications->already_sent( $order_id, 'order', $template_key ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$line_row = $this->get_line_row_for_order( $order );
		if ( ! $line_row ) {
			return;
		}

		$vars = Template_Renderer::get_order_vars( $order );
		$this->notif_manager->send_to_customer(
			$template_key,
			$line_row->line_user_id,
			(int) $order->get_customer_id(),
			$vars,
			[
				'reference_id'   => $order_id,
				'reference_type' => 'order',
			]
		);
	}

	private function get_line_row_for_order( \WC_Order $order ): object|null {
		$customer_id = (int) $order->get_customer_id();
		if ( ! $customer_id ) {
			return null;
		}
		return $this->db_users->get_by_wp_user_id( $customer_id );
	}
}
