<?php

namespace Line_Bridge_WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I18n {

	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'line-bridge-wp',
			false,
			dirname( LINE_BRIDGE_WP_BASENAME ) . '/languages/'
		);
	}
}
