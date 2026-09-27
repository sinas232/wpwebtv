<?php
/**
 * قالب آرشیو — نسخه ۴٫۰٫۰ (Bento)
 *
 * گرید کارت‌های هم‌ارتفاع auto-fit با پوسته بنتو. جایگاه archive با
 * Theme Builder المنتور قابل بازنویسی است.
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
			<p class="bx-eyebrow bx-eyebrow--center"><?php esc_html_e( 'آرشیو مطالب', 'pixva' ); ?></p>
			<h1 class="bx-page__title"><?php the_archive_title(); ?></h1>
			<?php
			the_archive_description( '<p class="bx-hero__lead">', '</p>' );
			?>
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
				<span class="bx-empty__icon"><?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h2><?php esc_html_e( 'موردی پیدا نشد', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'هنوز محتوایی در این بایگانی ثبت نشده است؛ از جستجو یا هاب‌های تخصصی استفاده کنید.', 'pixva' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
endif;

get_footer();
