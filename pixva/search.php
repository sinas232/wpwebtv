<?php
/**
 * قالب نتایج جستجو — نسخه ۴٫۰٫۰ (Bento)
 *
 * جایگاه search-results با Theme Builder المنتور قابل بازنویسی است.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'search-results' ) ) :
	/* translators: %s: عبارت جستجو */
	$pixva_search_title = sprintf( __( 'نتایج جستجو برای «%s»', 'pixva' ), get_search_query() );
?>

<main id="content" class="bx-page">
	<div class="bx-wrap">
		<header class="bx-page__head">
			<p class="bx-eyebrow bx-eyebrow--center"><?php echo esc_html( sprintf( /* translators: %s: تعداد */ __( '%s نتیجه', 'pixva' ), function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $GLOBALS['wp_query']->found_posts ) : $GLOBALS['wp_query']->found_posts ) ); ?></p>
			<h1 class="bx-page__title"><?php echo esc_html( $pixva_search_title ); ?></h1>
			<div class="bx-u-gap">
				<?php get_search_form(); ?>
			</div>
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
				<h2><?php esc_html_e( 'نتیجه‌ای پیدا نشد', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'عبارت دیگری را امتحان کنید یا مستقیماً از هاب‌های تخصصی ابزار مناسب را باز کنید.', 'pixva' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
endif;

get_footer();
