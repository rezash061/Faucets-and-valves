<?php
/**
 * Blog helpers.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Posts sharing a category with the given post, newest first; falls back to latest posts.
 *
 * @param int $post_id Post ID.
 * @param int $count   How many.
 * @return WP_Query
 */
function fv_related_posts( $post_id, $count = 3 ) {
	$cats = wp_get_post_categories( $post_id );
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => $count,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $cats ) {
		$args['category__in'] = $cats;
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() && $cats ) {
		unset( $args['category__in'] );
		$q = new WP_Query( $args );
	}
	return $q;
}

/**
 * Shorter automatic excerpts, Persian ellipsis.
 */
add_filter( 'excerpt_length', function () {
	return 28;
} );
add_filter( 'excerpt_more', function () {
	return '…';
} );
