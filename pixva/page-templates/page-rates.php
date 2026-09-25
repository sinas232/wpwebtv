<?php
/**
 * Template Name: نرخ‌نامه تعمیرات
 * Template Post Type: page
 *
 * برگه نرخ‌نامه: قیمت پایه خدمات از موتور قیمت سمت سرور (inc/pricing-engine.php)
 * خوانده می‌شود و همراه با توضیح ضرایب برند/سایز و هشدار تعویض کامل پنل نمایش داده می‌شود.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pricing  = pixva_pricing_settings();
$problems = pixva_problem_catalog();
?>
<main id="content">
	<?php pixva_page_hero( __( 'نرخ‌نامه و تعرفه رسمی خدمات تعمیرات تلویزیون ۱۴۰۵ (کف ۸ میلیون تومان)', 'pixva' ), __( 'تعرفه‌های پایه و استاندارد کارگاه پیکسوا با کف مصوب ۸ میلیون تومان برای سایز ۳۲ اینچ و تناسب دقیق برای سایزهای ۵۵ تا ۸۵ اینچ بر اساس قطعات فابریک.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">

		<section class="pixva-card">
			<h2><?php esc_html_e( 'هزینه کارشناسی (عیب‌یابی)', 'pixva' ); ?></h2>
			<p class="pixva-calc__price">
				<strong><?php echo esc_html( pixva_price( (int) $pricing['diagnostic_min'] ) ); ?></strong>
				<?php esc_html_e( 'تا', 'pixva' ); ?>
				<strong><?php echo esc_html( pixva_price( (int) $pricing['diagnostic_max'] ) ); ?></strong>
				<span><?php esc_html_e( 'تومان', 'pixva' ); ?></span>
			</p>
			<p class="pixva-muted"><?php esc_html_e( 'این مبلغ به بازه هر خدمت افزوده می‌شود و در صورت انجام تعمیر در همان جلسه، از فاکتور کسر می‌گردد.', 'pixva' ); ?></p>
		</section>

		<section class="pixva-card" style="margin-top:1.2rem">
			<h2><?php esc_html_e( 'قیمت پایه خدمات (پیش از ضرایب برند و سایز)', 'pixva' ); ?></h2>
			<table class="pixva-rates-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'حداقل (تومان)', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'حداکثر (تومان)', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pricing['services'] as $key => $row ) : ?>
						<?php if ( ! isset( $problems[ $key ] ) ) { continue; } ?>
						<tr>
							<td><?php echo esc_html( $problems[ $key ] ); ?></td>
							<td class="pixva-latin"><?php echo esc_html( pixva_price( (int) $row['min'] ) ); ?></td>
							<td class="pixva-latin"><?php echo esc_html( pixva_price( (int) $row['max'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="pixva-muted"><?php esc_html_e( 'نرخ محاسبه‌شده نهایی = (قیمت پایه × ضریب برند × ضریب سایز) + هزینه کارشناسی. ضریب سایز برای تعویض بک‌لایت کامل و برای تعمیر برد پاور، برد اصلی و صدا با نرخ ضعیف‌تر اعمال می‌شود تا قیمت تلویزیون‌های ۷۵ و ۸۵ اینچ بی‌دلیل نجومی نشود.', 'pixva' ); ?>
		</section>

		<aside class="pixva-notice pixva-notice--warning" style="margin-top:1.2rem">
			<strong><?php esc_html_e( 'تعویض کامل پنل:', 'pixva' ); ?></strong>
			<?php echo esc_html( pixva_panel_replacement_warning() ); ?>
		</aside>

		<section class="pixva-card" style="margin-top:1.2rem">
			<h2><?php esc_html_e( 'ضریب برندها', 'pixva' ); ?></h2>
			<p><?php esc_html_e( 'قیمت پایه در ضریب برند دستگاه ضرب می‌شود؛ مثلاً سونی ۱٫۲ و سامسونگ ۱٫۱۵. فهرست کامل ضرایب را مدیر سایت از پیشخوان ← پیگیری تعمیرات ← نرخ‌نامه کنترل می‌کند.', 'pixva' ); ?></p>
			<p>
				<a class="pixva-btn pixva-btn--cta pixva-btn--bolt" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه هزینه دستگاه من', 'pixva' ); ?></a>
			</p>
		</section>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) {
				echo '<div class="entry-content" style="margin-top:1.2rem">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
