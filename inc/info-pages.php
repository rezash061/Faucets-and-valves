<?php
/**
 * Information pages: About, Contact, FAQ and policy pages (shipping, returns, wholesale terms).
 *
 * Page templates (Page › Template):
 *  - «درباره ما»            page-templates/about.php   content + numbers + brands
 *  - «تماس با ما»           page-templates/contact.php form, phone, address, map links
 *  - «پرسش‌های متداول»      page-templates/faq.php     uses the editor's «جزئیات» (Details) blocks;
 *                                                     each block = one question, also sent to Google as FAQ data
 *  - «صفحهٔ اطلاعات»        page-templates/info.php    long text with a table of contents from its headings
 *
 * Contact form messages are emailed and also kept under «پیام‌ها» in the dashboard, so nothing
 * is lost if the host cannot send email.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Customizer: contact details ---------- */

add_filter( 'fv_defaults', function ( $d ) {
	return array_merge( $d, array(
		'fv_contact_email' => '',
		'fv_mobile'        => '',
		'fv_map_lat'       => '',
		'fv_map_lng'       => '',
		'fv_map_embed'     => '',
	) );
} );

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$wp->add_section( 'fv_contact', array(
		'title'       => __( 'تماس و نقشه', 'faucets-valves' ),
		'description' => __( 'تلفن، نشانی و ساعت کاری در بخش «اطلاعات تماس» تنظیم می‌شوند. این‌ها برای برگهٔ «تماس با ما» هستند.', 'faucets-valves' ),
		'panel'       => 'fv_panel',
	) );
	$fields = array(
		'fv_contact_email' => array( __( 'ایمیل دریافت پیام‌ها', 'faucets-valves' ), 'email', __( 'خالی = ایمیل مدیر سایت', 'faucets-valves' ), 'sanitize_email' ),
		'fv_mobile'        => array( __( 'موبایل / واتس‌اپ', 'faucets-valves' ), 'text', '', 'sanitize_text_field' ),
		'fv_map_lat'       => array( __( 'عرض جغرافیایی (lat)', 'faucets-valves' ), 'text', __( 'برای دکمه‌های مسیریابی نشان، بلد و گوگل. مثال: 35.6892', 'faucets-valves' ), 'fv_sanitize_coord' ),
		'fv_map_lng'       => array( __( 'طول جغرافیایی (lng)', 'faucets-valves' ), 'text', __( 'مثال: 51.3890', 'faucets-valves' ), 'fv_sanitize_coord' ),
		'fv_map_embed'     => array( __( 'نشانی نقشهٔ جاسازی (اختیاری)', 'faucets-valves' ), 'url', __( 'نشانی src کد جاسازی نقشه از نشان یا گوگل (با https).', 'faucets-valves' ), 'esc_url_raw' ),
	);
	foreach ( $fields as $id => $f ) {
		$wp->add_setting( $id, array( 'default' => '', 'sanitize_callback' => $f[3] ) );
		$wp->add_control( $id, array( 'label' => $f[0], 'type' => $f[1], 'description' => $f[2], 'section' => 'fv_contact' ) );
	}
} );

function fv_sanitize_coord( $v ) {
	$v = str_replace( ',', '.', fv_to_latin( (string) $v ) );
	return is_numeric( $v ) ? (string) round( (float) $v, 6 ) : '';
}

/**
 * Persian/Arabic digits to Latin.
 */
function fv_to_latin( $s ) {
	return strtr( (string) $s, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/* ---------- Messages (stored copies of contact form submissions) ---------- */

add_action( 'init', function () {
	register_post_type( 'fv_message', array(
		'labels'          => array(
			'name'          => __( 'پیام‌ها', 'faucets-valves' ),
			'singular_name' => __( 'پیام', 'faucets-valves' ),
			'edit_item'     => __( 'پیام', 'faucets-valves' ),
			'not_found'     => __( 'پیامی نرسیده است.', 'faucets-valves' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

add_filter( 'manage_fv_message_posts_columns', function ( $cols ) {
	return array(
		'cb'       => $cols['cb'],
		'title'    => __( 'موضوع', 'faucets-valves' ),
		'fv_from'  => __( 'فرستنده', 'faucets-valves' ),
		'fv_phone' => __( 'تلفن', 'faucets-valves' ),
		'date'     => $cols['date'],
	);
} );
add_action( 'manage_fv_message_posts_custom_column', function ( $col, $id ) {
	if ( 'fv_from' === $col ) {
		echo esc_html( get_post_meta( $id, '_fv_name', true ) );
	} elseif ( 'fv_phone' === $col ) {
		echo '<span dir="ltr">' . esc_html( get_post_meta( $id, '_fv_phone', true ) ) . '</span>';
	}
}, 10, 2 );

/* ---------- Contact form ---------- */

function fv_contact_subjects() {
	return array(
		'wholesale' => __( 'سفارش عمده و استعلام قیمت', 'faucets-valves' ),
		'order'     => __( 'پیگیری سفارش', 'faucets-valves' ),
		'partner'   => __( 'همکاری و نمایندگی', 'faucets-valves' ),
		'other'     => __( 'سایر', 'faucets-valves' ),
	);
}

add_action( 'admin_post_fv_contact', 'fv_handle_contact' );
add_action( 'admin_post_nopriv_fv_contact', 'fv_handle_contact' );

function fv_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( array( 'fv_sent', 'fv_err' ), $back );
	$fail = function ( $code ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'fv_err', $code, $back ) . '#contact-form' );
		exit;
	};

	if ( ! isset( $_POST['fv_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fv_contact_nonce'] ), 'fv_contact' ) ) {
		$fail( 'expired' );
	}
	// Bots: a hidden field people never fill, and forms sent within 3 seconds of loading.
	$started = isset( $_POST['fv_t'] ) ? (int) $_POST['fv_t'] : 0;
	if ( ! empty( $_POST['website'] ) || ( $started && time() - $started < 3 ) ) {
		wp_safe_redirect( add_query_arg( 'fv_sent', 1, $back ) . '#contact-form' );
		exit;
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'fv_contact_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		$fail( 'limit' );
	}

	$name    = isset( $_POST['fv_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fv_name'] ) ) : '';
	$phone   = isset( $_POST['fv_phone'] ) ? preg_replace( '/[^\d+]/', '', fv_to_latin( sanitize_text_field( wp_unslash( $_POST['fv_phone'] ) ) ) ) : '';
	$email   = isset( $_POST['fv_email'] ) ? sanitize_email( wp_unslash( $_POST['fv_email'] ) ) : '';
	$subject = isset( $_POST['fv_subject'] ) ? sanitize_key( $_POST['fv_subject'] ) : 'other';
	$message = isset( $_POST['fv_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fv_message'] ) ) : '';

	$old = array( 'n' => $name, 'p' => $phone, 'e' => $email, 's' => $subject, 'm' => $message );
	set_transient( 'fv_contact_old_' . md5( $ip ), $old, 10 * MINUTE_IN_SECONDS );

	if ( '' === $name || strlen( $phone ) < 8 || mb_strlen( $message ) < 10 ) {
		$fail( 'fields' );
	}
	$subjects = fv_contact_subjects();
	$label    = $subjects[ $subject ] ?? $subjects['other'];

	$id = wp_insert_post( array(
		'post_type'    => 'fv_message',
		'post_status'  => 'private',
		/* translators: 1: subject, 2: name */
		'post_title'   => sprintf( __( '%1$s — %2$s', 'faucets-valves' ), $label, $name ),
		'post_content' => $message,
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_fv_name', $name );
		update_post_meta( $id, '_fv_phone', $phone );
		update_post_meta( $id, '_fv_email', $email );
	}

	$to   = fv_mod( 'fv_contact_email' ) ? fv_mod( 'fv_contact_email' ) : get_option( 'admin_email' );
	$body = sprintf( "%s: %s\n%s: %s\n%s: %s\n%s: %s\n\n%s", __( 'نام', 'faucets-valves' ), $name, __( 'تلفن', 'faucets-valves' ), $phone, __( 'ایمیل', 'faucets-valves' ), $email ? $email : '—', __( 'موضوع', 'faucets-valves' ), $label, $message );
	$head = $email ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array();
	/* translators: %s: subject */
	wp_mail( $to, sprintf( __( 'پیام تازه از سایت: %s', 'faucets-valves' ), $label ), $body, $head );

	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	delete_transient( 'fv_contact_old_' . md5( $ip ) );
	wp_safe_redirect( add_query_arg( 'fv_sent', 1, $back ) . '#contact-form' );
	exit;
}

/**
 * Values the visitor typed before a failed submission, so they don't retype them.
 */
function fv_contact_old() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$o  = isset( $_GET['fv_err'] ) ? get_transient( 'fv_contact_old_' . md5( $ip ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification
	return wp_parse_args( is_array( $o ) ? $o : array(), array( 'n' => '', 'p' => '', 'e' => '', 's' => '', 'm' => '' ) );
}

/* ---------- Table of contents from H2 headings ---------- */

/**
 * Adds ids to the <h2> headings of rendered content and returns [content, toc].
 *
 * @return array{0:string,1:array}
 */
function fv_content_toc( $html ) {
	$toc  = array();
	$used = array();
	$html = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#is', function ( $m ) use ( &$toc, &$used ) {
		$text = trim( wp_strip_all_tags( $m[2] ) );
		if ( preg_match( '/\sid=["\']([^"\']+)/', $m[1], $idm ) ) {
			$id = $idm[1];
			$attrs = $m[1];
		} else {
			$id = 'sec-' . ( count( $toc ) + 1 );
			$attrs = $m[1] . ' id="' . esc_attr( $id ) . '"';
		}
		$used[] = $id;
		$toc[]  = array( 'id' => $id, 'text' => $text );
		return '<h2' . $attrs . '>' . $m[2] . '</h2>';
	}, $html );
	return array( $html, $toc );
}

/* ---------- FAQ structured data from Details blocks ---------- */

/**
 * Questions and answers from core/details blocks (recursively).
 *
 * @return array[] Each: q, a (plain text).
 */
function fv_faq_items( $post ) {
	$items = array();
	$walk  = function ( $blocks ) use ( &$walk, &$items ) {
		foreach ( $blocks as $b ) {
			if ( 'core/details' === $b['blockName'] ) {
				$html = render_block( $b );
				if ( preg_match( '#<summary[^>]*>(.*?)</summary>(.*)</details>#is', $html, $m ) ) {
					$items[] = array( 'q' => trim( wp_strip_all_tags( $m[1] ) ), 'a' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $m[2] ) ) ) );
				}
			} elseif ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $post->post_content ) );
	return $items;
}

add_action( 'wp_head', function () {
	if ( ! is_page_template( 'page-templates/faq.php' ) ) {
		return;
	}
	$items = fv_faq_items( get_queried_object() );
	if ( ! $items ) {
		return;
	}
	$data = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $i ) {
			return array( '@type' => 'Question', 'name' => $i['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $i['a'] ) );
		}, $items ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
} );

/* ---------- Assets ---------- */

add_action( 'wp_enqueue_scripts', function () {
	$templates = array( 'page-templates/about.php', 'page-templates/contact.php', 'page-templates/faq.php', 'page-templates/info.php' );
	foreach ( $templates as $t ) {
		if ( is_page_template( $t ) ) {
			$rel = 'assets/css/info.css';
			wp_enqueue_style( 'fv-info', FV_URI . "/{$rel}", array( 'fv-motion' ), fv_asset_ver( $rel ) );
			$rel = 'assets/js/ui/info.js';
			wp_enqueue_script( 'fv-info', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
			return;
		}
	}
} );

/**
 * Page header shared by the info templates: plaster wall, pipe, title block.
 *
 * @param array $cells Pairs of [label, value].
 */
function fv_page_head( $cells = array() ) {
	$excerpt = has_excerpt() ? get_the_excerpt() : '';
	?>
	<header class="page-hero surface-plaster">
		<div class="container">
			<div class="titleblock page-hero__block">
				<div class="tb-main">
					<h1><?php echo fv_split_words( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
					<?php if ( $excerpt ) : ?>
						<p><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
				</div>
				<?php foreach ( array_slice( $cells, 0, 3 ) as $c ) : ?>
					<div class="tb-cell"><?php echo esc_html( $c[0] ); ?><b><?php echo esc_html( $c[1] ); ?></b></div>
				<?php endforeach; ?>
			</div>
		</div>
		<span class="page-hero__pipe" aria-hidden="true"></span>
	</header>
	<?php
}
