<?php

namespace Line_Bridge_WP\Api;

use Line_Bridge_WP\Messaging\Line_API_Client;
use Line_Bridge_WP\Messaging\Webhook_Handler;
use Line_Bridge_WP\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webhook_Controller {

	public function __construct(
		private Line_API_Client $api_client,
		private Webhook_Handler $webhook_handler
	) {}

	public function register_routes(): void {
		register_rest_route( 'line-bridge/v1', '/webhook', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_webhook' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$body      = $request->get_body();
		$signature = $request->get_header( 'x-line-signature' );

		if ( ! $this->api_client->verify_webhook_signature( $body, $signature ) ) {
			Logger::error( 'Webhook signature verification failed' );
			return new \WP_REST_Response( [ 'error' => 'Forbidden' ], 403 );
		}

		$payload = json_decode( $body, true );
		if ( ! is_array( $payload ) ) {
			return new \WP_REST_Response( [ 'error' => 'Invalid JSON' ], 400 );
		}

		try {
			$this->webhook_handler->handle( $payload );
		} catch ( \Throwable $e ) {
			Logger::error( 'Webhook handler exception', [ 'error' => $e->getMessage() ] );
		}

		return new \WP_REST_Response( [ 'status' => 'ok' ], 200 );
	}
}
