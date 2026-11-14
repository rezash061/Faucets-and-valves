<?php
/**
 * Front page 3D house: a white cutaway model whose rooms are product categories.
 *
 * - Products › Categories › «جای این دسته در خانهٔ سه‌بعدی»: give a category a room (kitchen,
 *   bathroom, toilet, boiler room, yard), or a fixture inside a room (e.g. «حمام › شیر دوش و وان»).
 *   A room without a category stays white and cannot be clicked. A fixture without its own
 *   category links to its room's category.
 * - If no category has a room yet, the main categories fill the rooms in order.
 * - Customizer › تنظیمات قالب شیرآلات › «خانهٔ سه‌بعدی»: headline, text and the three cells.
 * - The 3D script (assets/js/scene/home.js) only upgrades the markup. Without WebGL, with data
 *   saving on, or without JavaScript, visitors get the rooms as plain category links.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valve types with a 3D model (used by the product page viewer).
 */
function fv_valve_kinds() {
	return array(
		'ball'  => __( 'شیر توپی', 'faucets-valves' ),
		'gate'  => __( 'شیر کشویی', 'faucets-valves' ),
		'globe' => __( 'شیر بشقابی', 'faucets-valves' ),
		'check' => __( 'شیر یک‌طرفه', 'faucets-valves' ),
	);
}

/**
 * Rooms of the house and the fixtures labelled in each.
 *
 * @return array room => [ label, spots => [ id => label ] ]
 */
function fv_house_map() {
	return apply_filters( 'fv_house_map', array(
		'kitchen' => array(
			'label' => __( 'آشپزخانه', 'faucets-valves' ),
			'spots' => array(
				'sink'   => __( 'شیر ظرفشویی', 'faucets-valves' ),
				'filter' => __( 'شیر تصفیهٔ آب', 'faucets-valves' ),
				'gas'    => __( 'شیر گاز اجاق', 'faucets-valves' ),
			),
		),
		'bath'    => array(
			'label' => __( 'حمام', 'faucets-valves' ),
			'spots' => array(
				'basin'    => __( 'شیر روشویی', 'faucets-valves' ),
				'shower'   => __( 'شیر دوش و وان', 'faucets-valves' ),
				'radiator' => __( 'شیر رادیاتور', 'faucets-valves' ),
			),
		),
		'wc'      => array(
			'label' => __( 'سرویس بهداشتی', 'faucets-valves' ),
			'spots' => array(
				'sprayer' => __( 'شیر و شلنگ توالت', 'faucets-valves' ),
				'angle'   => __( 'شیر پیسوار', 'faucets-valves' ),
				'basin'   => __( 'شیر روشویی کوچک', 'faucets-valves' ),
			),
		),
		'mech'    => array(
			'label' => __( 'موتورخانه', 'faucets-valves' ),
			'spots' => array(
				'gate'  => __( 'شیر کشویی', 'faucets-valves' ),
				'check' => __( 'شیر یک‌طرفه', 'faucets-valves' ),
				'ball'  => __( 'شیر توپی', 'faucets-valves' ),
				'globe' => __( 'شیر بشقابی', 'faucets-valves' ),
			),
		),
		'yard'    => array(
			'label' => __( 'حیاط و گاز', 'faucets-valves' ),
			'spots' => array(
				'tap' => __( 'شیر حیاط', 'faucets-valves' ),
				'gas' => __( 'شیر اصلی گاز', 'faucets-valves' ),
			),
		),
	) );
}

/**
 * Valid values of the category field: "room:kitchen", "spot:kitchen.sink", …
 */
function fv_house_places() {
	$out = array();
	foreach ( fv_house_map() as $room => $r ) {
		$out[ "room:{$room}" ] = $r['label'];
		foreach ( $r['spots'] as $id => $label ) {
			$out[ "spot:{$room}.{$id}" ] = $r['label'] . ' › ' . $label;
		}
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Category fields: place in the house + short note
 * ------------------------------------------------------------------------- */

function fv_house_select( $current = '' ) {
	echo '<select name="fv_house" id="fv_house">';
	echo '<option value="">' . esc_html__( 'در خانه نیست', 'faucets-valves' ) . '</option>';
	foreach ( fv_house_map() as $room => $r ) {
		echo '<optgroup label="' . esc_attr( $r['label'] ) . '">';
		/* translators: %s: room name */
		printf( '<option value="%s"%s>%s</option>', esc_attr( "room:{$room}" ), selected( $current, "room:{$room}", false ), esc_html( sprintf( __( 'کل اتاق: %s', 'faucets-valves' ), $r['label'] ) ) );
		foreach ( $r['spots'] as $id => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( "spot:{$room}.{$id}" ), selected( $current, "spot:{$room}.{$id}", false ), esc_html( $label ) );
		}
		echo '</optgroup>';
	}
	echo '</select>';
}

add_action( 'product_cat_add_form_fields', function () {
	wp_nonce_field( 'fv_cat_scene', 'fv_cat_scene_nonce' );
	?>
	<div class="form-field">
		<label for="fv_house"><?php esc_html_e( 'جای این دسته در خانهٔ سه‌بعدی', 'faucets-valves' ); ?></label>
		<?php fv_house_select(); ?>
		<p><?php esc_html_e( 'یک اتاق برای دسته‌های اصلی، یا یکی از شیرهای داخل اتاق برای زیردسته‌ها.', 'faucets-valves' ); ?></p>
	</div>
	<div class="form-field">
		<label for="fv_branch_note"><?php esc_html_e( 'یادداشت کوتاه', 'faucets-valves' ); ?></label>
		<input type="text" name="fv_branch_note" id="fv_branch_note" placeholder="<?php esc_attr_e( 'مثلاً: اتصال ½ اینچ', 'faucets-valves' ); ?>">
	</div>
	<?php
} );

add_action( 'product_cat_edit_form_fields', function ( $term ) {
	$note = get_term_meta( $term->term_id, 'fv_branch_note', true );
	wp_nonce_field( 'fv_cat_scene', 'fv_cat_scene_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fv_house"><?php esc_html_e( 'جای این دسته در خانهٔ سه‌بعدی', 'faucets-valves' ); ?></label></th>
		<td>
			<?php fv_house_select( (string) get_term_meta( $term->term_id, 'fv_house', true ) ); ?>
			<p class="description"><?php esc_html_e( '«کل اتاق»: با کلیک روی آن اتاق در صفحهٔ نخست، این دسته باز می‌شود. یک شیر: برچسب آن شیر به این دسته می‌رود (برای زیردسته‌ها). شیری که دسته ندارد به دستهٔ اتاقش می‌رود.', 'faucets-valves' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="fv_branch_note"><?php esc_html_e( 'یادداشت کوتاه', 'faucets-valves' ); ?></label></th>
		<td>
			<input type="text" name="fv_branch_note" id="fv_branch_note" value="<?php echo esc_attr( $note ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: اتصال ½ اینچ', 'faucets-valves' ); ?>">
			<p class="description"><?php esc_html_e( 'کنار تعداد کالا در خانه و بالای صفحهٔ دسته نمایش داده می‌شود.', 'faucets-valves' ); ?></p>
		</td>
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
	$place = isset( $_POST['fv_house'] ) ? sanitize_text_field( wp_unslash( $_POST['fv_house'] ) ) : '';
	if ( $place && isset( fv_house_places()[ $place ] ) ) {
		update_term_meta( $term_id, 'fv_house', $place );
	} else {
		delete_term_meta( $term_id, 'fv_house' );
	}
	$note = isset( $_POST['fv_branch_note'] ) ? sanitize_text_field( wp_unslash( $_POST['fv_branch_note'] ) ) : '';
	update_term_meta( $term_id, 'fv_branch_note', $note );
};
add_action( 'created_product_cat', $fv_save_cat_scene );
add_action( 'edited_product_cat', $fv_save_cat_scene );

// Show the place in the categories list.
add_filter( 'manage_edit-product_cat_columns', function ( $cols ) {
	$cols['fv_house'] = __( 'در خانه', 'faucets-valves' );
	return $cols;
} );
add_filter( 'manage_product_cat_custom_column', function ( $out, $col, $term_id ) {
	if ( 'fv_house' === $col ) {
		$place = (string) get_term_meta( $term_id, 'fv_house', true );
		$out   = esc_html( fv_house_places()[ $place ] ?? '—' );
	}
	return $out;
}, 10, 3 );

/* ---------------------------------------------------------------------------
 * Customizer
 * ------------------------------------------------------------------------- */

add_filter( 'fv_defaults', function ( $d ) {
	return array_merge( $d, array(
		'fv_scene_title'    => 'هر اتاق، یک دسته از شیرآلات',
		'fv_scene_lead'     => 'روی یک اتاق بزنید تا شیرهای آن را ببینید.',
		'fv_scene_cell_1_l' => 'کالا',
		'fv_scene_cell_1_v' => '۲٬۴۰۰ کد',
		'fv_scene_cell_2_l' => 'ارسال',
		'fv_scene_cell_2_v' => '۳۱ استان',
		'fv_scene_cell_3_l' => 'قیمت',
		'fv_scene_cell_3_v' => 'برای همه',
	) );
} );

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = fv_defaults();
	$wp->add_section( 'fv_scene', array(
		'title'       => __( 'خانهٔ سه‌بعدی', 'faucets-valves' ),
		'description' => __( 'خانهٔ سه‌بعدی بالای صفحهٔ نخست. اتاق هر دسته را در محصولات › دسته‌ها تعیین کنید.', 'faucets-valves' ),
		'panel'       => 'fv_panel',
		'priority'    => 5,
	) );
	$text = function ( $id, $label, $type = 'text' ) use ( $wp, $d ) {
		$wp->add_setting( $id, array(
			'default'           => $d[ $id ],
			'sanitize_callback' => 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_scene', 'type' => $type ) );
	};
	$text( 'fv_scene_title', __( 'تیتر', 'faucets-valves' ) );
	$text( 'fv_scene_lead', __( 'توضیح زیر تیتر', 'faucets-valves' ), 'textarea' );
	for ( $i = 1; $i <= 3; $i++ ) {
		/* translators: %d: cell number */
		$text( "fv_scene_cell_{$i}_l", sprintf( __( 'خانهٔ %d: عنوان', 'faucets-valves' ), $i ) );
		/* translators: %d: cell number */
		$text( "fv_scene_cell_{$i}_v", sprintf( __( 'خانهٔ %d: مقدار', 'faucets-valves' ), $i ) );
	}
} );

/* ---------------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------------- */

/**
 * Rooms that have a category, each with its fixtures and where their labels link.
 *
 * @return array[] key, room, name, meta, url, spots => [ id => label, sub, url ]
 */
function fv_house_rooms() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $cache;
	}
	$placed = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'meta_key'   => 'fv_house', // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$by_place = array();
	if ( ! is_wp_error( $placed ) ) {
		foreach ( $placed as $term ) {
			$place = (string) get_term_meta( $term->term_id, 'fv_house', true );
			if ( $place && ! isset( $by_place[ $place ] ) ) {
				$by_place[ $place ] = $term;
			}
		}
	}
	$map = fv_house_map();

	// Nothing set up yet: main categories take the rooms in order.
	$has_room = false;
	foreach ( array_keys( $by_place ) as $place ) {
		$has_room = $has_room || 0 === strpos( $place, 'room:' );
	}
	if ( ! $has_room ) {
		$top = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => count( $map ),
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		) );
		if ( ! is_wp_error( $top ) ) {
			$top = array_values( $top );
			foreach ( array_keys( $map ) as $i => $room ) {
				if ( isset( $top[ $i ] ) ) {
					$by_place[ "room:{$room}" ] = $top[ $i ];
				}
			}
		}
	}

	$count = function ( $term ) {
		/* translators: %s: number of products */
		return sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( $term->count ) );
	};
	foreach ( $map as $room => $r ) {
		if ( ! isset( $by_place[ "room:{$room}" ] ) ) {
			continue;
		}
		$term  = $by_place[ "room:{$room}" ];
		$url   = get_term_link( $term );
		$note  = (string) get_term_meta( $term->term_id, 'fv_branch_note', true );
		$spots = array();
		foreach ( $r['spots'] as $id => $label ) {
			$own          = $by_place[ "spot:{$room}.{$id}" ] ?? null;
			$spots[ $id ] = $own
				? array( 'label' => $own->name, 'sub' => $count( $own ), 'url' => get_term_link( $own ) )
				: array( 'label' => $label, 'sub' => '', 'url' => $url );
		}
		$cache[] = array(
			'key'   => $term->term_id,
			'room'  => $room,
			'name'  => $term->name,
			'meta'  => $count( $term ) . ( '' !== $note ? '، ' . $note : '' ),
			'url'   => $url,
			'spots' => $spots,
		);
	}
	return $cache;
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
	if ( ! fv_home_on( 'house' ) || ! fv_house_rooms() ) {
		return;
	}
	$rel = 'assets/js/scene/home.js';
	wp_enqueue_script( 'fv-scene', FV_URI . "/{$rel}", array( 'gsap' ), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'fv-scene', 'window.FV_SCENE = ' . wp_json_encode( array( 'rooms' => fv_house_rooms() ) ) . ';', 'before' );
} );
