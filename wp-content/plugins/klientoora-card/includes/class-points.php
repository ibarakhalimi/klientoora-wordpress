<?php
/**
 * Loyalty points service.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loyalty points updates and Make sync.
 */
class Klientoora_Card_Points {

	/**
	 * Adds points to a user using the centralized points setter.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Points to add.
	 * @param string $reason  Optional reason for the points update.
	 *
	 * @return array{previous_points: int, new_points: int, sync_attempted: bool, synced: bool, skipped: bool, response_code: int, error: string}
	 */
	public static function add_points( $user_id, $points, $reason = '' ) {
		$user_id         = absint( $user_id );
		$points          = absint( $points );
		$previous_points = absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );
		$new_points      = $previous_points + $points;
		$result          = self::set_points( $user_id, $new_points, $reason );

		return array(
			'previous_points' => $previous_points,
			'new_points'      => $new_points,
			'sync_attempted'  => empty( $result['skipped'] ),
			'synced'          => ! empty( $result['synced'] ),
			'skipped'         => ! empty( $result['skipped'] ),
			'response_code'   => isset( $result['response_code'] ) ? absint( $result['response_code'] ) : 0,
			'error'           => isset( $result['error'] ) ? sanitize_text_field( $result['error'] ) : '',
		);
	}

	/**
	 * Removes points from a user using the centralized points setter.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Points to remove.
	 * @param string $reason  Optional reason for the points update.
	 *
	 * @return array{previous_points: int, new_points: int, sync_attempted: bool, synced: bool, skipped: bool, response_code: int, error: string}
	 */
	public static function remove_points( $user_id, $points, $reason = '' ) {
		$user_id         = absint( $user_id );
		$points          = absint( $points );
		$previous_points = absint( get_user_meta( $user_id, 'klientoora_card_points', true ) );
		$new_points      = max( 0, $previous_points - $points );
		$result          = self::set_points( $user_id, $new_points, $reason );

		return array(
			'previous_points' => $previous_points,
			'new_points'      => $new_points,
			'sync_attempted'  => empty( $result['skipped'] ),
			'synced'          => ! empty( $result['synced'] ),
			'skipped'         => ! empty( $result['skipped'] ),
			'response_code'   => isset( $result['response_code'] ) ? absint( $result['response_code'] ) : 0,
			'error'           => isset( $result['error'] ) ? sanitize_text_field( $result['error'] ) : '',
		);
	}

	/**
	 * Updates a user's loyalty points and syncs the balance to PassKit via Make.
	 *
	 * @param int    $user_id    User ID.
	 * @param int    $new_points New loyalty points balance.
	 * @param string $reason     Optional reason for the points update.
	 *
	 * @return array{synced: bool, skipped: bool, response_code: int, error: string}
	 */
	public static function set_points( $user_id, $new_points, $reason = '' ) {
		$user_id    = absint( $user_id );
		$new_points = absint( $new_points );
		$reason     = sanitize_text_field( $reason );

		if ( 0 === $user_id ) {
			return array(
				'synced'        => false,
				'skipped'       => true,
				'response_code' => 0,
				'error'         => 'invalid_user_id',
			);
		}

		update_user_meta( $user_id, 'klientoora_card_points', $new_points );

		$passkit_member_id = get_user_meta( $user_id, 'klientoora_card_passkit_member_id', true );

		if ( '' === $passkit_member_id ) {
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_status', 'skipped_missing_passkit_member_id' );

			return array(
				'synced'        => false,
				'skipped'       => true,
				'response_code' => 0,
				'error'         => '',
			);
		}

		return self::sync_points_to_make( $user_id, $new_points, $passkit_member_id, $reason );
	}

	/**
	 * Sends the points update to Make.
	 *
	 * @param int    $user_id            User ID.
	 * @param int    $new_points         New loyalty points balance.
	 * @param string $passkit_member_id  PassKit member ID.
	 * @param string $reason             Optional reason for the points update.
	 *
	 * @return array{synced: bool, skipped: bool, response_code: int, error: string}
	 */
	private static function sync_points_to_make( $user_id, $new_points, $passkit_member_id, $reason ) {
		$webhook_url = get_option( 'klientoora_card_make_webhook_url', '' );

		if ( '' === $webhook_url ) {
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_status', 'skipped_missing_webhook_url' );

			return array(
				'synced'        => false,
				'skipped'       => true,
				'response_code' => 0,
				'error'         => '',
			);
		}

		$timestamp = gmdate( 'c' );
		$payload   = array(
			'action'            => 'sync_points',
			'user_id'           => $user_id,
			'passkit_member_id' => sanitize_text_field( $passkit_member_id ),
			'loyalty_points'    => $new_points,
			'reason'            => $reason,
			'timestamp'         => $timestamp,
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
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_status', 'failed' );
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_error', $response->get_error_message() );

			return array(
				'synced'        => false,
				'skipped'       => false,
				'response_code' => 0,
				'error'         => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 > $response_code || 300 <= $response_code ) {
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_status', 'failed' );
			update_user_meta( $user_id, 'klientoora_card_last_point_sync_response_code', $response_code );

			return array(
				'synced'        => false,
				'skipped'       => false,
				'response_code' => $response_code,
				'error'         => 'http_error',
			);
		}

		update_user_meta( $user_id, 'klientoora_card_last_point_sync', $timestamp );
		update_user_meta( $user_id, 'klientoora_card_last_point_sync_status', 'sent' );
		update_user_meta( $user_id, 'klientoora_card_last_point_sync_response_code', $response_code );

		return array(
			'synced'        => true,
			'skipped'       => false,
			'response_code' => $response_code,
			'error'         => '',
		);
	}
}
