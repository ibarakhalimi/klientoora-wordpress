<?php
/**
 * Admin menu registration and dashboard rendering.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin admin menu.
 */
class Klientoora_Card_Admin_Menu {

	const ORDER_STATUS_META_KEY = '_klientoora_order_status';

	/**
	 * Registers the top-level admin menu page.
	 *
	 * @return void
	 */
	public function register_menu_page() {
		add_menu_page(
			__( 'Klientoora Club', 'klientoora-card' ),
			__( 'Klientoora Club', 'klientoora-card' ),
			'manage_options',
			'loyalty-club',
			array( $this, 'render_dashboard_page' ),
			'dashicons-awards',
			56
		);

		add_submenu_page(
			'loyalty-club',
			__( 'Settings', 'klientoora-card' ),
			__( 'Settings', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'Members', 'klientoora-card' ),
			__( 'Members', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-members',
			array( $this, 'render_members_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'Club Coupons', 'klientoora-card' ),
			__( 'Club Coupons', 'klientoora-card' ),
			'read',
			'loyalty-club-coupons',
			array( $this, 'render_coupons_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'challenges', 'klientoora-card' ),
			__( 'challenges', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-challenges',
			array( $this, 'render_challenges_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'מימוש נקודות', 'klientoora-card' ),
			__( 'מימוש נקודות', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-product-redemptions',
			array( $this, 'render_product_redemptions_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'Orders Managment', 'klientoora-card' ),
			__( 'Orders Managment', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-orders-management',
			array( $this, 'render_orders_management_page' )
		);

		add_submenu_page(
			'loyalty-club',
			__( 'Product Managment', 'klientoora-card' ),
			__( 'Product Managment', 'klientoora-card' ),
			'manage_options',
			'loyalty-club-product-management',
			array( $this, 'render_product_management_page' )
		);
	}

	/**
	 * Registers plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'klientoora_card_settings',
			'klientoora_card_make_webhook_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_webhook_url' ),
				'default'           => '',
			)
		);

		register_setting(
			'klientoora_card_settings',
			'klientoora_card_points_earning_percentage',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_points_earning_percentage' ),
				'default'           => 10,
			)
		);

		register_setting(
			'klientoora_card_settings',
			'loyalty_member_discount_percentage',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_percentage' ),
				'default'           => 0,
			)
		);

		register_setting(
			'klientoora_card_challenges',
			'klientoora_card_order_challenge_goal',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_order_challenge_goal' ),
				'default'           => 5,
			)
		);

		register_setting(
			'klientoora_card_challenges',
			'klientoora_card_order_challenge_coupon_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_order_challenge_coupon_id' ),
				'default'           => 0,
			)
		);

		add_settings_section(
			'klientoora_card_make_section',
			__( 'Make Integration', 'klientoora-card' ),
			array( $this, 'render_make_section_description' ),
			'klientoora-card-settings'
		);

		add_settings_field(
			'klientoora_card_make_webhook_url',
			__( 'חיבור Webhook Make', 'klientoora-card' ),
			array( $this, 'render_make_webhook_url_field' ),
			'klientoora-card-settings',
			'klientoora_card_make_section'
		);

		add_settings_section(
			'klientoora_card_points_section',
			__( 'Points', 'klientoora-card' ),
			array( $this, 'render_points_section_description' ),
			'klientoora-card-settings'
		);

		add_settings_field(
			'klientoora_card_points_earning_percentage',
			__( 'Points earning percentage', 'klientoora-card' ),
			array( $this, 'render_points_earning_percentage_field' ),
			'klientoora-card-settings',
			'klientoora_card_points_section'
		);

		add_settings_section(
			'klientoora_card_benefits_section',
			__( 'הטבות ומבצעים', 'klientoora-card' ),
			array( $this, 'render_benefits_section_description' ),
			'klientoora-card-settings'
		);

		add_settings_field(
			'loyalty_member_discount_percentage',
			__( 'Fixed member discount percentage', 'klientoora-card' ),
			array( $this, 'render_member_discount_percentage_field' ),
			'klientoora-card-settings',
			'klientoora_card_benefits_section'
		);
	}

	/**
	 * Renders the Loyalty Club dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$total_users         = $this->get_total_users();
		$total_points_issued = $this->get_total_points_issued();
		$cards               = $this->get_placeholder_cards();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Loyalty Club', 'klientoora-card' ); ?></h1>

			<div class="klientoora-card-dashboard">
				<div class="klientoora-card-dashboard__stats">
					<div class="klientoora-card-dashboard__card">
						<h2><?php echo esc_html__( 'Total WordPress Users', 'klientoora-card' ); ?></h2>
						<p class="klientoora-card-dashboard__value">
							<?php echo esc_html( number_format_i18n( $total_users ) ); ?>
						</p>
					</div>

					<div class="klientoora-card-dashboard__card">
						<h2><?php echo esc_html__( 'Total Loyalty Points Issued', 'klientoora-card' ); ?></h2>
						<p class="klientoora-card-dashboard__value">
							<?php echo esc_html( number_format_i18n( $total_points_issued ) ); ?>
						</p>
					</div>
				</div>

				<div class="klientoora-card-dashboard__grid">
					<?php foreach ( $cards as $card ) : ?>
						<div class="klientoora-card-dashboard__card">
							<h2><?php echo esc_html( $card['title'] ); ?></h2>
							<p><?php echo esc_html( $card['description'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<?php $this->render_temporary_points_sync_test(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the temporary points sync test action.
	 *
	 * @return void
	 */
	public function handle_test_points_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_test_points_sync' );

		$result = Klientoora_Card_Points::add_points( 13, 10, 'manual_test' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                            => 'loyalty-club',
					'klientoora_card_test_points'     => '1',
					'klientoora_card_previous_points' => $result['previous_points'],
					'klientoora_card_new_points'      => $result['new_points'],
					'klientoora_card_sync_attempted'  => $result['sync_attempted'] ? '1' : '0',
					'klientoora_card_sync_synced'     => $result['synced'] ? '1' : '0',
					'klientoora_card_response_code'   => $result['response_code'],
					'klientoora_card_sync_error'      => $result['error'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders a temporary admin tool for testing points sync.
	 *
	 * @return void
	 */
	private function render_temporary_points_sync_test() {
		$has_result       = isset( $_GET['klientoora_card_test_points'] );
		$previous_points  = isset( $_GET['klientoora_card_previous_points'] ) ? absint( $_GET['klientoora_card_previous_points'] ) : 0;
		$new_points       = isset( $_GET['klientoora_card_new_points'] ) ? absint( $_GET['klientoora_card_new_points'] ) : 0;
		$sync_attempted   = isset( $_GET['klientoora_card_sync_attempted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['klientoora_card_sync_attempted'] ) );
		$sync_synced      = isset( $_GET['klientoora_card_sync_synced'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['klientoora_card_sync_synced'] ) );
		$response_code    = isset( $_GET['klientoora_card_response_code'] ) ? absint( $_GET['klientoora_card_response_code'] ) : 0;
		$sync_error       = isset( $_GET['klientoora_card_sync_error'] ) ? sanitize_text_field( wp_unslash( $_GET['klientoora_card_sync_error'] ) ) : '';
		$sync_status_text = $sync_attempted ? __( 'Yes', 'klientoora-card' ) : __( 'No', 'klientoora-card' );
		?>
		<div class="klientoora-card-dashboard__card">
			<h2><?php echo esc_html__( 'Temporary Points Sync Test', 'klientoora-card' ); ?></h2>
			<p><?php echo esc_html__( 'Temporary testing tool. Remove this block when points sync is verified.', 'klientoora-card' ); ?></p>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( 'klientoora_card_test_points_sync' ); ?>
				<input type="hidden" name="action" value="klientoora_card_test_points_sync" />
				<?php submit_button( __( 'Test Points Sync (User 13)', 'klientoora-card' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( $has_result ) : ?>
				<ul class="klientoora-card-test-result">
					<li>
						<strong><?php echo esc_html__( 'Previous points balance:', 'klientoora-card' ); ?></strong>
						<?php echo esc_html( number_format_i18n( $previous_points ) ); ?>
					</li>
					<li>
						<strong><?php echo esc_html__( 'New points balance:', 'klientoora-card' ); ?></strong>
						<?php echo esc_html( number_format_i18n( $new_points ) ); ?>
					</li>
					<li>
						<strong><?php echo esc_html__( 'Make sync attempted:', 'klientoora-card' ); ?></strong>
						<?php echo esc_html( $sync_status_text ); ?>
					</li>
					<?php if ( $sync_attempted ) : ?>
						<li>
							<strong><?php echo esc_html__( 'Make sync result:', 'klientoora-card' ); ?></strong>
							<?php echo esc_html( $sync_synced ? __( 'Synced', 'klientoora-card' ) : __( 'Failed', 'klientoora-card' ) ); ?>
						</li>
						<li>
							<strong><?php echo esc_html__( 'Response code:', 'klientoora-card' ); ?></strong>
							<?php echo esc_html( $response_code ? (string) $response_code : '-' ); ?>
						</li>
					<?php endif; ?>
					<?php if ( '' !== $sync_error ) : ?>
						<li>
							<strong><?php echo esc_html__( 'Error:', 'klientoora-card' ); ?></strong>
							<?php echo esc_html( $sync_error ); ?>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Settings', 'klientoora-card' ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'klientoora_card_settings' );
				do_settings_sections( 'klientoora-card-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the active members admin page.
	 *
	 * @return void
	 */
	public function render_members_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$members = $this->get_active_members();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Members', 'klientoora-card' ); ?></h1>

			<div class="klientoora-card-members">
				<div class="klientoora-card-dashboard__card">
					<h2>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d is the number of active loyalty members. */
								__( 'Active members: %d', 'klientoora-card' ),
								count( $members )
							)
						);
						?>
					</h2>

					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'User ID', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Name', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Email', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Current points balance', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Total redeemed points', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Orders count', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Total order amount', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Registered', 'klientoora-card' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'klientoora-card' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $members ) ) : ?>
								<tr>
									<td colspan="9"><?php echo esc_html__( 'No active members found.', 'klientoora-card' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $members as $member ) : ?>
									<?php $this->render_member_table_row( $member ); ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the members view for the standalone admin-main page.
	 *
	 * @return void
	 */
	public function render_members_admin_main_view() {
		if ( ! current_user_can( 'manage_options' ) ) {
			?>
			<div class="klientoora-admin-main-members">
				<h2><?php echo esc_html__( 'Members', 'klientoora-card' ); ?></h2>
				<p><?php echo esc_html__( 'You do not have permission to view members.', 'klientoora-card' ); ?></p>
			</div>
			<?php
			return;
		}

		$members           = $this->get_active_members();
		$joined_this_week  = $this->count_members_joined_this_week( $members );
		$total_members     = count( $members );
		?>
		<div class="klientoora-admin-main-members">
			<header class="klientoora-admin-main-members__header">
				<div>
					<h2><?php echo esc_html__( 'Members', 'klientoora-card' ); ?></h2>
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d is the number of active loyalty members. */
								__( 'Active members: %d', 'klientoora-card' ),
								count( $members )
							)
						);
						?>
					</p>
				</div>
			</header>

			<div class="klientoora-admin-main-members__stats" aria-label="<?php echo esc_attr__( 'Members summary', 'klientoora-card' ); ?>">
				<div class="klientoora-admin-main-members__stat">
					<span><?php echo esc_html__( 'מספר חברים', 'klientoora-card' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $total_members ) ); ?></strong>
				</div>
				<div class="klientoora-admin-main-members__stat">
					<span><?php echo esc_html__( 'מצטרפים השבוע', 'klientoora-card' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $joined_this_week ) ); ?></strong>
				</div>
				<div class="klientoora-admin-main-members__stat">
					<span><?php echo esc_html__( 'בהמשך אחליט', 'klientoora-card' ); ?></span>
					<strong>-</strong>
				</div>
			</div>

			<div class="klientoora-admin-main-members__table-wrap">
				<table class="klientoora-admin-main-members__table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'User ID', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Name', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Email', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Current points balance', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Total redeemed points', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Orders count', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Total order amount', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Registered', 'klientoora-card' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'klientoora-card' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $members ) ) : ?>
							<tr>
								<td colspan="9"><?php echo esc_html__( 'No active members found.', 'klientoora-card' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $members as $member ) : ?>
								<?php $this->render_admin_main_member_table_row( $member ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the club activity view for the standalone admin-main page.
	 *
	 * @return void
	 */
	public function render_club_activity_admin_main_view() {
		if ( ! current_user_can( 'manage_options' ) ) {
			?>
			<div class="klientoora-admin-main-club-activity">
				<h2><?php echo esc_html__( 'פעילות מועדון', 'klientoora-card' ); ?></h2>
				<p><?php echo esc_html__( 'You do not have permission to view club activity.', 'klientoora-card' ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<div class="klientoora-admin-main-club-activity">
			<header class="klientoora-admin-main-club-activity__header">
				<div>
					<h2><?php echo esc_html__( 'פעילות מועדון', 'klientoora-card' ); ?></h2>
					<p><?php echo esc_html__( 'Club Coupons, challenges, and point redemptions.', 'klientoora-card' ); ?></p>
				</div>
			</header>

			<?php $this->render_admin_main_coupon_notice(); ?>

			<div class="klientoora-admin-main-club-activity__tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Club activity sections', 'klientoora-card' ); ?>">
				<button class="is-active" type="button" role="tab" aria-selected="true" data-klientoora-club-activity-tab="club-coupons"><?php echo esc_html__( 'ניהול קופונים', 'klientoora-card' ); ?></button>
				<button type="button" role="tab" aria-selected="false" data-klientoora-club-activity-tab="challenges"><?php echo esc_html__( 'challenges', 'klientoora-card' ); ?></button>
				<button type="button" role="tab" aria-selected="false" data-klientoora-club-activity-tab="point-redemptions"><?php echo esc_html__( 'מימוש נקודות', 'klientoora-card' ); ?></button>
			</div>

			<section class="klientoora-admin-main-club-activity__panel" data-klientoora-club-activity-panel="club-coupons">
				<?php $this->render_admin_main_club_coupons_view(); ?>
			</section>

			<section class="klientoora-admin-main-club-activity__panel" data-klientoora-club-activity-panel="challenges" hidden>
				<?php $this->render_admin_main_challenges_view(); ?>
			</section>

			<section class="klientoora-admin-main-club-activity__panel" data-klientoora-club-activity-panel="point-redemptions" hidden>
				<?php $this->render_admin_main_product_redemptions_view(); ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Renders a clean Club Coupons view for admin-main.
	 *
	 * @return void
	 */
	private function render_admin_main_club_coupons_view() {
		$this->render_admin_main_member_discount_section();

		if ( ! $this->is_woocommerce_active() ) {
			?>
			<p class="klientoora-admin-main-club-activity__empty"><?php echo esc_html__( 'WooCommerce is not active. Club Coupons are disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
			<?php
			return;
		}

		$coupons        = $this->get_loyalty_coupons();
		$active_coupons = $this->get_active_loyalty_coupons( $coupons );
		?>
		<div class="klientoora-admin-main-club-activity__section-heading">
			<h3><?php echo esc_html__( 'קופונים פעילים', 'klientoora-card' ); ?></h3>
			<span><?php echo esc_html( number_format_i18n( count( $active_coupons ) ) ); ?></span>
		</div>

		<?php $this->render_admin_main_active_coupons_section( $active_coupons ); ?>

		<div class="klientoora-admin-main-club-activity__section-heading">
			<h3><?php echo esc_html__( 'מאגר קופונים', 'klientoora-card' ); ?></h3>
			<span><?php echo esc_html( number_format_i18n( count( $this->get_coupon_templates() ) ) ); ?></span>
		</div>
		<div class="klientoora-admin-main-coupons-bank">
			<?php foreach ( $this->get_coupon_templates() as $template ) : ?>
				<?php $this->render_admin_main_coupon_template_card( $template ); ?>
			<?php endforeach; ?>
		</div>

		<details class="klientoora-admin-main-coupons-manual">
			<summary><?php echo esc_html__( 'הוספת קופון ידנית', 'klientoora-card' ); ?></summary>
			<?php $this->render_admin_main_manual_coupon_form(); ?>
		</details>
		<?php
	}

	/**
	 * Renders active club coupons in the standalone admin view.
	 *
	 * @param array<int, WC_Coupon> $coupons Active loyalty coupons.
	 *
	 * @return void
	 */
	private function render_admin_main_active_coupons_section( $coupons ) {
		if ( empty( $coupons ) ) {
			?>
			<p class="klientoora-admin-main-club-activity__empty"><?php echo esc_html__( 'אין כרגע קופונים פעילים.', 'klientoora-card' ); ?></p>
			<?php
			return;
		}
		?>
		<div class="klientoora-admin-main-coupons-bank">
			<?php foreach ( $coupons as $coupon ) : ?>
				<?php $this->render_admin_main_active_coupon_card( $coupon ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Renders the synced fixed member discount setting for admin-main.
	 *
	 * @return void
	 */
	private function render_admin_main_member_discount_section() {
		$percentage = $this->sanitize_percentage( get_option( 'loyalty_member_discount_percentage', 0 ) );
		?>
		<section class="klientoora-admin-main-member-discount">
			<div>
				<h3><?php echo esc_html__( 'הנחה קבועה לחברי מועדון', 'klientoora-card' ); ?></h3>
				<p><?php echo esc_html__( 'השדה הזה מסונכרן עם Fixed member discount percentage בהגדרות התוסף ומחיל את ההנחה בעגלה לחברי מועדון.', 'klientoora-card' ); ?></p>
			</div>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( 'klientoora_card_save_member_discount' ); ?>
				<input type="hidden" name="action" value="klientoora_card_save_member_discount" />
				<input type="hidden" name="klientoora_coupons_return" value="admin-main" />
				<label for="klientoora_admin_main_member_discount"><?php echo esc_html__( 'אחוז הנחה קבוע', 'klientoora-card' ); ?></label>
				<div class="klientoora-admin-main-member-discount__control">
					<input
						type="number"
						id="klientoora_admin_main_member_discount"
						name="loyalty_member_discount_percentage"
						min="0"
						max="100"
						step="0.01"
						value="<?php echo esc_attr( $percentage ); ?>"
					/>
					<span>%</span>
				</div>
				<button type="submit"><?php echo esc_html__( 'שמירת הנחה', 'klientoora-card' ); ?></button>
			</form>
		</section>
		<?php
	}

	/**
	 * Renders one coupon template card for admin-main.
	 *
	 * @param array<string, string> $template Coupon template.
	 *
	 * @return void
	 */
	private function render_admin_main_coupon_template_card( $template ) {
		$dialog_id              = 'klientoora-admin-main-coupon-template-' . sanitize_html_class( strtolower( $template['coupon_code'] ) );
		$template_dialog_values = $template;
		$template_dialog_values['coupon_code'] = $this->get_available_coupon_code( $template['coupon_code'] );
		?>
		<article class="klientoora-admin-main-coupon-card">
			<button type="button" data-klientoora-coupon-dialog-trigger="<?php echo esc_attr( $dialog_id ); ?>">
				<span class="klientoora-admin-main-coupon-card__status"><?php echo esc_html__( 'הוספה', 'klientoora-card' ); ?></span>
				<strong><?php echo esc_html( $template['title'] ); ?></strong>
			</button>
		</article>
		<?php $this->render_admin_main_coupon_dialog( $dialog_id, $template_dialog_values, null, __( 'הוספה להפעלה', 'klientoora-card' ) ); ?>
		<?php
	}

	/**
	 * Renders one active coupon card for admin-main.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return void
	 */
	private function render_admin_main_active_coupon_card( $coupon ) {
		$dialog_id = 'klientoora-admin-main-active-coupon-' . absint( $coupon->get_id() );
		$values    = $this->get_admin_main_coupon_values_from_coupon( $coupon );
		$discount  = $this->format_admin_main_coupon_discount_summary( $coupon );
		$expiry    = $this->format_admin_main_coupon_expiry( $coupon );
		?>
		<article class="klientoora-admin-main-coupon-card is-active">
			<button type="button" data-klientoora-coupon-dialog-trigger="<?php echo esc_attr( $dialog_id ); ?>">
				<span class="klientoora-admin-main-coupon-card__status"><?php echo esc_html__( 'פעיל', 'klientoora-card' ); ?></span>
				<strong><?php echo esc_html( $coupon->get_code() ); ?></strong>
				<span class="klientoora-admin-main-coupon-card__meta">
					<span>
						<b><?php echo esc_html__( 'מהות ההנחה', 'klientoora-card' ); ?></b>
						<?php echo esc_html( $discount ); ?>
					</span>
					<span>
						<b><?php echo esc_html__( 'תוקף קופון', 'klientoora-card' ); ?></b>
						<?php echo esc_html( $expiry ); ?>
					</span>
				</span>
			</button>
		</article>
		<?php $this->render_admin_main_coupon_dialog( $dialog_id, $values, $coupon, __( 'עדכון קופון פעיל', 'klientoora-card' ) ); ?>
		<?php
	}

	/**
	 * Renders an editable coupon dialog for template activation and active coupons.
	 *
	 * @param string         $dialog_id Dialog ID.
	 * @param array<string, string> $source    Coupon values or template defaults.
	 * @param WC_Coupon|null $coupon    Existing coupon object.
	 * @param string         $button_text Submit button text.
	 *
	 * @return void
	 */
	private function render_admin_main_coupon_dialog( $dialog_id, $source, $coupon, $button_text ) {
		$is_edit = $coupon && $coupon->get_id() && 'publish' === $coupon->get_status();
		$values  = $is_edit ? $this->get_admin_main_coupon_values_from_coupon( $coupon ) : $source;
		$form_id = $dialog_id . '-form';
		?>
		<dialog class="klientoora-admin-main-coupon-dialog" id="<?php echo esc_attr( $dialog_id ); ?>">
			<div class="klientoora-admin-main-coupon-dialog__surface">
				<header class="klientoora-admin-main-coupon-dialog__header">
					<div>
						<p><?php echo esc_html( $is_edit ? __( 'קופון פעיל', 'klientoora-card' ) : __( 'הפעלת קמפיין', 'klientoora-card' ) ); ?></p>
						<h3><?php echo esc_html( isset( $values['title'] ) ? $values['title'] : $values['coupon_code'] ); ?></h3>
					</div>
					<button type="button" data-klientoora-coupon-dialog-close aria-label="<?php echo esc_attr__( 'Close dialog', 'klientoora-card' ); ?>">&times;</button>
				</header>

				<form id="<?php echo esc_attr( $form_id ); ?>" class="klientoora-admin-main-coupon-dialog__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<?php wp_nonce_field( 'klientoora_card_save_coupon' ); ?>
					<input type="hidden" name="action" value="klientoora_card_save_coupon" />
					<input type="hidden" name="klientoora_coupons_return" value="admin-main" />
					<?php if ( $coupon && $coupon->get_id() ) : ?>
						<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon->get_id() ); ?>" />
					<?php endif; ?>
					<?php $this->render_admin_main_coupon_edit_fields( $values, $dialog_id ); ?>
				</form>

				<footer class="klientoora-admin-main-coupon-dialog__footer">
					<div class="klientoora-admin-main-coupon-dialog__actions">
						<button type="submit" form="<?php echo esc_attr( $form_id ); ?>"><?php echo esc_html( $button_text ); ?></button>
						<button type="button" data-klientoora-coupon-dialog-close><?php echo esc_html__( 'ביטול', 'klientoora-card' ); ?></button>
					</div>

					<?php if ( $is_edit ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<?php wp_nonce_field( 'klientoora_card_delete_coupon' ); ?>
							<input type="hidden" name="action" value="klientoora_card_delete_coupon" />
							<input type="hidden" name="klientoora_coupons_return" value="admin-main" />
							<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon->get_id() ); ?>" />
							<button class="is-danger" type="submit"><?php echo esc_html__( 'הסרה מהפעלה', 'klientoora-card' ); ?></button>
						</form>
					<?php endif; ?>
				</footer>
			</div>
		</dialog>
		<?php
	}

	/**
	 * Renders editable coupon fields.
	 *
	 * @param array<string, string> $values    Coupon values.
	 * @param string                $id_prefix Unique field ID prefix.
	 *
	 * @return void
	 */
	private function render_admin_main_coupon_edit_fields( $values, $id_prefix ) {
		?>
		<div class="klientoora-admin-main-coupon-dialog__grid">
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-coupon-code"><?php echo esc_html__( 'Coupon code', 'klientoora-card' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id_prefix ); ?>-coupon-code" name="coupon_code" value="<?php echo esc_attr( $values['coupon_code'] ); ?>" required />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-discount-type"><?php echo esc_html__( 'Discount type', 'klientoora-card' ); ?></label>
				<select id="<?php echo esc_attr( $id_prefix ); ?>-discount-type" name="discount_type">
					<?php foreach ( $this->get_allowed_coupon_discount_types() as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $values['discount_type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-amount"><?php echo esc_html__( 'Coupon amount', 'klientoora-card' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-amount" name="amount" value="<?php echo esc_attr( $values['amount'] ); ?>" min="0" step="0.01" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-usage-limit"><?php echo esc_html__( 'Usage limit', 'klientoora-card' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-usage-limit" name="usage_limit" value="<?php echo esc_attr( $values['usage_limit'] ); ?>" min="0" step="1" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-usage-limit-per-user"><?php echo esc_html__( 'Usage limit per user', 'klientoora-card' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-usage-limit-per-user" name="usage_limit_per_user" value="<?php echo esc_attr( $values['usage_limit_per_user'] ); ?>" min="0" step="1" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-expiry-date"><?php echo esc_html__( 'Expiry date', 'klientoora-card' ); ?></label>
				<input type="date" id="<?php echo esc_attr( $id_prefix ); ?>-expiry-date" name="expiry_date" value="<?php echo esc_attr( $values['expiry_date'] ); ?>" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-minimum-spend"><?php echo esc_html__( 'Minimum spend', 'klientoora-card' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-minimum-spend" name="minimum_spend" value="<?php echo esc_attr( $values['minimum_spend'] ); ?>" min="0" step="0.01" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $id_prefix ); ?>-maximum-spend"><?php echo esc_html__( 'Maximum spend', 'klientoora-card' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id_prefix ); ?>-maximum-spend" name="maximum_spend" value="<?php echo esc_attr( $values['maximum_spend'] ); ?>" min="0" step="0.01" />
			</div>
			<div class="is-wide">
				<label for="<?php echo esc_attr( $id_prefix ); ?>-description"><?php echo esc_html__( 'Description', 'klientoora-card' ); ?></label>
				<textarea id="<?php echo esc_attr( $id_prefix ); ?>-description" name="description" rows="3"><?php echo esc_textarea( $values['description'] ); ?></textarea>
			</div>
			<div class="is-wide klientoora-admin-main-coupon-dialog__checks">
				<label><input type="checkbox" name="free_shipping" value="yes" <?php checked( 'yes', $values['free_shipping'] ); ?> /> <?php echo esc_html__( 'Allow free shipping', 'klientoora-card' ); ?></label>
				<label><input type="checkbox" name="members_only" value="yes" <?php checked( 'yes', $values['members_only'] ); ?> /> <?php echo esc_html__( 'Members only', 'klientoora-card' ); ?></label>
				<label><input type="checkbox" name="show_in_popup" value="yes" <?php checked( 'yes', $values['show_in_popup'] ); ?> /> <?php echo esc_html__( 'Show in loyalty popup', 'klientoora-card' ); ?></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a clean manual coupon form for admin-main.
	 *
	 * @return void
	 */
	private function render_admin_main_manual_coupon_form() {
		?>
		<form class="klientoora-admin-main-coupon-manual-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'klientoora_card_save_coupon' ); ?>
			<input type="hidden" name="action" value="klientoora_card_save_coupon" />
			<input type="hidden" name="klientoora_coupons_return" value="admin-main" />

			<div>
				<label for="klientoora_admin_main_coupon_code"><?php echo esc_html__( 'Coupon code', 'klientoora-card' ); ?></label>
				<input type="text" id="klientoora_admin_main_coupon_code" name="coupon_code" required />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_discount_type"><?php echo esc_html__( 'Discount type', 'klientoora-card' ); ?></label>
				<select id="klientoora_admin_main_coupon_discount_type" name="discount_type">
					<?php foreach ( $this->get_allowed_coupon_discount_types() as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_amount"><?php echo esc_html__( 'Coupon amount', 'klientoora-card' ); ?></label>
				<input type="number" id="klientoora_admin_main_coupon_amount" name="amount" min="0" step="0.01" />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_usage_limit"><?php echo esc_html__( 'Usage limit', 'klientoora-card' ); ?></label>
				<input type="number" id="klientoora_admin_main_coupon_usage_limit" name="usage_limit" min="0" step="1" />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_usage_limit_per_user"><?php echo esc_html__( 'Usage limit per user', 'klientoora-card' ); ?></label>
				<input type="number" id="klientoora_admin_main_coupon_usage_limit_per_user" name="usage_limit_per_user" min="0" step="1" />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_expiry_date"><?php echo esc_html__( 'Expiry date', 'klientoora-card' ); ?></label>
				<input type="date" id="klientoora_admin_main_coupon_expiry_date" name="expiry_date" />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_minimum_spend"><?php echo esc_html__( 'Minimum spend', 'klientoora-card' ); ?></label>
				<input type="number" id="klientoora_admin_main_coupon_minimum_spend" name="minimum_spend" min="0" step="0.01" />
			</div>
			<div>
				<label for="klientoora_admin_main_coupon_maximum_spend"><?php echo esc_html__( 'Maximum spend', 'klientoora-card' ); ?></label>
				<input type="number" id="klientoora_admin_main_coupon_maximum_spend" name="maximum_spend" min="0" step="0.01" />
			</div>
			<div class="is-wide">
				<label for="klientoora_admin_main_coupon_description"><?php echo esc_html__( 'Description', 'klientoora-card' ); ?></label>
				<textarea id="klientoora_admin_main_coupon_description" name="description" rows="2"></textarea>
			</div>
			<div class="is-wide klientoora-admin-main-coupon-manual-form__checks">
				<label><input type="checkbox" name="free_shipping" value="yes" /> <?php echo esc_html__( 'Allow free shipping', 'klientoora-card' ); ?></label>
				<label><input type="checkbox" name="members_only" value="yes" /> <?php echo esc_html__( 'Members only', 'klientoora-card' ); ?></label>
				<label><input type="checkbox" name="show_in_popup" value="yes" checked /> <?php echo esc_html__( 'Show in loyalty popup', 'klientoora-card' ); ?></label>
			</div>
			<div class="is-wide">
				<button type="submit"><?php echo esc_html__( 'הוספת קופון', 'klientoora-card' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Renders a clean challenges view for admin-main.
	 *
	 * @return void
	 */
	private function render_admin_main_challenges_view() {
		$goal      = max( 1, absint( get_option( 'klientoora_card_order_challenge_goal', 5 ) ) );
		$coupon_id = absint( get_option( 'klientoora_card_order_challenge_coupon_id', 0 ) );
		$coupon    = $coupon_id && $this->is_woocommerce_active() ? new WC_Coupon( $coupon_id ) : null;
		$coupon_label = $coupon && $coupon->get_id()
			? $this->get_coupon_option_label( $coupon )
			: __( 'משלוח חינם', 'klientoora-card' );
		?>
		<div class="klientoora-admin-main-club-activity__section-heading">
			<h3><?php echo esc_html__( 'challenges', 'klientoora-card' ); ?></h3>
		</div>

		<div class="klientoora-admin-main-club-activity__stats">
			<div class="klientoora-admin-main-club-activity__stat">
				<span><?php echo esc_html__( 'מספר הזמנות ליעד', 'klientoora-card' ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( $goal ) ); ?></strong>
			</div>
			<div class="klientoora-admin-main-club-activity__stat">
				<span><?php echo esc_html__( 'קופון הפרס', 'klientoora-card' ); ?></span>
				<strong><?php echo esc_html( $coupon_label ); ?></strong>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a clean product redemptions view for admin-main.
	 *
	 * @return void
	 */
	private function render_admin_main_product_redemptions_view() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			?>
			<p class="klientoora-admin-main-club-activity__empty"><?php echo esc_html__( 'WooCommerce is not active. Product point redemption is disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
			<?php
			return;
		}

		$products = class_exists( 'Klientoora_Card_Product_Redemption' ) ? Klientoora_Card_Product_Redemption::get_admin_products() : array();
		?>
		<div class="klientoora-admin-main-club-activity__section-heading">
			<h3><?php echo esc_html__( 'מימוש נקודות', 'klientoora-card' ); ?></h3>
			<span><?php echo esc_html( number_format_i18n( count( $products ) ) ); ?></span>
		</div>

		<div class="klientoora-admin-main-club-activity__table-wrap">
			<table class="klientoora-admin-main-club-activity__table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'ניתן לרכישה בנקודות', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'מוצר', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'מחיר רגיל', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'שווי נקודות', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'סטטוס', 'klientoora-card' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $products ) ) : ?>
						<tr>
							<td colspan="5"><?php echo esc_html__( 'No products found.', 'klientoora-card' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $products as $product ) : ?>
							<?php $this->render_admin_main_product_redemption_row( $product ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders one clean product redemption row for admin-main.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function render_admin_main_product_redemption_row( $product ) {
		$product_id   = $product->get_id();
		$enabled      = 'yes' === get_post_meta( $product_id, Klientoora_Card_Product_Redemption::META_ENABLED, true );
		$points_price = Klientoora_Card_Product_Redemption::get_product_points_price( $product );
		?>
		<tr>
			<td><?php echo esc_html( $enabled ? __( 'פעיל', 'klientoora-card' ) : __( 'לא פעיל', 'klientoora-card' ) ); ?></td>
			<td><strong><?php echo esc_html( $product->get_name() ); ?></strong></td>
			<td><?php echo wp_kses_post( $product->get_price_html() ? $product->get_price_html() : '-' ); ?></td>
			<td><?php echo esc_html( 0 < $points_price ? number_format_i18n( $points_price ) : '-' ); ?></td>
			<td><?php echo esc_html( $product->get_status() ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Renders admin-main coupon notices as toasts.
	 *
	 * @return void
	 */
	private function render_admin_main_coupon_notice() {
		$notice = isset( $_GET['klientoora_card_coupon_notice'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_coupon_notice'] ) ) : '';

		if ( '' === $notice ) {
			return;
		}

		$messages = $this->get_coupon_notice_messages();

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		$type = in_array( $notice, array( 'created', 'updated', 'deleted', 'member_discount_saved' ), true ) ? 'success' : 'error';
		?>
		<div class="klientoora-admin-main__toast is-<?php echo esc_attr( $type ); ?>" role="status" aria-live="polite" data-klientoora-admin-toast>
			<span><?php echo esc_html( $messages[ $notice ] ); ?></span>
			<button type="button" data-klientoora-admin-toast-close aria-label="<?php echo esc_attr__( 'Close notification', 'klientoora-card' ); ?>">&times;</button>
		</div>
		<?php
	}

	/**
	 * Renders the orders management page.
	 *
	 * @return void
	 */
	public function render_orders_management_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = isset( $_GET['klientoora_card_orders_notice'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_orders_notice'] ) ) : '';
		$orders = $this->get_orders_for_management();
		$orders_by_status = $this->group_orders_by_klientoora_status( $orders );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Orders Managment', 'klientoora-card' ); ?></h1>

			<?php $this->render_orders_management_notice( $notice ); ?>

			<?php if ( ! function_exists( 'wc_get_orders' ) ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'WooCommerce is not active. Orders management is disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
				</div>
			<?php else : ?>
				<div class="klientoora-card-orders-management">
					<div class="klientoora-card-dashboard__card">
						<h2><?php echo esc_html__( 'כל ההזמנות', 'klientoora-card' ); ?></h2>

						<?php if ( empty( $orders ) ) : ?>
							<p class="klientoora-card-orders-management__empty"><?php echo esc_html__( 'No orders found.', 'klientoora-card' ); ?></p>
						<?php else : ?>
							<div class="klientoora-card-orders-board">
								<?php foreach ( $this->get_order_status_options() as $status_key => $status_label ) : ?>
									<section class="klientoora-card-orders-board__column">
										<header class="klientoora-card-orders-board__column-header">
											<h3><?php echo esc_html( $status_label ); ?></h3>
											<span><?php echo esc_html( number_format_i18n( count( $orders_by_status[ $status_key ] ) ) ); ?></span>
										</header>

										<div class="klientoora-card-orders-board__cards">
											<?php if ( empty( $orders_by_status[ $status_key ] ) ) : ?>
												<p class="klientoora-card-orders-board__empty"><?php echo esc_html__( 'אין הזמנות בסטטוס זה.', 'klientoora-card' ); ?></p>
											<?php else : ?>
												<?php foreach ( $orders_by_status[ $status_key ] as $order ) : ?>
													<?php $this->render_order_management_card( $order ); ?>
												<?php endforeach; ?>
											<?php endif; ?>
										</div>
									</section>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the orders management view for the standalone admin-main page.
	 *
	 * @return void
	 */
	public function render_orders_admin_main_view() {
		if ( ! current_user_can( 'manage_options' ) ) {
			?>
			<div class="klientoora-admin-main-orders">
				<h2><?php echo esc_html__( 'Orders Managment', 'klientoora-card' ); ?></h2>
				<p><?php echo esc_html__( 'You do not have permission to view orders.', 'klientoora-card' ); ?></p>
			</div>
			<?php
			return;
		}

		$notice = isset( $_GET['klientoora_card_orders_notice'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_orders_notice'] ) ) : '';
		?>
		<div class="klientoora-admin-main-orders">
			<header class="klientoora-admin-main-orders__header">
				<div>
					<h2><?php echo esc_html__( 'Orders Managment', 'klientoora-card' ); ?></h2>
					<p><?php echo esc_html__( 'כל ההזמנות', 'klientoora-card' ); ?></p>
				</div>
			</header>

			<?php $this->render_admin_main_orders_notice( $notice ); ?>

			<?php if ( ! function_exists( 'wc_get_orders' ) ) : ?>
				<p class="klientoora-admin-main-orders__empty"><?php echo esc_html__( 'WooCommerce is not active. Orders management is disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
			<?php else : ?>
				<?php
				$orders           = $this->get_orders_for_management();
				$orders_by_status = $this->group_orders_by_klientoora_status( $orders );
				?>

				<?php if ( empty( $orders ) ) : ?>
					<p class="klientoora-admin-main-orders__empty"><?php echo esc_html__( 'No orders found.', 'klientoora-card' ); ?></p>
				<?php else : ?>
					<?php
					$this->render_admin_main_orders_status_summary( $orders_by_status );
					$this->render_admin_main_orders_toolbar();
					$this->render_admin_main_orders_board( __( 'ניהול הזמנות', 'klientoora-card' ), $this->get_primary_order_status_options(), $orders_by_status );
					$this->render_admin_main_orders_board( __( 'ניהול משלוח ואיסוף', 'klientoora-card' ), $this->get_fulfillment_order_status_options(), $orders_by_status );
					?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders a standalone orders status board.
	 *
	 * @param string                       $title            Board title.
	 * @param array<string, string>        $status_options   Status labels keyed by status.
	 * @param array<string, array<int, WC_Order>> $orders_by_status Orders grouped by status.
	 *
	 * @return void
	 */
	private function render_admin_main_orders_board( $title, $status_options, $orders_by_status ) {
		?>
		<section class="klientoora-admin-main-orders__section-block">
			<h3><?php echo esc_html( $title ); ?></h3>

			<div class="klientoora-admin-main-orders__board">
				<?php foreach ( $status_options as $status_key => $status_label ) : ?>
					<?php $status_orders = isset( $orders_by_status[ $status_key ] ) ? $orders_by_status[ $status_key ] : array(); ?>
					<section class="klientoora-admin-main-orders__column" data-klientoora-order-status-column="<?php echo esc_attr( $status_key ); ?>">
						<header class="klientoora-admin-main-orders__column-header">
							<h4><?php echo esc_html( $status_label ); ?></h4>
							<span><?php echo esc_html( number_format_i18n( count( $status_orders ) ) ); ?></span>
						</header>

						<div class="klientoora-admin-main-orders__cards">
							<?php if ( empty( $status_orders ) ) : ?>
								<p class="klientoora-admin-main-orders__empty"><?php echo esc_html__( 'אין הזמנות בסטטוס זה.', 'klientoora-card' ); ?></p>
							<?php else : ?>
								<?php foreach ( $status_orders as $order ) : ?>
									<?php $this->render_admin_main_order_card( $order ); ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Renders current order counts for every Klientoora status.
	 *
	 * @param array<string, array<int, WC_Order>> $orders_by_status Orders grouped by status.
	 *
	 * @return void
	 */
	private function render_admin_main_orders_status_summary( $orders_by_status ) {
		?>
		<section class="klientoora-admin-main-orders__summary" aria-label="<?php echo esc_attr__( 'Order status summary', 'klientoora-card' ); ?>">
			<?php foreach ( $this->get_order_status_options() as $status_key => $status_label ) : ?>
				<?php $status_orders = isset( $orders_by_status[ $status_key ] ) ? $orders_by_status[ $status_key ] : array(); ?>
				<div class="klientoora-admin-main-orders__summary-item" data-klientoora-order-status-summary="<?php echo esc_attr( $status_key ); ?>">
					<span><?php echo esc_html( $status_label ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( count( $status_orders ) ) ); ?></strong>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}

	/**
	 * Renders the standalone orders toolbar.
	 *
	 * @return void
	 */
	private function render_admin_main_orders_toolbar() {
		?>
		<div class="klientoora-admin-main-orders__toolbar">
			<div class="klientoora-admin-main-orders__status-filter">
				<button class="klientoora-admin-main-orders__status-filter-button" type="button">
					<?php echo esc_html__( 'תצוגת סטטוס', 'klientoora-card' ); ?>
				</button>

				<div class="klientoora-admin-main-orders__status-filter-panel" role="group" aria-label="<?php echo esc_attr__( 'Status columns', 'klientoora-card' ); ?>">
					<?php foreach ( $this->get_order_status_options() as $status_key => $status_label ) : ?>
						<label>
							<input type="checkbox" value="<?php echo esc_attr( $status_key ); ?>" data-klientoora-order-status-toggle checked />
							<span><?php echo esc_html( $status_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders standalone order notices.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function render_admin_main_orders_notice( $notice ) {
		$messages = $this->get_orders_management_notice_messages();

		if ( empty( $messages[ $notice ] ) ) {
			return;
		}

		$type = 'woocommerce_inactive' === $notice || 'not_found' === $notice ? 'error' : 'success';
		?>
		<div class="klientoora-admin-main__toast is-<?php echo esc_attr( $type ); ?>" role="status" aria-live="polite" data-klientoora-admin-toast>
			<span><?php echo esc_html( $messages[ $notice ] ); ?></span>
			<button type="button" data-klientoora-admin-toast-close aria-label="<?php echo esc_attr__( 'Close notification', 'klientoora-card' ); ?>">&times;</button>
		</div>
		<?php
	}

	/**
	 * Renders the product management page.
	 *
	 * @return void
	 */
	public function render_product_management_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$products = class_exists( 'Klientoora_Card_Product_Redemption' ) ? Klientoora_Card_Product_Redemption::get_admin_products() : array();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Product Managment', 'klientoora-card' ); ?></h1>

			<?php if ( ! function_exists( 'wc_get_products' ) ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'WooCommerce is not active. Product management is disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
				</div>
			<?php else : ?>
				<div class="klientoora-card-product-management">
					<div class="klientoora-card-dashboard__card">
						<h2><?php echo esc_html__( 'כל המוצרים', 'klientoora-card' ); ?></h2>

						<table class="widefat striped klientoora-card-product-management__table">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'מוצר', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'מחיר רגיל', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'סטטוס', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'מלאי', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'ניתן לרכישה בנקודות', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'שווי נקודות', 'klientoora-card' ); ?></th>
									<th><?php echo esc_html__( 'Actions', 'klientoora-card' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $products ) ) : ?>
									<tr>
										<td colspan="7"><?php echo esc_html__( 'No products found.', 'klientoora-card' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $products as $product ) : ?>
										<?php $this->render_product_management_row( $product ); ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handles saving Klientoora statuses from the orders management page.
	 *
	 * @return void
	 */
	public function handle_save_order_statuses() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_save_order_statuses' );

		if ( ! function_exists( 'wc_get_order' ) ) {
			$this->redirect_orders_management_page( 'woocommerce_inactive' );
		}

		$statuses = isset( $_POST['klientoora_order_status'] ) && is_array( $_POST['klientoora_order_status'] )
			? wp_unslash( $_POST['klientoora_order_status'] )
			: array();

		foreach ( $statuses as $order_id => $status ) {
			$order = wc_get_order( absint( $order_id ) );

			if ( ! $order ) {
				continue;
			}

			$this->save_order_klientoora_status( $order, $status );
		}

		$this->redirect_orders_management_page( 'saved' );
	}

	/**
	 * Handles moving one order to the next Klientoora status.
	 *
	 * @return void
	 */
	public function handle_advance_order_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_advance_order_status' );

		if ( ! function_exists( 'wc_get_order' ) ) {
			$this->redirect_orders_management_page( 'woocommerce_inactive' );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->redirect_orders_management_page( 'not_found' );
		}

		$current_status = $this->sanitize_order_status_key( $order->get_meta( self::ORDER_STATUS_META_KEY ) );
		$next_status    = $this->get_next_order_status_key( $current_status );

		if ( $next_status ) {
			$this->save_order_klientoora_status( $order, $next_status );
		}

		$this->redirect_orders_management_page( 'saved' );
	}

	/**
	 * Registers the Klientoora order meta box on WooCommerce order screens.
	 *
	 * @return void
	 */
	public function register_order_meta_box() {
		$screens = array( 'shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screens[] = 'woocommerce_page_wc-orders';
		}

		add_meta_box(
			'klientoora-order-details',
			__( 'Klientoora', 'klientoora-card' ),
			array( $this, 'render_order_meta_box' ),
			array_unique( $screens ),
			'side',
			'default'
		);
	}

	/**
	 * Renders the Klientoora order meta box.
	 *
	 * @param WP_Post|WC_Order $post_or_order Order post or order object.
	 *
	 * @return void
	 */
	public function render_order_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );

		if ( ! $order ) {
			return;
		}

		wp_nonce_field( 'klientoora_card_save_order_meta', 'klientoora_card_order_meta_nonce' );
		?>
		<p class="form-field form-field-wide">
			<label for="klientoora_order_status"><?php echo esc_html__( 'סטטוס הזמנה', 'klientoora-card' ); ?></label>
			<?php $this->render_order_status_select( $order->get_meta( self::ORDER_STATUS_META_KEY ) ); ?>
		</p>
		<?php
	}

	/**
	 * Saves Klientoora order meta from the WooCommerce order edit screen.
	 *
	 * @param int             $order_id      Order ID.
	 * @param WP_Post|WC_Order $post_or_order Order post or order object.
	 *
	 * @return void
	 */
	public function save_order_meta_box( $order_id, $post_or_order = null ) {
		if ( ! isset( $_POST['klientoora_card_order_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['klientoora_card_order_meta_nonce'] ) ), 'klientoora_card_save_order_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['klientoora_order_status'] ) || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->save_order_klientoora_status( $order, wp_unslash( $_POST['klientoora_order_status'] ) );
	}

	/**
	 * Returns all users with active loyalty membership status.
	 *
	 * @return array<int, WP_User>
	 */
	private function get_active_members() {
		$user_query = new WP_User_Query(
			array(
				'fields'     => 'all',
				'meta_key'   => Klientoora_Card_Membership_Status::META_KEY,
				'meta_value' => 'active',
				'orderby'    => 'display_name',
				'order'      => 'ASC',
			)
		);

		return $user_query->get_results();
	}

	/**
	 * Counts active members who joined since the start of the current week.
	 *
	 * @param array<int, WP_User> $members Member user objects.
	 *
	 * @return int
	 */
	private function count_members_joined_this_week( $members ) {
		$now            = current_datetime();
		$start_of_week  = absint( get_option( 'start_of_week', 0 ) );
		$current_day    = absint( $now->format( 'w' ) );
		$days_from_week = ( $current_day - $start_of_week + 7 ) % 7;
		$week_start     = $now->modify( '-' . $days_from_week . ' days' )->setTime( 0, 0, 0 );
		$count          = 0;

		foreach ( $members as $member ) {
			if ( empty( $member->user_registered ) ) {
				continue;
			}

			$registered = new DateTimeImmutable( $member->user_registered, new DateTimeZone( 'UTC' ) );
			$registered = $registered->setTimezone( wp_timezone() );

			if ( $registered >= $week_start ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Returns display data for one member row.
	 *
	 * @param WP_User $member Member user object.
	 *
	 * @return array<string, mixed>
	 */
	private function get_member_table_row_data( $member ) {
		$user_id     = absint( $member->ID );
		$total_spent = $this->get_member_total_order_amount( $user_id );

		return array(
			'user_id'        => $user_id,
			'display_name'   => $member->display_name,
			'user_email'     => $member->user_email,
			'points'         => absint( get_user_meta( $user_id, 'klientoora_card_points', true ) ),
			'redeemed_total' => absint( get_user_meta( $user_id, 'loyalty_points_redeemed_total', true ) ),
			'orders_count'   => $this->get_member_orders_count( $user_id ),
			'total_spent'    => $this->format_member_order_amount( $total_spent ),
			'edit_url'       => get_edit_user_link( $user_id ),
			'registered'     => $member->user_registered
				? mysql2date( get_option( 'date_format' ), $member->user_registered )
				: '-',
		);
	}

	/**
	 * Renders one active member table row.
	 *
	 * @param WP_User $member Member user object.
	 *
	 * @return void
	 */
	private function render_member_table_row( $member ) {
		$row = $this->get_member_table_row_data( $member );
		?>
		<tr>
			<td><?php echo esc_html( (string) $row['user_id'] ); ?></td>
			<td><?php echo esc_html( $row['display_name'] ); ?></td>
			<td><a href="mailto:<?php echo esc_attr( $row['user_email'] ); ?>"><?php echo esc_html( $row['user_email'] ); ?></a></td>
			<td><?php echo esc_html( number_format_i18n( $row['points'] ) ); ?></td>
			<td><?php echo esc_html( number_format_i18n( $row['redeemed_total'] ) ); ?></td>
			<td><?php echo esc_html( number_format_i18n( $row['orders_count'] ) ); ?></td>
			<td><?php echo esc_html( $row['total_spent'] ); ?></td>
			<td><?php echo esc_html( $row['registered'] ); ?></td>
			<td>
				<?php if ( $row['edit_url'] && current_user_can( 'edit_user', $row['user_id'] ) ) : ?>
					<a class="button button-small" href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html__( 'Edit user', 'klientoora-card' ); ?></a>
				<?php else : ?>
					<span><?php echo esc_html__( 'No actions available', 'klientoora-card' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders one member table row for the standalone admin-main page.
	 *
	 * @param WP_User $member Member user object.
	 *
	 * @return void
	 */
	private function render_admin_main_member_table_row( $member ) {
		$row = $this->get_member_table_row_data( $member );
		?>
		<tr>
			<td><?php echo esc_html( (string) $row['user_id'] ); ?></td>
			<td><strong><?php echo esc_html( $row['display_name'] ); ?></strong></td>
			<td><a href="mailto:<?php echo esc_attr( $row['user_email'] ); ?>"><?php echo esc_html( $row['user_email'] ); ?></a></td>
			<td><?php echo esc_html( number_format_i18n( $row['points'] ) ); ?></td>
			<td><?php echo esc_html( number_format_i18n( $row['redeemed_total'] ) ); ?></td>
			<td><?php echo esc_html( number_format_i18n( $row['orders_count'] ) ); ?></td>
			<td><?php echo esc_html( $row['total_spent'] ); ?></td>
			<td><?php echo esc_html( $row['registered'] ); ?></td>
			<td>
				<?php if ( $row['edit_url'] && current_user_can( 'edit_user', $row['user_id'] ) ) : ?>
					<a class="klientoora-admin-main-members__action" href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html__( 'Edit user', 'klientoora-card' ); ?></a>
				<?php else : ?>
					<span><?php echo esc_html__( 'No actions available', 'klientoora-card' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Returns the number of WooCommerce orders for a member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	private function get_member_orders_count( $user_id ) {
		$user_id = absint( $user_id );

		if ( 0 === $user_id ) {
			return 0;
		}

		if ( function_exists( 'wc_get_customer_order_count' ) ) {
			return absint( wc_get_customer_order_count( $user_id ) );
		}

		$orders = $this->get_member_paid_order_ids( $user_id );

		return count( $orders );
	}

	/**
	 * Returns the total WooCommerce order amount for a member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return float
	 */
	private function get_member_total_order_amount( $user_id ) {
		$user_id = absint( $user_id );

		if ( 0 === $user_id ) {
			return 0.0;
		}

		if ( function_exists( 'wc_get_customer_total_spent' ) ) {
			return (float) wc_get_customer_total_spent( $user_id );
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return 0.0;
		}

		$total = 0.0;

		foreach ( $this->get_member_paid_order_ids( $user_id ) as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$total += (float) $order->get_total();
			}
		}

		return $total;
	}

	/**
	 * Returns paid WooCommerce order IDs for a member.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array<int, int>
	 */
	private function get_member_paid_order_ids( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$statuses = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
		$orders   = wc_get_orders(
			array(
				'customer_id' => absint( $user_id ),
				'limit'       => -1,
				'return'      => 'ids',
				'status'      => $statuses,
			)
		);

		return is_array( $orders ) ? array_map( 'absint', $orders ) : array();
	}

	/**
	 * Returns all WooCommerce orders for the management page.
	 *
	 * @return array<int, WC_Order>
	 */
	private function get_orders_for_management() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'limit'   => -1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'objects',
				'status'  => array( 'processing' ),
			)
		);

		return is_array( $orders ) ? $orders : array();
	}

	/**
	 * Renders a product management table row.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function render_product_management_row( $product ) {
		$product_id     = $product->get_id();
		$edit_url       = get_edit_post_link( $product_id );
		$image_id       = $product->get_image_id();
		$image          = $image_id ? wp_get_attachment_image( $image_id, 'thumbnail' ) : ( function_exists( 'wc_placeholder_img' ) ? wc_placeholder_img( 'thumbnail' ) : '' );
		$points_enabled = 'yes' === get_post_meta( $product_id, Klientoora_Card_Product_Redemption::META_ENABLED, true );
		$points_price   = Klientoora_Card_Product_Redemption::get_product_points_price( $product );
		$stock_status   = function_exists( 'wc_get_product_stock_status_options' )
			? wc_get_product_stock_status_options()
			: array();
		$stock_label    = isset( $stock_status[ $product->get_stock_status() ] )
			? $stock_status[ $product->get_stock_status() ]
			: $product->get_stock_status();
		?>
		<tr>
			<td>
				<div class="klientoora-card-product-management__product">
					<span class="klientoora-card-product-management__image"><?php echo wp_kses_post( $image ); ?></span>
					<strong>
						<?php if ( $edit_url ) : ?>
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $product->get_name() ); ?>
						<?php endif; ?>
					</strong>
				</div>
			</td>
			<td><?php echo wp_kses_post( $product->get_price_html() ? $product->get_price_html() : '-' ); ?></td>
			<td><?php echo esc_html( $product->get_status() ); ?></td>
			<td><?php echo esc_html( $stock_label ); ?></td>
			<td>
				<span class="klientoora-card-product-management__badge <?php echo esc_attr( $points_enabled ? 'is-enabled' : 'is-disabled' ); ?>">
					<?php echo esc_html( $points_enabled ? __( 'כן', 'klientoora-card' ) : __( 'לא', 'klientoora-card' ) ); ?>
				</span>
			</td>
			<td><?php echo esc_html( 0 < $points_price ? number_format_i18n( $points_price ) : '-' ); ?></td>
			<td>
				<?php if ( $edit_url ) : ?>
					<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Edit product', 'klientoora-card' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Groups orders by Klientoora order status.
	 *
	 * @param array<int, WC_Order> $orders Order objects.
	 *
	 * @return array<string, array<int, WC_Order>>
	 */
	private function group_orders_by_klientoora_status( $orders ) {
		$grouped_orders = array();

		foreach ( array_keys( $this->get_order_status_options() ) as $status_key ) {
			$grouped_orders[ $status_key ] = array();
		}

		foreach ( $orders as $order ) {
			$status = $this->sanitize_order_status_key( $order->get_meta( self::ORDER_STATUS_META_KEY ) );
			$grouped_orders[ $status ][] = $order;
		}

		return $grouped_orders;
	}

	/**
	 * Returns display data for one order management card.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array<string, mixed>
	 */
	private function get_order_management_card_data( $order ) {
		$order_id = $order->get_id();
		$customer = $order->get_formatted_billing_full_name();

		if ( '' === trim( $customer ) ) {
			$customer = $order->get_billing_email();
		}

		$current_status = $this->sanitize_order_status_key( $order->get_meta( self::ORDER_STATUS_META_KEY ) );
		$next_status    = $this->get_next_order_status_key( $current_status );

		return array(
			'order_id'          => $order_id,
			'date'              => $order->get_date_created()
				? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
				: '-',
			'customer'          => '' !== $customer ? $customer : '-',
			'edit_url'          => $order->get_edit_order_url(),
			'order_total'       => $order->get_formatted_order_total(),
			'wc_status_name'    => wc_get_order_status_name( $order->get_status() ),
			'current_status'    => $current_status,
			'next_status'       => $next_status,
			'next_status_label' => $next_status ? $this->get_order_status_options()[ $next_status ] : '',
		);
	}

	/**
	 * Renders an orders management card.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function render_order_management_card( $order ) {
		$row = $this->get_order_management_card_data( $order );
		?>
		<article class="klientoora-card-order-card">
			<div class="klientoora-card-order-card__header">
				<strong>
					<?php if ( $row['edit_url'] ) : ?>
						<a href="<?php echo esc_url( $row['edit_url'] ); ?>">#<?php echo esc_html( (string) $row['order_id'] ); ?></a>
					<?php else : ?>
						#<?php echo esc_html( (string) $row['order_id'] ); ?>
					<?php endif; ?>
				</strong>
				<span><?php echo esc_html( $row['wc_status_name'] ); ?></span>
			</div>

			<dl class="klientoora-card-order-card__details">
				<div>
					<dt><?php echo esc_html__( 'Date', 'klientoora-card' ); ?></dt>
					<dd><?php echo esc_html( $row['date'] ); ?></dd>
				</div>
			</dl>

			<div class="klientoora-card-order-card__actions">
				<?php if ( $row['next_status'] ) : ?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<?php wp_nonce_field( 'klientoora_card_advance_order_status' ); ?>
						<input type="hidden" name="action" value="klientoora_card_advance_order_status" />
						<input type="hidden" name="order_id" value="<?php echo esc_attr( $row['order_id'] ); ?>" />
						<?php
						submit_button(
							sprintf(
								/* translators: %s is the next Klientoora order status label. */
								__( 'העבר ל%s', 'klientoora-card' ),
								$row['next_status_label']
							),
							'primary small',
							'submit',
							false
						);
						?>
					</form>
				<?php endif; ?>

				<?php if ( $row['edit_url'] ) : ?>
					<a class="button button-small" href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html__( 'Edit order', 'klientoora-card' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Renders an order card for the standalone admin-main page.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function render_admin_main_order_card( $order ) {
		$row       = $this->get_order_management_card_data( $order );
		$dialog_id = 'klientoora-admin-main-order-dialog-' . $row['order_id'];
		?>
		<article
			class="klientoora-admin-main-order-card"
			role="button"
			tabindex="0"
			aria-controls="<?php echo esc_attr( $dialog_id ); ?>"
			data-klientoora-order-dialog-trigger="<?php echo esc_attr( $dialog_id ); ?>"
		>
			<div class="klientoora-admin-main-order-card__header">
				<strong>#<?php echo esc_html( (string) $row['order_id'] ); ?></strong>
				<span><?php echo esc_html( $row['wc_status_name'] ); ?></span>
			</div>

			<dl class="klientoora-admin-main-order-card__details">
				<div>
					<dt><?php echo esc_html__( 'Date', 'klientoora-card' ); ?></dt>
					<dd><?php echo esc_html( $row['date'] ); ?></dd>
				</div>
				<div>
					<dt><?php echo esc_html__( 'Customer', 'klientoora-card' ); ?></dt>
					<dd><?php echo esc_html( $row['customer'] ); ?></dd>
				</div>
				<div>
					<dt><?php echo esc_html__( 'Total', 'klientoora-card' ); ?></dt>
					<dd><?php echo wp_kses_post( $row['order_total'] ); ?></dd>
				</div>
			</dl>
		</article>
		<?php $this->render_admin_main_order_dialog( $order, $row, $dialog_id ); ?>
		<?php
	}

	/**
	 * Renders the standalone order details dialog.
	 *
	 * @param WC_Order             $order     Order object.
	 * @param array<string, mixed> $row       Order display data.
	 * @param string               $dialog_id Dialog element ID.
	 *
	 * @return void
	 */
	private function render_admin_main_order_dialog( $order, $row, $dialog_id ) {
		$heading_id     = $dialog_id . '-heading';
		$status_options = $this->get_order_status_options();
		$current_label  = isset( $status_options[ $row['current_status'] ] ) ? $status_options[ $row['current_status'] ] : $row['current_status'];
		$order_data     = $order->get_data();

		unset( $order_data['meta_data'] );
		?>
		<dialog class="klientoora-admin-main-order-dialog" id="<?php echo esc_attr( $dialog_id ); ?>" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
			<div class="klientoora-admin-main-order-dialog__surface">
				<header class="klientoora-admin-main-order-dialog__header">
					<div>
						<p><?php echo esc_html__( 'WooCommerce order', 'klientoora-card' ); ?></p>
						<h3 id="<?php echo esc_attr( $heading_id ); ?>">#<?php echo esc_html( (string) $row['order_id'] ); ?></h3>
					</div>
					<button class="klientoora-admin-main-order-dialog__close" type="button" data-klientoora-order-dialog-close aria-label="<?php echo esc_attr__( 'Close', 'klientoora-card' ); ?>">&times;</button>
				</header>

				<div class="klientoora-admin-main-order-dialog__body">
					<details class="klientoora-admin-main-order-dialog__section" open>
						<summary><?php echo esc_html__( 'Summary', 'klientoora-card' ); ?></summary>
						<?php
						$this->render_admin_main_order_field_list(
							array(
								'woocommerce_status' => $row['wc_status_name'],
								'klientoora_status'  => $current_label,
								'customer'           => $row['customer'],
								'total'              => wp_strip_all_tags( $row['order_total'] ),
								'date'               => $row['date'],
							)
						);
						?>
					</details>

					<?php $this->render_admin_main_order_field_group( __( 'WooCommerce fields', 'klientoora-card' ), $order_data ); ?>
					<?php $this->render_admin_main_order_items( $order ); ?>
					<?php $this->render_admin_main_order_meta( $order ); ?>
				</div>

				<footer class="klientoora-admin-main-order-dialog__footer">
					<?php if ( $row['next_status'] ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<?php wp_nonce_field( 'klientoora_card_advance_order_status' ); ?>
							<input type="hidden" name="action" value="klientoora_card_advance_order_status" />
							<input type="hidden" name="order_id" value="<?php echo esc_attr( $row['order_id'] ); ?>" />
							<input type="hidden" name="klientoora_orders_return" value="admin-main" />
							<button class="klientoora-admin-main-order-dialog__advance" type="submit">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s is the next Klientoora order status label. */
										__( 'העברה ל%s', 'klientoora-card' ),
										$row['next_status_label']
									)
								);
								?>
							</button>
						</form>
					<?php else : ?>
						<span class="klientoora-admin-main-order-dialog__complete"><?php echo esc_html__( 'הושלם', 'klientoora-card' ); ?></span>
					<?php endif; ?>
				</footer>
			</div>
		</dialog>
		<?php
	}

	/**
	 * Renders one group of order fields.
	 *
	 * @param string               $title  Group title.
	 * @param array<string, mixed> $fields Field values.
	 *
	 * @return void
	 */
	private function render_admin_main_order_field_group( $title, $fields ) {
		if ( empty( $fields ) ) {
			return;
		}
		?>
		<details class="klientoora-admin-main-order-dialog__section">
			<summary><?php echo esc_html( $title ); ?></summary>
			<?php $this->render_admin_main_order_field_list( $fields ); ?>
		</details>
		<?php
	}

	/**
	 * Renders order field values as a definition list.
	 *
	 * @param array<string, mixed> $fields Field values.
	 *
	 * @return void
	 */
	private function render_admin_main_order_field_list( $fields ) {
		?>
		<dl class="klientoora-admin-main-order-dialog__fields">
			<?php foreach ( $fields as $field_key => $field_value ) : ?>
				<div>
					<dt><?php echo esc_html( $this->format_admin_main_order_field_key( $field_key ) ); ?></dt>
					<dd><?php echo wp_kses_post( $this->format_admin_main_order_field_value( $field_value ) ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php
	}

	/**
	 * Renders WooCommerce order items.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function render_admin_main_order_items( $order ) {
		$item_types = array(
			'line_item' => __( 'Line items', 'klientoora-card' ),
			'fee'       => __( 'Fees', 'klientoora-card' ),
			'shipping'  => __( 'Shipping', 'klientoora-card' ),
			'coupon'    => __( 'Coupons', 'klientoora-card' ),
			'tax'       => __( 'Taxes', 'klientoora-card' ),
		);
		$item_groups = array();

		foreach ( $item_types as $item_type => $item_label ) {
			$items = $order->get_items( $item_type );

			if ( ! empty( $items ) ) {
				$item_groups[ $item_label ] = $items;
			}
		}

		if ( empty( $item_groups ) ) {
			return;
		}
		?>
		<details class="klientoora-admin-main-order-dialog__section">
			<summary><?php echo esc_html__( 'Order items', 'klientoora-card' ); ?></summary>
			<div class="klientoora-admin-main-order-dialog__item-groups">
				<?php foreach ( $item_groups as $item_label => $items ) : ?>
					<details class="klientoora-admin-main-order-dialog__item-group">
						<summary><?php echo esc_html( $item_label ); ?></summary>
						<?php foreach ( $items as $item ) : ?>
							<details class="klientoora-admin-main-order-dialog__item">
								<summary><?php echo esc_html( method_exists( $item, 'get_name' ) ? $item->get_name() : __( 'Item', 'klientoora-card' ) ); ?></summary>
								<?php $this->render_admin_main_order_field_list( method_exists( $item, 'get_data' ) ? $item->get_data() : array() ); ?>
							</details>
						<?php endforeach; ?>
					</details>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
	}

	/**
	 * Renders WooCommerce order meta fields.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function render_admin_main_order_meta( $order ) {
		$meta_data = $order->get_meta_data();

		if ( empty( $meta_data ) ) {
			return;
		}
		?>
		<details class="klientoora-admin-main-order-dialog__section">
			<summary><?php echo esc_html__( 'Meta data', 'klientoora-card' ); ?></summary>
			<div class="klientoora-admin-main-order-dialog__item-groups">
				<?php foreach ( $meta_data as $meta ) : ?>
					<?php $meta_fields = method_exists( $meta, 'get_data' ) ? $meta->get_data() : array(); ?>
					<details class="klientoora-admin-main-order-dialog__item">
						<summary><?php echo esc_html( isset( $meta_fields['key'] ) ? $meta_fields['key'] : __( 'Meta field', 'klientoora-card' ) ); ?></summary>
						<?php $this->render_admin_main_order_field_list( $meta_fields ); ?>
					</details>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
	}

	/**
	 * Formats a WooCommerce field key for display.
	 *
	 * @param mixed $field_key Field key.
	 *
	 * @return string
	 */
	private function format_admin_main_order_field_key( $field_key ) {
		return ucwords( str_replace( '_', ' ', (string) $field_key ) );
	}

	/**
	 * Formats a WooCommerce order field value for display.
	 *
	 * @param mixed $field_value Field value.
	 *
	 * @return string
	 */
	private function format_admin_main_order_field_value( $field_value ) {
		if ( $field_value instanceof DateTimeInterface ) {
			if ( method_exists( $field_value, 'date_i18n' ) ) {
				return esc_html( $field_value->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
			}

			return esc_html( $field_value->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
		}

		if ( is_bool( $field_value ) ) {
			return esc_html( $field_value ? __( 'Yes', 'klientoora-card' ) : __( 'No', 'klientoora-card' ) );
		}

		if ( is_array( $field_value ) ) {
			if ( empty( $field_value ) ) {
				return '-';
			}

			$output = '<dl class="klientoora-admin-main-order-dialog__nested-fields">';

			foreach ( $field_value as $nested_key => $nested_value ) {
				$output .= '<div><dt>' . esc_html( $this->format_admin_main_order_field_key( $nested_key ) ) . '</dt><dd>' . $this->format_admin_main_order_field_value( $nested_value ) . '</dd></div>';
			}

			$output .= '</dl>';

			return $output;
		}

		if ( is_object( $field_value ) ) {
			if ( method_exists( $field_value, 'get_data' ) ) {
				return $this->format_admin_main_order_field_value( $field_value->get_data() );
			}

			if ( method_exists( $field_value, '__toString' ) ) {
				return esc_html( (string) $field_value );
			}

			return esc_html( get_class( $field_value ) );
		}

		if ( null === $field_value || '' === $field_value ) {
			return '-';
		}

		return esc_html( (string) $field_value );
	}

	/**
	 * Renders an orders management table row.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function render_order_management_row( $order ) {
		$order_id = $order->get_id();
		$date     = $order->get_date_created()
			? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
			: '-';
		$customer = $order->get_formatted_billing_full_name();

		if ( '' === trim( $customer ) ) {
			$customer = $order->get_billing_email();
		}

		$edit_url = $order->get_edit_order_url();
		?>
		<tr>
			<td>
				<strong>
					<?php if ( $edit_url ) : ?>
						<a href="<?php echo esc_url( $edit_url ); ?>">#<?php echo esc_html( (string) $order_id ); ?></a>
					<?php else : ?>
						#<?php echo esc_html( (string) $order_id ); ?>
					<?php endif; ?>
				</strong>
			</td>
			<td><?php echo esc_html( $date ); ?></td>
			<td><?php echo esc_html( '' !== $customer ? $customer : '-' ); ?></td>
			<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
			<td>
				<?php $this->render_order_status_select( $order->get_meta( self::ORDER_STATUS_META_KEY ), 'klientoora_order_status[' . $order_id . ']' ); ?>
			</td>
			<td>
				<?php if ( $edit_url ) : ?>
					<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Edit order', 'klientoora-card' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders an order status select field.
	 *
	 * @param string $current_status Current status key.
	 * @param string $field_name     Select field name.
	 *
	 * @return void
	 */
	private function render_order_status_select( $current_status, $field_name = 'klientoora_order_status' ) {
		$current_status = $this->sanitize_order_status_key( $current_status );
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>">
			<?php foreach ( $this->get_order_status_options() as $status_key => $status_label ) : ?>
				<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_status, $status_key ); ?>>
					<?php echo esc_html( $status_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Returns Klientoora order status options.
	 *
	 * @return array<string, string>
	 */
	private function get_order_status_options() {
		return $this->get_primary_order_status_options() + $this->get_fulfillment_order_status_options();
	}

	/**
	 * Returns primary Klientoora order status options.
	 *
	 * @return array<string, string>
	 */
	private function get_primary_order_status_options() {
		return array(
			'new'       => __( 'חדשות', 'klientoora-card' ),
			'preparing' => __( 'בהכנה', 'klientoora-card' ),
			'ready'     => __( 'הזמנה מוכנה', 'klientoora-card' ),
		);
	}

	/**
	 * Returns fulfillment Klientoora order status options.
	 *
	 * @return array<string, string>
	 */
	private function get_fulfillment_order_status_options() {
		return array(
			'waiting_shipping' => __( 'ממתין למשלוח', 'klientoora-card' ),
			'waiting_pickup'   => __( 'ממתין לאיסוף', 'klientoora-card' ),
			'completed'        => __( 'הושלם', 'klientoora-card' ),
		);
	}

	/**
	 * Sanitizes Klientoora order status values.
	 *
	 * @param mixed $status Status value.
	 *
	 * @return string
	 */
	private function sanitize_order_status_key( $status ) {
		$status = sanitize_key( (string) $status );
		$options = $this->get_order_status_options();

		return isset( $options[ $status ] ) ? $status : 'new';
	}

	/**
	 * Returns the next Klientoora order status key.
	 *
	 * @param string $current_status Current status key.
	 *
	 * @return string
	 */
	private function get_next_order_status_key( $current_status ) {
		$status_keys = array_keys( $this->get_order_status_options() );
		$current_status = $this->sanitize_order_status_key( $current_status );
		$current_index  = array_search( $current_status, $status_keys, true );

		if ( false === $current_index || ! isset( $status_keys[ $current_index + 1 ] ) ) {
			return '';
		}

		return $status_keys[ $current_index + 1 ];
	}

	/**
	 * Saves the Klientoora status on an order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param mixed    $status Submitted status.
	 *
	 * @return void
	 */
	private function save_order_klientoora_status( $order, $status ) {
		$order->update_meta_data( self::ORDER_STATUS_META_KEY, $this->sanitize_order_status_key( $status ) );
		$order->save();
	}

	/**
	 * Renders notices for the orders management page.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function render_orders_management_notice( $notice ) {
		$messages = $this->get_orders_management_notice_messages();

		if ( empty( $messages[ $notice ] ) ) {
			return;
		}

		$type = 'woocommerce_inactive' === $notice || 'not_found' === $notice ? 'error' : 'success';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $messages[ $notice ] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns order management notice messages.
	 *
	 * @return array<string, string>
	 */
	private function get_orders_management_notice_messages() {
		return array(
			'saved'                => __( 'Order statuses saved.', 'klientoora-card' ),
			'woocommerce_inactive' => __( 'WooCommerce is not active.', 'klientoora-card' ),
			'not_found'            => __( 'Order not found.', 'klientoora-card' ),
		);
	}

	/**
	 * Redirects back to the orders management page.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function redirect_orders_management_page( $notice ) {
		$return_to = isset( $_POST['klientoora_orders_return'] ) ? sanitize_key( wp_unslash( $_POST['klientoora_orders_return'] ) ) : '';

		if ( 'admin-main' === $return_to ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'klientoora_card_orders_notice' => $notice,
					),
					home_url( '/admin-main/' )
				) . '#orders'
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                         => 'loyalty-club-orders-management',
					'klientoora_card_orders_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Formats a member order total for display.
	 *
	 * @param float $amount Order amount.
	 *
	 * @return string
	 */
	private function format_member_order_amount( $amount ) {
		if ( function_exists( 'wc_price' ) ) {
			return wp_strip_all_tags( wc_price( $amount ) );
		}

		return number_format_i18n( (float) $amount, 2 );
	}

	/**
	 * Renders the sales challenges admin page.
	 *
	 * @return void
	 */
	public function render_challenges_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$goal      = absint( get_option( 'klientoora_card_order_challenge_goal', 5 ) );
		$goal      = max( 1, $goal );
		$coupon_id = absint( get_option( 'klientoora_card_order_challenge_coupon_id', 0 ) );
		$coupons   = $this->get_challenge_coupon_options();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'challenges', 'klientoora-card' ); ?></h1>

			<div class="klientoora-card-challenges">
				<div class="klientoora-card-dashboard__card klientoora-card-challenge-card">
					<h2><?php echo esc_html__( 'אתגר מספר הזמנות', 'klientoora-card' ); ?></h2>
					<p><?php echo esc_html__( 'הגדרת יעד הזמנות והקופון שיופיע כפרס בסוף המד בפופאפ המועדון.', 'klientoora-card' ); ?></p>

					<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
						<?php settings_fields( 'klientoora_card_challenges' ); ?>

						<div class="klientoora-card-challenge-form-grid">
							<div class="klientoora-card-coupon-field">
								<label for="klientoora_card_order_challenge_goal"><?php echo esc_html__( 'מספר הזמנות ליעד', 'klientoora-card' ); ?></label>
								<input
									type="number"
									id="klientoora_card_order_challenge_goal"
									name="klientoora_card_order_challenge_goal"
									value="<?php echo esc_attr( $goal ); ?>"
									min="1"
									step="1"
									required
								/>
							</div>

							<div class="klientoora-card-coupon-field">
								<label for="klientoora_card_order_challenge_coupon_id"><?php echo esc_html__( 'קופון הפרס', 'klientoora-card' ); ?></label>
								<select
									id="klientoora_card_order_challenge_coupon_id"
									name="klientoora_card_order_challenge_coupon_id"
									<?php disabled( ! $this->is_woocommerce_active() ); ?>
								>
									<option value="0"><?php echo esc_html__( 'משלוח חינם', 'klientoora-card' ); ?></option>
									<?php foreach ( $coupons as $coupon ) : ?>
										<option value="<?php echo esc_attr( $coupon->get_id() ); ?>" <?php selected( $coupon_id, $coupon->get_id() ); ?>>
											<?php echo esc_html( $this->get_coupon_option_label( $coupon ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php echo esc_html__( 'אם לא נבחר קופון, בפופאפ יוצג הטקסט משלוח חינם.', 'klientoora-card' ); ?>
								</p>
							</div>
						</div>

						<?php if ( ! $this->is_woocommerce_active() ) : ?>
							<div class="notice notice-warning inline">
								<p><?php echo esc_html__( 'WooCommerce is not active, so coupon selection is disabled.', 'klientoora-card' ); ?></p>
							</div>
						<?php endif; ?>

						<?php submit_button( __( 'Save challenge', 'klientoora-card' ) ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the product point redemption admin page.
	 *
	 * @return void
	 */
	public function render_product_redemptions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice  = isset( $_GET['klientoora_card_product_redemption_notice'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_product_redemption_notice'] ) ) : '';
		$products = class_exists( 'Klientoora_Card_Product_Redemption' ) ? Klientoora_Card_Product_Redemption::get_admin_products() : array();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'מימוש נקודות', 'klientoora-card' ); ?></h1>

			<?php $this->render_product_redemption_notice( $notice ); ?>

			<?php if ( ! function_exists( 'wc_get_products' ) ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'WooCommerce is not active. Product point redemption is disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
				</div>
			<?php else : ?>
				<div class="klientoora-card-product-redemptions">
					<div class="klientoora-card-dashboard__card">
						<h2><?php echo esc_html__( 'מוצרים למימוש בנקודות', 'klientoora-card' ); ?></h2>
						<p><?php echo esc_html__( 'בחרו אילו מוצרים יופיעו באזור המועדון וכמה נקודות נדרשות למימוש כל מוצר.', 'klientoora-card' ); ?></p>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<?php wp_nonce_field( 'klientoora_card_save_product_redemptions' ); ?>
							<input type="hidden" name="action" value="klientoora_card_save_product_redemptions" />

							<table class="widefat striped klientoora-card-product-redemptions__table">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'ניתן לרכישה בנקודות', 'klientoora-card' ); ?></th>
										<th><?php echo esc_html__( 'מוצר', 'klientoora-card' ); ?></th>
										<th><?php echo esc_html__( 'מחיר רגיל', 'klientoora-card' ); ?></th>
										<th><?php echo esc_html__( 'שווי נקודות', 'klientoora-card' ); ?></th>
										<th><?php echo esc_html__( 'סטטוס', 'klientoora-card' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $products ) ) : ?>
										<tr>
											<td colspan="5"><?php echo esc_html__( 'No products found.', 'klientoora-card' ); ?></td>
										</tr>
									<?php else : ?>
										<?php foreach ( $products as $product ) : ?>
											<?php $this->render_product_redemption_row( $product ); ?>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>

							<?php submit_button( __( 'Save product redemptions', 'klientoora-card' ) ); ?>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handles saving point redemption settings for products.
	 *
	 * @return void
	 */
	public function handle_save_product_redemptions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_save_product_redemptions' );

		if ( ! function_exists( 'wc_get_product' ) ) {
			$this->redirect_product_redemptions_page( 'woocommerce_inactive' );
		}

		$product_ids = isset( $_POST['product_ids'] ) && is_array( $_POST['product_ids'] )
			? array_map( 'absint', wp_unslash( $_POST['product_ids'] ) )
			: array();
		$enabled_ids = isset( $_POST['redemption_enabled'] ) && is_array( $_POST['redemption_enabled'] )
			? array_map( 'absint', wp_unslash( $_POST['redemption_enabled'] ) )
			: array();
		$points_map  = isset( $_POST['redemption_points'] ) && is_array( $_POST['redemption_points'] )
			? wp_unslash( $_POST['redemption_points'] )
			: array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$points  = isset( $points_map[ $product_id ] ) ? absint( $points_map[ $product_id ] ) : 0;
			$enabled = in_array( $product_id, $enabled_ids, true ) && 0 < $points ? 'yes' : 'no';

			$product->update_meta_data( Klientoora_Card_Product_Redemption::META_ENABLED, $enabled );
			$product->update_meta_data( Klientoora_Card_Product_Redemption::META_POINTS, $points );
			$product->save();
		}

		$this->redirect_product_redemptions_page( 'saved' );
	}

	/**
	 * Renders a product redemption table row.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function render_product_redemption_row( $product ) {
		$product_id   = $product->get_id();
		$enabled      = 'yes' === get_post_meta( $product_id, Klientoora_Card_Product_Redemption::META_ENABLED, true );
		$points_price = Klientoora_Card_Product_Redemption::get_product_points_price( $product );
		$edit_url     = get_edit_post_link( $product_id );
		?>
		<tr>
			<td>
				<input type="hidden" name="product_ids[]" value="<?php echo esc_attr( $product_id ); ?>" />
				<label>
					<input type="checkbox" name="redemption_enabled[]" value="<?php echo esc_attr( $product_id ); ?>" <?php checked( $enabled ); ?> />
					<?php echo esc_html__( 'פעיל', 'klientoora-card' ); ?>
				</label>
			</td>
			<td>
				<strong>
					<?php if ( $edit_url ) : ?>
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $product->get_name() ); ?>
					<?php endif; ?>
				</strong>
			</td>
			<td><?php echo wp_kses_post( $product->get_price_html() ? $product->get_price_html() : '-' ); ?></td>
			<td>
				<input
					type="number"
					name="redemption_points[<?php echo esc_attr( $product_id ); ?>]"
					value="<?php echo esc_attr( $points_price ); ?>"
					min="0"
					step="1"
					class="small-text"
				/>
			</td>
			<td><?php echo esc_html( $product->get_status() ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Renders notices for the product redemption page.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function render_product_redemption_notice( $notice ) {
		$messages = array(
			'saved'                => __( 'Product redemptions saved.', 'klientoora-card' ),
			'woocommerce_inactive' => __( 'WooCommerce is not active.', 'klientoora-card' ),
		);

		if ( empty( $messages[ $notice ] ) ) {
			return;
		}

		$type = 'woocommerce_inactive' === $notice ? 'error' : 'success';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $messages[ $notice ] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirects back to the product redemption admin page.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function redirect_product_redemptions_page( $notice ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                                      => 'loyalty-club-product-redemptions',
					'klientoora_card_product_redemption_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders the Club Coupons admin page.
	 *
	 * @return void
	 */
	public function render_coupons_page() {
		if ( ! $this->current_user_can_manage_coupons() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		$notice    = isset( $_GET['klientoora_card_coupon_notice'] ) ? sanitize_key( wp_unslash( $_GET['klientoora_card_coupon_notice'] ) ) : '';
		$coupon_id = isset( $_GET['coupon_id'] ) ? absint( $_GET['coupon_id'] ) : 0;
		$coupon    = $coupon_id && $this->is_woocommerce_active() ? $this->get_loyalty_coupon( $coupon_id ) : null;

		if ( $coupon_id && ! $coupon ) {
			$notice = 'not_found';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Club Coupons', 'klientoora-card' ); ?></h1>

			<?php $this->render_coupon_notice( $notice ); ?>

			<?php if ( ! $this->is_woocommerce_active() ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'WooCommerce is not active. Club Coupons are disabled until WooCommerce is available.', 'klientoora-card' ); ?></p>
				</div>
			<?php else : ?>
				<div class="klientoora-card-coupons">
					<?php $this->render_coupon_templates(); ?>
					<?php $this->render_coupon_form( $coupon ); ?>
					<?php $this->render_coupons_table(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handles creating or updating a native WooCommerce coupon.
	 *
	 * @return void
	 */
	public function handle_save_coupon() {
		if ( ! $this->current_user_can_manage_coupons() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_save_coupon' );

		if ( ! $this->is_woocommerce_active() ) {
			$this->redirect_coupons_page( 'woocommerce_inactive' );
		}

		$coupon_id = isset( $_POST['coupon_id'] ) ? absint( wp_unslash( $_POST['coupon_id'] ) ) : 0;
		$code      = isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) ) : '';

		if ( '' === $code ) {
			$this->redirect_coupons_page( 'missing_code', $coupon_id );
		}

		$existing_coupon_id = wc_get_coupon_id_by_code( $code );

		if ( $existing_coupon_id && $existing_coupon_id !== $coupon_id ) {
			$this->redirect_coupons_page( 'duplicate_code', $coupon_id );
		}

		$coupon = $coupon_id ? $this->get_loyalty_coupon( $coupon_id ) : new WC_Coupon();

		if ( ! $coupon ) {
			$this->redirect_coupons_page( 'not_found' );
		}

		try {
			$this->populate_coupon_from_request( $coupon, $code );
			$coupon->save();
		} catch ( Exception $exception ) {
			unset( $exception );
			$this->redirect_coupons_page( 'save_failed', $coupon_id );
		}

		$this->redirect_coupons_page( $coupon_id ? 'updated' : 'created' );
	}

	/**
	 * Handles moving a loyalty coupon to the trash.
	 *
	 * @return void
	 */
	public function handle_delete_coupon() {
		if ( ! $this->current_user_can_manage_coupons() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_delete_coupon' );

		if ( ! $this->is_woocommerce_active() ) {
			$this->redirect_coupons_page( 'woocommerce_inactive' );
		}

		$coupon_id = isset( $_POST['coupon_id'] ) ? absint( wp_unslash( $_POST['coupon_id'] ) ) : 0;
		$coupon    = $this->get_loyalty_coupon( $coupon_id );

		if ( ! $coupon ) {
			$this->redirect_coupons_page( 'not_found' );
		}

		wp_trash_post( $coupon_id );
		$this->redirect_coupons_page( 'deleted' );
	}

	/**
	 * Handles updating the synced fixed member discount percentage.
	 *
	 * @return void
	 */
	public function handle_save_member_discount() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'klientoora-card' ) );
		}

		check_admin_referer( 'klientoora_card_save_member_discount' );

		$percentage = isset( $_POST['loyalty_member_discount_percentage'] )
			? $this->sanitize_percentage( wp_unslash( $_POST['loyalty_member_discount_percentage'] ) )
			: 0;

		update_option( 'loyalty_member_discount_percentage', $percentage );
		$this->redirect_coupons_page( 'member_discount_saved' );
	}

	/**
	 * Checks whether the current admin can manage club coupons.
	 *
	 * @return bool
	 */
	private function current_user_can_manage_coupons() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Checks whether WooCommerce coupon APIs are available.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WC_Coupon' ) && function_exists( 'wc_get_coupon_id_by_code' );
	}

	/**
	 * Renders coupon action notices.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return void
	 */
	private function render_coupon_notice( $notice ) {
		if ( '' === $notice ) {
			return;
		}

		$messages = $this->get_coupon_notice_messages();

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		$type = in_array( $notice, array( 'created', 'updated', 'deleted', 'member_discount_saved' ), true ) ? 'success' : 'error';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $messages[ $notice ] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns coupon notice messages.
	 *
	 * @return array<string, string>
	 */
	private function get_coupon_notice_messages() {
		return array(
			'created'               => __( 'Coupon created.', 'klientoora-card' ),
			'updated'               => __( 'Coupon updated.', 'klientoora-card' ),
			'deleted'               => __( 'Coupon moved to trash.', 'klientoora-card' ),
			'member_discount_saved' => __( 'Fixed member discount saved.', 'klientoora-card' ),
			'missing_code'          => __( 'Coupon code is required.', 'klientoora-card' ),
			'duplicate_code'        => __( 'A coupon with this code already exists.', 'klientoora-card' ),
			'save_failed'           => __( 'Coupon could not be saved. Please check the form fields and try again.', 'klientoora-card' ),
			'not_found'             => __( 'Coupon was not found.', 'klientoora-card' ),
			'woocommerce_inactive'  => __( 'WooCommerce is not active.', 'klientoora-card' ),
		);
	}

	/**
	 * Renders coupon template cards.
	 *
	 * @return void
	 */
	private function render_coupon_templates() {
		?>
		<div class="klientoora-card-dashboard__card">
			<h2><?php echo esc_html__( 'Coupon Templates', 'klientoora-card' ); ?></h2>
			<div class="klientoora-card-coupon-templates" data-klientoora-coupon-templates>
				<?php foreach ( $this->get_coupon_templates() as $template ) : ?>
					<button
						type="button"
						class="klientoora-card-coupon-template"
						aria-pressed="false"
						data-klientoora-coupon-template
						data-template-coupon-code="<?php echo esc_attr( $template['coupon_code'] ); ?>"
						data-template-description="<?php echo esc_attr( $template['description'] ); ?>"
						data-template-discount-type="<?php echo esc_attr( $template['discount_type'] ); ?>"
						data-template-amount="<?php echo esc_attr( $template['amount'] ); ?>"
						data-template-free-shipping="<?php echo esc_attr( $template['free_shipping'] ); ?>"
						data-template-expiry-date="<?php echo esc_attr( $template['expiry_date'] ); ?>"
						data-template-minimum-spend="<?php echo esc_attr( $template['minimum_spend'] ); ?>"
						data-template-maximum-spend="<?php echo esc_attr( $template['maximum_spend'] ); ?>"
						data-template-usage-limit="<?php echo esc_attr( $template['usage_limit'] ); ?>"
						data-template-usage-limit-per-user="<?php echo esc_attr( $template['usage_limit_per_user'] ); ?>"
						data-template-members-only="<?php echo esc_attr( $template['members_only'] ); ?>"
						data-template-show-in-popup="<?php echo esc_attr( $template['show_in_popup'] ); ?>"
					>
						<span class="klientoora-card-coupon-template__selected"><?php echo esc_html__( 'נבחר', 'klientoora-card' ); ?></span>
						<strong><?php echo esc_html( $template['title'] ); ?></strong>
						<span><?php echo esc_html( $template['summary'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the create/edit coupon form.
	 *
	 * @param WC_Coupon|null $coupon Coupon being edited.
	 *
	 * @return void
	 */
	private function render_coupon_form( $coupon = null ) {
		$is_edit                = $coupon && $coupon->get_id();
		$coupon_id              = $is_edit ? $coupon->get_id() : 0;
		$expiry_date            = $is_edit && $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '';
		$discount_type          = $is_edit ? $coupon->get_discount_type() : 'fixed_cart';
		$allowed_discount_types = $this->get_allowed_coupon_discount_types();
		?>
		<div class="klientoora-card-dashboard__card klientoora-card-coupon-form-card">
			<h2>
				<?php echo esc_html( $is_edit ? __( 'Edit Coupon', 'klientoora-card' ) : __( 'Create New Coupon', 'klientoora-card' ) ); ?>
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( 'klientoora_card_save_coupon' ); ?>
				<input type="hidden" name="action" value="klientoora_card_save_coupon" />
				<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon_id ); ?>" />

				<div class="klientoora-card-coupon-form-grid">
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_code"><?php echo esc_html__( 'Coupon code', 'klientoora-card' ); ?></label>
						<input type="text" id="klientoora_coupon_code" name="coupon_code" value="<?php echo esc_attr( $is_edit ? $coupon->get_code() : '' ); ?>" required />
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_discount_type"><?php echo esc_html__( 'Discount type', 'klientoora-card' ); ?></label>
						<select id="klientoora_coupon_discount_type" name="discount_type">
							<?php foreach ( $allowed_discount_types as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $discount_type, $type ); ?>>
									<?php echo esc_html( $type ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_amount"><?php echo esc_html__( 'Coupon amount', 'klientoora-card' ); ?></label>
						<input type="number" id="klientoora_coupon_amount" name="amount" value="<?php echo esc_attr( $is_edit ? $coupon->get_amount() : '' ); ?>" min="0" step="0.01" />
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_expiry_date"><?php echo esc_html__( 'Expiry date', 'klientoora-card' ); ?></label>
						<input type="date" id="klientoora_coupon_expiry_date" name="expiry_date" value="<?php echo esc_attr( $expiry_date ); ?>" />
					</div>
					<div class="klientoora-card-coupon-field klientoora-card-coupon-field--wide">
						<label for="klientoora_coupon_description"><?php echo esc_html__( 'Description', 'klientoora-card' ); ?></label>
						<textarea id="klientoora_coupon_description" name="description" rows="2"><?php echo esc_textarea( $is_edit ? $coupon->get_description() : '' ); ?></textarea>
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_minimum_spend"><?php echo esc_html__( 'Minimum spend', 'klientoora-card' ); ?></label>
						<input type="number" id="klientoora_coupon_minimum_spend" name="minimum_spend" value="<?php echo esc_attr( $is_edit ? $coupon->get_minimum_amount() : '' ); ?>" min="0" step="0.01" />
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_maximum_spend"><?php echo esc_html__( 'Maximum spend', 'klientoora-card' ); ?></label>
						<input type="number" id="klientoora_coupon_maximum_spend" name="maximum_spend" value="<?php echo esc_attr( $is_edit ? $coupon->get_maximum_amount() : '' ); ?>" min="0" step="0.01" />
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_usage_limit"><?php echo esc_html__( 'Usage limit', 'klientoora-card' ); ?></label>
						<input type="number" id="klientoora_coupon_usage_limit" name="usage_limit" value="<?php echo esc_attr( $is_edit ? $coupon->get_usage_limit() : '' ); ?>" min="0" step="1" />
					</div>
					<div class="klientoora-card-coupon-field">
						<label for="klientoora_coupon_usage_limit_per_user"><?php echo esc_html__( 'Usage limit per user', 'klientoora-card' ); ?></label>
						<input type="number" id="klientoora_coupon_usage_limit_per_user" name="usage_limit_per_user" value="<?php echo esc_attr( $is_edit ? $coupon->get_usage_limit_per_user() : '' ); ?>" min="0" step="1" />
					</div>
				</div>

				<div class="klientoora-card-coupon-toggles">
					<label><input type="checkbox" name="free_shipping" value="yes" <?php checked( $is_edit && $coupon->get_free_shipping() ); ?> /> <?php echo esc_html__( 'Allow free shipping', 'klientoora-card' ); ?></label>
					<label><input type="checkbox" name="members_only" value="yes" <?php checked( $is_edit && 'yes' === $coupon->get_meta( '_loyalty_members_only' ) ); ?> /> <?php echo esc_html__( 'Members only', 'klientoora-card' ); ?></label>
					<label><input type="checkbox" name="show_in_popup" value="yes" <?php checked( $is_edit && 'yes' === $coupon->get_meta( '_loyalty_show_in_popup' ) ); ?> /> <?php echo esc_html__( 'Show in loyalty popup', 'klientoora-card' ); ?></label>
				</div>

				<div class="klientoora-card-coupon-form-actions">
					<?php submit_button( $is_edit ? __( 'Update coupon', 'klientoora-card' ) : __( 'Create coupon', 'klientoora-card' ), 'primary', 'submit', false ); ?>

					<?php if ( $is_edit ) : ?>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=loyalty-club-coupons' ) ); ?>"><?php echo esc_html__( 'Cancel edit', 'klientoora-card' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the loyalty coupons table.
	 *
	 * @return void
	 */
	private function render_coupons_table() {
		$coupons = $this->get_loyalty_coupons();
		?>
		<div class="klientoora-card-dashboard__card">
			<h2><?php echo esc_html__( 'Existing Club Coupons', 'klientoora-card' ); ?></h2>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Coupon code', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Discount type', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Amount', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Free shipping', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Expiry date', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Usage limit', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Status', 'klientoora-card' ); ?></th>
						<th><?php echo esc_html__( 'Actions', 'klientoora-card' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $coupons ) ) : ?>
						<tr>
							<td colspan="8"><?php echo esc_html__( 'No club coupons yet.', 'klientoora-card' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $coupons as $coupon ) : ?>
							<?php $this->render_coupon_table_row( $coupon ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders a coupon table row.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return void
	 */
	private function render_coupon_table_row( $coupon ) {
		$expiry_date = $coupon->get_date_expires()
			? $coupon->get_date_expires()->date_i18n( get_option( 'date_format' ) )
			: __( 'No expiry', 'klientoora-card' );
		$usage_limit = $coupon->get_usage_limit() ? $coupon->get_usage_limit() : __( 'Unlimited', 'klientoora-card' );
		$edit_url    = add_query_arg(
			array(
				'page'      => 'loyalty-club-coupons',
				'coupon_id' => $coupon->get_id(),
			),
			admin_url( 'admin.php' )
		);
		?>
		<tr>
			<td><code><?php echo esc_html( $coupon->get_code() ); ?></code></td>
			<td><?php echo esc_html( $coupon->get_discount_type() ); ?></td>
			<td><?php echo esc_html( $coupon->get_amount() ); ?></td>
			<td><?php echo esc_html( $coupon->get_free_shipping() ? __( 'Yes', 'klientoora-card' ) : __( 'No', 'klientoora-card' ) ); ?></td>
			<td><?php echo esc_html( $expiry_date ); ?></td>
			<td><?php echo esc_html( $usage_limit ); ?></td>
			<td><?php echo esc_html( $coupon->get_status() ); ?></td>
			<td class="klientoora-card-coupon-actions">
				<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Edit', 'klientoora-card' ); ?></a>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<?php wp_nonce_field( 'klientoora_card_delete_coupon' ); ?>
					<input type="hidden" name="action" value="klientoora_card_delete_coupon" />
					<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon->get_id() ); ?>" />
					<?php submit_button( __( 'Delete', 'klientoora-card' ), 'delete button-small', 'submit', false ); ?>
				</form>
			</td>
		</tr>
		<?php
	}

	/**
	 * Populates a WooCommerce coupon from the submitted admin form.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 * @param string    $code   Sanitized coupon code.
	 *
	 * @return void
	 */
	private function populate_coupon_from_request( $coupon, $code ) {
		$discount_type = isset( $_POST['discount_type'] ) ? sanitize_key( wp_unslash( $_POST['discount_type'] ) ) : 'fixed_cart';

		if ( ! in_array( $discount_type, $this->get_allowed_coupon_discount_types(), true ) ) {
			$discount_type = 'fixed_cart';
		}

		$coupon->set_code( $code );
		$coupon->set_status( 'publish' );
		$coupon->set_description( isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '' );
		$coupon->set_discount_type( $discount_type );
		$coupon->set_amount( $this->sanitize_coupon_decimal( 'amount' ) );
		$coupon->set_free_shipping( isset( $_POST['free_shipping'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['free_shipping'] ) ) );
		$coupon->set_date_expires( $this->sanitize_coupon_date( 'expiry_date' ) );
		$coupon->set_minimum_amount( $this->sanitize_coupon_decimal( 'minimum_spend' ) );
		$coupon->set_maximum_amount( $this->sanitize_coupon_decimal( 'maximum_spend' ) );
		$coupon->set_usage_limit( $this->sanitize_coupon_integer_or_null( 'usage_limit' ) );
		$coupon->set_usage_limit_per_user( $this->sanitize_coupon_integer_or_null( 'usage_limit_per_user' ) );
		$coupon->update_meta_data( '_loyalty_coupon', 'yes' );
		$coupon->update_meta_data( '_loyalty_members_only', isset( $_POST['members_only'] ) ? 'yes' : 'no' );
		$coupon->update_meta_data( '_loyalty_show_in_popup', isset( $_POST['show_in_popup'] ) ? 'yes' : 'no' );
	}

	/**
	 * Returns allowed coupon discount types for this management UI.
	 *
	 * @return array<int, string>
	 */
	private function get_allowed_coupon_discount_types() {
		return array( 'fixed_cart', 'percent', 'fixed_product' );
	}

	/**
	 * Returns coupon template definitions for the admin UI.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_coupon_templates() {
		return array(
			array(
				'title'                => __( '10% Members Discount', 'klientoora-card' ),
				'summary'              => __( 'Percent discount for active club members.', 'klientoora-card' ),
				'coupon_code'          => 'CLUB10',
				'description'          => __( '10% discount for active loyalty club members.', 'klientoora-card' ),
				'discount_type'        => 'percent',
				'amount'               => '10',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Free Shipping', 'klientoora-card' ),
				'summary'              => __( 'Free shipping coupon shown in the loyalty popup.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBSHIP',
				'description'          => __( 'Free shipping benefit for loyalty club members.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '0',
				'free_shipping'        => 'yes',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Fixed Cart Benefit', 'klientoora-card' ),
				'summary'              => __( 'Fixed cart amount discount for a campaign.', 'klientoora-card' ),
				'coupon_code'          => 'CLUB50',
				'description'          => __( 'Fixed cart discount for a loyalty club campaign.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '50',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Birthday Gift', 'klientoora-card' ),
				'summary'              => __( 'Birthday fixed cart benefit for club members.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBBDAY',
				'description'          => __( 'Birthday benefit for active loyalty club members.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '30',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'VIP 15%', 'klientoora-card' ),
				'summary'              => __( 'Stronger percent discount for a VIP campaign.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBVIP15',
				'description'          => __( '15% discount for selected loyalty club campaigns.', 'klientoora-card' ),
				'discount_type'        => 'percent',
				'amount'               => '15',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Weekend Deal', 'klientoora-card' ),
				'summary'              => __( 'Fixed weekend cart discount.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBWEEKEND',
				'description'          => __( 'Weekend loyalty club cart benefit.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '25',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '100',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Product Treat', 'klientoora-card' ),
				'summary'              => __( 'Fixed product discount for member campaigns.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBPRODUCT',
				'description'          => __( 'Product discount for loyalty club members.', 'klientoora-card' ),
				'discount_type'        => 'fixed_product',
				'amount'               => '10',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Free Shipping Plus', 'klientoora-card' ),
				'summary'              => __( 'Free shipping with a small cart discount.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBSHIPPLUS',
				'description'          => __( 'Free shipping and a small fixed cart benefit.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '15',
				'free_shipping'        => 'yes',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
			array(
				'title'                => __( 'Welcome Back', 'klientoora-card' ),
				'summary'              => __( 'Cart discount for returning club members.', 'klientoora-card' ),
				'coupon_code'          => 'CLUBBACK',
				'description'          => __( 'Welcome back benefit for loyalty club members.', 'klientoora-card' ),
				'discount_type'        => 'fixed_cart',
				'amount'               => '20',
				'free_shipping'        => 'no',
				'expiry_date'          => '',
				'minimum_spend'        => '',
				'maximum_spend'        => '',
				'usage_limit'          => '',
				'usage_limit_per_user' => '1',
				'members_only'         => 'yes',
				'show_in_popup'        => 'yes',
			),
		);
	}

	/**
	 * Returns coupons created by this plugin.
	 *
	 * @return array<int, WC_Coupon>
	 */
	private function get_loyalty_coupons() {
		$coupon_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_loyalty_coupon',
				'meta_value'     => 'yes',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'post_type'      => 'shop_coupon',
				'posts_per_page' => -1,
			)
		);
		$coupons    = array();

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );

			if ( $coupon->get_id() ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * Filters active loyalty coupons.
	 *
	 * @param array<int, WC_Coupon> $coupons Loyalty coupons.
	 *
	 * @return array<int, WC_Coupon>
	 */
	private function get_active_loyalty_coupons( $coupons ) {
		return array_values(
			array_filter(
				$coupons,
				function ( $coupon ) {
					return $coupon && $coupon->get_id() && 'publish' === $coupon->get_status();
				}
			)
		);
	}

	/**
	 * Returns an available coupon code based on a template code.
	 *
	 * @param string $base_code Template coupon code.
	 *
	 * @return string
	 */
	private function get_available_coupon_code( $base_code ) {
		$base_code = wc_format_coupon_code( sanitize_text_field( $base_code ) );

		if ( '' === $base_code || ! wc_get_coupon_id_by_code( $base_code ) ) {
			return $base_code;
		}

		for ( $index = 2; $index <= 999; $index++ ) {
			$candidate = wc_format_coupon_code( $base_code . '-' . $index );

			if ( ! wc_get_coupon_id_by_code( $candidate ) ) {
				return $candidate;
			}
		}

		return wc_format_coupon_code( $base_code . '-' . wp_generate_password( 4, false, false ) );
	}

	/**
	 * Formats an active coupon discount summary for admin-main cards.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function format_admin_main_coupon_discount_summary( $coupon ) {
		$amount        = (float) $coupon->get_amount();
		$amount_label  = $this->format_admin_main_coupon_amount( $amount );
		$money_label   = $this->format_admin_main_coupon_money_amount( $amount );
		$free_shipping = $coupon->get_free_shipping();

		if ( $free_shipping && 0.0 === $amount ) {
			return __( 'משלוח חינם', 'klientoora-card' );
		}

		switch ( $coupon->get_discount_type() ) {
			case 'percent':
				$summary = sprintf(
					/* translators: %s is the coupon percentage amount. */
					__( '%s%% הנחה', 'klientoora-card' ),
					$amount_label
				);
				break;
			case 'fixed_product':
				$summary = sprintf(
					/* translators: %s is the coupon fixed product amount. */
					__( '%s הנחה למוצר', 'klientoora-card' ),
					$money_label
				);
				break;
			case 'fixed_cart':
			default:
				$summary = sprintf(
					/* translators: %s is the coupon fixed cart amount. */
					__( '%s הנחה לעגלה', 'klientoora-card' ),
					$money_label
				);
				break;
		}

		if ( $free_shipping ) {
			$summary .= ' + ' . __( 'משלוח חינם', 'klientoora-card' );
		}

		return $summary;
	}

	/**
	 * Formats a coupon amount without unnecessary trailing zeros.
	 *
	 * @param float $amount Coupon amount.
	 *
	 * @return string
	 */
	private function format_admin_main_coupon_amount( $amount ) {
		$decimals = floor( $amount ) === $amount ? 0 : 2;

		return number_format_i18n( $amount, $decimals );
	}

	/**
	 * Formats a fixed coupon amount as store currency.
	 *
	 * @param float $amount Coupon amount.
	 *
	 * @return string
	 */
	private function format_admin_main_coupon_money_amount( $amount ) {
		if ( function_exists( 'wc_price' ) ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}

		return $this->format_admin_main_coupon_amount( $amount );
	}

	/**
	 * Formats an active coupon expiry date for admin-main cards.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function format_admin_main_coupon_expiry( $coupon ) {
		$expiry = $coupon->get_date_expires();

		if ( ! $expiry ) {
			return __( 'ללא תוקף', 'klientoora-card' );
		}

		return $expiry->date_i18n( get_option( 'date_format' ) );
	}

	/**
	 * Returns editable admin-main values from a WooCommerce coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return array<string, string>
	 */
	private function get_admin_main_coupon_values_from_coupon( $coupon ) {
		$expiry_date = $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '';

		return array(
			'title'                => $coupon->get_code(),
			'summary'              => $coupon->get_description(),
			'coupon_code'          => $coupon->get_code(),
			'description'          => $coupon->get_description(),
			'discount_type'        => $coupon->get_discount_type(),
			'amount'               => (string) $coupon->get_amount(),
			'free_shipping'        => $coupon->get_free_shipping() ? 'yes' : 'no',
			'expiry_date'          => $expiry_date,
			'minimum_spend'        => (string) $coupon->get_minimum_amount(),
			'maximum_spend'        => (string) $coupon->get_maximum_amount(),
			'usage_limit'          => $coupon->get_usage_limit() ? (string) $coupon->get_usage_limit() : '',
			'usage_limit_per_user' => $coupon->get_usage_limit_per_user() ? (string) $coupon->get_usage_limit_per_user() : '',
			'members_only'         => 'yes' === $coupon->get_meta( '_loyalty_members_only' ) ? 'yes' : 'no',
			'show_in_popup'        => 'yes' === $coupon->get_meta( '_loyalty_show_in_popup' ) ? 'yes' : 'no',
		);
	}

	/**
	 * Returns a coupon only when it belongs to this plugin.
	 *
	 * @param int $coupon_id Coupon ID.
	 *
	 * @return WC_Coupon|null
	 */
	private function get_loyalty_coupon( $coupon_id ) {
		if ( 0 >= $coupon_id || 'yes' !== get_post_meta( $coupon_id, '_loyalty_coupon', true ) ) {
			return null;
		}

		$coupon = new WC_Coupon( $coupon_id );

		return $coupon->get_id() ? $coupon : null;
	}

	/**
	 * Returns published WooCommerce coupons that can be selected for challenges.
	 *
	 * @return array<int, WC_Coupon>
	 */
	private function get_challenge_coupon_options() {
		if ( ! $this->is_woocommerce_active() ) {
			return array();
		}

		$coupon_ids = get_posts(
			array(
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'post_type'      => 'shop_coupon',
				'posts_per_page' => -1,
			)
		);
		$coupons    = array();

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );

			if ( $coupon->get_id() ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * Returns a compact coupon option label.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	private function get_coupon_option_label( $coupon ) {
		return sprintf(
			/* translators: 1: coupon code, 2: discount type, 3: coupon amount. */
			__( '%1$s - %2$s - %3$s', 'klientoora-card' ),
			$coupon->get_code(),
			$coupon->get_discount_type(),
			$coupon->get_free_shipping() ? __( 'Free shipping', 'klientoora-card' ) : $coupon->get_amount()
		);
	}

	/**
	 * Sanitizes the order challenge goal setting.
	 *
	 * @param mixed $value Submitted value.
	 *
	 * @return int
	 */
	public function sanitize_order_challenge_goal( $value ) {
		return max( 1, absint( $value ) );
	}

	/**
	 * Sanitizes the order challenge coupon setting.
	 *
	 * @param mixed $value Submitted value.
	 *
	 * @return int
	 */
	public function sanitize_order_challenge_coupon_id( $value ) {
		$coupon_id = absint( $value );

		if ( 0 === $coupon_id ) {
			return 0;
		}

		$coupon = get_post( $coupon_id );

		if ( ! $coupon || 'shop_coupon' !== $coupon->post_type || 'publish' !== $coupon->post_status ) {
			return 0;
		}

		return $coupon_id;
	}

	/**
	 * Sanitizes a decimal coupon request field.
	 *
	 * @param string $field Field key.
	 *
	 * @return float
	 */
	private function sanitize_coupon_decimal( $field ) {
		$value = isset( $_POST[ $field ] ) ? wc_format_decimal( wp_unslash( $_POST[ $field ] ) ) : 0;

		return max( 0, (float) $value );
	}

	/**
	 * Sanitizes an optional integer coupon request field.
	 *
	 * @param string $field Field key.
	 *
	 * @return int|null
	 */
	private function sanitize_coupon_integer_or_null( $field ) {
		if ( ! isset( $_POST[ $field ] ) || '' === trim( wp_unslash( $_POST[ $field ] ) ) ) {
			return null;
		}

		return absint( wp_unslash( $_POST[ $field ] ) );
	}

	/**
	 * Sanitizes an optional coupon date field.
	 *
	 * @param string $field Field key.
	 *
	 * @return string|null
	 */
	private function sanitize_coupon_date( $field ) {
		$date = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';

		if ( '' === $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		$date_parts = explode( '-', $date );

		if ( ! checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] ) ) {
			return null;
		}

		return $date;
	}

	/**
	 * Redirects back to the Club Coupons page.
	 *
	 * @param string $notice   Notice key.
	 * @param int    $coupon_id Optional coupon ID to keep editing.
	 *
	 * @return void
	 */
	private function redirect_coupons_page( $notice, $coupon_id = 0 ) {
		$return_to = isset( $_POST['klientoora_coupons_return'] ) ? sanitize_key( wp_unslash( $_POST['klientoora_coupons_return'] ) ) : '';

		if ( 'admin-main' === $return_to ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'klientoora_card_coupon_notice' => $notice,
					),
					home_url( '/admin-main/' )
				) . '#club-activity'
			);
			exit;
		}

		$args = array(
			'page'                          => 'loyalty-club-coupons',
			'klientoora_card_coupon_notice' => $notice,
		);

		if ( $coupon_id ) {
			$args['coupon_id'] = $coupon_id;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Renders the Make integration section description.
	 *
	 * @return void
	 */
	public function render_make_section_description() {
		echo '<p>' . esc_html__( 'Add the Make webhook URL that will be used by future automations and integrations.', 'klientoora-card' ) . '</p>';
	}

	/**
	 * Renders the points settings section description.
	 *
	 * @return void
	 */
	public function render_points_section_description() {
		echo '<p>' . esc_html__( 'Configure how many loyalty points members earn from completed WooCommerce orders.', 'klientoora-card' ) . '</p>';
	}

	/**
	 * Renders the benefits and promotions settings section description.
	 *
	 * @return void
	 */
	public function render_benefits_section_description() {
		echo '<p>' . esc_html__( 'Configure fixed loyalty member benefits and promotions.', 'klientoora-card' ) . '</p>';
	}

	/**
	 * Renders the Make webhook URL field.
	 *
	 * @return void
	 */
	public function render_make_webhook_url_field() {
		$webhook_url = get_option( 'klientoora_card_make_webhook_url', '' );
		?>
		<input
			type="url"
			id="klientoora_card_make_webhook_url"
			name="klientoora_card_make_webhook_url"
			class="regular-text"
			value="<?php echo esc_url( $webhook_url ); ?>"
			placeholder="<?php echo esc_attr__( 'https://hook.eu1.make.com/example', 'klientoora-card' ); ?>"
		/>
		<p class="description">
			<?php echo esc_html__( 'Paste the webhook URL you created in Make. Future automations and connections will use this address.', 'klientoora-card' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the points earning percentage field.
	 *
	 * @return void
	 */
	public function render_points_earning_percentage_field() {
		$percentage = get_option( 'klientoora_card_points_earning_percentage', 10 );
		?>
		<input
			type="number"
			id="klientoora_card_points_earning_percentage"
			name="klientoora_card_points_earning_percentage"
			class="small-text"
			value="<?php echo esc_attr( $percentage ); ?>"
			min="0"
			step="0.01"
		/>
		<p class="description">
			<?php echo esc_html__( 'Example: 10 means a 500 ILS order earns 50 points.', 'klientoora-card' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the fixed member discount percentage field.
	 *
	 * @return void
	 */
	public function render_member_discount_percentage_field() {
		$percentage = get_option( 'loyalty_member_discount_percentage', 0 );
		?>
		<input
			type="number"
			id="loyalty_member_discount_percentage"
			name="loyalty_member_discount_percentage"
			class="small-text"
			value="<?php echo esc_attr( $percentage ); ?>"
			min="0"
			max="100"
			step="0.01"
		/>
		<p class="description">
			<?php echo esc_html__( 'Example: 10 means active loyalty members get a 10% cart discount.', 'klientoora-card' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitizes the Make webhook URL.
	 *
	 * @param string $url Webhook URL.
	 *
	 * @return string
	 */
	public function sanitize_webhook_url( $url ) {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	/**
	 * Sanitizes the points earning percentage.
	 *
	 * @param mixed $percentage Submitted percentage.
	 *
	 * @return float
	 */
	public function sanitize_points_earning_percentage( $percentage ) {
		$percentage = is_numeric( $percentage ) ? (float) $percentage : 10;

		return max( 0, $percentage );
	}

	/**
	 * Sanitizes a percentage setting.
	 *
	 * @param mixed $percentage Submitted percentage.
	 *
	 * @return float
	 */
	public function sanitize_percentage( $percentage ) {
		$percentage = is_numeric( $percentage ) ? (float) $percentage : 0;

		return min( 100, max( 0, $percentage ) );
	}

	/**
	 * Returns the total number of WordPress users.
	 *
	 * @return int
	 */
	private function get_total_users() {
		$user_count = count_users();

		return isset( $user_count['total_users'] ) ? absint( $user_count['total_users'] ) : 0;
	}

	/**
	 * Returns the total loyalty points issued.
	 *
	 * @return int
	 */
	private function get_total_points_issued() {
		return absint( get_option( 'klientoora_card_total_points_issued', 0 ) );
	}

	/**
	 * Returns placeholder dashboard cards.
	 *
	 * @return array<int, array{title: string, description: string}>
	 */
	private function get_placeholder_cards() {
		return array(
			array(
				'title'       => __( 'Settings', 'klientoora-card' ),
				'description' => __( 'Configure loyalty club rules and preferences.', 'klientoora-card' ),
			),
			array(
				'title'       => __( 'Points', 'klientoora-card' ),
				'description' => __( 'Manage point balances, earning rules, and redemptions.', 'klientoora-card' ),
			),
			array(
				'title'       => __( 'WooCommerce', 'klientoora-card' ),
				'description' => __( 'Connect purchases and customer activity to loyalty rewards.', 'klientoora-card' ),
			),
			array(
				'title'       => __( 'Webhooks', 'klientoora-card' ),
				'description' => __( 'Prepare external integrations for loyalty events.', 'klientoora-card' ),
			),
		);
	}
}
