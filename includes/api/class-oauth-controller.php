<?php

namespace Line_Bridge_WP\Api;

use Line_Bridge_WP\Auth\Line_Login;
use Line_Bridge_WP\Notifications\Notification_Manager;
use Line_Bridge_WP\Database\DB_Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OAuth_Controller {

	public function __construct(
		private Line_Login $line_login,
		private ?Notification_Manager $notif_manager = null,
		private ?DB_Users $db_users = null
	) {}

	public function register_routes(): void {
		register_rest_route( 'line-bridge/v1', '/oauth/callback', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_callback' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'code'  => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'state' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'error' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );

		register_rest_route( 'line-bridge/v1', '/oauth/link', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_link' ],
			'permission_callback' => [ $this, 'require_logged_in' ],
		] );

		register_rest_route( 'line-bridge/v1', '/oauth/unlink', [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'handle_unlink' ],
			'permission_callback' => [ $this, 'require_logged_in' ],
		] );
	}

	public function handle_callback( \WP_REST_Request $request ): void {
		$login_url = wp_login_url();
		$redirect  = home_url();

		if ( $request->get_param( 'error' ) ) {
			wp_redirect( add_query_arg( 'line_error', rawurlencode( $request->get_param( 'error' ) ), $login_url ) );
			exit;
		}

		$code  = $request->get_param( 'code' );
		$state = $request->get_param( 'state' );

		if ( ! $code || ! $state ) {
			wp_redirect( add_query_arg( 'line_error', 'missing_params', $login_url ) );
			exit;
		}

		$wp_user = $this->line_login->handle_callback( $code, $state );

		if ( is_wp_error( $wp_user ) ) {
			wp_redirect( add_query_arg( 'line_error', rawurlencode( $wp_user->get_error_message() ), $login_url ) );
			exit;
		}

		wp_set_auth_cookie( $wp_user->ID, true );
		do_action( 'wp_login', $wp_user->user_login, $wp_user );

		$is_new = get_user_meta( $wp_user->ID, '_line_bridge_welcomed', true );
		if ( ! $is_new && $this->notif_manager ) {
			update_user_meta( $wp_user->ID, '_line_bridge_welcomed', '1' );
			$line_row = $this->db_users ? $this->db_users->get_by_wp_user_id( $wp_user->ID ) : null;
			if ( $line_row ) {
				$this->notif_manager->send_to_customer(
					'welcome',
					$line_row->line_user_id,
					$wp_user->ID,
					[ 'CUSTOMER_NAME' => $wp_user->display_name ]
				);
			}
		}

		$redirect = apply_filters( 'line_bridge_login_redirect', wc_get_page_permalink( 'myaccount' ), $wp_user );
		wp_redirect( esc_url_raw( $redirect ) );
		exit;
	}

	public function handle_link( \WP_REST_Request $request ): \WP_REST_Response {
		$url = $this->line_login->get_authorization_url();
		return new \WP_REST_Response( [ 'redirect_url' => $url ], 200 );
	}

	public function handle_unlink( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$user_id = get_current_user_id();
		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new \WP_Error( 'invalid_nonce', 'Invalid nonce', [ 'status' => 403 ] );
		}
		$result = $this->line_login->unlink_account( $user_id );
		if ( ! $result ) {
			return new \WP_Error( 'unlink_failed', 'No linked account found', [ 'status' => 404 ] );
		}
		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function require_logged_in(): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'not_logged_in', 'Authentication required', [ 'status' => 401 ] );
		}
		return true;
	}
}
