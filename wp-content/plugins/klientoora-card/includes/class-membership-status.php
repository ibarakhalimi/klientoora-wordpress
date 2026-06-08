<?php
/**
 * Membership status helpers and migration.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles normalized loyalty membership status values.
 */
class Klientoora_Card_Membership_Status {

	/**
	 * Current membership meta key.
	 */
	const META_KEY = 'membership_status';

	/**
	 * Old plugin-specific membership status key.
	 */
	const OLD_META_KEY = 'klientoora_card_membership_status';

	/**
	 * Legacy membership flag key.
	 */
	const LEGACY_MEMBER_META_KEY = 'loyalty_member';

	/**
	 * Migration option key.
	 */
	const MIGRATION_OPTION_KEY = 'klientoora_card_membership_status_migrated';

	/**
	 * Returns the normalized membership status for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	public static function get_status( $user_id ) {
		$user_id = absint( $user_id );

		if ( 0 === $user_id ) {
			return 'not_active';
		}

		return self::normalize_status( get_user_meta( $user_id, self::META_KEY, true ) );
	}

	/**
	 * Checks whether a user is an active loyalty member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	public static function is_active( $user_id ) {
		return 'active' === self::get_status( $user_id );
	}

	/**
	 * Saves a normalized membership status.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Membership status.
	 *
	 * @return void
	 */
	public static function set_status( $user_id, $status ) {
		$user_id = absint( $user_id );

		if ( 0 === $user_id ) {
			return;
		}

		update_user_meta( $user_id, self::META_KEY, self::normalize_status( $status ) );
		delete_user_meta( $user_id, self::OLD_META_KEY );
		delete_user_meta( $user_id, self::LEGACY_MEMBER_META_KEY );
	}

	/**
	 * Migrates old membership status values once.
	 *
	 * @return void
	 */
	public function migrate_existing_statuses() {
		if ( '1' === get_option( self::MIGRATION_OPTION_KEY, '0' ) ) {
			return;
		}

		$user_query = new WP_User_Query(
			array(
				'fields'     => 'ID',
				'number'     => -1,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => self::META_KEY,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => self::OLD_META_KEY,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => self::LEGACY_MEMBER_META_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $user_query->get_results() as $user_id ) {
			self::set_status( $user_id, $this->get_migration_source_status( $user_id ) );
		}

		update_option( self::MIGRATION_OPTION_KEY, '1', false );
	}

	/**
	 * Gets membership status from current or legacy keys for one-time migration only.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	private function get_migration_source_status( $user_id ) {
		$status = get_user_meta( $user_id, self::META_KEY, true );

		if ( '' === $status ) {
			$status = get_user_meta( $user_id, self::OLD_META_KEY, true );
		}

		if ( '' === $status ) {
			$status = get_user_meta( $user_id, self::LEGACY_MEMBER_META_KEY, true );
		}

		return self::normalize_status( $status );
	}

	/**
	 * Normalizes old and new membership status values.
	 *
	 * @param mixed $status Membership status.
	 *
	 * @return string
	 */
	public static function normalize_status( $status ) {
		$status = strtolower( trim( (string) $status ) );

		if ( in_array( $status, array( 'active', 'yes', 'true', '1', 'member' ), true ) ) {
			return 'active';
		}

		return 'not_active';
	}
}
