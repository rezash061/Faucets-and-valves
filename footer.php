<?php
/**
 * Site footer + mobile bottom bar.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;
$phone   = fv_mod( 'fv_phone' );
$address = fv_mod( 'fv_address' );
$hours   = fv_mod( 'fv_hours' );
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="trust-strip">
			<div><?php echo fv_icon( 'truck' ); // phpcs:ignore ?><b><?php esc_html_e( 'ارسال به سراسر ایران', 'faucets-valves' ); ?></b><span><?php esc_html_e( 'باربری و پست پیشتاز', 'faucets-valves' ); ?></span></div>
			<div><?php echo fv_icon( 'shield' ); // phpcs:ignore ?><b><?php esc_html_e( 'ضمانت اصالت کالا', 'faucets-valves' ); ?></b><span><?php esc_html_e( 'مستقیم از نمایندگی', 'faucets-valves' ); ?></span></div>
			<div><?php echo fv_icon( 'card' ); // phpcs:ignore ?><b><?php esc_html_e( 'پرداخت امن', 'faucets-valves' ); ?></b><span><?php esc_html_e( 'زرین‌پال یا کارت به کارت', 'faucets-valves' ); ?></span></div>
			<div><?php echo fv_icon( 'document' ); // phpcs:ignore ?><b><?php esc_html_e( 'پیش‌فاکتور رسمی', 'faucets-valves' ); ?></b><span><?php esc_html_e( 'برای پروژه‌ها و پیمانکاران', 'faucets-valves' ); ?></span></div>
		</div>

		<div class="footer-grid">
			<div class="footer-col footer-col--about">
				<?php fv_logo(); ?>
				<p><?php echo esc_html( fv_mod( 'fv_footer_about' ) ); ?></p>
			</div>

			<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="footer-col" aria-label="<?php esc_attr_e( 'پیوندهای فوتر', 'faucets-valves' ); ?>">
				<h2 class="footer-col__title"><?php esc_html_e( 'دسترسی سریع', 'faucets-valves' ); ?></h2>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'footer-menu',
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
				?>
			</nav>
			<?php endif; ?>

			<div class="footer-col">
				<h2 class="footer-col__title"><?php esc_html_e( 'تماس', 'faucets-valves' ); ?></h2>
				<ul class="contact-list">
					<?php if ( $phone ) : ?>
						<li><?php echo fv_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" dir="ltr"><?php echo esc_html( $phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<li><?php echo fv_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $address ); ?></span></li>
					<?php endif; ?>
					<?php if ( $hours ) : ?>
						<li><?php echo fv_icon( 'clock' ); // phpcs:ignore ?><span><?php echo esc_html( $hours ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="footer-col footer-col--badges">
				<?php if ( is_active_sidebar( 'footer-badges' ) ) : ?>
					<?php dynamic_sidebar( 'footer-badges' ); ?>
				<?php endif; ?>
			</div>
		</div>

		<p class="footer-bottom">
			<?php
			/* translators: 1: year, 2: site name */
			printf( esc_html__( '© %1$s %2$s. تمام حقوق محفوظ است.', 'faucets-valves' ), esc_html( wp_date( 'Y' ) ), esc_html( get_bloginfo( 'name' ) ) );
			?>
		</p>
	</div>
</footer>

<?php get_template_part( 'template-parts/mobile-bar' ); ?>

<?php wp_footer(); ?>
</body>
</html>
