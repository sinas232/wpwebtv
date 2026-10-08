<?php
/**
 * Single article (/blog/{article}/): TOC, meta, FAQ, related links.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$pixva_id      = get_the_ID();
	$pixva_content = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter; adds heading ids (pixva_heading_ids).
	$pixva_content = str_replace( ']]>', ']]&gt;', $pixva_content );
	$pixva_toc     = pixva_build_toc( $pixva_content );
	$pixva_brand   = pixva_linked_post( $pixva_id, '_pixva_post_brand', 'tv_brands' );
	$pixva_service = pixva_linked_post( $pixva_id, '_pixva_post_service', 'tv_services' );
	$pixva_faq     = pixva_meta_faq_pairs( $pixva_id, '_pixva_post_faq' );
	$pixva_diff    = array(
		'easy'   => __( 'مقدماتی', 'pixva' ),
		'medium' => __( 'متوسط', 'pixva' ),
		'hard'   => __( 'تخصصی', 'pixva' ),
	)[ (string) get_post_meta( $pixva_id, '_pixva_post_difficulty', true ) ] ?? '';
	?>
	<main id="main" class="site-main">
		<article <?php post_class( 'article' ); ?>>
			<header class="page-head">
				<div class="container container--narrow">
					<?php pixva_breadcrumbs(); ?>
					<h1 class="page-head__title"><?php the_title(); ?></h1>
					<?php pixva_post_meta_line(); ?>
					<?php if ( '' !== $pixva_diff ) : ?>
						<p><span class="badge"><?php echo esc_html( sprintf( /* translators: %s: level. */ __( 'سطح: %s', 'pixva' ), $pixva_diff ) ); ?></span></p>
					<?php endif; ?>
				</div>
			</header>
			<div class="container container--narrow section">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="article__media">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'decoding'      => 'async',
						)
					);
					?>
													</figure>
				<?php endif; ?>
				<?php if ( $pixva_toc ) : ?>
					<?php echo $pixva_toc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in pixva_build_toc(). ?>
				<?php endif; ?>
				<div class="entry-content">
					<?php echo $pixva_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
				</div>
				<?php wp_link_pages(); ?>
				<?php if ( $pixva_faq ) : ?>
					<section class="section--tight" aria-labelledby="post-faq"><h2 id="post-faq"><?php esc_html_e( 'پرسش و پاسخ', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq ); ?></section>
				<?php endif; ?>
				<?php pixva_problem_chips( $pixva_id ); ?>
				<?php
				$pixva_links = array_filter( array( $pixva_service, $pixva_brand ) );
				pixva_related_links( __( 'صفحه‌های مرتبط', 'pixva' ), $pixva_links );
				pixva_related_links( __( 'کدهای خطای مرتبط', 'pixva' ), pixva_posts_by_meta( 'pixva_error', '_pixva_err_article', $pixva_id, 6 ) );
				pixva_related_links( __( 'مقاله‌های مرتبط', 'pixva' ), pixva_posts_by_problem( 'post', pixva_post_problem_ids( $pixva_id ), $pixva_id, 4 ) );
				pixva_cta_box( '', '', 'article' );
				?>
				<?php if ( comments_open() || get_comments_number() ) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			</div>
		</article>
	</main>
	<?php
endwhile;
get_footer();
