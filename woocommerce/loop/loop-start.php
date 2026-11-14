<?php
/**
 * Product loop start. The list carries Staggered Entrance.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;
?>
<ul class="products product-grid columns-<?php echo esc_attr( wc_get_loop_prop( 'columns' ) ); ?>" data-motion="stagger">
