<?php
/**
 * Customizer: home page sections (on/off, titles, stats, catalog band).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Home section keys, labels and default visibility, in display order.
 */
function fv_home_sections() {
	return array(
		'manifold'   => __( 'لوله‌کشی سه‌بعدی (دسته‌ها)', 'faucets-valves' ),
		'anatomy'    => __( 'کالبدشکافی شیر', 'faucets-valves' ),
		'categories' => __( 'کشوهای انبار (همهٔ دسته‌ها)', 'faucets-valves' ),
		'finishes'   => __( 'مجموعه‌ها بر اساس پرداخت', 'faucets-valves' ),
		'best'       => __( 'پرفروش‌ها', 'faucets-valves' ),
		'stats'      => __( 'آمار و اعداد', 'faucets-valves' ),
		'new'        => __( 'تازه‌ها', 'faucets-valves' ),
		'brands'     => __( 'برندها', 'faucets-valves' ),
		'catalog'    => __( 'نوار دانلود کاتالوگ', 'faucets-valves' ),
		'journal'    => __( 'راهنماها و مقالات', 'faucets-valves' ),
	);
}

add_filter( 'fv_defaults', function ( $d ) {
	foreach ( array_keys( fv_home_sections() ) as $key ) {
		$d[ "fv_home_{$key}" ] = true;
	}
	return array_merge( $d, array(
		'fv_finishes_title' => 'یک طراحی، چند پرداخت',
		'fv_finishes_sub'   => 'کروم براق، برنج، مشکی مات و طلایی؛ هر سطح با آبکاری چندلایه.',
		'fv_stat_1_value'   => 15,
		'fv_stat_1_suffix'  => '+',
		'fv_stat_1_label'   => 'سال تجربه در بازار عمده',
		'fv_stat_2_value'   => 2400,
		'fv_stat_2_suffix'  => '',
		'fv_stat_2_label'   => 'کد کالا در انبار',
		'fv_stat_3_value'   => 31,
		'fv_stat_3_suffix'  => '',
		'fv_stat_3_label'   => 'استان زیر پوشش ارسال',
		'fv_stat_4_value'   => 1200,
		'fv_stat_4_suffix'  => '+',
		'fv_stat_4_label'   => 'پیمانکار و فروشگاه همکار',
		'fv_catalog_title'  => 'کاتالوگ کامل محصولات',
		'fv_catalog_text'   => 'مشخصات فنی، ابعاد و کد کالای همهٔ برندها را در قالب PDF دریافت کنید.',
		'fv_catalog_button' => 'مشاهدهٔ کاتالوگ‌ها',
		'fv_catalog_url'    => '',
		'fv_catalog_image'  => 0,
	) );
} );

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = fv_defaults();

	// Section visibility.
	$wp->add_section( 'fv_home', array(
		'title'       => __( 'بخش‌های صفحهٔ نخست', 'faucets-valves' ),
		'description' => __( 'بخش‌هایی که در صفحهٔ نخست نمایش داده شوند.', 'faucets-valves' ),
		'panel'       => 'fv_panel',
	) );
	foreach ( fv_home_sections() as $key => $label ) {
		$id = "fv_home_{$key}";
		$wp->add_setting( $id, array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_home', 'type' => 'checkbox' ) );
	}

	// Finishes.
	foreach ( array(
		'fv_finishes_title' => __( 'عنوان بخش پرداخت‌ها', 'faucets-valves' ),
		'fv_finishes_sub'   => __( 'توضیح بخش پرداخت‌ها', 'faucets-valves' ),
	) as $id => $label ) {
		$wp->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_home', 'type' => 'text' ) );
	}

	// Stats.
	$wp->add_section( 'fv_stats', array( 'title' => __( 'آمار صفحهٔ نخست', 'faucets-valves' ), 'panel' => 'fv_panel' ) );
	for ( $i = 1; $i <= 4; $i++ ) {
		$wp->add_setting( "fv_stat_{$i}_value", array( 'default' => $d[ "fv_stat_{$i}_value" ], 'sanitize_callback' => 'absint' ) );
		$wp->add_control( "fv_stat_{$i}_value", array(
			/* translators: %s: stat number */
			'label'   => sprintf( __( 'عدد %s', 'faucets-valves' ), number_format_i18n( $i ) ),
			'section' => 'fv_stats',
			'type'    => 'number',
		) );
		$wp->add_setting( "fv_stat_{$i}_suffix", array( 'default' => $d[ "fv_stat_{$i}_suffix" ], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( "fv_stat_{$i}_suffix", array( 'label' => __( 'پسوند (مثلاً +)', 'faucets-valves' ), 'section' => 'fv_stats', 'type' => 'text' ) );
		$wp->add_setting( "fv_stat_{$i}_label", array( 'default' => $d[ "fv_stat_{$i}_label" ], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( "fv_stat_{$i}_label", array( 'label' => __( 'عنوان', 'faucets-valves' ), 'section' => 'fv_stats', 'type' => 'text' ) );
	}

	// Catalog band.
	$wp->add_section( 'fv_catalog_band', array( 'title' => __( 'نوار کاتالوگ صفحهٔ نخست', 'faucets-valves' ), 'panel' => 'fv_panel' ) );
	foreach ( array(
		'fv_catalog_title'  => array( __( 'عنوان', 'faucets-valves' ), 'text', 'sanitize_text_field' ),
		'fv_catalog_text'   => array( __( 'متن', 'faucets-valves' ), 'textarea', 'sanitize_textarea_field' ),
		'fv_catalog_button' => array( __( 'متن دکمه', 'faucets-valves' ), 'text', 'sanitize_text_field' ),
		'fv_catalog_url'    => array( __( 'پیوند دکمه (خالی = صفحهٔ کاتالوگ‌ها)', 'faucets-valves' ), 'url', 'esc_url_raw' ),
	) as $id => list( $label, $type, $san ) ) {
		$wp->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => $san ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_catalog_band', 'type' => $type ) );
	}
	$wp->add_setting( 'fv_catalog_image', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( new WP_Customize_Media_Control( $wp, 'fv_catalog_image', array(
		'label'     => __( 'تصویر نوار کاتالوگ', 'faucets-valves' ),
		'section'   => 'fv_catalog_band',
		'mime_type' => 'image',
	) ) );
} );

/**
 * Whether a home section is switched on.
 */
function fv_home_on( $key ) {
	return (bool) fv_mod( "fv_home_{$key}" );
}

/**
 * URL of the first page using the catalogs template.
 */
function fv_catalogs_page_url() {
	return fv_page_url_by_template( 'page-templates/catalogs.php' );
}
