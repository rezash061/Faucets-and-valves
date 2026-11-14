<?php
/**
 * Block pattern category. Patterns themselves live in /patterns and register automatically.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_block_pattern_category( 'faucets-valves', array( 'label' => __( 'شیرآلات — نمایشگاه', 'faucets-valves' ) ) );
} );
