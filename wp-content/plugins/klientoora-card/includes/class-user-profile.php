<?php
/**
 * User profile fields.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Klientoora Card user profile metadata.
 */
class Klientoora_Card_User_Profile {

	/**
	 * Renders Klientoora Card profile fields.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return void
	 */
	public function render_profile_fields( $user ) {
		$birth_date        = get_user_meta( $user->ID, 'klientoora_card_birth_date', true );
		$phone             = get_user_meta( $user->ID, 'klientoora_card_phone', true );
		$points            = get_user_meta( $user->ID, 'klientoora_card_points', true );
		$redeemed_total    = absint( get_user_meta( $user->ID, 'loyalty_points_redeemed_total', true ) );
		$pass_url          = get_user_meta( $user->ID, 'klientoora_card_pass_url', true );
		$passkit_member_id = get_user_meta( $user->ID, 'klientoora_card_passkit_member_id', true );
		$last_point_sync   = get_user_meta( $user->ID, 'klientoora_card_last_point_sync', true );
		$membership_status = Klientoora_Card_Membership_Status::get_status( $user->ID );
		$webhook_status    = get_user_meta( $user->ID, 'klientoora_card_make_webhook_status', true );
		$webhook_code      = get_user_meta( $user->ID, 'klientoora_card_make_webhook_response_code', true );
		$webhook_body      = get_user_meta( $user->ID, 'klientoora_card_make_webhook_response_body', true );
		?>
		<h2><?php echo esc_html__( 'Klientoora Card', 'klientoora-card' ); ?></h2>
		<?php wp_nonce_field( 'klientoora_card_user_profile_' . $user->ID, 'klientoora_card_user_profile_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th>
					<label for="klientoora_card_birth_date"><?php echo esc_html__( 'תאריך לידה', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="date"
						id="klientoora_card_birth_date"
						name="klientoora_card_birth_date"
						value="<?php echo esc_attr( $birth_date ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php echo esc_html__( 'תאריך הלידה שנשמר מטופס ההרשמה למועדון.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="klientoora_card_phone"><?php echo esc_html__( 'מס׳ נייד', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="tel"
						id="klientoora_card_phone"
						name="klientoora_card_phone"
						value="<?php echo esc_attr( $phone ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php echo esc_html__( 'מספר הנייד שנשמר מטופס ההרשמה למועדון.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="klientoora_card_points"><?php echo esc_html__( 'Current points balance', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="klientoora_card_points"
						name="klientoora_card_points"
						value="<?php echo esc_attr( $points ); ?>"
						class="regular-text"
						min="0"
						step="1"
						inputmode="numeric"
					/>
					<p class="description">
						<?php echo esc_html__( 'מספר נקודות המועדון של המשתמש. שדה זה מיועד לעדכוני אוטומציות בעתיד.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="loyalty_points_redeemed_total"><?php echo esc_html__( 'Total redeemed points', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="loyalty_points_redeemed_total"
						value="<?php echo esc_attr( number_format_i18n( $redeemed_total ) ); ?>"
						class="regular-text"
						readonly
					/>
					<p class="description">
						<?php echo esc_html__( 'Display-only total of points deducted from paid WooCommerce orders.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="klientoora_card_passkit_member_id"><?php echo esc_html__( 'Passkit Member id', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="klientoora_card_passkit_member_id"
						name="klientoora_card_passkit_member_id"
						value="<?php echo esc_attr( $passkit_member_id ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php echo esc_html__( 'Passkit member identifier for future automations.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="klientoora_card_last_point_sync"><?php echo esc_html__( 'Last point sync (timestamp)', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="klientoora_card_last_point_sync"
						name="klientoora_card_last_point_sync"
						value="<?php echo esc_attr( $last_point_sync ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php echo esc_html__( 'Timestamp of the last points sync.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="membership_status"><?php echo esc_html__( 'Membership Status', 'klientoora-card' ); ?></label>
				</th>
				<td>
					<select id="membership_status" name="membership_status">
						<option value="active" <?php selected( $membership_status, 'active' ); ?>>
							<?php echo esc_html__( 'active', 'klientoora-card' ); ?>
						</option>
						<option value="not_active" <?php selected( $membership_status, 'not_active' ); ?>>
							<?php echo esc_html__( 'not active', 'klientoora-card' ); ?>
						</option>
					</select>
					<p class="description">
						<?php echo esc_html__( 'Current membership status for Klientoora Card.', 'klientoora-card' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php echo esc_html__( 'Pass URL', 'klientoora-card' ); ?></th>
				<td>
					<?php if ( '' !== $pass_url ) : ?>
						<a href="<?php echo esc_url( $pass_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $pass_url ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html__( 'לא נשמר Pass URL עדיין.', 'klientoora-card' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php echo esc_html__( 'Make Webhook Debug', 'klientoora-card' ); ?></th>
				<td>
					<p>
						<strong><?php echo esc_html__( 'Status:', 'klientoora-card' ); ?></strong>
						<?php echo esc_html( '' !== $webhook_status ? $webhook_status : '-' ); ?>
					</p>
					<p>
						<strong><?php echo esc_html__( 'Response Code:', 'klientoora-card' ); ?></strong>
						<?php echo esc_html( '' !== $webhook_code ? $webhook_code : '-' ); ?>
					</p>
					<?php if ( '' !== $webhook_body ) : ?>
						<textarea class="large-text code" rows="4" readonly><?php echo esc_textarea( $webhook_body ); ?></textarea>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Saves Klientoora Card profile fields.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function save_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['klientoora_card_user_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['klientoora_card_user_profile_nonce'] ) ), 'klientoora_card_user_profile_' . $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['klientoora_card_birth_date'] ) ) {
			return;
		}

		$birth_date        = sanitize_text_field( wp_unslash( $_POST['klientoora_card_birth_date'] ) );
		$phone             = isset( $_POST['klientoora_card_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_phone'] ) ) : '';
		$points            = isset( $_POST['klientoora_card_points'] ) ? absint( wp_unslash( $_POST['klientoora_card_points'] ) ) : 0;
		$passkit_member_id = isset( $_POST['klientoora_card_passkit_member_id'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_passkit_member_id'] ) ) : '';
		$last_point_sync   = isset( $_POST['klientoora_card_last_point_sync'] ) ? sanitize_text_field( wp_unslash( $_POST['klientoora_card_last_point_sync'] ) ) : '';
		$membership_status = isset( $_POST['membership_status'] ) ? sanitize_key( wp_unslash( $_POST['membership_status'] ) ) : 'not_active';

		if ( '' === $birth_date ) {
			delete_user_meta( $user_id, 'klientoora_card_birth_date' );
		} elseif ( $this->is_valid_birth_date( $birth_date ) ) {
			update_user_meta( $user_id, 'klientoora_card_birth_date', $birth_date );
		}

		if ( '' === $phone ) {
			delete_user_meta( $user_id, 'klientoora_card_phone' );
		} else {
			update_user_meta( $user_id, 'klientoora_card_phone', $phone );
		}

		if ( '' === $passkit_member_id ) {
			delete_user_meta( $user_id, 'klientoora_card_passkit_member_id' );
		} else {
			update_user_meta( $user_id, 'klientoora_card_passkit_member_id', $passkit_member_id );
		}

		Klientoora_Card_Points::set_points( $user_id, $points, 'profile_update' );

		if ( '' === $last_point_sync ) {
			delete_user_meta( $user_id, 'klientoora_card_last_point_sync' );
		} else {
			update_user_meta( $user_id, 'klientoora_card_last_point_sync', $last_point_sync );
		}

		Klientoora_Card_Membership_Status::set_status( $user_id, $membership_status );
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
}
