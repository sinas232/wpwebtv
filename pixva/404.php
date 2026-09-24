<?php
/**
 * صفحه ۴۰۴
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$suggested = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 3,
		'no_found_rows'  => true,
	)
);
?>
<main id="content">
	<?php pixva_page_hero( __( 'این صفحه پیدا نشد', 'pixva' ), __( 'آدرس عوض شده یا اشتباه تایپ شده است. از جستجو یا مسیرهای زیر استفاده کنید.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content pixva-404">
		<p class="pixva-404__code" aria-hidden="true"><?php echo esc_html( pixva_fa_num( '4' ) ); ?><span><?php echo esc_html( pixva_fa_num( '0' ) ); ?></span><?php echo esc_html( pixva_fa_num( '4' ) ); ?></p>
		<div class="pixva-search" style="max-width:520px;margin:0 auto 1.5rem">
			<?php get_search_form(); ?>
		</div>
		<div class="pixva-hero__actions" style="justify-content:center">
			<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه هزینه', 'pixva' ); ?></a>
			<a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری تعمیر', 'pixva' ); ?></a>
			<a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'بازگشت به خانه', 'pixva' ); ?></a>
		</div>
		<?php if ( ! empty( $suggested ) ) : ?>
			<h2><?php esc_html_e( 'شاید این مقاله‌ها به کارتان بیاید', 'pixva' ); ?></h2>
			<div class="pixva-grid pixva-grid--3" style="text-align:start">
				<?php foreach ( $suggested as $suggested_post ) : ?>
					<?php pixva_post_card( $suggested_post->ID ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
