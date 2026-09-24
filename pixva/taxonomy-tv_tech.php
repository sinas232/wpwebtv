<?php
/**
 * آرشیو تکنولوژی صفحه
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$pixva_term = get_queried_object();
$name       = $pixva_term instanceof WP_Term ? $pixva_term->name : __( 'تکنولوژی', 'pixva' );
$desc       = $pixva_term instanceof WP_Term && $pixva_term->description ? $pixva_term->description : __( 'تفاوت تعمیر LED، QLED، OLED و پلاسما در قطعه، ریسک و زمان کار است.', 'pixva' );
?>
<main id="content">
	<?php pixva_page_hero( sprintf( /* translators: %s: تکنولوژی */ __( 'تعمیر نمایشگر %s', 'pixva' ), $name ), $desc ); ?>
	<div class="pixva-container pixva-content">
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
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'برای این تکنولوژی هنوز محتوایی ثبت نشده است.', 'pixva' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
