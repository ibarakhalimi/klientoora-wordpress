<?php
/**
 * Standalone admin main front page.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the standalone /admin-main route.
 */
class Klientoora_Card_Admin_Main_Page {

	const QUERY_VAR = 'klientoora_admin_main';

	/**
	 * Registers rewrite rules for the standalone page.
	 *
	 * @return void
	 */
	public static function register_rewrite_rule() {
		add_rewrite_rule( '^admin-main/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Registers the custom query var.
	 *
	 * @param array<int, string> $query_vars Public query vars.
	 *
	 * @return array<int, string>
	 */
	public function register_query_var( $query_vars ) {
		$query_vars[] = self::QUERY_VAR;

		return $query_vars;
	}

	/**
	 * Loads the plugin template instead of the active theme template.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public function load_template( $template ) {
		if ( ! $this->is_admin_main_request() ) {
			return $template;
		}

		return KLIENTOORA_CARD_PATH . 'templates/admin-main.php';
	}

	/**
	 * Enqueues standalone page assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_admin_main_request() ) {
			return;
		}

		$css_path = KLIENTOORA_CARD_PATH . 'assets/css/admin-main.css';
		$js_path  = KLIENTOORA_CARD_PATH . 'assets/js/admin-main.js';

		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'klientoora-card-admin-main',
			KLIENTOORA_CARD_URL . 'assets/css/admin-main.css',
			array(),
			file_exists( $css_path ) ? filemtime( $css_path ) : KLIENTOORA_CARD_VERSION,
			'all'
		);

		wp_enqueue_script(
			'klientoora-card-admin-main',
			KLIENTOORA_CARD_URL . 'assets/js/admin-main.js',
			array(),
			file_exists( $js_path ) ? filemtime( $js_path ) : KLIENTOORA_CARD_VERSION,
			true
		);
	}

	/**
	 * Hides the WordPress admin bar on the standalone page.
	 *
	 * @param bool $show Whether to show the admin bar.
	 *
	 * @return bool
	 */
	public function hide_admin_bar( $show ) {
		return $this->is_admin_main_request() ? false : $show;
	}

	/**
	 * Checks whether the current request is the standalone page.
	 *
	 * @return bool
	 */
	private function is_admin_main_request() {
		return '1' === (string) get_query_var( self::QUERY_VAR );
	}
}
