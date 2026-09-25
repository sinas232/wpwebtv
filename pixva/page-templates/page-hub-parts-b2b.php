<?php
/**
 * Template Name: هاب ۵: انبار قطعات و خدمات سازمانی
 * Template Post Type: page
 *
 * ابزارهای ۴۶ تا ۶۰: جدول برندها، سرویس در محل، محافظ ضربه، استبیلایزر،
 * تست ریموت، زمان تحویل قطعات، بیمه، محاسبه حمل، اشتراک، سنجش دیوار،
 * نمای انفجاری، تور مجازی کارگاه، تداخل سیگنال، بررسی کابل و گزارش کارشناسی.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_hub = 'parts-b2b';
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'هاب ۵ — انبار قطعات فابریک و خدمات سازمانی', 'pixva' ),
		__( 'استعلام موجودی انبار مرکزی، زمان تحویل قطعات وارداتی، قرارداد نگهداری هتل‌ها و ارگان‌ها، اشتراک پیکسوا پلاس و صدور گزارش کارشناسی برای بیمه.', 'pixva' ),
		'pixva-hub-hero'
	);

	pixva_render_hub_index( $pixva_hub );
	?>

	<section class="pixva-section pixva-section--alt" id="hub-virtual-tour">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'کارگاه مرکزی', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ); ?></h2>
				<p><?php echo esc_html( pixva_workshop_setting( 'address' ) ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--3">
				<?php foreach ( pixva_branches() as $branch ) : ?>
					<article class="pixva-card pixva-branch-card">
						<h3><?php echo esc_html( $branch['title'] ); ?></h3>
						<p class="pixva-muted"><?php echo esc_html( $branch['address'] ); ?></p>
						<p><?php echo pixva_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $branch['hours'] ); ?></span></p>
						<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_tel_href( $branch['phone'] ) ); ?>"><?php echo esc_html( pixva_fa_num( $branch['phone'] ) ); ?></a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	pixva_render_hub_tools( $pixva_hub );

	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) {
			echo '<div class="pixva-container pixva-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();
