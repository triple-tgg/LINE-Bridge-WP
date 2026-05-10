<?php

namespace Line_Bridge_WP\Api;

use Line_Bridge_WP\Messaging\Line_API_Client;
use Line_Bridge_WP\Auth\Line_Login;
use Line_Bridge_WP\Database\DB_Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Controller {

	public function __construct(
		private Line_API_Client $api_client,
		private Line_Login $line_login,
		private DB_Users $db_users
	) {}

	public function register_routes(): void {
		register_rest_route( 'line-bridge/v1', '/admin/test-message', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'test_message' ],
			'permission_callback' => [ $this, 'require_admin' ],
		] );

		register_rest_route( 'line-bridge/v1', '/admin/unlink-user', [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'unlink_user' ],
			'permission_callback' => [ $this, 'require_admin' ],
		] );

		register_rest_route( 'line-bridge/v1', '/order-status', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_order_status' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'order'     => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'line_token'=> [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );
	}

	public function test_message( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! check_ajax_referer( 'line_bridge_admin', 'nonce', false ) ) {
			return new \WP_Error( 'invalid_nonce', 'Invalid nonce', [ 'status' => 403 ] );
		}

		$recipient = get_option( 'line_bridge_admin_line_id', '' );
		if ( empty( $recipient ) ) {
			return new \WP_Error( 'no_recipient', 'Admin LINE ID not configured', [ 'status' => 400 ] );
		}

		$result = $this->api_client->push_message( $recipient, [
			[
				'type' => 'text',
				'text' => '✅ ทดสอบการเชื่อมต่อ LINE Bridge WP สำเร็จ! — ' . get_bloginfo( 'name' ),
			],
		] );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'send_failed', $result->get_error_message(), [ 'status' => 500 ] );
		}

		return new \WP_REST_Response( [ 'success' => true, 'message' => 'ส่งข้อความสำเร็จ' ], 200 );
	}

	public function unlink_user( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! check_ajax_referer( 'line_bridge_admin', 'nonce', false ) ) {
			return new \WP_Error( 'invalid_nonce', 'Invalid nonce', [ 'status' => 403 ] );
		}

		$wp_user_id = absint( $request->get_param( 'user_id' ) );
		if ( ! $wp_user_id ) {
			return new \WP_Error( 'invalid_user', 'Invalid user ID', [ 'status' => 400 ] );
		}

		$result = $this->line_login->unlink_account( $wp_user_id );
		if ( ! $result ) {
			return new \WP_Error( 'not_found', 'No linked LINE account', [ 'status' => 404 ] );
		}

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function get_order_status( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$order_id   = $request->get_param( 'order' );
		$line_token = $request->get_param( 'line_token' );

		$profile_response = wp_remote_get( 'https://api.line.me/v2/profile', [
			'headers' => [ 'Authorization' => 'Bearer ' . $line_token ],
		] );

		if ( is_wp_error( $profile_response ) ) {
			return new \WP_Error( 'auth_failed', 'Cannot verify LINE token', [ 'status' => 401 ] );
		}

		$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );
		if ( empty( $profile['userId'] ) ) {
			return new \WP_Error( 'auth_failed', 'Invalid LINE token', [ 'status' => 401 ] );
		}

		$line_row = $this->db_users->get_by_line_user_id( $profile['userId'] );
		if ( ! $line_row ) {
			return new \WP_Error( 'not_linked', 'LINE account not linked', [ 'status' => 403 ] );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || (int) $order->get_customer_id() !== (int) $line_row->wp_user_id ) {
			return new \WP_Error( 'not_found', 'Order not found', [ 'status' => 404 ] );
		}

		$status_labels = wc_get_order_statuses();
		$status_key    = 'wc-' . $order->get_status();

		return new \WP_REST_Response( [
			'order_number' => $order->get_order_number(),
			'status'       => $status_labels[ $status_key ] ?? $order->get_status(),
			'total'        => $order->get_formatted_order_total(),
			'date'         => $order->get_date_created()?->date( 'd/m/Y H:i' ),
			'items'        => array_map( function ( $item ) {
				return [
					'name'     => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'total'    => wc_price( $item->get_total() ),
				];
			}, array_values( $order->get_items() ) ),
		], 200 );
	}

	public function require_admin(): bool|\WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'forbidden', 'Insufficient permissions', [ 'status' => 403 ] );
		}
		return true;
	}
}
