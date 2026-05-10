<?php

namespace Line_Bridge_WP\Notifications;

use Line_Bridge_WP\Messaging\Message_Builder;
use Line_Bridge_WP\Utilities\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payment_Slip_Notification {

	public function __construct(
		private Notification_Manager $notif_manager,
		private Message_Builder $msg_builder
	) {
		$this->hooks();
	}

	private function hooks(): void {
		add_action( 'line_bridge_payment_slip_received', [ $this, 'on_slip_received' ], 10, 3 );
	}

	/**
	 * @param int    $order_id     WooCommerce Order ID
	 * @param string $slip_url     URL of the slip image
	 * @param array  $payment_data Additional payment details
	 */
	public function on_slip_received( int $order_id, string $slip_url, array $payment_data = [] ): void {
		if ( ! $this->notif_manager->flag_enabled( 'payment_slip' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$vars = array_merge(
			Template_Renderer::get_order_vars( $order ),
			[
				'SLIP_IMAGE_URL'  => esc_url_raw( $slip_url ),
				'PAYMENT_AMOUNT'  => $payment_data['amount'] ?? $order->get_formatted_order_total(),
			]
		);

		$messages = [];

		if ( $slip_url ) {
			$messages[] = $this->msg_builder->build_image_message( $slip_url );
		}

		$text_msg = $this->msg_builder->build( 'payment_slip', $vars );
		if ( is_array( $text_msg ) && isset( $text_msg['type'] ) ) {
			$messages[] = $text_msg;
		}

		if ( ! empty( $messages ) ) {
			$this->notif_manager->send_to_admin( 'payment_slip', $vars, [
				'reference_id'   => $order_id,
				'reference_type' => 'order',
			] );
		}
	}
}
