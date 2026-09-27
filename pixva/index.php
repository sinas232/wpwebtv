<?php
/**
 * قالب پایه/بلاگ — نسخه ۴٫۰٫۰ (Bento)
 *
 * fallback اصلی وردپرس؛ از جایگاه archive المنتور پیروی می‌کند و گرید
 * کارت‌های بنتو را با بخش قالب card-post رندر می‌کند.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'archive' ) ) :
?>

<main id="content" class="bx-page">
	<div class="bx-wrap">
		<header class="bx-page__head">
			<p class="bx-eyebrow bx-eyebrow--center"><?php esc_html_e( 'مجله تعمیرات', 'pixva' ); ?></p>
			<h1 class="bx-page__title"><?php echo esc_html( is_home() && ! is_front_page() ? get_the_title( (int) get_option( 'page_for_posts' ) ) : __( 'تازه‌ترین مقاله‌های فنی', 'pixva' ) ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="bx-cards">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card', 'post' );
				endwhile;
				?>
			</div>
			<nav class="bx-pagination" aria-label="<?php esc_attr_e( 'صفحه‌بندی', 'pixva' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'type'      => 'list',
							'mid_size'  => 1,
							'prev_text' => '‹',
							'next_text' => '›',
						)
					)
				);
				?>
			</nav>
		<?php else : ?>
			<div class="bx-surface bx-empty">
				<span class="bx-empty__icon"><?php echo pixva_bento_icons( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h2><?php esc_html_e( 'هنوز مقاله‌ای منتشر نشده', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'به‌زودی راهنماهای فنی تعمیر تلویزیون اینجا منتشر می‌شود.', 'pixva' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
endif;

get_footer();
