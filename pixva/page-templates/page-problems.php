<?php
/**
 * Template Name: مشکلات تلویزیون
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'مشکل تلویزیونت چیه؟', 'pixva' ), __( 'مشکل را انتخاب کن تا سریع‌تر راهنمایی‌ات کنیم.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--4">
			<?php foreach ( pixva_finder_items() as $item ) : ?>
				<a class="pixva-card px-problem" href="<?php echo esc_url( pixva_problem_url( $item['key'] ) ); ?>">
					<span class="px-ico"><?php echo pixva_icon( $item['icon'] ); ?></span>
					<h3><?php echo esc_html( $item['title'] ); ?></h3>
					<p><?php echo esc_html( $item['text'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</main>
<?php
get_footer();
