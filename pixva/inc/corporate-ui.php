<?php
/**
 * لایه رابط کاربری سازمانی پیکسوا — نسخه ۲٫۱٫۰ (Master Prompt v12)
 *
 * این پرونده «سامانه طراحی سازمانی» را به پوسته وصل می‌کند:
 *  - کلیدهای حالت سازمانی (قابل خاموش‌کردن از سفارشی‌ساز، پیش‌فرض روشن)؛
 *  - داده واقعی فهرست اعتماد هیرو (گارانتی/اعزام/قطعه فابریک) بدون متن سخت‌کد؛
 *  - کارت فرم اعزام فوری برای ستون کناری هیروی دوستونی (وارینت card ماژول v11).
 *
 * هیچ اسکریپت تازه‌ای بارگذاری نمی‌شود؛ رفتار فرم از همان seo-cro.js است.
 *
 * @package Pixva
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

if ( ! function_exists( 'pixva_corporate_ui_mode' ) ) {
	/**
	 * حالت رابط سازمانی (لایه ۲٫۱٫۰) روشن است؟
	 *
	 * @return bool
	 */
	function pixva_corporate_ui_mode() {
		$enabled = (bool) pixva_option( 'pixva_corporate_ui', true );

		/**
		 * فیلتر حالت رابط کاربری سازمانی.
		 *
		 * @param bool $enabled روشن بودن لایه سازمانی.
		 */
		return (bool) apply_filters( 'pixva_corporate_ui_mode', $enabled );
	}
}

if ( ! function_exists( 'pixva_corporate_hero_enabled' ) ) {
	/**
	 * آیا هیروی دوستونی سازمانی (به‌جای هیروی قدیمی) رندر شود؟
	 *
	 * @return bool
	 */
	function pixva_corporate_hero_enabled() {
		if ( ! pixva_corporate_ui_mode() ) {
			return false;
		}

		$enabled = (bool) pixva_option( 'pixva_corporate_hero', true );

		/**
		 * فیلتر هیروی سازمانی.
		 *
		 * @param bool $enabled روشن بودن هیروی دوستونی.
		 */
		return (bool) apply_filters( 'pixva_corporate_hero_enabled', $enabled );
	}
}

if ( ! function_exists( 'pixva_hero_trust_items' ) ) {
	/**
	 * سه مورد فهرست اعتماد هیرو — همه از داده واقعی سایت ساخته می‌شوند.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_hero_trust_items() {
		$control  = function_exists( 'pixva_control_options' ) ? (array) pixva_control_options() : array();
		$hours    = isset( $control['hub_eta_hours'] ) && '' !== $control['hub_eta_hours'] ? (string) $control['hub_eta_hours'] : __( '۲ ساعت', 'pixva' );
		$warranty = function_exists( 'pixva_warranty_days' ) ? pixva_warranty_days() : 180;

		$items = array(
			array(
				'icon' => 'truck',
				'text' => sprintf(
					/* translators: %s: زمان اعزام */
					__( 'اعزام تکنسین به منزل یا محل شما در کمتر از %s — بدون حمل دستگاه', 'pixva' ),
					$hours
				),
			),
			array(
				'icon' => 'shield',
				'text' => sprintf(
					/* translators: %s: روزهای گارانتی */
					__( 'گارانتی کتبی %s روزه قطعات و اجرت تعمیر روی فاکتور رسمی', 'pixva' ),
					function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $warranty ) : $warranty
				),
			),
			array(
				'icon' => 'cert',
				'text' => __( 'قطعات فابریک با هولوگرام اصالت و برآورد شفاف هزینه پیش از شروع تعمیر', 'pixva' ),
			),
		);

		/**
		 * فیلتر فهرست اعتماد هیروی سازمانی.
		 *
		 * @param array $items سه مورد اعتماد.
		 */
		return apply_filters( 'pixva_hero_trust_items', $items );
	}
}

if ( ! function_exists( 'pixva_render_hero_booking' ) ) {
	/**
	 * کارت فرم اعزام فوری برای ستون کناری هیرو (وارینت card ماژول v11).
	 *
	 * همان رندر مشترک pixva_render_express_booking با شناسه، منبع و عنوان
	 * جداگانه؛ بنابراین اعتبارسنجی، اندپوینت REST و پیامک بدون تغییر است.
	 *
	 * @return void
	 */
	function pixva_render_hero_booking() {
		if ( ! function_exists( 'pixva_render_express_booking' ) ) {
			return;
		}

		pixva_render_express_booking(
			array(
				'variant'    => 'card',
				'section_id' => 'hero-booking',
				'source'     => 'hero',
				'title'      => (string) pixva_option( 'pixva_hero_booking_title', __( 'اعزام فوری تکنسین تعمیر تلویزیون', 'pixva' ) ),
				'subtitle'   => (string) pixva_option(
					'pixva_hero_booking_subtitle',
					__( 'شماره موبایل و شرح کوتاه مشکل را بنویسید؛ کارشناس برای هماهنگی و اعلام برآورد هزینه تماس می‌گیرد.', 'pixva' )
				),
			)
		);
	}
}
