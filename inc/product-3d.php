<?php
/**
 * Product page 3D viewer: a working valve between two glass pipe sections.
 *
 * Which model a product shows:
 *  - Product › Product data › General › «مدل سه‌بعدی» (auto, none or a valve type);
 *  - auto = the model set on its category (Products › Categories › «مدل سه‌بعدی محصولات»).
 * The viewer script (assets/js/scene/product.js) loads only when the section nears the screen.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

function fv_model_choices( $with_auto = false ) {
	$c = array(
		''       => $with_auto ? __( 'خودکار (از دسته)', 'faucets-valves' ) : __( 'بدون مدل', 'faucets-valves' ),
		'none'   => __( 'بدون مدل', 'faucets-valves' ),
	);
	if ( ! $with_auto ) {
		unset( $c['none'] );
	}
	foreach ( fv_valve_kinds() as $k => $label ) {
		$c[ $k ] = $label;
	}
	return $c;
}

/**
 * Model for a product: faucet | ball | gate | globe | check, or '' for none.
 */
function fv_product_model( $product ) {
	$model = (string) $product->get_meta( '_fv_model' );
	if ( 'none' === $model ) {
		return '';
	}
	if ( '' === $model ) {
		foreach ( $product->get_category_ids() as $cat_id ) {
			$m = (string) get_term_meta( $cat_id, 'fv_model', true );
			if ( '' !== $m ) {
				$model = $m;
				break;
			}
		}
	}
	return array_key_exists( $model, fv_model_choices() ) && '' !== $model ? $model : '';
}

/* ---------- Category field ---------- */

add_action( 'product_cat_add_form_fields', function () {
	?>
	<div class="form-field">
		<label for="fv_model"><?php esc_html_e( 'مدل سه‌بعدی محصولات', 'faucets-valves' ); ?></label>
		<select name="fv_model" id="fv_model">
			<?php foreach ( fv_model_choices() as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><?php esc_html_e( 'در صفحهٔ هر محصول این دسته نمایش داده می‌شود.', 'faucets-valves' ); ?></p>
	</div>
	<?php
}, 20 );

add_action( 'product_cat_edit_form_fields', function ( $term ) {
	$cur = get_term_meta( $term->term_id, 'fv_model', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fv_model"><?php esc_html_e( 'مدل سه‌بعدی محصولات', 'faucets-valves' ); ?></label></th>
		<td>
			<select name="fv_model" id="fv_model">
				<?php foreach ( fv_model_choices() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $cur, $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'در صفحهٔ هر محصول این دسته نمایش داده می‌شود؛ هر محصول می‌تواند آن را تغییر دهد.', 'faucets-valves' ); ?></p>
		</td>
	</tr>
	<?php
}, 20 );

$fv_save_cat_model = function ( $term_id ) {
	if ( ! isset( $_POST['fv_cat_scene_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fv_cat_scene_nonce'] ), 'fv_cat_scene' ) || ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	$m = isset( $_POST['fv_model'] ) ? sanitize_key( $_POST['fv_model'] ) : '';
	if ( $m && array_key_exists( $m, fv_model_choices() ) ) {
		update_term_meta( $term_id, 'fv_model', $m );
	} else {
		delete_term_meta( $term_id, 'fv_model' );
	}
};
add_action( 'created_product_cat', $fv_save_cat_model );
add_action( 'edited_product_cat', $fv_save_cat_model );

/* ---------- Product fields (Product data › General) ---------- */

add_action( 'woocommerce_product_options_general_product_data', function () {
	echo '<div class="options_group">';
	woocommerce_wp_select( array(
		'id'          => '_fv_model',
		'label'       => __( 'مدل سه‌بعدی', 'faucets-valves' ),
		'options'     => fv_model_choices( true ),
		'desc_tip'    => true,
		'description' => __( 'نمای سه‌بعدی در صفحهٔ محصول: شیری که باز و بسته می‌شود و جریان آب را نشان می‌دهد.', 'faucets-valves' ),
	) );
	echo '</div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product nonce.
	if ( isset( $_POST['_fv_model'] ) ) {
		$m = sanitize_key( $_POST['_fv_model'] );
		$product->update_meta_data( '_fv_model', array_key_exists( $m, fv_model_choices( true ) ) ? $m : '' );
	}
	// phpcs:enable
} );

/* ---------- Section ---------- */

/**
 * Section title for a model.
 */
function fv_product_3d_title( $model ) {
	return __( 'طرز کار', 'faucets-valves' );
}

/**
 * Renders the viewer section (called from fv_product_sections()).
 */
function fv_product_3d_section( $product, $model ) {
	$how       = array(
		'ball'  => __( 'شیر توپی با یک چرخش ربع‌دور باز و بسته می‌شود: سوراخِ توپِ داخل بدنه یا هم‌راستای لوله است یا عمود بر آن.', 'faucets-valves' ),
		'gate'  => __( 'با چرخاندن فلکهٔ شیر کشویی، دریچه‌ای از مسیر آب بالا می‌رود؛ در حالت باز مسیر کاملاً آزاد است و فشار کم نمی‌شود.', 'faucets-valves' ),
		'globe' => __( 'شیر بشقابی با پایین و بالا رفتن یک بشقاب روی نشیمنگاه، مقدار جریان را دقیق تنظیم می‌کند.', 'faucets-valves' ),
		'check' => __( 'شیر یک‌طرفه فقط در جهت پیکانِ روی بدنه آب را عبور می‌دهد؛ با فشار برگشتی، فنر آن را می‌بندد.', 'faucets-valves' ),
	);
	$src = FV_URI . '/assets/js/scene/product.js?ver=' . fv_asset_ver( 'assets/js/scene/product.js' );
	?>
	<section class="product-section product-3d product-3d--<?php echo esc_attr( $model ); ?>" id="product-model" aria-labelledby="product-model-title"
		data-model="<?php echo esc_attr( $model ); ?>" data-src="<?php echo esc_url( $src ); ?>">
		<h2 class="product-section__title" id="product-model-title"><?php echo esc_html( fv_product_3d_title( $model ) ); ?></h2>
		<p class="p3d__lead">
			<?php echo esc_html( $how[ $model ] . ' ' . __( 'شیر را باز و بسته کنید و برای چرخاندن روی تصویر بکشید.', 'faucets-valves' ) ); ?>
		</p>

		<div class="p3d">
			<div class="p3d__stage">
				<canvas class="p3d__canvas" aria-hidden="true"></canvas>
				<svg class="p3d__lines" aria-hidden="true"></svg>
				<div class="p3d__labels" aria-hidden="true"></div>
			</div>
			<div class="p3d__controls">
					<button class="pill p3d__toggle" type="button" aria-pressed="false"
						data-open="<?php esc_attr_e( 'باز کردن شیر', 'faucets-valves' ); ?>"
						data-close="<?php esc_attr_e( 'بستن شیر', 'faucets-valves' ); ?>"><?php esc_html_e( 'باز کردن شیر', 'faucets-valves' ); ?></button>
					<span class="p3d__status" role="status"
						data-open="<?php esc_attr_e( 'باز: آب به سمت خروجی جریان دارد.', 'faucets-valves' ); ?>"
						data-closed="<?php esc_attr_e( 'بسته: آب فقط در سمت ورودی است.', 'faucets-valves' ); ?>"><?php esc_html_e( 'بسته: آب فقط در سمت ورودی است.', 'faucets-valves' ); ?></span>
			</div>
		</div>

	</section>
	<script>(function(){var s=document.getElementById('product-model');if(!s||!('IntersectionObserver' in window))return;var io=new IntersectionObserver(function(e){if(!e[0].isIntersecting)return;io.disconnect();var t=document.createElement('script');t.src=s.getAttribute('data-src');t.async=true;document.body.appendChild(t);},{rootMargin:'600px'});io.observe(s);})();</script>
	<?php
}
