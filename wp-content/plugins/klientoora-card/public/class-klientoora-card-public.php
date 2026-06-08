<?php
/**
 * Public functionality.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public-facing functionality.
 */
class Klientoora_Card_Public {

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
	 * Enqueues public styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'dashicons' );

		$style_path = KLIENTOORA_CARD_PATH . 'public/css/klientoora-card-public.css';

		wp_enqueue_style(
			$this->plugin_name,
			KLIENTOORA_CARD_URL . 'public/css/klientoora-card-public.css',
			array(),
			file_exists( $style_path ) ? filemtime( $style_path ) : $this->version,
			'all'
		);
	}

	/**
	 * Enqueues public scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$script_path = KLIENTOORA_CARD_PATH . 'public/js/klientoora-card-public.js';

		wp_enqueue_script(
			$this->plugin_name,
			KLIENTOORA_CARD_URL . 'public/js/klientoora-card-public.js',
			array(),
			file_exists( $script_path ) ? filemtime( $script_path ) : $this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'klientooraCardPublic',
			array(
				'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
				'successTitle'           => __( 'ההרשמה בוצעה בהצלחה', 'klientoora-card' ),
				'successText'            => __( 'נרשמת בהצלחה למועדון.', 'klientoora-card' ),
				'walletText'             => __( 'הוספה לארנק הדיגיטלי', 'klientoora-card' ),
				'errorText'              => __( 'אירעה שגיאה בהרשמה. נסו שוב.', 'klientoora-card' ),
				'submittingText'         => __( 'שולח...', 'klientoora-card' ),
				'redeemPointsNonce'       => wp_create_nonce( 'klientoora_card_redeem_points' ),
				'applyCouponNonce'        => wp_create_nonce( 'klientoora_card_apply_coupon' ),
				'redeemChallengeNonce'    => wp_create_nonce( 'klientoora_card_redeem_challenge' ),
				'redeemPointsErrorText'   => __( 'אירעה שגיאה במימוש הנקודות. נסו שוב.', 'klientoora-card' ),
				'redeemPointsLoadingText' => __( 'מממש...', 'klientoora-card' ),
				'clearPointsLoadingText'  => __( 'מבטל...', 'klientoora-card' ),
				'applyCouponErrorText'    => __( 'אירעה שגיאה בהחלת הקופון. נסו שוב.', 'klientoora-card' ),
				'applyCouponLoadingText'  => __( 'מחיל...', 'klientoora-card' ),
				'redeemChallengeErrorText' => __( 'אירעה שגיאה במימוש ההטבה. נסו שוב.', 'klientoora-card' ),
				'redeemChallengeLoadingText' => __( 'מממש...', 'klientoora-card' ),
				'selectCouponText'        => __( 'בחירה', 'klientoora-card' ),
				'selectedCouponText'      => __( 'נבחר', 'klientoora-card' ),
				'redeemProductNonce'       => wp_create_nonce( 'klientoora_card_redeem_product' ),
				'pointsBalanceNonce'       => wp_create_nonce( 'klientoora_card_points_balance' ),
				'redeemProductErrorText'   => __( 'אירעה שגיאה במימוש המוצר. נסו שוב.', 'klientoora-card' ),
				'redeemProductLoadingText' => __( 'מממש...', 'klientoora-card' ),
				'redeemProductDoneText'    => __( 'בעגלה', 'klientoora-card' ),
			)
		);
	}

	/**
	 * Renders the floating club button on the front page.
	 *
	 * @return void
	 */
	public function render_floating_club_button() {
		if ( '1' === (string) get_query_var( 'klientoora_admin_main' ) ) {
			return;
		}

		$is_account_page = function_exists( 'is_account_page' ) && is_account_page();

		if ( ! is_front_page() && ! $is_account_page ) {
			return;
		}

		$is_logged_in = is_user_logged_in();
		$label        = $is_logged_in
			? __( 'מחובר למועדון', 'klientoora-card' )
			: __( 'הצטרפות למועדון', 'klientoora-card' );
		$target       = $is_logged_in ? 'klientoora-card-member-panel' : 'klientoora-card-join-modal';
		?>
		<button
			type="button"
			class="klientoora-card-floating-button"
			aria-controls="<?php echo esc_attr( $target ); ?>"
			aria-expanded="false"
			data-klientoora-card-open-modal
		>
			<span class="dashicons dashicons-awards" aria-hidden="true"></span>
			<span class="klientoora-card-floating-button__label"><?php echo esc_html( $label ); ?></span>
		</button>

		<?php
		if ( $is_logged_in ) {
			$this->render_member_panel();
			return;
		}

		$this->render_join_modal();
	}

	/**
	 * Renders the join club modal for logged-out visitors.
	 *
	 * @return void
	 */
	private function render_join_modal() {
		?>
		<div class="klientoora-card-overlay" data-klientoora-card-close hidden></div>
		<div
			id="klientoora-card-join-modal"
			class="klientoora-card-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="klientoora-card-join-modal-title"
			hidden
		>
			<button type="button" class="klientoora-card-close" data-klientoora-card-close aria-label="<?php echo esc_attr__( 'Close', 'klientoora-card' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>

			<h2 id="klientoora-card-join-modal-title"><?php echo esc_html__( 'הצטרפות למועדון', 'klientoora-card' ); ?></h2>
			<p>
				<?php echo esc_html__( 'הצטרפו למועדון Klientoora Club כדי ליהנות מהטבות, נקודות ועדכונים אישיים בהמשך הדרך.', 'klientoora-card' ); ?>
			</p>

			<div class="klientoora-card-actions">
				<button
					type="button"
					class="klientoora-card-primary-action"
					aria-controls="klientoora-card-registration-modal"
					aria-expanded="false"
					data-klientoora-card-open-register
				>
					<?php echo esc_html__( 'הרשמה למועדון', 'klientoora-card' ); ?>
				</button>
				<a class="klientoora-card-secondary-action" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
					<?php echo esc_html__( 'התחברות למועדון', 'klientoora-card' ); ?>
				</a>
			</div>
		</div>
		<?php $this->render_registration_modal(); ?>
		<?php
	}

	/**
	 * Renders the club registration form modal.
	 *
	 * @return void
	 */
	private function render_registration_modal() {
		?>
		<div
			id="klientoora-card-registration-modal"
			class="klientoora-card-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="klientoora-card-registration-modal-title"
			hidden
		>
			<button type="button" class="klientoora-card-close" data-klientoora-card-close aria-label="<?php echo esc_attr__( 'Close', 'klientoora-card' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>

			<h2 id="klientoora-card-registration-modal-title"><?php echo esc_html__( 'טופס הרשמה למועדון', 'klientoora-card' ); ?></h2>

			<form class="klientoora-card-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-klientoora-card-registration-form>
				<?php $this->render_registration_notice(); ?>
				<input type="hidden" name="action" value="klientoora_card_register_member" />
				<input type="hidden" name="klientoora_card_redirect_to" value="<?php echo esc_attr( get_permalink() ); ?>" />
				<?php wp_nonce_field( 'klientoora_card_registration', 'klientoora_card_registration_nonce' ); ?>
				<div class="klientoora-card-form__notice klientoora-card-form__notice--error" data-klientoora-card-registration-error hidden></div>

				<p class="klientoora-card-form__field">
					<label for="klientoora_card_full_name"><?php echo esc_html__( 'שם מלא', 'klientoora-card' ); ?></label>
					<input type="text" id="klientoora_card_full_name" name="klientoora_card_full_name" autocomplete="name" required />
				</p>

				<p class="klientoora-card-form__field">
					<label for="klientoora_card_phone"><?php echo esc_html__( 'מס׳ נייד', 'klientoora-card' ); ?></label>
					<input type="tel" id="klientoora_card_phone" name="klientoora_card_phone" autocomplete="tel" required />
				</p>

				<p class="klientoora-card-form__field">
					<label for="klientoora_card_email"><?php echo esc_html__( 'כתובת מייל', 'klientoora-card' ); ?></label>
					<input type="email" id="klientoora_card_email" name="klientoora_card_email" autocomplete="email" required />
				</p>

				<p class="klientoora-card-form__field">
					<label for="klientoora_card_password"><?php echo esc_html__( 'ססמה לאתר (6 תווים)', 'klientoora-card' ); ?></label>
					<input type="password" id="klientoora_card_password" name="klientoora_card_password" autocomplete="new-password" minlength="6" required />
				</p>

				<p class="klientoora-card-form__field">
					<label for="klientoora_card_birth_date"><?php echo esc_html__( 'תאריך לידה', 'klientoora-card' ); ?></label>
					<input type="date" id="klientoora_card_birth_date" name="klientoora_card_birth_date" required />
				</p>

				<button type="submit" class="klientoora-card-primary-action">
					<?php echo esc_html__( 'שליחת הרשמה', 'klientoora-card' ); ?>
				</button>
			</form>
			<div class="klientoora-card-registration-success" data-klientoora-card-registration-success tabindex="-1" hidden></div>
		</div>
		<?php
	}

	/**
	 * Renders a registration notice after form submission.
	 *
	 * @return void
	 */
	private function render_registration_notice() {
		$status  = isset( $_GET['klientoora_card_status'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_status'] ) ) : '';
		$message = isset( $_GET['klientoora_card_message'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_message'] ) ) : '';

		if ( '' === $status || '' === $message ) {
			return;
		}

		$notice_text = $this->get_registration_notice_text( $status, $message );

		if ( '' === $notice_text ) {
			return;
		}
		?>
		<div class="klientoora-card-form__notice klientoora-card-form__notice--<?php echo esc_attr( $status ); ?>">
			<?php echo esc_html( $notice_text ); ?>
		</div>
		<?php
	}

	/**
	 * Returns the registration notice text.
	 *
	 * @param string $status  Notice status.
	 * @param string $message Notice message code.
	 *
	 * @return string
	 */
	private function get_registration_notice_text( $status, $message ) {
		if ( 'success' === $status ) {
			return __( 'ההרשמה בוצעה בהצלחה.', 'klientoora-card' );
		}

		$messages = array(
			'invalid_nonce'      => __( 'אירעה שגיאה באימות הטופס. נסו שוב.', 'klientoora-card' ),
			'missing_fields'     => __( 'יש למלא את כל השדות בטופס.', 'klientoora-card' ),
			'invalid_email'      => __( 'כתובת המייל אינה תקינה.', 'klientoora-card' ),
			'email_exists'       => __( 'כתובת המייל כבר קיימת באתר.', 'klientoora-card' ),
			'password_too_short' => __( 'הססמה חייבת להכיל לפחות 6 תווים.', 'klientoora-card' ),
			'invalid_birth_date' => __( 'תאריך הלידה אינו תקין.', 'klientoora-card' ),
			'make_webhook_error' => __( 'אירעה שגיאה בחיבור למועדון. נסו שוב.', 'klientoora-card' ),
		);

		return isset( $messages[ $message ] ) ? $messages[ $message ] : __( 'אירעה שגיאה בהרשמה. נסו שוב.', 'klientoora-card' );
	}

	/**
	 * Handles member registration form submissions.
	 *
	 * @return void
	 */
	public function handle_member_registration() {
		$redirect_url = $this->get_registration_redirect_url();

		if ( ! isset( $_POST['klientoora_card_registration_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['klientoora_card_registration_nonce'] ) ), 'klientoora_card_registration' ) ) {
			$this->redirect_registration_result( $redirect_url, 'error', 'invalid_nonce' );
		}

		$form_data = $this->get_sanitized_registration_form_data();
		$error     = $this->validate_registration_form_data( $form_data );

		if ( '' !== $error ) {
			$this->redirect_registration_result( $redirect_url, 'error', $error );
		}

		$user_id = $this->create_member_user( $form_data );

		if ( is_wp_error( $user_id ) ) {
			$this->redirect_registration_result( $redirect_url, 'error', $user_id->get_error_code() );
		}

		$this->save_member_user_meta( $user_id, $form_data );
		$webhook_result = $this->send_member_registration_webhook( $user_id, $form_data );

		if ( empty( $webhook_result['success'] ) ) {
			$this->redirect_registration_result( $redirect_url, 'error', 'make_webhook_error' );
		}

		$this->login_registered_member( $user_id );
		$this->redirect_registration_result( $redirect_url, 'success', 'registered' );
	}

	/**
	 * Handles member registration form submissions over AJAX.
	 *
	 * @return void
	 */
	public function handle_member_registration_ajax() {
		if ( ! isset( $_POST['klientoora_card_registration_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['klientoora_card_registration_nonce'] ) ), 'klientoora_card_registration' ) ) {
			wp_send_json_error(
				array(
					'message' => $this->get_registration_notice_text( 'error', 'invalid_nonce' ),
				),
				400
			);
		}

		$form_data = $this->get_sanitized_registration_form_data();
		$error     = $this->validate_registration_form_data( $form_data );

		if ( '' !== $error ) {
			wp_send_json_error(
				array(
					'message' => $this->get_registration_notice_text( 'error', $error ),
				),
				400
			);
		}

		$user_id = $this->create_member_user( $form_data );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error(
				array(
					'message' => $this->get_registration_notice_text( 'error', $user_id->get_error_code() ),
				),
				400
			);
		}

		$this->save_member_user_meta( $user_id, $form_data );

		$webhook_result = $this->send_member_registration_webhook( $user_id, $form_data );

		if ( empty( $webhook_result['success'] ) ) {
			wp_send_json_error(
				array(
					'message' => ! empty( $webhook_result['message'] )
						? $webhook_result['message']
						: $this->get_registration_notice_text( 'error', 'make_webhook_error' ),
					'debug'   => ! empty( $webhook_result['debug'] ) ? $webhook_result['debug'] : '',
				),
				502
			);
		}

		$this->login_registered_member( $user_id );

		wp_send_json_success(
			array(
				'message'  => $this->get_registration_notice_text( 'success', 'registered' ),
				'pass_url' => ! empty( $webhook_result['pass_url'] ) ? $webhook_result['pass_url'] : '',
			)
		);
	}

	/**
	 * Handles redeeming a completed challenge coupon.
	 *
	 * @return void
	 */
	public function handle_redeem_challenge() {
		check_ajax_referer( 'klientoora_card_redeem_challenge', 'nonce' );

		if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error(
				array(
					'message' => __( 'לא ניתן לממש הטבה עבור משתמש זה.', 'klientoora-card' ),
				),
				403
			);
		}

		$challenge_type = isset( $_POST['challenge_type'] ) ? sanitize_key( wp_unslash( $_POST['challenge_type'] ) ) : '';
		$user_id        = get_current_user_id();
		$source_coupon  = null;

		if ( 'orders' === $challenge_type ) {
			$orders_goal  = max( 1, absint( get_option( 'klientoora_card_order_challenge_goal', 5 ) ) );
			$orders_count = $this->get_user_orders_count( $user_id );

			if ( $orders_count < $orders_goal ) {
				wp_send_json_error(
					array(
						'message' => __( 'האתגר עדיין לא הושלם.', 'klientoora-card' ),
					),
					403
				);
			}

			$source_coupon = $this->get_order_challenge_coupon();
		} elseif ( 'spend' === $challenge_type ) {
			$spend_goal  = max( 0, (float) get_option( 'klientoora_card_spend_challenge_goal', 0 ) );
			$spend_total = $this->get_user_paid_orders_total( $user_id );

			if ( 0 >= $spend_goal || $spend_total < $spend_goal ) {
				wp_send_json_error(
					array(
						'message' => __( 'האתגר עדיין לא הושלם.', 'klientoora-card' ),
					),
					403
				);
			}

			$source_coupon = $this->get_spend_challenge_coupon();
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'סוג האתגר אינו תקין.', 'klientoora-card' ),
				),
				400
			);
		}

		$coupon = $this->get_or_create_personal_challenge_coupon( $challenge_type, $user_id, $source_coupon );

		if ( ! $coupon || ! $coupon->get_id() ) {
			wp_send_json_error(
				array(
					'message' => __( 'לא ניתן ליצור קופון אישי למימוש האתגר.', 'klientoora-card' ),
				),
				400
			);
		}

		$coupon_code = wc_format_coupon_code( $coupon->get_code() );

		if ( WC()->cart->has_discount( $coupon_code ) ) {
			wp_send_json_success(
				array(
					'coupon_code' => $coupon_code,
					'message'     => sprintf(
						/* translators: %s is the coupon code. */
						__( 'הקופון %s כבר מופעל בעגלה.', 'klientoora-card' ),
						$coupon->get_code()
					),
				)
			);
		}

		wc_clear_notices();

		if ( ! WC()->cart->apply_coupon( $coupon_code ) ) {
			wp_send_json_error(
				array(
					'message' => $this->get_latest_coupon_error_message(),
				),
				400
			);
		}

		WC()->cart->calculate_totals();

		wp_send_json_success(
			array(
				'coupon_code' => $coupon_code,
				'message'     => sprintf(
					/* translators: %s is the coupon code. */
					__( 'הקופון %s מומש והוחל על העגלה.', 'klientoora-card' ),
					$coupon->get_code()
				),
			)
		);
	}

	/**
	 * Returns the safe post-registration redirect URL.
	 *
	 * @return string
	 */
	private function get_registration_redirect_url() {
		$redirect_url = isset( $_POST['klientoora_card_redirect_to'] )
			? esc_url_raw( wp_unslash( $_POST['klientoora_card_redirect_to'] ) )
			: home_url( '/' );

		return wp_validate_redirect( $redirect_url, home_url( '/' ) );
	}

	/**
	 * Redirects after registration handling.
	 *
	 * @param string $redirect_url Redirect URL.
	 * @param string $status       Registration status.
	 * @param string $message      Registration message code.
	 *
	 * @return void
	 */
	private function redirect_registration_result( $redirect_url, $status, $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'klientoora_card_status'  => sanitize_key( $status ),
					'klientoora_card_message' => sanitize_key( $message ),
				),
				$redirect_url
			)
		);
		exit;
	}

	/**
	 * Sanitizes submitted registration form data.
	 *
	 * @return array<string, string>
	 */
	private function get_sanitized_registration_form_data() {
		return array(
			'full_name'  => isset( $_POST['klientoora_card_full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_full_name'] ) ) : '',
			'phone'      => isset( $_POST['klientoora_card_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_phone'] ) ) : '',
			'email'      => isset( $_POST['klientoora_card_email'] ) ? sanitize_email( wp_unslash( $_POST['klientoora_card_email'] ) ) : '',
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The password is validated and passed directly to WordPress for hashing.
			'password'   => isset( $_POST['klientoora_card_password'] ) ? (string) wp_unslash( $_POST['klientoora_card_password'] ) : '',
			'birth_date' => isset( $_POST['klientoora_card_birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_birth_date'] ) ) : '',
		);
	}

	/**
	 * Validates registration form data.
	 *
	 * @param array<string, string> $form_data Submitted form data.
	 *
	 * @return string Error code, or an empty string when valid.
	 */
	private function validate_registration_form_data( $form_data ) {
		if ( '' === $form_data['full_name'] || '' === $form_data['phone'] || '' === $form_data['email'] || '' === $form_data['password'] || '' === $form_data['birth_date'] ) {
			return 'missing_fields';
		}

		if ( ! is_email( $form_data['email'] ) ) {
			return 'invalid_email';
		}

		if ( email_exists( $form_data['email'] ) ) {
			return 'email_exists';
		}

		if ( strlen( $form_data['password'] ) < 6 ) {
			return 'password_too_short';
		}

		if ( ! $this->is_valid_birth_date( $form_data['birth_date'] ) ) {
			return 'invalid_birth_date';
		}

		return '';
	}

	/**
	 * Validates the birth date format.
	 *
	 * @param string $birth_date Birth date.
	 *
	 * @return bool
	 */
	private function is_valid_birth_date( $birth_date ) {
		$date_parts = explode( '-', $birth_date );

		if ( 3 !== count( $date_parts ) ) {
			return false;
		}

		return checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] );
	}

	/**
	 * Creates the WordPress user for the new member.
	 *
	 * @param array<string, string> $form_data Submitted form data.
	 *
	 * @return int|WP_Error
	 */
	private function create_member_user( $form_data ) {
		return wp_insert_user(
			array(
				'user_login'   => $this->generate_unique_username( $form_data['email'] ),
				'user_email'   => $form_data['email'],
				'user_pass'    => $form_data['password'],
				'display_name' => $form_data['full_name'],
				'nickname'     => $form_data['full_name'],
				'role'         => $this->get_member_user_role(),
			)
		);
	}

	/**
	 * Returns the member user role for new registrations.
	 *
	 * @return string
	 */
	private function get_member_user_role() {
		if ( get_role( 'customer' ) ) {
			return 'customer';
		}

		if ( get_role( 'costumer' ) ) {
			return 'costumer';
		}

		return get_option( 'default_role', 'subscriber' );
	}

	/**
	 * Generates a unique username from the submitted email address.
	 *
	 * @param string $email Email address.
	 *
	 * @return string
	 */
	private function generate_unique_username( $email ) {
		$email_parts = explode( '@', $email );
		$base        = sanitize_user( $email_parts[0], true );

		if ( '' === $base ) {
			$base = 'klientoora_member';
		}

		$username = $base;
		$suffix   = 1;

		while ( username_exists( $username ) ) {
			$username = $base . '_' . $suffix;
			++$suffix;
		}

		return $username;
	}

	/**
	 * Saves member data as user meta.
	 *
	 * @param int                   $user_id   User ID.
	 * @param array<string, string> $form_data Submitted form data.
	 *
	 * @return void
	 */
	private function save_member_user_meta( $user_id, $form_data ) {
		update_user_meta( $user_id, 'klientoora_card_full_name', $form_data['full_name'] );
		update_user_meta( $user_id, 'klientoora_card_phone', $form_data['phone'] );
		update_user_meta( $user_id, 'klientoora_card_birth_date', $form_data['birth_date'] );
		update_user_meta( $user_id, 'klientoora_card_member_source', 'website_registration' );
		update_user_meta( $user_id, 'klientoora_card_member_registered_at', gmdate( 'c' ) );
		Klientoora_Card_Membership_Status::set_status( $user_id, 'active' );
	}

	/**
	 * Sends the member registration data to the configured Make webhook.
	 *
	 * @param int                   $user_id   User ID.
	 * @param array<string, string> $form_data Submitted form data.
	 *
	 * @return array{success: bool, pass_url: string, passkit_member_id: string, message: string, debug?: string}
	 */
	private function send_member_registration_webhook( $user_id, $form_data ) {
		$webhook_url = get_option( 'klientoora_card_make_webhook_url', '' );

		if ( '' === $webhook_url ) {
			update_user_meta( $user_id, 'klientoora_card_make_webhook_status', 'missing_url' );
			return array(
				'success'           => false,
				'pass_url'          => '',
				'passkit_member_id' => '',
				'message'           => __( 'כתובת ה-Webhook אינה מוגדרת.', 'klientoora-card' ),
				'debug'             => 'missing_url',
			);
		}

		$payload = array(
			'action'    => 'register_member',
			'source'    => 'website_registration',
			'timestamp' => gmdate( 'c' ),
			'form_data' => array(
				'user_id'    => $user_id,
				'full_name'  => $form_data['full_name'],
				'phone'      => $form_data['phone'],
				'email'      => $form_data['email'],
				'birth_date' => $form_data['birth_date'],
			),
		);

		$response = wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			update_user_meta( $user_id, 'klientoora_card_make_webhook_status', 'failed' );
			update_user_meta( $user_id, 'klientoora_card_make_webhook_error', $response->get_error_message() );
			return array(
				'success'           => false,
				'pass_url'          => '',
				'passkit_member_id' => '',
				'message'           => __( 'אירעה שגיאה בשליחת הנתונים למועדון.', 'klientoora-card' ),
				'debug'             => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 > $response_code || 300 <= $response_code ) {
			update_user_meta( $user_id, 'klientoora_card_make_webhook_status', 'failed' );
			update_user_meta( $user_id, 'klientoora_card_make_webhook_response_code', $response_code );
			return array(
				'success'           => false,
				'pass_url'          => '',
				'passkit_member_id' => '',
				'message'           => __( 'אירעה שגיאה בחיבור למועדון. נסו שוב.', 'klientoora-card' ),
				'debug'             => 'HTTP ' . $response_code,
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = $this->decode_make_webhook_response_body( $response_body );
		$make_result   = $this->normalize_make_webhook_response( $response_data );

		update_user_meta( $user_id, 'klientoora_card_make_webhook_response_body', wp_strip_all_tags( $response_body ) );

		if ( ! $make_result['success'] ) {
			update_user_meta( $user_id, 'klientoora_card_make_webhook_status', 'failed' );
			update_user_meta( $user_id, 'klientoora_card_make_webhook_response_code', $response_code );

			return array(
				'success'           => false,
				'pass_url'          => '',
				'passkit_member_id' => '',
				'message'           => __( 'Make החזיר תגובה לא תקינה. בדקו שה-Response ב-Make מחזיר JSON עם success=true.', 'klientoora-card' ),
				'debug'             => 'HTTP ' . $response_code . ' | Body: ' . mb_substr( wp_strip_all_tags( $response_body ), 0, 300 ),
			);
		}

		$pass_url          = $make_result['pass_url'];
		$passkit_member_id = $make_result['passkit_member_id'];

		update_user_meta( $user_id, 'klientoora_card_make_webhook_status', 'sent' );
		update_user_meta( $user_id, 'klientoora_card_make_webhook_response_code', $response_code );

		if ( '' !== $pass_url ) {
			update_user_meta( $user_id, 'klientoora_card_pass_url', $pass_url );
		}

		if ( '' !== $passkit_member_id ) {
			update_user_meta( $user_id, 'klientoora_card_passkit_member_id', $passkit_member_id );
		}

		return array(
			'success'           => true,
			'pass_url'          => $pass_url,
			'passkit_member_id' => $passkit_member_id,
			'message'           => '',
		);
	}

	/**
	 * Decodes the Make webhook response body.
	 *
	 * @param string $response_body Raw response body.
	 *
	 * @return mixed
	 */
	private function decode_make_webhook_response_body( $response_body ) {
		$response_body = trim( preg_replace( '/^\xEF\xBB\xBF/', '', $response_body ) );
		$decoded       = json_decode( $response_body, true );

		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		if ( is_string( $decoded ) ) {
			$decoded_string = json_decode( $decoded, true );

			if ( is_array( $decoded_string ) ) {
				return $decoded_string;
			}
		}

		$json_start = strpos( $response_body, '{' );
		$json_end   = strrpos( $response_body, '}' );

		if ( false !== $json_start && false !== $json_end && $json_end > $json_start ) {
			$json_fragment = substr( $response_body, $json_start, $json_end - $json_start + 1 );
			$decoded       = json_decode( $json_fragment, true );

			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return $decoded;
	}

	/**
	 * Normalizes the Make webhook JSON response.
	 *
	 * @param mixed $response_data Decoded JSON response.
	 *
	 * @return array{success: bool, pass_url: string, passkit_member_id: string}
	 */
	private function normalize_make_webhook_response( $response_data ) {
		if ( isset( $response_data[0] ) && is_array( $response_data[0] ) ) {
			$response_data = $response_data[0];
		}

		if ( isset( $response_data['body'] ) ) {
			$response_data = $this->decode_make_webhook_response_body( (string) $response_data['body'] );
		} elseif ( isset( $response_data['Body'] ) ) {
			$response_data = $this->decode_make_webhook_response_body( (string) $response_data['Body'] );
		}

		if ( ! is_array( $response_data ) ) {
			return array(
				'success'           => false,
				'pass_url'          => '',
				'passkit_member_id' => '',
			);
		}

		$success = isset( $response_data['success'] ) ? $response_data['success'] : false;
		$success = true === $success || 'true' === strtolower( (string) $success ) || '1' === (string) $success;
		$pass_url          = '';
		$passkit_member_id = '';

		if ( ! empty( $response_data['pass_url'] ) ) {
			$pass_url = esc_url_raw( $response_data['pass_url'] );
		} elseif ( ! empty( $response_data['data']['pass_url'] ) ) {
			$pass_url = esc_url_raw( $response_data['data']['pass_url'] );
		} elseif ( ! empty( $response_data['passUrl'] ) ) {
			$pass_url = esc_url_raw( $response_data['passUrl'] );
		}

		if ( ! empty( $response_data['passkit_member_id'] ) ) {
			$passkit_member_id = sanitize_text_field( $response_data['passkit_member_id'] );
		} elseif ( ! empty( $response_data['data']['passkit_member_id'] ) ) {
			$passkit_member_id = sanitize_text_field( $response_data['data']['passkit_member_id'] );
		} elseif ( ! empty( $response_data['passkitMemberId'] ) ) {
			$passkit_member_id = sanitize_text_field( $response_data['passkitMemberId'] );
		}

		return array(
			'success'           => $success || '' !== $pass_url,
			'pass_url'          => $pass_url,
			'passkit_member_id' => $passkit_member_id,
		);
	}

	/**
	 * Logs in the newly registered member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	private function login_registered_member( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
	}

	/**
	 * Renders the member modal for logged-in users.
	 *
	 * @return void
	 */
	private function render_member_panel() {
		$current_user = wp_get_current_user();
		$user_name    = $current_user instanceof WP_User && '' !== $current_user->display_name
			? $current_user->display_name
			: $current_user->user_login;
		$user_id           = get_current_user_id();
		$points            = absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );
		$redeemed_total    = absint( get_user_meta( $user_id, 'loyalty_points_redeemed_total', true ) );
		$discount          = (float) get_option( 'loyalty_member_discount_percentage', 0 );
		$discount          = max( 0, min( 100, $discount ) );
		$discount_decimals = 0.0 === fmod( $discount, 1.0 ) ? 0 : 2;
		$orders_count      = $this->get_user_orders_count( $user_id );
		$orders_goal       = max( 1, absint( get_option( 'klientoora_card_order_challenge_goal', 5 ) ) );
		$orders_progress   = min( $orders_count, $orders_goal );
		$orders_percentage = ( $orders_progress / $orders_goal ) * 100;
		$orders_remaining  = max( 0, $orders_goal - $orders_count );
		$spend_total       = $this->get_user_paid_orders_total( $user_id );
		$spend_goal        = max( 0, (float) get_option( 'klientoora_card_spend_challenge_goal', 0 ) );
		$spend_progress    = 0 < $spend_goal ? min( $spend_total, $spend_goal ) : 0;
		$spend_percentage  = 0 < $spend_goal ? ( $spend_progress / $spend_goal ) * 100 : 0;
		$spend_remaining   = max( 0, $spend_goal - $spend_total );
		$spend_goal_label  = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $spend_goal ) ) : number_format_i18n( $spend_goal, 2 );
		$spend_remaining_label = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $spend_remaining ) ) : number_format_i18n( $spend_remaining, 2 );
		$coupons           = $this->get_member_popup_coupons();
		$challenge_coupon  = $this->get_order_challenge_coupon();
		$challenge_reward  = $challenge_coupon
			? $this->get_order_challenge_reward_label( $challenge_coupon )
			: __( 'משלוח חינם', 'klientoora-card' );
		$orders_challenge_can_redeem = $orders_count >= $orders_goal;
		$spend_challenge_coupon = $this->get_spend_challenge_coupon();
		$spend_challenge_reward = $spend_challenge_coupon
			? $this->get_order_challenge_reward_label( $spend_challenge_coupon )
			: __( 'משלוח חינם', 'klientoora-card' );
		$spend_challenge_can_redeem = 0 < $spend_goal && $spend_total >= $spend_goal;
		$redemption_products = class_exists( 'Klientoora_Card_Product_Redemption' )
			? Klientoora_Card_Product_Redemption::get_redeemable_products()
			: array();

		if ( ! empty( $redemption_products ) ) {
			usort(
				$redemption_products,
				function ( $first_product, $second_product ) use ( $points ) {
					$first_can_redeem  = $this->can_user_redeem_product( $first_product, $points );
					$second_can_redeem = $this->can_user_redeem_product( $second_product, $points );

					if ( $first_can_redeem === $second_can_redeem ) {
						return strcasecmp( $first_product->get_name(), $second_product->get_name() );
					}

					return $first_can_redeem ? -1 : 1;
				}
			);
		}
		?>
		<div class="klientoora-card-overlay" data-klientoora-card-close hidden></div>
		<div
			id="klientoora-card-member-panel"
			class="klientoora-card-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="klientoora-card-member-panel-title"
			hidden
		>
			<button type="button" class="klientoora-card-close" data-klientoora-card-close aria-label="<?php echo esc_attr__( 'Close', 'klientoora-card' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>

			<h2 id="klientoora-card-member-panel-title">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s is the current user's display name. */
						__( 'שלום %s', 'klientoora-card' ),
						$user_name
					)
				);
				?>
			</h2>
			<p><?php echo esc_html__( 'כאן יופיעו בהמשך פרטי המועדון, הנקודות וההטבות של המשתמש.', 'klientoora-card' ); ?></p>

			<div class="klientoora-card-member-benefits" aria-labelledby="klientoora-card-member-benefits-title">
				<h3 id="klientoora-card-member-benefits-title"><?php echo esc_html__( 'אזור הטבות', 'klientoora-card' ); ?></h3>
				<ul class="klientoora-card-member-benefits__list">
					<li class="klientoora-card-member-benefits__item">
						<span><?php echo esc_html__( 'יתרת נקודות נוכחית', 'klientoora-card' ); ?></span>
						<strong data-klientoora-card-member-points-balance><?php echo esc_html( number_format_i18n( $points ) ); ?></strong>
					</li>
					<li class="klientoora-card-member-benefits__item">
						<span><?php echo esc_html__( 'סה״כ נקודות שמומשו', 'klientoora-card' ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $redeemed_total ) ); ?></strong>
					</li>
					<li class="klientoora-card-member-benefits__item">
						<span><?php echo esc_html__( 'הטבה קבועה', 'klientoora-card' ); ?></span>
						<strong>
							<?php
							if ( 0 < $discount ) {
								echo esc_html(
									sprintf(
										/* translators: %s is the configured member discount percentage. */
										__( '%s%% הנחה', 'klientoora-card' ),
										number_format_i18n( $discount, $discount_decimals )
									)
								);
							} else {
								echo esc_html__( 'לא הוגדרה הטבה קבועה', 'klientoora-card' );
							}
							?>
						</strong>
					</li>
				</ul>
			</div>

			<?php if ( ! empty( $coupons ) ) : ?>
				<div class="klientoora-card-member-coupons" aria-labelledby="klientoora-card-member-coupons-title">
					<h3 id="klientoora-card-member-coupons-title"><?php echo esc_html__( 'קופונים זמינים', 'klientoora-card' ); ?></h3>
					<div class="klientoora-card-member-coupons__list">
						<?php foreach ( $coupons as $coupon ) : ?>
							<div class="klientoora-card-member-coupon">
								<strong><?php echo esc_html( $coupon->get_code() ); ?></strong>
								<span><?php echo esc_html( $this->get_member_coupon_summary( $coupon ) ); ?></span>
								<?php if ( 'yes' === $coupon->get_meta( '_loyalty_members_only' ) ) : ?>
									<small><?php echo esc_html__( 'לחברי מועדון בלבד', 'klientoora-card' ); ?></small>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $redemption_products ) ) : ?>
				<div class="klientoora-card-member-products" aria-labelledby="klientoora-card-member-products-title" data-klientoora-card-product-redemptions>
					<h3 id="klientoora-card-member-products-title"><?php echo esc_html__( 'מימוש נקודות', 'klientoora-card' ); ?></h3>
					<div class="klientoora-card-member-products__notice" data-klientoora-card-product-notice hidden></div>
					<div class="klientoora-card-member-products__list">
						<?php foreach ( $redemption_products as $product ) : ?>
							<?php $this->render_redemption_product_card( $product, $points ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="klientoora-card-member-orders" aria-labelledby="klientoora-card-member-orders-title">
				<h3 id="klientoora-card-member-orders-title"><?php echo esc_html__( 'אתגרי מכירה', 'klientoora-card' ); ?></h3>
				<div class="klientoora-card-member-orders__notice" data-klientoora-card-challenge-notice hidden></div>
				<div class="klientoora-card-member-orders__summary">
					<span><?php echo esc_html__( 'מספר הזמנות', 'klientoora-card' ); ?></span>
					<strong>
						<?php
						if ( $orders_count >= $orders_goal ) {
							echo esc_html(
								sprintf(
									/* translators: %s is the challenge reward label. */
									__( 'זכאי לקבלת %s', 'klientoora-card' ),
									$challenge_reward
								)
							);
						} else {
							echo esc_html(
								sprintf(
									/* translators: 1: remaining orders count, 2: challenge reward label. */
									__( 'עוד %1$d לקבלת %2$s', 'klientoora-card' ),
									$orders_remaining,
									$challenge_reward
								)
							);
						}
						?>
					</strong>
				</div>
				<div class="klientoora-card-member-orders__meter" role="meter" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $orders_goal ); ?>" aria-valuenow="<?php echo esc_attr( $orders_progress ); ?>">
					<span style="width: <?php echo esc_attr( $orders_percentage ); ?>%;"></span>
				</div>
				<ul class="klientoora-card-member-orders__steps" aria-label="<?php echo esc_attr__( 'שלבי הטבות לפי הזמנות', 'klientoora-card' ); ?>">
					<li class="<?php echo esc_attr( $orders_count >= $orders_goal ? 'is-complete' : '' ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: order goal count, 2: challenge reward label. */
								__( 'ב-%1$d: %2$s', 'klientoora-card' ),
								$orders_goal,
								$challenge_reward
							)
						);
						?>
					</li>
				</ul>
				<button
					type="button"
					class="klientoora-card-primary-action klientoora-card-member-orders__redeem"
					data-klientoora-card-redeem-challenge
					data-challenge-type="orders"
					<?php disabled( ! $orders_challenge_can_redeem ); ?>
				>
					<?php echo esc_html__( 'מימוש', 'klientoora-card' ); ?>
				</button>
				<?php if ( 0 < $spend_goal ) : ?>
					<div class="klientoora-card-member-orders__summary">
						<span><?php echo esc_html__( 'סכום הזמנות', 'klientoora-card' ); ?></span>
						<strong>
							<?php
							if ( $spend_total >= $spend_goal ) {
								echo esc_html(
									sprintf(
										/* translators: %s is the challenge reward label. */
										__( 'זכאי לקבלת %s', 'klientoora-card' ),
										$spend_challenge_reward
									)
								);
							} else {
								echo esc_html(
									sprintf(
										/* translators: 1: remaining paid spend, 2: challenge reward label. */
										__( 'עוד %1$s לקבלת %2$s', 'klientoora-card' ),
										$spend_remaining_label,
										$spend_challenge_reward
									)
								);
							}
							?>
						</strong>
					</div>
					<div class="klientoora-card-member-orders__meter" role="meter" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $spend_goal ); ?>" aria-valuenow="<?php echo esc_attr( $spend_progress ); ?>">
						<span style="width: <?php echo esc_attr( $spend_percentage ); ?>%;"></span>
					</div>
					<ul class="klientoora-card-member-orders__steps" aria-label="<?php echo esc_attr__( 'שלבי הטבות לפי סכום הזמנות', 'klientoora-card' ); ?>">
						<li class="<?php echo esc_attr( $spend_total >= $spend_goal ? 'is-complete' : '' ); ?>">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: spend goal, 2: challenge reward label. */
									__( 'ב-%1$s: %2$s', 'klientoora-card' ),
									$spend_goal_label,
									$spend_challenge_reward
								)
							);
							?>
						</li>
					</ul>
					<button
						type="button"
						class="klientoora-card-primary-action klientoora-card-member-orders__redeem"
						data-klientoora-card-redeem-challenge
						data-challenge-type="spend"
						<?php disabled( ! $spend_challenge_can_redeem ); ?>
					>
						<?php echo esc_html__( 'מימוש', 'klientoora-card' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a product card for point redemption in the member panel.
	 *
	 * @param WC_Product $product        Product object.
	 * @param int        $points_balance Current user points balance.
	 *
	 * @return void
	 */
	private function render_redemption_product_card( $product, $points_balance ) {
		$product_id   = $product->get_id();
		$points_price = Klientoora_Card_Product_Redemption::get_product_points_price( $product );
		$can_redeem   = $this->can_user_redeem_product( $product, $points_balance );
		$missing      = max( 0, $points_price - $points_balance );
		$image_id     = $product->get_image_id();
		$image        = $image_id ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img( 'woocommerce_thumbnail' );
		?>
		<div class="klientoora-card-member-product<?php echo esc_attr( $can_redeem ? '' : ' is-disabled' ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
			<a class="klientoora-card-member-product__image" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
				<?php echo wp_kses_post( $image ); ?>
			</a>
			<div class="klientoora-card-member-product__content">
				<strong><?php echo esc_html( $product->get_name() ); ?></strong>
				<small>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d is the product point price. */
							__( '%d נקודות', 'klientoora-card' ),
							$points_price
						)
					);
					?>
				</small>
				<?php if ( $can_redeem ) : ?>
					<button type="button" class="klientoora-card-primary-action" data-klientoora-card-redeem-product data-product-id="<?php echo esc_attr( $product_id ); ?>">
						<?php echo esc_html__( 'מימוש מוצר', 'klientoora-card' ); ?>
					</button>
				<?php else : ?>
					<span class="klientoora-card-member-product__disabled">
						<?php
						if ( ! $product->is_in_stock() ) {
							echo esc_html__( 'לא במלאי', 'klientoora-card' );
						} else {
							echo esc_html(
								sprintf(
									/* translators: %d is the missing points amount. */
									__( 'חסרות %d נקודות', 'klientoora-card' ),
									$missing
								)
							);
						}
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Checks whether a product is currently redeemable by the user.
	 *
	 * @param WC_Product $product        Product object.
	 * @param int        $points_balance User points balance.
	 *
	 * @return bool
	 */
	private function can_user_redeem_product( $product, $points_balance ) {
		$points_price = Klientoora_Card_Product_Redemption::get_product_points_price( $product );

		return 0 < $points_price && $points_balance >= $points_price && $product->is_in_stock() && $product->is_purchasable();
	}

	/**
	 * Returns an existing personal challenge coupon or creates one from the configured reward.
	 *
	 * @param string         $challenge_type Challenge type.
	 * @param int            $user_id        User ID.
	 * @param WC_Coupon|null $source_coupon  Optional configured WooCommerce coupon.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_or_create_personal_challenge_coupon( $challenge_type, $user_id, $source_coupon = null ) {
		if ( ! class_exists( 'WC_Coupon' ) || ! in_array( $challenge_type, array( 'orders', 'spend' ), true ) ) {
			return null;
		}

		$coupon = $this->get_existing_personal_challenge_coupon( $challenge_type, $user_id );

		if ( ! $coupon ) {
			$coupon = new WC_Coupon();
			$coupon->set_code( $this->generate_personal_challenge_coupon_code( $challenge_type, $user_id ) );
		}

		$this->apply_challenge_coupon_reward( $coupon, $source_coupon, $challenge_type );
		$coupon->set_status( 'publish' );
		$coupon->set_usage_limit( 1 );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->update_meta_data( '_klientoora_challenge_coupon', 'yes' );
		$coupon->update_meta_data( '_klientoora_challenge_type', $challenge_type );
		$coupon->update_meta_data( '_klientoora_challenge_user_id', $user_id );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Finds an existing personal challenge coupon for a user.
	 *
	 * @param string $challenge_type Challenge type.
	 * @param int    $user_id        User ID.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_existing_personal_challenge_coupon( $challenge_type, $user_id ) {
		$coupon_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_klientoora_challenge_coupon',
						'value' => 'yes',
					),
					array(
						'key'   => '_klientoora_challenge_type',
						'value' => $challenge_type,
					),
					array(
						'key'   => '_klientoora_challenge_user_id',
						'value' => (string) $user_id,
					),
				),
				'post_status'    => 'any',
				'post_type'      => 'shop_coupon',
				'posts_per_page' => 1,
			)
		);

		if ( empty( $coupon_ids ) ) {
			return null;
		}

		$coupon = new WC_Coupon( $coupon_ids[0] );

		return $coupon->get_id() ? $coupon : null;
	}

	/**
	 * Applies the configured challenge reward to a personal coupon.
	 *
	 * @param WC_Coupon      $coupon         Personal coupon.
	 * @param WC_Coupon|null $source_coupon  Optional configured source coupon.
	 * @param string         $challenge_type Challenge type.
	 *
	 * @return void
	 */
	private function apply_challenge_coupon_reward( $coupon, $source_coupon, $challenge_type ) {
		$description = 'orders' === $challenge_type
			? __( 'קופון אישי מאתגר מספר הזמנות', 'klientoora-card' )
			: __( 'קופון אישי מאתגר סכום הזמנות', 'klientoora-card' );

		$coupon->set_description( $description );

		if ( $source_coupon && $source_coupon->get_id() ) {
			$coupon->set_discount_type( $source_coupon->get_discount_type() );
			$coupon->set_amount( $source_coupon->get_amount() );
			$coupon->set_free_shipping( $source_coupon->get_free_shipping() );
			$coupon->set_date_expires( $source_coupon->get_date_expires() );
			$coupon->set_minimum_amount( $source_coupon->get_minimum_amount() );
			$coupon->set_maximum_amount( $source_coupon->get_maximum_amount() );
			$coupon->set_individual_use( $source_coupon->get_individual_use() );
			$coupon->set_product_ids( $source_coupon->get_product_ids() );
			$coupon->set_excluded_product_ids( $source_coupon->get_excluded_product_ids() );
			$coupon->set_product_categories( $source_coupon->get_product_categories() );
			$coupon->set_excluded_product_categories( $source_coupon->get_excluded_product_categories() );
			$coupon->set_exclude_sale_items( $source_coupon->get_exclude_sale_items() );

			return;
		}

		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( true );
		$coupon->set_date_expires( null );
		$coupon->set_minimum_amount( '' );
		$coupon->set_maximum_amount( '' );
		$coupon->set_individual_use( false );
		$coupon->set_product_ids( array() );
		$coupon->set_excluded_product_ids( array() );
		$coupon->set_product_categories( array() );
		$coupon->set_excluded_product_categories( array() );
		$coupon->set_exclude_sale_items( false );
	}

	/**
	 * Generates a unique personal challenge coupon code.
	 *
	 * @param string $challenge_type Challenge type.
	 * @param int    $user_id        User ID.
	 *
	 * @return string
	 */
	private function generate_personal_challenge_coupon_code( $challenge_type, $user_id ) {
		$prefix = 'orders' === $challenge_type ? 'KLC-ORD' : 'KLC-SPD';
		$code   = sprintf( '%1$s-%2$d', $prefix, $user_id );

		if ( function_exists( 'wc_get_coupon_id_by_code' ) && wc_get_coupon_id_by_code( $code ) ) {
			$code = sprintf( '%1$s-%2$d-%3$s', $prefix, $user_id, wp_generate_password( 6, false, false ) );
		}

		return $code;
	}

	/**
	 * Returns the configured order challenge coupon.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_order_challenge_coupon() {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return null;
		}

		$coupon_id = absint( get_option( 'klientoora_card_order_challenge_coupon_id', 0 ) );

		if ( 0 >= $coupon_id ) {
			return null;
		}

		$coupon = new WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() || ! $this->is_member_popup_coupon_active( $coupon ) ) {
			return null;
		}

		return $coupon;
	}

	/**
	 * Returns the configured spend challenge coupon.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_spend_challenge_coupon() {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return null;
		}

		$coupon_id = absint( get_option( 'klientoora_card_spend_challenge_coupon_id', 0 ) );

		if ( 0 >= $coupon_id ) {
			return null;
		}

		$coupon = new WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() || ! $this->is_member_popup_coupon_active( $coupon ) ) {
			return null;
		}

		return $coupon;
	}

	/**
	 * Returns the display label for the configured order challenge reward.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function get_order_challenge_reward_label( $coupon ) {
		return sprintf(
			/* translators: 1: coupon code, 2: coupon summary. */
			__( '%1$s - %2$s', 'klientoora-card' ),
			$coupon->get_code(),
			$this->get_member_coupon_summary( $coupon )
		);
	}

	/**
	 * Returns the latest WooCommerce coupon error message.
	 *
	 * @return string
	 */
	private function get_latest_coupon_error_message() {
		if ( function_exists( 'wc_get_notices' ) ) {
			$notices = wc_get_notices( 'error' );

			if ( ! empty( $notices ) ) {
				$last_notice = end( $notices );

				if ( is_array( $last_notice ) && ! empty( $last_notice['notice'] ) ) {
					return wp_strip_all_tags( $last_notice['notice'] );
				}

				if ( is_string( $last_notice ) ) {
					return wp_strip_all_tags( $last_notice );
				}
			}
		}

		return __( 'לא ניתן לממש את הקופון כעת.', 'klientoora-card' );
	}

	/**
	 * Returns the current user's WooCommerce order count.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	private function get_user_orders_count( $user_id ) {
		if ( function_exists( 'wc_get_customer_order_count' ) ) {
			return absint( wc_get_customer_order_count( $user_id ) );
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => -1,
				'return'      => 'ids',
			)
		);

		return is_array( $orders ) ? count( $orders ) : 0;
	}

	/**
	 * Returns the total amount paid by the current user in WooCommerce orders.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return float
	 */
	private function get_user_paid_orders_total( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$statuses = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
		$orders   = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => -1,
				'status'      => $statuses,
			)
		);
		$total    = 0;

		if ( ! is_array( $orders ) ) {
			return 0;
		}

		foreach ( $orders as $order ) {
			if ( method_exists( $order, 'get_total' ) ) {
				$total += (float) $order->get_total();
			}
		}

		return $total;
	}

	/**
	 * Returns active loyalty coupons that should appear in the member popup.
	 *
	 * @return array<int, WC_Coupon>
	 */
	private function get_member_popup_coupons() {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return array();
		}

		$coupon_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_loyalty_coupon',
				'meta_value'     => 'yes',
				'orderby'        => 'date',
				'order'          => 'DESC',
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

			if ( $coupon->get_id() && $this->is_member_popup_coupon_active( $coupon ) ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * Checks basic display rules for member popup coupons.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return bool
	 */
	private function is_member_popup_coupon_active( $coupon ) {
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
	 * Returns a short display summary for a member popup coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function get_member_coupon_summary( $coupon ) {
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
}
