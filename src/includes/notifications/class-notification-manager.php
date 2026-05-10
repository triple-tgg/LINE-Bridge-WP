<?php

namespace Line_Bridge_WP\Notifications;

use Line_Bridge_WP\Messaging\Push_Service;
use Line_Bridge_WP\Messaging\Message_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notification_Manager {

	public function __construct(
		private Push_Service $push_service,
		private Message_Builder $message_builder
	) {}

	public function send_to_customer(
		string $template_key,
		string $line_user_id,
		int $wp_user_id,
		array $extra_vars = [],
		array $log_meta = []
	): bool {
		$message  = $this->message_builder->build( $template_key, $extra_vars );
		$messages = is_array( $message ) && isset( $message['type'] ) ? [ $message ] : $message;

		return $this->push_service->send(
			$line_user_id,
			$messages,
			array_merge( [ 'wp_user_id' => $wp_user_id, 'notification_type' => $template_key ], $log_meta )
		);
	}

	public function send_to_admin(
		string $template_key,
		array $extra_vars = [],
		array $log_meta = []
	): bool {
		$message  = $this->message_builder->build( $template_key, $extra_vars );
		$messages = is_array( $message ) && isset( $message['type'] ) ? [ $message ] : $message;

		return $this->push_service->send_to_admin(
			$messages,
			array_merge( [ 'notification_type' => $template_key ], $log_meta )
		);
	}

	public function send_order_flex_to_customer( string $line_user_id, int $wp_user_id, \WC_Order $order ): bool {
		$messages = $this->message_builder->build_order_flex( $order );
		return $this->push_service->send( $line_user_id, $messages, [
			'wp_user_id'        => $wp_user_id,
			'notification_type' => 'order_placed',
			'reference_id'      => $order->get_id(),
			'reference_type'    => 'order',
		] );
	}

	public function push_text( string $line_user_id, string $text ): bool {
		return $this->push_service->send( $line_user_id, [
			[ 'type' => 'text', 'text' => $text ],
		], [ 'notification_type' => 'reply' ] );
	}

	private function is_enabled( string $flag_key ): bool {
		$flags = maybe_unserialize( get_option( 'line_bridge_notification_flags', [] ) );
		return ! empty( $flags[ $flag_key ] );
	}

	public function flag_enabled( string $flag_key ): bool {
		return $this->is_enabled( $flag_key );
	}
}
