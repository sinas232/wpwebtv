<?php
/**
 * Template Name: خدمات سازمانی و B2B
 * Template Post Type: page
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
$pixva_eta     = isset( $pixva_control['hub_eta_hours'] ) ? $pixva_control['hub_eta_hours'] : '۲ ساعت';
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'پورتال خدمات سازمانی، ارگان‌ها و هتل‌ها', 'pixva' ),
		__( 'قرارداد نگهداری دوره‌ای، تأمین قطعات فابریک، پوشش اورژانسی ۲۴ ساعته و صدور فاکتور رسمی با گارانتی کتبی.', 'pixva' ),
		'pixva-hub-hero'
	);
	?>
	<section class="pixva-section">
		<div class="pixva-container">
			<div class="pixva-grid pixva-grid--3">
				<article class="pixva-card pixva-service-card pixva-reveal">
					<?php echo pixva_icon( 'box' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h3><?php esc_html_e( 'تأمین قطعه با قیمت عمده', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'دست کامل بک‌لایت، برد تغذیه و مین‌برد فابریک از انبار مرکزی با هولوگرام اصالت و فاکتور رسمی.', 'pixva' ); ?></p>
				</article>
				<article class="pixva-card pixva-service-card pixva-reveal">
					<?php echo pixva_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h3><?php echo esc_html( sprintf( __( 'اعزام تیم زیر %s', 'pixva' ), $pixva_eta ) ); ?></h3>
					<p><?php esc_html_e( 'تکنسین‌های کارگاه مرکزی برای هتل‌ها و مجتمع‌های اداری در تهران، با امکان سرویس شبانه و تعطیلات.', 'pixva' ); ?></p>
				</article>
				<article class="pixva-card pixva-service-card pixva-reveal">
					<?php echo pixva_icon( 'cert' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h3><?php esc_html_e( 'فاکتور رسمی و قرارداد سالانه', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'صدور فاکتور رسمی با شناسه یکتا، گزارش کارشناسی برای بیمه و چک‌اپ دوره‌ای رایگان دستگاه‌ها.', 'pixva' ); ?></p>
				</article>
			</div>
		</div>
	</section>

	<div class="pixva-container pixva-content">
		<?php
		foreach ( array( 21, 54, 52, 47, 53, 60 ) as $pixva_tool_id ) {
			echo pixva_render_tool_by_id( $pixva_tool_id, 'section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) {
				echo '<div class="pixva-content">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
