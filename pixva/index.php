<?php
/**
 * Fallback template (any query without a more specific template).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<div class="grid grid--cards">
				<?php
				while ( have_posts() ) :
					the_post();
					pixva_card( get_post(), '', 'h2' );
				endwhile;
				?>
			</div>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'موردی پیدا نشد', 'pixva' ), __( 'از جست‌وجو یا ابزارهای عیب‌یابی استفاده کنید.', 'pixva' ), array( __( 'صفحه اصلی', 'pixva' ) => home_url( '/' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
