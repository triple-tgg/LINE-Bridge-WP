<?php

namespace Line_Bridge_WP\Frontend;

use Line_Bridge_WP\Auth\Line_Login;
use Line_Bridge_WP\Database\DB_Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PublicFrontend {

	public function __construct(
		private Line_Login $line_login,
		private DB_Users $db_users
	) {
		$this->hooks();
	}

	private function hooks(): void {
		add_action( 'login_enqueue_scripts',        [ $this, 'enqueue_assets' ] );
		add_action( 'wp_enqueue_scripts',           [ $this, 'enqueue_assets' ] );
		add_action( 'wp_login_form',                [ $this, 'render_login_button' ] );
		add_action( 'woocommerce_login_form',       [ $this, 'render_login_button' ] );
		add_action( 'woocommerce_register_form',    [ $this, 'render_login_button' ] );
		add_action( 'login_message',                [ $this, 'show_line_error_message' ] );
		add_action( 'init',                         [ $this, 'add_my_account_endpoint' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_my_account_menu_item' ] );
		add_action( 'woocommerce_account_line-bridge_endpoint', [ $this, 'render_my_account_section' ] );
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(
			'line-bridge-public',
			LINE_BRIDGE_WP_URL . 'assets/css/public.css',
			[],
			LINE_BRIDGE_WP_VERSION
		);
	}

	public function render_login_button(): void {
		if ( ! get_option( 'line_bridge_login_channel_id' ) ) {
			return;
		}
		echo '<div class="line-bridge-separator"><span>' . esc_html__( 'หรือ', 'line-bridge-wp' ) . '</span></div>';
		echo wp_kses_post( $this->line_login->render_login_button() );
	}

	public function show_line_error_message( string $message ): string {
		$error = isset( $_GET['line_error'] ) ? sanitize_text_field( wp_unslash( $_GET['line_error'] ) ) : '';
		if ( $error ) {
			$message .= '<div class="message error"><p>'
				. esc_html( urldecode( $error ) )
				. '</p></div>';
		}
		return $message;
	}

	public function add_my_account_endpoint(): void {
		add_rewrite_endpoint( 'line-bridge', EP_ROOT | EP_PAGES );
	}

	public function add_my_account_menu_item( array $items ): array {
		$items['line-bridge'] = __( 'เชื่อมต่อ LINE', 'line-bridge-wp' );
		return $items;
	}

	public function render_my_account_section(): void {
		$user_id  = get_current_user_id();
		$line_row = $this->db_users->get_by_wp_user_id( $user_id );
		include LINE_BRIDGE_WP_PATH . 'public/partials/account-link.php';
	}
}
