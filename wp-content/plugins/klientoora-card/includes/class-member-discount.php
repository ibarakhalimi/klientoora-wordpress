<?php
/**
 * WooCommerce fixed member discount.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies and records fixed loyalty member discounts.
 */
class Klientoora_Card_Member_Discount {

	/**
	 * Discount option key.
	 */
	const OPTION_KEY = 'loyalty_member_discount_percentage';

	/**
	 * Discount fee name.
	 */
	const FEE_NAME = 'Loyalty member discount';

	/**
	 * Applies a fixed member discount as a negative WooCommerce fee.
	 *
	 * @param WC_Cart $cart WooCommerce cart.
	 *
	 * @return void
	 */
	public function apply_member_discount_fee( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->current_user_can_receive_discount() ) {
			return;
		}

		$percentage      = $this->get_discount_percentage();
		$discount_amount = $this->calculate_discount_amount( $cart, $percentage );

		if ( 0 >= $discount_amount ) {
			return;
		}

		$cart->add_fee( self::FEE_NAME, -1 * $discount_amount, false );
	}

	/**
	 * Saves applied member discount details to order meta.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return void
	 */
	public function save_member_discount_to_order( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$user_id = absint( $order->get_user_id() );

		if ( 0 === $user_id || ! Klientoora_Card_Membership_Status::is_active( $user_id ) ) {
			return;
		}

		$percentage      = $this->get_discount_percentage();
		$discount_amount = $this->get_order_discount_amount( $order );

		if ( 0 >= $percentage || 0 >= $discount_amount ) {
			return;
		}

		$order->update_meta_data( 'loyalty_member_discount_amount', $discount_amount );
		$order->update_meta_data( 'loyalty_member_discount_percentage', $percentage );
	}

	/**
	 * Saves applied member discount details to a created order and persists it.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return void
	 */
	public function save_member_discount_to_created_order( $order ) {
		$this->save_member_discount_to_order( $order );

		if ( $order && is_a( $order, 'WC_Order' ) ) {
			$order->save();
		}
	}

	/**
	 * Saves applied member discount details to processed classic checkout orders.
	 *
	 * @param int      $order_id    WooCommerce order ID.
	 * @param array    $posted_data Posted checkout data.
	 * @param WC_Order $order       WooCommerce order.
	 *
	 * @return void
	 */
	public function save_member_discount_to_processed_order( $order_id, $posted_data = array(), $order = null ) {
		unset( $posted_data );

		if ( ! $order && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}

		$this->save_member_discount_to_order( $order );

		if ( $order ) {
			$order->save();
		}
	}

	/**
	 * Sends a Make webhook event when the fixed member discount changes.
	 *
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $value     New option value.
	 * @param string $option    Option name.
	 *
	 * @return void
	 */
	public function sync_discount_update_to_make( $old_value, $value, $option = self::OPTION_KEY ) {
		if ( self::OPTION_KEY !== $option ) {
			return;
		}

		$old_percentage = (float) $old_value;
		$new_percentage = (float) $value;

		if ( $old_percentage === $new_percentage ) {
			return;
		}

		$this->send_discount_webhook( $new_percentage );
	}

	/**
	 * Sends a Make webhook event when the fixed member discount option is created.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 *
	 * @return void
	 */
	public function sync_new_discount_to_make( $option, $value ) {
		if ( self::OPTION_KEY !== $option ) {
			return;
		}

		$this->send_discount_webhook( (float) $value );
	}

	/**
	 * Checks whether the current user can receive the member discount.
	 *
	 * @return bool
	 */
	private function current_user_can_receive_discount() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return Klientoora_Card_Membership_Status::is_active( get_current_user_id() );
	}

	/**
	 * Returns the configured discount percentage.
	 *
	 * @return float
	 */
	private function get_discount_percentage() {
		return (float) get_option( self::OPTION_KEY, 0 );
	}

	/**
	 * Calculates the member discount amount for a cart.
	 *
	 * @param WC_Cart $cart       WooCommerce cart.
	 * @param float   $percentage Discount percentage.
	 *
	 * @return float
	 */
	private function calculate_discount_amount( $cart, $percentage ) {
		if ( 0 >= $percentage ) {
			return 0;
		}

		$discount_base = max( 0, (float) $cart->get_cart_contents_total() );
		$discount      = $discount_base * ( $percentage / 100 );

		return round( min( $discount, $discount_base ), wc_get_price_decimals() );
	}

	/**
	 * Gets the discount amount from order fee items.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return float
	 */
	private function get_order_discount_amount( $order ) {
		$discount_amount = 0;

		foreach ( $order->get_fees() as $fee ) {
			if ( self::FEE_NAME !== $fee->get_name() ) {
				continue;
			}

			$discount_amount += abs( (float) $fee->get_total() );
		}

		return round( $discount_amount, wc_get_price_decimals() );
	}

	/**
	 * Sends the discount sync payload to Make.
	 *
	 * @param float $discount_percentage Discount percentage.
	 *
	 * @return void
	 */
	private function send_discount_webhook( $discount_percentage ) {
		$webhook_url = get_option( 'klientoora_card_make_webhook_url', '' );

		if ( '' === $webhook_url ) {
			return;
		}

		wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'action'              => 'sync_member_discount',
						'discount_percentage' => $discount_percentage,
						'timestamp'           => gmdate( 'c' ),
						'source'              => 'wordpress_loyalty_plugin',
					)
				),
			)
		);
	}
}
