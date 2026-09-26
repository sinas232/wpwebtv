<?php
/**
 * رندر اجزای تعاملی سنگین پیکسوا (inc/interactive-tools.php)
 *
 * این پرونده فقط «اجزای بصری/تعاملی» مشترک را رندر می‌کند:
 * - هاب پیگیری پرونده و استعلام اصالت گارانتی دیجیتال (ابزار ۱۷ و ۱۹)
 * - تستر زنده پیکسل‌سوختگی RGB و چرخه احیای OLED (ابزار ۶ و ۷)
 * - هاب اعزام اورژانسی و پیگیری آنلاین (ابزار ۱۷ و ۱۹)
 *
 * رابط دستیار هوش مصنوعی در inc/ai-bot.php و بقیه ۶۰ ابزار در
 * inc/tool-renderers.php پیاده‌سازی شده‌اند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_render_screen_rgb_tester' ) ) {
	/**
	 * ابزار ۶ و ۷: تستر RGB پنل و چرخه احیای OLED.
	 *
	 * @param array $args گزینه‌ها: wrap و mode (rgb|oled).
	 * @return void
	 */
	function pixva_render_screen_rgb_tester( $args = array() ) {
		$args = wp_parse_args( $args, array( 'wrap' => true, 'mode' => 'rgb' ) );
		$mode = 'oled' === $args['mode'] ? 'oled' : 'rgb';

		$patterns = array(
			'#FF0000' => __( 'قرمز خالص (تست پیکسل سوخته قرمز)', 'pixva' ),
			'#00FF00' => __( 'سبز خالص (تست پیکسل سوخته سبز)', 'pixva' ),
			'#0000FF' => __( 'آبی خالص (تست پیکسل سوخته آبی)', 'pixva' ),
			'#FFFFFF' => __( 'سفید کالیبراسیون (لکه‌های نوری و هاله)', 'pixva' ),
			'#000000' => __( 'سیاه عمیق (نشتی نور بک‌لایت)', 'pixva' ),
			'#808080' => __( 'خاکستری میانه (باندینگ و یکنواختی)', 'pixva' ),
		);
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section pixva-section--alt" id="screen-tester">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'تست و احیای پنل', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'تستر پیکسل‌سوختگی RGB و احیاکننده OLED', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'الگوهای رنگی استاندارد را تمام‌صفحه روی همان تلویزیون اجرا کنید تا پیکسل‌های سوخته، گیرکرده و لکه‌های نوری مشخص شوند.', 'pixva' ); ?></p>
				</div>
		<?php endif; ?>

				<div class="pixva-card pixva-rgb-tester" data-rgb-tester data-tester-mode="<?php echo esc_attr( $mode ); ?>">
					<div class="pixva-rgb-controls">
						<?php foreach ( $patterns as $color => $label ) : ?>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $label ); ?></button>
						<?php endforeach; ?>
						<button type="button" class="pixva-btn pixva-btn--primary" data-fullscreen><?php esc_html_e( 'تمام‌صفحه', 'pixva' ); ?></button>
						<?php if ( 'oled' === $mode ) : ?>
							<button type="button" class="pixva-btn pixva-btn--cta" data-oled-cleaner><?php esc_html_e( 'اجرای چرخه احیای OLED', 'pixva' ); ?></button>
						<?php endif; ?>
					</div>
					<div class="pixva-rgb-canvas" data-rgb-canvas style="background:#0E2C38;">
						<span data-rgb-hint><?php esc_html_e( 'برای تست، یکی از الگوهای بالا را انتخاب کنید. در پنل‌های OLED اجرای طولانی الگوی ثابت باعث ماندگاری تصویر می‌شود؛ چرخه احیا را حداکثر ۱۰ دقیقه اجرا کنید.', 'pixva' ); ?></span>
					</div>
				</div>

		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
	/**
	 * ابزار ۱۷ و ۱۹: هاب پیگیری زنده پرونده، استعلام اصالت گارانتی و ثبت سفارش (CRM).
	 *
	 * @param array $args گزینه‌ها: wrap.
	 * @return void
	 */
	function pixva_render_dispatch_and_warranty_hub( $args = array() ) {
		$args  = wp_parse_args( $args, array( 'wrap' => true ) );
		$phone = pixva_support_phone();
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section" id="dispatch-hub">
			<div class="pixva-container">
		<?php endif; ?>
				<div class="pixva-grid pixva-grid--2">
					<div class="pixva-card">
						<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'سامانه رهگیری لحظه‌ای', 'pixva' ); ?></span>
						<h3><?php esc_html_e( 'پیگیری زنده وضعیت تعمیر دستگاه', 'pixva' ); ?></h3>
						<p class="pixva-muted"><?php esc_html_e( 'کد پیگیری و شماره همراه ثبت‌شده هنگام پذیرش را وارد کنید تا تایم‌لاین شش مرحله‌ای پرونده و وضعیت گارانتی نمایش داده شود.', 'pixva' ); ?></p>
						<form action="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" method="get" class="pixva-tool-row">
							<input type="text" name="code" placeholder="<?php esc_attr_e( 'کد پیگیری (PXV-…)', 'pixva' ); ?>" class="pixva-input" aria-label="<?php esc_attr_e( 'کد پیگیری', 'pixva' ); ?>" required>
							<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" class="pixva-input" inputmode="numeric" aria-label="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" required>
							<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'مشاهده تایم‌لاین', 'pixva' ); ?></button>
						</form>
					</div>

					<div class="pixva-card pixva-warranty-check" data-pixva-warranty-check>
						<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'گارانتی دیجیتال', 'pixva' ); ?></span>
						<h3><?php esc_html_e( 'استعلام اصالت گارانتی با سریال', 'pixva' ); ?></h3>
						<p class="pixva-muted"><?php esc_html_e( 'سریال کارت گارانتی (PXV-G-…) را وارد کنید تا اعتبار، تعمیرکار مسئول، قطعات تحت پوشش و تاریخ انقضا از سامانه استعلام شود.', 'pixva' ); ?></p>
						<form class="pixva-tool-row" data-warranty-form novalidate>
							<input type="text" name="serial" class="pixva-input" dir="ltr" placeholder="PXV-G-000000-XXXXXX" aria-label="<?php esc_attr_e( 'سریال گارانتی', 'pixva' ); ?>" data-warranty-serial required>
							<button type="submit" class="pixva-btn pixva-btn--cta" data-warranty-submit><?php esc_html_e( 'استعلام اصالت', 'pixva' ); ?></button>
						</form>
						<div class="pixva-warranty-check__result" data-warranty-result hidden>
							<p class="pixva-warranty-check__state" data-warranty-state></p>
							<dl>
								<div><dt><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></dt><dd data-warranty-tech>—</dd></div>
								<div><dt><?php esc_html_e( 'صدور', 'pixva' ); ?></dt><dd data-warranty-issued>—</dd></div>
								<div><dt><?php esc_html_e( 'انقضا', 'pixva' ); ?></dt><dd data-warranty-expires>—</dd></div>
								<div><dt><?php esc_html_e( 'پوشش', 'pixva' ); ?></dt><dd data-warranty-covers>—</dd></div>
							</dl>
						</div>
						<p class="pixva-notice pixva-notice--error" data-warranty-error hidden></p>
						<div class="pixva-tool-row">
							<a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>" class="pixva-btn pixva-btn--ghost-dark"><?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( pixva_fa_num( $phone ) ); ?></a>
							<a href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" class="pixva-btn pixva-btn--ghost-dark"><?php esc_html_e( 'پیگیری پرونده تعمیر', 'pixva' ); ?></a>
						</div>
					</div>
				</div>

				<?php
				// جریان واقعی ثبت سفارش (جایگزین کادر ایستای پیک جمع‌آوری).
				if ( function_exists( 'pixva_render_order_wizard' ) ) {
					pixva_render_order_wizard(
						array(
							'id'      => 'pixva-wizard-dispatch',
							'wrap'    => false,
							'compact' => false,
							'source'  => 'dispatch-hub',
						)
					);
				}
				?>
		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}
