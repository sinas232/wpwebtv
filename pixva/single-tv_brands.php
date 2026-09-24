<?php
/**
 * صفحه اختصاصی برند
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	while ( have_posts() ) :
		the_post();
		$slug = sanitize_title( get_post_field( 'post_name' ) );
		pixva_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : __( 'عیب‌های شایع، الگوی چشمک و مسیر تعمیر این برند در کارگاه پیکسوا.', 'pixva' ) );
		?>
		<div class="pixva-container pixva-content">
			<div class="pixva-grid pixva-grid--2" style="align-items:start">
				<article <?php post_class( 'entry-content' ); ?>>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'pixva-wide' );
					}
					the_content();
					?>
					<p><a class="pixva-btn pixva-btn--ghost pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'دیدن کدهای خطای این خانواده', 'pixva' ); ?></a></p>
				</article>
				<aside>
					<?php
					pixva_render_calculator(
						array(
							'preset_brand' => $slug,
							'compact'      => true,
						)
					);
					?>
				</aside>
			</div>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();
