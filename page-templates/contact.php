<?php
/**
 * Template Name: تماس با ما
 *
 * Contact form (emailed and kept under «پیام‌ها»), phone, mobile, address, hours and map links.
 * Details: Customizer › تنظیمات قالب شیرآلات › «اطلاعات تماس» and «تماس و نقشه».
 * Any text written in the editor appears above the form.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

$phone   = fv_mod( 'fv_phone' );
$mobile  = fv_mod( 'fv_mobile' );
$address = fv_mod( 'fv_address' );
$hours   = fv_mod( 'fv_hours' );
$lat     = fv_mod( 'fv_map_lat' );
$lng     = fv_mod( 'fv_map_lng' );
$embed   = fv_mod( 'fv_map_embed' );
$old     = fv_contact_old();
$sent    = isset( $_GET['fv_sent'] ); // phpcs:ignore WordPress.Security.NonceVerification
$err     = isset( $_GET['fv_err'] ) ? sanitize_key( $_GET['fv_err'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$errors  = array(
	'fields'  => __( 'نام، شمارهٔ تماس و متن پیام (دست‌کم ۱۰ حرف) را بنویسید.', 'faucets-valves' ),
	'expired' => __( 'صفحه منقضی شده بود. دوباره ارسال کنید.', 'faucets-valves' ),
	'limit'   => __( 'چند پیام پشت سر هم فرستاده‌اید. یک ساعت دیگر دوباره امتحان کنید یا تماس بگیرید.', 'faucets-valves' ),
);

while ( have_posts() ) :
	the_post();
	$cells = array();
	if ( $phone ) {
		$cells[] = array( __( 'تلفن', 'faucets-valves' ), $phone );
	}
	if ( $hours ) {
		$cells[] = array( __( 'ساعت کاری', 'faucets-valves' ), $hours );
	}
	fv_page_head( $cells );
	?>
	<div class="container contact-layout">
		<section class="contact-form-wrap" id="contact-form" aria-labelledby="contact-form-title">
			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<div class="entry-content contact-intro"><?php the_content(); ?></div>
			<?php endif; ?>
			<h2 id="contact-form-title"><?php esc_html_e( 'پیام بفرستید', 'faucets-valves' ); ?></h2>

			<?php if ( $sent ) : ?>
				<div class="notice-box notice-box--ok" role="status"><?php esc_html_e( 'پیامتان رسید. در ساعت کاری با شما تماس می‌گیریم.', 'faucets-valves' ); ?></div>
			<?php elseif ( $err && isset( $errors[ $err ] ) ) : ?>
				<div class="notice-box notice-box--error" role="alert"><?php echo esc_html( $errors[ $err ] ); ?></div>
			<?php endif; ?>

			<form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fv_contact">
				<input type="hidden" name="fv_t" value="<?php echo esc_attr( time() ); ?>">
				<?php wp_nonce_field( 'fv_contact', 'fv_contact_nonce' ); ?>
				<p class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>

				<p class="field">
					<label for="fv_name"><?php esc_html_e( 'نام و نام خانوادگی', 'faucets-valves' ); ?> <span class="req">*</span></label>
					<input id="fv_name" name="fv_name" type="text" required autocomplete="name" value="<?php echo esc_attr( $old['n'] ); ?>">
				</p>
				<p class="field">
					<label for="fv_phone"><?php esc_html_e( 'شمارهٔ تماس', 'faucets-valves' ); ?> <span class="req">*</span></label>
					<input id="fv_phone" name="fv_phone" type="tel" inputmode="tel" required autocomplete="tel" dir="ltr" placeholder="09xxxxxxxxx" value="<?php echo esc_attr( $old['p'] ); ?>">
				</p>
				<p class="field">
					<label for="fv_email"><?php esc_html_e( 'ایمیل', 'faucets-valves' ); ?> <span class="opt"><?php esc_html_e( '(اختیاری)', 'faucets-valves' ); ?></span></label>
					<input id="fv_email" name="fv_email" type="email" autocomplete="email" dir="ltr" value="<?php echo esc_attr( $old['e'] ); ?>">
				</p>
				<p class="field">
					<label for="fv_subject"><?php esc_html_e( 'موضوع', 'faucets-valves' ); ?></label>
					<select id="fv_subject" name="fv_subject">
						<?php foreach ( fv_contact_subjects() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $old['s'], $k ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="field field--wide">
					<label for="fv_message"><?php esc_html_e( 'پیام', 'faucets-valves' ); ?> <span class="req">*</span></label>
					<textarea id="fv_message" name="fv_message" rows="6" required minlength="10" placeholder="<?php esc_attr_e( 'برای استعلام قیمت عمده، کد کالاها و تعداد را بنویسید.', 'faucets-valves' ); ?>"><?php echo esc_textarea( $old['m'] ); ?></textarea>
				</p>
				<p class="field field--wide"><button class="pill" type="submit"><?php esc_html_e( 'ارسال پیام', 'faucets-valves' ); ?></button></p>
			</form>
		</section>

		<aside class="contact-card" aria-label="<?php esc_attr_e( 'اطلاعات تماس', 'faucets-valves' ); ?>">
			<dl class="contact-list">
				<?php if ( $phone ) : ?>
					<div><dt><?php esc_html_e( 'تلفن', 'faucets-valves' ); ?></dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', fv_to_latin( $phone ) ) ); ?>" dir="ltr"><?php echo esc_html( $phone ); ?></a></dd></div>
				<?php endif; ?>
				<?php if ( $mobile ) : ?>
					<?php $m = preg_replace( '/[^0-9]/', '', fv_to_latin( $mobile ) ); ?>
					<div><dt><?php esc_html_e( 'موبایل و واتس‌اپ', 'faucets-valves' ); ?></dt><dd><a href="tel:<?php echo esc_attr( $m ); ?>" dir="ltr"><?php echo esc_html( $mobile ); ?></a> · <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/^0/', '98', $m ) ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'واتس‌اپ', 'faucets-valves' ); ?></a></dd></div>
				<?php endif; ?>
				<?php if ( $address ) : ?>
					<div><dt><?php esc_html_e( 'نشانی', 'faucets-valves' ); ?></dt><dd><?php echo esc_html( $address ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $hours ) : ?>
					<div><dt><?php esc_html_e( 'ساعت کاری', 'faucets-valves' ); ?></dt><dd><?php echo esc_html( $hours ); ?></dd></div>
				<?php endif; ?>
			</dl>

			<?php if ( $embed && 0 === strpos( $embed, 'https://' ) ) : ?>
				<iframe class="contact-map" src="<?php echo esc_url( $embed ); ?>" title="<?php esc_attr_e( 'نقشه', 'faucets-valves' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
			<?php endif; ?>

			<?php if ( $lat && $lng ) : ?>
				<p class="contact-route__title"><?php esc_html_e( 'مسیریابی با', 'faucets-valves' ); ?></p>
				<div class="contact-route">
					<a class="btn btn--ghost" href="<?php echo esc_url( "https://nshn.ir/?lat={$lat}&lng={$lng}" ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'نشان', 'faucets-valves' ); ?></a>
					<a class="btn btn--ghost" href="<?php echo esc_url( "https://balad.ir/location?latitude={$lat}&longitude={$lng}&zoom=16" ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'بلد', 'faucets-valves' ); ?></a>
					<a class="btn btn--ghost" href="<?php echo esc_url( "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}" ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'گوگل مپ', 'faucets-valves' ); ?></a>
				</div>
			<?php endif; ?>
		</aside>
	</div>
	<?php
endwhile;

get_footer();
