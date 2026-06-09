<?php
/**
 * Main plugin class.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once KLIENTOORA_CARD_PATH . 'includes/class-klientoora-card-loader.php';
require_once KLIENTOORA_CARD_PATH . 'admin/class-klientoora-card-admin.php';
require_once KLIENTOORA_CARD_PATH . 'public/class-klientoora-card-public.php';

/**
 * Coordinates plugin hooks and dependencies.
 */
class Klientoora_Card {

	/**
	 * Hook loader instance.
	 *
	 * @var Klientoora_Card_Loader
	 */
	protected $loader;

	/**
	 * Plugin text domain.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_name = 'klientoora-card';
		$this->version     = KLIENTOORA_CARD_VERSION;
		$this->loader      = new Klientoora_Card_Loader();

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Loads plugin textdomain.
	 *
	 * @return void
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
	}

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Klientoora_Card_Admin( $this->get_plugin_name(), $this->get_version() );
		$admin_menu   = new Klientoora_Card_Admin_Menu();
		$user_profile = new Klientoora_Card_User_Profile();
		$membership   = new Klientoora_Card_Membership_Status();
		$product_redemption = new Klientoora_Card_Product_Redemption();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $membership, 'migrate_existing_statuses' );
		$this->loader->add_action( 'admin_init', $admin_menu, 'register_settings' );
		$this->loader->add_action( 'admin_menu', $admin_menu, 'register_menu_page' );
		$this->loader->add_action( 'admin_post_klientoora_card_test_points_sync', $admin_menu, 'handle_test_points_sync' );
		$this->loader->add_action( 'admin_post_klientoora_card_save_coupon', $admin_menu, 'handle_save_coupon' );
		$this->loader->add_action( 'admin_post_klientoora_card_delete_coupon', $admin_menu, 'handle_delete_coupon' );
		$this->loader->add_action( 'admin_post_klientoora_card_save_challenge', $admin_menu, 'handle_save_challenge' );
		$this->loader->add_action( 'admin_post_klientoora_card_create_product', $admin_menu, 'handle_create_product' );
		$this->loader->add_action( 'admin_post_klientoora_card_save_points_earning', $admin_menu, 'handle_save_points_earning' );
		$this->loader->add_action( 'admin_post_klientoora_card_save_product_redemptions', $admin_menu, 'handle_save_product_redemptions' );
		$this->loader->add_action( 'admin_post_klientoora_card_save_order_statuses', $admin_menu, 'handle_save_order_statuses' );
		$this->loader->add_action( 'admin_post_klientoora_card_advance_order_status', $admin_menu, 'handle_advance_order_status' );
		$this->loader->add_action( 'add_meta_boxes', $admin_menu, 'register_order_meta_box' );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $admin_menu, 'save_order_meta_box', 60, 2 );
		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $product_redemption, 'render_product_fields' );
		$this->loader->add_action( 'woocommerce_admin_process_product_object', $product_redemption, 'save_product_fields' );
		$this->loader->add_action( 'show_user_profile', $user_profile, 'render_profile_fields' );
		$this->loader->add_action( 'edit_user_profile', $user_profile, 'render_profile_fields' );
		$this->loader->add_action( 'personal_options_update', $user_profile, 'save_profile_fields' );
		$this->loader->add_action( 'edit_user_profile_update', $user_profile, 'save_profile_fields' );
	}

	/**
	 * Registers public hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public       = new Klientoora_Card_Public( $this->get_plugin_name(), $this->get_version() );
		$admin_main_page     = new Klientoora_Card_Admin_Main_Page();
		$coupon_validation   = new Klientoora_Card_Coupon_Validation();
		$checkout_redemption = new Klientoora_Card_Checkout_Redemption();
		$order_points        = new Klientoora_Card_Order_Points();
		$product_redemption  = new Klientoora_Card_Product_Redemption();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $admin_main_page, 'register_rewrite_rule' );
		$this->loader->add_filter( 'query_vars', $admin_main_page, 'register_query_var' );
		$this->loader->add_filter( 'template_include', $admin_main_page, 'load_template' );
		$this->loader->add_action( 'wp_enqueue_scripts', $admin_main_page, 'enqueue_assets' );
		$this->loader->add_filter( 'show_admin_bar', $admin_main_page, 'hide_admin_bar' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'render_floating_club_button' );
		$this->loader->add_action( 'admin_post_nopriv_klientoora_card_register_member', $plugin_public, 'handle_member_registration' );
		$this->loader->add_action( 'admin_post_klientoora_card_register_member', $plugin_public, 'handle_member_registration' );
		$this->loader->add_action( 'wp_ajax_nopriv_klientoora_card_register_member', $plugin_public, 'handle_member_registration_ajax' );
		$this->loader->add_action( 'wp_ajax_klientoora_card_register_member', $plugin_public, 'handle_member_registration_ajax' );
		$this->loader->add_action( 'wp_ajax_klientoora_card_redeem_challenge', $plugin_public, 'handle_redeem_challenge' );
		$this->loader->add_filter( 'woocommerce_coupon_is_valid', $coupon_validation, 'validate_members_only_coupon', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_totals_coupon_label', $checkout_redemption, 'filter_checkout_coupon_label', 10, 2 );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $checkout_redemption, 'render_checkout_box' );
		$this->loader->add_action( 'woocommerce_checkout_before_order_review', $checkout_redemption, 'render_checkout_box' );
		$this->loader->add_action( 'woocommerce_review_order_before_payment', $checkout_redemption, 'render_checkout_box' );
		$this->loader->add_action( 'wp_footer', $checkout_redemption, 'render_checkout_page_fallback', 20 );
		$this->loader->add_action( 'wp_ajax_klientoora_card_apply_loyalty_coupon', $checkout_redemption, 'handle_apply_loyalty_coupon' );
		$this->loader->add_action( 'wp_ajax_klientoora_card_redeem_product', $product_redemption, 'handle_redeem_product' );
		$this->loader->add_action( 'wp_ajax_klientoora_card_get_points_balance', $product_redemption, 'handle_get_points_balance' );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $checkout_redemption, 'ensure_fixed_member_coupon_applied', 5 );
		$this->loader->add_action( 'woocommerce_cart_loaded_from_session', $checkout_redemption, 'ensure_fixed_member_coupon_applied', 20 );
		$this->loader->add_action( 'woocommerce_removed_coupon', $checkout_redemption, 'restore_fixed_member_coupon_if_removed', 10, 1 );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $product_redemption, 'set_cart_item_redemption_price', 20 );
		$this->loader->add_filter( 'woocommerce_get_item_data', $product_redemption, 'display_cart_item_redemption_data', 10, 2 );
		$this->loader->add_action( 'woocommerce_remove_cart_item', $product_redemption, 'restore_points_from_removed_cart_item', 10, 2 );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $product_redemption, 'restore_points_after_removed_cart_item', 10, 2 );
		$this->loader->add_action( 'woocommerce_restore_cart_item', $product_redemption, 'deduct_points_from_restored_cart_item', 10, 2 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $product_redemption, 'save_order_item_redemption_meta', 10, 3 );
		$this->loader->add_action( 'woocommerce_checkout_create_order', $product_redemption, 'mark_checkout_redemption_order' );
		$this->loader->add_action( 'woocommerce_store_api_checkout_update_order_meta', $product_redemption, 'mark_checkout_redemption_order' );
		$this->loader->add_action( 'woocommerce_payment_complete', $order_points, 'award_points_for_order', 20 );
		$this->loader->add_action( 'woocommerce_order_status_completed', $order_points, 'award_points_for_order', 20 );
	}

	/**
	 * Loads the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->plugin_name,
			false,
			dirname( KLIENTOORA_CARD_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Runs all registered hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Returns the plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Returns the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
