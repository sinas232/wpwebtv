<?php
/**
 * Template Name: استعلام انبار قطعات فابریک
 * Template Post Type: page
 *
 * موجودی از رکوردهای CPT «انبار قطعات فابریک» در پیشخوان خوانده می‌شود و در
 * نبود رکورد، کاتالوگ مرجع کارگاه نمایش داده می‌شود.
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
		__( 'انبار مرکزی قطعات فابریک و اصالت کالا', 'pixva' ),
		__( 'استعلام زنده موجودی دست کامل بک‌لایت، برد تغذیه، مین‌برد و فلت‌های COF به‌همراه زمان تحویل قطعات وارداتی و استعلام اصالت با شماره سریال.', 'pixva' ),
		'pixva-hub-hero'
	);
	?>
	<div class="pixva-container pixva-content">
		<?php
		foreach ( array( 14, 22, 51, 30, 46 ) as $pixva_tool_id ) {
			echo pixva_render_tool_by_id( $pixva_tool_id, 'section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<aside class="pixva-notice pixva-notice--info">
			<strong><?php esc_html_e( 'ثبت قطعه در انبار:', 'pixva' ); ?></strong>
			<?php esc_html_e( 'مدیر سایت می‌تواند هر قطعه را با شماره سریال، کد فنی، موجودی، قیمت و مدت گارانتی در پیشخوان ← انبار قطعات ثبت کند؛ استعلام‌های این صفحه از همان رکوردها خوانده می‌شود.', 'pixva' ); ?>
		</aside>
		<?php
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
