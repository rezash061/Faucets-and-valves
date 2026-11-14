<?php
/**
 * Customizer: hero, contact details, footer text.
 * WordPress's own Customizer is used, so the theme needs no admin panel of its own.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme-mod defaults in one place.
 */
function fv_defaults() {
	return apply_filters( 'fv_defaults', array(
		'fv_logo_tagline'   => 'فروش عمده، قیمت برای همه',
		'fv_hero_image'     => 0,
		'fv_hero_eyebrow'   => 'مجموعهٔ ۱۴۰۵',
		'fv_hero_title'     => 'درخشش فلز، <strong>دقت</strong> مهندسی',
		'fv_hero_subtitle'  => 'شیرآلات ساختمانی و شیرهای صنعتی ممتاز، مستقیم از انبار به پروژهٔ شما — با قیمت عمده و ارسال به سراسر ایران.',
		'fv_hero_cta_text'  => 'مشاهدهٔ محصولات',
		'fv_hero_cta_url'   => '',
		'fv_hero_cta2_text' => 'دانلود کاتالوگ',
		'fv_hero_cta2_url'  => '',
		'fv_phone'          => '',
		'fv_address'        => '',
		'fv_hours'          => 'شنبه تا چهارشنبه ۹ تا ۱۸، پنجشنبه ۹ تا ۱۴',
		'fv_footer_about'   => 'عرضهٔ عمدهٔ شیرآلات ساختمانی، شیرهای صنعتی و اتصالات با ضمانت اصالت کالا.',
	) );
}

/**
 * Read a theme mod with the theme default.
 */
function fv_mod( $key ) {
	$d = fv_defaults();
	return get_theme_mod( $key, $d[ $key ] ?? '' );
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = fv_defaults();

	$wp->add_panel( 'fv_panel', array(
		'title'    => __( 'تنظیمات قالب شیرآلات', 'faucets-valves' ),
		'priority' => 30,
	) );

	// Hero.
	$wp->add_section( 'fv_hero', array( 'title' => __( 'بخش اصلی صفحه نخست (Hero)', 'faucets-valves' ), 'panel' => 'fv_panel' ) );

	$wp->add_setting( 'fv_hero_image', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( new WP_Customize_Media_Control( $wp, 'fv_hero_image', array(
		'label'       => __( 'تصویر Hero', 'faucets-valves' ),
		'description' => __( 'حداقل ۲۰۰۰×۱۱۲۵ پیکسل، زمینهٔ تیره. موضوع اصلی در سمت چپ قاب باشد.', 'faucets-valves' ),
		'section'     => 'fv_hero',
		'mime_type'   => 'image',
	) ) );

	$texts = array(
		'fv_hero_eyebrow'   => array( __( 'برچسب بالای عنوان', 'faucets-valves' ), 'text' ),
		'fv_hero_title'     => array( __( 'عنوان (برای تأکید از <strong> استفاده کنید)', 'faucets-valves' ), 'textarea' ),
		'fv_hero_subtitle'  => array( __( 'زیرعنوان', 'faucets-valves' ), 'textarea' ),
		'fv_hero_cta_text'  => array( __( 'متن دکمهٔ اصلی', 'faucets-valves' ), 'text' ),
		'fv_hero_cta_url'   => array( __( 'پیوند دکمهٔ اصلی (خالی = فروشگاه)', 'faucets-valves' ), 'url' ),
		'fv_hero_cta2_text' => array( __( 'متن دکمهٔ دوم', 'faucets-valves' ), 'text' ),
		'fv_hero_cta2_url'  => array( __( 'پیوند دکمهٔ دوم (خالی = پنهان)', 'faucets-valves' ), 'url' ),
	);
	foreach ( $texts as $id => list( $label, $type ) ) {
		$wp->add_setting( $id, array(
			'default'           => $d[ $id ],
			'sanitize_callback' => 'url' === $type ? 'esc_url_raw' : ( 'fv_hero_title' === $id ? 'fv_sanitize_title' : 'sanitize_textarea_field' ),
		) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_hero', 'type' => $type ) );
	}

	// Contact + footer.
	$wp->add_section( 'fv_contact', array( 'title' => __( 'اطلاعات تماس و فوتر', 'faucets-valves' ), 'panel' => 'fv_panel' ) );
	$contact = array(
		'fv_logo_tagline' => array( __( 'زیرنویس لوگوی متنی', 'faucets-valves' ), 'text' ),
		'fv_phone'        => array( __( 'تلفن', 'faucets-valves' ), 'text' ),
		'fv_address'      => array( __( 'نشانی', 'faucets-valves' ), 'textarea' ),
		'fv_hours'        => array( __( 'ساعات کاری', 'faucets-valves' ), 'text' ),
		'fv_footer_about' => array( __( 'متن کوتاه دربارهٔ فروشگاه', 'faucets-valves' ), 'textarea' ),
	);
	foreach ( $contact as $id => list( $label, $type ) ) {
		$wp->add_setting( $id, array( 'default' => $d[ $id ], 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'fv_contact', 'type' => $type ) );
	}
} );

/**
 * Hero title may contain <strong>/<em> only.
 */
function fv_sanitize_title( $value ) {
	return wp_kses( $value, array( 'strong' => array(), 'em' => array() ) );
}
