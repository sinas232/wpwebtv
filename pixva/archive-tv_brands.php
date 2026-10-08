<?php
/**
 * Brands archive (/brands/).
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
	<?php pixva_page_header( pixva_routes()['brands']['title'], pixva_route_description( 'brands' ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<ul class="grid grid--brands">
				<?php
				while ( have_posts() ) :
					the_post();
					$pixva_logo = absint( get_post_meta( get_the_ID(), '_pixva_brand_logo', true ) );
					$pixva_en   = (string) get_post_meta( get_the_ID(), '_pixva_brand_en', true );
					?>
					<li class="brand-card">
						<?php if ( $pixva_logo ) : ?>
							<?php
							echo wp_get_attachment_image(
								$pixva_logo,
								'thumbnail',
								false,
								array(
									'alt'     => '',
									'loading' => 'lazy',
									'class'   => 'brand-card__logo',
								)
							);
							?>
						<?php endif; ?>
						<h2 class="brand-card__title"><a class="card__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( '' !== $pixva_en ) : ?>
							<p class="brand-card__en" lang="en" dir="ltr"><?php echo esc_html( $pixva_en ); ?></p>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'هنوز برندی ثبت نشده است', 'pixva' ), '', array( __( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
