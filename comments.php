<?php
/**
 * Comments.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			/* translators: %s: number of comments */
			echo esc_html( sprintf( __( '%s دیدگاه', 'faucets-valves' ), number_format_i18n( get_comments_number() ) ) );
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php
	$fv_commenter = wp_get_current_commenter();
	$fv_req       = ' <span class="required" aria-hidden="true">*</span>';
	comment_form( array(
		// Persian labels whatever the site language; no website field (it only attracts spam).
		'fields'               => array(
			'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'نام', 'faucets-valves' ) . $fv_req . '</label><input id="author" name="author" type="text" value="' . esc_attr( $fv_commenter['comment_author'] ) . '" maxlength="245" autocomplete="name" required></p>',
			'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'ایمیل', 'faucets-valves' ) . $fv_req . '</label><input id="email" name="email" type="email" dir="ltr" value="' . esc_attr( $fv_commenter['comment_author_email'] ) . '" maxlength="100" autocomplete="email" required></p>',
			'cookies' => '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . ( empty( $fv_commenter['comment_author_email'] ) ? '' : ' checked' ) . '><label for="wp-comment-cookies-consent">' . esc_html__( 'نام و ایمیلم را برای دیدگاه بعدی در این مرورگر نگه دار.', 'faucets-valves' ) . '</label></p>',
		),
		'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'دیدگاه', 'faucets-valves' ) . $fv_req . '</label><textarea id="comment" name="comment" rows="6" maxlength="65525" required></textarea></p>',
		'comment_notes_before' => '<p class="comment-notes">' . esc_html__( 'ایمیل شما نمایش داده نمی‌شود. بخش‌های ستاره‌دار لازم‌اند.', 'faucets-valves' ) . '</p>',
		'logged_in_as'         => '',
		'title_reply_to'       => __( 'پاسخ به %s', 'faucets-valves' ),
		'cancel_reply_link'    => __( 'لغو پاسخ', 'faucets-valves' ),
		'title_reply'  => __( 'دیدگاه خود را بنویسید', 'faucets-valves' ),
		'label_submit' => __( 'ارسال دیدگاه', 'faucets-valves' ),
		'class_submit' => 'pill',
	) );
	?>
</section>
