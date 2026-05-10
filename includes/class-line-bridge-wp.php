<?php

namespace Line_Bridge_WP;

use Line_Bridge_WP\Api\Admin_Controller;
use Line_Bridge_WP\Api\OAuth_Controller;
use Line_Bridge_WP\Api\Webhook_Controller;
use Line_Bridge_WP\Auth\Line_Login;
use Line_Bridge_WP\Auth\Token_Manager;
use Line_Bridge_WP\Database\DB_Notifications;
use Line_Bridge_WP\Database\DB_Templates;
use Line_Bridge_WP\Database\DB_Users;
use Line_Bridge_WP\Frontend\PublicFrontend;
use Line_Bridge_WP\Liff\Liff_Manager;
use Line_Bridge_WP\Messaging\Line_API_Client;
use Line_Bridge_WP\Messaging\Message_Builder;
use Line_Bridge_WP\Messaging\Push_Service;
use Line_Bridge_WP\Messaging\Webhook_Handler;
use Line_Bridge_WP\Notifications\Admin_Notification;
use Line_Bridge_WP\Notifications\Notification_Manager;
use Line_Bridge_WP\Notifications\Order_Notification;
use Line_Bridge_WP\Notifications\Payment_Slip_Notification;
use Line_Bridge_WP\Notifications\Stock_Notification;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	private static ?Plugin $instance = null;

	private DB_Users $db_users;
	private DB_Notifications $db_notifications;
	private DB_Templates $db_templates;
	private Line_API_Client $api_client;
	private Message_Builder $msg_builder;
	private Push_Service $push_service;
	private Token_Manager $token_manager;
	private Notification_Manager $notif_manager;
	private Line_Login $line_login;
	private Liff_Manager $liff_manager;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		( new I18n() )->load_plugin_textdomain();

		$this->db_users         = new DB_Users();
		$this->db_notifications = new DB_Notifications();
		$this->db_templates     = new DB_Templates();

		$this->api_client    = new Line_API_Client();
		$this->msg_builder   = new Message_Builder( $this->db_templates );
		$this->push_service  = new Push_Service( $this->api_client, $this->db_notifications );
		$this->token_manager = new Token_Manager( $this->db_users );
		$this->notif_manager = new Notification_Manager( $this->push_service, $this->msg_builder );
		$this->line_login    = new Line_Login( $this->token_manager, $this->db_users );
		$this->liff_manager  = new Liff_Manager();

		$webhook_handler = new Webhook_Handler( $this->notif_manager );

		new Order_Notification( $this->notif_manager, $this->db_users, $this->db_notifications );
		new Stock_Notification( $this->notif_manager );
		new Admin_Notification( $this->notif_manager );
		new Payment_Slip_Notification( $this->notif_manager, $this->msg_builder );

		if ( is_admin() ) {
			new Admin\Admin( $this->db_users, $this->db_notifications, $this->db_templates, $this->api_client );
		}

		new PublicFrontend( $this->line_login, $this->db_users );

		add_action( 'rest_api_init', function () use ( $webhook_handler ) {
			( new Webhook_Controller( $this->api_client, $webhook_handler ) )->register_routes();
			( new OAuth_Controller( $this->line_login, $this->notif_manager, $this->db_users ) )->register_routes();
			( new Admin_Controller( $this->api_client, $this->line_login, $this->db_users ) )->register_routes();
		} );

		$this->schedule_cron();
		add_filter( 'plugin_action_links_' . LINE_BRIDGE_WP_BASENAME, [ $this, 'plugin_action_links' ] );
	}

	private function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'line_bridge_retry_failed_notifications' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'line_bridge_retry_failed_notifications' );
		}
		add_action( 'line_bridge_retry_failed_notifications', function () {
			$this->push_service->retry_failed();
		} );
		add_filter( 'cron_schedules', function ( array $schedules ) {
			$schedules['fifteen_minutes'] = [
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'ทุก 15 นาที', 'line-bridge-wp' ),
			];
			return $schedules;
		} );
	}

	public function plugin_action_links( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=line-bridge-wp' ) ) . '">'
			. esc_html__( 'ตั้งค่า', 'line-bridge-wp' )
			. '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
