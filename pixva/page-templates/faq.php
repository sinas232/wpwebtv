<?php
/**
 * Template Name: PIXVA — پرسش‌های متداول
 *
 * All published pixva_faq items grouped by topic. FAQPage schema is
 * emitted from the same items (inc/schema.php) — no invented Q&A.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Elementor takeover: when this page is saved with Elementor the document
// below the header IS the page (classic sections are skipped). Classic
// behavior is untouched whenever the flag/data is absent.
if ( pixva_elementor_takeover() ) {
	pixva_elementor_render_page( '' );
	return;
}
get_header();
$pixva_items  = pixva_faq_items();
$pixva_topics = array(
	'general'  => __( 'عمومی', 'pixva' ),
	'booking'  => __( 'ثبت درخواست', 'pixva' ),
	'pricing'  => __( 'هزینه', 'pixva' ),
	'tracking' => __( 'پیگیری', 'pixva' ),
	'warranty' => __( 'گارانتی', 'pixva' ),
);
$pixva_groups = array();
foreach ( $pixva_items as $pixva_it ) {
	$pixva_t                    = isset( $pixva_topics[ $pixva_it['topic'] ] ) ? $pixva_it['topic'] : 'general';
	$pixva_groups[ $pixva_t ][] = $pixva_it;
}
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<?php if ( ! $pixva_groups ) : ?>
			<?php pixva_empty_state( __( 'هنوز پرسشی منتشر نشده است', 'pixva' ), __( 'پرسش خود را از طریق فرم تماس بفرستید.', 'pixva' ), array( __( 'تماس با ما', 'pixva' ) => pixva_route_url( 'contact' ) ) ); ?>
		<?php else : ?>
			<?php if ( count( $pixva_groups ) > 1 ) : ?>
				<nav class="toc" aria-label="<?php esc_attr_e( 'دسته‌ها', 'pixva' ); ?>"><ul>
					<?php foreach ( $pixva_topics as $pixva_k => $pixva_label ) : ?>
						<?php if ( ! empty( $pixva_groups[ $pixva_k ] ) ) : ?>
							<li><a href="#faq-<?php echo esc_attr( $pixva_k ); ?>"><?php echo esc_html( $pixva_label ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul></nav>
			<?php endif; ?>
			<?php foreach ( $pixva_topics as $pixva_k => $pixva_label ) : ?>
				<?php if ( ! empty( $pixva_groups[ $pixva_k ] ) ) : ?>
					<section class="section--tight" id="faq-<?php echo esc_attr( $pixva_k ); ?>" aria-labelledby="faq-h-<?php echo esc_attr( $pixva_k ); ?>">
						<h2 id="faq-h-<?php echo esc_attr( $pixva_k ); ?>"><?php echo esc_html( $pixva_label ); ?></h2>
						<?php pixva_faq_list( $pixva_groups[ $pixva_k ], true ); ?>
					</section>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'faq' ); ?></div>
</main>
<?php
get_footer();
