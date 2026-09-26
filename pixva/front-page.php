<?php
/**
 * صفحه اصلی پیکسوا
 *
 * ترتیب و نمایش سکشن‌ها از سفارشی‌ساز خوانده می‌شود.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	foreach ( pixva_active_home_sections() as $pixva_section ) {
		$pixva_callback = 'pixva_home_' . $pixva_section;
		if ( function_exists( $pixva_callback ) ) {
			call_user_func( $pixva_callback );
		}
	}
	?>
</main>
<?php
get_footer();

/**
 * هیروی صفحه اصلی (v25.1): پس‌زمینه روشن و خوانا + سیمولاتور زنده تشخیص عیب.
 *
 * ماکت ایستای «تست نهایی کارگاه / الگوی RGB» حذف و با سیمولاتور تعاملی جایگزین شد:
 * سه علامت خرابی پرکاربرد (بک‌لایت، پنل/فلت، برد پاور) که تصویر مانیتور، علت
 * احتمالی، حدود هزینه و زمان تحویل را بر پایه فرمول نرخ‌نامه سمت سرور نشان می‌دهد.
 *
 * @return void
 */
function pixva_home_hero() {
	$title = (string) pixva_option( 'pixva_hero_title', __( 'تعمیر تخصصی تلویزیون با گارانتی کتبی ۱۸۰ روزه', 'pixva' ) );
	$lead  = (string) pixva_option(
		'pixva_hero_subtitle',
		__( 'کارگاه مرکزی پیکسوا در پاساژ علاءالدین؛ تعمیر پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک، دستگاه بندینگ صنعتی و تست نهایی با الگوهای کالیبراسیون.', 'pixva' )
	);
	$eta   = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
	$hours = isset( $eta['hub_eta_hours'] ) ? $eta['hub_eta_hours'] : '۲ ساعت';

	// سیمولاتور زنده تشخیص عیب: سه علامت پرکاربرد با داده‌های واقعی نرخ‌نامه.
	$sim_args = apply_filters(
		'pixva_hero_simulator_args',
		array(
			'brand' => 'samsung',
			'tech'  => 'led',
			'size'  => '55',
		)
	);

	$sim_symptoms = array(
		array(
			'key'   => 'no_picture',
			'dot'   => 'red',
			'icon'  => '🔴',
			'label' => __( 'تصویر سیاه / صدا دارد', 'pixva' ),
			'tag'   => __( 'بک‌لایت', 'pixva' ),
			'cause' => __( 'سوختن ریسه‌های LED بک‌لایت یا خرابی درایور بک‌لایت؛ گاهی برد T-CON', 'pixva' ),
		),
		array(
			'key'   => 'lines',
			'dot'   => 'green',
			'icon'  => '🟢',
			'label' => __( 'خطوط عمودی و رنگی', 'pixva' ),
			'tag'   => __( 'پنل / فلت', 'pixva' ),
			'cause' => __( 'آسیب COF و فلت‌کبل پنل یا خرابی T-CON؛ نیازمند بندینگ صنعتی', 'pixva' ),
		),
		array(
			'key'   => 'blink',
			'dot'   => 'blue',
			'icon'  => '🔵',
			'label' => __( 'چراغ پاور چشمک می‌زند', 'pixva' ),
			'tag'   => __( 'برد تغذیه / مین‌برد', 'pixva' ),
			'cause' => __( 'نوسان یا قطعی برد پاور، خازن‌های بادکرده یا خطای محافظت مین‌برد', 'pixva' ),
		),
	);

	$sim_labels  = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
	$sim_pick    = function ( $group, $key ) use ( $sim_labels ) {
		return isset( $sim_labels[ $group ][ $key ] ) ? $sim_labels[ $group ][ $key ] : $key;
	};
	$sim_items   = array();

	foreach ( $sim_symptoms as $symptom ) {
		$estimate = pixva_calculate_estimate( $sim_args['brand'], $sim_args['tech'], $sim_args['size'], $symptom['key'] );

		if ( ! is_array( $estimate ) ) {
			continue;
		}

		if ( ! empty( $estimate['panel_replacement'] ) ) {
			$cost = __( 'پس از بازدید کارشناس', 'pixva' );
			$days = __( 'پس از عیب‌یابی', 'pixva' );
		} else {
			$cost = sprintf(
				/* translators: 1: minimum price, 2: maximum price. */
				__( '%1$s تا %2$s تومان', 'pixva' ),
				pixva_price( $estimate['min'] ),
				pixva_price( $estimate['max'] )
			);
			$days = '' !== $estimate['days'] ? pixva_fa_num( (string) $estimate['days'] ) : __( '۲ تا ۴ روز کاری', 'pixva' );
		}

		$sim_items[] = array(
			'key'   => $symptom['key'],
			'emoji' => $symptom['icon'],
			'label' => $symptom['label'],
			'tag'   => $symptom['tag'],
			'cause' => $symptom['cause'],
			'cost'  => $cost,
			'days'  => $days,
		);
	}

	$sim_sample = sprintf(
		/* translators: 1: brand label, 2: technology label, 3: screen size label. */
		__( 'نمونه محاسبه: %1$s %2$s %3$s', 'pixva' ),
		$sim_pick( 'brand', $sim_args['brand'] ),
		$sim_pick( 'tech', $sim_args['tech'] ),
		$sim_pick( 'size', $sim_args['size'] )
	);

	// مسیر ثبت درخواست: جادوگر همین صفحه (با JS) و برگه محاسبه‌گر (بدون JS).
	$order_url = pixva_page_url( 'calculator' );
	$wa_number = preg_replace( '/[^0-9]/', '', (string) pixva_option( 'pixva_whatsapp_number', '989120000000' ) );
	$first     = isset( $sim_items[0] ) ? $sim_items[0] : array();
	$wa_text   = sprintf(
		/* translators: 1: fault symptom, 2: probable cause. */
		__( 'سلام، تلویزیون من علامت «%1$s» دارد (%2$s). برای اعزام کارشناس و برآورد هزینه راهنمایی می‌خواهم.', 'pixva' ),
		isset( $first['label'] ) ? $first['label'] : '',
		isset( $first['tag'] ) ? $first['tag'] : ''
	);
	?>
	<section class="pixva-hero pixva-hero--light pixva-hero--simulator" id="hero">
		<div class="pixva-hero__aurora" aria-hidden="true">
			<span class="pixva-aurora-blob pixva-aurora-blob--a"></span>
			<span class="pixva-aurora-blob pixva-aurora-blob--b"></span>
			<span class="pixva-aurora-blob pixva-aurora-blob--c"></span>
		</div>

		<div class="pixva-container pixva-hero__grid">
			<div class="pixva-hero__content">
				<div class="pixva-hero__badges">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'مرکز تخصصی بندینگ و برد', 'pixva' ); ?></span>
					<span class="pixva-badge pixva-badge--success"><?php echo esc_html( sprintf( __( 'گارانتی کتبی %s روزه', 'pixva' ), pixva_fa_num( (string) pixva_warranty_days() ) ) ); ?></span>
					<span class="pixva-badge pixva-badge--accent"><?php esc_html_e( 'قطعه فابریک با هولوگرام اصالت', 'pixva' ); ?></span>
				</div>

				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="pixva-hero__lead"><?php echo esc_html( $lead ); ?></p>

				<div class="pixva-hero__actions">
					<a class="pixva-btn pixva-btn--orange pixva-btn--shimmer" href="#quick-calc"><?php esc_html_e( 'استعلام سریع قیمت', 'pixva' ); ?></a>
					<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_hub_url( 'ai-diagnostics' ) ); ?>"><?php esc_html_e( 'عیب‌یابی با هوش مصنوعی', 'pixva' ); ?></a>
				</div>

				<div class="pixva-stats">
					<?php foreach ( pixva_hero_stats() as $stat ) : ?>
						<div class="pixva-stat">
							<strong data-count-to="<?php echo esc_attr( (string) $stat['value'] ); ?>"<?php echo $stat['suffix'] ? ' data-count-suffix="' . esc_attr( $stat['suffix'] ) . '"' : ''; ?>>۰</strong>
							<span><?php echo esc_html( $stat['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="pixva-hero__chips pixva-hero__chips--inline">
					<span class="pixva-hero__chip pixva-hero__chip--top">
						<?php echo pixva_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( sprintf( __( 'اعزام اورژانسی زیر %s', 'pixva' ), $hours ) ); ?></span>
					</span>
					<span class="pixva-hero__chip pixva-hero__chip--bottom">
						<?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'فاکتور رسمی و رسید کتبی', 'pixva' ); ?></span>
					</span>
				</div>
			</div>

			<div class="pixva-hero__visual">
				<?php
				pixva_render_fault_simulator(
					array(
						'id'          => 'pixva-hero-simulator',
						'variant'     => 'hero',
						'items'       => $sim_items,
						'pricing'     => $sim_args,
						'badge'       => (string) pixva_option( 'pixva_simulator_badge', __( 'سیمولاتور زنده تشخیص عیب', 'pixva' ) ),
						'cta_text'    => (string) pixva_option( 'pixva_simulator_cta_text', __( 'ثبت درخواست تعمیر این ایراد', 'pixva' ) ),
						'cta_url'     => $order_url,
						'calc_target' => '#quick-calc',
						'wa_enabled'  => true,
						'wa_text'     => $wa_text,
						'note'        => $sim_sample . ' — ' . __( 'قیمت نهایی پس از عیب‌یابی رایگان تأیید شما می‌رسد.', 'pixva' ),
					)
				);
				?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * ویجت استعلام قیمت (بلافاصله پس از هیرو).
 *
 * @return void
 */
function pixva_home_quote() {
	?>
	<section class="pixva-quote-widget" id="quick-calc">
		<div class="pixva-container">
			<div class="pixva-quote-widget__card pixva-glass">
				<div class="pixva-quote-widget__head">
					<div>
						<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ); ?></span>
						<h2><?php esc_html_e( 'هزینه تعمیر را همین‌جا ببینید', 'pixva' ); ?></h2>
						<p><?php esc_html_e( 'برند، تکنولوژی، سایز و نوع خرابی را انتخاب کنید؛ بازه قیمت سمت سرور و بر پایه فرمول نرخ‌نامه محاسبه می‌شود.', 'pixva' ); ?></p>
					</div>
					<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>"><?php esc_html_e( 'جدول کامل نرخ‌نامه', 'pixva' ); ?></a>
				</div>
				<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * مسیر پنج‌مرحله‌ای تعمیر با نقطه نورانی.
 *
 * @return void
 */
function pixva_home_journey() {
	$steps = array(
		array(
			'icon'  => 'phone',
			'title' => __( 'ثبت علائم و استعلام', 'pixva' ),
			'text'  => __( 'برند، سایز و علائم را در محاسبه‌گر یا دستیار هوشمند وارد کنید تا بازه قیمت و زمان تعمیر اعلام شود.', 'pixva' ),
		),
		array(
			'icon'  => 'truck',
			'title' => __( 'اعزام پیک ضدضربه', 'pixva' ),
			'text'  => __( 'جمع‌آوری دستگاه با جعبه پددار استاندارد در تهران و صدور رسید کتبی با مهر کارگاه.', 'pixva' ),
		),
		array(
			'icon'  => 'tool',
			'title' => __( 'عیب‌یابی کارگاهی', 'pixva' ),
			'text'  => __( 'تست جداگانه مسیر تغذیه، بک‌لایت، مین‌برد و پنل؛ هزینه قطعی پیش از تعویض هر قطعه تأیید شما می‌رسد.', 'pixva' ),
		),
		array(
			'icon'  => 'bolt',
			'title' => __( 'تعمیر با قطعه فابریک', 'pixva' ),
			'text'  => __( 'تعویض دست کامل بک‌لایت، بندینگ فلت COF یا ریبال آی‌سی با دستگاه BGA و قطعه دارای هولوگرام اصالت.', 'pixva' ),
		),
		array(
			'icon'  => 'shield',
			'title' => __( 'تست نهایی و گارانتی', 'pixva' ),
			'text'  => __( 'تحویل پس از اجرای الگوهای کالیبراسیون، همراه با کارت گارانتی دیجیتال و امکان پیگیری آنلاین پرونده.', 'pixva' ),
		),
	);
	?>
	<section class="pixva-section" id="journey">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مسیر تعمیر', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از تماس تا تحویل، پنج مرحله شفاف', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'دستگاه بی‌دلیل باز نمی‌شود و هیچ قطعه‌ای بدون تأیید شما تعویض نمی‌گردد.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-journey" data-pixva-journey>
				<span class="pixva-journey__track" aria-hidden="true"><span class="pixva-journey__spark"></span></span>
				<ol class="pixva-journey__steps">
					<?php foreach ( $steps as $index => $step ) : ?>
						<li class="pixva-journey__step">
							<span class="pixva-journey__dot"><?php echo pixva_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span>
								<span class="pixva-journey__no"><?php echo esc_html( pixva_fa_num( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) ); ?></span>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<p><?php echo esc_html( $step['text'] ); ?></p>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
	</section>
	<?php
}

/**
 * محاسبه‌گر صفحه اصلی.
 *
 * @return void
 */
function pixva_home_calculator() {
	?>
	<section class="pixva-section" id="calculator">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'برآورد شفاف', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'هزینه تعمیر را قبل از آوردن دستگاه ببینید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'برند، تکنولوژی، سایز و نوع خرابی را انتخاب کنید. بازه قیمت روی سرور محاسبه می‌شود، نه داخل مرورگر.', 'pixva' ); ?></p>
			</div>
			<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
		</div>
	</section>
	<?php
}

/**
 * چهار کارت خدمت اصلی صفحه نخست.
 *
 * @return void
 */
function pixva_home_services() {
	$services = get_posts(
		array(
			'post_type'      => 'tv_services',
			'posts_per_page' => 4,
			'no_found_rows'  => true,
		)
	);
	$fallbacks = array_slice( pixva_service_fallbacks(), 0, 4 );
	?>
	<section class="pixva-section pixva-section--alt pixva-home-services" id="services">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'خدمات تخصصی', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'تعمیر همان‌جایی که خرابی است', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'پنل را بی‌دلیل تعویض نمی‌کنیم؛ اول مسیر ارزان‌تر و قابل ضمانت بررسی می‌شود.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--4">
				<?php if ( ! empty( $services ) ) : ?>
					<?php foreach ( $services as $service ) : ?>
						<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( get_permalink( $service ) ); ?>">
							<?php
							if ( has_post_thumbnail( $service ) ) {
								echo get_the_post_thumbnail( $service, 'pixva-card' );
							} else {
								echo pixva_icon( 'tool' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
							<h3><?php echo esc_html( get_the_title( $service ) ); ?></h3>
							<p><?php echo esc_html( get_the_excerpt( $service ) ); ?></p>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( $fallbacks as $service ) : ?>
						<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( add_query_arg( 'problem', $service['key'], pixva_page_url( 'calculator' ) ) ); ?>">
							<?php echo pixva_icon( $service['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h3><?php echo esc_html( $service['title'] ); ?></h3>
							<p><?php echo esc_html( $service['text'] ); ?></p>
							<span class="pixva-service-card__cta"><?php esc_html_e( 'برآورد هزینه این خدمت', 'pixva' ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * اسلایدر قبل و بعد.
 *
 * @return void
 */
function pixva_home_before_after() {
	$case   = get_posts(
		array(
			'post_type'      => 'repair_cases',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$images = array(
		'before' => PIXVA_URI . '/assets/images/panel-before.jpg',
		'after'  => PIXVA_URI . '/assets/images/panel-after.jpg',
	);
	$title  = __( 'یک پنل خط‌دار، بدون تعویض شیشه', 'pixva' );
	$text   = __( 'خط جداکننده را بکشید. تصویر چپ وضعیت پذیرش است و تصویر راست خروجی تست نهایی کارگاه.', 'pixva' );
	if ( ! empty( $case ) ) {
		$images = pixva_case_images( $case[0]->ID );
		$title  = get_the_title( $case[0] );
		$text   = get_the_excerpt( $case[0] );
	}
	?>
	<section class="pixva-section">
		<div class="pixva-container pixva-grid pixva-grid--2" style="align-items:center">
			<div>
				<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'نمونه واقعی', 'pixva' ); ?></span>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p class="pixva-muted"><?php echo esc_html( $text ); ?></p>
				<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'repair_cases' ) ); ?>"><?php esc_html_e( 'همه نمونه‌کارها', 'pixva' ); ?></a>
			</div>
			<?php pixva_render_before_after( $images['before'], $images['after'], $title ); ?>
		</div>
	</section>
	<?php
}

/**
 * شبکه برندها.
 *
 * @return void
 */
function pixva_home_brands() {
	$posts = get_posts(
		array(
			'post_type'      => 'tv_brands',
			'posts_per_page' => 8,
			'no_found_rows'  => true,
		)
	);
	?>
	<section class="pixva-section pixva-section--alt">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'برندها', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از سامسونگ و سونی تا اسنوا و جی‌پلاس', 'pixva' ); ?></h2>
			</div>
			<div class="pixva-grid pixva-grid--4">
				<?php if ( ! empty( $posts ) ) : ?>
					<?php foreach ( $posts as $brand ) : ?>
						<a class="pixva-card pixva-brand-tile" href="<?php echo esc_url( get_permalink( $brand ) ); ?>">
							<strong><?php echo esc_html( get_the_title( $brand ) ); ?></strong>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( pixva_brand_catalog() as $key => $brand ) : ?>
						<a class="pixva-card pixva-brand-tile" href="<?php echo esc_url( add_query_arg( 'brand', $key, pixva_page_url( 'calculator' ) ) ); ?>">
							<span class="pixva-latin pixva-brand-tile__en"><?php echo esc_html( $brand['en'] ); ?></span>
							<span><?php echo esc_html( $brand['fa'] ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * روایت مشتریان. بدون اسکیما Review.
 *
 * @return void
 */
function pixva_home_testimonials() {
	?>
	<section class="pixva-section">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'از زبان مشتری', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'تعمیر را با نتیجه می‌سنجیم', 'pixva' ); ?></h2>
			</div>
			<div class="pixva-grid pixva-grid--3">
				<?php foreach ( pixva_testimonials() as $item ) : ?>
					<blockquote class="pixva-card pixva-quote pixva-reveal">
						<div class="pixva-stars" aria-hidden="true">★★★★★</div>
						<p><?php echo esc_html( $item['quote'] ); ?></p>
						<footer><strong><?php echo esc_html( $item['name'] ); ?></strong> · <?php echo esc_html( $item['role'] ); ?></footer>
					</blockquote>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * مسیر پذیرش تا تحویل.
 *
 * @return void
 */
function pixva_home_process() {
	$steps = array(
		array(
			'title' => __( 'ثبت علائم', 'pixva' ),
			'text'  => __( 'برند، سایز، مدل و تعداد چشمک را در محاسبه‌گر یا واتساپ بفرستید. اگر ضربه یا آب‌خوردگی بوده همان اول بگویید.', 'pixva' ),
		),
		array(
			'title' => __( 'برآورد و نوبت', 'pixva' ),
			'text'  => __( 'بازه قیمت روی سرور حساب می‌شود. با ثبت شماره، کد پیگیری می‌گیرید و جمع‌آوری در تهران هماهنگ می‌شود.', 'pixva' ),
		),
		array(
			'title' => __( 'عیب‌یابی کارگاه', 'pixva' ),
			'text'  => __( 'مسیر تغذیه، بک‌لایت، مین‌برد و پنل جدا تست می‌شود. قبل از تعویض قطعه، هزینه قطعی را تأیید می‌کنید.', 'pixva' ),
		),
		array(
			'title' => __( 'تست و گارانتی', 'pixva' ),
			'text'  => __( 'تحویل با الگوی رنگ و خاکستری، به‌همراه برگه ۱۸۰ روزه برای برد و بک‌لایت. وضعیت را آنلاین پیگیری می‌کنید.', 'pixva' ),
		),
	);
	?>
	<section class="pixva-section" id="process">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مسیر کار', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از تماس تا تحویل، چهار مرحله شفاف', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'دستگاه بی‌دلیل باز نمی‌شود و قطعه‌ای بدون تأیید شما عوض نمی‌شود.', 'pixva' ); ?></p>
			</div>
			<ol class="pixva-steps pixva-steps--home">
				<?php foreach ( $steps as $step ) : ?>
					<li>
						<strong><?php echo esc_html( $step['title'] ); ?></strong>
						<p><?php echo esc_html( $step['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
}

/**
 * میان‌بر پایگاه کدهای خطا.
 *
 * @return void
 */
function pixva_home_errors() {
	$brands = pixva_brand_catalog();
	$rows   = array_slice( pixva_error_code_catalog(), 0, 4 );
	?>
	<section class="pixva-section pixva-section--alt pixva-reveal" id="error-codes">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'کدهای خطا', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'چشمک چراغ را قبل از باز کردن دستگاه بشمارید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'این راهنمای کارگاهی است، نه سرویس‌منوال رسمی. برد پاور ولتاژ خطرناک دارد.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--2">
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$brand_name = isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['fa'] : $row['brand'];
					$blinks     = $row['blinks'] > 0
						? pixva_fa_num( (string) $row['blinks'] ) . ' ' . __( 'چشمک', 'pixva' )
						: $row['code'];
					?>
					<article class="pixva-card pixva-error-teaser">
						<p class="pixva-kicker"><?php echo esc_html( $brand_name . ' · ' . $blinks ); ?></p>
						<h3><?php echo esc_html( $row['title'] ); ?></h3>
						<p><?php echo esc_html( $row['symptom'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<p style="text-align:center;margin-top:1.4rem">
				<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'پایگاه کامل کدهای خطا', 'pixva' ); ?></a>
			</p>
		</div>
	</section>
	<?php
}

/**
 * سوالات متداول صفحه اصلی.
 *
 * @return void
 */
function pixva_home_faq() {
	?>
	<section class="pixva-section" id="faq">
		<div class="pixva-container" style="max-width:860px">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'قبل از آوردن دستگاه، این‌ها را بدانید', 'pixva' ); ?></h2>
			</div>
			<?php pixva_render_faq( array_slice( pixva_default_faqs(), 0, 6 ), 'home-faq' ); ?>
			<p style="text-align:center;margin-top:1.4rem">
				<a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( pixva_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'همه سوالات', 'pixva' ); ?></a>
			</p>
		</div>
	</section>
	<?php
}

/**
 * مجله تخصصی.
 *
 * @return void
 */
function pixva_home_blog() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
	?>
	<section class="pixva-section pixva-section--alt">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مجله', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'قبل از آوردن دستگاه، این‌ها را بخوانید', 'pixva' ); ?></h2>
			</div>
			<?php if ( ! empty( $posts ) ) : ?>
				<div class="pixva-grid pixva-grid--3">
					<?php foreach ( $posts as $pixva_post ) : ?>
						<?php pixva_post_card( $pixva_post->ID ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'هنوز مقاله‌ای منتشر نشده است. اولین مطلب را از پیشخوان اضافه کنید.', 'pixva' ); ?></p>
			<?php endif; ?>
			<p style="text-align:center;margin-top:1.4rem"><a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( pixva_blog_url() ); ?>"><?php esc_html_e( 'همه مقاله‌ها', 'pixva' ); ?></a></p>
		</div>
	</section>
	<?php
}

/**
 * سکشن جادوگر ثبت سفارش تعمیر (موتور CRM).
 *
 * @return void
 */
function pixva_home_order_wizard() {
	if ( function_exists( 'pixva_render_order_wizard' ) ) {
		pixva_render_order_wizard( array( 'source' => 'home' ) );
	}
}

/**
 * سکشن تستر زنده پیکسل‌سوختگی و احیاکننده OLED.
 *
 * @return void
 */
function pixva_home_screen_tester() {
	if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
		pixva_render_screen_rgb_tester();
	}
}

/**
 * سکشن هاب اعزام اورژانسی و پیگیری آنلاین.
 *
 * @return void
 */
function pixva_home_dispatch_hub() {
	if ( function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
		pixva_render_dispatch_and_warranty_hub();
	}
}
