<?php

namespace Line_Bridge_WP\Notifications;

use Line_Bridge_WP\Utilities\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Notification {

	public function __construct( private Notification_Manager $notif_manager ) {
		$this->hooks();
	}

	private function hooks(): void {
		add_action( 'user_register', [ $this, 'on_new_user' ], 10, 1 );
	}

	public function on_new_user( int $wp_user_id ): void {
		if ( ! $this->notif_manager->flag_enabled( 'new_user' ) ) {
			return;
		}
		$user = get_userdata( $wp_user_id );
		if ( ! $user ) {
			return;
		}
		$vars = Template_Renderer::get_user_vars( $user );
		$this->notif_manager->send_to_admin( 'admin_new_user', $vars, [
			'wp_user_id'     => $wp_user_id,
			'reference_id'   => $wp_user_id,
			'reference_type' => 'user',
		] );
	}
}
