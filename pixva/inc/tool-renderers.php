<?php
/**
 * رندررهای ۶۰ ابزار تخصصی پیکسوا (inc/tool-renderers.php)
 *
 * هر رندرر خروجی واقعی و قابل استفاده تولید می‌کند:
 * - ابزارهای محاسباتی از داده واقعی سرور (نرخ‌نامه، مناطق، انبار) استفاده می‌کنند
 * - ابزارهای پرونده‌ای به اندپوینت‌های AJAX/REST پیکسوا متصل‌اند
 * - ابزارهای هوش مصنوعی به Gemini 1.5 Flash متصل‌اند (بدون داده ساختگی)
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   هاب ۱ — هوش مصنوعی و عیب‌یابی (ابزار ۱ تا ۱۰)
   ========================================================================== */

/**
 * ابزار ۱: دستیار متنی هوش مصنوعی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_ai_text( $id, $tool ) {
	if ( function_exists( 'pixva_render_ai_assistant' ) ) {
		pixva_render_ai_assistant( 'text', $id );
	}
}

/**
 * ابزار ۲: عیب‌یابی با آپلود تصویر (Gemini Vision).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_ai_image( $id, $tool ) {
	if ( function_exists( 'pixva_render_ai_assistant' ) ) {
		pixva_render_ai_assistant( 'image', $id );
	}
}

/**
 * ابزار ۳: آنالیز چندفریمی پرش تصویر.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_ai_frames( $id, $tool ) {
	if ( function_exists( 'pixva_render_ai_assistant' ) ) {
		pixva_render_ai_assistant( 'frames', $id );
	}
}

/**
 * ابزار ۴: دستیار صوتی (Web Speech API + Gemini).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_ai_voice( $id, $tool ) {
	if ( function_exists( 'pixva_render_ai_assistant' ) ) {
		pixva_render_ai_assistant( 'voice', $id );
	}
}

/**
 * ابزار ۵: شبیه‌ساز لمسی تلویزیون مجازی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_tv_simulator( $id, $tool ) {
	if ( function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
		pixva_render_tv_canvas_simulator( array( 'wrap' => false ) );
	}
}

/**
 * ابزار ۶: تستر پیکسل‌سوختگی RGB.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_rgb_tester( $id, $tool ) {
	if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
		pixva_render_screen_rgb_tester( array( 'wrap' => false, 'mode' => 'rgb' ) );
	}
}

/**
 * ابزار ۷: چرخه احیای سوختگی OLED.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_oled_cleaner( $id, $tool ) {
	if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
		pixva_render_screen_rgb_tester( array( 'wrap' => false, 'mode' => 'oled' ) );
	}
}

/**
 * ابزار ۸: تستر الگوی چشمک چراغ پاور + تطبیق با پایگاه کدهای خطا.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_blink_tester( $id, $tool ) {
	$brands = pixva_brand_catalog();
	$codes  = pixva_error_code_catalog();
	$by_blink = array();
	foreach ( $codes as $code ) {
		$blinks = (int) $code['blinks'];
		if ( $blinks < 1 ) {
			continue;
		}
		$by_blink[ $blinks ][] = $code;
	}
	?>
	<div class="pixva-tool-row" data-blink-tester>
		<?php
		pixva_tool_field(
			'blink-brand-' . $id,
			__( 'برند دستگاه', 'pixva' ),
			'select',
			array(
				'name'    => 'brand',
				'options' => array_merge( array( '' => __( 'همه برندها', 'pixva' ) ), wp_list_pluck( $brands, 'fa' ) ),
			)
		);
		pixva_tool_field(
			'blink-count-' . $id,
			__( 'تعداد چشمک متوالی', 'pixva' ),
			'number',
			array(
				'name'      => 'blinks',
				'min'       => 1,
				'max'       => 14,
				'value'     => 3,
				'inputmode' => 'numeric',
			)
		);
		?>
		<div class="pixva-field">
			<label for="blink-speed-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'سرعت چشمک', 'pixva' ); ?></label>
			<select id="blink-speed-<?php echo esc_attr( $id ); ?>" name="speed" class="pixva-input">
				<option value="900"><?php esc_html_e( 'آهسته (حدود ۱ چشمک در ثانیه)', 'pixva' ); ?></option>
				<option value="600" selected><?php esc_html_e( 'متوسط', 'pixva' ); ?></option>
				<option value="380"><?php esc_html_e( 'تند', 'pixva' ); ?></option>
			</select>
		</div>
		<button type="button" class="pixva-btn pixva-btn--primary" data-blink-run><?php esc_html_e( 'پخش الگو و تطبیق کد', 'pixva' ); ?></button>
	</div>

	<div class="pixva-blink-stage" data-blink-stage aria-live="polite">
		<span class="pixva-blink-led" data-blink-led></span>
		<span class="pixva-blink-label" data-blink-label><?php esc_html_e( 'الگوی چشمک چراغ استندبای این‌جا بازسازی می‌شود.', 'pixva' ); ?></span>
	</div>

	<div class="pixva-blink-matches" data-blink-matches hidden>
		<h4><?php esc_html_e( 'کدهای خطای منطبق در پایگاه کارگاه', 'pixva' ); ?></h4>
		<ul>
			<?php foreach ( $by_blink as $blinks => $rows ) : ?>
				<?php foreach ( $rows as $row ) : ?>
					<li data-blink-match="<?php echo esc_attr( $blinks ); ?>" data-match-brand="<?php echo esc_attr( $row['brand'] ); ?>" hidden>
						<strong><?php echo esc_html( ( isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['fa'] : $row['brand'] ) . ' · ' . $row['code'] ); ?></strong>
						<span><?php echo esc_html( $row['title'] ); ?></span>
						<em><?php echo esc_html( $row['symptom'] ); ?></em>
					</li>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</ul>
		<p class="pixva-tool-note"><?php esc_html_e( 'این پایگاه تجربه کارگاهی پیکسوا است، نه سرویس‌منوال رسمی سازنده. برد پاور ولتاژ خطرناک دارد و باز کردن دستگاه بدون تخصص توصیه نمی‌شود.', 'pixva' ); ?></p>
	</div>
	<?php
}

/**
 * ابزار ۹: اسلایدر قبل/بعد صحنه واحد.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_before_after( $id, $tool ) {
	$cases  = get_posts(
		array(
			'post_type'      => 'repair_cases',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$before = PIXVA_URI . '/assets/images/panel-before.jpg';
	$after  = PIXVA_URI . '/assets/images/panel-after.jpg';
	$title  = __( 'ترمیم خطوط پنل آب‌خورده در کارگاه مرکزی', 'pixva' );
	if ( ! empty( $cases ) ) {
		$images = pixva_case_images( $cases[0]->ID );
		$before = $images['before'];
		$after  = $images['after'];
		$title  = get_the_title( $cases[0] );
	}
	pixva_render_before_after( $before, $after, $title );
}

/**
 * ابزار ۱۰: تستر فرکانس بلندگو (WebAudio).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_audio_tester( $id, $tool ) {
	$presets = array(
		'100'  => __( '۱۰۰ هرتز — تست بیس و لرزش بدنه', 'pixva' ),
		'440'  => __( '۴۴۰ هرتز — مرجع کوک و میانه', 'pixva' ),
		'1000' => __( '۱ کیلوهرتز — تشخیص اعوجاج', 'pixva' ),
		'3000' => __( '۳ کیلوهرتز — وضوح دیالوگ', 'pixva' ),
		'8000' => __( '۸ کیلوهرتز — تست توییتر و نویز', 'pixva' ),
	);
	?>
	<div class="pixva-tool-row" data-audio-tester>
		<?php
		pixva_tool_field(
			'audio-freq-' . $id,
			__( 'فرکانس سیگنال', 'pixva' ),
			'select',
			array( 'name' => 'frequency', 'options' => $presets )
		);
		pixva_tool_field(
			'audio-vol-' . $id,
			__( 'دامنه (۰ تا ۱۰۰)', 'pixva' ),
			'number',
			array( 'name' => 'volume', 'min' => 0, 'max' => 100, 'value' => 45, 'inputmode' => 'numeric' )
		);
		?>
		<div class="pixva-field">
			<span class="pixva-muted"><?php esc_html_e( 'سیگنال با WebAudio در مرورگر تولید می‌شود؛ خروجی را به ورودی AUX تلویزیون وصل کنید.', 'pixva' ); ?></span>
		</div>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--primary" data-audio-play><?php esc_html_e( 'پخش سیگنال', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-audio-sweep><?php esc_html_e( 'جاروب ۱۰۰ تا ۸۰۰۰ هرتز', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-audio-stop><?php esc_html_e( 'توقف', 'pixva' ); ?></button>
		</div>
	</div>
	<?php
}

/* ==========================================================================
   هاب ۲ — محاسبه‌گر و نرخ‌نامه (ابزار ۱۱ تا ۲۰)
   ========================================================================== */

/**
 * ابزار ۱۱: تعمیر یا خرید دستگاه نو؟
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_repair_vs_buy( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-repair-vs-buy>
		<?php
		pixva_tool_field(
			'rvb-problem-' . $id,
			__( 'نوع خرابی', 'pixva' ),
			'select',
			array( 'name' => 'problem', 'options' => pixva_problem_catalog() )
		);
		pixva_tool_field(
			'rvb-size-' . $id,
			__( 'سایز دستگاه', 'pixva' ),
			'select',
			array( 'name' => 'size', 'options' => pixva_size_catalog() )
		);
		pixva_tool_field(
			'rvb-brand-' . $id,
			__( 'برند', 'pixva' ),
			'select',
			array( 'name' => 'brand', 'options' => wp_list_pluck( pixva_brand_catalog(), 'fa' ) )
		);
		pixva_tool_field(
			'rvb-age-' . $id,
			__( 'سن دستگاه (سال)', 'pixva' ),
			'number',
			array( 'name' => 'age', 'min' => 0, 'max' => 20, 'value' => 5, 'inputmode' => 'numeric' )
		);
		pixva_tool_field(
			'rvb-newprice-' . $id,
			__( 'قیمت دستگاه نو مشابه (تومان)', 'pixva' ),
			'number',
			array( 'name' => 'new_price', 'min' => 0, 'step' => 100000, 'value' => 45000000, 'inputmode' => 'numeric' )
		);
		?>
		<button type="button" class="pixva-btn pixva-btn--cta" data-rvb-run><?php esc_html_e( 'تحلیل اقتصادی', 'pixva' ); ?></button>
	</div>
	<div class="pixva-breakdown" data-rvb-breakdown hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'برآورد تعمیر از موتور نرخ‌نامه سمت سرور گرفته می‌شود؛ قاعه کارگاه: اگر هزینه تعمیر بیش از ۵۵٪ قیمت دستگاه نو و سن دستگاه بیش از ۸ سال باشد، تعویض منطقی‌تر است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۲: اسلایدر سایز با برآورد زنده.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_size_slider( $id, $tool ) {
	?>
	<div data-size-slider>
		<div class="pixva-tool-row">
			<?php
			pixva_tool_field(
				'ss-problem-' . $id,
				__( 'خدمت', 'pixva' ),
				'select',
				array( 'name' => 'problem', 'options' => pixva_problem_catalog() )
			);
			pixva_tool_field(
				'ss-brand-' . $id,
				__( 'برند', 'pixva' ),
				'select',
				array( 'name' => 'brand', 'options' => wp_list_pluck( pixva_brand_catalog(), 'fa' ) )
			);
			pixva_tool_field(
				'ss-tech-' . $id,
				__( 'تکنولوژی', 'pixva' ),
				'select',
				array( 'name' => 'tech', 'options' => pixva_tech_catalog() )
			);
			?>
		</div>
		<label for="ss-range-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'سایز پنل (اینچ)', 'pixva' ); ?></label>
		<input type="range" class="pixva-range" id="ss-range-<?php echo esc_attr( $id ); ?>" min="32" max="98" step="1" value="55" data-size-range>
		<p class="pixva-range-value"><span data-size-value>۵۵</span> <?php esc_html_e( 'اینچ', 'pixva' ); ?> · <?php esc_html_e( 'ضریب سایز', 'pixva' ); ?>: <span data-size-factor>—</span></p>
		<div class="pixva-bar" aria-hidden="true"><span data-size-bar></span></div>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'ضریب سایز با فرمول نرخ‌نامه محاسبه می‌شود: SizeFactor = 1 + ((Size − 32) / 32)^1.35 و برآورد نهایی از سرور می‌آید.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۳: نمودار تفکیک شفاف هزینه‌ها.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_cost_breakdown( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-cost-breakdown>
		<?php
		pixva_tool_field(
			'cb-problem-' . $id,
			__( 'خدمت', 'pixva' ),
			'select',
			array( 'name' => 'problem', 'options' => pixva_problem_catalog() )
		);
		pixva_tool_field(
			'cb-size-' . $id,
			__( 'سایز', 'pixva' ),
			'select',
			array( 'name' => 'size', 'options' => pixva_size_catalog() )
		);
		pixva_tool_field(
			'cb-brand-' . $id,
			__( 'برند', 'pixva' ),
			'select',
			array( 'name' => 'brand', 'options' => wp_list_pluck( pixva_brand_catalog(), 'fa' ) )
		);
		pixva_tool_field(
			'cb-tech-' . $id,
			__( 'تکنولوژی', 'pixva' ),
			'select',
			array( 'name' => 'tech', 'options' => pixva_tech_catalog() )
		);
		?>
		<button type="button" class="pixva-btn pixva-btn--primary" data-breakdown-run><?php esc_html_e( 'تفکیک هزینه', 'pixva' ); ?></button>
	</div>
	<div class="pixva-breakdown" data-breakdown-rows hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'تفکیک شامل قطعه فابریک، اجرت تخصصی کارگاه، هزینه کارشناسی/عیب‌یابی و گارانتی ۱۸۰ روزه است و همه مقادیر سمت سرور محاسبه می‌شوند.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۴: استعلام زنده انبار قطعات.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_stock_checker( $id, $tool ) {
	$brands = pixva_brand_catalog();
	?>
	<div class="pixva-tool-row" data-stock-checker>
		<?php
		pixva_tool_field(
			'stock-q-' . $id,
			__( 'جست‌وجوی قطعه یا کد فنی', 'pixva' ),
			'search',
			array( 'name' => 'q', 'placeholder' => __( 'مثلاً بک‌لایت ۵۵ یا BN44', 'pixva' ) )
		);
		pixva_tool_field(
			'stock-brand-' . $id,
			__( 'برند', 'pixva' ),
			'select',
			array(
				'name'    => 'brand',
				'options' => array_merge( array( '' => __( 'همه برندها', 'pixva' ) ), wp_list_pluck( $brands, 'fa' ) ),
			)
		);
		?>
		<button type="button" class="pixva-btn pixva-btn--primary" data-stock-run><?php esc_html_e( 'استعلام موجودی', 'pixva' ); ?></button>
	</div>
	<div class="pixva-stock-list" data-stock-list hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'منبع داده: انبار مرکزی پاساژ علاءالدین و رکوردهای «انبار قطعات» در پیشخوان. قیمت‌ها بر اساس نرخ‌نامه ۱۴۰۵ است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۵: تایم‌لاین زنده پرونده تعمیر.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_timeline( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-timeline-form novalidate>
		<?php
		pixva_tool_field(
			'tl-code-' . $id,
			__( 'کد پیگیری', 'pixva' ),
			'text',
			array( 'name' => 'code', 'placeholder' => 'PXV-XXXXXX', 'required' => true )
		);
		pixva_tool_field(
			'tl-phone-' . $id,
			__( 'شماره همراه ثبت‌شده', 'pixva' ),
			'tel',
			array( 'name' => 'phone', 'placeholder' => '0912xxxxxxx', 'inputmode' => 'numeric', 'required' => true )
		);
		?>
		<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'نمایش تایم‌لاین', 'pixva' ); ?></button>
	</form>
	<ol class="pixva-timeline" data-timeline hidden></ol>
	<p class="pixva-tool-note"><?php esc_html_e( 'برای حفظ حریم خصوصی، استعلام فقط با تطابق هم‌زمان کد پیگیری و شماره همراه امکان‌پذیر است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۶: کارت گارانتی دیجیتال با هش SHA-256.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_warranty_card( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-warranty-form novalidate>
		<?php
		pixva_tool_field(
			'wc-code-' . $id,
			__( 'کد پیگیری پرونده', 'pixva' ),
			'text',
			array( 'name' => 'code', 'placeholder' => 'PXV-XXXXXX', 'required' => true )
		);
		pixva_tool_field(
			'wc-phone-' . $id,
			__( 'شماره همراه', 'pixva' ),
			'tel',
			array( 'name' => 'phone', 'placeholder' => '0912xxxxxxx', 'inputmode' => 'numeric', 'required' => true )
		);
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'صدور کارت گارانتی', 'pixva' ); ?></button>
	</form>
	<div class="pixva-warranty-card" data-warranty-card hidden>
		<div class="pixva-warranty-card__head">
			<strong><?php esc_html_e( 'کارت گارانتی کتبی ۱۸۰ روزه پیکسوا', 'pixva' ); ?></strong>
			<span data-warranty-code></span>
		</div>
		<ul>
			<li><span><?php esc_html_e( 'دستگاه', 'pixva' ); ?></span><b data-warranty-device></b></li>
			<li><span><?php esc_html_e( 'خدمت انجام‌شده', 'pixva' ); ?></span><b data-warranty-service></b></li>
			<li><span><?php esc_html_e( 'تاریخ تحویل', 'pixva' ); ?></span><b data-warranty-delivered></b></li>
			<li><span><?php esc_html_e( 'پایان گارانتی', 'pixva' ); ?></span><b data-warranty-expires></b></li>
			<li><span><?php esc_html_e( 'هش اعتبارسنجی SHA-256', 'pixva' ); ?></span><code data-warranty-hash></code></li>
		</ul>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-warranty-print><?php esc_html_e( 'چاپ کارت', 'pixva' ); ?></button>
			<span class="pixva-tool-note"><?php esc_html_e( 'هش از داده‌های واقعی پرونده در سرور محاسبه می‌شود و برای استعلام اصالت کارت کاربرد دارد.', 'pixva' ); ?></span>
		</div>
	</div>
	<?php
}

/**
 * ابزار ۱۷: اعزام اورژانسی زیر ۲ ساعت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_dispatch( $id, $tool ) {
	$zones = pixva_zone_catalog();
	?>
	<form class="pixva-tool-row" data-dispatch-form novalidate>
		<?php
		pixva_tool_field(
			'ds-name-' . $id,
			__( 'نام و نام خانوادگی', 'pixva' ),
			'text',
			array( 'name' => 'customer_name', 'required' => true )
		);
		pixva_tool_field(
			'ds-phone-' . $id,
			__( 'شماره همراه', 'pixva' ),
			'tel',
			array( 'name' => 'phone', 'inputmode' => 'numeric', 'placeholder' => '0912xxxxxxx', 'required' => true )
		);
		pixva_tool_field(
			'ds-zone-' . $id,
			__( 'منطقه', 'pixva' ),
			'select',
			array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) )
		);
		pixva_tool_field(
			'ds-kind-' . $id,
			__( 'نوع اعزام', 'pixva' ),
			'select',
			array(
				'name'    => 'kind',
				'options' => array(
					'pickup'   => __( 'پیک جمع‌آوری دستگاه', 'pixva' ),
					'onsite'   => __( 'تکنسین در محل', 'pixva' ),
					'emergency' => __( 'اعزام اورژانسی (زیر ۲ ساعت)', 'pixva' ),
				),
			)
		);
		?>
		<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'ثبت درخواست اعزام', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'پس از ثبت، کد پیگیری صادر و به واحد اعزام کارگاه اطلاع داده می‌شود. حمل تلویزیون‌های بزرگ با جعبه پددار انجام می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۱۸: تحلیل‌گر نویز صوتی دستگاه (میکروفون + AnalyserNode).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_audio_analyzer( $id, $tool ) {
	?>
	<div data-audio-analyzer>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--primary" data-analyzer-start><?php esc_html_e( 'شروع ضبط و تحلیل نویز', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-analyzer-stop><?php esc_html_e( 'توقف', 'pixva' ); ?></button>
			<span class="pixva-tool-note"><?php esc_html_e( 'میکروفون گوشی را نزدیک بدنه تلویزیون بگیرید؛ هیچ صوتی ذخیره یا ارسال نمی‌شود و تحلیل فقط در مرورگر انجام می‌شود.', 'pixva' ); ?></span>
		</div>
		<canvas class="pixva-analyzer-canvas" data-analyzer-canvas width="640" height="140" aria-label="<?php esc_attr_e( 'نمایش طیف فرکانسی نویز', 'pixva' ); ?>"></canvas>
		<ul class="pixva-analyzer-readout">
			<li><span><?php esc_html_e( 'فرکانس غالب', 'pixva' ); ?></span><b data-analyzer-peak>—</b></li>
			<li><span><?php esc_html_e( 'سطح نویز', 'pixva' ); ?></span><b data-analyzer-level>—</b></li>
			<li><span><?php esc_html_e( 'تفسیر کارگاهی', 'pixva' ); ?></span><b data-analyzer-note>—</b></li>
		</ul>
	</div>
	<?php
}

/**
 * ابزار ۱۹: رهگیری مراحل اعزام تکنسین.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_dispatch_tracker( $id, $tool ) {
	$stages = pixva_dispatch_stages();
	?>
	<div class="pixva-journey pixva-journey--mini" data-pixva-journey>
		<span class="pixva-journey__track" aria-hidden="true"><span class="pixva-journey__spark"></span></span>
		<ol class="pixva-journey__steps">
			<?php foreach ( $stages as $index => $stage ) : ?>
				<li class="pixva-journey__step">
					<span class="pixva-journey__dot"><?php echo pixva_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<span class="pixva-journey__no"><?php echo esc_html( pixva_fa_num( (string) ( $index + 1 ) ) ); ?></span>
						<strong><?php echo esc_html( $stage['title'] ); ?></strong>
						<p><?php echo esc_html( $stage['time'] ); ?></p>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
	<?php
}

/**
 * ابزار ۲۰: فرم استعلام سریع تصویری.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_quick_quote( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-quickquote-form enctype="multipart/form-data" novalidate>
		<?php
		pixva_tool_field(
			'qq-name-' . $id,
			__( 'نام', 'pixva' ),
			'text',
			array( 'name' => 'customer_name', 'required' => true )
		);
		pixva_tool_field(
			'qq-phone-' . $id,
			__( 'شماره همراه', 'pixva' ),
			'tel',
			array( 'name' => 'phone', 'inputmode' => 'numeric', 'placeholder' => '0912xxxxxxx', 'required' => true )
		);
		pixva_tool_field(
			'qq-brand-' . $id,
			__( 'برند', 'pixva' ),
			'select',
			array( 'name' => 'brand', 'options' => wp_list_pluck( pixva_brand_catalog(), 'fa' ) )
		);
		pixva_tool_field(
			'qq-size-' . $id,
			__( 'سایز', 'pixva' ),
			'select',
			array( 'name' => 'size', 'options' => pixva_size_catalog() )
		);
		pixva_tool_field(
			'qq-problem-' . $id,
			__( 'علائم خرابی', 'pixva' ),
			'select',
			array( 'name' => 'problem', 'options' => pixva_problem_catalog() )
		);
		pixva_tool_field(
			'qq-photo-' . $id,
			__( 'عکس صفحه یا برچسب دستگاه', 'pixva' ),
			'file',
			array( 'name' => 'photo', 'accept' => 'image/jpeg,image/png,image/webp' )
		);
		?>
		<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'ارسال استعلام سریع', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'پرونده استعلام با کد پیگیری ثبت می‌شود و در صورت فعال بودن کلید Gemini، تحلیل هوشمند تصویر هم ضمیمه می‌گردد.', 'pixva' ); ?></p>
	<?php
}

/* ==========================================================================
   هاب ۳ — پیگیری و گارانتی (ابزار ۲۱ تا ۳۰)
   ========================================================================== */

/**
 * ابزار ۲۱: پورتال خدمات سازمانی و هتل‌ها (B2B).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_b2b_form( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-request-form="b2b" novalidate>
		<?php
		pixva_tool_field( 'b2b-org-' . $id, __( 'نام سازمان / هتل', 'pixva' ), 'text', array( 'name' => 'organization', 'required' => true ) );
		pixva_tool_field( 'b2b-person-' . $id, __( 'نام رابط', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'b2b-phone-' . $id, __( 'تلفن تماس', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field(
			'b2b-devices-' . $id,
			__( 'تعداد دستگاه تحت پوشش', 'pixva' ),
			'number',
			array( 'name' => 'devices', 'min' => 1, 'max' => 5000, 'value' => 20, 'inputmode' => 'numeric' )
		);
		pixva_tool_field(
			'b2b-kind-' . $id,
			__( 'نوع قرارداد', 'pixva' ),
			'select',
			array(
				'name'    => 'kind',
				'options' => array(
					'maintenance' => __( 'نگهداری دوره‌ای سالانه', 'pixva' ),
					'per-call'    => __( 'خدمات موردی با فاکتور رسمی', 'pixva' ),
					'parts'       => __( 'تأمین قطعات فابریک', 'pixva' ),
					'emergency'   => __( 'پوشش اورژانسی ۲۴ ساعته', 'pixva' ),
				),
			)
		);
		pixva_tool_field( 'b2b-note-' . $id, __( 'توضیحات', 'pixva' ), 'textarea', array( 'name' => 'message' ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ثبت درخواست قرارداد', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'قراردادهای سازمانی با فاکتور رسمی، گواهی ارزش افزوده و اولویت اعزام ۲ ساعته تنظیم می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۲: استعلام اصالت قطعه با شماره سریال.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_serial_verify( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-serial-form novalidate>
		<?php pixva_tool_field( 'sv-serial-' . $id, __( 'شماره سریال قطعه (روی برچسب هولوگرام)', 'pixva' ), 'text', array( 'name' => 'serial', 'placeholder' => 'PXV-BL-000123', 'required' => true ) ); ?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'بررسی اصالت', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'سریال‌ها در زمان نصب قطعه فابریک در پرونده تعمیر ثبت می‌شوند؛ استعلام از پایگاه REST پیکسوا انجام می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۳: محاسبه‌گر زمان رسیدن تکنسین (ETA).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_eta_finder( $id, $tool ) {
	$zones = pixva_zone_catalog();
	?>
	<div class="pixva-tool-row" data-eta-finder data-zones="<?php echo esc_attr( wp_json_encode( $zones ) ); ?>">
		<?php
		pixva_tool_field( 'eta-zone-' . $id, __( 'منطقه', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) );
		pixva_tool_field(
			'eta-kind-' . $id,
			__( 'نوع خدمت', 'pixva' ),
			'select',
			array(
				'name'    => 'kind',
				'options' => array(
					'standard'  => __( 'اعزام استاندارد', 'pixva' ),
					'express'   => __( 'اعزام فوری (ضریب ۰٫۶ زمان)', 'pixva' ),
					'pickup'    => __( 'فقط جمع‌آوری دستگاه', 'pixva' ),
				),
			)
		);
		pixva_tool_field(
			'eta-size-' . $id,
			__( 'سایز دستگاه', 'pixva' ),
			'select',
			array( 'name' => 'size', 'options' => pixva_size_catalog() )
		);
		?>
	</div>
	<div class="pixva-breakdown" data-eta-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'بازه زمانی بر اساس فاصله شعبه تا منطقه، نوع خدمت و زمان آماده‌سازی جعبه حمل محاسبه می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۴: پایگاه زنده کدهای خطا.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_error_db( $id, $tool ) {
	if ( function_exists( 'pixva_render_error_database' ) ) {
		pixva_render_error_database();
	}
}

/**
 * ابزار ۲۵: جست‌وجوی صوتی فارسی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_voice_search( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-voice-search>
		<?php pixva_tool_field( 'vs-q-' . $id, __( 'متن جست‌وجو', 'pixva' ), 'search', array( 'name' => 'q', 'placeholder' => __( 'مثلاً: چراغ پاور ۶ بار چشمک می‌زند', 'pixva' ) ) ); ?>
		<button type="button" class="pixva-btn pixva-btn--primary" data-voice-start><?php esc_html_e( 'گفتار به متن (فارسی)', 'pixva' ); ?></button>
		<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-voice-go><?php esc_html_e( 'جست‌وجو در سایت', 'pixva' ); ?></a>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'این ابزار از Web Speech API مرورگر استفاده می‌کند. در صورت پشتیبانی نکردن مرورگر، پیام راهنما نمایش داده می‌شود و تایپ دستی ممکن است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۶: رزرو نوبت و پیش‌فاکتور دیجیتال.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_booking( $id, $tool ) {
	$slots = array(
		'09-12' => __( '۰۹:۰۰ تا ۱۲:۰۰', 'pixva' ),
		'12-15' => __( '۱۲:۰۰ تا ۱۵:۰۰', 'pixva' ),
		'15-18' => __( '۱۵:۰۰ تا ۱۸:۰۰', 'pixva' ),
		'18-20' => __( '۱۸:۰۰ تا ۲۰:۰۰', 'pixva' ),
	);
	?>
	<form class="pixva-tool-row" data-booking-form novalidate>
		<?php
		pixva_tool_field( 'bk-name-' . $id, __( 'نام', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'bk-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field( 'bk-date-' . $id, __( 'تاریخ مراجعه', 'pixva' ), 'date', array( 'name' => 'booking_date', 'value' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ), 'required' => true ) );
		pixva_tool_field( 'bk-slot-' . $id, __( 'بازه زمانی', 'pixva' ), 'select', array( 'name' => 'slot', 'options' => $slots ) );
		pixva_tool_field( 'bk-problem-' . $id, __( 'نوع خرابی', 'pixva' ), 'select', array( 'name' => 'problem', 'options' => pixva_problem_catalog() ) );
		pixva_tool_field( 'bk-size-' . $id, __( 'سایز', 'pixva' ), 'select', array( 'name' => 'size', 'options' => pixva_size_catalog() ) );
		pixva_tool_field( 'bk-brand-' . $id, __( 'برند', 'pixva' ), 'select', array( 'name' => 'brand', 'options' => wp_list_pluck( pixva_brand_catalog(), 'fa' ) ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'رزرو نوبت و دریافت پیش‌فاکتور', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'بازه زمانی بر اساس ساعت کاری کارگاه (شنبه تا پنجشنبه ۰۹ تا ۲۰) تنظیم می‌شود و کد پیگیری بلافاصله صادر می‌گردد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۷: داشبورد پرونده‌های مشتری.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_client_hub( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-clienthub-form novalidate>
		<?php pixva_tool_field( 'ch-phone-' . $id, __( 'شماره همراه ثبت‌شده', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'placeholder' => '0912xxxxxxx', 'required' => true ) ); ?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'نمایش پرونده‌ها', 'pixva' ); ?></button>
	</form>
	<div class="pixva-clienthub" data-clienthub hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'فهرست پرونده‌ها فقط با شماره همراهی نمایش داده می‌شود که هنگام پذیرش ثبت شده است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۸: انتخاب‌گر شعبه و واحد سیار.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_branches( $id, $tool ) {
	$branches = pixva_branches();
	$zones    = pixva_zone_catalog();
	?>
	<div class="pixva-tool-row" data-branch-switcher>
		<?php pixva_tool_field( 'br-zone-' . $id, __( 'منطقه من', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) ); ?>
	</div>
	<div class="pixva-tool-grid pixva-grid pixva-grid--2" data-branch-list>
		<?php foreach ( $branches as $index => $branch ) : ?>
			<article class="pixva-card pixva-branch-card" data-branch data-zones="<?php echo esc_attr( implode( ',', (array) $branch['zones'] ) ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
				<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( $branch['type'] ); ?></span>
				<h3><?php echo esc_html( $branch['name'] ); ?></h3>
				<p class="pixva-muted"><?php echo esc_html( $branch['address'] ); ?></p>
				<p class="pixva-muted"><?php echo esc_html( $branch['hours'] ); ?></p>
				<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_tel_href( $branch['phone'] ) ); ?>"><?php echo esc_html( pixva_fa_num( $branch['phone'] ) ); ?></a>
			</article>
		<?php endforeach; ?>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'با انتخاب منطقه، شعبه‌های پوشش‌دهنده برجسته می‌شوند. منبع داده: پست‌تایپ «شعب و تکنسین‌ها» در پیشخوان.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۲۹: مقایسه فناوری‌های پنل.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_panel_compare( $id, $tool ) {
	$matrix = pixva_panel_tech_matrix();
	?>
	<div class="pixva-table-wrap">
		<table class="pixva-rates-table" data-panel-compare>
			<thead>
				<tr>
					<th><?php esc_html_e( 'فناوری پنل', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'کنتراست', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'عمر مفید', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'ریسک رایج', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'وضعیت تعمیر', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'ضریب نرخ‌نامه', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $matrix as $row ) : ?>
					<tr>
						<td data-label="<?php esc_attr_e( 'فناوری', 'pixva' ); ?>"><strong><?php echo esc_html( $row['tech'] ); ?></strong></td>
						<td data-label="<?php esc_attr_e( 'کنتراست', 'pixva' ); ?>"><?php echo esc_html( $row['contrast'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'عمر مفید', 'pixva' ); ?>"><?php echo esc_html( $row['life'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'ریسک', 'pixva' ); ?>"><?php echo esc_html( $row['risk'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'تعمیر', 'pixva' ); ?>"><?php echo esc_html( $row['repair'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'ضریب', 'pixva' ); ?>"><?php echo esc_html( pixva_fa_num( $row['multiplier'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * ابزار ۳۰: بررسی مهر اصالت قطعه فابریک.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_genuine_seal( $id, $tool ) {
	$checks = array(
		__( 'هولوگرام سه‌بعدی با لوگوی کارگاه که در زاویه ۴۵ درجه تغییر رنگ می‌دهد.', 'pixva' ),
		__( 'شماره سریال حک‌شده لیزری که با برچسب کاغذی تفاوت دارد.', 'pixva' ),
		__( 'کد QR اختصاصی که به پرونده تعمیر همان دستگاه متصل است.', 'pixva' ),
		__( 'بسته‌بندی ضد رطوبت با نوار پلمپ یک‌بار مصرف.', 'pixva' ),
		__( 'برگه گارانتی ۱۸۰ روزه کتبی با مهر و امضای کارگاه علاءالدین.', 'pixva' ),
	);
	?>
	<ul class="pixva-checklist" data-seal-checklist>
		<?php foreach ( $checks as $index => $check ) : ?>
			<li>
				<label>
					<input type="checkbox" data-seal-item value="<?php echo esc_attr( $index ); ?>">
					<span><?php echo esc_html( $check ); ?></span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="pixva-bar"><span data-seal-progress></span></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'اگر حداقل چهار مورد از پنج نشانه اصالت را تأیید می‌کنید، قطعه نصب‌شده فابریک است؛ در غیر این صورت سریال را در ابزار ۲۲ استعلام کنید.', 'pixva' ); ?></p>
	<?php
}

/* ==========================================================================
   هاب ۴ — کدهای خطا و آموزش (ابزار ۳۱ تا ۴۵)
   ========================================================================== */

/**
 * ابزار ۳۱: محاسبه هزینه ایاب‌وذهاب بر اساس مناطق شهرداری.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_transport_fee( $id, $tool ) {
	$zones = pixva_zone_catalog();
	?>
	<div class="pixva-tool-row" data-transport-fee data-zones="<?php echo esc_attr( wp_json_encode( $zones ) ); ?>">
		<?php
		pixva_tool_field( 'tf-zone-' . $id, __( 'منطقه', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) );
		pixva_tool_field( 'tf-size-' . $id, __( 'سایز دستگاه', 'pixva' ), 'select', array( 'name' => 'size', 'options' => pixva_size_catalog() ) );
		pixva_tool_field(
			'tf-floor-' . $id,
			__( 'طبقه (بدون آسانسور)', 'pixva' ),
			'number',
			array( 'name' => 'floor', 'min' => 0, 'max' => 20, 'value' => 0, 'inputmode' => 'numeric' )
		);
		pixva_tool_field(
			'tf-stairs-' . $id,
			__( 'وضعیت آسانسور', 'pixva' ),
			'select',
			array(
				'name'    => 'elevator',
				'options' => array(
					'yes' => __( 'آسانسور دارد', 'pixva' ),
					'no'  => __( 'آسانسور ندارد / دستگاه در آسانسور جا نمی‌شود', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'فرمول کارگاه: هزینه منطقه × ضریب سایز + (طبقه × ۱۲۰٬۰۰۰ تومان در صورت نبود آسانسور). حمل تلویزیون ۷۵ اینچ به بالا دو نفره است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۲: راهنمای گام‌به‌گام بسته‌بندی و ایمن‌سازی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_packing_guide( $id, $tool ) {
	$steps = pixva_packing_steps();
	?>
	<ol class="pixva-checklist pixva-checklist--steps" data-packing-guide>
		<?php foreach ( $steps as $index => $step ) : ?>
			<li>
				<label>
					<input type="checkbox" data-pack-item value="<?php echo esc_attr( $index ); ?>">
					<span>
						<strong><?php echo esc_html( pixva_fa_num( (string) ( $index + 1 ) ) . '. ' . $step['title'] ); ?></strong>
						<em><?php echo esc_html( $step['text'] ); ?></em>
					</span>
				</label>
			</li>
		<?php endforeach; ?>
	</ol>
	<div class="pixva-bar"><span data-pack-progress></span></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'پیش از حمل، همه مرحله‌ها را تیک بزنید؛ بیشترین خسارت پنل در حمل غیراصولی و خواباندن طولانی دستگاه رخ می‌دهد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۳: ثبت تجربه و نظر مشتریان.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_review_form( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-request-form="review" novalidate>
		<?php
		pixva_tool_field( 'rv-name-' . $id, __( 'نام شما', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'rv-phone-' . $id, __( 'شماره همراه (برای تطبیق پرونده)', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field( 'rv-code-' . $id, __( 'کد پیگیری پرونده', 'pixva' ), 'text', array( 'name' => 'code', 'placeholder' => 'PXV-XXXXXX' ) );
		pixva_tool_field(
			'rv-rating-' . $id,
			__( 'امتیاز', 'pixva' ),
			'select',
			array(
				'name'    => 'rating',
				'options' => array(
					'5' => __( '۵ — کاملاً راضی', 'pixva' ),
					'4' => __( '۴ — راضی', 'pixva' ),
					'3' => __( '۳ — متوسط', 'pixva' ),
					'2' => __( '۲ — ناراضی', 'pixva' ),
					'1' => __( '۱ — بسیار ناراضی', 'pixva' ),
				),
			)
		);
		pixva_tool_field( 'rv-message-' . $id, __( 'شرح تجربه', 'pixva' ), 'textarea', array( 'name' => 'message', 'required' => true ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ثبت بازخورد', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'بازخوردها پس از تطبیق با پرونده واقعی و تأیید کارگاه در بخش نظرات منتشر می‌شوند. هیچ نظر ساختگی منتشر نمی‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۴: نوار شناور شیشه‌ای موبایل.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_mobile_dock( $id, $tool ) {
	$phone = pixva_support_phone();
	?>
	<div class="pixva-dock-preview" data-dock-preview>
		<div class="pixva-dock-preview__frame">
			<iframe data-dock-frame title="<?php esc_attr_e( 'پیش‌نمایش زنده صفحه مقصد نوار موبایل', 'pixva' ); ?>" src="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>" loading="lazy"></iframe>
		</div>
		<div class="pixva-mobile-dock pixva-mobile-dock--static">
			<a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>" class="pixva-mobile-dock__item" data-dock-link="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>"><?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'تماس', 'pixva' ); ?></span></a>
			<a href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>" class="pixva-mobile-dock__item is-cta is-active" data-dock-link="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'محاسبه قیمت', 'pixva' ); ?></span></a>
			<a href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" class="pixva-mobile-dock__item" data-dock-link="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php echo pixva_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'پیگیری', 'pixva' ); ?></span></a>
			<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ); ?>" class="pixva-mobile-dock__item" target="_blank" rel="noopener" data-dock-link="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ); ?>"><?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'واتساپ', 'pixva' ); ?></span></a>
		</div>
	</div>
	<p class="pixva-tool-note" data-dock-hint><?php esc_html_e( 'این نوار در همه صفحه‌ها روی موبایل به‌صورت ثابت نمایش داده می‌شود. روی هر آیتم بزنید تا صفحه مقصدش در همین قاب پیش‌نمایش باز شود؛ آیتم واتساپ در زبانه جدید باز می‌گردد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۵: یادآور سرویس دوره‌ای بک‌لایت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_reminder( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-reminder-calc>
		<?php
		pixva_tool_field( 'rm-date-' . $id, __( 'تاریخ تعمیر/خرید', 'pixva' ), 'date', array( 'name' => 'service_date', 'value' => gmdate( 'Y-m-d' ) ) );
		pixva_tool_field(
			'rm-hours-' . $id,
			__( 'میانگین ساعت تماشا در روز', 'pixva' ),
			'number',
			array( 'name' => 'hours', 'min' => 1, 'max' => 18, 'value' => 6, 'inputmode' => 'numeric' )
		);
		pixva_tool_field(
			'rm-brightness-' . $id,
			__( 'سطح روشنایی', 'pixva' ),
			'select',
			array(
				'name'    => 'brightness',
				'options' => array(
					'low'  => __( 'کم (زیر ۴۰٪)', 'pixva' ),
					'mid'  => __( 'متوسط (۴۰ تا ۷۰٪)', 'pixva' ),
					'high' => __( 'زیاد (بالای ۷۰٪)', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<form class="pixva-tool-row" data-request-form="reminder" novalidate>
		<?php
		pixva_tool_field( 'rm-phone-' . $id, __( 'شماره همراه برای یادآوری پیامکی', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field( 'rm-code-' . $id, __( 'کد پیگیری (اختیاری)', 'pixva' ), 'text', array( 'name' => 'code' ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--ghost-dark"><?php esc_html_e( 'ثبت در سامانه یادآوری', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'مبنای محاسبه: عمر مفید بک‌لایت LED حدود ۴۰٬۰۰۰ ساعت در روشنایی متوسط است؛ روشنایی بالا ضریب استهلاک را ۱٫۴ برابر می‌کند.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۶: شبیه‌ساز لایه‌های پنل و مسیر نفوذ رطوبت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_layers3d( $id, $tool ) {
	$layers = array(
		array( 'name' => __( 'گلس محافظ بیرونی', 'pixva' ), 'detail' => __( 'اولین لایه‌ای که در آب‌خوردگی با مایع تماس دارد و معمولاً سالم می‌ماند.', 'pixva' ) ),
		array( 'name' => __( 'پولارایزر بالا', 'pixva' ), 'detail' => __( 'نفوذ رطوبت باعث مات‌شدگی و لکه‌های زرد می‌شود.', 'pixva' ) ),
		array( 'name' => __( 'لایه کریستال مایع / پیکسل', 'pixva' ), 'detail' => __( 'حساس‌ترین لایه؛ اتصال الکترودها در برابر رطوبت اکسید می‌شود.', 'pixva' ) ),
		array( 'name' => __( 'فلت‌های COF (بندینگ)', 'pixva' ), 'detail' => __( 'محل اصلی خرابی آب‌خوردگی؛ ترمیم با دستگاه بندینگ صنعتی انجام می‌شود.', 'pixva' ) ),
		array( 'name' => __( 'برد T-Con', 'pixva' ), 'detail' => __( 'سیگنال‌دهی ستون‌ها و ردیف‌ها؛ خرابی آن تصویر منفی یا نیمه سیاه می‌دهد.', 'pixva' ) ),
		array( 'name' => __( 'دیفیوزر و صفحات نوری', 'pixva' ), 'detail' => __( 'در آب‌خوردگی شدید، لکه دائمی روی صفحات نوری می‌ماند.', 'pixva' ) ),
		array( 'name' => __( 'دست بک‌لایت LED', 'pixva' ), 'detail' => __( 'زیر صفحات نوری؛ در تماس با مایع، اتصال کوتاه و خاموشی کامل رخ می‌دهد.', 'pixva' ) ),
	);
	?>
	<div class="pixva-layers" data-layers3d>
		<div class="pixva-layers__stack" aria-hidden="true">
			<?php foreach ( $layers as $index => $layer ) : ?>
				<span class="pixva-layer" data-layer-index="<?php echo esc_attr( $index ); ?>" style="--layer-index:<?php echo esc_attr( $index ); ?>"></span>
			<?php endforeach; ?>
		</div>
		<ul class="pixva-layers__list">
			<?php foreach ( $layers as $index => $layer ) : ?>
				<li>
					<button type="button" class="pixva-layer-btn" data-layer-target="<?php echo esc_attr( $index ); ?>" aria-expanded="false">
						<strong><?php echo esc_html( $layer['name'] ); ?></strong>
						<span><?php echo esc_html( $layer['detail'] ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'روی هر لایه کلیک کنید تا در نمای انفجاری برجسته شود؛ مسیر نفوذ رطوبت از لبه پایین پنل به سمت فلت‌های COF است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۷: محاسبه‌گر مصرف برق و انرژی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_power_saver( $id, $tool ) {
	$tariffs = pixva_electricity_tariffs();
	?>
	<div class="pixva-tool-row" data-power-saver data-tariffs="<?php echo esc_attr( wp_json_encode( $tariffs ) ); ?>">
		<?php
		pixva_tool_field( 'ps-watt-' . $id, __( 'توان دستگاه (وات)', 'pixva' ), 'number', array( 'name' => 'watt', 'min' => 20, 'max' => 900, 'value' => 120, 'inputmode' => 'numeric' ) );
		pixva_tool_field( 'ps-hours-' . $id, __( 'ساعت تماشا در روز', 'pixva' ), 'number', array( 'name' => 'hours', 'min' => 1, 'max' => 24, 'value' => 6, 'step' => '0.5' ) );
		pixva_tool_field( 'ps-tariff-' . $id, __( 'تعرفه برق', 'pixva' ), 'select', array( 'name' => 'tariff', 'options' => wp_list_pluck( $tariffs, 'label' ) ) );
		pixva_tool_field(
			'ps-eco-' . $id,
			__( 'حالت صرفه‌جویی', 'pixva' ),
			'select',
			array(
				'name'    => 'eco',
				'options' => array(
					'off' => __( 'خاموش', 'pixva' ),
					'on'  => __( 'روشن (کاهش ۲۲٪ مصرف)', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<div class="pixva-breakdown" data-power-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'مبنای محاسبه: توان × ساعت × ۳۰ روز ÷ ۱۰۰۰ = کیلووات‌ساعت ماهانه، ضرب‌در تعرفه انتخابی (ریال).', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۳۸: راهنمای آپدیت سیستم‌عامل.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_os_guide( $id, $tool ) {
	$guides = pixva_os_update_guides();
	$index  = 0;
	?>
	<div class="pixva-tabs" data-os-guide>
		<div class="pixva-tabs__nav" role="tablist">
			<?php foreach ( $guides as $key => $guide ) : ?>
				<button type="button" role="tab" class="pixva-tab<?php echo 0 === $index ? ' is-active' : ''; ?>" data-os-tab="<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="os-panel-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $guide['label'] ); ?></button>
				<?php ++$index; ?>
			<?php endforeach; ?>
		</div>
		<?php $index = 0; ?>
		<?php foreach ( $guides as $key => $guide ) : ?>
			<div class="pixva-tabpanel<?php echo 0 === $index ? ' is-active' : ''; ?>" id="os-panel-<?php echo esc_attr( $key ); ?>" data-os-panel="<?php echo esc_attr( $key ); ?>" role="tabpanel" <?php echo 0 === $index ? '' : 'hidden'; ?>>
				<ol class="pixva-steps">
					<?php foreach ( $guide['steps'] as $step ) : ?>
						<li><?php echo esc_html( $step ); ?></li>
					<?php endforeach; ?>
				</ol>
				<p class="pixva-notice pixva-notice--warning"><?php echo esc_html( $guide['risk'] ); ?></p>
			</div>
			<?php ++$index; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * ابزار ۳۹: محاسبه فاصله استاندارد تماشا.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_viewing_distance( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-viewing-distance>
		<?php
		pixva_tool_field( 'vd-size-' . $id, __( 'سایز پنل (اینچ)', 'pixva' ), 'number', array( 'name' => 'size', 'min' => 24, 'max' => 98, 'value' => 55, 'inputmode' => 'numeric' ) );
		pixva_tool_field(
			'vd-res-' . $id,
			__( 'رزولوشن', 'pixva' ),
			'select',
			array(
				'name'    => 'resolution',
				'options' => array(
					'fhd' => 'Full HD (1080p)',
					'uhd' => '4K UHD (2160p)',
					'8k'  => '8K (4320p)',
				),
			)
		);
		?>
	</div>
	<div class="pixva-breakdown" data-vd-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'مبنای محاسبه زاویه دید ۳۰ درجه برای 4K و ۳۲ درجه برای Full HD (استاندارد SMPTE/THX) به‌همراه ارتفاع نصب مرکز صفحه روی ۱۰۵ تا ۱۱۵ سانتی‌متر از کف.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۰: تنظیم اعلان پیامکی تغییر وضعیت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_sms_pref( $id, $tool ) {
	$stages = array(
		'received'  => __( 'دریافت دستگاه در کارگاه', 'pixva' ),
		'diagnosed' => __( 'پایان عیب‌یابی و اعلام هزینه قطعی', 'pixva' ),
		'waiting'   => __( 'انتظار برای تأمین قطعه', 'pixva' ),
		'repaired'  => __( 'پایان تعمیر', 'pixva' ),
		'tested'    => __( 'پاس شدن تست کیفیت', 'pixva' ),
		'ready'     => __( 'آماده تحویل', 'pixva' ),
	);
	?>
	<form class="pixva-tool-row" data-request-form="sms" novalidate>
		<?php
		pixva_tool_field( 'sms-code-' . $id, __( 'کد پیگیری', 'pixva' ), 'text', array( 'name' => 'code', 'placeholder' => 'PXV-XXXXXX', 'required' => true ) );
		pixva_tool_field( 'sms-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		?>
		<fieldset class="pixva-fieldset" data-sms-stages>
			<legend><?php esc_html_e( 'در کدام مرحله‌ها پیامک دریافت کنم؟', 'pixva' ); ?></legend>
			<?php foreach ( $stages as $key => $label ) : ?>
				<label class="pixva-check">
					<input type="checkbox" name="stages[]" value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, array( 'diagnosed', 'ready' ), true ) ? 'checked' : ''; ?>>
					<span><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ذخیره تنظیمات پیامک', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'ارسال پیامک تنها در صورت تنظیم کلید سرویس پیامک در مرکز کنترل پیشخوان انجام می‌شود؛ در غیر این صورت تنظیمات فقط روی پرونده ثبت و در اعلان ایمیل اعمال می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۱: نصب وب‌اپلیکیشن (PWA).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_pwa_install( $id, $tool ) {
	?>
	<div class="pixva-pwa-card" data-pwa-install>
		<p><strong><?php esc_html_e( 'نصب وب‌اپ پیکسوا روی گوشی', 'pixva' ); ?></strong></p>
		<p class="pixva-muted"><?php esc_html_e( 'دسترسی سریع به نرخ‌نامه، پیگیری پرونده و دستیار هوشمند بدون باز کردن مرورگر.', 'pixva' ); ?></p>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--cta" data-pwa-button><?php esc_html_e( 'نصب وب‌اپ', 'pixva' ); ?></button>
			<span class="pixva-tool-note" data-pwa-hint><?php esc_html_e( 'در iOS: دکمه Share و سپس Add to Home Screen. در اندروید/کروم: منوی سه‌نقطه و Install app.', 'pixva' ); ?></span>
		</div>
	</div>
	<?php
}

/**
 * ابزار ۴۲: سنجش استهلاک و عمر مفید بک‌لایت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_backlight_life( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-backlight-life>
		<?php
		pixva_tool_field( 'bl-hours-' . $id, __( 'ساعات کارکرد تا امروز', 'pixva' ), 'number', array( 'name' => 'hours', 'min' => 0, 'max' => 80000, 'value' => 12000, 'inputmode' => 'numeric' ) );
		pixva_tool_field(
			'bl-brightness-' . $id,
			__( 'میانگین روشنایی', 'pixva' ),
			'select',
			array(
				'name'    => 'brightness',
				'options' => array(
					'low'  => __( 'کم (زیر ۴۰٪)', 'pixva' ),
					'mid'  => __( 'متوسط (۴۰ تا ۷۰٪)', 'pixva' ),
					'high' => __( 'زیاد (بالای ۷۰٪)', 'pixva' ),
				),
			)
		);
		pixva_tool_field(
			'bl-temp-' . $id,
			__( 'تهویه دستگاه', 'pixva' ),
			'select',
			array(
				'name'    => 'vent',
				'options' => array(
					'open'   => __( 'فضای باز و تهویه مناسب', 'pixva' ),
					'closed' => __( 'داخل محفظه بسته یا چسبیده به دیوار', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<div class="pixva-breakdown" data-backlight-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'مبنای استهلاک کارگاه: عمر نامی بک‌لایت LED ۴۰٬۰۰۰ ساعت در روشنایی متوسط؛ روشنایی بالا ۱٫۴ برابر و تهویه نامناسب ۱٫۲ برابر استهلاک را تسریع می‌کند.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۳: تستر پورت‌های HDMI و ARC.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_port_tester( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-port-tester>
		<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-port-pattern="grid"><?php esc_html_e( 'الگوی شبکه (تست رزولوشن)', 'pixva' ); ?></button>
		<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-port-pattern="gradient"><?php esc_html_e( 'الگوی شیب رنگ (تست ۱۰ بیت)', 'pixva' ); ?></button>
		<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-port-pattern="motion"><?php esc_html_e( 'الگوی حرکت (تست نرخ نوسازی)', 'pixva' ); ?></button>
		<button type="button" class="pixva-btn pixva-btn--primary" data-port-hdcp><?php esc_html_e( 'راهنمای عیب‌یابی HDCP', 'pixva' ); ?></button>
	</div>
	<div class="pixva-port-canvas" data-port-canvas></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'الگوها روی همین صفحه تولید می‌شوند؛ برای تست واقعی، صفحه را با کابل HDMI به تلویزیون وصل و تمام‌صفحه کنید. پرش یا برفک دیجیتال نشانه کابل یا پورت معیوب است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۴: ارزیابی ارزش داغی دستگاه کهنه.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_trade_in( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-trade-in>
		<?php
		pixva_tool_field( 'ti-size-' . $id, __( 'سایز دستگاه', 'pixva' ), 'select', array( 'name' => 'size', 'options' => pixva_size_catalog() ) );
		pixva_tool_field( 'ti-age-' . $id, __( 'سن دستگاه (سال)', 'pixva' ), 'number', array( 'name' => 'age', 'min' => 0, 'max' => 25, 'value' => 7, 'inputmode' => 'numeric' ) );
		pixva_tool_field(
			'ti-panel-' . $id,
			__( 'وضعیت پنل', 'pixva' ),
			'select',
			array(
				'name'    => 'panel',
				'options' => array(
					'intact'  => __( 'سالم (بدون خط و شکستگی)', 'pixva' ),
					'lines'   => __( 'خط‌دار اما بدون شکستگی', 'pixva' ),
					'cracked' => __( 'شکسته / ضربه‌خورده', 'pixva' ),
				),
			)
		);
		pixva_tool_field(
			'ti-board-' . $id,
			__( 'وضعیت بردها', 'pixva' ),
			'select',
			array(
				'name'    => 'boards',
				'options' => array(
					'ok'      => __( 'بردها سالم‌اند', 'pixva' ),
					'partial' => __( 'یک برد معیوب', 'pixva' ),
					'dead'    => __( 'هر دو برد معیوب', 'pixva' ),
				),
			)
		);
		?>
		<button type="button" class="pixva-btn pixva-btn--primary" data-trade-run><?php esc_html_e( 'ارزیابی داغی', 'pixva' ); ?></button>
	</div>
	<div class="pixva-breakdown" data-trade-result hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'مبنای ارزیابی کارگاه: ارزش داغی از قیمت پایه قطعات قابل استفاده (پنل سالم، برد پاور، مین‌برد، اسپیکر، ریموت) و ضریب سن دستگاه محاسبه می‌شود.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۵: راهنمای تعاملی کالیبراسیون نور و رنگ.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_calibration( $id, $tool ) {
	$steps = pixva_calibration_steps();
	?>
	<div class="pixva-calibration" data-calibration>
		<ol class="pixva-checklist pixva-checklist--steps">
			<?php foreach ( $steps as $index => $step ) : ?>
				<li>
					<label>
						<input type="checkbox" data-cal-item value="<?php echo esc_attr( $index ); ?>">
						<span><strong><?php echo esc_html( $step['title'] ); ?></strong><em><?php echo esc_html( $step['text'] ); ?></em></span>
					</label>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-cal-pattern="black"><?php esc_html_e( 'الگوی سیاه', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-cal-pattern="white"><?php esc_html_e( 'الگوی سفید', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-cal-pattern="gray"><?php esc_html_e( 'شیب خاکستری', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-cal-pattern="color"><?php esc_html_e( 'نوار رنگی', 'pixva' ); ?></button>
		</div>
		<div class="pixva-port-canvas" data-cal-canvas></div>
		<div class="pixva-bar"><span data-cal-progress></span></div>
	</div>
	<?php
}

/* ==========================================================================
   هاب ۵ — انبار قطعات و B2B (ابزار ۴۶ تا ۶۰)
   ========================================================================== */

/**
 * ابزار ۴۶: جدول مقایسه طول عمر و کیفیت برندها.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_brand_table( $id, $tool ) {
	$brands   = pixva_brand_catalog();
	$lifespan = pixva_brand_lifespan_data();
	$pricing  = pixva_pricing_brand_defaults();
	?>
	<div class="pixva-table-wrap">
		<table class="pixva-rates-table" data-brand-table>
			<thead>
				<tr>
					<th><?php esc_html_e( 'برند', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'عمر مفید میانگین', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'فراوانی قطعه در بازار', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'ضریب نرخ‌نامه', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'یادداشت کارگاه', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $brands as $key => $brand ) : ?>
					<?php
					$life = isset( $lifespan[ $key ] ) ? $lifespan[ $key ] : array(
						'life'  => 6,
						'parts' => __( 'متوسط', 'pixva' ),
						'note'  => __( 'قطعات از مسیر واردات عمومی تأمین می‌شود.', 'pixva' ),
					);
					?>
					<tr>
						<td data-label="<?php esc_attr_e( 'برند', 'pixva' ); ?>"><strong><?php echo esc_html( $brand['fa'] ); ?></strong> <span class="pixva-latin pixva-muted"><?php echo esc_html( $brand['en'] ); ?></span></td>
						<td data-label="<?php esc_attr_e( 'عمر مفید', 'pixva' ); ?>"><?php echo esc_html( pixva_fa_num( (string) $life['life'] ) . ' ' . __( 'سال', 'pixva' ) ); ?></td>
						<td data-label="<?php esc_attr_e( 'قطعه', 'pixva' ); ?>"><?php echo esc_html( $life['parts'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'ضریب', 'pixva' ); ?>"><?php echo esc_html( pixva_fa_num( isset( $pricing[ $key ] ) ? number_format( (float) $pricing[ $key ], 2 ) : '1.00' ) ); ?></td>
						<td data-label="<?php esc_attr_e( 'یادداشت', 'pixva' ); ?>"><?php echo esc_html( $life['note'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * ابزار ۴۷: رزرو تعمیر در محل زیر ۱ ساعت.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_onsite_booking( $id, $tool ) {
	$zones = pixva_zone_catalog();
	?>
	<form class="pixva-tool-row" data-request-form="onsite" novalidate>
		<?php
		pixva_tool_field( 'os-name-' . $id, __( 'نام', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'os-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field( 'os-zone-' . $id, __( 'منطقه', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) );
		pixva_tool_field( 'os-address-' . $id, __( 'آدرس دقیق', 'pixva' ), 'text', array( 'name' => 'address', 'required' => true ) );
		pixva_tool_field(
			'os-service-' . $id,
			__( 'خدمت در محل', 'pixva' ),
			'select',
			array(
				'name'    => 'service',
				'options' => array(
					'backlight'  => __( 'تعویض بک‌لایت در محل', 'pixva' ),
					'mount'      => __( 'نصب و دیوارکوبی', 'pixva' ),
					'software'   => __( 'رفع مشکلات نرم‌افزاری و آپدیت', 'pixva' ),
					'diagnostic' => __( 'عیب‌یابی تخصصی در محل', 'pixva' ),
				),
			)
		);
		pixva_tool_field( 'os-note-' . $id, __( 'توضیح علائم', 'pixva' ), 'textarea', array( 'name' => 'message' ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'رزرو اعزام فوری', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'خدمات در محل برای تعویض بک‌لایت سایزهای تا ۶۵ اینچ، نصب و مشکلات نرم‌افزاری ارائه می‌شود؛ تعمیرات برد و بندینگ فقط در کارگاه مرکزی انجام می‌گیرد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۸: پیشنهاد محافظ صفحه و استبیلایزر.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_protector( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-protector>
		<?php
		pixva_tool_field( 'pr-size-' . $id, __( 'سایز دستگاه', 'pixva' ), 'select', array( 'name' => 'size', 'options' => pixva_size_catalog() ) );
		pixva_tool_field(
			'pr-risk-' . $id,
			__( 'ریسک محیطی', 'pixva' ),
			'select',
			array(
				'name'    => 'risk',
				'options' => array(
					'child'   => __( 'کودک خردسال در خانه', 'pixva' ),
					'pet'     => __( 'حیوان خانگی', 'pixva' ),
					'sun'     => __( 'نور مستقیم آفتاب', 'pixva' ),
					'normal'  => __( 'محیط معمولی', 'pixva' ),
				),
			)
		);
		pixva_tool_field(
			'pr-grid-' . $id,
			__( 'وضعیت برق منطقه', 'pixva' ),
			'select',
			array(
				'name'    => 'grid',
				'options' => array(
					'stable'   => __( 'پایدار (بدون نوسان محسوس)', 'pixva' ),
					'unstable' => __( 'نوسان و قطعی مکرر', 'pixva' ),
					'industrial' => __( 'برق صنعتی/کشاورزی مشترک', 'pixva' ),
				),
			)
		);
		pixva_tool_field( 'pr-watt-' . $id, __( 'توان دستگاه (وات)', 'pixva' ), 'number', array( 'name' => 'watt', 'min' => 20, 'max' => 900, 'value' => 130, 'inputmode' => 'numeric' ) );
		?>
	</div>
	<div class="pixva-breakdown" data-protector-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'پیشنهادها بر اساس تجربه کارگاه در پرونده‌های واقعی آسیب پنل و سوختگی برد پاور تنظیم شده‌اند.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۴۹: محاسبه توان استبیلایزر موردنیاز (VA).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_stabilizer( $id, $tool ) {
	?>
	<div class="pixva-tool-row" data-stabilizer>
		<?php
		pixva_tool_field( 'st-watt-' . $id, __( 'توان کل دستگاه‌ها (وات)', 'pixva' ), 'number', array( 'name' => 'watt', 'min' => 20, 'max' => 5000, 'value' => 180, 'inputmode' => 'numeric' ) );
		pixva_tool_field(
			'sst-extra-' . $id,
			__( 'دستگاه جانبی متصل', 'pixva' ),
			'select',
			array(
				'name'    => 'extra',
				'options' => array(
					'none'   => __( 'فقط تلویزیون', 'pixva' ),
					'sound'  => __( 'ساندبار/سینمای خانگی (+۱۵۰ وات)', 'pixva' ),
					'console' => __( 'کنسول بازی (+۲۰۰ وات)', 'pixva' ),
					'all'    => __( 'ساندبار و کنسول (+۳۵۰ وات)', 'pixva' ),
				),
			)
		);
		pixva_tool_field(
			'st-margin-' . $id,
			__( 'ضریب اطمینان', 'pixva' ),
			'select',
			array(
				'name'    => 'margin',
				'options' => array(
					'1.3' => __( '۱٫۳ (حداقل پیشنهادی)', 'pixva' ),
					'1.6' => __( '۱٫۶ (پیشنهاد کارگاه)', 'pixva' ),
					'2.0' => __( '۲٫۰ (توسعه آینده)', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<div class="pixva-breakdown" data-stabilizer-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'فرمول: VA = (وات × ضریب اطمینان) ÷ ضریب توان ۰٫۸۵ و سپس گرد شدن به نزدیک‌ترین ظرفیت استاندارد بازار (۱۰۰۰، ۱۵۰۰، ۲۰۰۰، ۳۰۰۰، ۴۰۰۰، ۶۰۰۰ VA).', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۵۰: تست سلامت ریموت کنترل با دوربین.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_remote_tester( $id, $tool ) {
	?>
	<div data-remote-tester>
		<div class="pixva-tool-row">
			<button type="button" class="pixva-btn pixva-btn--primary" data-remote-start><?php esc_html_e( 'روشن کردن دوربین', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-remote-stop><?php esc_html_e( 'خاموش کردن دوربین', 'pixva' ); ?></button>
			<span class="pixva-tool-note"><?php esc_html_e( 'دوربین را رو به فرستنده ریموت بگیرید و کلیدها را فشار دهید؛ نور مادون قرمز در دوربین به‌صورت نقطه بنفش/سفید دیده می‌شود. تصویر فقط در مرورگر شما پردازش می‌شود.', 'pixva' ); ?></span>
		</div>
		<video class="pixva-remote-video" data-remote-video playsinline muted></video>
		<ol class="pixva-steps">
			<li><?php esc_html_e( 'اگر نور IR دیده می‌شود اما دستگاه فرمان نمی‌گیرد، مشکل از گیرنده IR تلویزیون یا جفت‌سازی بلوتوثی است.', 'pixva' ); ?></li>
			<li><?php esc_html_e( 'اگر نور دیده نمی‌شود، باتری و سپس برد ریموت را بررسی کنید؛ تعویض ریموت اصلی در انبار موجود است.', 'pixva' ); ?></li>
			<li><?php esc_html_e( 'برای ریموت‌های Magic/Smart، جفت‌سازی مجدد با نگه‌داشتن هم‌زمان دو کلید مشخص انجام می‌شود.', 'pixva' ); ?></li>
		</ol>
	</div>
	<?php
}

/**
 * ابزار ۵۱: جدول زمان‌بندی تحویل قطعات وارداتی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_leadtime( $id, $tool ) {
	$rows = pixva_leadtime_catalog();
	?>
	<div class="pixva-table-wrap">
		<table class="pixva-rates-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'قطعه', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'وضعیت انبار', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'بازه واردات', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'یادداشت کارگاه', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td data-label="<?php esc_attr_e( 'قطعه', 'pixva' ); ?>"><strong><?php echo esc_html( $row['part'] ); ?></strong></td>
						<td data-label="<?php esc_attr_e( 'انبار', 'pixva' ); ?>"><?php echo esc_html( $row['instock'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'واردات', 'pixva' ); ?>"><?php echo esc_html( $row['import'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'یادداشت', 'pixva' ); ?>"><?php echo esc_html( $row['note'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * ابزار ۵۲: استعلام پرونده خسارت بیمه.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_insurance( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-request-form="insurance" novalidate>
		<?php
		pixva_tool_field( 'in-name-' . $id, __( 'نام بیمه‌گذار', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'in-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field(
			'in-company-' . $id,
			__( 'شرکت بیمه', 'pixva' ),
			'select',
			array(
				'name'    => 'insurer',
				'options' => array(
					'iran'     => __( 'بیمه ایران', 'pixva' ),
					'dana'     => __( 'بیمه دانا', 'pixva' ),
					'asia'     => __( 'بیمه آسیا', 'pixva' ),
					'alborz'   => __( 'بیمه البرز', 'pixva' ),
					'pasargad' => __( 'بیمه پاسارگاد', 'pixva' ),
					'saman'    => __( 'بیمه سامان', 'pixva' ),
					'other'    => __( 'سایر شرکت‌ها', 'pixva' ),
				),
			)
		);
		pixva_tool_field( 'in-policy-' . $id, __( 'شماره بیمه‌نامه', 'pixva' ), 'text', array( 'name' => 'policy' ) );
		pixva_tool_field( 'in-message-' . $id, __( 'شرح حادثه', 'pixva' ), 'textarea', array( 'name' => 'message', 'required' => true ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ثبت درخواست گزارش کارشناسی', 'pixva' ); ?></button>
	</form>
	<p class="pixva-tool-note"><?php esc_html_e( 'کارگاه پیکسوا گزارش رسمی کارشناسی با مهر و سربرگ برای پرونده خسارت صادر می‌کند؛ زمان صدور معمولاً ۱ تا ۲ روز کاری پس از بازدید دستگاه است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۵۳: محاسبه هزینه حمل تخصصی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_shipping_calc( $id, $tool ) {
	$zones = pixva_zone_catalog();
	?>
	<div class="pixva-tool-row" data-shipping-calc data-zones="<?php echo esc_attr( wp_json_encode( $zones ) ); ?>">
		<?php
		pixva_tool_field( 'sh-zone-' . $id, __( 'مقصد', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) );
		pixva_tool_field( 'sh-size-' . $id, __( 'سایز دستگاه', 'pixva' ), 'select', array( 'name' => 'size', 'options' => pixva_size_catalog() ) );
		pixva_tool_field(
			'sh-box-' . $id,
			__( 'نوع بسته‌بندی', 'pixva' ),
			'select',
			array(
				'name'    => 'box',
				'options' => array(
					'factory' => __( 'کارتن فابریک کارخانه', 'pixva' ),
					'padded'  => __( 'جعبه پددار کارگاه', 'pixva' ),
					'none'    => __( 'بدون بسته‌بندی (حمل با پتو)', 'pixva' ),
				),
			)
		);
		?>
	</div>
	<div class="pixva-breakdown" data-shipping-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'هزینه حمل = کرایه منطقه × ضریب سایز × ضریب بسته‌بندی. بدون بسته‌بندی مناسب، مسئولیت آسیب پنل با مشتری است.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۵۴: اشتراک سالانه پیکسوا پلاس.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_subscription( $id, $tool ) {
	$plans = array(
		'home'   => array( 'name' => __( 'پیکسوا پلاس خانگی', 'pixva' ), 'price' => 2900000, 'features' => array( __( 'یک بازدید دوره‌ای رایگان در سال', 'pixva' ), __( '۱۵٪ تخفیف قطعات فابریک', 'pixva' ), __( 'اولویت اعزام ۲ ساعته', 'pixva' ) ) ),
		'family' => array( 'name' => __( 'پیکسوا پلاس خانواده', 'pixva' ), 'price' => 4900000, 'features' => array( __( 'دو بازدید دوره‌ای رایگان', 'pixva' ), __( '۲۰٪ تخفیف قطعات تا ۳ دستگاه', 'pixva' ), __( 'گارانتی تمدیدشونده ۲۴۰ روزه', 'pixva' ) ) ),
		'office' => array( 'name' => __( 'پیکسوا پلاس اداری', 'pixva' ), 'price' => 9800000, 'features' => array( __( 'پوشش تا ۶ دستگاه', 'pixva' ), __( 'فاکتور رسمی و قرارداد سالانه', 'pixva' ), __( 'پشتیبانی تلفنی اختصاصی', 'pixva' ) ) ),
	);
	?>
	<div class="pixva-grid pixva-grid--3" data-subscription>
		<?php foreach ( $plans as $key => $plan ) : ?>
			<article class="pixva-card pixva-plan-card" data-plan="<?php echo esc_attr( $key ); ?>">
				<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( $plan['name'] ); ?></span>
				<h3><?php echo esc_html( pixva_price( $plan['price'] ) . ' ' . __( 'تومان / سال', 'pixva' ) ); ?></h3>
				<ul>
					<?php foreach ( $plan['features'] as $feature ) : ?>
						<li><?php echo pixva_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $feature ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<button type="button" class="pixva-btn pixva-btn--cta" data-plan-select="<?php echo esc_attr( $key ); ?>" data-plan-price="<?php echo esc_attr( $plan['price'] ); ?>"><?php esc_html_e( 'ثبت‌نام اشتراک', 'pixva' ); ?></button>
			</article>
		<?php endforeach; ?>
	</div>
	<form class="pixva-tool-row" data-request-form="subscription" hidden novalidate>
		<?php
		pixva_tool_field( 'sub-name-' . $id, __( 'نام', 'pixva' ), 'text', array( 'name' => 'customer_name', 'required' => true ) );
		pixva_tool_field( 'sub-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		pixva_tool_field( 'sub-plan-' . $id, __( 'اشتراک انتخابی', 'pixva' ), 'text', array( 'name' => 'plan', 'value' => '' ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'نهایی‌کردن ثبت‌نام', 'pixva' ); ?></button>
	</form>
	<?php
}

/**
 * ابزار ۵۵: سنجش واقعیت‌نما جایگاه دیوار.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_ar_wall( $id, $tool ) {
	?>
	<div data-ar-wall>
		<div class="pixva-tool-row">
			<?php pixva_tool_field( 'ar-size-' . $id, __( 'سایز تلویزیون (اینچ)', 'pixva' ), 'number', array( 'name' => 'size', 'min' => 24, 'max' => 98, 'value' => 65, 'inputmode' => 'numeric' ) ); ?>
			<button type="button" class="pixva-btn pixva-btn--primary" data-ar-start><?php esc_html_e( 'نمایش روی دیوار با دوربین', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-ar-stop><?php esc_html_e( 'توقف دوربین', 'pixva' ); ?></button>
		</div>
		<div class="pixva-ar-stage" data-ar-stage>
			<video class="pixva-ar-video" data-ar-video playsinline muted></video>
			<span class="pixva-ar-frame" data-ar-frame></span>
		</div>
		<div class="pixva-breakdown" data-ar-dims></div>
	</div>
	<p class="pixva-tool-note"><?php esc_html_e( 'ابعاد قاب بر اساس اندازه استاندارد پنل‌های ۱۶:۹ محاسبه می‌شود و روی تصویر دوربین با مقیاس قابل تنظیم می‌نشیند؛ برای دقت بیشتر، فاصله گوشی تا دیوار را وارد کنید.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۵۶: نمای انفجاری اجزای تلویزیون.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_exploded_view( $id, $tool ) {
	$parts = pixva_exploded_parts();
	?>
	<div class="pixva-exploded" data-exploded>
		<div class="pixva-exploded__stage" aria-hidden="true">
			<?php foreach ( $parts as $index => $part ) : ?>
				<span class="pixva-exploded__part" data-part-index="<?php echo esc_attr( $index ); ?>" style="--part-offset:<?php echo esc_attr( $index ); ?>"></span>
			<?php endforeach; ?>
		</div>
		<ul class="pixva-exploded__list">
			<?php foreach ( $parts as $index => $part ) : ?>
				<li>
					<button type="button" class="pixva-layer-btn" data-part-target="<?php echo esc_attr( $index ); ?>" aria-expanded="false">
						<strong><?php echo esc_html( $part['layer'] ); ?></strong>
						<span><?php echo esc_html( $part['fault'] ); ?></span>
						<em><?php echo esc_html( $part['repairable'] ); ?></em>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * ابزار ۵۷: تور مجازی کارگاه مرکزی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_virtual_tour( $id, $tool ) {
	$stations = array(
		array( 'title' => __( 'میز پذیرش و ثبت علائم', 'pixva' ), 'text' => __( 'ثبت برند، مدل، تعداد چشمک و صدور کد پیگیری برای مشتری.', 'pixva' ) ),
		array( 'title' => __( 'اتاق تست الگوی رنگ', 'pixva' ), 'text' => __( 'تست با الگوی خاکستری، رنگی و HDR پیش از تحویل دستگاه.', 'pixva' ) ),
		array( 'title' => __( 'ایستگاه بندینگ و ترمیم فلت', 'pixva' ), 'text' => __( 'دستگاه بندینگ صنعتی برای ترمیم فلت‌های COF پنل‌های آب‌خورده.', 'pixva' ) ),
		array( 'title' => __( 'ایستگاه BGA و ریبال برد', 'pixva' ), 'text' => __( 'تعویض و ریبال آی‌سی‌های مین‌برد با دستگاه BGA و میکروسکوپ.', 'pixva' ) ),
		array( 'title' => __( 'انبار قطعات فابریک', 'pixva' ), 'text' => __( 'بک‌لایت، برد پاور، مین‌برد و فلت‌ها با هولوگرام اصالت و کد رهگیری.', 'pixva' ) ),
	);
	?>
	<div class="pixva-tour" data-virtual-tour>
		<div class="pixva-tour__viewport" data-tour-viewport>
			<img src="<?php echo esc_url( PIXVA_URI . '/assets/images/bonding-lab.jpg' ); ?>" alt="<?php esc_attr_e( 'نمای کارگاه بندینگ پیکسوا در پاساژ علاءالدین', 'pixva' ); ?>" data-tour-image>
			<span class="pixva-tour__hint"><?php esc_html_e( 'برای دیدن ادامه کارگاه، تصویر را بکشید (یا با کیبورد جهت‌نما)', 'pixva' ); ?></span>
		</div>
		<ol class="pixva-tour__stations">
			<?php foreach ( $stations as $index => $station ) : ?>
				<li>
					<button type="button" class="pixva-layer-btn" data-tour-station="<?php echo esc_attr( $index ); ?>" aria-expanded="false">
						<strong><?php echo esc_html( pixva_fa_num( (string) ( $index + 1 ) ) . '. ' . $station['title'] ); ?></strong>
						<span><?php echo esc_html( $station['text'] ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
	<?php
}

/**
 * ابزار ۵۸: راهنمای رفع نویز و تداخل فرکانسی.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_interference( $id, $tool ) {
	$rows = pixva_interference_tree();
	?>
	<div class="pixva-tool-row" data-interference data-rows="<?php echo esc_attr( wp_json_encode( array_values( $rows ) ) ); ?>">
		<?php
		pixva_tool_field(
			'if-symptom-' . $id,
			__( 'علائم مشاهده‌شده', 'pixva' ),
			'select',
			array(
				'name'    => 'symptom',
				'options' => array( '' => __( 'انتخاب کنید…', 'pixva' ) ) + array_combine( array_keys( $rows ), wp_list_pluck( $rows, 'symptom' ) ),
			)
		);
		?>
	</div>
	<div class="pixva-interference-result" data-interference-result hidden></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'درخت تصمیم بر اساس پرونده‌های واقعی تداخل در تهران تنظیم شده است؛ اگر با رفع منبع تداخل مشکل باقی ماند، برد مین یا تیونر ورودی نیاز به بررسی کارگاهی دارد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۵۹: سنجش کیفیت کابل 4K و 8K.
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_cable_check( $id, $tool ) {
	$standards = pixva_cable_standards();
	?>
	<div class="pixva-tool-row" data-cable-check data-standards="<?php echo esc_attr( wp_json_encode( $standards ) ); ?>">
		<?php
		pixva_tool_field( 'cb-mode-' . $id, __( 'حالت استفاده', 'pixva' ), 'select', array( 'name' => 'mode', 'options' => wp_list_pluck( $standards, 'label' ) ) );
		pixva_tool_field( 'cb-length-' . $id, __( 'طول کابل موردنیاز (متر)', 'pixva' ), 'number', array( 'name' => 'length', 'min' => 1, 'max' => 30, 'value' => 3, 'inputmode' => 'numeric' ) );
		?>
	</div>
	<div class="pixva-breakdown" data-cable-result></div>
	<p class="pixva-tool-note"><?php esc_html_e( 'پهنای باند و استاندارد کابل از جدول واقعی HDMI استخراج می‌شود؛ بیش از طول مجاز، فقط کابل اکتیو فیبری پاسخ می‌دهد.', 'pixva' ); ?></p>
	<?php
}

/**
 * ابزار ۶۰: گزارش کامل کارشناسی (چاپ / PDF).
 *
 * @param int   $id   شناسه ابزار.
 * @param array $tool رکورد رجیستری.
 * @return void
 */
function pixva_tool_render_report_export( $id, $tool ) {
	?>
	<form class="pixva-tool-row" data-report-form novalidate>
		<?php
		pixva_tool_field( 'rp-code-' . $id, __( 'کد پیگیری پرونده', 'pixva' ), 'text', array( 'name' => 'code', 'placeholder' => 'PXV-XXXXXX', 'required' => true ) );
		pixva_tool_field( 'rp-phone-' . $id, __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'required' => true ) );
		?>
		<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ساخت گزارش کارشناسی', 'pixva' ); ?></button>
		<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-report-print hidden><?php esc_html_e( 'چاپ / ذخیره PDF', 'pixva' ); ?></button>
	</form>
	<article class="pixva-report" data-report hidden>
		<header class="pixva-report__head">
			<strong><?php esc_html_e( 'گزارش کارشناسی فنی مرکز تعمیرات پیکسوا', 'pixva' ); ?></strong>
			<span data-report-code></span>
		</header>
		<ol class="pixva-timeline pixva-timeline--report" data-report-timeline></ol>
		<dl class="pixva-report__grid">
			<div><dt><?php esc_html_e( 'دستگاه', 'pixva' ); ?></dt><dd data-report-device></dd></div>
			<div><dt><?php esc_html_e( 'شرح پذیرش', 'pixva' ); ?></dt><dd data-report-problem></dd></div>
			<div><dt><?php esc_html_e( 'برآورد هزینه', 'pixva' ); ?></dt><dd data-report-estimate></dd></div>
			<div><dt><?php esc_html_e( 'وضعیت پرونده', 'pixva' ); ?></dt><dd data-report-status></dd></div>
			<div><dt><?php esc_html_e( 'گارانتی', 'pixva' ); ?></dt><dd data-report-warranty></dd></div>
			<div><dt><?php esc_html_e( 'تاریخ صدور گزارش', 'pixva' ); ?></dt><dd data-report-date></dd></div>
		</dl>
		<p class="pixva-report__sign"><?php esc_html_e( 'کارگاه مرکزی پیکسوا — پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ); ?></p>
	</article>
	<p class="pixva-tool-note"><?php esc_html_e( 'گزارش از داده واقعی پرونده ساخته می‌شود و با گزینه چاپ مرورگر می‌توان آن را به‌صورت PDF ذخیره یا برای بیمه ارسال کرد.', 'pixva' ); ?></p>
	<?php
}
