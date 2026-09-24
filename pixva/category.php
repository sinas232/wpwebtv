<?php
/**
 * آرشیو دسته‌بندی مقالات
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$pixva_term = get_queried_object();
?>
<main id="content">
	<?php
	pixva_page_hero(
		$pixva_term instanceof WP_Term ? $pixva_term->name : __( 'دسته‌بندی', 'pixva' ),
		$pixva_term instanceof WP_Term ? $pixva_term->description : ''
	);
	?>
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
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'در این دسته هنوز مقاله‌ای نیست.', 'pixva' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
