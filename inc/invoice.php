<?php
/**
 * Printable proforma invoice (from the cart) and invoice (per order).
 *
 *   Proforma:  /?fv-invoice=proforma
 *   Invoice:   /?fv-invoice=order&order=123&key=wc_order_xxx
 *              (order key, or the logged-in owner, or a shop manager)
 *
 * Seller details: Customizer › تنظیمات قالب شیرآلات › فاکتور و پیش‌فاکتور.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Helpers: Jalali date, Persian digits, number to words ---------- */

/**
 * Gregorian → Jalali.
 *
 * @return int[] [year, month, day]
 */
function fv_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
	$days %= 12053;
	$jy   += 4 * intdiv( $days, 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy  += intdiv( $days - 1, 365 );
		$days = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + intdiv( $days, 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + intdiv( $days - 186, 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * Jalali date string (۱۴۰۵/۰۸/۲۳) for a timestamp, in the site's time zone.
 */
function fv_jalali_date( $timestamp = null ) {
	$dt = ( new DateTimeImmutable( '@' . ( $timestamp ? $timestamp : time() ) ) )->setTimezone( wp_timezone() );
	list( $y, $m, $d ) = fv_gregorian_to_jalali( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	return fv_fa_digits( sprintf( '%04d/%02d/%02d', $y, $m, $d ) );
}

/**
 * Latin digits → Persian digits.
 */
function fv_fa_digits( $value ) {
	return strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/**
 * Integer → Persian words (e.g. 4850000 → «چهار میلیون و هشتصد و پنجاه هزار»).
 */
function fv_number_to_words( $number ) {
	$number = (int) round( (float) $number );
	if ( 0 === $number ) {
		return 'صفر';
	}
	$ones   = array( '', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه' );
	$teens  = array( 'ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده' );
	$tens   = array( '', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود' );
	$hund   = array( '', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد' );
	$scales = array( '', 'هزار', 'میلیون', 'میلیارد', 'هزار میلیارد' );

	$three = function ( $n ) use ( $ones, $teens, $tens, $hund ) {
		$parts = array();
		$h     = intdiv( $n, 100 );
		$rest  = $n % 100;
		if ( $h ) {
			$parts[] = $hund[ $h ];
		}
		if ( $rest >= 10 && $rest < 20 ) {
			$parts[] = $teens[ $rest - 10 ];
		} else {
			if ( intdiv( $rest, 10 ) ) {
				$parts[] = $tens[ intdiv( $rest, 10 ) ];
			}
			if ( $rest % 10 ) {
				$parts[] = $ones[ $rest % 10 ];
			}
		}
		return implode( ' و ', $parts );
	};

	$negative = $number < 0;
	$number   = abs( $number );
	$groups   = array();
	$i        = 0;
	while ( $number > 0 && $i < count( $scales ) ) {
		$chunk = $number % 1000;
		if ( $chunk ) {
			// «هزار», not «یک هزار».
			$groups[] = ( 1 === $chunk && 1 === $i ) ? $scales[ $i ] : trim( $three( $chunk ) . ' ' . $scales[ $i ] );
		}
		$number = intdiv( $number, 1000 );
		$i++;
	}
	$words = implode( ' و ', array_reverse( $groups ) );
	return ( $negative ? 'منفی ' : '' ) . $words;
}

/**
 * Plain currency label (e.g. تومان) without HTML.
 */
function fv_currency_label() {
	return trim( wp_strip_all_tags( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ) );
}

/**
 * Amount as Persian-digit number with thousands separators.
 */
function fv_money( $amount ) {
	return fv_fa_digits( number_format( round( (float) $amount, wc_get_price_decimals() ), wc_get_price_decimals(), '.', '٬' ) );
}

/* ---------- Seller details in the Customizer ---------- */

add_filter( 'fv_defaults', function ( $d ) {
	return array_merge( $d, array(
		'fv_seller_name'     => get_bloginfo( 'name' ),
		'fv_seller_national' => '',
		'fv_seller_economic' => '',
		'fv_seller_address'  => '',
		'fv_seller_postcode' => '',
		'fv_seller_phone'    => '',
		'fv_invoice_valid'   => 3,
		'fv_invoice_note'    => 'قیمت‌ها بر اساس موجودی و نرخ روز است و پس از پایان اعتبار ممکن است تغییر کند.',
	) );
} );

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = fv_defaults();
	$wp->add_section( 'fv_invoice', array(
		'title'       => __( 'فاکتور و پیش‌فاکتور', 'faucets-valves' ),
		'description' => __( 'اطلاعات فروشنده که در سربرگ فاکتور و پیش‌فاکتور چاپ می‌شود.', 'faucets-valves' ),
		'panel'       => 'fv_panel',
	) );
	$fields = array(
		'fv_seller_name'     => array( __( 'نام فروشنده / شرکت', 'faucets-valves' ), 'text' ),
		'fv_seller_national' => array( __( 'شناسه ملی', 'faucets-valves' ), 'text' ),
		'fv_seller_economic' => array( __( 'کد اقتصادی', 'faucets-valves' ), 'text' ),
		'fv_seller_address'  => array( __( 'نشانی', 'faucets-valves' ), 'textarea' ),
		'fv_seller_postcode' => array( __( 'کد پستی', 'faucets-valves' ), 'text' ),
		'fv_seller_phone'    => array( __( 'تلفن', 'faucets-valves' ), 'text' ),
		'fv_invoice_valid'   => array( __( 'اعتبار پیش‌فاکتور (روز)', 'faucets-valves' ), 'number' ),
		'fv_invoice_note'    => array( __( 'توضیح پایین پیش‌فاکتور', 'faucets-valves' ), 'textarea' ),
	);
	foreach ( $fields as $id => list( $label, $type ) ) {
		$wp->add_setting( $id, array(
			'default'           => $d[ $id ],
			'sanitize_callback' => 'number' === $type ? 'absint' : 'sanitize_textarea_field',
		) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_invoice', 'type' => $type ) );
	}
} );

/* ---------- URLs ---------- */

function fv_proforma_url() {
	return add_query_arg( 'fv-invoice', 'proforma', home_url( '/' ) );
}

/**
 * @param WC_Order $order Order.
 */
function fv_order_invoice_url( $order ) {
	return add_query_arg( array(
		'fv-invoice' => 'order',
		'order'      => $order->get_id(),
		'key'        => $order->get_order_key(),
	), home_url( '/' ) );
}

/* ---------- Data builders ---------- */

/**
 * Invoice data from the current cart.
 */
function fv_invoice_from_cart() {
	$cart  = WC()->cart;
	$items = array();
	foreach ( $cart->get_cart() as $item ) {
		$product = $item['data'];
		if ( ! $product ) {
			continue;
		}
		$qty     = (float) $item['quantity'];
		$line    = $cart->display_prices_including_tax() ? $item['line_subtotal'] + $item['line_subtotal_tax'] : $item['line_subtotal'];
		$name    = $product->get_name();
		$meta    = wp_strip_all_tags( wc_get_formatted_cart_item_data( $item, true ) );
		$items[] = array(
			'code'  => $product->get_sku(),
			'name'  => $meta ? $name . ' — ' . preg_replace( '/\s+/', ' ', $meta ) : $name,
			'qty'   => $qty,
			'unit'  => $qty ? $line / $qty : 0,
			'total' => $line,
		);
	}
	$customer = WC()->customer;
	$hash     = substr( md5( wp_json_encode( $items ) ), 0, 4 );
	list( $jy, $jm, $jd ) = fv_gregorian_to_jalali( (int) wp_date( 'Y' ), (int) wp_date( 'n' ), (int) wp_date( 'j' ) );

	return array(
		'type'     => 'proforma',
		'title'    => __( 'پیش‌فاکتور', 'faucets-valves' ),
		'number'   => sprintf( 'PF-%04d%02d%02d-%s', $jy, $jm, $jd, strtoupper( $hash ) ),
		'date'     => time(),
		'buyer'    => array(
			'name'     => $customer ? trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() ) : '',
			'company'  => $customer ? $customer->get_billing_company() : '',
			'national' => $customer ? (string) get_user_meta( $customer->get_id(), 'billing_fv_national_id', true ) : '',
			'phone'    => $customer ? $customer->get_billing_phone() : '',
			'address'  => $customer ? trim( implode( '، ', array_filter( array( WC()->countries->get_states( 'IR' )[ $customer->get_billing_state() ] ?? $customer->get_billing_state(), $customer->get_billing_city(), $customer->get_billing_address_1() ) ) ) ) : '',
		),
		'editable' => true,
		'items'    => $items,
		'subtotal' => (float) $cart->get_subtotal() + ( $cart->display_prices_including_tax() ? (float) $cart->get_subtotal_tax() : 0 ),
		'discount' => (float) $cart->get_discount_total(),
		'shipping' => null, // Chosen at checkout.
		'tax'      => $cart->display_prices_including_tax() ? 0 : (float) $cart->get_total_tax(),
		'total'    => (float) $cart->get_total( 'edit' ),
		'valid'    => (int) fv_mod( 'fv_invoice_valid' ),
		'note'     => fv_mod( 'fv_invoice_note' ),
		'payment'  => '',
		'status'   => '',
	);
}

/**
 * Invoice data from an order.
 *
 * @param WC_Order $order Order.
 */
function fv_invoice_from_order( $order ) {
	$items = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$qty     = (float) $item->get_quantity();
		$line    = (float) $order->get_line_subtotal( $item, $order->get_prices_include_tax() || 'incl' === get_option( 'woocommerce_tax_display_cart' ), false );
		$meta    = wp_strip_all_tags( wc_display_item_meta( $item, array( 'echo' => false, 'separator' => '، ', 'before' => '', 'after' => '', 'autop' => false ) ) );
		$items[] = array(
			'code'  => $product ? $product->get_sku() : '',
			'name'  => $meta ? $item->get_name() . ' — ' . $meta : $item->get_name(),
			'qty'   => $qty,
			'unit'  => $qty ? $line / $qty : 0,
			'total' => $line,
		);
	}
	$states = WC()->countries->get_states( $order->get_billing_country() ? $order->get_billing_country() : 'IR' );
	$state  = $states[ $order->get_billing_state() ] ?? $order->get_billing_state();
	$incl   = 'incl' === get_option( 'woocommerce_tax_display_cart' );
	return array(
		'type'     => 'invoice',
		'title'    => $order->is_paid() ? __( 'فاکتور فروش', 'faucets-valves' ) : __( 'پیش‌فاکتور سفارش', 'faucets-valves' ),
		'number'   => $order->get_order_number(),
		'date'     => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time(),
		'buyer'    => array(
			'name'     => $order->get_formatted_billing_full_name(),
			'company'  => $order->get_billing_company(),
			'national' => (string) $order->get_meta( '_billing_fv_national_id' ),
			'phone'    => $order->get_billing_phone(),
			'address'  => trim( implode( '، ', array_filter( array( $state, $order->get_billing_city(), $order->get_billing_address_1(), $order->get_billing_postcode() ? 'کد پستی ' . $order->get_billing_postcode() : '' ) ) ) ),
		),
		'editable' => false,
		'items'    => $items,
		'subtotal' => (float) $order->get_subtotal() + ( $incl ? (float) $order->get_cart_tax() : 0 ),
		'discount' => (float) $order->get_discount_total(),
		'shipping' => (float) $order->get_shipping_total() + ( $incl ? (float) $order->get_shipping_tax() : 0 ),
		'tax'      => $incl ? 0 : (float) $order->get_total_tax(),
		'total'    => (float) $order->get_total(),
		'valid'    => 0,
		'note'     => '',
		'payment'  => $order->get_payment_method_title(),
		'status'   => wc_get_order_status_name( $order->get_status() ),
	);
}

/* ---------- Route + render ---------- */

add_action( 'template_redirect', function () {
	if ( empty( $_GET['fv-invoice'] ) || ! function_exists( 'WC' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$type = sanitize_key( wp_unslash( $_GET['fv-invoice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );

	if ( 'proforma' === $type ) {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
		WC()->cart->calculate_totals();
		fv_render_invoice( fv_invoice_from_cart() );
		exit;
	}

	if ( 'order' === $type ) {
		$order = wc_get_order( absint( $_GET['order'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$ok    = $order && (
			( $key && hash_equals( $order->get_order_key(), $key ) )
			|| ( is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id() )
			|| current_user_can( 'edit_shop_orders' )
		);
		if ( ! $ok ) {
			wp_die( esc_html__( 'این فاکتور در دسترس نیست.', 'faucets-valves' ), '', array( 'response' => 403 ) );
		}
		fv_render_invoice( fv_invoice_from_order( $order ) );
		exit;
	}
}, 5 );

/**
 * Print the standalone invoice page.
 *
 * @param array $inv Invoice data.
 */
function fv_render_invoice( $inv ) {
	$cur     = fv_currency_label();
	$seller  = array(
		'name'     => fv_mod( 'fv_seller_name' ),
		'national' => fv_mod( 'fv_seller_national' ),
		'economic' => fv_mod( 'fv_seller_economic' ),
		'address'  => fv_mod( 'fv_seller_address' ),
		'postcode' => fv_mod( 'fv_seller_postcode' ),
		'phone'    => fv_mod( 'fv_seller_phone' ) ? fv_mod( 'fv_seller_phone' ) : fv_mod( 'fv_phone' ),
	);
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$edit    = $inv['editable'] ? ' contenteditable="true" spellcheck="false"' : '';
	$back    = 'proforma' === $inv['type'] ? wc_get_cart_url() : wc_get_account_endpoint_url( 'orders' );
	?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $inv['title'] . ' ' . $inv['number'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( FV_URI . '/assets/css/invoice.css?ver=' . fv_asset_ver( 'assets/css/invoice.css' ) ); ?>">
<style>
@font-face{font-family:"Estedad";src:url("<?php echo esc_url( FV_URI . '/assets/fonts/Estedad.woff2' ); ?>") format("woff2");font-weight:100 900;font-display:swap}
@font-face{font-family:"FV Digits";src:url("<?php echo esc_url( FV_URI . '/assets/fonts/EstedadDigits.woff2' ); ?>") format("woff2");font-weight:100 900;unicode-range:U+0030-0039}
</style>
</head>
<body class="invoice-<?php echo esc_attr( $inv['type'] ); ?>">
<div class="toolbar no-print">
	<a href="<?php echo esc_url( $back ); ?>">→ <?php esc_html_e( 'بازگشت', 'faucets-valves' ); ?></a>
	<?php if ( $inv['editable'] ) : ?>
		<span class="toolbar__hint"><?php esc_html_e( 'می‌توانید مشخصات خریدار را پیش از چاپ روی همین صفحه بنویسید.', 'faucets-valves' ); ?></span>
	<?php endif; ?>
	<button type="button" onclick="window.print()"><?php esc_html_e( 'چاپ یا ذخیرهٔ PDF', 'faucets-valves' ); ?></button>
</div>

<main class="sheet">
	<header class="inv-head">
		<div class="inv-brand">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'inv-logo', 'alt' => '' ) ); ?>
			<?php else : ?>
				<b class="inv-brand__name"><?php echo esc_html( $seller['name'] ); ?></b>
			<?php endif; ?>
		</div>
		<h1 class="inv-title"><?php echo esc_html( $inv['title'] ); ?></h1>
		<dl class="inv-meta">
			<div><dt><?php esc_html_e( 'شماره', 'faucets-valves' ); ?></dt><dd dir="ltr"><?php echo esc_html( fv_fa_digits( $inv['number'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'تاریخ', 'faucets-valves' ); ?></dt><dd><?php echo esc_html( fv_jalali_date( $inv['date'] ) ); ?></dd></div>
			<?php if ( $inv['status'] ) : ?>
				<div><dt><?php esc_html_e( 'وضعیت', 'faucets-valves' ); ?></dt><dd><?php echo esc_html( $inv['status'] ); ?></dd></div>
			<?php endif; ?>
		</dl>
	</header>

	<section class="party">
		<h2><?php esc_html_e( 'مشخصات فروشنده', 'faucets-valves' ); ?></h2>
		<div class="party__grid">
			<p><span><?php esc_html_e( 'نام:', 'faucets-valves' ); ?></span> <?php echo esc_html( $seller['name'] ); ?></p>
			<p><span><?php esc_html_e( 'شناسه ملی:', 'faucets-valves' ); ?></span> <?php echo esc_html( fv_fa_digits( $seller['national'] ) ); ?></p>
			<p><span><?php esc_html_e( 'کد اقتصادی:', 'faucets-valves' ); ?></span> <?php echo esc_html( fv_fa_digits( $seller['economic'] ) ); ?></p>
			<p><span><?php esc_html_e( 'تلفن:', 'faucets-valves' ); ?></span> <?php echo esc_html( fv_fa_digits( $seller['phone'] ) ); ?></p>
			<p class="wide"><span><?php esc_html_e( 'نشانی:', 'faucets-valves' ); ?></span> <?php echo esc_html( fv_fa_digits( $seller['address'] ) ); ?><?php echo $seller['postcode'] ? ' — ' . esc_html__( 'کد پستی', 'faucets-valves' ) . ' ' . esc_html( fv_fa_digits( $seller['postcode'] ) ) : ''; ?></p>
		</div>
	</section>

	<section class="party">
		<h2><?php esc_html_e( 'مشخصات خریدار', 'faucets-valves' ); ?></h2>
		<div class="party__grid">
			<p><span><?php esc_html_e( 'نام:', 'faucets-valves' ); ?></span> <b class="fill"<?php echo $edit; // phpcs:ignore ?>><?php echo esc_html( $inv['buyer']['name'] ); ?></b></p>
			<p><span><?php esc_html_e( 'شرکت / فروشگاه:', 'faucets-valves' ); ?></span> <b class="fill"<?php echo $edit; // phpcs:ignore ?>><?php echo esc_html( $inv['buyer']['company'] ); ?></b></p>
			<p><span><?php esc_html_e( 'شناسه ملی / کد اقتصادی:', 'faucets-valves' ); ?></span> <b class="fill"<?php echo $edit; // phpcs:ignore ?>><?php echo esc_html( fv_fa_digits( $inv['buyer']['national'] ) ); ?></b></p>
			<p><span><?php esc_html_e( 'تلفن:', 'faucets-valves' ); ?></span> <b class="fill"<?php echo $edit; // phpcs:ignore ?>><?php echo esc_html( fv_fa_digits( $inv['buyer']['phone'] ) ); ?></b></p>
			<p class="wide"><span><?php esc_html_e( 'نشانی:', 'faucets-valves' ); ?></span> <b class="fill"<?php echo $edit; // phpcs:ignore ?>><?php echo esc_html( fv_fa_digits( $inv['buyer']['address'] ) ); ?></b></p>
		</div>
	</section>

	<table class="items">
		<thead>
			<tr>
				<th class="n"><?php esc_html_e( 'ردیف', 'faucets-valves' ); ?></th>
				<th class="code"><?php esc_html_e( 'کد کالا', 'faucets-valves' ); ?></th>
				<th><?php esc_html_e( 'شرح کالا', 'faucets-valves' ); ?></th>
				<th class="num"><?php esc_html_e( 'تعداد', 'faucets-valves' ); ?></th>
				<th class="num">
					<?php
					/* translators: %s: currency */
					echo esc_html( sprintf( __( 'قیمت واحد (%s)', 'faucets-valves' ), $cur ) );
					?>
				</th>
				<th class="num">
					<?php
					/* translators: %s: currency */
					echo esc_html( sprintf( __( 'مبلغ کل (%s)', 'faucets-valves' ), $cur ) );
					?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $inv['items'] as $i => $row ) : ?>
				<tr>
					<td class="n"><?php echo esc_html( fv_fa_digits( $i + 1 ) ); ?></td>
					<td class="code" dir="ltr"><?php echo esc_html( $row['code'] ); ?></td>
					<td><?php echo esc_html( $row['name'] ); ?></td>
					<td class="num"><?php echo esc_html( fv_fa_digits( wc_stock_amount( $row['qty'] ) ) ); ?></td>
					<td class="num"><?php echo esc_html( fv_money( $row['unit'] ) ); ?></td>
					<td class="num"><?php echo esc_html( fv_money( $row['total'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="summary">
		<div class="summary__words">
			<p class="label"><?php esc_html_e( 'مبلغ قابل پرداخت به حروف:', 'faucets-valves' ); ?></p>
			<p class="words"><?php echo esc_html( fv_number_to_words( $inv['total'] ) . ' ' . $cur ); ?></p>
			<?php if ( $inv['payment'] ) : ?>
				<p class="label"><?php esc_html_e( 'روش پرداخت:', 'faucets-valves' ); ?> <b><?php echo esc_html( $inv['payment'] ); ?></b></p>
			<?php endif; ?>
		</div>
		<table class="totals">
			<tr><th><?php esc_html_e( 'جمع کالاها', 'faucets-valves' ); ?></th><td><?php echo esc_html( fv_money( $inv['subtotal'] ) ); ?></td></tr>
			<?php if ( $inv['discount'] > 0 ) : ?>
				<tr><th><?php esc_html_e( 'تخفیف', 'faucets-valves' ); ?></th><td>− <?php echo esc_html( fv_money( $inv['discount'] ) ); ?></td></tr>
			<?php endif; ?>
			<tr>
				<th><?php esc_html_e( 'هزینهٔ ارسال', 'faucets-valves' ); ?></th>
				<td><?php echo null === $inv['shipping'] ? esc_html__( 'در مرحلهٔ ثبت سفارش', 'faucets-valves' ) : esc_html( fv_money( $inv['shipping'] ) ); ?></td>
			</tr>
			<?php if ( $inv['tax'] > 0 ) : ?>
				<tr><th><?php esc_html_e( 'مالیات بر ارزش افزوده', 'faucets-valves' ); ?></th><td><?php echo esc_html( fv_money( $inv['tax'] ) ); ?></td></tr>
			<?php endif; ?>
			<tr class="grand"><th><?php esc_html_e( 'مبلغ قابل پرداخت', 'faucets-valves' ); ?></th><td><?php echo esc_html( fv_money( $inv['total'] ) ); ?> <small><?php echo esc_html( $cur ); ?></small></td></tr>
		</table>
	</div>

	<?php if ( $inv['valid'] || $inv['note'] ) : ?>
		<section class="notes">
			<?php if ( $inv['valid'] ) : ?>
				<p>
					<?php
					/* translators: %s: days */
					echo esc_html( sprintf( __( 'اعتبار این پیش‌فاکتور %s روز از تاریخ صدور است.', 'faucets-valves' ), fv_fa_digits( $inv['valid'] ) ) );
					?>
				</p>
			<?php endif; ?>
			<?php if ( $inv['note'] ) : ?>
				<p><?php echo esc_html( $inv['note'] ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<footer class="signs">
		<div><?php esc_html_e( 'مهر و امضای فروشنده', 'faucets-valves' ); ?></div>
		<div><?php esc_html_e( 'مهر و امضای خریدار', 'faucets-valves' ); ?></div>
	</footer>
</main>
</body>
</html>
	<?php
}

/* ---------- Buttons ---------- */

/**
 * Proforma + continue shopping under the cart totals.
 */
add_action( 'woocommerce_proceed_to_checkout', function () {
	printf(
		'<a class="btn btn--ghost proforma-link" href="%1$s" target="_blank" rel="noopener">%2$s%3$s</a>',
		esc_url( fv_proforma_url() ),
		fv_icon( 'document' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'دریافت پیش‌فاکتور', 'faucets-valves' )
	);
	printf(
		'<a class="continue-link" href="%1$s">%2$s</a>',
		esc_url( wc_get_page_permalink( 'shop' ) ),
		esc_html__( 'ادامهٔ خرید', 'faucets-valves' )
	);
}, 30 );

/**
 * "Invoice" and "Reorder" actions in My account › Orders.
 */
add_filter( 'woocommerce_my_account_my_orders_actions', function ( $actions, $order ) {
	$actions = array_merge(
		array(
			'fv-invoice' => array(
				'url'  => fv_order_invoice_url( $order ),
				'name' => __( 'فاکتور', 'faucets-valves' ),
			),
		),
		$actions
	);
	if ( in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ), true ) && $order->get_item_count() ) {
		$actions['fv-reorder'] = array(
			'url'  => wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' ),
			'name' => __( 'سفارش مجدد', 'faucets-valves' ),
		);
	}
	return $actions;
}, 10, 2 );

/**
 * Invoice link in the admin order screen.
 */
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ) {
	printf(
		'<p class="form-field form-field-wide"><a class="button" href="%1$s" target="_blank">%2$s</a></p>',
		esc_url( fv_order_invoice_url( $order ) ),
		esc_html__( 'چاپ فاکتور', 'faucets-valves' )
	);
} );
