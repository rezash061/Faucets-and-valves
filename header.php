<?php
/**
 * Site header.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link sr-only-focusable" href="#main"><?php esc_html_e( 'رفتن به محتوا', 'faucets-valves' ); ?></a>

<header class="site-header" id="site-header" data-overlay="<?php echo is_front_page() ? 'true' : 'false'; ?>">
	<div class="container site-header__inner">
		<button class="icon-btn nav-toggle" type="button" aria-controls="nav-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'باز کردن منو', 'faucets-valves' ); ?>">
			<?php echo fv_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<?php fv_logo(); ?>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'faucets-valves' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'primary-nav__list',
				'depth'          => 2,
				'fallback_cb'    => false,
			) );
			?>
		</nav>

		<div class="header-actions">
			<button class="icon-btn search-toggle" type="button" aria-controls="header-search" aria-expanded="false" aria-label="<?php esc_attr_e( 'جستجو', 'faucets-valves' ); ?>">
				<?php echo fv_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="search-toggle__text" aria-hidden="true"><?php esc_html_e( 'نام یا کد کالا', 'faucets-valves' ); ?></span>
			</button>
			<a class="icon-btn header-account" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'حساب کاربری', 'faucets-valves' ); ?>">
				<?php echo fv_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
			<?php echo fv_cart_link( 'icon-btn header-cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>

	<div class="header-search" id="header-search" hidden>
		<form class="container header-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="sr-only" for="header-search-input"><?php esc_html_e( 'جستجوی محصول', 'faucets-valves' ); ?></label>
			<input id="header-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'نام محصول یا کد کالا…', 'faucets-valves' ); ?>" autocomplete="off">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<input type="hidden" name="post_type" value="product">
			<?php endif; ?>
			<button class="btn btn--brass" type="submit"><?php esc_html_e( 'جستجو', 'faucets-valves' ); ?></button>
		</form>
	</div>
</header>

<div class="nav-drawer" id="nav-drawer" hidden>
	<div class="nav-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'منو', 'faucets-valves' ); ?>">
		<button class="icon-btn nav-drawer__close" type="button" aria-label="<?php esc_attr_e( 'بستن منو', 'faucets-valves' ); ?>">
			<?php echo fv_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<?php
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'nav-drawer__list',
			'depth'          => 2,
			'fallback_cb'    => false,
		) );
		?>
	</div>
	<div class="nav-drawer__scrim" data-close></div>
</div>

<main id="main" class="site-main" tabindex="-1">
