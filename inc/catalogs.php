<?php
/**
 * Catalog downloads: a simple "کاتالوگ" post type.
 *
 * Each catalog = title, cover image (featured image), PDF file (picked from the Media
 * Library), optional brand (WooCommerce Brands), optional short note (excerpt).
 * Listed by the page template "کاتالوگ‌ها" (page-templates/catalogs.php).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'fv_catalog', array(
		'labels'       => array(
			'name'               => __( 'کاتالوگ‌ها', 'faucets-valves' ),
			'singular_name'      => __( 'کاتالوگ', 'faucets-valves' ),
			'add_new'            => __( 'افزودن کاتالوگ', 'faucets-valves' ),
			'add_new_item'       => __( 'افزودن کاتالوگ تازه', 'faucets-valves' ),
			'edit_item'          => __( 'ویرایش کاتالوگ', 'faucets-valves' ),
			'all_items'          => __( 'همهٔ کاتالوگ‌ها', 'faucets-valves' ),
			'not_found'          => __( 'کاتالوگی پیدا نشد.', 'faucets-valves' ),
			'featured_image'     => __( 'تصویر جلد', 'faucets-valves' ),
			'set_featured_image' => __( 'انتخاب تصویر جلد', 'faucets-valves' ),
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-media-document',
		'menu_position' => 56,
		'supports'     => array( 'title', 'thumbnail', 'excerpt', 'page-attributes' ),
	) );
	if ( taxonomy_exists( 'product_brand' ) ) {
		register_taxonomy_for_object_type( 'product_brand', 'fv_catalog' );
	}
}, 20 );

/* ---------- PDF field ---------- */

add_action( 'add_meta_boxes_fv_catalog', function () {
	add_meta_box( 'fv_catalog_file', __( 'فایل PDF', 'faucets-valves' ), 'fv_catalog_file_box', 'fv_catalog', 'normal', 'high' );
} );

/**
 * Meta box: file picker.
 */
function fv_catalog_file_box( $post ) {
	wp_nonce_field( 'fv_catalog_file', 'fv_catalog_nonce' );
	$id  = (int) get_post_meta( $post->ID, '_fv_file_id', true );
	$url = $id ? wp_get_attachment_url( $id ) : '';
	?>
	<p>
		<input type="hidden" name="fv_file_id" id="fv_file_id" value="<?php echo esc_attr( $id ? $id : '' ); ?>">
		<input type="text" class="widefat" id="fv_file_url" value="<?php echo esc_attr( $url ); ?>" readonly placeholder="<?php esc_attr_e( 'فایلی انتخاب نشده', 'faucets-valves' ); ?>">
	</p>
	<p>
		<button type="button" class="button" id="fv_file_pick"><?php esc_html_e( 'انتخاب یا بارگذاری PDF', 'faucets-valves' ); ?></button>
		<button type="button" class="button-link-delete" id="fv_file_clear"><?php esc_html_e( 'حذف', 'faucets-valves' ); ?></button>
	</p>
	<script>
	(function(){
		var pick=document.getElementById('fv_file_pick'),clear=document.getElementById('fv_file_clear'),id=document.getElementById('fv_file_id'),url=document.getElementById('fv_file_url'),frame;
		pick.addEventListener('click',function(){
			if(!window.wp||!wp.media)return;
			frame=frame||wp.media({title:<?php echo wp_json_encode( __( 'فایل کاتالوگ', 'faucets-valves' ) ); ?>,library:{type:'application/pdf'},button:{text:<?php echo wp_json_encode( __( 'انتخاب', 'faucets-valves' ) ); ?>},multiple:false});
			frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();id.value=a.id;url.value=a.url;});
			frame.open();
		});
		clear.addEventListener('click',function(){id.value='';url.value='';});
	})();
	</script>
	<?php
}

add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( $screen && 'fv_catalog' === $screen->post_type ) {
		wp_enqueue_media();
	}
} );

add_action( 'save_post_fv_catalog', function ( $post_id ) {
	if ( ! isset( $_POST['fv_catalog_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fv_catalog_nonce'] ), 'fv_catalog_file' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$id = isset( $_POST['fv_file_id'] ) ? absint( $_POST['fv_file_id'] ) : 0;
	if ( $id ) {
		update_post_meta( $post_id, '_fv_file_id', $id );
	} else {
		delete_post_meta( $post_id, '_fv_file_id' );
	}
} );

/**
 * File column in the catalog list.
 */
add_filter( 'manage_fv_catalog_posts_columns', function ( $cols ) {
	$cols['fv_file'] = __( 'فایل', 'faucets-valves' );
	return $cols;
} );
add_action( 'manage_fv_catalog_posts_custom_column', function ( $col, $post_id ) {
	if ( 'fv_file' !== $col ) {
		return;
	}
	$id = (int) get_post_meta( $post_id, '_fv_file_id', true );
	echo $id ? esc_html( wp_basename( get_attached_file( $id ) ) ) : '—';
}, 10, 2 );

/* ---------- Front-end data ---------- */

/**
 * Catalogs with their file info.
 *
 * @return array[] Each: id, title, url, size (human), note, thumb_id, brands (WP_Term[]).
 */
function fv_get_catalogs() {
	$posts = get_posts( array(
		'post_type'      => 'fv_catalog',
		'posts_per_page' => 100,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	) );
	$out = array();
	foreach ( $posts as $p ) {
		$file = (int) get_post_meta( $p->ID, '_fv_file_id', true );
		if ( ! $file ) {
			continue;
		}
		$path   = get_attached_file( $file );
		$brands = taxonomy_exists( 'product_brand' ) ? wp_get_post_terms( $p->ID, 'product_brand' ) : array();
		$out[]  = array(
			'id'       => $p->ID,
			'title'    => get_the_title( $p ),
			'url'      => wp_get_attachment_url( $file ),
			'size'     => $path && file_exists( $path ) ? size_format( filesize( $path ), 1 ) : '',
			'note'     => $p->post_excerpt,
			'thumb_id' => get_post_thumbnail_id( $p ),
			'brands'   => is_wp_error( $brands ) ? array() : $brands,
			'date'     => get_the_modified_date( '', $p ),
		);
	}
	return $out;
}
