<?php
/**
 * Not found.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container page-wrap not-found">
	<p class="not-found__code" aria-hidden="true">۴۰۴</p>
	<h1 class="entry-title"><?php esc_html_e( 'این صفحه پیدا نشد', 'faucets-valves' ); ?></h1>
	<p><?php esc_html_e( 'شاید محصول جابه‌جا شده باشد. نام یا کد کالا را جستجو کنید.', 'faucets-valves' ); ?></p>
	<?php get_search_form(); ?>
</div>
<?php
get_footer();
