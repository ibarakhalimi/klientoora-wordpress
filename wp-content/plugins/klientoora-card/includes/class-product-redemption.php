<?php
/**
 * Product redemption with loyalty points.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles point prices for WooCommerce products and product redemption orders.
 */
class Klientoora_Card_Product_Redemption {

	const META_ENABLED = '_klientoora_card_points_redeemable';
	const META_POINTS  = '_klientoora_card_points_price';
	const CART_ITEM_KEY = 'klientoora_card_points_redemption';
	const CART_POINTS_KEY = 'klientoora_card_points_price';
	const CART_USER_KEY = 'klientoora_card_points_user_id';
	const CART_RESTORED_KEY = 'klientoora_card_points_restored';
	const SESSION_LEDGER_KEY = 'klientoora_card_points_cart_ledger';

	/**
	 * Whether this request is creating a checkout order.
	 *
	 * @var bool
	 */
	private $is_creating_redemption_order = false;

	/**
	 * Renders product data fields in WooCommerce.
	 *
	 * @return void
	 */
	public function render_product_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			array(
				'id'          => self::META_ENABLED,
				'label'       => __( 'ניתן לרכישה בנקודות', 'klientoora-card' ),
				'description' => __( 'הצגת המוצר באזור המועדון כמוצר למימוש נקודות.', 'klientoora-card' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => self::META_POINTS,
				'label'             => __( 'שווי נקודות', 'klientoora-card' ),
				'description'       => __( 'מספר הנקודות הדרוש לרכישת המוצר.', 'klientoora-card' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
			)
		);

		echo '</div>';
	}

	/**
	 * Saves product data fields.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	public function save_product_fields( $product ) {
		$enabled = isset( $_POST[ self::META_ENABLED ] ) ? 'yes' : 'no';
		$points  = isset( $_POST[ self::META_POINTS ] ) ? absint( wp_unslash( $_POST[ self::META_POINTS ] ) ) : 0;

		$product->update_meta_data( self::META_ENABLED, $enabled );
		$product->update_meta_data( self::META_POINTS, $points );
	}

	/**
	 * Returns products enabled for point redemption.
	 *
	 * @return array<int, WC_Product>
	 */
	public static function get_redeemable_products() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		$product_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => self::META_ENABLED,
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'key'     => self::META_POINTS,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'post_type'      => 'product',
				'posts_per_page' => -1,
			)
		);
		$products    = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product && self::is_product_redeemable( $product ) ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Returns all editable products for the admin redemption table.
	 *
	 * @return array<int, WC_Product>
	 */
	public static function get_admin_products() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		return wc_get_products(
			array(
				'limit'   => -1,
				'orderby' => 'title',
				'order'   => 'ASC',
				'status'  => array( 'publish', 'private', 'draft' ),
			)
		);
	}

	/**
	 * Returns the configured point price for a product.
	 *
	 * @param WC_Product|int $product Product object or ID.
	 *
	 * @return int
	 */
	public static function get_product_points_price( $product ) {
		$product_id = $product instanceof WC_Product ? $product->get_id() : absint( $product );

		return absint( get_post_meta( $product_id, self::META_POINTS, true ) );
	}

	/**
	 * Checks whether a product has a configured point price.
	 *
	 * @param WC_Product|int $product Product object or ID.
	 *
	 * @return bool
	 */
	public static function has_product_points_price( $product ) {
		return 0 < self::get_product_points_price( $product );
	}

	/**
	 * Checks whether a product can be redeemed with points.
	 *
	 * @param WC_Product|int $product Product object or ID.
	 *
	 * @return bool
	 */
	public static function is_product_redeemable( $product ) {
		$product_id = $product instanceof WC_Product ? $product->get_id() : absint( $product );

		return self::has_product_points_price( $product_id )
			&& 'yes' === get_post_meta( $product_id, self::META_ENABLED, true );
	}

	/**
	 * Handles an AJAX product redemption request.
	 *
	 * @return void
	 */
	public function handle_redeem_product() {
		check_ajax_referer( 'klientoora_card_redeem_product', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'יש להתחבר כדי לממש נקודות.', 'klientoora-card' ) ),
				403
			);
		}

		if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'WC' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'WooCommerce אינו זמין כרגע.', 'klientoora-card' ) ),
				500
			);
		}

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! WC()->cart ) {
			wp_send_json_error(
				array( 'message' => __( 'העגלה אינה זמינה כרגע.', 'klientoora-card' ) ),
				500
			);
		}

		$user_id    = get_current_user_id();
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = wc_get_product( $product_id );

		if ( ! $product || ! self::is_product_redeemable( $product ) || ! $product->is_purchasable() ) {
			wp_send_json_error(
				array( 'message' => __( 'לא ניתן לממש נקודות עבור מוצר זה.', 'klientoora-card' ) ),
				400
			);
		}

		if ( ! $product->is_in_stock() ) {
			wp_send_json_error(
				array( 'message' => __( 'המוצר אינו במלאי.', 'klientoora-card' ) ),
				400
			);
		}

		$points_price = self::get_product_points_price( $product );
		$balance      = absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );

		if ( $this->cart_has_redeemed_product( $product_id ) ) {
			wp_send_json_success(
				array(
					'message'        => __( 'המוצר כבר נמצא בעגלה.', 'klientoora-card' ),
					'cart_url'       => wc_get_cart_url(),
					'points_balance' => $balance,
				)
			);
		}

		if ( $balance < $points_price ) {
			wp_send_json_error(
				array( 'message' => __( 'אין לך מספיק נקודות למימוש המוצר.', 'klientoora-card' ) ),
				400
			);
		}

		$cart_item_key = WC()->cart->add_to_cart(
			$product_id,
			1,
			0,
			array(),
			array(
				self::CART_ITEM_KEY   => true,
				self::CART_POINTS_KEY => $points_price,
				self::CART_USER_KEY   => $user_id,
				'unique_key'         => 'klientoora_points_' . $product_id . '_' . wp_generate_uuid4(),
			)
		);

		if ( ! $cart_item_key ) {
			wp_send_json_error(
				array( 'message' => __( 'אירעה שגיאה בהוספת המוצר לעגלה.', 'klientoora-card' ) ),
				500
			);
		}

		Klientoora_Card_Points::remove_points( $user_id, $points_price, 'product_redemption_cart_add' );
		$this->save_cart_ledger_item( $cart_item_key, $user_id, $product_id, $points_price, false );

		WC()->cart->calculate_totals();

		wp_send_json_success(
			array(
				'message'        => __( 'המוצר נוסף לעגלה והנקודות נשמרו למימוש.', 'klientoora-card' ),
				'cart_url'       => wc_get_cart_url(),
				'points_balance' => max( 0, $balance - $points_price ),
			)
		);
	}

	/**
	 * Makes point redemption cart items free in the cart and checkout.
	 *
	 * @param WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function set_cart_item_redemption_price( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			if ( isset( $cart_item['quantity'] ) && 1 < absint( $cart_item['quantity'] ) ) {
				$cart->set_quantity( $cart_item_key, 1, false );
			}

			$cart_item['data']->set_price( 0 );
		}
	}

	/**
	 * Displays point redemption details in cart item data.
	 *
	 * @param array<int, array<string, string>> $item_data Cart item display data.
	 * @param array                            $cart_item Cart item values.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function display_cart_item_redemption_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) ) {
			return $item_data;
		}

		$points = isset( $cart_item[ self::CART_POINTS_KEY ] ) ? absint( $cart_item[ self::CART_POINTS_KEY ] ) : 0;

		if ( 0 === $points ) {
			return $item_data;
		}

		$item_data[] = array(
			'key'   => __( 'מימוש נקודות', 'klientoora-card' ),
			'value' => sprintf(
				/* translators: %d is the number of redeemed points. */
				__( '%d נקודות', 'klientoora-card' ),
				$points
			),
		);

		return $item_data;
	}

	/**
	 * Restores points when a redemption product is removed from the cart.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param WC_Cart $cart          Cart object.
	 *
	 * @return void
	 */
	public function restore_points_from_removed_cart_item( $cart_item_key, $cart ) {
		if ( ! $cart instanceof WC_Cart || empty( $cart->cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		if ( $this->is_creating_redemption_order ) {
			return;
		}

		$this->restore_points_for_cart_item( $cart_item_key, $cart, $cart->cart_contents[ $cart_item_key ] );
	}

	/**
	 * Restores points after a redemption product was removed from the cart.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param WC_Cart $cart          Cart object.
	 *
	 * @return void
	 */
	public function restore_points_after_removed_cart_item( $cart_item_key, $cart ) {
		if ( ! $cart instanceof WC_Cart || empty( $cart->removed_cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		if ( $this->is_creating_redemption_order ) {
			return;
		}

		$this->restore_points_for_cart_item( $cart_item_key, $cart, $cart->removed_cart_contents[ $cart_item_key ] );
	}

	/**
	 * Deducts points again when a removed redemption cart item is restored with Undo.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param WC_Cart $cart          Cart object.
	 *
	 * @return void
	 */
	public function deduct_points_from_restored_cart_item( $cart_item_key, $cart ) {
		if ( ! $cart instanceof WC_Cart || empty( $cart->removed_cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		$cart_item = $cart->removed_cart_contents[ $cart_item_key ];

		if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) ) {
			return;
		}

		$user_id = isset( $cart_item[ self::CART_USER_KEY ] ) ? absint( $cart_item[ self::CART_USER_KEY ] ) : 0;
		$points  = isset( $cart_item[ self::CART_POINTS_KEY ] ) ? absint( $cart_item[ self::CART_POINTS_KEY ] ) : 0;

		if ( 0 === $user_id || 0 === $points ) {
			return;
		}

		$balance = absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );

		if ( $balance < $points ) {
			unset( $cart->cart_contents[ $cart_item_key ] );

			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'אין מספיק נקודות כדי לשחזר את מוצר המימוש לעגלה.', 'klientoora-card' ), 'error' );
			}

			return;
		}

		Klientoora_Card_Points::remove_points( $user_id, $points, 'product_redemption_cart_restored' );
		$this->save_cart_ledger_item(
			$cart_item_key,
			$user_id,
			isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0,
			$points,
			false
		);

		if ( isset( $cart->removed_cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] ) ) {
			unset( $cart->removed_cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] );
		}

		if ( isset( $cart->cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] ) ) {
			unset( $cart->cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] );
		}
	}

	/**
	 * Restores points for a removed redemption cart item once.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param WC_Cart $cart          Cart object.
	 * @param array   $cart_item     Cart item values.
	 *
	 * @return void
	 */
	private function restore_points_for_cart_item( $cart_item_key, $cart, $cart_item ) {
		$ledger_item = $this->get_cart_ledger_item( $cart_item_key );

		if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) && empty( $ledger_item ) ) {
			return;
		}

		$was_restored = ! empty( $cart_item[ self::CART_RESTORED_KEY ] )
			|| ( isset( $ledger_item['restored'] ) && $ledger_item['restored'] );

		if ( $was_restored ) {
			return;
		}

		$user_id    = isset( $cart_item[ self::CART_USER_KEY ] ) ? absint( $cart_item[ self::CART_USER_KEY ] ) : 0;
		$points     = isset( $cart_item[ self::CART_POINTS_KEY ] ) ? absint( $cart_item[ self::CART_POINTS_KEY ] ) : 0;
		$product_id = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;

		if ( ! empty( $ledger_item ) ) {
			$user_id    = 0 < $user_id ? $user_id : absint( $ledger_item['user_id'] );
			$points     = 0 < $points ? $points : absint( $ledger_item['points'] );
			$product_id = 0 < $product_id ? $product_id : absint( $ledger_item['product_id'] );
		}

		if ( 0 === $user_id || 0 === $points ) {
			return;
		}

		Klientoora_Card_Points::add_points( $user_id, $points, 'product_redemption_cart_removed' );
		$this->save_cart_ledger_item( $cart_item_key, $user_id, $product_id, $points, true );

		if ( isset( $cart->removed_cart_contents[ $cart_item_key ] ) ) {
			$cart->removed_cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] = true;
		}

		if ( isset( $cart->cart_contents[ $cart_item_key ] ) ) {
			$cart->cart_contents[ $cart_item_key ][ self::CART_RESTORED_KEY ] = true;
		}
	}

	/**
	 * Saves redemption details to order line items.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 *
	 * @return void
	 */
	public function save_order_item_redemption_meta( $item, $cart_item_key, $values ) {
		unset( $cart_item_key );

		if ( empty( $values[ self::CART_ITEM_KEY ] ) ) {
			return;
		}

		$points = isset( $values[ self::CART_POINTS_KEY ] ) ? absint( $values[ self::CART_POINTS_KEY ] ) : 0;

		if ( 0 === $points ) {
			return;
		}

		$item->add_meta_data( __( 'מימוש נקודות', 'klientoora-card' ), $points, true );
		$item->add_meta_data( '_klientoora_card_points_product_redemption', 'yes', true );
		$item->add_meta_data( '_klientoora_card_redeemed_product_points', $points, true );
	}

	/**
	 * Marks checkout as started so order cart cleanup does not restore spent points.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	public function mark_checkout_redemption_order( $order ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		if ( $order->get_meta( '_klientoora_card_points_product_redemption' ) ) {
			return;
		}

		$total_points = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) ) {
				continue;
			}

			$total_points += isset( $cart_item[ self::CART_POINTS_KEY ] ) ? absint( $cart_item[ self::CART_POINTS_KEY ] ) : 0;
		}

		if ( 0 === $total_points ) {
			return;
		}

		$this->is_creating_redemption_order = true;

		$user_id = absint( $order->get_user_id() );

		$order->update_meta_data( '_klientoora_card_points_product_redemption', 'yes' );
		$order->update_meta_data( '_klientoora_card_product_redemption_points_total', $total_points );

		if ( 0 < $user_id ) {
			update_user_meta( $user_id, 'loyalty_points_redeemed_total', absint( get_user_meta( $user_id, 'loyalty_points_redeemed_total', true ) ) + $total_points );
		}
	}

	/**
	 * Returns the current user's points balance for AJAX UI refreshes.
	 *
	 * @return void
	 */
	public function handle_get_points_balance() {
		check_ajax_referer( 'klientoora_card_points_balance', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'יש להתחבר כדי לצפות ביתרת הנקודות.', 'klientoora-card' ) ),
				403
			);
		}

		$points = absint( get_user_meta( get_current_user_id(), 'klientoora_card_points', true ) );

		wp_send_json_success(
			array(
				'points_balance'           => $points,
				'points_balance_formatted' => number_format_i18n( $points ),
			)
		);
	}

	/**
	 * Saves a point redemption cart item in the WooCommerce session.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $user_id       User ID.
	 * @param int    $product_id    Product ID.
	 * @param int    $points        Point price.
	 * @param bool   $restored      Whether points were restored for this removed item.
	 *
	 * @return void
	 */
	private function save_cart_ledger_item( $cart_item_key, $user_id, $product_id, $points, $restored ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$ledger = $this->get_cart_ledger();
		$ledger[ $cart_item_key ] = array(
			'user_id'    => absint( $user_id ),
			'product_id' => absint( $product_id ),
			'points'     => absint( $points ),
			'restored'   => (bool) $restored,
		);

		WC()->session->set( self::SESSION_LEDGER_KEY, $ledger );
	}

	/**
	 * Returns a ledger item from the WooCommerce session.
	 *
	 * @param string $cart_item_key Cart item key.
	 *
	 * @return array<string, mixed>
	 */
	private function get_cart_ledger_item( $cart_item_key ) {
		$ledger = $this->get_cart_ledger();

		return isset( $ledger[ $cart_item_key ] ) && is_array( $ledger[ $cart_item_key ] )
			? $ledger[ $cart_item_key ]
			: array();
	}

	/**
	 * Returns the point redemption cart ledger.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_cart_ledger() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}

		$ledger = WC()->session->get( self::SESSION_LEDGER_KEY, array() );

		return is_array( $ledger ) ? $ledger : array();
	}

	/**
	 * Checks whether a product is already in the cart as a point redemption item.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private function cart_has_redeemed_product( $product_id ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) ) {
				continue;
			}

			if ( isset( $cart_item['product_id'] ) && absint( $cart_item['product_id'] ) === absint( $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
