<?php
/**
 * WooCommerce checkout points redemption.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles checkout loyalty points redemption.
 */
class Klientoora_Card_Checkout_Redemption {

	/**
	 * WooCommerce session key for redeemed points.
	 *
	 * @var string
	 */
	private $session_key = 'klientoora_card_redeemed_points';

	/**
	 * WooCommerce session key for the selected checkout benefit type.
	 *
	 * @var string
	 */
	private $benefit_mode_session_key = 'klientoora_card_checkout_benefit_mode';

	/**
	 * Tracks whether the checkout box was already rendered in the current request.
	 *
	 * @var bool
	 */
	private $did_render_checkout_box = false;

	/**
	 * Renders the checkout points redemption box.
	 *
	 * @return void
	 */
	public function render_checkout_box() {
		if ( $this->did_render_checkout_box ) {
			return;
		}

		if ( ! $this->can_current_user_redeem_points() ) {
			return;
		}

		$this->did_render_checkout_box = true;

		$this->maybe_default_to_points();

		$user_id         = get_current_user_id();
		$points_balance  = $this->get_user_points_balance( $user_id );
		$redeemed_points = $this->get_session_redeemed_points();
		$coupons         = $this->get_active_checkout_coupons();
		$applied_coupons = $this->get_applied_coupon_codes();
		?>
		<div class="klientoora-card-checkout-redemption" data-klientoora-card-checkout-redemption>
			<h3><?php echo esc_html__( 'Klientoora Club', 'klientoora-card' ); ?></h3>

			<?php if ( 0 < $redeemed_points ) : ?>
				<p class="klientoora-card-checkout-redemption__applied" data-klientoora-card-redeemed-message>
					<?php echo esc_html( $this->get_redeemed_points_message( $redeemed_points ) ); ?>
				</p>
			<?php else : ?>
				<p class="klientoora-card-checkout-redemption__applied" data-klientoora-card-redeemed-message hidden></p>
			<?php endif; ?>

			<div class="klientoora-card-checkout-benefits">
				<button
					type="button"
					class="klientoora-card-checkout-benefit<?php echo 0 < $redeemed_points ? ' is-selected' : ''; ?>"
					data-klientoora-card-toggle-points
					aria-pressed="<?php echo esc_attr( 0 < $redeemed_points ? 'true' : 'false' ); ?>"
					<?php disabled( 0 === $points_balance ); ?>
				>
					<span class="klientoora-card-checkout-benefit__title"><?php echo esc_html__( 'יתרת נקודות', 'klientoora-card' ); ?></span>
					<span class="klientoora-card-checkout-benefit__summary">
						<strong data-klientoora-card-points-balance><?php echo esc_html( number_format_i18n( $points_balance ) ); ?></strong>
						<?php echo esc_html__( 'נקודות זמינות', 'klientoora-card' ); ?>
					</span>
					<span class="klientoora-card-checkout-benefit__state" data-klientoora-card-points-state>
						<?php echo esc_html( 0 < $redeemed_points ? __( 'נבחר', 'klientoora-card' ) : __( 'בחירה', 'klientoora-card' ) ); ?>
					</span>
				</button>

				<?php foreach ( $coupons as $coupon ) : ?>
					<?php
					$coupon_code = $coupon->get_code();
					$is_applied  = in_array( wc_format_coupon_code( $coupon_code ), $applied_coupons, true );
					?>
					<button
						type="button"
						class="klientoora-card-checkout-benefit<?php echo $is_applied ? ' is-selected' : ''; ?>"
						data-klientoora-card-apply-coupon
						data-coupon-code="<?php echo esc_attr( $coupon_code ); ?>"
						aria-pressed="<?php echo esc_attr( $is_applied ? 'true' : 'false' ); ?>"
					>
						<span class="klientoora-card-checkout-benefit__title"><?php echo esc_html( $coupon_code ); ?></span>
						<span class="klientoora-card-checkout-benefit__summary"><?php echo esc_html( $this->get_coupon_summary( $coupon ) ); ?></span>
						<span class="klientoora-card-checkout-benefit__state" data-klientoora-card-coupon-state>
							<?php echo esc_html( $is_applied ? __( 'נבחר', 'klientoora-card' ) : __( 'בחירה', 'klientoora-card' ) ); ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="klientoora-card-checkout-redemption__notice" data-klientoora-card-redeem-notice hidden></div>
		</div>
		<?php
	}

	/**
	 * Renders a fallback box for checkout implementations that do not fire classic hooks.
	 *
	 * @return void
	 */
	public function render_checkout_page_fallback() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return;
		}

		if ( $this->did_render_checkout_box ) {
			return;
		}

		if ( ! $this->can_current_user_redeem_points() ) {
			return;
		}

		echo '<div class="klientoora-card-checkout-redemption-fallback" data-klientoora-card-checkout-redemption-fallback>';
		$this->render_checkout_box();
		echo '</div>';
	}

	/**
	 * Handles applying available points to the WooCommerce session.
	 *
	 * @return void
	 */
	public function handle_redeem_points() {
		check_ajax_referer( 'klientoora_card_redeem_points', 'nonce' );

		if ( ! $this->can_current_user_redeem_points() ) {
			wp_send_json_error(
				array(
					'message' => __( 'לא ניתן לממש נקודות עבור משתמש זה.', 'klientoora-card' ),
				),
				403
			);
		}

		$this->remove_other_loyalty_coupons( '' );

		$user_id           = get_current_user_id();
		$points_balance    = $this->get_user_points_balance( $user_id );
		$max_discount      = $this->get_max_redeemable_amount();
		$redeemable_points = min( $points_balance, absint( floor( $max_discount ) ) );

		if ( 0 >= $redeemable_points ) {
			$this->set_session_redeemed_points( 0 );
			wp_send_json_error(
				array(
					'message' => __( 'אין נקודות זמינות למימוש בהזמנה זו.', 'klientoora-card' ),
				),
				400
			);
		}

		$this->set_session_benefit_mode( 'points' );
		$this->set_session_redeemed_points( $redeemable_points );

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->calculate_totals();
		}

		wp_send_json_success(
			array(
				'message'         => $this->get_redeemed_points_message( $redeemable_points ),
				'redeemed_points' => $redeemable_points,
				'selected_mode'   => 'points',
			)
		);
	}

	/**
	 * Handles clearing redeemed points from the WooCommerce session.
	 *
	 * @return void
	 */
	public function handle_clear_redeemed_points() {
		check_ajax_referer( 'klientoora_card_redeem_points', 'nonce' );

		$this->set_session_redeemed_points( 0 );
		$this->set_session_benefit_mode( 'points' );

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->calculate_totals();
		}

		wp_send_json_success(
			array(
				'message'         => __( 'מימוש הנקודות בוטל.', 'klientoora-card' ),
				'redeemed_points' => 0,
			)
		);
	}

	/**
	 * Applies one selected loyalty coupon to the current cart.
	 *
	 * @return void
	 */
	public function handle_apply_loyalty_coupon() {
		check_ajax_referer( 'klientoora_card_apply_coupon', 'nonce' );

		if ( ! $this->can_current_user_redeem_points() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error(
				array(
					'message' => __( 'לא ניתן להחיל קופון עבור משתמש זה.', 'klientoora-card' ),
				),
				403
			);
		}

		$coupon_code = isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) ) : '';
		$coupon      = $this->get_active_checkout_coupon_by_code( $coupon_code );

		if ( ! $coupon ) {
			wp_send_json_error(
				array(
					'message' => __( 'הקופון אינו זמין כעת.', 'klientoora-card' ),
				),
				400
			);
		}

		if ( WC()->cart->has_discount( $coupon_code ) ) {
			WC()->cart->remove_coupon( $coupon_code );
			$this->set_session_benefit_mode( 'points' );
			$redeemable_points = $this->get_current_redeemable_points();
			$this->set_session_redeemed_points( $redeemable_points );
			WC()->cart->calculate_totals();

			wp_send_json_success(
				array(
					'coupon_code'     => '',
					'redeemed_points' => $redeemable_points,
					'selected_mode'   => 'points',
					'points_message'  => $this->get_redeemed_points_message( $redeemable_points ),
					'message'         => sprintf(
						/* translators: %s is the removed coupon code. */
						__( 'הקופון %s הוסר. מימוש הנקודות הופעל.', 'klientoora-card' ),
						$coupon->get_code()
					),
				)
			);
		}

		$this->set_session_benefit_mode( 'coupon' );
		$this->set_session_redeemed_points( 0 );
		$this->remove_other_loyalty_coupons( $coupon_code );

		wc_clear_notices();

		if ( ! WC()->cart->apply_coupon( $coupon_code ) ) {
			$this->set_session_benefit_mode( 'points' );
			$this->set_session_redeemed_points( $this->get_current_redeemable_points() );

			wp_send_json_error(
				array(
					'message' => $this->get_checkout_coupon_error_message(),
				),
				400
			);
		}

		WC()->cart->calculate_totals();

		wp_send_json_success(
			array(
				'coupon_code'     => $coupon_code,
				'redeemed_points' => 0,
				'selected_mode'   => 'coupon',
				'points_message'  => '',
				'message'         => sprintf(
					/* translators: %s is the applied coupon code. */
					__( 'הקופון %s הוחל בהצלחה.', 'klientoora-card' ),
					$coupon->get_code()
				),
			)
		);
	}

	/**
	 * Applies redeemed points as a negative WooCommerce fee.
	 *
	 * @param WC_Cart $cart WooCommerce cart.
	 *
	 * @return void
	 */
	public function apply_redeemed_points_fee( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->can_current_user_redeem_points() ) {
			$this->set_session_redeemed_points( 0 );
			return;
		}

		if ( $this->has_applied_loyalty_coupon() ) {
			$this->set_session_redeemed_points( 0 );
			$this->set_session_benefit_mode( 'coupon' );
			return;
		}

		$this->set_session_benefit_mode( 'points' );

		$redeemed_points = $this->get_session_redeemed_points();

		if ( 0 >= $redeemed_points ) {
			$redeemed_points = $this->get_current_redeemable_points();
			$this->set_session_redeemed_points( $redeemed_points );
		}

		if ( 0 >= $redeemed_points ) {
			return;
		}

		$user_points     = $this->get_user_points_balance( get_current_user_id() );
		$max_discount    = $this->get_max_redeemable_amount();
		$discount_amount = min( $redeemed_points, $user_points, absint( floor( $max_discount ) ) );

		if ( 0 >= $discount_amount ) {
			$this->set_session_redeemed_points( 0 );
			return;
		}

		$this->set_session_redeemed_points( $discount_amount );
		$cart->add_fee( __( 'מימוש נקודות', 'klientoora-card' ), -1 * $discount_amount, false );
	}

	/**
	 * Saves redeemed points to order meta during checkout.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return void
	 */
	public function save_redeemed_points_to_order( $order ) {
		$this->save_redeemed_points_meta( $order );
	}

	/**
	 * Saves redeemed points to order meta after checkout order creation.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return void
	 */
	public function save_redeemed_points_to_created_order( $order ) {
		$this->save_redeemed_points_meta( $order );

		if ( $order && is_a( $order, 'WC_Order' ) ) {
			$order->save();
		}
	}

	/**
	 * Saves redeemed points to order meta for processed classic checkout orders.
	 *
	 * @param int      $order_id    WooCommerce order ID.
	 * @param array    $posted_data Posted checkout data.
	 * @param WC_Order $order       WooCommerce order.
	 *
	 * @return void
	 */
	public function save_redeemed_points_to_processed_order( $order_id, $posted_data = array(), $order = null ) {
		unset( $posted_data );

		if ( ! $order && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}

		$this->save_redeemed_points_meta( $order );

		if ( $order ) {
			$order->save();
		}
	}

	/**
	 * Deducts redeemed points only after payment/completion.
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return void
	 */
	public function deduct_redeemed_points_from_paid_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || $this->was_redeemed_points_deducted( $order ) ) {
			return;
		}

		$user_id         = absint( $order->get_user_id() );
		$redeemed_points = absint( $order->get_meta( 'loyalty_points_redeemed' ) );

		if ( 0 >= $redeemed_points ) {
			$this->save_redeemed_points_meta( $order );
			$redeemed_points = absint( $order->get_meta( 'loyalty_points_redeemed' ) );
		}

		if ( 0 === $user_id || 0 >= $redeemed_points ) {
			return;
		}

		Klientoora_Card_Points::remove_points( $user_id, $redeemed_points, 'order_redemption' );
		$this->increase_user_redeemed_points_total( $user_id, $redeemed_points );

		$order->update_meta_data( 'loyalty_points_redeemed_deducted', 'yes' );
		$order->update_meta_data( '_klientoora_card_points_deducted', 'yes' );
		$order->save();
		$this->set_session_redeemed_points( 0 );
	}

	/**
	 * Checks whether redeemed points were already deducted for an order.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return bool
	 */
	private function was_redeemed_points_deducted( $order ) {
		if ( 'yes' === $order->get_meta( 'loyalty_points_redeemed_deducted' ) ) {
			return true;
		}

		if ( 'yes' === $order->get_meta( '_klientoora_card_points_deducted' ) ) {
			$order->update_meta_data( 'loyalty_points_redeemed_deducted', 'yes' );
			$order->save();

			return true;
		}

		return false;
	}

	/**
	 * Increases the user's lifetime redeemed points total.
	 *
	 * @param int $user_id User ID.
	 * @param int $points  Redeemed points.
	 *
	 * @return void
	 */
	private function increase_user_redeemed_points_total( $user_id, $points ) {
		$user_id = absint( $user_id );
		$points  = absint( $points );

		if ( 0 === $user_id || 0 >= $points ) {
			return;
		}

		$current_total = absint( get_user_meta( $user_id, 'loyalty_points_redeemed_total', true ) );

		update_user_meta( $user_id, 'loyalty_points_redeemed_total', $current_total + $points );
	}

	/**
	 * Saves the current session redeemed points on an order object.
	 *
	 * @param WC_Order|null $order WooCommerce order.
	 *
	 * @return void
	 */
	private function save_redeemed_points_meta( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$redeemed_points = $this->get_session_redeemed_points();

		if ( 0 >= $redeemed_points ) {
			return;
		}

		$order->update_meta_data( 'loyalty_points_redeemed', $redeemed_points );
	}

	/**
	 * Checks whether the current user can redeem points.
	 *
	 * @return bool
	 */
	private function can_current_user_redeem_points() {
		if ( ! is_user_logged_in() || ! function_exists( 'WC' ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		return Klientoora_Card_Membership_Status::is_active( $user_id );
	}

	/**
	 * Returns user points balance.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	private function get_user_points_balance( $user_id ) {
		return absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );
	}

	/**
	 * Defaults checkout benefit selection to points when no loyalty coupon is active.
	 *
	 * @return void
	 */
	private function maybe_default_to_points() {
		if ( $this->has_applied_loyalty_coupon() ) {
			$this->set_session_redeemed_points( 0 );
			$this->set_session_benefit_mode( 'coupon' );
			return;
		}

		$this->set_session_benefit_mode( 'points' );

		if ( 0 >= $this->get_session_redeemed_points() ) {
			$this->set_session_redeemed_points( $this->get_current_redeemable_points() );
		}
	}

	/**
	 * Returns the current number of points that can be redeemed.
	 *
	 * @return int
	 */
	private function get_current_redeemable_points() {
		$user_id        = get_current_user_id();
		$points_balance = $this->get_user_points_balance( $user_id );
		$max_discount   = $this->get_max_redeemable_amount();

		return min( $points_balance, absint( floor( $max_discount ) ) );
	}

	/**
	 * Returns the redeemed points status message.
	 *
	 * @param int $points Redeemed points.
	 *
	 * @return string
	 */
	private function get_redeemed_points_message( $points ) {
		if ( 0 >= $points ) {
			return '';
		}

		return sprintf(
			/* translators: %d is the redeemed points amount. */
			__( 'מומשו %d נקודות בהזמנה זו.', 'klientoora-card' ),
			$points
		);
	}

	/**
	 * Checks whether a displayed loyalty coupon is currently applied.
	 *
	 * @return bool
	 */
	private function has_applied_loyalty_coupon() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( $this->get_active_checkout_coupons() as $coupon ) {
			if ( WC()->cart->has_discount( wc_format_coupon_code( $coupon->get_code() ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns active loyalty coupons that should be shown at checkout.
	 *
	 * @return array<int, WC_Coupon>
	 */
	private function get_active_checkout_coupons() {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return array();
		}

		$coupon_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_loyalty_coupon',
				'meta_value'     => 'yes',
				'post_status'    => 'publish',
				'post_type'      => 'shop_coupon',
				'posts_per_page' => -1,
			)
		);
		$coupons    = array();

		foreach ( $coupon_ids as $coupon_id ) {
			if ( 'yes' !== get_post_meta( $coupon_id, '_loyalty_show_in_popup', true ) ) {
				continue;
			}

			$coupon = new WC_Coupon( $coupon_id );

			if ( $coupon->get_id() && $this->is_checkout_coupon_active( $coupon ) ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * Returns an active checkout loyalty coupon by code.
	 *
	 * @param string $coupon_code Coupon code.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_active_checkout_coupon_by_code( $coupon_code ) {
		foreach ( $this->get_active_checkout_coupons() as $coupon ) {
			if ( wc_format_coupon_code( $coupon->get_code() ) === $coupon_code ) {
				return $coupon;
			}
		}

		return null;
	}

	/**
	 * Checks basic active-state display rules for checkout coupon cards.
	 *
	 * WooCommerce still handles final validation when the coupon is applied.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return bool
	 */
	private function is_checkout_coupon_active( $coupon ) {
		if ( 'publish' !== $coupon->get_status() ) {
			return false;
		}

		if ( $coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < current_time( 'timestamp' ) ) {
			return false;
		}

		$usage_limit = absint( $coupon->get_usage_limit() );

		if ( 0 < $usage_limit && absint( $coupon->get_usage_count() ) >= $usage_limit ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns currently applied coupon codes.
	 *
	 * @return array<int, string>
	 */
	private function get_applied_coupon_codes() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		return array_map( 'wc_format_coupon_code', WC()->cart->get_applied_coupons() );
	}

	/**
	 * Removes other displayed loyalty coupons before applying a selected one.
	 *
	 * @param string $selected_coupon_code Selected coupon code.
	 *
	 * @return void
	 */
	private function remove_other_loyalty_coupons( $selected_coupon_code ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		foreach ( $this->get_active_checkout_coupons() as $coupon ) {
			$coupon_code = wc_format_coupon_code( $coupon->get_code() );

			if ( $selected_coupon_code !== $coupon_code && WC()->cart->has_discount( $coupon_code ) ) {
				WC()->cart->remove_coupon( $coupon_code );
			}
		}
	}

	/**
	 * Returns a short display summary for a coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function get_coupon_summary( $coupon ) {
		$amount        = $coupon->get_amount();
		$discount_type = $coupon->get_discount_type();

		if ( 'percent' === $discount_type ) {
			return sprintf(
				/* translators: %s is a coupon percentage amount. */
				__( '%s%% הנחה', 'klientoora-card' ),
				number_format_i18n( (float) $amount, 0.0 === fmod( (float) $amount, 1.0 ) ? 0 : 2 )
			);
		}

		if ( $coupon->get_free_shipping() && 0 >= (float) $amount ) {
			return __( 'משלוח חינם', 'klientoora-card' );
		}

		return sprintf(
			/* translators: %s is a fixed coupon amount. */
			__( '%s הנחה', 'klientoora-card' ),
			function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $amount ) ) : number_format_i18n( (float) $amount )
		);
	}

	/**
	 * Returns the latest WooCommerce coupon error message.
	 *
	 * @return string
	 */
	private function get_checkout_coupon_error_message() {
		if ( function_exists( 'wc_get_notices' ) ) {
			$notices = wc_get_notices( 'error' );

			if ( ! empty( $notices ) ) {
				$notice = reset( $notices );
				$message = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : $notice;

				wc_clear_notices();

				return wp_strip_all_tags( (string) $message );
			}
		}

		return __( 'לא ניתן להחיל את הקופון.', 'klientoora-card' );
	}

	/**
	 * Returns the maximum redeemable amount for the current cart.
	 *
	 * @return float
	 */
	private function get_max_redeemable_amount() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0;
		}

		$cart = WC()->cart;

		return max(
			0,
			(float) $cart->get_cart_contents_total()
			+ (float) $cart->get_shipping_total()
			+ (float) $cart->get_cart_contents_tax()
			+ (float) $cart->get_shipping_tax()
		);
	}

	/**
	 * Gets redeemed points from WooCommerce session.
	 *
	 * @return int
	 */
	private function get_session_redeemed_points() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return 0;
		}

		return absint( WC()->session->get( $this->session_key, 0 ) );
	}

	/**
	 * Sets redeemed points in WooCommerce session.
	 *
	 * @param int $points Redeemed points.
	 *
	 * @return void
	 */
	private function set_session_redeemed_points( $points ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		WC()->session->set( $this->session_key, absint( $points ) );
	}

	/**
	 * Gets selected checkout benefit mode from WooCommerce session.
	 *
	 * @return string
	 */
	private function get_session_benefit_mode() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return 'points';
		}

		$mode = sanitize_key( WC()->session->get( $this->benefit_mode_session_key, 'points' ) );

		return in_array( $mode, array( 'points', 'coupon' ), true ) ? $mode : 'points';
	}

	/**
	 * Sets selected checkout benefit mode in WooCommerce session.
	 *
	 * @param string $mode Benefit mode.
	 *
	 * @return void
	 */
	private function set_session_benefit_mode( $mode ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$mode = sanitize_key( $mode );

		WC()->session->set(
			$this->benefit_mode_session_key,
			in_array( $mode, array( 'points', 'coupon' ), true ) ? $mode : 'points'
		);
	}
}
