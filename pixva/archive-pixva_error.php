<?php
/**
 * Error-code database (/error-codes/) with server-side search and filters
 * (works without JS); lookup.js adds live results via REST.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
global $wp_query;
$pixva_q      = pixva_get_request_var( 'q' );
$pixva_brand  = absint( pixva_get_request_var( 'brand', '0' ) );
$pixva_sev    = sanitize_key( pixva_get_request_var( 'severity' ) );
$pixva_brands = pixva_brand_choices();
$pixva_filter = '' !== $pixva_q || $pixva_brand || '' !== $pixva_sev;
?>
<main id="main" class="site-main">
	<?php pixva_page_header( pixva_routes()['error_codes']['title'], pixva_route_description( 'error_codes' ) ); ?>
	<div class="container section">
		<form class="filters" method="get" action="<?php echo esc_url( pixva_route_url( 'error_codes' ) ); ?>" role="search" data-error-search data-track-submit="error_code_search">
			<div class="field">
				<label for="err-q"><?php esc_html_e( 'کد خطا، تعداد چشمک یا شرح', 'pixva' ); ?></label>
				<input type="search" id="err-q" name="q" value="<?php echo esc_attr( $pixva_q ); ?>" placeholder="<?php esc_attr_e( 'مثلاً ۶ بار چشمک یا E-203', 'pixva' ); ?>" autocomplete="off">
			</div>
			<?php if ( $pixva_brands ) : ?>
				<div class="field">
					<label for="err-brand"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
					<select id="err-brand" name="brand">
						<option value=""><?php esc_html_e( 'همه برندها', 'pixva' ); ?></option>
						<?php foreach ( $pixva_brands as $pixva_bid => $pixva_bt ) : ?>
							<option value="<?php echo esc_attr( (string) $pixva_bid ); ?>" <?php selected( $pixva_brand, $pixva_bid ); ?>><?php echo esc_html( $pixva_bt ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<div class="field">
				<label for="err-sev"><?php esc_html_e( 'شدت', 'pixva' ); ?></label>
				<select id="err-sev" name="severity">
					<option value=""><?php esc_html_e( 'همه', 'pixva' ); ?></option>
					<?php foreach ( pixva_severity_levels() as $pixva_k => $pixva_l ) : ?>
						<option value="<?php echo esc_attr( $pixva_k ); ?>" <?php selected( $pixva_sev, $pixva_k ); ?>><?php echo esc_html( $pixva_l ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="filters__actions">
				<button class="btn btn--primary" type="submit"><?php esc_html_e( 'جست‌وجو', 'pixva' ); ?></button>
				<?php if ( $pixva_filter ) : ?>
					<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'error_codes' ) ); ?>"><?php esc_html_e( 'حذف فیلترها', 'pixva' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
		<p class="results-count" data-error-count aria-live="polite">
			<?php
			if ( $pixva_filter ) {
				echo esc_html( sprintf( /* translators: %s: count. */ __( '%s کد خطا پیدا شد.', 'pixva' ), pixva_fa_num( (int) $wp_query->found_posts ) ) );
			}
			?>
		</p>
		<div data-error-results>
			<?php if ( have_posts() ) : ?>
				<ul class="code-list">
					<?php
					while ( have_posts() ) :
						the_post();
						$pixva_c = pixva_error_code_card_data( get_post() );
						?>
						<li class="card card--error">
							<div class="card__body">
								<p class="card__meta"><?php echo esc_html( $pixva_c['brand'] ); ?></p>
								<h2 class="card__title"><a class="card__link" href="<?php echo esc_url( $pixva_c['url'] ); ?>"><?php echo esc_html( $pixva_c['title'] ); ?></a></h2>
								<?php if ( '' !== $pixva_c['code'] ) : ?>
									<p class="code" dir="auto"><?php echo esc_html( $pixva_c['code'] ); ?></p>
								<?php endif; ?>
								<p class="card__text"><?php echo esc_html( $pixva_c['meaning'] ); ?></p>
								<?php pixva_severity_badge( $pixva_c['severity'] ); ?>
							</div>
						</li>
					<?php endwhile; ?>
				</ul>
				<?php pixva_pagination(); ?>
			<?php elseif ( $pixva_filter ) : ?>
				<?php
				pixva_empty_state(
					__( 'کدی با این مشخصات ثبت نشده است', 'pixva' ),
					__( 'عبارت دیگری امتحان کنید یا با ابزار تشخیص، علت‌های محتمل را بر اساس نشانه‌ها ببینید.', 'pixva' ),
					array(
						__( 'تشخیص آنلاین', 'pixva' )      => pixva_route_url(
							'diagnosis',
							array(
								'problem' => 'blink',
								'step'    => '1',
							)
						),
						__( 'ثبت درخواست بررسی', 'pixva' ) => pixva_route_url( 'booking' ),
					)
				);
				?>
			<?php else : ?>
				<?php
				pixva_empty_state(
					__( 'پایگاه کدهای خطا در حال تکمیل است', 'pixva' ),
					__( 'تا آن زمان، ابزار تشخیص بر اساس الگوی چشمک و نشانه‌ها راهنمایی می‌کند.', 'pixva' ),
					array(
						__( 'تشخیص آنلاین', 'pixva' ) => pixva_route_url(
							'diagnosis',
							array(
								'problem' => 'blink',
								'step'    => '1',
							)
						),
					)
				);
				?>
			<?php endif; ?>
		</div>
		<?php pixva_notice( 'warning', __( 'برد پاور حتی پس از کشیدن دوشاخه بار الکتریکی نگه می‌دارد. برای بررسی کدهای خطا هرگز قاب دستگاه را باز نکنید.', 'pixva' ), __( 'ایمنی', 'pixva' ) ); ?>
	</div>
</main>
<?php
get_footer();
