<?php

namespace Line_Bridge_WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader {

	private static array $class_map = [
		'Line_Bridge_WP\\Plugin'                              => 'includes/class-line-bridge-wp.php',
		'Line_Bridge_WP\\Activator'                           => 'includes/class-activator.php',
		'Line_Bridge_WP\\Deactivator'                         => 'includes/class-deactivator.php',
		'Line_Bridge_WP\\I18n'                                => 'includes/class-i18n.php',

		// API
		'Line_Bridge_WP\\Api\\Webhook_Controller'             => 'includes/api/class-webhook-controller.php',
		'Line_Bridge_WP\\Api\\OAuth_Controller'               => 'includes/api/class-oauth-controller.php',
		'Line_Bridge_WP\\Api\\Admin_Controller'               => 'includes/api/class-admin-controller.php',

		// Auth
		'Line_Bridge_WP\\Auth\\Line_Login'                    => 'includes/auth/class-line-login.php',
		'Line_Bridge_WP\\Auth\\Token_Manager'                 => 'includes/auth/class-token-manager.php',

		// Messaging
		'Line_Bridge_WP\\Messaging\\Line_API_Client'          => 'includes/messaging/class-line-api-client.php',
		'Line_Bridge_WP\\Messaging\\Message_Builder'          => 'includes/messaging/class-message-builder.php',
		'Line_Bridge_WP\\Messaging\\Push_Service'             => 'includes/messaging/class-push-service.php',
		'Line_Bridge_WP\\Messaging\\Webhook_Handler'          => 'includes/messaging/class-webhook-handler.php',

		// Notifications
		'Line_Bridge_WP\\Notifications\\Notification_Manager'         => 'includes/notifications/class-notification-manager.php',
		'Line_Bridge_WP\\Notifications\\Order_Notification'           => 'includes/notifications/class-order-notification.php',
		'Line_Bridge_WP\\Notifications\\Stock_Notification'           => 'includes/notifications/class-stock-notification.php',
		'Line_Bridge_WP\\Notifications\\Admin_Notification'           => 'includes/notifications/class-admin-notification.php',
		'Line_Bridge_WP\\Notifications\\Payment_Slip_Notification'    => 'includes/notifications/class-payment-slip-notification.php',

		// LIFF
		'Line_Bridge_WP\\Liff\\Liff_Manager'                  => 'includes/liff/class-liff-manager.php',

		// Database
		'Line_Bridge_WP\\Database\\DB_Users'                  => 'includes/database/class-db-users.php',
		'Line_Bridge_WP\\Database\\DB_Notifications'          => 'includes/database/class-db-notifications.php',
		'Line_Bridge_WP\\Database\\DB_Templates'              => 'includes/database/class-db-templates.php',

		// Utilities
		'Line_Bridge_WP\\Utilities\\Logger'                   => 'includes/utilities/class-logger.php',
		'Line_Bridge_WP\\Utilities\\Crypto'                   => 'includes/utilities/class-crypto.php',
		'Line_Bridge_WP\\Utilities\\Template_Renderer'        => 'includes/utilities/class-template-renderer.php',

		// Admin
		'Line_Bridge_WP\\Admin\\Admin'                        => 'admin/class-admin.php',
		'Line_Bridge_WP\\Admin\\Settings_Page'                => 'admin/class-settings-page.php',
		'Line_Bridge_WP\\Admin\\Users_List_Table'             => 'admin/class-users-list-table.php',
		'Line_Bridge_WP\\Admin\\Logs_List_Table'              => 'admin/class-logs-list-table.php',

		// Public
		'Line_Bridge_WP\\Frontend\\PublicFrontend'            => 'public/class-public.php',
	];

	public static function register(): void {
		spl_autoload_register( [ static::class, 'autoload' ] );
	}

	public static function autoload( string $class ): void {
		if ( ! isset( self::$class_map[ $class ] ) ) {
			return;
		}
		$file = LINE_BRIDGE_WP_PATH . self::$class_map[ $class ];
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
