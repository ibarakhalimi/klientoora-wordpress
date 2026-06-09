<?php
/**
 * Standalone Admin Main template.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$klientoora_admin_menu = new Klientoora_Card_Admin_Menu();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html__( 'Admin Main', 'klientoora-card' ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="klientoora-admin-main">
	<div class="klientoora-admin-main__shell">
		<aside class="klientoora-admin-main__sidebar" aria-label="<?php echo esc_attr__( 'ניווט ראשי', 'klientoora-card' ); ?>">
			<div class="klientoora-admin-main__brand">
				<span class="klientoora-admin-main__brand-mark">K</span>
				<span class="klientoora-admin-main__brand-name">Klientoora</span>
			</div>

			<nav class="klientoora-admin-main__nav">
				<a class="is-active" href="#dashboard" data-klientoora-main-nav><?php echo esc_html__( 'דשבורד', 'klientoora-card' ); ?></a>
				<a href="#orders" data-klientoora-main-nav><?php echo esc_html__( 'הזמנות', 'klientoora-card' ); ?></a>
				<a href="#products" data-klientoora-main-nav><?php echo esc_html__( 'מוצרים', 'klientoora-card' ); ?></a>
				<a href="#members" data-klientoora-main-nav><?php echo esc_html__( 'חברי מועדון', 'klientoora-card' ); ?></a>
				<div class="klientoora-admin-main__nav-group">
					<a href="#club-activity" data-klientoora-main-nav><?php echo esc_html__( 'פעילות מועדון', 'klientoora-card' ); ?></a>
					<div class="klientoora-admin-main__subnav" aria-label="<?php echo esc_attr__( 'תתי תפריט פעילות מועדון', 'klientoora-card' ); ?>">
						<a href="#club-coupons" data-klientoora-main-nav><?php echo esc_html__( 'ניהול קופונים', 'klientoora-card' ); ?></a>
						<a href="#challenges" data-klientoora-main-nav><?php echo esc_html__( 'ניהול אתגרים', 'klientoora-card' ); ?></a>
						<a href="#point-redemptions" data-klientoora-main-nav><?php echo esc_html__( 'מימוש נקודות', 'klientoora-card' ); ?></a>
					</div>
				</div>
				<a href="#settings" data-klientoora-main-nav><?php echo esc_html__( 'הגדרות', 'klientoora-card' ); ?></a>
			</nav>
		</aside>

		<div class="klientoora-admin-main__workspace">
			<header class="klientoora-admin-main__topbar">
				<div>
					<p><?php echo esc_html__( 'מערכת ניהול', 'klientoora-card' ); ?></p>
					<h1><?php echo esc_html__( 'Admin Main', 'klientoora-card' ); ?></h1>
				</div>
			</header>

			<main class="klientoora-admin-main__content" id="dashboard">
				<section class="klientoora-admin-main__panel" id="dashboard-panel" data-klientoora-admin-main-panel="dashboard">
					<?php $klientoora_admin_menu->render_dashboard_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="orders-panel" data-klientoora-admin-main-panel="orders" hidden>
					<?php $klientoora_admin_menu->render_orders_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="products-panel" data-klientoora-admin-main-panel="products" hidden>
					<?php $klientoora_admin_menu->render_products_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="members-panel" data-klientoora-admin-main-panel="members" hidden>
					<?php $klientoora_admin_menu->render_members_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="club-activity-panel" data-klientoora-admin-main-panel="club-activity" hidden>
					<?php $klientoora_admin_menu->render_club_activity_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="club-coupons-panel" data-klientoora-admin-main-panel="club-coupons" hidden>
					<?php $klientoora_admin_menu->render_club_coupons_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="challenges-panel" data-klientoora-admin-main-panel="challenges" hidden>
					<?php $klientoora_admin_menu->render_challenges_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="point-redemptions-panel" data-klientoora-admin-main-panel="point-redemptions" hidden>
					<?php $klientoora_admin_menu->render_point_redemptions_admin_main_view(); ?>
				</section>

				<section class="klientoora-admin-main__panel" id="settings-panel" data-klientoora-admin-main-panel="settings" hidden>
					<h2><?php echo esc_html__( 'הגדרות', 'klientoora-card' ); ?></h2>
				</section>
			</main>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
