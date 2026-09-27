<?php
/**
 * قالب برگه — نسخه ۴٫۰٫۰ (Bento)
 *
 * جایگاه page با Theme Builder المنتور قابل بازنویسی است؛ در غیر این صورت
 * سربرگ متمرکز + سطح مقاله سفید رندر می‌شود.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'page' ) ) :
?>

<main id="content" class="bx-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="bx-wrap">
			<header class="bx-page__head">
				<h1 class="bx-page__title"><?php the_title(); ?></h1>
			</header>

			<article <?php post_class( 'bx-article' ); ?>>
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'pixva-wide' );
				}
				the_content();
				wp_link_pages();
				?>
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	<?php endwhile; ?>
</main>

<?php
endif;

get_footer();
