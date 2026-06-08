<?php
/**
 * Plugin activation tasks.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation.
 */
class Klientoora_Card_Activator {

	/**
	 * Runs on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( class_exists( 'Klientoora_Card_Admin_Main_Page' ) ) {
			Klientoora_Card_Admin_Main_Page::register_rewrite_rule();
		}

		flush_rewrite_rules();
	}
}
