<?php
/**
 * فهرست مجله و fallback قالب
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// تیتر هوشمند: صفحه مجله، آرشیو، دسته یا برچسب.
if ( is_home() && ! is_front_page() ) {
	$hero_title = __( 'مجله تخصصی پیکسوا', 'pixva' );
	$hero_sub   = __( 'عیب‌یابی واقعی تلویزیون، قدم‌به‌قدم و بدون لورم‌ایپسوم. هر مقاله را می‌شود همین امروز روی دستگاه خودت اجرا کرد.', 'pixva' );
} else {
	$hero_title = wp_strip_all_tags( get_the_archive_title() );
	$hero_sub   = wp_strip_all_tags( (string) get_the_archive_description() );
	if ( '' === $hero_title ) {
		$hero_title = __( 'مجله تخصصی پیکسوا', 'pixva' );
	}
}
?>
<main id="content">
	<?php pixva_page_hero( $hero_title, $hero_sub ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-layout pixva-layout--blog">
			<div>
				<?php if ( have_posts() ) : ?>
					<?php
					// مطلب اول صفحه اول، ویژه نمایش داده می‌شود.
					$pixva_count = 0;
					$pixva_first = ! is_paged();
					$pixva_open  = false;
					while ( have_posts() ) :
						the_post();
						++$pixva_count;
						if ( 1 === $pixva_count && $pixva_first ) {
							pixva_featured_card();
							continue;
						}
						if ( ! $pixva_open ) {
							echo '<div class="pixva-grid pixva-grid--2 pixva-blog-grid">';
							$pixva_open = true;
						}
						pixva_post_card();
					endwhile;
					if ( $pixva_open ) {
						echo '</div>';
					}
					?>
					<?php pixva_pagination(); ?>
				<?php else : ?>
					<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'هنوز مطلبی در این فهرست نیست. سری به محاسبه‌گر بزن یا سؤالت را بپرس.', 'pixva' ); ?></p>
					<p><a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه هزینه تعمیر', 'pixva' ); ?></a></p>
				<?php endif; ?>
			</div>
			<aside>
				<?php if ( is_active_sidebar( 'blog-sidebar' ) ) : ?>
					<?php dynamic_sidebar( 'blog-sidebar' ); ?>
				<?php else : ?>
					<section class="pixva-card pixva-widget">
						<h3 class="pixva-widget__title"><?php esc_html_e( 'جستجو در مجله', 'pixva' ); ?></h3>
						<?php get_search_form(); ?>
					</section>
					<section class="pixva-card pixva-widget">
						<h3 class="pixva-widget__title"><?php esc_html_e( 'خرابی‌ات را پیدا کن', 'pixva' ); ?></h3>
						<ul class="pixva-widget-list">
							<?php
							$pixva_terms = get_terms(
								array(
									'taxonomy'   => 'tv_problem',
									'hide_empty' => true,
									'number'     => 6,
								)
							);
							if ( ! is_wp_error( $pixva_terms ) && ! empty( $pixva_terms ) ) {
								foreach ( $pixva_terms as $pixva_term ) {
									echo '<li><a href="' . esc_url( get_term_link( $pixva_term ) ) . '">' . esc_html( $pixva_term->name ) . '</a></li>';
								}
							} else {
								echo '<li><a href="' . esc_url( pixva_page_url( 'error-codes' ) ) . '">' . esc_html__( 'کدهای خطا و چشمک چراغ', 'pixva' ) . '</a></li>';
							}
							?>
						</ul>
					</section>
					<section class="pixva-card pixva-widget pixva-widget--cta">
						<h3 class="pixva-widget__title"><?php esc_html_e( 'خواندی و درست نشد؟', 'pixva' ); ?></h3>
						<p><?php esc_html_e( 'بازه هزینه و زمان تعمیر دستگاهت را در ۳۰ ثانیه ببین.', 'pixva' ); ?></p>
						<a class="pixva-btn pixva-btn--cta pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه‌گر هزینه', 'pixva' ); ?></a>
					</section>
				<?php endif; ?>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();
