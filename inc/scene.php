<?php
/**
 * Front page 3D scenes: the pipe manifold (one branch per category) and the exploded faucet.
 *
 * - Products › Categories: each category gets a valve type (ball, gate, globe, check) and a short
 *   note (e.g. «اتصال ½ اینچ»), shown on its branch.
 * - Customizer › تنظیمات قالب شیرآلات › صحنهٔ صفحهٔ نخست: headline, title block, which categories
 *   become branches, and the product shown at the end of the exploded view.
 * - The 3D script (assets/js/scene/home.js) only upgrades the markup. Without WebGL, with data
 *   saving on, or without JavaScript, visitors get a static pipe diagram with category links.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valve types the manifold can draw, with their Persian names.
 */
function fv_valve_kinds() {
	return array(
		'ball'  => __( 'شیر توپی', 'faucets-valves' ),
		'gate'  => __( 'شیر کشویی', 'faucets-valves' ),
		'globe' => __( 'شیر بشقابی', 'faucets-valves' ),
		'check' => __( 'شیر یک‌طرفه', 'faucets-valves' ),
	);
}

/* ---------------------------------------------------------------------------
 * Category fields: valve type + branch note
 * ------------------------------------------------------------------------- */

add_action( 'product_cat_add_form_fields', function () {
	wp_nonce_field( 'fv_cat_scene', 'fv_cat_scene_nonce' );
	?>
	<div class="form-field">
		<label for="fv_valve"><?php esc_html_e( 'نوع شیر در صحنهٔ لوله‌کشی', 'faucets-valves' ); ?></label>
		<select name="fv_valve" id="fv_valve">
			<option value=""><?php esc_html_e( 'خودکار', 'faucets-valves' ); ?></option>
			<?php foreach ( fv_valve_kinds() as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="form-field">
		<label for="fv_branch_note"><?php esc_html_e( 'یادداشت کوتاه روی لوله', 'faucets-valves' ); ?></label>
		<input type="text" name="fv_branch_note" id="fv_branch_note" placeholder="<?php esc_attr_e( 'مثلاً: اتصال ½ اینچ', 'faucets-valves' ); ?>">
	</div>
	<?php
} );

add_action( 'product_cat_edit_form_fields', function ( $term ) {
	$valve = get_term_meta( $term->term_id, 'fv_valve', true );
	$note  = get_term_meta( $term->term_id, 'fv_branch_note', true );
	wp_nonce_field( 'fv_cat_scene', 'fv_cat_scene_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fv_valve"><?php esc_html_e( 'نوع شیر در صحنهٔ لوله‌کشی', 'faucets-valves' ); ?></label></th>
		<td>
			<select name="fv_valve" id="fv_valve">
				<option value=""><?php esc_html_e( 'خودکار', 'faucets-valves' ); ?></option>
				<?php foreach ( fv_valve_kinds() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $valve, $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'شیری که روی انشعاب این دسته در صفحهٔ نخست دیده می‌شود.', 'faucets-valves' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="fv_branch_note"><?php esc_html_e( 'یادداشت کوتاه روی لوله', 'faucets-valves' ); ?></label></th>
		<td><input type="text" name="fv_branch_note" id="fv_branch_note" value="<?php echo esc_attr( $note ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: اتصال ½ اینچ', 'faucets-valves' ); ?>"></td>
	</tr>
	<?php
} );

$fv_save_cat_scene = function ( $term_id ) {
	if ( ! isset( $_POST['fv_cat_scene_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fv_cat_scene_nonce'] ), 'fv_cat_scene' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	$valve = isset( $_POST['fv_valve'] ) ? sanitize_key( $_POST['fv_valve'] ) : '';
	if ( $valve && isset( fv_valve_kinds()[ $valve ] ) ) {
		update_term_meta( $term_id, 'fv_valve', $valve );
	} else {
		delete_term_meta( $term_id, 'fv_valve' );
	}
	$note = isset( $_POST['fv_branch_note'] ) ? sanitize_text_field( wp_unslash( $_POST['fv_branch_note'] ) ) : '';
	update_term_meta( $term_id, 'fv_branch_note', $note );
};
add_action( 'created_product_cat', $fv_save_cat_scene );
add_action( 'edited_product_cat', $fv_save_cat_scene );

/* ---------------------------------------------------------------------------
 * Customizer
 * ------------------------------------------------------------------------- */

add_filter( 'fv_defaults', function ( $d ) {
	return array_merge( $d, array(
		'fv_scene_title'     => 'برای دیدن محصولات، یک شیر را باز کنید',
		'fv_scene_lead'      => 'هر انشعاب یک دسته است؛ شیر هر انشعاب همان نوعی است که در آن دسته می‌فروشیم.',
		'fv_scene_cell_1_l'  => 'کالا',
		'fv_scene_cell_1_v'  => '۲٬۴۰۰ کد',
		'fv_scene_cell_2_l'  => 'ارسال',
		'fv_scene_cell_2_v'  => '۳۱ استان',
		'fv_scene_cell_3_l'  => 'قیمت',
		'fv_scene_cell_3_v'  => 'برای همه',
		'fv_scene_cats'      => '',
		'fv_anatomy_product' => 0,
		'fv_anatomy_t1'      => 'یک شیر، نُه قطعه',
		'fv_anatomy_p1'      => 'پیش از خرید ببینید هر شیر از چه ساخته شده است. پایین بکشید تا قطعه‌ها از هم جدا شوند.',
		'fv_anatomy_t2'      => 'بدنهٔ برنج، قلب سرامیکی',
		'fv_anatomy_p2'      => 'کارتریج سرامیکی ۳۵ میلی‌متری، بدنهٔ برنج ریخته‌گری با آبکاری کروم، پیچ نصب برنجی و شیلنگ استیل بافته با اتصال ½ اینچ.',
	) );
} );

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = fv_defaults();
	$wp->add_section( 'fv_scene', array(
		'title'       => __( 'صحنهٔ صفحهٔ نخست', 'faucets-valves' ),
		'description' => __( 'لوله‌کشی سه‌بعدی (یک انشعاب برای هر دسته) و کالبدشکافی شیر. نوع شیر هر دسته را در محصولات › دسته‌ها تعیین کنید.', 'faucets-valves' ),
		'panel'       => 'fv_panel',
		'priority'    => 5,
	) );
	$text = function ( $id, $label, $type = 'text', $desc = '' ) use ( $wp, $d ) {
		$wp->add_setting( $id, array(
			'default'           => $d[ $id ],
			'sanitize_callback' => 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_scene', 'type' => $type, 'description' => $desc ) );
	};
	$text( 'fv_scene_title', __( 'تیتر', 'faucets-valves' ) );
	$text( 'fv_scene_lead', __( 'توضیح زیر تیتر', 'faucets-valves' ), 'textarea' );
	for ( $i = 1; $i <= 3; $i++ ) {
		/* translators: %d: cell number */
		$text( "fv_scene_cell_{$i}_l", sprintf( __( 'خانهٔ %d: عنوان', 'faucets-valves' ), $i ) );
		/* translators: %d: cell number */
		$text( "fv_scene_cell_{$i}_v", sprintf( __( 'خانهٔ %d: مقدار', 'faucets-valves' ), $i ) );
	}
	$text( 'fv_scene_cats', __( 'دسته‌های روی لوله (شناسه‌ها با ویرگول)', 'faucets-valves' ), 'text', __( 'خالی = دسته‌های اصلی به ترتیب خودشان، حداکثر ۶ دسته. مثال: 12,15,9', 'faucets-valves' ) );

	$wp->add_setting( 'fv_anatomy_product', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( 'fv_anatomy_product', array(
		'label'       => __( 'شناسهٔ محصول در پایان کالبدشکافی', 'faucets-valves' ),
		'description' => __( 'قیمت و دکمهٔ خرید این محصول بعد از سرهم شدن قطعه‌ها نمایش داده می‌شود. خالی = پرفروش‌ترین محصول.', 'faucets-valves' ),
		'section'     => 'fv_scene',
		'type'        => 'number',
	) );
	$text( 'fv_anatomy_t1', __( 'کالبدشکافی: تیتر اول', 'faucets-valves' ) );
	$text( 'fv_anatomy_p1', __( 'کالبدشکافی: متن اول', 'faucets-valves' ), 'textarea' );
	$text( 'fv_anatomy_t2', __( 'کالبدشکافی: تیتر دوم', 'faucets-valves' ) );
	$text( 'fv_anatomy_p2', __( 'کالبدشکافی: متن دوم', 'faucets-valves' ), 'textarea' );
} );

/* ---------------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------------- */

/**
 * Categories shown as branches, with their valve, note and up to 6 products.
 *
 * @return array[]
 */
function fv_scene_branches() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $cache;
	}
	$ids = array_filter( array_map( 'absint', explode( ',', (string) fv_mod( 'fv_scene_cats' ) ) ) );
	if ( $ids ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'include' => $ids, 'orderby' => 'include', 'hide_empty' => false ) );
	} else {
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		) );
	}
	if ( is_wp_error( $terms ) ) {
		return $cache;
	}
	$kinds = array_keys( fv_valve_kinds() );
	foreach ( array_slice( $terms, 0, 6 ) as $i => $term ) {
		$kind = get_term_meta( $term->term_id, 'fv_valve', true );
		if ( ! in_array( $kind, $kinds, true ) ) {
			$kind = $kinds[ $i % count( $kinds ) ];
		}
		$note = (string) get_term_meta( $term->term_id, 'fv_branch_note', true );
		/* translators: %s: number of products */
		$meta = sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( $term->count ) );
		if ( '' !== $note ) {
			$meta .= '، ' . $note;
		}
		$cache[] = array(
			'key'   => $term->term_id,
			'kind'  => $kind,
			'name'  => $term->name,
			'meta'  => $meta,
			'valve' => fv_valve_kinds()[ $kind ],
			'url'   => get_term_link( $term ),
			'term'  => $term,
		);
	}
	return $cache;
}

/**
 * Picture key for a product without a photo: a faucet in its finish, or the branch's valve.
 */
function fv_scene_thumb_key( $product, $kind ) {
	$finish = function_exists( 'fv_product_finish' ) ? fv_product_finish( $product ) : '';
	if ( '' === $finish ) {
		return $kind;
	}
	$swatch = function_exists( 'fv_finish_swatch' ) ? fv_finish_swatch( $finish ) : 'chrome';
	$map    = array( 'black' => 'black', 'gold' => 'gold', 'brass' => 'gold', 'rose' => 'gold', 'copper' => 'gold', 'steel' => 'nickel' );
	return 'faucet-' . ( $map[ $swatch ] ?? 'chrome' );
}

/**
 * Product shown when the faucet reassembles: Customizer choice, else best seller.
 *
 * @return WC_Product|null
 */
function fv_anatomy_product() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$id = (int) fv_mod( 'fv_anatomy_product' );
	$p  = $id ? wc_get_product( $id ) : null;
	if ( $p && $p->is_visible() ) {
		return $p;
	}
	$best = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'orderby' => 'popularity', 'order' => 'DESC', 'visibility' => 'catalog' ) );
	return $best ? $best[0] : null;
}

/**
 * Labels of the nine faucet parts (top to bottom). Filter to match your own product.
 */
function fv_anatomy_parts() {
	return apply_filters( 'fv_anatomy_parts', array(
		array( 'اهرم', 'برنج با آبکاری کروم' ),
		array( 'کلاهک', 'پیچ‌شو، قابل تعویض' ),
		array( 'کارتریج سرامیکی', '۳۵ میلی‌متر، ۵۰۰٬۰۰۰ بار باز و بسته' ),
		array( 'بدنه', 'برنج ریخته‌گری، ۳۲ سانتی‌متر' ),
		array( 'دهانه و سرشیر', 'سرشیر ضد رسوب، دهانهٔ ۱۴ سانتی‌متر' ),
		array( 'واشر آب‌بندی', 'لاستیک EPDM' ),
		array( 'صفحهٔ پایه', 'ضخامت ۷ میلی‌متر' ),
		array( 'پیچ و مهرهٔ نصب', 'برنج، رزوهٔ کامل' ),
		array( 'شیلنگ استیل', 'اتصال ½ اینچ، ۴۰ سانتی‌متر' ),
	) );
}

/* ---------------------------------------------------------------------------
 * Script
 * ------------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$rel = 'assets/css/scene.css';
	wp_enqueue_style( 'fv-scene', FV_URI . "/{$rel}", array( 'fv-motion' ), fv_asset_ver( $rel ) );
	$want_manifold = fv_home_on( 'manifold' ) && fv_scene_branches();
	$want_anatomy  = fv_home_on( 'anatomy' );
	if ( ! $want_manifold && ! $want_anatomy ) {
		return;
	}
	$rel = 'assets/js/scene/home.js';
	wp_enqueue_script( 'fv-scene', FV_URI . "/{$rel}", array( 'gsap', 'gsap-scrolltrigger' ), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	$branches = array_map( function ( $b ) {
		unset( $b['term'] );
		return $b;
	}, $want_manifold ? fv_scene_branches() : array() );
	wp_add_inline_script( 'fv-scene', 'window.FV_SCENE = ' . wp_json_encode( array(
		'branches' => $branches,
		'anatomy'  => array( 'labels' => fv_anatomy_parts() ),
		'i18n'     => array( 'added' => __( 'در مخزن', 'faucets-valves' ) ),
	) ) . ';', 'before' );
} );
