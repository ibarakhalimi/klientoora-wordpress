<?php
/**
 * WooCommerce order points earning.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles earning loyalty points from paid/completed orders.
 */
class Klientoora_Card_Order_Points {

	/**
	 * Awards points for a paid/completed WooCommerce order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return void
	 */
	public function award_points_for_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_meta( '_klientoora_card_points_earned_added' ) ) {
			return;
		}

		$user_id = absint( $order->get_user_id() );

		if ( 0 === $user_id || ! $this->is_loyalty_member( $user_id ) ) {
			return;
		}

		$earned_points = $this->calculate_earned_points( $order );

		if ( 0 >= $earned_points ) {
			return;
		}

		Klientoora_Card_Points::add_points( $user_id, $earned_points, 'order_completed' );

		$order->update_meta_data( 'loyalty_points_earned', $earned_points );
		$order->update_meta_data( '_klientoora_card_points_earned_added', 'yes' );
		$order->save();
	}

	/**
	 * Calculates earned points for an order.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return int
	 */
	private function calculate_earned_points( $order ) {
		$percentage  = (float) get_option( 'klientoora_card_points_earning_percentage', 10 );
		$order_total = (float) $order->get_total();

		if ( 0 >= $percentage || 0 >= $order_total ) {
			return 0;
		}

		return absint( floor( $order_total * ( $percentage / 100 ) ) );
	}

	/**
	 * Checks whether the user is an active loyalty member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function is_loyalty_member( $user_id ) {
		return Klientoora_Card_Membership_Status::is_active( $user_id );
	}
}
