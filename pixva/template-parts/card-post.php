<?php
/**
 * بخش قالب: کارت نوشته بنتو (گرید آرشیو/جستجو/بلاگ)
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_cats = get_the_category();
?>
<article <?php post_class( 'bx-card bx-reveal' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="bx-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'pixva-card' ); ?>
		</a>
	<?php endif; ?>
	<div class="bx-card__body">
		<?php if ( ! empty( $pixva_cats ) ) : ?>
			<a class="bx-chip" href="<?php echo esc_url( get_category_link( $pixva_cats[0] ) ); ?>"><?php echo esc_html( $pixva_cats[0]->name ); ?></a>
		<?php endif; ?>
		<h2 class="bx-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="bx-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
		<div class="bx-card__foot">
			<span><?php echo esc_html( get_the_date() ); ?></span>
			<a class="bx-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'ادامه مطلب ←', 'pixva' ); ?></a>
		</div>
	</div>
</article>
