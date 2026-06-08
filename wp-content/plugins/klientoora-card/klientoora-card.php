<?php
/**
 * Plugin Name:       Klientoora Card
 * Plugin URI:        https://example.com/klientoora-card
 * Description:       A WordPress plugin scaffold for Klientoora card features.
 * Version:           0.2.1
 * Author:            Klientoora
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       klientoora-card
 * Domain Path:       /languages
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KLIENTOORA_CARD_VERSION', '0.2.1' );
define( 'KLIENTOORA_CARD_FILE', __FILE__ );
define( 'KLIENTOORA_CARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'KLIENTOORA_CARD_URL', plugin_dir_url( __FILE__ ) );
define( 'KLIENTOORA_CARD_BASENAME', plugin_basename( __FILE__ ) );

require_once KLIENTOORA_CARD_PATH . 'includes/class-klientoora-card-activator.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-klientoora-card-deactivator.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-admin-menu.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-checkout-redemption.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-coupon-validation.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-member-discount.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-membership-status.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-points.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-order-points.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-product-redemption.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-user-profile.php';
require_once KLIENTOORA_CARD_PATH . 'includes/class-klientoora-card.php';

register_activation_hook( __FILE__, array( 'Klientoora_Card_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Klientoora_Card_Deactivator', 'deactivate' ) );

/**
 * Starts the plugin.
 *
 * @return void
 */
function klientoora_card_run() {
	$plugin = new Klientoora_Card();
	$plugin->run();
}

klientoora_card_run();
