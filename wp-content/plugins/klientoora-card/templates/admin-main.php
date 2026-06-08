<?php
/**
 * Standalone Admin Main template.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
				<a class="is-active" href="#dashboard"><?php echo esc_html__( 'דשבורד', 'klientoora-card' ); ?></a>
				<a href="#orders"><?php echo esc_html__( 'הזמנות', 'klientoora-card' ); ?></a>
				<a href="#customers"><?php echo esc_html__( 'לקוחות', 'klientoora-card' ); ?></a>
				<a href="#points"><?php echo esc_html__( 'נקודות', 'klientoora-card' ); ?></a>
				<a href="#redemptions"><?php echo esc_html__( 'מימושים', 'klientoora-card' ); ?></a>
				<a href="#coupons"><?php echo esc_html__( 'קופונים', 'klientoora-card' ); ?></a>
				<a href="#settings"><?php echo esc_html__( 'הגדרות', 'klientoora-card' ); ?></a>
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
				<section class="klientoora-admin-main__panel">
					<h2><?php echo esc_html__( 'ברוך הבא למערכת הניהול', 'klientoora-card' ); ?></h2>
				</section>
			</main>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
