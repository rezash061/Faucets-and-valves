<?php
/**
 * Front page: the 3D house. Each room is a product category (see inc/scene.php).
 *
 * Without 3D the rooms are a plain list of category links. With 3D
 * (assets/js/scene/home.js) the names float over the model; picking a room colours it, moves
 * the camera in and labels its faucets and valves, each label linking to its products.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$rooms = function_exists( 'fv_house_rooms' ) ? fv_house_rooms() : array();
if ( ! $rooms ) {
	return;
}
?>
<section class="fv-house surface-plaster" id="fv-house" aria-labelledby="fv-scene-title">
	<canvas class="fv-house__canvas" aria-hidden="true"></canvas>

	<ul class="fv-rooms" aria-label="<?php esc_attr_e( 'دسته‌های محصولات', 'faucets-valves' ); ?>">
		<?php foreach ( $rooms as $r ) : ?>
			<li>
				<a class="rlabel" href="<?php echo esc_url( $r['url'] ); ?>" data-key="<?php echo esc_attr( $r['key'] ); ?>" aria-expanded="false" aria-controls="fv-roomcard">
					<b><?php echo esc_html( $r['name'] ); ?></b>
					<span><?php echo esc_html( $r['meta'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="fv-spots"></div>

	<div class="titleblock">
		<div class="tb-main">
			<h1 id="fv-scene-title"><?php echo fv_split_words( fv_mod( 'fv_scene_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></h1>
			<?php if ( fv_mod( 'fv_scene_lead' ) ) : ?>
				<p><?php echo esc_html( fv_mod( 'fv_scene_lead' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<?php if ( fv_mod( "fv_scene_cell_{$i}_v" ) ) : ?>
				<div class="tb-cell"><?php echo esc_html( fv_mod( "fv_scene_cell_{$i}_l" ) ); ?><b><?php echo esc_html( fv_mod( "fv_scene_cell_{$i}_v" ) ); ?></b></div>
			<?php endif; ?>
		<?php endfor; ?>
	</div>

	<aside class="fv-roomcard" id="fv-roomcard" hidden aria-live="polite">
		<h2 class="fv-roomcard__title"></h2>
		<p class="fv-roomcard__meta"></p>
		<p class="fv-roomcard__hint"><?php esc_html_e( 'روی برچسب هر شیر بزنید تا محصولاتش را ببینید.', 'faucets-valves' ); ?></p>
		<div class="fv-roomcard__row">
			<a class="pill fv-roomcard__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'همهٔ محصولات این دسته', 'faucets-valves' ); ?></a>
			<button class="pill pill--ghost fv-roomcard__back" type="button"><?php esc_html_e( 'بازگشت به خانه', 'faucets-valves' ); ?></button>
		</div>
	</aside>
</section>
