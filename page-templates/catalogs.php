<?php
/**
 * Template Name: کاتالوگ‌ها
 *
 * Catalog downloads from Dashboard › کاتالوگ‌ها, with brand filter chips.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

$catalogs = fv_get_catalogs();
$brands   = array();
foreach ( $catalogs as $c ) {
	foreach ( $c['brands'] as $b ) {
		$brands[ $b->slug ] = $b->name;
	}
}

while ( have_posts() ) :
	the_post();
	?>
	<div class="container page-wrap catalogs-page">
		<header class="page-header">
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="page-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( '' !== trim( get_the_content() ) ) : ?>
			<div class="entry-content"><?php the_content(); ?></div>
		<?php endif; ?>

		<?php if ( $catalogs ) : ?>
			<?php if ( count( $brands ) > 1 ) : ?>
				<div class="chips" role="group" aria-label="<?php esc_attr_e( 'فیلتر بر اساس برند', 'faucets-valves' ); ?>" data-catalog-filter>
					<button type="button" class="chip" aria-pressed="true" data-brand=""><?php esc_html_e( 'همه', 'faucets-valves' ); ?></button>
					<?php foreach ( $brands as $slug => $name ) : ?>
						<button type="button" class="chip" aria-pressed="false" data-brand="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<ul class="catalog-grid" data-motion="stagger">
				<?php foreach ( $catalogs as $c ) : ?>
					<li class="catalog-card" data-brands="<?php echo esc_attr( implode( ' ', wp_list_pluck( $c['brands'], 'slug' ) ) ); ?>">
						<a class="catalog-card__link" href="<?php echo esc_url( $c['url'] ); ?>" target="_blank" rel="noopener" download>
							<span class="catalog-card__cover material" data-motion="material">
								<?php
								if ( $c['thumb_id'] ) {
									echo wp_get_attachment_image( $c['thumb_id'], 'fv-tile', false, array( 'alt' => '', 'loading' => 'lazy' ) );
								} else {
									echo '<span class="catalog-card__blank" aria-hidden="true"><b>PDF</b></span>';
								}
								?>
							</span>
							<span class="catalog-card__body">
								<?php if ( $c['brands'] ) : ?>
									<span class="catalog-card__brand"><?php echo esc_html( implode( '، ', wp_list_pluck( $c['brands'], 'name' ) ) ); ?></span>
								<?php endif; ?>
								<span class="catalog-card__title"><?php echo esc_html( $c['title'] ); ?></span>
								<?php if ( $c['note'] ) : ?>
									<span class="catalog-card__note"><?php echo esc_html( $c['note'] ); ?></span>
								<?php endif; ?>
								<span class="catalog-card__foot">
									<span class="catalog-card__dl"><?php echo fv_icon( 'document' ); // phpcs:ignore ?><?php esc_html_e( 'دانلود PDF', 'faucets-valves' ); ?></span>
									<?php if ( $c['size'] ) : ?>
										<span class="catalog-card__size"><?php echo esc_html( $c['size'] ); ?></span>
									<?php endif; ?>
								</span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<script>
			(function(){
				var bar=document.querySelector('[data-catalog-filter]'); if(!bar) return;
				bar.addEventListener('click',function(e){
					var b=e.target.closest('[data-brand]'); if(!b) return;
					bar.querySelectorAll('[data-brand]').forEach(function(x){x.setAttribute('aria-pressed',String(x===b));});
					document.querySelectorAll('.catalog-card').forEach(function(card){
						var show=!b.dataset.brand||(' '+card.dataset.brands+' ').indexOf(' '+b.dataset.brand+' ')>-1;
						card.hidden=!show; if(show){card.style.opacity=1;}
					});
				});
			})();
			</script>
		<?php else : ?>
			<p class="shop-empty"><?php esc_html_e( 'هنوز کاتالوگی منتشر نشده است. از پیشخوان › کاتالوگ‌ها اضافه کنید.', 'faucets-valves' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_footer();
