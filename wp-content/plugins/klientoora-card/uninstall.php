<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'klientoora_card_version' );
