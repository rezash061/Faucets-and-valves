<?php
/**
 * Mobile bottom bar: Home, Shop, Search, Cart, Account (5 items).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$has_wc   = function_exists( 'wc_get_page_permalink' );
$shop     = $has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account  = $has_wc ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?>
<nav class="mobile-bar" aria-label="<?php esc_attr_e( 'پیمایش پایین صفحه', 'faucets-valves' ); ?>">
	<a class="mobile-bar__item<?php echo is_front_page() ? ' is-current' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>
		<?php echo fv_icon( 'home' ); // phpcs:ignore ?><span><?php esc_html_e( 'خانه', 'faucets-valves' ); ?></span>
	</a>
	<a class="mobile-bar__item" href="<?php echo esc_url( $shop ); ?>">
		<?php echo fv_icon( 'grid' ); // phpcs:ignore ?><span><?php esc_html_e( 'محصولات', 'faucets-valves' ); ?></span>
	</a>
	<button class="mobile-bar__item search-toggle" type="button" aria-controls="header-search" aria-expanded="false">
		<?php echo fv_icon( 'search' ); // phpcs:ignore ?><span><?php esc_html_e( 'جستجو', 'faucets-valves' ); ?></span>
	</button>
	<?php echo fv_cart_link( 'mobile-bar__item mobile-bar-cart', true ); // phpcs:ignore ?>
	<a class="mobile-bar__item" href="<?php echo esc_url( $account ); ?>">
		<?php echo fv_icon( 'user' ); // phpcs:ignore ?><span><?php esc_html_e( 'حساب من', 'faucets-valves' ); ?></span>
	</a>
</nav>
