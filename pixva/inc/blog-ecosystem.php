<?php
/**
 * اکوسیستم وبلاگ و سایدبارهای داینامیک (inc/blog-ecosystem.php)
 *
 * - زمان مطالعه و متای شیشه‌ای کارت مقاله‌ها.
 * - نوار اشتراک‌گذاری سریع (پیوندهای بومی، بدون اسکریپت سنگین).
 * - ابزارک‌های بومی وردپرس برای سایدبار مجله:
 *     ۱) جست‌وجوی هوشمند کدهای خطا (زنده از REST)
 *     ۲) آخرین مقالات آموزش نگهداری تلویزیون
 *     ۳) استعلام سریع هزینه تعمیرات (نرخ‌نامه سمت سرور)
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_reading_time' ) ) {
	/**
	 * زمان مطالعه تقریبی یک مطلب به دقیقه.
	 *
	 * این تابع تنها تعریف قالب است و هم شناسه نوشته و هم رشته محتوا را
	 * می‌پذیرد تا فراخوان‌های قدیمی (محتوای خام) نتیجه درست بدهند.
	 *
	 * @param int|string $post_or_content شناسه نوشته یا محتوای آن.
	 * @return int
	 */
	function pixva_reading_time( $post_or_content = 0 ) {
		$post_id = 0;

		if ( is_string( $post_or_content ) && ! is_numeric( $post_or_content ) && '' !== trim( $post_or_content ) ) {
			$content = $post_or_content;
		} else {
			$post_id = (int) $post_or_content ? (int) $post_or_content : (int) get_the_ID();
			if ( ! $post_id ) {
				return 1;
			}
			$content = (string) get_post_field( 'post_content', $post_id );
		}

		$content = wp_strip_all_tags( strip_shortcodes( $content ) );

		// فارسی: میانگین ۱۸۰ کلمه در دقیقه.
		$words   = preg_split( '/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
		$count   = is_array( $words ) ? count( $words ) : 0;
		$minutes = (int) ceil( $count / 180 );

		/**
		 * فیلتر زمان مطالعه.
		 *
		 * @param int $minutes دقیقه.
		 * @param int $post_id شناسه مطلب.
		 */
		return max( 1, (int) apply_filters( 'pixva_reading_time', $minutes, $post_id ) );
	}
}

if ( ! function_exists( 'pixva_reading_time_label' ) ) {
	/**
	 * برچسب فارسی زمان مطالعه.
	 *
	 * @param int $post_id شناسه مطلب.
	 * @return string
	 */
	function pixva_reading_time_label( $post_id = 0 ) {
		return sprintf(
			/* translators: %s: دقیقه */
			__( '%s دقیقه مطالعه', 'pixva' ),
			pixva_fa_num( (string) pixva_reading_time( $post_id ) )
		);
	}
}

if ( ! function_exists( 'pixva_share_bar' ) ) {
	/**
	 * نوار اشتراک‌گذاری سریع مقاله (واتساپ، تلگرام، ایکس، کپی پیوند).
	 *
	 * @param int $post_id شناسه مطلب.
	 * @return void
	 */
	function pixva_share_bar( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$url   = (string) get_permalink( $post_id );
		$title = (string) get_the_title( $post_id );
		$text  = rawurlencode( $title . ' — ' . $url );

		$networks = array(
			array(
				'key'   => 'whatsapp',
				'label' => __( 'واتساپ', 'pixva' ),
				'href'  => 'https://api.whatsapp.com/send?text=' . $text,
				'icon'  => 'whatsapp',
			),
			array(
				'key'   => 'telegram',
				'label' => __( 'تلگرام', 'pixva' ),
				'href'  => 'https://t.me/share/url?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
				'icon'  => 'send',
			),
			array(
				'key'   => 'x',
				'label' => __( 'ایکس', 'pixva' ),
				'href'  => 'https://twitter.com/intent/tweet?text=' . $text,
				'icon'  => 'share',
			),
		);

		/**
		 * فیلتر شبکه‌های اشتراک‌گذاری.
		 *
		 * @param array $networks شبکه‌ها.
		 * @param int   $post_id  شناسه مطلب.
		 */
		$networks = apply_filters( 'pixva_share_networks', $networks, $post_id );
		?>
		<div class="pixva-share" data-pixva-share>
			<span class="pixva-share__label"><?php esc_html_e( 'اشتراک‌گذاری', 'pixva' ); ?></span>
			<div class="pixva-share__items">
				<?php foreach ( $networks as $network ) : ?>
					<a class="pixva-share__btn pixva-share__btn--<?php echo esc_attr( $network['key'] ); ?>"
						href="<?php echo esc_url( $network['href'] ); ?>" target="_blank" rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( $network['label'] ); ?>">
						<?php echo pixva_icon( $network['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endforeach; ?>
				<button type="button" class="pixva-share__btn pixva-share__btn--copy" data-copy="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( 'کپی پیوند مطلب', 'pixva' ); ?>">
					<?php echo pixva_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_entry_meta' ) ) {
	/**
	 * نوار متای مقاله (تاریخ، زمان مطالعه، دسته، نویسنده).
	 *
	 * @param int $post_id شناسه مطلب.
	 * @return void
	 */
	function pixva_entry_meta( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$cats = get_the_category( $post_id );
		?>
		<div class="pixva-entry-meta">
			<span class="pixva-entry-meta__item">
				<?php echo pixva_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( pixva_format_date( get_post_timestamp( $post_id ) ) ); ?></time>
			</span>
			<span class="pixva-entry-meta__item">
				<?php echo pixva_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( pixva_reading_time_label( $post_id ) ); ?></span>
			</span>
			<?php if ( ! empty( $cats ) ) : ?>
				<a class="pixva-entry-meta__item pixva-entry-meta__item--cat" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>">
					<?php echo esc_html( $cats[0]->name ); ?>
				</a>
			<?php endif; ?>
			<span class="pixva-entry-meta__item">
				<?php echo pixva_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) ); ?></span>
			</span>
		</div>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ابزارک‌های بومی سایدبار
 * ---------------------------------------------------------------------------
 */

if ( ! class_exists( 'Pixva_Error_Search_Widget' ) ) {
	/**
	 * ابزارک «جست‌وجوی هوشمند کدهای خطا» — نتیجه زنده از REST.
	 */
	class Pixva_Error_Search_Widget extends WP_Widget {

		/**
		 * سازنده ابزارک.
		 */
		public function __construct() {
			parent::__construct(
				'pixva_error_search',
				__( 'پیکسوا: جست‌وجوی هوشمند کدهای خطا', 'pixva' ),
				array(
					'description'     => __( 'جست‌وجوی زنده در پایگاه کدهای خطای چشمک چراغ با نمایش راه‌حل و اقدام.', 'pixva' ),
					'classname'       => 'pixva-widget--errors',
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * خروجی فرانت‌اند.
		 *
		 * @param array $args  آرگومان‌های ناحیه.
		 * @param array $instance تنظیمات ابزارک.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			$title    = ! empty( $instance['title'] ) ? $instance['title'] : __( 'جست‌وجوی هوشمند کدهای خطا', 'pixva' );
			$hint     = ! empty( $instance['hint'] ) ? $instance['hint'] : __( 'تعداد چشمک یا کد خطا را بنویسید؛ علت، قطعه و اقدام پیشنهادی فوراً نمایش داده می‌شود.', 'pixva' );
			$endpoint = rest_url( 'pixva/v1/errors' );

			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<section class="pixva-card pixva-widget pixva-widget--errors" data-pixva-error-search>
				<h3 class="pixva-widget__title"><?php echo esc_html( apply_filters( 'widget_title', $title ) ); ?></h3>
				<p class="pixva-widget__hint"><?php echo esc_html( $hint ); ?></p>

				<form class="pixva-widget__search" role="search" data-error-form action="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'error-codes' ) : home_url( '/' ) ); ?>" method="get">
					<label class="screen-reader-text" for="pixva-error-q-<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'جست‌وجوی کد خطا', 'pixva' ); ?></label>
					<input id="pixva-error-q-<?php echo esc_attr( $this->id ); ?>" type="search" name="q" value="" autocomplete="off"
						placeholder="<?php esc_attr_e( 'مثلاً ۳ چشمک یا E101', 'pixva' ); ?>" data-error-input>
					<button type="submit" class="pixva-btn pixva-btn--primary pixva-btn--sm" aria-label="<?php esc_attr_e( 'جست‌وجو', 'pixva' ); ?>">
						<?php echo pixva_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</form>

				<ul class="pixva-widget__results" data-error-results data-error-endpoint="<?php echo esc_url( $endpoint ); ?>" aria-live="polite"></ul>
				<p class="pixva-widget__empty" data-error-empty hidden><?php esc_html_e( 'موردی پیدا نشد؛ عبارت کوتاه‌تری بنویسید یا برند را تغییر دهید.', 'pixva' ); ?></p>

				<a class="pixva-widget__more" href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'error-codes' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'پایگاه کامل کدهای خطا', 'pixva' ); ?></a>
			</section>
			<?php
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * فرم تنظیمات در پیشخوان.
		 *
		 * @param array $instance تنظیمات جاری.
		 * @return void
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$hint  = isset( $instance['hint'] ) ? $instance['hint'] : '';
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'عنوان:', 'pixva' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hint' ) ); ?>"><?php esc_html_e( 'راهنما:', 'pixva' ); ?></label>
				<textarea class="widefat" rows="3" id="<?php echo esc_attr( $this->get_field_id( 'hint' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hint' ) ); ?>"><?php echo esc_textarea( $hint ); ?></textarea>
			</p>
			<?php
		}

		/**
		 * ذخیره تنظیمات.
		 *
		 * @param array $new_instance مقادیر جدید.
		 * @param array $old_instance مقادیر قبلی.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			return array(
				'title' => sanitize_text_field( $new_instance['title'] ),
				'hint'  => sanitize_textarea_field( $new_instance['hint'] ),
			);
		}
	}
}

if ( ! class_exists( 'Pixva_Latest_Guides_Widget' ) ) {
	/**
	 * ابزارک «آخرین مقالات آموزش نگهداری تلویزیون».
	 */
	class Pixva_Latest_Guides_Widget extends WP_Widget {

		/**
		 * سازنده ابزارک.
		 */
		public function __construct() {
			parent::__construct(
				'pixva_latest_guides',
				__( 'پیکسوا: آخرین راهنماهای نگهداری', 'pixva' ),
				array(
					'description'     => __( 'فهرست آخرین مقاله‌های آموزشی با زمان مطالعه و تصویر شاخص.', 'pixva' ),
					'classname'       => 'pixva-widget--guides',
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * خروجی فرانت‌اند.
		 *
		 * @param array $args     آرگومان‌های ناحیه.
		 * @param array $instance تنظیمات ابزارک.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'آخرین آموزش‌های نگهداری تلویزیون', 'pixva' );
			$count = ! empty( $instance['count'] ) ? max( 1, min( 10, (int) $instance['count'] ) ) : 4;
			$cat   = ! empty( $instance['category'] ) ? (string) $instance['category'] : '';

			$query_args = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			);

			if ( '' !== $cat ) {
				if ( is_numeric( $cat ) ) {
					$query_args['cat'] = (int) $cat;
				} else {
					$query_args['category_name'] = $cat;
				}
			}

			$query = new WP_Query( $query_args );
			if ( ! $query->have_posts() ) {
				wp_reset_postdata();
				return;
			}

			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<section class="pixva-card pixva-widget pixva-widget--guides">
				<h3 class="pixva-widget__title"><?php echo esc_html( apply_filters( 'widget_title', $title ) ); ?></h3>
				<ul class="pixva-widget__posts">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$post_id = get_the_ID();
						?>
						<li class="pixva-widget__post">
							<a href="<?php the_permalink(); ?>">
								<span class="pixva-widget__post-thumb">
									<?php
									if ( has_post_thumbnail( $post_id ) ) {
										echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( get_the_title( $post_id ) ) ) );
									} else {
										echo pixva_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</span>
								<span class="pixva-widget__post-body">
									<strong><?php the_title(); ?></strong>
									<small><?php echo esc_html( pixva_reading_time_label( $post_id ) ); ?> · <?php echo esc_html( pixva_format_date( get_post_timestamp( $post_id ) ) ); ?></small>
								</span>
							</a>
						</li>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				</ul>
				<a class="pixva-widget__more" href="<?php echo esc_url( function_exists( 'pixva_blog_url' ) ? pixva_blog_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'همه مقاله‌ها', 'pixva' ); ?></a>
			</section>
			<?php
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * فرم تنظیمات.
		 *
		 * @param array $instance تنظیمات جاری.
		 * @return void
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$count = isset( $instance['count'] ) ? (int) $instance['count'] : 4;
			$cat   = isset( $instance['category'] ) ? $instance['category'] : '';
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'عنوان:', 'pixva' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'تعداد مقاله:', 'pixva' ); ?></label>
				<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="10" value="<?php echo esc_attr( (string) $count ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'دسته (نامک یا شناسه؛ خالی = همه):', 'pixva' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>" type="text" value="<?php echo esc_attr( $cat ); ?>" dir="ltr">
			</p>
			<?php
		}

		/**
		 * ذخیره تنظیمات.
		 *
		 * @param array $new_instance مقادیر جدید.
		 * @param array $old_instance مقادیر قبلی.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			return array(
				'title'    => sanitize_text_field( $new_instance['title'] ),
				'count'    => max( 1, min( 10, (int) $new_instance['count'] ) ),
				'category' => sanitize_text_field( $new_instance['category'] ),
			);
		}
	}
}

if ( ! class_exists( 'Pixva_Quick_Quote_Widget' ) ) {
	/**
	 * ابزارک «استعلام سریع هزینه تعمیرات» در سایدبار.
	 */
	class Pixva_Quick_Quote_Widget extends WP_Widget {

		/**
		 * سازنده ابزارک.
		 */
		public function __construct() {
			parent::__construct(
				'pixva_quick_quote',
				__( 'پیکسوا: استعلام سریع هزینه', 'pixva' ),
				array(
					'description'     => __( 'برآورد فوری بازه هزینه و زمان تعمیر از نرخ‌نامه سمت سرور + پیوند ثبت سفارش.', 'pixva' ),
					'classname'       => 'pixva-widget--quote',
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * خروجی فرانت‌اند.
		 *
		 * @param array $args     آرگومان‌های ناحیه.
		 * @param array $instance تنظیمات ابزارک.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'استعلام سریع هزینه تعمیر', 'pixva' );
			$text  = ! empty( $instance['text'] ) ? $instance['text'] : __( 'برند و علامت خرابی را انتخاب کنید؛ بازه قیمت و زمان تحویل از نرخ‌نامه مصوب محاسبه می‌شود.', 'pixva' );

			$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
			$brands = isset( $labels['brand'] ) ? array_slice( $labels['brand'], 0, 12, true ) : array();
			$issues = isset( $labels['problem'] ) ? array_slice( $labels['problem'], 0, 10, true ) : array();
			$sizes  = isset( $labels['size'] ) ? $labels['size'] : array();

			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<section class="pixva-card pixva-widget pixva-widget--quote" data-pixva-quick-quote>
				<h3 class="pixva-widget__title"><?php echo esc_html( apply_filters( 'widget_title', $title ) ); ?></h3>
				<p class="pixva-widget__hint"><?php echo esc_html( $text ); ?></p>

				<form class="pixva-widget__quote-form" data-quote-form novalidate>
					<div class="pixva-field">
						<label for="pixva-quote-brand-<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
						<select id="pixva-quote-brand-<?php echo esc_attr( $this->id ); ?>" name="brand" data-quote-brand>
							<?php foreach ( $brands as $key => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="pixva-field">
						<label for="pixva-quote-size-<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'سایز', 'pixva' ); ?></label>
						<select id="pixva-quote-size-<?php echo esc_attr( $this->id ); ?>" name="size" data-quote-size>
							<?php foreach ( $sizes as $key => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $key ); ?>" <?php selected( '55', (string) $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="pixva-field">
						<label for="pixva-quote-problem-<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'علامت خرابی', 'pixva' ); ?></label>
						<select id="pixva-quote-problem-<?php echo esc_attr( $this->id ); ?>" name="problem" data-quote-problem>
							<?php foreach ( $issues as $key => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<button type="submit" class="pixva-btn pixva-btn--gradient pixva-btn--sm" data-quote-submit><?php esc_html_e( 'محاسبه برآورد', 'pixva' ); ?></button>

					<div class="pixva-widget__quote-result" data-quote-result hidden>
						<p class="pixva-widget__quote-price" data-quote-price>—</p>
						<p class="pixva-widget__quote-days" data-quote-days></p>
					</div>
					<p class="pixva-notice pixva-notice--error" data-quote-error hidden></p>
				</form>

				<div class="pixva-widget__quote-actions">
					<a class="pixva-btn pixva-btn--cta pixva-btn--sm" href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'ثبت سفارش تعمیر', 'pixva' ); ?></a>
					<?php $phone = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : ''; ?>
					<?php if ( $phone ) : ?>
						<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
							<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( pixva_fa_num( $phone ) ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			</section>
			<?php
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * فرم تنظیمات.
		 *
		 * @param array $instance تنظیمات جاری.
		 * @return void
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$text  = isset( $instance['text'] ) ? $instance['text'] : '';
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'عنوان:', 'pixva' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php esc_html_e( 'توضیح کوتاه:', 'pixva' ); ?></label>
				<textarea class="widefat" rows="3" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
			</p>
			<?php
		}

		/**
		 * ذخیره تنظیمات.
		 *
		 * @param array $new_instance مقادیر جدید.
		 * @param array $old_instance مقادیر قبلی.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			return array(
				'title' => sanitize_text_field( $new_instance['title'] ),
				'text'  => sanitize_textarea_field( $new_instance['text'] ),
			);
		}
	}
}

if ( ! function_exists( 'pixva_blog_register_widgets' ) ) {
	/**
	 * ثبت ابزارک‌های بومی سایدبار وبلاگ.
	 *
	 * @return void
	 */
	function pixva_blog_register_widgets() {
		register_widget( 'Pixva_Error_Search_Widget' );
		register_widget( 'Pixva_Latest_Guides_Widget' );
		register_widget( 'Pixva_Quick_Quote_Widget' );
	}
}
add_action( 'widgets_init', 'pixva_blog_register_widgets', 20 );

if ( ! function_exists( 'pixva_blog_sidebar_extras' ) ) {
	/**
	 * خروجی ابزارک‌های وبلاگ وقتی مدیر هنوز ابزارکی نچیده است.
	 *
	 * @return void
	 */
	function pixva_blog_sidebar_extras() {
		$error_widget = new Pixva_Error_Search_Widget();
		$error_widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
			),
			array(
				'title' => __( 'جست‌وجوی هوشمند کدهای خطا', 'pixva' ),
				'hint'  => __( 'تعداد چشمک یا کد خطا را بنویسید؛ علت، قطعه و اقدام پیشنهادی فوراً نمایش داده می‌شود.', 'pixva' ),
			)
		);

		$guides_widget = new Pixva_Latest_Guides_Widget();
		$guides_widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
			),
			array(
				'title'    => __( 'آخرین آموزش‌های نگهداری تلویزیون', 'pixva' ),
				'count'    => 4,
				'category' => '',
			)
		);

		$quote_widget = new Pixva_Quick_Quote_Widget();
		$quote_widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
			),
			array(
				'title' => __( 'استعلام سریع هزینه تعمیر', 'pixva' ),
				'text'  => __( 'برند و علامت خرابی را انتخاب کنید؛ بازه قیمت و زمان تحویل از نرخ‌نامه مصوب محاسبه می‌شود.', 'pixva' ),
			)
		);
	}
}
