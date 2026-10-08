<?php
/**
 * Template Name: PIXVA — درباره ما
 *
 * Editor-written content plus only the business facts that are configured
 * in business claims (no invented stats, team, awards or years).
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
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container container--narrow section">
		<?php
		while ( have_posts() ) :
			the_post();
			if ( '' !== trim( get_the_content() ) ) {
				echo '<div class="entry-content">';
				the_content();
				echo '</div>';
			} elseif ( current_user_can( 'edit_post', get_the_ID() ) ) {
				pixva_notice( 'info', __( 'متن این صفحه هنوز نوشته نشده است (این پیام فقط برای ویرایشگران دیده می‌شود).', 'pixva' ) );
			}
		endwhile;
		?>
		<?php
		$pixva_facts = array();
		if ( pixva_has_claim( 'legal_name' ) ) {
			$pixva_facts[ __( 'نام رسمی', 'pixva' ) ] = (string) pixva_claim( 'legal_name' );
		}
		if ( (int) pixva_claim( 'founded_year' ) > 1900 ) {
			$pixva_facts[ __( 'شروع فعالیت', 'pixva' ) ] = pixva_fa_num( (int) pixva_claim( 'founded_year' ) );
		}
		if ( pixva_has_claim( 'service_area' ) ) {
			$pixva_facts[ __( 'محدوده خدمت', 'pixva' ) ] = (string) pixva_claim( 'service_area' );
		}
		if ( $pixva_facts ) :
			?>
			<dl class="details details--specs">
				<?php foreach ( $pixva_facts as $pixva_k => $pixva_v ) : ?>
					<dt><?php echo esc_html( $pixva_k ); ?></dt><dd><?php echo esc_html( $pixva_v ); ?></dd>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<p><a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'contact' ) ); ?>"><?php esc_html_e( 'تماس با ما', 'pixva' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
