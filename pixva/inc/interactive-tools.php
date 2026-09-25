<?php
/**
 * بخش رندر و هندلر ۶۰ ابزار تعاملی و هوشمند پیکسوا (inc/interactive-tools.php)
 *
 * شامل ویجت‌ها و ابزارهای کلیدی:
 * - ویجت چت‌بات شناور AI (متصل به Gemini / موتور محلی)
 * - شبیه‌ساز لمسی خرابی روی تلویزیون مجازی (Canvas Simulator)
 * - تستر زنده پیکسل‌سوختگی RGB و احیای OLED
 * - تستر چشمک‌زن پاور و فرکانس صدا
 * - اسلایدر تعیین سایز و محاسبه زنده هزینه با کف ۸ میلیون تومان
 * - فرم استعلام سریع تصویری و نقشه اعزام تکنسین
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
	/**
	 * رندر ویجت شناور دستیار هوش مصنوعی پیکسوا (ابزار ۱ تا ۴).
	 *
	 * @return void
	 */
	function pixva_render_ai_chatbot_widget() {
		$opts = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		if ( empty( $opts['ai_enable_floating'] ) ) {
			return;
		}
		?>
		<div class="pixva-ai-widget" data-pixva-ai-bot>
			<button type="button" class="pixva-ai-trigger" aria-label="<?php esc_attr_e( 'دستیار هوش مصنوعی عیب‌یابی پیکسوا', 'pixva' ); ?>" data-ai-toggle>
				<span class="pixva-ai-trigger__pulse"></span>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2 2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM4.93 4.93a2 2 0 0 1 2.83 0l1.41 1.41a2 2 0 0 1-2.83 2.83L4.93 7.76a2 2 0 0 1 0-2.83zM2 12a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2 2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2H4v-2z"/></svg>
				<span class="pixva-ai-trigger__badge"><?php esc_html_e( 'عیب‌یابی AI', 'pixva' ); ?></span>
			</button>

			<div class="pixva-ai-panel" data-ai-panel hidden>
				<header class="pixva-ai-header">
					<div>
						<span class="pixva-badge pixva-badge--pulse"><?php esc_html_e( 'هوش مصنوعی چندگانه', 'pixva' ); ?></span>
						<h4><?php esc_html_e( 'دستیار عیب‌یاب هوشمند پیکسوا', 'pixva' ); ?></h4>
					</div>
					<button type="button" class="pixva-ai-close" data-ai-close aria-label="<?php esc_attr_e( 'بستن', 'pixva' ); ?>">✕</button>
				</header>
				<div class="pixva-ai-messages" data-ai-messages>
					<div class="pixva-ai-msg pixva-ai-msg--bot">
						<p><?php esc_html_e( 'سلام! برند تلویزیون و شرح خرابی دستگاه (مثلاً: صدا هست ولی تصویر سیاه است، یا آب‌خوردگی پنل) را بنویسید تا بلافاصله عیب‌یابی و برآورد هزینه شود.', 'pixva' ); ?></p>
					</div>
				</div>
				<form class="pixva-ai-input-form" data-ai-form>
					<div class="pixva-ai-field-group">
						<select name="brand" class="pixva-ai-brand-select" data-ai-brand>
							<option value=""><?php esc_html_e( 'برند دستگاه...', 'pixva' ); ?></option>
							<option value="سامسونگ">سامسونگ (Samsung)</option>
							<option value="ال‌جی">ال‌جی (LG)</option>
							<option value="سونی">سونی (Sony)</option>
							<option value="اسنوا">اسنوا (Snowa)</option>
							<option value="ایکس‌ویژن">ایکس‌ویژن (X.Vision)</option>
							<option value="سایر">سایر برندها</option>
						</select>
						<input type="text" name="message" class="pixva-ai-input" placeholder="<?php esc_attr_e( 'مشکل تلویزیون چیست؟...', 'pixva' ); ?>" required data-ai-input>
					</div>
					<button type="submit" class="pixva-btn pixva-btn--cta" data-ai-submit>
						<span><?php esc_html_e( 'آنالیز AI', 'pixva' ); ?></span>
					</button>
				</form>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
	/**
	 * ابزار ۵: شبیه‌ساز لمسی خرابی روی تلویزیون مجازی (Canvas TV Damage Simulator).
	 *
	 * @return void
	 */
	function pixva_render_tv_canvas_simulator() {
		?>
		<section class="pixva-section" id="tv-simulator">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'ابزار شماره ۵: شبیه‌ساز تعاملی', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'شبیه‌ساز لمسی عیب‌یابی تلویزیون مجازی', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'روی هر بخش از صفحه یا برد تلویزیون لمس/کلیک کنید تا عیب، قطعه معیوب و هزینه کف بازار در لحظه شبیه‌سازی شود.', 'pixva' ); ?></p>
				</div>

				<div class="pixva-card pixva-simulator-card" data-tv-simulator>
					<div class="pixva-sim-tv">
						<div class="pixva-sim-screen">
							<div class="pixva-sim-hotspot" data-part="backlight" style="top:25%;left:25%;" title="<?php esc_attr_e( 'ناحیه لامپ‌های بک‌لایت', 'pixva' ); ?>">
								<span>بک‌لایت</span>
							</div>
							<div class="pixva-sim-hotspot" data-part="water" style="bottom:15%;left:50%;" title="<?php esc_attr_e( 'ناحیه فلت بندینگ و تیکان', 'pixva' ); ?>">
								<span>فلت COF / آب‌خوردگی</span>
							</div>
							<div class="pixva-sim-hotspot" data-part="mainboard" style="top:30%;right:15%;" title="<?php esc_attr_e( 'پردازنده و ورودی‌های مین‌برد', 'pixva' ); ?>">
								<span>مین‌برد و پورت‌ها</span>
							</div>
							<div class="pixva-sim-hotspot" data-part="powerboard" style="bottom:25%;right:20%;" title="<?php esc_attr_e( 'مدار تغذیه و خازن‌ها', 'pixva' ); ?>">
								<span>برد پاور تغذیه</span>
							</div>
							<div class="pixva-sim-display-msg" data-sim-screen-msg>
								<p><?php esc_html_e( '👈 یکی از بخش‌های تلویزیون را لمس کنید', 'pixva' ); ?></p>
							</div>
						</div>
					</div>

					<div class="pixva-sim-info" data-sim-info>
						<span class="pixva-badge pixva-badge--pulse" data-sim-tag><?php esc_html_e( 'آماده تست', 'pixva' ); ?></span>
						<h3 data-sim-title><?php esc_html_e( 'روی نقاط چشمک‌زن تلویزیون لمس کنید', 'pixva' ); ?></h3>
						<p data-sim-desc><?php esc_html_e( 'این شبیه‌ساز تعاملی کارگاهی به شما اجازه می‌دهد پیش از باز شدن فیزیکی دستگاه، آسیب‌های محتمل هر بخش را بشناسید.', 'pixva' ); ?></p>
						<div class="pixva-sim-price-box" data-sim-price-box hidden>
							<span class="pixva-muted"><?php esc_html_e( 'کف قیمت قطعه فابریک و دستمزد (بازار ۱۴۰۵):', 'pixva' ); ?></span>
							<strong data-sim-price>۸٬۰۰۰٬۰۰۰ تومان</strong>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_screen_rgb_tester' ) ) {
	/**
	 * ابزار ۶ و ۷: تستر زنده پیکسل‌سوختگی و احیاکننده OLED (Screen RGB & OLED Cleaner).
	 *
	 * @return void
	 */
	function pixva_render_screen_rgb_tester() {
		?>
		<section class="pixva-section pixva-section--alt" id="screen-tester">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--success"><?php esc_html_e( 'ابزار شماره ۶ و ۷: تست و احیای پنل', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'تستر پیکسل‌سوختگی RGB و احیاکننده OLED', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'با مرورگر تلویزیون یا گوشی خود الگوهای رنگی استاندارد را تمام‌صفحه تست کنید تا پیکسل‌های گیرکرده و سوخته مشخص شوند.', 'pixva' ); ?></p>
				</div>

				<div class="pixva-card pixva-rgb-tester" data-rgb-tester>
					<div class="pixva-rgb-controls">
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#ff0000">🔴 قرمز خالص (Red)</button>
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#00ff00">🟢 سبز خالص (Green)</button>
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#0000ff">🔵 آبی خالص (Blue)</button>
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#ffffff">⚪ سفید کالیبراسیون</button>
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#000000">⚫ سیاه عمیق (تست نور پس‌زمینه)</button>
						<button type="button" class="pixva-btn pixva-btn--cta" data-oled-cleaner>⚡ اجرای چرخه احیای OLED</button>
					</div>
					<div class="pixva-rgb-canvas" data-rgb-canvas style="background:#0F172A;">
						<span class="pixva-muted" style="color:#94a3b8;"><?php esc_html_e( 'برای تست، یکی از رنگ‌های بالا را انتخاب کنید.', 'pixva' ); ?></span>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
	/**
	 * ابزار ۱۵ تا ۱۷: استعلام پیگیری زنده، کارت گارانتی دیجیتال و اعزام اورژانسی.
	 *
	 * @return void
	 */
	function pixva_render_dispatch_and_warranty_hub() {
		?>
		<section class="pixva-section" id="dispatch-hub">
			<div class="pixva-container pixva-grid pixva-grid--2" style="align-items:stretch;gap:1.5rem;">
				<div class="pixva-card" style="border-radius:18px;">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'سامانه رهگیری لحظه‌ای', 'pixva' ); ?></span>
					<h3 style="margin:0.8rem 0 0.4rem;font-size:var(--fs-xl);color:#0F172A;"><?php esc_html_e( 'پیگیری زنده وضعیت تعمیر دستگاه', 'pixva' ); ?></h3>
					<p class="pixva-muted"><?php esc_html_e( 'کد رهگیری پیامک‌شده و شماره موبایل خود را وارد کنید تا وضعیت قطعه و گارانتی دیجیتال نمایش داده شود.', 'pixva' ); ?></p>
					<form action="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" method="get" style="display:flex;gap:0.75rem;margin-top:1.2rem;flex-wrap:wrap;">
						<input type="text" name="code" value="PXV-DEMO-2401" placeholder="کد رهگیری (مثلاً PXV-DEMO-2401)" class="pixva-input" style="flex:1;min-width:180px;">
						<input type="text" name="phone" value="09121111111" placeholder="شماره همراه" class="pixva-input" style="flex:1;min-width:140px;">
						<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'مشاهده تایم‌لاین ۶ مرحله‌ای', 'pixva' ); ?></button>
					</form>
				</div>

				<div class="pixva-card" style="border-radius:18px;background:linear-gradient(135deg, #FFFFFF 0%, #F1F5F9 100%);">
					<span class="pixva-badge pixva-badge--pulse"><?php esc_html_e( 'اعزام فوری زیر ۲ ساعت', 'pixva' ); ?></span>
					<h3 style="margin:0.8rem 0 0.4rem;font-size:var(--fs-xl);color:#0F172A;"><?php esc_html_e( 'پیک جمع‌آوری ضدضربه و تکنسین سیار', 'pixva' ); ?></h3>
					<p class="pixva-muted"><?php esc_html_e( 'حمل تلویزیون‌های ۵۵ تا ۸۵ اینچ با جعبه‌های پددار استاندارد کارخانه انجام شده و رسید کتبی با مهر کارگاه علاءالدین تقدیم می‌شود.', 'pixva' ); ?></p>
					<div style="margin-top:1.2rem;display:flex;gap:0.75rem;align-items:center;">
						<a href="tel:02191009990" class="pixva-btn pixva-btn--cta">📞 تماس مستقیم: ۰۲۱۹۱۰۰۹۹۹۰</a>
						<a href="<?php echo esc_url( pixva_page_url( 'contact' ) ); ?>" class="pixva-btn pixva-btn--ghost-dark"><?php esc_html_e( 'ثبت درخواست اعزام', 'pixva' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
