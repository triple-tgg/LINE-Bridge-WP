<?php

namespace Line_Bridge_WP\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Renderer {

	public static function render( string $body, array $vars ): string {
		foreach ( $vars as $key => $value ) {
			$body = str_replace( '{{' . strtoupper( $key ) . '}}', (string) $value, $body );
		}
		return $body;
	}

	public static function get_order_vars( \WC_Order $order ): array {
		$status_labels = wc_get_order_statuses();
		$status_key    = 'wc-' . $order->get_status();
		return [
			'ORDER_NUMBER'   => $order->get_order_number(),
			'ORDER_TOTAL'    => $order->get_formatted_order_total(),
			'ORDER_STATUS'   => $status_labels[ $status_key ] ?? $order->get_status(),
			'ORDER_URL'      => $order->get_view_order_url(),
			'CUSTOMER_NAME'  => $order->get_formatted_billing_full_name(),
			'DATE_TIME'      => current_time( 'd/m/Y H:i' ),
			'SITE_NAME'      => get_bloginfo( 'name' ),
			'TRACKING_URL'   => $order->get_view_order_url(),
		];
	}

	public static function get_user_vars( \WP_User $user ): array {
		return [
			'USER_DISPLAY_NAME' => $user->display_name,
			'USER_EMAIL'        => $user->user_email,
			'DATE_TIME'         => current_time( 'd/m/Y H:i' ),
			'SITE_NAME'         => get_bloginfo( 'name' ),
		];
	}

	public static function get_product_vars( \WC_Product $product ): array {
		return [
			'PRODUCT_NAME' => $product->get_name(),
			'STOCK_QTY'    => (string) $product->get_stock_quantity(),
			'DATE_TIME'    => current_time( 'd/m/Y H:i' ),
			'SITE_NAME'    => get_bloginfo( 'name' ),
		];
	}
}
