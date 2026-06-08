<?php
/**
 * WooCommerce coupon validation.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loyalty coupon validation rules.
 */
class Klientoora_Card_Coupon_Validation {

	/**
	 * Validates members-only loyalty coupons.
	 *
	 * @param bool      $is_valid Whether the coupon is valid.
	 * @param WC_Coupon $coupon   WooCommerce coupon.
	 *
	 * @return bool
	 *
	 * @throws Exception When the coupon is members-only and the current user is not an active member.
	 */
	public function validate_members_only_coupon( $is_valid, $coupon ) {
		if ( ! $is_valid || ! $coupon || ! is_a( $coupon, 'WC_Coupon' ) ) {
			return $is_valid;
		}

		if ( 'yes' !== $coupon->get_meta( '_loyalty_members_only' ) ) {
			return $is_valid;
		}

		if ( ! is_user_logged_in() || 'active' !== get_user_meta( get_current_user_id(), 'membership_status', true ) ) {
			throw new Exception( esc_html__( 'קופון זה זמין לחברי מועדון בלבד.', 'klientoora-card' ) );
		}

		return $is_valid;
	}
}
