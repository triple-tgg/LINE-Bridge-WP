<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! get_option( 'line_bridge_delete_data_on_uninstall' ) ) {
	return;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}line_bridge_users" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}line_bridge_notifications" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}line_bridge_message_templates" );

$options = [
	'line_bridge_login_channel_id',
	'line_bridge_login_channel_secret',
	'line_bridge_login_redirect_uri',
	'line_bridge_login_auto_register',
	'line_bridge_messaging_channel_token',
	'line_bridge_messaging_channel_secret',
	'line_bridge_admin_line_id',
	'line_bridge_admin_recipient_type',
	'line_bridge_liff_id',
	'line_bridge_notification_flags',
	'line_bridge_low_stock_threshold',
	'line_bridge_send_welcome',
	'line_bridge_delete_data_on_uninstall',
	'line_bridge_db_version',
];
foreach ( $options as $option ) {
	delete_option( $option );
}

wp_clear_scheduled_hook( 'line_bridge_retry_failed_notifications' );
