<?php
/**
 * Template Name: سفارش سریع
 *
 * Bulk order form: search by name or code, quantities, paste a list, add all to the cart.
 * Needs JavaScript; without it the page points to the shop.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<?php
	fv_page_head( array(), array(
		'lead' => has_excerpt() ? get_the_excerpt() : __( 'کالاها را با نام یا کد پیدا کنید، تعداد را وارد کنید و همه را یک‌جا به سبد اضافه کنید.', 'faucets-valves' ),
	) );
	?>
	<div class="container page-wrap quick-order">
		<?php if ( '' !== trim( get_the_content() ) ) : ?>
			<div class="entry-content"><?php the_content(); ?></div>
		<?php endif; ?>

		<noscript><p class="shop-empty"><?php esc_html_e( 'برای سفارش سریع، جاوااسکریپت مرورگر را روشن کنید یا از فروشگاه خرید کنید.', 'faucets-valves' ); ?></p></noscript>

		<div class="qo" id="quick-order" hidden>
			<div class="qo__tools">
				<button type="button" class="btn btn--ghost" data-qo="add-row"><?php echo fv_icon( 'plus' ); // phpcs:ignore ?><?php esc_html_e( 'افزودن ردیف', 'faucets-valves' ); ?></button>
				<button type="button" class="btn btn--ghost" data-qo="toggle-paste" aria-expanded="false" aria-controls="qo-paste"><?php echo fv_icon( 'document' ); // phpcs:ignore ?><?php esc_html_e( 'چسباندن فهرست کدها', 'faucets-valves' ); ?></button>
			</div>

			<div class="qo__paste" id="qo-paste" hidden>
				<label for="qo-paste-input"><?php esc_html_e( 'هر خط: کد کالا و تعداد (با فاصله، ویرگول یا Tab). مثال:', 'faucets-valves' ); ?> <code dir="ltr">FV-1021 24</code></label>
				<textarea id="qo-paste-input" rows="6" dir="ltr" placeholder="FV-1021 24&#10;IV-0050, 100&#10;FV-0305	36"></textarea>
				<div class="qo__paste-actions">
					<button type="button" class="btn btn--brass" data-qo="parse"><?php esc_html_e( 'تبدیل به ردیف‌ها', 'faucets-valves' ); ?></button>
					<p class="qo__paste-msg" role="status"></p>
				</div>
			</div>

			<div class="qo__table" role="table" aria-label="<?php esc_attr_e( 'فهرست سفارش', 'faucets-valves' ); ?>">
				<div class="qo__row qo__row--head" role="row">
					<span role="columnheader">#</span>
					<span role="columnheader"><?php esc_html_e( 'کالا', 'faucets-valves' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'قیمت واحد', 'faucets-valves' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'تعداد', 'faucets-valves' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'جمع', 'faucets-valves' ); ?></span>
					<span role="columnheader"><span class="sr-only"><?php esc_html_e( 'حذف', 'faucets-valves' ); ?></span></span>
				</div>
				<div class="qo__body" data-qo="rows"></div>
			</div>

			<div class="qo__foot">
				<p class="qo__summary" role="status" aria-live="polite">
					<span data-qo="lines">۰</span> <?php esc_html_e( 'قلم', 'faucets-valves' ); ?> ·
					<?php esc_html_e( 'جمع:', 'faucets-valves' ); ?> <b data-qo="total">۰</b> <?php echo esc_html( fv_currency_label() ); ?>
				</p>
				<p class="qo__msg" data-qo="msg" role="alert"></p>
				<button type="button" class="btn btn--brass qo__submit" data-qo="submit"><?php echo fv_icon( 'bag' ); // phpcs:ignore ?><?php esc_html_e( 'افزودن همه به سبد خرید', 'faucets-valves' ); ?></button>
			</div>
		</div>
	</div>
	<?php
endwhile;

get_footer();
