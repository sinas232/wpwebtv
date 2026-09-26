<?php
/**
 * آرشیو عمومی
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$archive_title = wp_strip_all_tags( get_the_archive_title() );
$archive_desc  = wp_strip_all_tags( (string) get_the_archive_description() );
if ( is_post_type_archive( 'tv_services' ) ) {
	$archive_title = __( 'خدمات تخصصی تعمیر', 'pixva' );
	$archive_desc  = __( 'هر خدمت مسیر عیب‌یابی، قطعه و گارانتی خودش را دارد.', 'pixva' );
} elseif ( is_post_type_archive( 'tv_brands' ) ) {
	$archive_title = __( 'تعمیر بر اساس برند', 'pixva' );
	$archive_desc  = __( 'شاسی، برد و الگوی چشمک هر برند جدا بررسی می‌شود.', 'pixva' );
} elseif ( is_post_type_archive( 'repair_cases' ) ) {
	$archive_title = __( 'نمونه‌کارهای واقعی', 'pixva' );
	$archive_desc  = __( 'قبل و بعد تعمیر، قطعه تعویضی و زمان صرف‌شده.', 'pixva' );
}
?>
<main id="content">
	<?php pixva_page_hero( $archive_title, $archive_desc ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-layout">
		<div class="pixva-layout__main">
		<?php if ( have_posts() ) : ?>
			<div class="pixva-grid pixva-grid--3">
				<?php
				while ( have_posts() ) :
					the_post();
					pixva_post_card();
				endwhile;
				?>
			</div>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'موردی برای نمایش نیست. از پیشخوان یک مورد اضافه کنید یا به محاسبه‌گر برگردید.', 'pixva' ); ?></p>
			<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه هزینه', 'pixva' ); ?></a>
		<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
