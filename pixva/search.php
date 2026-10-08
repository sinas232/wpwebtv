<?php
/**
 * Search results across content types (§18). Noindex (seo.php).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
global $wp_query;
$pixva_q     = get_search_query();
$pixva_types = array(
	'post'         => __( 'مقاله', 'pixva' ),
	'page'         => __( 'صفحه', 'pixva' ),
	'tv_services'  => __( 'خدمت', 'pixva' ),
	'tv_brands'    => __( 'برند', 'pixva' ),
	'tv_model'     => __( 'مدل', 'pixva' ),
	'pixva_error'  => __( 'کد خطا', 'pixva' ),
	'repair_cases' => __( 'نمونه‌کار', 'pixva' ),
);
?>
<main id="main" class="site-main">
	<?php
	pixva_page_header(
		'' !== $pixva_q ? sprintf( /* translators: %s: query. */ __( 'نتایج جست‌وجو برای «%s»', 'pixva' ), $pixva_q ) : __( 'جست‌وجو', 'pixva' ),
		'' !== $pixva_q ? sprintf( /* translators: %s: count. */ __( '%s نتیجه پیدا شد.', 'pixva' ), pixva_fa_num( (int) $wp_query->found_posts ) ) : ''
	);
	?>
	<div class="container section">
		<?php get_search_form(); ?>
		<?php if ( '' !== $pixva_q && have_posts() ) : ?>
			<ol class="results">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="result">
						<p class="result__type"><?php echo esc_html( $pixva_types[ get_post_type() ] ?? '' ); ?></p>
						<h2 class="result__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="result__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
					</li>
				<?php endwhile; ?>
			</ol>
			<?php pixva_pagination(); ?>
		<?php elseif ( '' !== $pixva_q ) : ?>
			<?php
			pixva_empty_state(
				__( 'نتیجه‌ای پیدا نشد', 'pixva' ),
				__( 'عبارت کوتاه‌تری امتحان کنید، یا ایراد را با ابزار تشخیص بررسی کنید. برای کد خطا، پایگاه کدهای خطا را جست‌وجو کنید.', 'pixva' ),
				array(
					__( 'تشخیص آنلاین', 'pixva' )     => pixva_route_url( 'diagnosis' ),
					__( 'پایگاه کدهای خطا', 'pixva' ) => pixva_route_url( 'error_codes', array( 'q' => $pixva_q ) ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
