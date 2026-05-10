<?php

namespace Line_Bridge_WP\Admin;

use Line_Bridge_WP\Database\DB_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings_Page {

	public function __construct( private DB_Templates $db_templates ) {}

	public function register(): void {
		$this->register_login_settings();
		$this->register_messaging_settings();
		$this->register_notification_settings();
		$this->register_liff_settings();
		$this->register_advanced_settings();
	}

	private function register_login_settings(): void {
		register_setting( 'line_bridge_login', 'line_bridge_login_channel_id',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_login', 'line_bridge_login_channel_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_login', 'line_bridge_login_auto_register',  [ 'sanitize_callback' => 'absint' ] );
	}

	private function register_messaging_settings(): void {
		register_setting( 'line_bridge_messaging', 'line_bridge_messaging_channel_token',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_messaging', 'line_bridge_messaging_channel_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_messaging', 'line_bridge_admin_line_id',            [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_messaging', 'line_bridge_admin_recipient_type',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'line_bridge_messaging', 'line_bridge_send_welcome',             [ 'sanitize_callback' => 'absint' ] );
	}

	private function register_notification_settings(): void {
		register_setting( 'line_bridge_notifications', 'line_bridge_notification_flags', [
			'sanitize_callback' => function ( $value ) {
				if ( ! is_array( $value ) ) {
					return serialize( [] );
				}
				$allowed_keys = [ 'new_user', 'new_order', 'order_placed', 'order_status', 'low_stock', 'out_of_stock', 'payment_slip' ];
				$clean        = [];
				foreach ( $allowed_keys as $key ) {
					$clean[ $key ] = ! empty( $value[ $key ] );
				}
				return serialize( $clean );
			},
		] );
		register_setting( 'line_bridge_notifications', 'line_bridge_low_stock_threshold', [ 'sanitize_callback' => 'absint' ] );
	}

	private function register_liff_settings(): void {
		register_setting( 'line_bridge_liff', 'line_bridge_liff_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
	}

	private function register_advanced_settings(): void {
		register_setting( 'line_bridge_advanced', 'line_bridge_delete_data_on_uninstall', [ 'sanitize_callback' => 'absint' ] );
	}
}
