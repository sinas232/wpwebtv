<?php
/**
 * آرشیو نوع خرابی تلویزیون
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$pixva_term = get_queried_object();
$name       = $pixva_term instanceof WP_Term ? $pixva_term->name : __( 'نوع خرابی', 'pixva' );
$desc       = $pixva_term instanceof WP_Term && $pixva_term->description ? $pixva_term->description : __( 'مقاله‌ها، خدمات و نمونه‌کارهای مرتبط با این خرابی.', 'pixva' );
?>
<main id="content">
	<?php pixva_page_hero( $name, $desc ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-cta-box">
			<div>
				<h2><?php echo esc_html( sprintf( /* translators: %s: نام خرابی */ __( 'هزینه تعمیر «%s» را برآورد کنید', 'pixva' ), $name ) ); ?></h2>
				<p><?php esc_html_e( 'برند و سایز را انتخاب کنید تا بازه کارگاه را ببینید.', 'pixva' ); ?></p>
			</div>
			<a class="pixva-btn pixva-btn--cta pixva-btn--bolt" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه‌گر', 'pixva' ); ?></a>
		</div>
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
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'برای این خرابی هنوز محتوایی ثبت نشده است.', 'pixva' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
