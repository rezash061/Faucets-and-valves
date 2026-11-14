<?php
/**
 * Stats band: four numbers that count up when they enter the viewport (data-motion="countup").
 * The final number is in the HTML, so it reads correctly without JavaScript.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$stats = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$label = fv_mod( "fv_stat_{$i}_label" );
	if ( '' === trim( (string) $label ) ) {
		continue;
	}
	$stats[] = array(
		'value'  => (int) fv_mod( "fv_stat_{$i}_value" ),
		'suffix' => (string) fv_mod( "fv_stat_{$i}_suffix" ),
		'label'  => $label,
	);
}
if ( ! $stats ) {
	return;
}
?>
<section class="section section--showroom stats" aria-label="<?php esc_attr_e( 'به زبان اعداد', 'faucets-valves' ); ?>">
	<div class="container">
		<ul class="stats__list" data-motion="stagger">
			<?php foreach ( $stats as $stat ) : ?>
				<li class="stat">
					<span class="stat__value">
						<span data-motion="countup" data-to="<?php echo esc_attr( $stat['value'] ); ?>"><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></span><?php echo esc_html( $stat['suffix'] ); ?>
					</span>
					<span class="stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
