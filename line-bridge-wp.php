<?php
/**
 * Plugin Name:       LINE Bridge WP
 * Plugin URI:        https://github.com/triple-tgg/line-bridge-wp
 * Description:       เชื่อมต่อ WordPress + WooCommerce กับ LINE Login และ LINE Messaging API รองรับการล็อกอินด้วย LINE, แจ้งเตือนออเดอร์, สต็อกสินค้า และการส่งข้อความถึงลูกค้าผ่าน LINE Bot
 * Version:           1.0.0
 * Requires at least: 6.9.4
 * Requires PHP:      8.1
 * Author:            LINE Bridge WP
 * Text Domain:       line-bridge-wp
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 10.7.0
 * WC tested up to:      10.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LINE_BRIDGE_WP_VERSION',    '1.0.0' );
define( 'LINE_BRIDGE_WP_DB_VERSION', '1.0.0' );
define( 'LINE_BRIDGE_WP_FILE',       __FILE__ );
define( 'LINE_BRIDGE_WP_PATH',       plugin_dir_path( __FILE__ ) );
define( 'LINE_BRIDGE_WP_URL',        plugin_dir_url( __FILE__ ) );
define( 'LINE_BRIDGE_WP_SLUG',       'line-bridge-wp' );
define( 'LINE_BRIDGE_WP_BASENAME',   plugin_basename( __FILE__ ) );

require_once LINE_BRIDGE_WP_PATH . 'includes/class-autoloader.php';
Line_Bridge_WP\Autoloader::register();

register_activation_hook( __FILE__, [ 'Line_Bridge_WP\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Line_Bridge_WP\\Deactivator', 'deactivate' ] );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			LINE_BRIDGE_WP_FILE,
			true
		);
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'LINE Bridge WP ต้องการให้ WooCommerce ทำงานอยู่ก่อน กรุณาติดตั้งและเปิดใช้งาน WooCommerce', 'line-bridge-wp' )
				. '</p></div>';
		} );
		return;
	}
	Line_Bridge_WP\Plugin::get_instance()->init();
} );
