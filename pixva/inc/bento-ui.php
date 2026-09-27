<?php
/**
 * پیکسوا — موتور رندر Bento (لایه ۴٫۰٫۰ / Master Prompt v14)
 *
 * همه رندررهای صفحه اصلی جدید: هیروی مرکزی تعاملی با موتور رزرو تب‌دار،
 * کاوشگر بنتو ۱۲ ستونه (انتخابگر عیب، رهگیر اعزام، استعلام گارانتی و
 * مقایسه قیمت برند)، نوار برندها و جمع‌بندی CTA.
 *
 * اصل‌ها:
 * - هیچ متن/عدد/لینک سخت‌کدشده‌ای وجود ندارد؛ همه‌چیز از options، کاتالوگ‌ها
 *   و موتور نرخ‌نامه (pixva_calculate_estimate) می‌آید.
 * - خروجی فقط کلاس‌های جدید bx- است (بدون هیچ ساختار قدیمی pixva یا px).
 * - REST: pixva/v1/price-table، pixva/v1/express-booking، pixva/v1/dispatch-live
 *   و pixva/v1/track (هندلرها در همان ماژول‌های موجودند).
 *
 * @package Pixva
 * @since   4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_bento_icons' ) ) {
	/**
	 * آیکون‌های SVG درون‌خطی اختصاصی بنتو (بدون کتابخانه بیرونی).
	 *
	 * @param string $name نام آیکون.
	 * @return string
	 */
	function pixva_bento_icons( $name ) {
		$icons = array(
			'bolt'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
			'wrench'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
			'tag'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2H2v10l9.29 9.29a2.4 2.4 0 0 0 3.42 0l6.58-6.58a2.4 2.4 0 0 0 0-3.42z"/><path d="M7 7h.01"/></svg>',
			'search'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
			'shield'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
			'radar'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19.07 4.93A10 10 0 0 0 6.99 3.34"/><path d="M4 6h.01"/><path d="M2.29 9.62a10 10 0 1 0 19.02-1.27"/><path d="M16.24 7.76a6 6 0 1 0-8.01 8.91"/><path d="M12 18h.01"/><path d="M17.99 11.66a6 6 0 0 1-2.22 4.58"/><circle cx="12" cy="12" r="2"/><path d="m13.41 10.59 5.66-5.66"/></svg>',
			'tech'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
			'phone'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
			'check'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
			'clock'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
			'chart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
			'menu'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
			'close'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
			'pin'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>',
			'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/></svg>',
			'book'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
			'sparkle'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.287 1.288L3 12l5.8 1.9a2 2 0 0 1 1.288 1.287L12 21l1.9-5.8a2 2 0 0 1 1.287-1.288L21 12l-5.8-1.9a2 2 0 0 1-1.288-1.287z"/></svg>',
		);

		$name = (string) $name;
		if ( isset( $icons[ $name ] ) ) {
			return $icons[ $name ];
		}

		// بازگشت امن به مجموعه آیکون پوسته.
		if ( function_exists( 'pixva_icon' ) ) {
			return (string) pixva_icon( $name );
		}

		return $icons['sparkle'];
	}
}

if ( ! function_exists( 'pixva_bento_url' ) ) {
	/**
	 * خروجی امن برای لینک CTA: لنگرهای درون‌صفحه‌ای (#id) یا نشانی کامل.
	 *
	 * esc_url لنگر تک‌تکه‌ای را خالی می‌کند؛ این کمکی هر دو حالت را پوشش می‌دهد.
	 *
	 * @param string $url لینک.
	 * @return string
	 */
	function pixva_bento_url( $url ) {
		$url = trim( (string) $url );
		if ( preg_match( '/^#[A-Za-z0-9_-]+$/', $url ) ) {
			return $url;
		}
		return esc_url( $url );
	}
}

if ( ! function_exists( 'pixva_bento_first_key' ) ) {
	/**
	 * نخستین کلید آرایه (سازگار با PHP 7.2).
	 *
	 * @param array<mixed, mixed> $array آرایه.
	 * @return string
	 */
	function pixva_bento_first_key( $array ) {
		foreach ( (array) $array as $key => $unused ) {
			return (string) $key;
		}
		return '';
	}
}

if ( ! function_exists( 'pixva_bento_services' ) ) {
	/**
	 * خدمات موتور رزرو (تب درخواست تعمیر) — کلید و برچسب از کاتالوگ واقعی.
	 *
	 * @return array<int, array{key: string, label: string}>
	 */
	function pixva_bento_services() {
		$problems = function_exists( 'pixva_problem_catalog' ) ? (array) pixva_problem_catalog() : array();
		$keys     = function_exists( 'pixva_price_table_services' ) ? (array) pixva_price_table_services() : array();
		$out      = array();

		foreach ( $keys as $key ) {
			$out[] = array(
				'key'   => (string) $key,
				'label' => isset( $problems[ $key ] ) ? (string) $problems[ $key ] : (string) $key,
			);
		}

		/**
		 * فیلتر خدمات موتور رزرو بنتو.
		 *
		 * @param array<int, array{key: string, label: string}> $out خدمات.
		 */
		return (array) apply_filters( 'pixva_bento_services', $out );
	}
}

if ( ! function_exists( 'pixva_bento_initial_estimate' ) ) {
	/**
	 * برآورد اولیه سمت سرور برای موتور رزرو (بدون نیاز به JS).
	 *
	 * @param string $brand   کلید برند.
	 * @param string $size    سایز.
	 * @param string $service کلید خدمت.
	 * @return array{min: int, max: int, days: string}|null
	 */
	function pixva_bento_initial_estimate( $brand, $size, $service ) {
		if ( ! function_exists( 'pixva_calculate_estimate' ) ) {
			return null;
		}

		$estimate = pixva_calculate_estimate( (string) $brand, 'led', (string) $size, (string) $service );
		if ( ! is_array( $estimate ) || empty( $estimate['min'] ) ) {
			return null;
		}

		return array(
			'min'  => (int) $estimate['min'],
			'max'  => (int) $estimate['max'],
			'days' => isset( $estimate['days'] ) ? (string) $estimate['days'] : '',
		);
	}
}

if ( ! function_exists( 'pixva_bento_micro_trust' ) ) {
	/**
	 * سه نشان اعتماد شناور هیرو (گارانتی، اعزام، شفافیت).
	 *
	 * @return array<int, array{icon: string, text: string, tone: string}>
	 */
	function pixva_bento_micro_trust() {
		$warranty = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;
		$control  = function_exists( 'pixva_control_options' ) ? (array) pixva_control_options() : array();
		$eta      = isset( $control['hub_eta_hours'] ) ? (string) $control['hub_eta_hours'] : '۲ ساعت';
		$fa       = function_exists( 'pixva_fa_num' ) ? 'pixva_fa_num' : 'strval';

		$items = array(
			array(
				'icon' => 'shield',
				'text' => sprintf( /* translators: %s: روز */ __( 'گارانتی کتبی %s روزه', 'pixva' ), $fa( (string) $warranty ) ),
				'tone' => '',
			),
			array(
				'icon' => 'bolt',
				'text' => sprintf( /* translators: %s: بازه اعزام */ __( 'اعزام تکنسین زیر %s', 'pixva' ), $eta ),
				'tone' => 'bx-microtrust__item--warn',
			),
			array(
				'icon' => 'tag',
				'text' => (string) pixva_option( 'pixva_hero_trust_fee', __( 'بدون هزینه پنهان پیش از تعمیر', 'pixva' ) ),
				'tone' => 'bx-microtrust__item--brand',
			),
		);

		/**
		 * فیلتر نشان‌های اعتماد خرد هیرو.
		 *
		 * @param array $items نشان‌ها.
		 */
		return (array) apply_filters( 'pixva_bento_micro_trust', $items );
	}
}

if ( ! function_exists( 'pixva_bento_nav_fallback' ) ) {
	/**
	 * ناوبری قرصی هدر بر پایه پنج هاب تخصصی (fallback منوی سفارشی).
	 *
	 * @return void
	 */
	function pixva_bento_nav_fallback() {
		echo '<ul class="bx-nav" data-bx-nav>';
		if ( function_exists( 'pixva_hubs' ) ) {
			foreach ( (array) pixva_hubs() as $hub ) {
				printf(
					'<li><a class="bx-nav-link" href="%1$s">%2$s<span>%3$s</span></a></li>',
					esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( $hub['slug'] ) : home_url( '/' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					pixva_bento_icons( isset( $hub['icon'] ) ? $hub['icon'] : 'sparkle' ),
					esc_html( isset( $hub['short'] ) ? $hub['short'] : $hub['title'] )
				);
			}
		}
		printf(
			'<li><a class="bx-nav-link" href="%1$s">%2$s<span>%3$s</span></a></li>',
			esc_url( function_exists( 'pixva_blog_url' ) ? pixva_blog_url() : home_url( '/' ) ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			pixva_bento_icons( 'book' ),
			esc_html__( 'مجله تعمیرات', 'pixva' )
		);
		echo '</ul>';
	}
}

if ( ! function_exists( 'pixva_bento_booking' ) ) {
	/**
	 * موتور رزرو تعاملی: پانل شیشه‌ای با سه تب (رزرو، قیمت فوری، پیگیری).
	 *
	 * @return void
	 */
	function pixva_bento_booking() {
		$brands   = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$sizes    = function_exists( 'pixva_price_table_sizes' ) ? (array) pixva_price_table_sizes() : array( '55' );
		$services = pixva_bento_services();
		$fa       = function_exists( 'pixva_fa_num' ) ? 'pixva_fa_num' : 'strval';

		$brand_default = (string) pixva_option( 'pixva_bento_default_brand', ! empty( $brands ) ? (string) pixva_bento_first_key( $brands ) : '' );
		if ( ! isset( $brands[ $brand_default ] ) ) {
			$brand_default = ! empty( $brands ) ? (string) pixva_bento_first_key( $brands ) : '';
		}
		$size_default    = in_array( (string) pixva_option( 'pixva_bento_default_size', '55' ), $sizes, true ) ? (string) pixva_option( 'pixva_bento_default_size', '55' ) : (string) $sizes[0];
		$service_default = ! empty( $services ) ? (string) $services[0]['key'] : '';

		$initial = pixva_bento_initial_estimate( $brand_default, $size_default, $service_default );
		$nonce   = function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'pixva_express_booking' ) : '';
		$phone   = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';

		$estimate_html = '';
		if ( is_array( $initial ) && function_exists( 'pixva_price' ) ) {
			$estimate_html = sprintf(
				'%1$s — %2$s %3$s',
				pixva_price( $initial['min'] ),
				pixva_price( $initial['max'] ),
				esc_html__( 'تومان', 'pixva' )
			);
		}
		?>
		<div class="bx-booking" id="booking" data-bx-booking
			data-brand="<?php echo esc_attr( $brand_default ); ?>"
			data-size="<?php echo esc_attr( $size_default ); ?>"
			data-service="<?php echo esc_attr( $service_default ); ?>">

			<div class="bx-tabs" role="tablist" aria-label="<?php esc_attr_e( 'موتور رزرو تعاملی', 'pixva' ); ?>">
				<button type="button" class="bx-tab is-active" role="tab" id="bx-tab-book" aria-selected="true" aria-controls="bx-panel-book" data-bx-tab="book">
					<?php echo pixva_bento_icons( 'wrench' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( (string) pixva_option( 'pixva_bento_tab_book', __( 'رزرو تعمیرکار', 'pixva' ) ) ); ?></span>
				</button>
				<button type="button" class="bx-tab" role="tab" id="bx-tab-price" aria-selected="false" aria-controls="bx-panel-price" data-bx-tab="price">
					<?php echo pixva_bento_icons( 'chart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( (string) pixva_option( 'pixva_bento_tab_price', __( 'قیمت فوری', 'pixva' ) ) ); ?></span>
				</button>
				<button type="button" class="bx-tab" role="tab" id="bx-tab-track" aria-selected="false" aria-controls="bx-panel-track" data-bx-tab="track">
					<?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( (string) pixva_option( 'pixva_bento_tab_track', __( 'پیگیری و گارانتی', 'pixva' ) ) ); ?></span>
				</button>
			</div>

			<!-- تب ۱: رزرو اعزام با برآورد زنده -->
			<div class="bx-panel is-active" role="tabpanel" id="bx-panel-book" aria-labelledby="bx-tab-book" data-bx-panel="book">
				<form class="bx-booking__form" data-bx-booking-form novalidate>
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" name="source" value="bento-hero">
					<label class="bx-honeypot" for="bx-hp-hero">
						<span><?php esc_html_e( 'این فیلد را خالی بگذارید', 'pixva' ); ?></span>
						<input type="text" id="bx-hp-hero" name="pixva_hp" tabindex="-1" autocomplete="off">
					</label>

					<div class="bx-form-row">
						<div class="bx-field">
							<label class="bx-field__label" for="bx-brand"><?php echo esc_html( (string) pixva_option( 'pixva_express_brand_label', __( 'برند تلویزیون', 'pixva' ) ) ); ?></label>
							<select class="bx-select" id="bx-brand" name="brand" data-bx-brand>
								<?php foreach ( $brands as $key => $brand ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $brand_default ); ?>>
										<?php echo esc_html( $brand['fa'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="bx-field">
							<label class="bx-field__label" for="bx-size"><?php esc_html_e( 'سایز صفحه (اینچ)', 'pixva' ); ?></label>
							<select class="bx-select" id="bx-size" name="size" data-bx-size>
								<?php foreach ( $sizes as $size ) : ?>
									<option value="<?php echo esc_attr( $size ); ?>" <?php selected( (string) $size, $size_default ); ?>>
										<?php echo esc_html( $fa( (string) $size ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="bx-field">
						<label class="bx-field__label" for="bx-service"><?php esc_html_e( 'نوع ایراد / خدمت', 'pixva' ); ?></label>
						<select class="bx-select" id="bx-service" name="service" data-bx-service>
							<?php foreach ( $services as $service ) : ?>
								<option value="<?php echo esc_attr( $service['key'] ); ?>" <?php selected( $service['key'], $service_default ); ?>>
									<?php echo esc_html( $service['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="bx-estimate" data-bx-estimate aria-live="polite">
						<span class="bx-estimate__label">
							<?php echo pixva_bento_icons( 'sparkle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'برآورد زنده نرخ‌نامه', 'pixva' ); ?>
						</span>
						<span class="bx-estimate__value" data-bx-estimate-value>
							<?php echo esc_html( '' !== $estimate_html ? $estimate_html : __( 'پس از انتخاب برند و سایز', 'pixva' ) ); ?>
						</span>
						<span class="bx-estimate__days" data-bx-estimate-days>
							<?php
							if ( is_array( $initial ) && '' !== $initial['days'] ) {
								echo esc_html( sprintf( /* translators: %s: مدت تعمیر */ __( 'مدت تعمیر: %s', 'pixva' ), $initial['days'] ) );
							}
							?>
						</span>
					</div>

					<div class="bx-field">
						<label class="bx-field__label" for="bx-details"><?php esc_html_e( 'شرح کوتاه مشکل', 'pixva' ); ?></label>
						<textarea class="bx-textarea" id="bx-details" name="details" rows="3" placeholder="<?php esc_attr_e( 'مثلاً: تصویر نیست ولی صدا هست؛ نیمه صفحه تاریک شده…', 'pixva' ); ?>" data-bx-details required></textarea>
					</div>

					<div class="bx-field">
						<label class="bx-field__label" for="bx-phone"><?php esc_html_e( 'شماره موبایل (برای هماهنگی اعزام)', 'pixva' ); ?></label>
						<input class="bx-input" id="bx-phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" placeholder="09xxxxxxxxx" data-bx-phone required>
					</div>

					<button type="submit" class="bx-btn bx-btn--accent bx-btn--block" data-bx-submit>
						<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( (string) pixva_option( 'pixva_bento_submit', __( 'ثبت درخواست اعزام فوری', 'pixva' ) ) ); ?></span>
					</button>

					<p class="bx-msg" data-bx-booking-msg role="status" aria-live="polite"></p>

					<div class="bx-booking__foot">
						<span><?php echo pixva_bento_icons( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'ثبت بدون پیش‌پرداخت', 'pixva' ); ?></span>
						<span><?php echo pixva_bento_icons( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) pixva_option( 'pixva_working_hours', __( 'شنبه تا پنجشنبه ۹ تا ۲۰', 'pixva' ) ) ); ?></span>
						<?php if ( '' !== $phone ) : ?>
							<span><?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( function_exists( 'pixva_tel_href' ) ? pixva_tel_href( $phone ) : 'tel:' . $phone ); ?>"><?php echo esc_html( $fa( $phone ) ); ?></a></span>
						<?php endif; ?>
					</div>
				</form>
			</div>

			<!-- تب ۲: استعلام قیمت فوری از نرخ‌نامه -->
			<div class="bx-panel" role="tabpanel" id="bx-panel-price" aria-labelledby="bx-tab-price" data-bx-panel="price" hidden>
				<div class="bx-form-row">
					<div class="bx-field">
						<label class="bx-field__label" for="bx-q-brand"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
						<select class="bx-select" id="bx-q-brand" data-bx-quote-brand>
							<?php foreach ( $brands as $key => $brand ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $brand['fa'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="bx-field">
						<label class="bx-field__label" for="bx-q-size"><?php esc_html_e( 'سایز (اینچ)', 'pixva' ); ?></label>
						<select class="bx-select" id="bx-q-size" data-bx-quote-size>
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>"><?php echo esc_html( $fa( (string) $size ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="bx-price-tablewrap">
					<table class="bx-price-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
								<th scope="col"><?php esc_html_e( 'بازه قیمت (تومان)', 'pixva' ); ?></th>
								<th scope="col"><?php esc_html_e( 'مدت', 'pixva' ); ?></th>
							</tr>
						</thead>
						<tbody data-bx-quote-rows></tbody>
					</table>
				</div>
				<p class="bx-msg" data-bx-quote-msg role="status" aria-live="polite"></p>
			</div>

			<!-- تب ۳: پیگیری پرونده و استعلام گارانتی -->
			<div class="bx-panel" role="tabpanel" id="bx-panel-track" aria-labelledby="bx-tab-track" data-bx-panel="track" hidden>
				<form class="bx-lookup__form" data-bx-lookup-form="hero" novalidate>
					<div class="bx-form-row">
						<div class="bx-field">
							<label class="bx-field__label" for="bx-track-code"><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></label>
							<input class="bx-input" id="bx-track-code" name="code" type="text" dir="ltr" autocomplete="off" placeholder="PX-XXXXX" data-bx-track-code required>
						</div>
						<div class="bx-field">
							<label class="bx-field__label" for="bx-track-phone"><?php esc_html_e( 'شماره همراه پرونده', 'pixva' ); ?></label>
							<input class="bx-input" id="bx-track-phone" name="phone" type="tel" inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09xxxxxxxxx" data-bx-track-phone required>
						</div>
					</div>
					<button type="submit" class="bx-btn bx-btn--primary bx-btn--block">
						<?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'استعلام وضعیت و گارانتی', 'pixva' ); ?></span>
					</button>
					<p class="bx-msg" data-bx-lookup-msg role="status" aria-live="polite"></p>
					<div class="bx-lookup__result" data-bx-lookup-result></div>
				</form>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_hero' ) ) {
	/**
	 * هیروی مرکزی پرقدرت: بج متحرک، تیتر غول‌پیکر، نشان‌های شناور و موتور رزرو.
	 *
	 * @return void
	 */
	function pixva_bento_hero() {
		$title = (string) pixva_option( 'pixva_hero_title', __( 'تعمیر فوق‌تخصصی تلویزیون، در محل شما', 'pixva' ) );
		$lead  = (string) pixva_option(
			'pixva_hero_lead',
			__( 'پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک و هولوگرام اصالت؛ عیب‌یابی شفاف، نرخ مصوب و گارانتی کتبی — بدون جابه‌جایی دستگاه.', 'pixva' )
		);
		$badge = (string) pixva_option( 'pixva_hero_badge', __( 'مرکز فوق‌تخصصی خدمات و تعمیرات', 'pixva' ) );

		$cta_label = (string) pixva_option( 'pixva_hero_cta_label', __( 'شروع رزرو فوری', 'pixva' ) );
		$cta_link  = (string) pixva_option( 'pixva_hero_cta_link', '#booking' );
		$phone     = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
		?>
		<section class="bx-hero" id="hero" data-bx-hero>
			<div class="bx-wrap bx-hero__inner">
				<span class="bx-hero__badge">
					<span class="bx-badge-dot" aria-hidden="true"></span>
					<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $badge ); ?>
				</span>

				<h1 class="bx-hero__title"><?php echo wp_kses_post( $title ); ?></h1>
				<p class="bx-hero__lead"><?php echo esc_html( $lead ); ?></p>

				<ul class="bx-microtrust">
					<?php foreach ( pixva_bento_micro_trust() as $item ) : ?>
						<li class="bx-microtrust__item <?php echo esc_attr( $item['tone'] ); ?>">
							<?php echo pixva_bento_icons( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( $item['text'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="bx-hero__actions">
					<a class="bx-btn bx-btn--primary" href="<?php echo pixva_bento_url( $cta_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
						<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $cta_label ); ?></span>
					</a>
					<?php if ( '' !== $phone ) : ?>
						<a class="bx-btn bx-btn--ghost" href="<?php echo esc_url( function_exists( 'pixva_tel_href' ) ? pixva_tel_href( $phone ) : 'tel:' . $phone ); ?>">
							<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( (string) pixva_option( 'pixva_hero_call_label', __( 'تماس با کارشناس', 'pixva' ) ) ); ?></span>
						</a>
					<?php endif; ?>
				</div>

				<?php pixva_bento_booking(); ?>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_fault_cell' ) ) {
	/**
	 * بنتو ۱ (۸ ستون): انتخابگر عیب با تشخیص زنده و اقدام رزرو.
	 *
	 * @return void
	 */
	function pixva_bento_fault_cell() {
		$items = function_exists( 'pixva_symptom_guide_items' ) ? (array) pixva_symptom_guide_items() : array();
		if ( empty( $items ) ) {
			return;
		}
		$items = array_slice( $items, 0, 6 );
		?>
		<article class="bx-cell bx-cell--fault bx-col-8 bx-reveal" id="fault-explorer" data-bx-fault>
			<header class="bx-cell__head">
				<span class="bx-cell__icon"><?php echo pixva_bento_icons( 'radar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<p class="bx-eyebrow"><?php esc_html_e( 'عیب‌یابی هوشمند', 'pixva' ); ?></p>
					<h2 class="bx-cell__title"><?php echo esc_html( (string) pixva_option( 'pixva_bento_fault_title', __( 'نشانه را انتخاب کنید؛ تشخیص فنی را ببینید', 'pixva' ) ) ); ?></h2>
					<p class="bx-cell__lead"><?php esc_html_e( 'روی هر نشانه بزنید تا علت احتمالی، تست‌های خانگی و برآورد اعزام نمایش داده شود.', 'pixva' ); ?></p>
				</div>
			</header>

			<div class="bx-fault-chips" role="listbox" aria-label="<?php esc_attr_e( 'نشانه‌های خرابی', 'pixva' ); ?>" data-bx-fault-chips>
				<?php foreach ( $items as $index => $item ) : ?>
					<button type="button" class="bx-fault-chip<?php echo 0 === $index ? ' is-active' : ''; ?>" role="option"
						aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
						data-bx-fault-chip="<?php echo esc_attr( $item['id'] ); ?>">
						<?php echo pixva_bento_icons( isset( $item['icon'] ) ? $item['icon'] : 'sparkle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $item['title'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $items as $index => $item ) : ?>
				<article class="bx-fault-view" data-bx-fault-view="<?php echo esc_attr( $item['id'] ); ?>" <?php echo 0 === $index ? '' : 'hidden'; ?>>
					<h3 class="bx-fault-view__title"><?php echo esc_html( $item['question'] ); ?></h3>
					<span class="bx-fault-view__cause">
						<?php echo pixva_bento_icons( 'wrench' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo esc_html( $item['cause'] ); ?>
					</span>
					<p class="bx-fault-view__body"><?php echo esc_html( $item['body'] ); ?></p>
					<ul class="bx-fault-checks">
						<?php foreach ( (array) $item['checks'] as $check ) : ?>
							<li><?php echo esc_html( $check ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="bx-fault-warning"><?php echo esc_html( $item['warning'] ); ?></p>
					<div class="bx-fault-actions">
						<button type="button" class="bx-btn bx-btn--accent bx-btn--sm" data-bx-fault-book="<?php echo esc_attr( $item['title'] ); ?>">
							<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'رزرو تعمیر این ایراد', 'pixva' ); ?></span>
						</button>
					</div>
				</article>
			<?php endforeach; ?>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_dispatch_cell' ) ) {
	/**
	 * بنتو ۲ (۴ ستون): رهگیر زنده اعزام تکنسین (دمو از REST واقعی dispatch-live).
	 *
	 * @return void
	 */
	function pixva_bento_dispatch_cell() {
		?>
		<article class="bx-cell bx-cell--dispatch bx-cell--dark bx-col-4 bx-reveal" id="dispatch-tracker" data-bx-dispatch>
			<header class="bx-cell__head">
				<span class="bx-cell__icon"><?php echo pixva_bento_icons( 'radar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<p class="bx-eyebrow bx-eyebrow--dark"><?php esc_html_e( 'اعزام زنده', 'pixva' ); ?></p>
					<h2 class="bx-cell__title"><?php echo esc_html( (string) pixva_option( 'pixva_bento_dispatch_title', __( 'لحظه‌به‌لحظه تا رسیدن تکنسین', 'pixva' ) ) ); ?></h2>
				</div>
				<span class="bx-live-dot"><?php esc_html_e( 'زنده', 'pixva' ); ?></span>
			</header>

			<div class="bx-dispatch__radar" aria-hidden="true">
				<span class="bx-dispatch__tech"><?php echo pixva_bento_icons( 'tech' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>

			<p class="bx-dispatch__eta">
				<b data-bx-dispatch-eta>—</b>
				<span><?php esc_html_e( 'دقیقه تا رسیدن', 'pixva' ); ?></span>
			</p>

			<div class="bx-track">
				<span class="bx-track__bar"><i data-bx-dispatch-progress></i></span>
				<span class="bx-track__meta">
					<span class="bx-track__status" data-bx-dispatch-status><?php esc_html_e( 'در حال اتصال…', 'pixva' ); ?></span>
					<span data-bx-dispatch-updated></span>
				</span>
			</div>

			<div class="bx-dispatch__who">
				<b data-bx-dispatch-tech><?php esc_html_e( 'تکنسین شیفت', 'pixva' ); ?></b>
				<span data-bx-dispatch-skill></span>
			</div>
			<p class="bx-dispatch__note"><?php esc_html_e( 'نمایش زنده سامانه اعزام (حالت دمو) — برای پرونده واقعی از تب پیگیری استفاده کنید.', 'pixva' ); ?></p>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_warranty_cell' ) ) {
	/**
	 * بنتو ۳ (۴ ستون): ابزار استعلام گارانتی با کد پیگیری + شماره همراه.
	 *
	 * @return void
	 */
	function pixva_bento_warranty_cell() {
		?>
		<article class="bx-cell bx-cell--warranty bx-col-4 bx-reveal" id="warranty-lookup" data-bx-warranty>
			<header class="bx-cell__head">
				<span class="bx-cell__icon"><?php echo pixva_bento_icons( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<p class="bx-eyebrow"><?php esc_html_e( 'گارانتی دیجیتال', 'pixva' ); ?></p>
					<h2 class="bx-cell__title"><?php echo esc_html( (string) pixva_option( 'pixva_bento_warranty_title', __( 'استعلام اعتبار گارانتی', 'pixva' ) ) ); ?></h2>
					<p class="bx-cell__lead"><?php esc_html_e( 'کد پیگیری و شماره همراه پرونده را وارد کنید تا وضعیت و اعتبار گارانتی نمایش داده شود.', 'pixva' ); ?></p>
				</div>
			</header>

			<form class="bx-lookup__form" data-bx-lookup-form="cell" novalidate>
				<div class="bx-field">
					<label class="bx-field__label" for="bx-w-code"><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></label>
					<input class="bx-input" id="bx-w-code" name="code" type="text" dir="ltr" autocomplete="off" placeholder="PX-XXXXX" data-bx-track-code required>
				</div>
				<div class="bx-field">
					<label class="bx-field__label" for="bx-w-phone"><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></label>
					<input class="bx-input" id="bx-w-phone" name="phone" type="tel" inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09xxxxxxxxx" data-bx-track-phone required>
				</div>
				<button type="submit" class="bx-btn bx-btn--primary bx-btn--block">
					<?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'استعلام گارانتی', 'pixva' ); ?></span>
				</button>
				<p class="bx-msg" data-bx-lookup-msg role="status" aria-live="polite"></p>
				<div class="bx-lookup__result" data-bx-lookup-result></div>
			</form>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_pricing_cell' ) ) {
	/**
	 * بنتو ۴ (۸ ستون): مقایسه قیمت پویای برندها با رندر اولیه سمت سرور.
	 *
	 * @return void
	 */
	function pixva_bento_pricing_cell() {
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$sizes  = function_exists( 'pixva_price_table_sizes' ) ? (array) pixva_price_table_sizes() : array( '55' );
		$fa     = function_exists( 'pixva_fa_num' ) ? 'pixva_fa_num' : 'strval';
		if ( empty( $brands ) ) {
			return;
		}

		$brand_default = (string) pixva_option( 'pixva_bento_default_brand', (string) pixva_bento_first_key( $brands ) );
		if ( ! isset( $brands[ $brand_default ] ) ) {
			$brand_default = (string) pixva_bento_first_key( $brands );
		}
		$size_default = in_array( (string) pixva_option( 'pixva_bento_default_size', '55' ), $sizes, true ) ? (string) pixva_option( 'pixva_bento_default_size', '55' ) : (string) $sizes[0];

		$rows = function_exists( 'pixva_price_table_matrix' ) ? (array) pixva_price_table_matrix( $brand_default, $size_default ) : array();
		?>
		<article class="bx-cell bx-cell--pricing bx-col-8 bx-reveal" id="pricing-compare" data-bx-pricing
			data-brand="<?php echo esc_attr( $brand_default ); ?>" data-size="<?php echo esc_attr( $size_default ); ?>">
			<header class="bx-cell__head">
				<span class="bx-cell__icon"><?php echo pixva_bento_icons( 'chart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<p class="bx-eyebrow"><?php esc_html_e( 'نرخ‌نامه شفاف ۱۴۰۵', 'pixva' ); ?></p>
					<h2 class="bx-cell__title"><?php echo esc_html( (string) pixva_option( 'pixva_bento_pricing_title', __( 'مقایسه قیمت تعمیر به تفکیک برند', 'pixva' ) ) ); ?></h2>
					<p class="bx-cell__lead"><?php esc_html_e( 'همه مبالغ از موتور نرخ‌نامه مصوب محاسبه می‌شود؛ مبلغ نهایی پس از عیب‌یابی در محل قطعی است.', 'pixva' ); ?></p>
				</div>
			</header>

			<div class="bx-price-toolbar">
				<div class="bx-price-pills" role="group" aria-label="<?php esc_attr_e( 'انتخاب برند', 'pixva' ); ?>">
					<?php foreach ( $brands as $key => $brand ) : ?>
						<button type="button" class="bx-price-pill<?php echo $key === $brand_default ? ' is-active' : ''; ?>" data-bx-price-pill="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $key === $brand_default ? 'true' : 'false'; ?>">
							<?php echo esc_html( $brand['fa'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<label class="bx-price-size" for="bx-p-size">
					<?php esc_html_e( 'سایز:', 'pixva' ); ?>
					<select class="bx-select" id="bx-p-size" data-bx-price-size>
						<?php foreach ( $sizes as $size ) : ?>
							<option value="<?php echo esc_attr( $size ); ?>" <?php selected( (string) $size, $size_default ); ?>><?php echo esc_html( $fa( (string) $size ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="bx-price-tablewrap">
				<table class="bx-price-table">
					<caption class="bx-sr" data-bx-price-caption>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: برند، 2: سایز */
								__( 'نرخ‌نامه تعمیر تلویزیون %1$s — %2$s اینچ (تومان)', 'pixva' ),
								$brands[ $brand_default ]['fa'],
								$fa( $size_default )
							)
						);
						?>
					</caption>
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
							<th scope="col"><?php esc_html_e( 'بازه قیمت (تومان)', 'pixva' ); ?></th>
							<th scope="col"><?php esc_html_e( 'مدت تعمیر', 'pixva' ); ?></th>
							<th scope="col"><?php esc_html_e( 'یادداشت', 'pixva' ); ?></th>
						</tr>
					</thead>
					<tbody data-bx-price-rows>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
								<td class="bx-price-num">
									<?php echo esc_html( function_exists( 'pixva_price' ) && $row['min'] ? pixva_price( (int) $row['min'] ) . ' — ' . pixva_price( (int) $row['max'] ) : '—' ); ?>
								</td>
								<td><?php echo esc_html( '' !== $row['days'] ? $row['days'] : '—' ); ?></td>
								<td>
									<?php if ( ! empty( $row['panel_replacement'] ) ) : ?>
										<span class="bx-price-tag bx-price-tag--warn"><?php esc_html_e( 'پس از بازدید', 'pixva' ); ?></span>
									<?php else : ?>
										<span class="bx-price-tag"><?php esc_html_e( 'نرخ مصوب', 'pixva' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="bx-price-quote" data-bx-price-quote><?php esc_html_e( 'تعویض کامل پنل خارج از نرخ‌نامه است؛ پس از بازدید، امکان‌سنجی و قیمت اعلام می‌شود.', 'pixva' ); ?></p>
			<p class="bx-msg" data-bx-price-msg role="status" aria-live="polite"></p>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_explorer' ) ) {
	/**
	 * کاوشگر بنتو: گرید ۱۲ ستونه با چهار سلول تخصصی.
	 *
	 * @return void
	 */
	function pixva_bento_explorer() {
		?>
		<section class="bx-section bx-section--tight" id="bento" aria-label="<?php esc_attr_e( 'کاوشگر خدمات و ابزارها', 'pixva' ); ?>">
			<div class="bx-wrap">
				<div class="bx-bento" data-bx-bento>
					<?php
					pixva_bento_fault_cell();
					pixva_bento_dispatch_cell();
					pixva_bento_warranty_cell();
					pixva_bento_pricing_cell();
					?>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_brands' ) ) {
	/**
	 * نوار برندهای تحت پوشش (از کاتالوگ واقعی).
	 *
	 * @return void
	 */
	function pixva_bento_brands() {
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		if ( empty( $brands ) ) {
			return;
		}
		?>
		<section class="bx-section bx-section--tight" aria-label="<?php esc_attr_e( 'برندهای تحت پوشش', 'pixva' ); ?>">
			<div class="bx-wrap">
				<p class="bx-eyebrow bx-eyebrow--center"><?php esc_html_e( 'پوشش کامل برندها', 'pixva' ); ?></p>
				<div class="bx-brands bx-u-gap">
					<?php foreach ( $brands as $brand ) : ?>
						<span class="bx-brand-chip">
							<?php echo esc_html( $brand['fa'] ); ?>
							<small><?php echo esc_html( $brand['en'] ); ?></small>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_closer' ) ) {
	/**
	 * جمع‌بندی CTA: باند ابسیدین با درخشش گرادیانی.
	 *
	 * @return void
	 */
	function pixva_bento_closer() {
		$phone = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
		$fa    = function_exists( 'pixva_fa_num' ) ? 'pixva_fa_num' : 'strval';
		?>
		<section class="bx-closer" aria-label="<?php esc_attr_e( 'درخواست تعمیر', 'pixva' ); ?>">
			<div class="bx-wrap">
				<div class="bx-closer__card bx-reveal">
					<div>
						<p class="bx-eyebrow bx-eyebrow--dark"><?php esc_html_e( 'همین حالا شروع کنید', 'pixva' ); ?></p>
						<h2 class="bx-closer__title"><?php echo esc_html( (string) pixva_option( 'pixva_bento_closer_title', __( 'تلویزیون را امروز به کارگاه بسپارید', 'pixva' ) ) ); ?></h2>
						<p class="bx-closer__lead"><?php echo esc_html( (string) pixva_option( 'pixva_bento_closer_lead', __( 'ثبت درخواست کمتر از یک دقیقه طول می‌کشد؛ تکنسین با قطعه درست و برآورد شفاف به محل شما می‌آید.', 'pixva' ) ) ); ?></p>
					</div>
					<div class="bx-closer__actions">
						<a class="bx-btn bx-btn--accent" href="<?php echo pixva_bento_url( (string) pixva_option( 'pixva_hero_cta_link', '#booking' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
							<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( (string) pixva_option( 'pixva_hero_cta_label', __( 'شروع رزرو فوری', 'pixva' ) ) ); ?></span>
						</a>
						<?php if ( '' !== $phone ) : ?>
							<a class="bx-btn bx-btn--on-dark" href="<?php echo esc_url( function_exists( 'pixva_tel_href' ) ? pixva_tel_href( $phone ) : 'tel:' . $phone ); ?>">
								<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( $fa( $phone ) ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_bento_localize' ) ) {
	/**
	 * داده‌های درون‌خطی اسکریپت بنتو (REST، نانس و i18n).
	 *
	 * @return array<string, mixed>
	 */
	function pixva_bento_localize() {
		return array(
			'restUrl' => esc_url_raw( rest_url( 'pixva/v1' ) ),
			'nonce'   => wp_create_nonce( 'pixva_express_booking' ),
			'homeUrl' => esc_url_raw( home_url( '/' ) ),
			'i18n'    => array(
				'loading'      => __( 'در حال پردازش…', 'pixva' ),
				'updating'     => __( 'در حال به‌روزرسانی نرخ‌نامه…', 'pixva' ),
				'error'        => __( 'خطا در ارتباط با سرور؛ دوباره تلاش کنید.', 'pixva' ),
				'needPhone'    => __( 'برای هماهنگی اعزام، شماره موبایل الزامی است.', 'pixva' ),
				'badPhone'     => __( 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', 'pixva' ),
				'needDetails'  => __( 'شرح کوتاه مشکل را بنویسید (حداقل ۳ نویسه).', 'pixva' ),
				'needCode'     => __( 'کد پیگیری و شماره همراه هر دو لازم است.', 'pixva' ),
				'toman'        => __( 'تومان', 'pixva' ),
				'estimateWait' => __( 'پس از انتخاب برند و سایز', 'pixva' ),
				'daysPrefix'   => __( 'مدت تعمیر: %s', 'pixva' ),
				'minute'       => __( 'دقیقه', 'pixva' ),
				'panelQuote'   => __( 'تعویض کامل پنل خارج از نرخ‌نامه است؛ پس از بازدید، امکان‌سنجی و قیمت اعلام می‌شود.', 'pixva' ),
				'approved'     => __( 'نرخ مصوب', 'pixva' ),
				'afterVisit'   => __( 'پس از بازدید', 'pixva' ),
				'resultStatus' => __( 'وضعیت پرونده', 'pixva' ),
				'resultDevice' => __( 'دستگاه', 'pixva' ),
				'resultWarranty' => __( 'گارانتی', 'pixva' ),
				'resultEstimate' => __( 'برآورد', 'pixva' ),
				'resultCode'   => __( 'کد پیگیری', 'pixva' ),
			),
		);
	}
}
