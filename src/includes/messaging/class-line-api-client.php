<?php

namespace Line_Bridge_WP\Messaging;

use Line_Bridge_WP\Utilities\Crypto;
use Line_Bridge_WP\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Line_API_Client {

	private const BASE_URL = 'https://api.line.me';

	private function channel_token(): string {
		return get_option( 'line_bridge_messaging_channel_token', '' );
	}

	public function push_message( string $to, array $messages ): array|\WP_Error {
		return $this->request( 'POST', '/v2/bot/message/push', [
			'to'       => $to,
			'messages' => $messages,
		] );
	}

	public function multicast_message( array $to, array $messages ): array|\WP_Error {
		return $this->request( 'POST', '/v2/bot/message/multicast', [
			'to'       => $to,
			'messages' => $messages,
		] );
	}

	public function get_profile( string $user_id ): array|\WP_Error {
		return $this->request( 'GET', '/v2/bot/profile/' . rawurlencode( $user_id ) );
	}

	public function verify_webhook_signature( string $body, string $signature ): bool {
		$secret = get_option( 'line_bridge_messaging_channel_secret', '' );
		return Crypto::verify_line_signature( $body, $signature, $secret );
	}

	private function request( string $method, string $endpoint, array $body = [] ): array|\WP_Error {
		$token = $this->channel_token();
		if ( empty( $token ) ) {
			return new \WP_Error( 'no_token', 'LINE Messaging API token not configured' );
		}

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 15,
		];

		if ( $method === 'POST' && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE_URL . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			Logger::error( 'LINE API request failed', [
				'endpoint' => $endpoint,
				'error'    => $response->get_error_message(),
			] );
			return $response;
		}

		$code         = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$msg = $response_body['message'] ?? 'LINE API error';
			Logger::error( 'LINE API error response', [
				'endpoint' => $endpoint,
				'code'     => $code,
				'body'     => $response_body,
			] );
			return new \WP_Error( 'line_api_error', $msg, [ 'status' => $code ] );
		}

		return $response_body ?? [];
	}
}
