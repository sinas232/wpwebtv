<?php
/**
 * سیمولاتور زنده تشخیص عیب (inc/fault-simulator.php)
 *
 * رندرر مشترک و بدون وابستگی به المنتور: هم هیروی صفحه اصلی و هم ویجت المنتور
 * «Pixva Fault Simulator» از همین تابع استفاده می‌کنند تا یک علامت خرابی هرگز
 * دو بار پیاده‌سازی نشود.
 *
 * قوانین داده:
 * - هیچ عدد و قیمتی hardcode نیست: وقتی «هزینه» یا «زمان» یک آیتم خالی باشد از
 *   pixva_calculate_estimate() (نرخ‌نامه سمت سرور) پر می‌شود.
 * - متن‌ها از آرگومان‌ها، تنظیمات پوسته یا فیلترها می‌آیند.
 *
 * @package Pixva
 * @since   1.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_fault_causes' ) ) {
	/**
	 * علت‌های فنی پیش‌فرض برای هر کلید خرابی (قابل تغییر با فیلتر).
	 *
	 * @return array<string, array{label:string, tag:string, icon:string, cause:string}>
	 */
	function pixva_fault_causes() {
		$catalog = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();

		$causes = array(
			'no_picture' => array(
				'label' => isset( $catalog['no_picture'] ) ? $catalog['no_picture'] : __( 'تصویر سیاه / صدا دارد', 'pixva' ),
				'tag'   => __( 'بک‌لایت', 'pixva' ),
				'icon'  => '🔴',
				'cause' => __( 'سوختن ریسه‌های LED بک‌لایت یا خرابی درایور بک‌لایت؛ گاهی برد T-CON', 'pixva' ),
			),
			'lines'      => array(
				'label' => isset( $catalog['lines'] ) ? $catalog['lines'] : __( 'خطوط عمودی و رنگی', 'pixva' ),
				'tag'   => __( 'پنل / فلت', 'pixva' ),
				'icon'  => '🟢',
				'cause' => __( 'آسیب COF و فلت‌کبل پنل یا خرابی T-CON؛ نیازمند بندینگ صنعتی', 'pixva' ),
			),
			'blink'      => array(
				'label' => isset( $catalog['blink'] ) ? $catalog['blink'] : __( 'چراغ پاور چشمک می‌زند', 'pixva' ),
				'tag'   => __( 'برد تغذیه / مین‌برد', 'pixva' ),
				'icon'  => '🔵',
				'cause' => __( 'نوسان یا قطعی برد پاور، خازن‌های بادکرده یا خطای محافظت مین‌برد', 'pixva' ),
			),
			'no_sound'   => array(
				'label' => isset( $catalog['no_sound'] ) ? $catalog['no_sound'] : __( 'قطع صدا', 'pixva' ),
				'tag'   => __( 'برد صدا / اسپیکر', 'pixva' ),
				'icon'  => '🟠',
				'cause' => __( 'خرابی برد صدا، فلت اسپیکر یا مسیر صوتی پنل', 'pixva' ),
			),
			'water'      => array(
				'label' => isset( $catalog['water'] ) ? $catalog['water'] : __( 'آب‌خوردگی پنل', 'pixva' ),
				'tag'   => __( 'نفوذ مایع', 'pixva' ),
				'icon'  => '🟣',
				'cause' => __( 'اکسیدشدن مسیرهای برد و فلت پس از نفوذ مایع؛ نیاز به شست‌وشوی تخصصی', 'pixva' ),
			),
			'no_power'   => array(
				'label' => isset( $catalog['no_power'] ) ? $catalog['no_power'] : __( 'خاموشی کامل', 'pixva' ),
				'tag'   => __( 'پاور / مین‌برد', 'pixva' ),
				'icon'  => '⚫',
				'cause' => __( 'قطعی کامل برد تغذیه، فیوز، یا مین‌برد و مسیر استندبای', 'pixva' ),
			),
		);

		/**
		 * فیلتر علت‌های پیش‌فرض سیمولاتور.
		 *
		 * @param array $causes علت‌ها بر پایه کلید خدمت.
		 */
		return apply_filters( 'pixva_fault_causes', $causes );
	}
}

if ( ! function_exists( 'pixva_fault_simulator_items' ) ) {
	/**
	 * ساخت آیتم‌های پیش‌فرض سیمولاتور از نرخ‌نامه سمت سرور.
	 *
	 * @param array $pricing آرایه brand/tech/size برای نمونه‌گیری قیمت.
	 * @return array<int, array<string, string>>
	 */
	function pixva_fault_simulator_items( $pricing = array() ) {
		$causes = pixva_fault_causes();
		$keys   = apply_filters( 'pixva_fault_simulator_keys', array( 'no_picture', 'lines', 'blink' ) );
		$items  = array();

		foreach ( $keys as $key ) {
			$key = sanitize_key( (string) $key );
			if ( ! isset( $causes[ $key ] ) ) {
				continue;
			}

			$items[] = array(
				'key'   => $key,
				'label' => $causes[ $key ]['label'],
				'tag'   => $causes[ $key ]['tag'],
				'icon'  => $causes[ $key ]['icon'],
				'cause' => $causes[ $key ]['cause'],
				'cost'  => '',
				'days'  => '',
			);
		}

		return pixva_fault_simulator_fill( $items, $pricing );
	}
}

if ( ! function_exists( 'pixva_fault_simulator_fill' ) ) {
	/**
	 * پرکردن هزینه و زمان آیتم‌ها از موتور نرخ‌نامه (فقط برای فیلدهای خالی).
	 *
	 * @param array $items   آیتم‌های سیمولاتور.
	 * @param array $pricing آرایه brand/tech/size.
	 * @return array<int, array<string, string>>
	 */
	function pixva_fault_simulator_fill( $items, $pricing = array() ) {
		$brand = isset( $pricing['brand'] ) ? sanitize_key( $pricing['brand'] ) : 'samsung';
		$tech  = isset( $pricing['tech'] ) ? sanitize_key( $pricing['tech'] ) : 'led';
		$size  = isset( $pricing['size'] ) ? sanitize_key( $pricing['size'] ) : '55';

		foreach ( $items as $index => $item ) {
			$problem = isset( $item['problem'] ) && '' !== $item['problem'] ? sanitize_key( $item['problem'] ) : ( isset( $item['key'] ) ? sanitize_key( $item['key'] ) : '' );
			$items[ $index ]['problem'] = $problem;

			$needs_cost = empty( $item['cost'] );
			$needs_days = empty( $item['days'] );
			if ( ! $needs_cost && ! $needs_days ) {
				continue;
			}

			$estimate = ( '' !== $problem && function_exists( 'pixva_calculate_estimate' ) )
				? pixva_calculate_estimate( $brand, $tech, $size, $problem )
				: null;

			if ( ! is_array( $estimate ) ) {
				if ( $needs_cost ) {
					$items[ $index ]['cost'] = __( 'پس از عیب‌یابی رایگان', 'pixva' );
				}
				if ( $needs_days ) {
					$items[ $index ]['days'] = __( '۲ تا ۴ روز کاری', 'pixva' );
				}
				continue;
			}

			if ( ! empty( $estimate['panel_replacement'] ) ) {
				$items[ $index ]['cost'] = $needs_cost ? __( 'پس از بازدید کارشناس', 'pixva' ) : $items[ $index ]['cost'];
				$items[ $index ]['days'] = $needs_days ? __( 'پس از عیب‌یابی', 'pixva' ) : $items[ $index ]['days'];
				continue;
			}

			if ( $needs_cost ) {
				$items[ $index ]['cost'] = sprintf(
					/* translators: 1: minimum price, 2: maximum price. */
					__( '%1$s تا %2$s تومان', 'pixva' ),
					pixva_price( $estimate['min'] ),
					pixva_price( $estimate['max'] )
				);
			}

			if ( $needs_days ) {
				$items[ $index ]['days'] = '' !== $estimate['days'] ? pixva_fa_num( (string) $estimate['days'] ) : __( '۲ تا ۴ روز کاری', 'pixva' );
			}
		}

		return $items;
	}
}

if ( ! function_exists( 'pixva_fault_simulator_defaults' ) ) {
	/**
	 * آرگومان‌های پیش‌فرض سیمولاتور از تنظیمات پوسته.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_fault_simulator_defaults() {
		$pricing = apply_filters(
			'pixva_hero_simulator_args',
			array(
				'brand' => (string) pixva_option( 'pixva_simulator_brand', 'samsung' ),
				'tech'  => (string) pixva_option( 'pixva_simulator_tech', 'led' ),
				'size'  => (string) pixva_option( 'pixva_simulator_size', '55' ),
			)
		);

		$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
		$pick   = static function ( $group, $key ) use ( $labels ) {
			return isset( $labels[ $group ][ $key ] ) ? $labels[ $group ][ $key ] : $key;
		};

		return array(
			'pricing'     => $pricing,
			'items'       => pixva_fault_simulator_items( $pricing ),
			'badge'       => (string) pixva_option( 'pixva_simulator_badge', __( 'سیمولاتور زنده تشخیص عیب', 'pixva' ) ),
			'title'       => '',
			'subtitle'    => '',
			'cta_text'    => (string) pixva_option( 'pixva_simulator_cta_text', __( 'ثبت درخواست تعمیر این ایراد', 'pixva' ) ),
			'cta_url'     => (string) pixva_option( 'pixva_simulator_cta_url', pixva_page_url( 'calculator' ) ),
			'calc_target' => '#quick-calc',
			'wa_enabled'  => (bool) pixva_option( 'pixva_simulator_whatsapp', true ),
			'wa_text'     => '',
			'wa_label'    => (string) pixva_option( 'pixva_simulator_wa_text', __( 'مشاوره فوری در واتساپ', 'pixva' ) ),
			'glow'        => '',
			'glow_strength' => 0.45,
			'frame_color' => '',
			'frame_border' => '',
			'frame_image' => '',
			'radius'      => 20,
			'scan'        => true,
			'stand'       => true,
			'swipe'       => true,
			'dots'        => true,
			'variant'     => 'hero',
			'id'          => '',
			'note'        => sprintf(
				/* translators: 1: brand, 2: technology, 3: size. */
				__( 'نمونه محاسبه: %1$s %2$s %3$s — قیمت نهایی پس از عیب‌یابی رایگان تأیید شما می‌رسد.', 'pixva' ),
				$pick( 'brand', $pricing['brand'] ),
				$pick( 'tech', $pricing['tech'] ),
				$pick( 'size', $pricing['size'] )
			),
		);
	}
}

if ( ! function_exists( 'pixva_simulator_media_url' ) ) {
	/**
	 * تبدیل کنترل مدیای المنتور (یا آدرس خام) به URL قابل نمایش.
	 *
	 * @param mixed $media مقدار کنترل مدیا.
	 * @return string
	 */
	function pixva_simulator_media_url( $media ) {
		if ( is_array( $media ) ) {
			if ( ! empty( $media['id'] ) ) {
				$url = wp_get_attachment_image_url( (int) $media['id'], 'large' );
				if ( $url ) {
					return (string) $url;
				}
			}
			if ( ! empty( $media['url'] ) ) {
				return (string) $media['url'];
			}
			return '';
		}

		return is_string( $media ) ? $media : '';
	}
}

if ( ! function_exists( 'pixva_render_fault_simulator' ) ) {
	/**
	 * رندر سیمولاتور زنده تشخیص عیب.
	 *
	 * @param array $args آرگومان‌ها (items, badge, cta_text, cta_url, glow, frame_image, …).
	 * @return void
	 */
	function pixva_render_fault_simulator( $args = array() ) {
		$args = wp_parse_args( $args, pixva_fault_simulator_defaults() );

		$items = array_values( array_filter( (array) $args['items'], 'is_array' ) );
		if ( empty( $items ) ) {
			return;
		}
		$items = pixva_fault_simulator_fill( $items, (array) $args['pricing'] );

		static $instance = 0;
		++$instance;

		$variant   = 'widget' === $args['variant'] ? 'widget' : 'hero';
		$root_id   = '' !== trim( (string) $args['id'] ) ? sanitize_html_class( $args['id'] ) : 'pixva-sim-' . $instance;
		$pricing   = (array) $args['pricing'];
		$wa_number = preg_replace( '/[^0-9]/', '', (string) pixva_option( 'pixva_whatsapp_number', '989120000000' ) );
		$first     = $items[0];

		$inline = array();
		if ( ! empty( $args['glow'] ) ) {
			$inline[] = '--sim-glow:' . $args['glow'];
		}
		if ( isset( $args['glow_strength'] ) && '' !== $args['glow_strength'] ) {
			$inline[] = '--sim-glow-strength:' . round( (float) $args['glow_strength'], 2 );
		}
		if ( ! empty( $args['frame_color'] ) ) {
			$inline[] = '--sim-frame:' . $args['frame_color'];
		}
		if ( ! empty( $args['frame_border'] ) ) {
			$inline[] = '--sim-frame-line:' . $args['frame_border'];
		}
		if ( ! empty( $args['radius'] ) ) {
			$inline[] = '--sim-radius:' . (int) $args['radius'] . 'px';
		}
		$style = $inline ? ' style="' . esc_attr( implode( ';', $inline ) . ';' ) . '"' : '';

		$frame_image = pixva_simulator_media_url( isset( $args['frame_image'] ) ? $args['frame_image'] : '' );
		$swipe       = empty( $args['swipe'] ) ? '0' : '1';
		?>
		<div class="pixva-sim pixva-sim--<?php echo esc_attr( $variant ); ?>" id="<?php echo esc_attr( $root_id ); ?>"
			data-pixva-simulator
			data-calc-target="<?php echo esc_attr( (string) $args['calc_target'] ); ?>"
			data-order-url="<?php echo esc_url( (string) $args['cta_url'] ); ?>"
			data-sim-brand="<?php echo esc_attr( $pricing['brand'] ); ?>"
			data-sim-tech="<?php echo esc_attr( $pricing['tech'] ); ?>"
			data-sim-size="<?php echo esc_attr( $pricing['size'] ); ?>"
			data-sim-swipe="<?php echo esc_attr( $swipe ); ?>"
			data-sim-dots="<?php echo empty( $args['dots'] ) ? '0' : '1'; ?>"
			data-wa-number="<?php echo esc_attr( $wa_number ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS vars escaped above. ?>>

			<?php if ( '' !== trim( (string) $args['badge'] ) ) : ?>
				<div class="pixva-sim__badge">
					<span class="pixva-sim__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( (string) $args['badge'] ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $args['title'] ) ) : ?>
				<div class="pixva-sim__head">
					<h3 class="pixva-sim__title"><?php echo esc_html( (string) $args['title'] ); ?></h3>
					<?php if ( '' !== trim( (string) $args['subtitle'] ) ) : ?>
						<p class="pixva-sim__subtitle"><?php echo esc_html( (string) $args['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="pixva-sim__tv">
				<span class="pixva-sim__glow" aria-hidden="true"></span>
				<?php if ( $frame_image ) : ?>
					<img class="pixva-sim__frame-img" src="<?php echo esc_url( $frame_image ); ?>" alt="" loading="lazy" decoding="async">
				<?php endif; ?>

				<div class="pixva-sim__bezel">
					<div class="pixva-sim__screen"
						data-sim-screen="<?php echo esc_attr( (string) $first['key'] ); ?>"
						data-sim-mode="<?php echo ! empty( $first['media'] ) ? 'media' : 'fault'; ?>"
						role="img"
						aria-label="<?php echo esc_attr( sprintf( __( 'شبیه‌سازی %s', 'pixva' ), (string) $first['label'] ) ); ?>">
						<span class="pixva-sim__picture" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
						<?php if ( ! isset( $args['scan'] ) || ! empty( $args['scan'] ) ) : ?>
							<span class="pixva-sim__scan" aria-hidden="true"></span>
						<?php endif; ?>
						<img class="pixva-sim__shot" data-sim-shot src="<?php echo esc_url( pixva_simulator_media_url( isset( $first['media'] ) ? $first['media'] : '' ) ); ?>" alt="" loading="lazy" decoding="async">
						<span class="pixva-sim__fault pixva-sim__fault--backlight" data-sim-fault="no_picture" aria-hidden="true"></span>
						<span class="pixva-sim__fault pixva-sim__fault--lines" data-sim-fault="lines" aria-hidden="true"></span>
						<span class="pixva-sim__fault pixva-sim__fault--blink" data-sim-fault="blink" aria-hidden="true"></span>

						<div class="pixva-sim__hud">
							<span class="pixva-sim__hud-tag"><?php echo esc_html( (string) $first['tag'] ); ?></span>
							<strong class="pixva-sim__hud-title"><?php echo esc_html( (string) $first['label'] ); ?></strong>
						</div>
					</div>
				</div>
				<?php if ( ! isset( $args['stand'] ) || ! empty( $args['stand'] ) ) : ?>
					<span class="pixva-sim__stand" aria-hidden="true"></span>
				<?php endif; ?>
			</div>

			<div class="pixva-sim__symptoms" role="group" aria-label="<?php esc_attr_e( 'علامت خرابی تلویزیون را انتخاب کنید', 'pixva' ); ?>" data-sim-track>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$key   = isset( $item['key'] ) && '' !== $item['key'] ? sanitize_key( $item['key'] ) : 'item_' . $index;
					$icon  = pixva_simulator_media_url( isset( $item['icon'] ) ? $item['icon'] : '' );
					$emoji = isset( $item['emoji'] ) ? (string) $item['emoji'] : ( isset( $item['icon_text'] ) ? (string) $item['icon_text'] : '' );
					$media = pixva_simulator_media_url( isset( $item['media'] ) ? $item['media'] : '' );
					$cta   = isset( $item['cta_url'] ) && '' !== $item['cta_url'] ? (string) $item['cta_url'] : '';
					?>
					<button type="button"
						class="pixva-sim__symptom<?php echo 0 === $index ? ' is-active' : ''; ?>"
						<?php echo ! empty( $item['accent'] ) ? 'style="--sim-accent:' . esc_attr( $item['accent'] ) . '"' : ''; ?>
						data-sim-symptom="<?php echo esc_attr( $key ); ?>"
						data-sim-tag="<?php echo esc_attr( isset( $item['tag'] ) ? (string) $item['tag'] : '' ); ?>"
						data-sim-label="<?php echo esc_attr( isset( $item['label'] ) ? (string) $item['label'] : '' ); ?>"
						data-sim-cause="<?php echo esc_attr( isset( $item['cause'] ) ? (string) $item['cause'] : '' ); ?>"
						data-sim-cost="<?php echo esc_attr( isset( $item['cost'] ) ? (string) $item['cost'] : '' ); ?>"
						data-sim-days="<?php echo esc_attr( isset( $item['days'] ) ? (string) $item['days'] : '' ); ?>"
						data-sim-media="<?php echo esc_url( $media ); ?>"
						data-sim-problem="<?php echo esc_attr( isset( $item['problem'] ) ? (string) $item['problem'] : $key ); ?>"
						data-sim-cta="<?php echo esc_url( $cta ); ?>"
						aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>">
						<?php if ( $icon ) : ?>
							<img class="pixva-sim__symptom-icon pixva-sim__symptom-icon--img" src="<?php echo esc_url( $icon ); ?>" alt="" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="pixva-sim__symptom-icon" aria-hidden="true"><?php echo esc_html( $emoji ); ?></span>
						<?php endif; ?>
						<span class="pixva-sim__symptom-text">
							<strong><?php echo esc_html( isset( $item['label'] ) ? (string) $item['label'] : '' ); ?></strong>
							<small><?php echo esc_html( isset( $item['tag'] ) ? (string) $item['tag'] : '' ); ?></small>
						</span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $args['dots'] ) ) : ?>
				<div class="pixva-sim__dots" data-sim-dots aria-hidden="true"></div>
			<?php endif; ?>

			<div class="pixva-sim__info" data-sim-info>
				<div class="pixva-sim__row">
					<span><?php echo esc_html( (string) pixva_option( 'pixva_simulator_cause_label', __( 'علت احتمالی', 'pixva' ) ) ); ?></span>
					<strong data-sim-cause><?php echo esc_html( (string) $first['cause'] ); ?></strong>
				</div>
				<div class="pixva-sim__row">
					<span><?php echo esc_html( (string) pixva_option( 'pixva_simulator_cost_label', __( 'حدود هزینه', 'pixva' ) ) ); ?></span>
					<strong data-sim-cost><?php echo esc_html( (string) $first['cost'] ); ?></strong>
				</div>
				<div class="pixva-sim__row">
					<span><?php echo esc_html( (string) pixva_option( 'pixva_simulator_time_label', __( 'زمان تحویل', 'pixva' ) ) ); ?></span>
					<strong data-sim-time><?php echo esc_html( (string) $first['days'] ); ?></strong>
				</div>
			</div>

			<div class="pixva-sim__cta">
				<a class="pixva-btn pixva-btn--gradient" href="<?php echo esc_url( (string) $args['cta_url'] ); ?>" data-sim-order>
					<?php echo esc_html( (string) $args['cta_text'] ); ?>
				</a>

				<?php if ( ! empty( $args['wa_enabled'] ) && '' !== $wa_number ) : ?>
					<?php
					$wa_text = '' !== trim( (string) $args['wa_text'] )
						? (string) $args['wa_text']
						: sprintf(
							/* translators: 1: fault label, 2: fault tag. */
							__( 'سلام، تلویزیون من علامت «%1$s» دارد (%2$s). برای اعزام کارشناس و برآورد هزینه راهنمایی می‌خواهم.', 'pixva' ),
							(string) $first['label'],
							(string) $first['tag']
						);
					?>
					<a class="pixva-btn pixva-btn--ghost-dark pixva-sim__wa" href="<?php echo esc_url( pixva_whatsapp_url( $wa_text ) ); ?>" data-sim-wa target="_blank" rel="noopener noreferrer">
						<?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( '' !== trim( (string) $args['wa_label'] ) ? (string) $args['wa_label'] : (string) pixva_option( 'pixva_simulator_wa_text', __( 'مشاوره فوری در واتساپ', 'pixva' ) ) ); ?></span>
					</a>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) $args['note'] ) ) : ?>
					<p class="pixva-sim__cta-note"><?php echo esc_html( (string) $args['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
