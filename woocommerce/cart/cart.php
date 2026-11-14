<?php
/**
 * Cart: item list (not a table) + sticky totals. Keeps WooCommerce's form names, classes and
 * hooks so its cart scripts (AJAX update, remove, coupons) work unchanged.
 * Quantities update automatically (assets/js/ui/cart.js).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>
<div class="cart-layout">
	<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="cart-head">
			<h1 class="cart-head__title"><?php esc_html_e( 'سبد خرید', 'faucets-valves' ); ?></h1>
			<span class="cart-head__count">
				<?php
				$fv_count = WC()->cart->get_cart_contents_count();
				/* translators: %s: item count */
				echo esc_html( sprintf( __( '%s عدد کالا', 'faucets-valves' ), number_format_i18n( $fv_count ) ) );
				?>
			</span>
		</div>

		<ul class="cart-items shop_table cart woocommerce-cart-form__contents">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
				?>
				<li class="cart-item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="cart-item__media product-thumbnail">
						<?php
						if ( $product_permalink ) {
							printf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>

					<div class="cart-item__info product-name">
						<?php
						if ( $product_permalink ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a class="cart-item__name" href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
						} else {
							echo '<span class="cart-item__name">' . wp_kses_post( $product_name ) . '</span>';
						}
						do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

						if ( $_product->get_sku() ) {
							echo '<span class="cart-item__sku" dir="ltr">' . esc_html( $_product->get_sku() ) . '</span>';
						}
						echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput

						if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'با تأخیر ارسال می‌شود (پیش‌سفارش)', 'faucets-valves' ) . '</p>', $product_id ) );
						}
						?>
						<span class="cart-item__unit product-price">
							<span class="cart-item__label"><?php esc_html_e( 'قیمت واحد:', 'faucets-valves' ); ?></span>
							<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</div>

					<div class="cart-item__qty product-quantity">
						<?php
						if ( $_product->is_sold_individually() ) {
							$min_quantity = 1;
							$max_quantity = 1;
						} else {
							$min_quantity = 0;
							$max_quantity = $_product->get_max_purchase_quantity();
						}
						$product_quantity = woocommerce_quantity_input(
							array(
								'input_name'   => "cart[{$cart_item_key}][qty]",
								'input_value'  => $cart_item['quantity'],
								'max_value'    => $max_quantity,
								'min_value'    => $min_quantity,
								'product_name' => $product_name,
							),
							$_product,
							false
						);
						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>

					<div class="cart-item__subtotal product-subtotal">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>

					<div class="cart-item__remove product-remove">
						<?php
						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a role="button" href="%1$s" class="remove" aria-label="%2$s" data-product_id="%3$s" data-product_sku="%4$s">%5$s</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s: product name */
								esc_attr( sprintf( __( 'حذف %s از سبد', 'faucets-valves' ), wp_strip_all_tags( $product_name ) ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() ),
								fv_icon( 'close' )
							),
							$cart_item_key
						);
						?>
					</div>
				</li>
				<?php
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
		</ul>

		<div class="cart-actions actions">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="coupon">
					<label for="coupon_code" class="sr-only"><?php esc_html_e( 'کد تخفیف', 'faucets-valves' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'کد تخفیف', 'faucets-valves' ); ?>">
					<button type="submit" class="btn btn--ghost" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'اعمال', 'faucets-valves' ); ?></button>
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
			<?php endif; ?>

			<button type="submit" class="btn btn--ghost cart-update" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'به‌روزرسانی سبد', 'faucets-valves' ); ?></button>

			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<div class="cart-collaterals">
		<?php do_action( 'woocommerce_cart_collaterals' ); ?>
	</div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
