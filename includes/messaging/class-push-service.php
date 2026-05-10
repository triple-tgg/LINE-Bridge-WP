<?php

namespace Line_Bridge_WP\Messaging;

use Line_Bridge_WP\Database\DB_Notifications;
use Line_Bridge_WP\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Push_Service {

	public function __construct(
		private Line_API_Client $api_client,
		private DB_Notifications $db_notifications
	) {}

	public function send(
		string $recipient_id,
		array $messages,
		array $log_meta = []
	): bool {
		$messages = apply_filters( 'line_bridge_before_send_message', $messages, $recipient_id, $log_meta );

		$log_id = $this->db_notifications->insert( array_merge( $log_meta, [
			'recipient_id' => $recipient_id,
			'payload'      => $messages,
			'status'       => 'pending',
		] ) );

		$result = $this->api_client->push_message( $recipient_id, $messages );

		if ( is_wp_error( $result ) ) {
			Logger::error( 'Push failed', [
				'to'    => $recipient_id,
				'error' => $result->get_error_message(),
			] );
			if ( $log_id ) {
				$this->db_notifications->update_status( $log_id, 'failed', $result->get_error_message() );
			}
			return false;
		}

		if ( $log_id ) {
			$this->db_notifications->update_status( $log_id, 'sent' );
		}

		do_action( 'line_bridge_after_send_message', $recipient_id, $messages, $log_meta );
		return true;
	}

	public function send_to_admin( array $messages, array $log_meta = [] ): bool {
		$recipient_id   = get_option( 'line_bridge_admin_line_id', '' );
		if ( empty( $recipient_id ) ) {
			Logger::info( 'Admin LINE ID not configured, skipping admin notification' );
			return false;
		}
		return $this->send( $recipient_id, $messages, $log_meta );
	}

	public function retry_failed( int $max_retries = 3 ): void {
		$failed = $this->db_notifications->get_failed_for_retry( $max_retries );
		foreach ( $failed as $notification ) {
			$payload = json_decode( $notification->payload, true );
			if ( ! $payload ) {
				continue;
			}
			$this->db_notifications->increment_retry( $notification->id );
			$result = $this->api_client->push_message( $notification->recipient_id, $payload );
			if ( is_wp_error( $result ) ) {
				$this->db_notifications->update_status( $notification->id, 'failed', $result->get_error_message() );
			} else {
				$this->db_notifications->update_status( $notification->id, 'sent' );
			}
		}
	}
}
