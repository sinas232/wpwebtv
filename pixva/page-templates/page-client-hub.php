<?php
/**
 * Template Name: پنل مشتریان و گارانتی دیجیتال
 * Template Post Type: page
 *
 * همه داده‌ها از پرونده‌های واقعی (CPT داخلی pixva_orders) و انبار قطعات خوانده
 * می‌شود؛ هیچ کارت یا هش نمونه‌ای از پیش ساخته نشده است.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'پنل مشتریان و گارانتی دیجیتال', 'pixva' ),
		sprintf(
			/* translators: %s: مدت گارانتی */
			__( 'مشاهده همه پرونده‌های ثبت‌شده با شماره همراه، صدور کارت گارانتی دیجیتال با هش SHA-256، استعلام اصالت قطعه و ثبت یادآور سرویس دوره‌ای. گارانتی کتبی %s روزه.', 'pixva' ),
			pixva_fa_num( (string) pixva_warranty_days() )
		),
		'pixva-hub-hero'
	);
	?>
	<div class="pixva-container pixva-content">
		<?php
		foreach ( array( 27, 16, 22, 30, 35, 60 ) as $pixva_tool_id ) {
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
