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
	comment_form( array(
		'title_reply'  => __( 'دیدگاه خود را بنویسید', 'faucets-valves' ),
		'label_submit' => __( 'ارسال دیدگاه', 'faucets-valves' ),
		'class_submit' => 'btn btn--brass',
	) );
	?>
</section>
