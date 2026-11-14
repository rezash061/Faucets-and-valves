<?php
/**
 * My account: Persian menu labels, quick-order link, dashboard data.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$labels = array(
		'dashboard'       => __( 'پیشخوان', 'faucets-valves' ),
		'orders'          => __( 'سفارش‌ها', 'faucets-valves' ),
		'downloads'       => __( 'دانلودها', 'faucets-valves' ),
		'edit-address'    => __( 'نشانی‌ها', 'faucets-valves' ),
		'payment-methods' => __( 'روش‌های پرداخت', 'faucets-valves' ),
		'edit-account'    => __( 'اطلاعات حساب', 'faucets-valves' ),
		'customer-logout' => __( 'خروج', 'faucets-valves' ),
	);
	foreach ( $labels as $key => $label ) {
		if ( isset( $items[ $key ] ) ) {
			$items[ $key ] = $label;
		}
	}
	unset( $items['downloads'] ); // Physical goods only.

	if ( fv_page_url_by_template( 'page-templates/quick-order.php' ) ) {
		$logout = $items['customer-logout'] ?? null;
		unset( $items['customer-logout'] );
		$items['fv-quick-order'] = __( 'سفارش سریع', 'faucets-valves' );
		if ( $logout ) {
			$items['customer-logout'] = $logout;
		}
	}
	return $items;
}, 20 );

add_filter( 'woocommerce_get_endpoint_url', function ( $url, $endpoint ) {
	if ( 'fv-quick-order' === $endpoint ) {
		return fv_page_url_by_template( 'page-templates/quick-order.php' );
	}
	return $url;
}, 10, 2 );

/**
 * Dashboard numbers for the current customer.
 *
 * @return array
 */
function fv_account_summary() {
	$user_id = get_current_user_id();
	$orders  = wc_get_orders( array(
		'customer_id' => $user_id,
		'limit'       => -1,
		'return'      => 'ids',
		'status'      => array_keys( wc_get_order_statuses() ),
	) );
	$awaiting = wc_get_orders( array(
		'customer_id'    => $user_id,
		'limit'          => 5,
		'status'         => array( 'on-hold', 'pending' ),
		'payment_method' => 'bacs',
	) );
	$awaiting = array_filter( $awaiting, function ( $o ) {
		return ! $o->get_meta( '_fv_receipt' );
	} );
	$last = wc_get_orders( array( 'customer_id' => $user_id, 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC' ) );
	$customer = new WC_Customer( $user_id );
	return array(
		'count'    => count( $orders ),
		'spent'    => (float) $customer->get_total_spent(),
		'awaiting' => array_values( $awaiting ),
		'last'     => $last ? $last[0] : null,
	);
}
