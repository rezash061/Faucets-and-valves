<?php
/**
 * Hero with Cinematic Reveal (data-motion="cinematic").
 *
 * The image is a real <img> with fetchpriority="high" and is never lazy-loaded, so it counts
 * as the Largest Contentful Paint. Headline words are wrapped on the server (no layout shift).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$image_id = (int) fv_mod( 'fv_hero_image' );
$cta_url  = fv_mod( 'fv_hero_cta_url' );
if ( ! $cta_url && function_exists( 'wc_get_page_permalink' ) ) {
	$cta_url = wc_get_page_permalink( 'shop' );
}
$cta2_url = fv_mod( 'fv_hero_cta2_url' ) ? fv_mod( 'fv_hero_cta2_url' ) : fv_catalogs_page_url();
?>
<section class="hero" data-motion="cinematic" aria-labelledby="hero-title">
	<div class="hero__mask">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'fv-hero', false, array(
				'class'         => 'hero__img',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
				'sizes'         => '100vw',
			) );
		} else {
			echo '<div class="hero__img hero__img--placeholder" aria-hidden="true"></div>';
		}
		?>
	</div>
	<div class="hero__shade" aria-hidden="true"></div>

	<div class="container hero__content">
		<?php if ( fv_mod( 'fv_hero_eyebrow' ) ) : ?>
			<p class="hero__eyebrow"><?php echo esc_html( fv_mod( 'fv_hero_eyebrow' ) ); ?></p>
		<?php endif; ?>

		<h1 class="hero__title" id="hero-title"><?php echo fv_split_words( fv_mod( 'fv_hero_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></h1>

		<?php if ( fv_mod( 'fv_hero_subtitle' ) ) : ?>
			<p class="hero__sub"><?php echo esc_html( fv_mod( 'fv_hero_subtitle' ) ); ?></p>
		<?php endif; ?>

		<div class="hero__ctas">
			<?php if ( $cta_url ) : ?>
				<a class="btn btn--brass" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( fv_mod( 'fv_hero_cta_text' ) ); ?>
					<?php echo fv_icon( 'arrow' ); // phpcs:ignore ?>
				</a>
			<?php endif; ?>
			<?php if ( $cta2_url && fv_mod( 'fv_hero_cta2_text' ) ) : ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( fv_mod( 'fv_hero_cta2_text' ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
