<?php

namespace Line_Bridge_WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
		wp_clear_scheduled_hook( 'line_bridge_retry_failed_notifications' );
	}
}
