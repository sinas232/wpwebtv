<?php
/**
 * Template Name: نرخ‌نامه تعمیرات
 * Template Post Type: page
 *
 * نمایش شفاف نرخ‌نامه ۱۴۰۵ از روی منبع حقیقت سمت سرور.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$rates    = pixva_get_rates();
$problems = pixva_problem_catalog();
$brands   = pixva_brand_catalog();
$techs    = pixva_tech_catalog();
?>
<main id="content">
	<?php pixva_page_hero( __( 'نرخ‌نامه ۱۴۰۵: همه عددها روی میز', 'pixva' ), __( 'کف و سقف پایه هر خدمت برای مبنای ۳۲ اینچ، به‌همراه ضریب برند و سایز. همین جدول، مبنای محاسبه‌گر و مبنای فاکتور کارگاه است — یک عدد برای همه.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<section class="pixva-card pixva-reveal">
			<h2><?php esc_html_e( 'هزینه کارشناسی و نکته پنل', 'pixva' ); ?></h2>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: کف کارشناسی، 2: سقف کارشناسی */
						__( 'کارشناسی و عیب‌یابی: %1$s تا %2$s تومان.', 'pixva' ),
						pixva_price( (int) $rates['expert_min'] ),
						pixva_price( (int) $rates['expert_max'] )
					)
				);
				?>
			</p>
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'تعویض کامل پنل خارج از جدول است و اغلب از ۱۰ میلیون تومان شروع می‌شود. اگر سلول سالم باشد، بندینگ جایگزین به‌صرفه‌تر است.', 'pixva' ); ?></p>
			<p class="pixva-muted"><?php esc_html_e( 'اثر سایز برای بک‌لایت و پنل کامل است و برای برد پاور، برد اصلی و صدا ملایم می‌شود تا تعمیر برد روی تلویزیون بزرگ بی‌دلیل گران نشود.', 'pixva' ); ?></p>
			<h3><?php esc_html_e( 'قیمت تو چطور حساب می‌شود؟', 'pixva' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'کف و سقف پایه خدمت را از جدول پایین بردار.', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'در ضریب برند و تکنولوژی دستگاهت ضرب کن.', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'ضریب سایز را اعمال کن — برای برد و صدا فقط یک‌چهارم اختلاف سایز حساب می‌شود.', 'pixva' ); ?></li>
			</ol>
			<p class="pixva-muted"><?php esc_html_e( 'حوصله حساب نداری؟ محاسبه‌گر همین کار را در ۳۰ ثانیه می‌کند.', 'pixva' ); ?></p>
		</section>

		<section class="pixva-card pixva-reveal" style="margin-top:1.2rem">
			<h2><?php esc_html_e( 'کف و سقف پایه هر خدمت (تومان)', 'pixva' ); ?></h2>
			<div class="pixva-rates-scroll">
				<table class="pixva-rates-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'کف', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'سقف', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'زمان تقریبی', 'pixva' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rates['base'] as $key => $pair ) : ?>
							<tr>
								<td><?php echo esc_html( isset( $problems[ $key ] ) ? $problems[ $key ] : $key ); ?></td>
								<td><?php echo esc_html( pixva_price( (int) $pair[0] ) ); ?></td>
								<td><?php echo esc_html( pixva_price( (int) $pair[1] ) ); ?></td>
								<td><?php echo esc_html( pixva_fa_num( isset( $rates['days'][ $key ] ) ? $rates['days'][ $key ] : '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<div class="pixva-grid pixva-grid--2" style="margin-top:1.2rem;align-items:start">
			<section class="pixva-card pixva-reveal">
				<h2><?php esc_html_e( 'ضریب برندها', 'pixva' ); ?></h2>
				<ul class="pixva-rate-list">
					<?php foreach ( $rates['brand'] as $key => $coef ) : ?>
						<li>
							<span><?php echo esc_html( isset( $brands[ $key ] ) ? $brands[ $key ]['fa'] : $key ); ?></span>
							<strong><?php echo esc_html( pixva_fa_num( number_format( (float) $coef, 2 ) ) ); ?></strong>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
			<div>
				<section class="pixva-card pixva-reveal">
					<h2><?php esc_html_e( 'ضریب تکنولوژی و سایز', 'pixva' ); ?></h2>
					<ul class="pixva-rate-list">
						<?php foreach ( $rates['tech'] as $key => $coef ) : ?>
							<li>
								<span><?php echo esc_html( isset( $techs[ $key ] ) ? $techs[ $key ] : $key ); ?></span>
								<strong><?php echo esc_html( pixva_fa_num( number_format( (float) $coef, 2 ) ) ); ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
					<h3><?php esc_html_e( 'سایز (اینچ)', 'pixva' ); ?></h3>
					<ul class="pixva-rate-list">
						<?php foreach ( $rates['size'] as $size => $coef ) : ?>
							<li>
								<span><?php echo esc_html( pixva_fa_num( $size ) . ' ' . __( 'اینچ', 'pixva' ) ); ?></span>
								<strong><?php echo esc_html( pixva_fa_num( number_format( (float) $coef, 2 ) ) ); ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
				<section class="pixva-card pixva-reveal" style="margin-top:1.2rem">
					<h2><?php esc_html_e( 'نمونه واقعی', 'pixva' ); ?></h2>
					<?php
					$demo_back = pixva_calculate_estimate( 'samsung', 'led', '55', 'backlight' );
					$demo_pwr  = pixva_calculate_estimate( 'samsung', 'led', '55', 'powerboard' );
					?>
					<?php if ( $demo_back ) : ?>
						<p><?php esc_html_e( 'سامسونگ ۵۵ اینچ LED — تعویض بک‌لایت:', 'pixva' ); ?> <strong><?php echo esc_html( pixva_price( $demo_back['min'] ) . ' تا ' . pixva_price( $demo_back['max'] ) . ' ' . __( 'تومان', 'pixva' ) ); ?></strong></p>
					<?php endif; ?>
					<?php if ( $demo_pwr ) : ?>
						<p><?php esc_html_e( 'سامسونگ ۵۵ اینچ LED — برد پاور:', 'pixva' ); ?> <strong><?php echo esc_html( pixva_price( $demo_pwr['min'] ) . ' تا ' . pixva_price( $demo_pwr['max'] ) . ' ' . __( 'تومان', 'pixva' ) ); ?></strong></p>
					<?php endif; ?>
					<p><a class="pixva-btn pixva-btn--cta pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه برای دستگاه من', 'pixva' ); ?></a></p>
				</section>
			</div>
		</div>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) {
				echo '<div class="entry-content pixva-card" style="margin-top:1.2rem">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
