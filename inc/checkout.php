<?php
/**
 * Cart & checkout: classic switch, steps bar, Iranian checkout fields, payment titles,
 * reorder, assets.
 *
 * The theme styles WooCommerce's classic (shortcode) cart and checkout. WooCommerce now
 * creates block-based pages by default; an admin notice offers a one-click switch.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Classic cart/checkout switch ---------- */

/**
 * Pages still using the cart/checkout blocks.
 *
 * @return array page_id => shortcode.
 */
function fv_block_checkout_pages() {
	$out = array();
	foreach ( array( 'cart' => 'woocommerce_cart', 'checkout' => 'woocommerce_checkout' ) as $page => $shortcode ) {
		$id = wc_get_page_id( $page );
		if ( $id > 0 && has_block( "woocommerce/{$page}", $id ) ) {
			$out[ $id ] = $shortcode;
		}
	}
	return $out;
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! fv_block_checkout_pages() ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=fv_classic_checkout' ), 'fv_classic_checkout' );
	?>
	<div class="notice notice-warning">
		<p><strong><?php esc_html_e( 'قالب شیرآلات:', 'faucets-valves' ); ?></strong>
		<?php esc_html_e( 'برگه‌های سبد خرید و تسویه حساب از بلوک‌های جدید ووکامرس استفاده می‌کنند. طراحی قالب، پیش‌فاکتور، کارت به کارت و بیشتر درگاه‌های ایرانی با نسخهٔ کلاسیک کار می‌کنند.', 'faucets-valves' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'تبدیل به سبد خرید و تسویه حساب کلاسیک', 'faucets-valves' ); ?></a></p>
	</div>
	<?php
} );

add_action( 'admin_post_fv_classic_checkout', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'دسترسی ندارید.', 'faucets-valves' ) );
	}
	check_admin_referer( 'fv_classic_checkout' );
	foreach ( fv_block_checkout_pages() as $id => $shortcode ) {
		wp_update_post( array(
			'ID'           => $id,
			'post_content' => "<!-- wp:shortcode -->\n[{$shortcode}]\n<!-- /wp:shortcode -->",
		) );
	}
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
} );

/* ---------- Steps bar: cart → details & payment → done ---------- */

/**
 * @param int $current 1 cart, 2 checkout, 3 order received.
 */
function fv_checkout_steps( $current ) {
	$steps = array(
		1 => __( 'سبد خرید', 'faucets-valves' ),
		2 => __( 'اطلاعات و پرداخت', 'faucets-valves' ),
		3 => __( 'ثبت سفارش', 'faucets-valves' ),
	);
	echo '<ol class="checkout-steps" aria-label="' . esc_attr__( 'مراحل خرید', 'faucets-valves' ) . '">';
	foreach ( $steps as $n => $label ) {
		$state = $n < $current ? 'is-done' : ( $n === $current ? 'is-current' : '' );
		printf(
			'<li class="%1$s"%2$s><span class="checkout-steps__num">%3$s</span><span>%4$s</span></li>',
			esc_attr( $state ),
			$n === $current ? ' aria-current="step"' : '',
			esc_html( number_format_i18n( $n ) ),
			esc_html( $label )
		);
	}
	echo '</ol>';
}
add_action( 'woocommerce_before_cart', function () { fv_checkout_steps( 1 ); }, 1 );
add_action( 'woocommerce_before_checkout_form', function () { fv_checkout_steps( 2 ); }, 1 );

/* ---------- Checkout fields for Iran ---------- */

add_filter( 'woocommerce_default_address_fields', function ( $fields ) {
	unset( $fields['address_2'] );
	$set = array(
		'first_name' => array( 10, __( 'نام', 'faucets-valves' ), 'form-row-first' ),
		'last_name'  => array( 20, __( 'نام خانوادگی', 'faucets-valves' ), 'form-row-last' ),
		'company'    => array( 50, __( 'نام شرکت یا فروشگاه', 'faucets-valves' ), 'form-row-first' ),
		'country'    => array( 60, __( 'کشور', 'faucets-valves' ), 'form-row-wide' ),
		'state'      => array( 70, __( 'استان', 'faucets-valves' ), 'form-row-first' ),
		'city'       => array( 80, __( 'شهر', 'faucets-valves' ), 'form-row-last' ),
		'address_1'  => array( 90, __( 'نشانی کامل', 'faucets-valves' ), 'form-row-wide' ),
		'postcode'   => array( 100, __( 'کد پستی', 'faucets-valves' ), 'form-row-first' ),
	);
	foreach ( $set as $key => list( $priority, $label, $class ) ) {
		if ( ! isset( $fields[ $key ] ) ) {
			continue;
		}
		$fields[ $key ]['priority'] = $priority;
		$fields[ $key ]['label']    = $label;
		$fields[ $key ]['class']    = array( $class );
	}
	$fields['address_1']['placeholder'] = __( 'خیابان، کوچه، پلاک، واحد', 'faucets-valves' );
	$fields['postcode']['required']     = false;
	$fields['company']['required']      = false;
	return $fields;
}, 20 );

add_filter( 'woocommerce_billing_fields', function ( $fields ) {
	if ( isset( $fields['billing_phone'] ) ) {
		$fields['billing_phone']['label']    = __( 'تلفن همراه', 'faucets-valves' );
		$fields['billing_phone']['required'] = true;
		$fields['billing_phone']['priority'] = 30;
		$fields['billing_phone']['class']    = array( 'form-row-first' );
		$fields['billing_phone']['custom_attributes'] = array( 'dir' => 'ltr', 'inputmode' => 'tel' );
		$fields['billing_phone']['placeholder'] = '09xxxxxxxxx';
	}
	if ( isset( $fields['billing_email'] ) ) {
		$fields['billing_email']['label']    = __( 'ایمیل', 'faucets-valves' );
		$fields['billing_email']['required'] = (bool) apply_filters( 'fv_checkout_email_required', false );
		$fields['billing_email']['priority'] = 40;
		$fields['billing_email']['class']    = array( 'form-row-last' );
		$fields['billing_email']['custom_attributes'] = array( 'dir' => 'ltr' );
	}
	$fields['billing_fv_national_id'] = array(
		'label'             => __( 'شناسه ملی / کد اقتصادی', 'faucets-valves' ),
		'required'          => false,
		'priority'          => 55,
		'class'             => array( 'form-row-last' ),
		'custom_attributes' => array( 'dir' => 'ltr', 'inputmode' => 'numeric' ),
		'description'       => __( 'برای فاکتور رسمی شرکت‌ها', 'faucets-valves' ),
	);
	return $fields;
}, 20 );

/**
 * Iranian mobile number check (accepts Persian digits, +98, 0098).
 */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( empty( $data['billing_phone'] ) || ! function_exists( 'fv_latin_digits' ) ) {
		return;
	}
	$phone = preg_replace( '/\D/', '', fv_latin_digits( $data['billing_phone'] ) );
	$phone = preg_replace( '/^(0098|98)/', '0', $phone );
	if ( ! preg_match( '/^0\d{10}$/', $phone ) ) {
		$errors->add( 'billing_phone', __( 'شمارهٔ تلفن را کامل وارد کنید؛ مثلاً ۰۹۱۲۱۲۳۴۵۶۷.', 'faucets-valves' ) );
	}
}, 10, 2 );

add_filter( 'woocommerce_process_checkout_field_billing_phone', function ( $value ) {
	return function_exists( 'fv_latin_digits' ) ? fv_latin_digits( $value ) : $value;
} );

/**
 * Show the national ID / economic code in the order screen.
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$id = $order->get_meta( '_billing_fv_national_id' );
	if ( $id ) {
		printf( '<p><strong>%s</strong> <span dir="ltr">%s</span></p>', esc_html__( 'شناسه ملی / کد اقتصادی:', 'faucets-valves' ), esc_html( $id ) );
	}
} );

/* ---------- Payment method titles ---------- */

/**
 * Bank transfer (BACS) becomes "card to card" while the admin keeps WooCommerce's default text.
 */
add_filter( 'woocommerce_gateway_title', function ( $title, $id = '' ) {
	if ( 'bacs' === $id && in_array( $title, array( 'Direct bank transfer', __( 'Direct bank transfer', 'woocommerce' ) ), true ) ) {
		return __( 'کارت به کارت', 'faucets-valves' );
	}
	return $title;
}, 10, 2 );

add_filter( 'woocommerce_gateway_description', function ( $description, $id = '' ) {
	if ( 'bacs' === $id && false !== strpos( $description, 'Make your payment directly into our bank account' ) ) {
		return __( 'مبلغ سفارش را به کارت ما واریز کنید. شمارهٔ کارت پس از ثبت سفارش نمایش داده می‌شود و می‌توانید رسید را همان‌جا ارسال کنید.', 'faucets-valves' );
	}
	return $description;
}, 10, 2 );

/* ---------- Reorder for any finished or pending order ---------- */

add_filter( 'woocommerce_valid_order_statuses_for_order_again', function () {
	return array( 'completed', 'processing', 'on-hold', 'cancelled', 'failed', 'refunded' );
} );

/* ---------- Cart: cross-sells under the cart, 4 columns ---------- */

add_filter( 'woocommerce_cross_sells_columns', function () {
	return 4;
} );
add_filter( 'woocommerce_cross_sells_total', function () {
	return 4;
} );
add_filter( 'woocommerce_product_cross_sells_products_heading', function () {
	return __( 'شاید به این‌ها هم نیاز داشته باشید', 'faucets-valves' );
} );
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display' );

/* ---------- Assets ---------- */

add_action( 'wp_enqueue_scripts', function () {
	$on = is_cart() || is_checkout() || is_account_page() || is_page_template( 'page-templates/quick-order.php' );
	if ( $on ) {
		$rel = 'assets/css/cart-checkout.css';
		wp_enqueue_style( 'fv-cart-checkout', FV_URI . "/{$rel}", array( 'fv-woocommerce' ), fv_asset_ver( $rel ) );
		$rel = 'assets/js/ui/cart.js';
		wp_enqueue_script( 'fv-cart', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	if ( is_cart() || is_account_page() ) {
		// Quantity steppers in the cart.
		$rel = 'assets/js/ui/product.js';
		wp_enqueue_script( 'fv-product', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}, 20 );
