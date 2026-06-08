<?php
/**
 * Admin functionality.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin-facing functionality.
 */
class Klientoora_Card_Admin {

	/**
	 * Plugin text domain.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin text domain.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueues admin styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			KLIENTOORA_CARD_URL . 'admin/css/klientoora-card-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			KLIENTOORA_CARD_URL . 'admin/js/klientoora-card-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'loyalty-club-coupons' !== $page ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-coupon-templates',
			KLIENTOORA_CARD_URL . 'assets/js/admin-coupon-templates.js',
			array(),
			$this->version,
			true
		);
	}
}
