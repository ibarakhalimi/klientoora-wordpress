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
		flush_rewrite_rules();
	}
}
