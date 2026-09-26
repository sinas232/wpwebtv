<?php
/**
 * ناوبری داینامیک و سایدبارها (inc/nav-menu.php)
 *
 * - درخت منوی اصلی وردپرس با عمق نامحدود (pixva_menu_tree)
 * - رندر آبشاری شیشه‌ای برای دسکتاپ (pixva_render_nav_items)
 * - رندر آکاردئونی برای دروئر موبایل (pixva_render_drawer_nav)
 * - انتخاب سایدبار بر اساس بافت صفحه (pixva_active_sidebar)
 * - محتوای جایگزین سایدبارها، کاملاً از داده‌های سایت (pixva_sidebar_fallback)
 *
 * هیچ عنوان، لینک یا متنی در این پرونده hardcode نیست: همه از منوهای وردپرس،
 * نوع‌های محتوای اختصاصی و تنظیمات پوسته خوانده می‌شوند.
 *
 * @package Pixva
 * @since   1.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_menu_tree' ) ) {
	/**
	 * ساخت درخت آیتم‌های یک جایگاه منو.
	 *
	 * @param string $location جایگاه منو (primary|footer).
	 * @param array  $args     گزینه‌ها: skip_hubs, skip_front, depth.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_menu_tree( $location = 'primary', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'skip_hubs'  => true,
				'skip_front' => true,
				'depth'      => 0,
			)
		);

		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return array();
		}

		$menu = wp_get_nav_menu_object( $locations[ $location ] );
		if ( ! $menu ) {
			return array();
		}

		$raw = wp_get_nav_menu_items( (int) $menu->term_id );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$skip = array();
		if ( $args['skip_hubs'] && function_exists( 'pixva_hubs' ) ) {
			foreach ( array_keys( pixva_hubs() ) as $hub_slug ) {
				$page = get_page_by_path( $hub_slug );
				if ( $page instanceof WP_Post ) {
					$skip[] = (int) $page->ID;
				}
			}
		}
		if ( $args['skip_front'] ) {
			$skip[] = (int) get_option( 'page_on_front' );
		}
		$skip = array_unique( array_filter( $skip ) );

		/**
		 * فیلتر شناسه‌هایی که از منوی رندرشده حذف می‌شوند.
		 *
		 * @param int[]  $skip     شناسه objectها.
		 * @param string $location جایگاه منو.
		 */
		$skip = apply_filters( 'pixva_menu_skip_ids', $skip, $location );

		$by_id = array();
		foreach ( $raw as $item ) {
			$title = trim( (string) $item->title );
			if ( '' === $title || empty( $item->url ) ) {
				continue;
			}
			if ( in_array( (int) $item->object_id, $skip, true ) ) {
				continue;
			}
			$by_id[ (int) $item->ID ] = array(
				'id'       => (int) $item->ID,
				'parent'   => (int) $item->menu_item_parent,
				'title'    => $title,
				'url'      => (string) $item->url,
				'target'   => (string) $item->target,
				'classes'  => array_values( array_filter( array_map( 'sanitize_html_class', (array) $item->classes ) ) ),
				'desc'     => trim( (string) $item->description ),
				'current'  => in_array( 'current-menu-item', (array) $item->classes, true ) || in_array( 'current-menu-ancestor', (array) $item->classes, true ),
				'children' => array(),
			);
		}

		$tree = array();
		foreach ( $by_id as $id => &$node ) {
			if ( $node['parent'] && isset( $by_id[ $node['parent'] ] ) ) {
				$by_id[ $node['parent'] ]['children'][] = &$node;
			} else {
				$tree[] = &$node;
			}
		}
		unset( $node );

		if ( (int) $args['depth'] > 0 ) {
			$tree = pixva_menu_limit_depth( $tree, (int) $args['depth'] );
		}

		return $tree;
	}
}

if ( ! function_exists( 'pixva_menu_limit_depth' ) ) {
	/**
	 * محدودکردن عمق درخت منو.
	 *
	 * @param array $tree  درخت.
	 * @param int   $depth عمق مجاز.
	 * @param int   $level سطح جاری.
	 * @return array
	 */
	function pixva_menu_limit_depth( $tree, $depth, $level = 1 ) {
		foreach ( $tree as $key => $node ) {
			if ( $level >= $depth ) {
				$tree[ $key ]['children'] = array();
			} else {
				$tree[ $key ]['children'] = pixva_menu_limit_depth( (array) $node['children'], $depth, $level + 1 );
			}
		}
		return $tree;
	}
}

if ( ! function_exists( 'pixva_render_nav_items' ) ) {
	/**
	 * رندر آیتم‌های منوی اصلی با آبشاری شیشه‌ای چندسطحی (دسکتاپ).
	 *
	 * خروجی برای قرارگیری داخل .pixva-mega__list آماده است و از همان
	 * data-mega-item / data-mega-trigger / data-mega-panel استفاده می‌کند تا
	 * منطق باز و بسته‌شدن (هاور، کلیک، Escape) مشترک بماند.
	 *
	 * @param array  $tree      درخت منو.
	 * @param string $id_prefix پیشوند شناسه پنل‌ها.
	 * @return void
	 */
	function pixva_render_nav_items( $tree, $id_prefix = 'nav' ) {
		foreach ( $tree as $index => $item ) {
			$has_children = ! empty( $item['children'] );
			$classes      = array( 'pixva-mega__item', 'pixva-nav-item' );
			if ( $has_children ) {
				$classes[] = 'has-children';
			}
			if ( ! empty( $item['current'] ) ) {
				$classes[] = 'is-current';
			}
			$classes = array_merge( $classes, (array) $item['classes'] );
			$panel   = $id_prefix . '-panel-' . (int) $item['id'];
			?>
			<li class="<?php echo esc_attr( implode( ' ', array_unique( $classes ) ) ); ?>"<?php echo $has_children ? ' data-mega-item' : ''; ?>>
				<?php if ( $has_children ) : ?>
					<button type="button" class="pixva-mega__trigger" data-mega-trigger aria-expanded="false" aria-controls="<?php echo esc_attr( $panel ); ?>">
						<span><?php echo esc_html( $item['title'] ); ?></span>
						<svg class="pixva-mega__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
					</button>
					<div class="pixva-mega__panel pixva-mega__panel--menu" id="<?php echo esc_attr( $panel ); ?>" data-mega-panel>
						<div class="pixva-dropdown">
							<?php pixva_render_dropdown_level( (array) $item['children'], 2 ); ?>
						</div>
					</div>
				<?php else : ?>
					<a class="pixva-mega__link" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $item['title'] ); ?></a>
				<?php endif; ?>
			</li>
			<?php
		}
	}
}

if ( ! function_exists( 'pixva_render_dropdown_level' ) ) {
	/**
	 * رندر بازگشتی سطوح آبشاری (بدون محدودیت عمق).
	 *
	 * @param array $children آیتم‌های فرزند.
	 * @param int   $level    سطح جاری.
	 * @return void
	 */
	function pixva_render_dropdown_level( $children, $level = 2 ) {
		if ( empty( $children ) ) {
			return;
		}
		?>
		<ul class="pixva-dropdown__list pixva-dropdown__list--level-<?php echo esc_attr( (string) $level ); ?>">
			<?php foreach ( $children as $item ) : ?>
				<?php
				$has_children = ! empty( $item['children'] );
				$classes      = array( 'pixva-dropdown__item' );
				if ( $has_children ) {
					$classes[] = 'has-children';
				}
				if ( ! empty( $item['current'] ) ) {
					$classes[] = 'is-current';
				}
				?>
				<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : ''; ?>>
						<span><?php echo esc_html( $item['title'] ); ?></span>
						<?php if ( '' !== $item['desc'] ) : ?>
							<small><?php echo esc_html( $item['desc'] ); ?></small>
						<?php endif; ?>
						<?php if ( $has_children ) : ?>
							<svg class="pixva-dropdown__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
						<?php endif; ?>
					</a>
					<?php if ( $has_children ) : ?>
						<div class="pixva-dropdown__sub">
							<?php pixva_render_dropdown_level( (array) $item['children'], $level + 1 ); ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_drawer_nav' ) ) {
	/**
	 * رندر آکاردئونی منو برای دروئر موبایل (آبشاری چندسطحی).
	 *
	 * @param array  $tree      درخت منو.
	 * @param string $id_prefix پیشوند شناسه پنل‌ها.
	 * @param int    $level     سطح جاری.
	 * @return void
	 */
	function pixva_render_drawer_nav( $tree, $id_prefix = 'drawer-nav', $level = 1 ) {
		if ( empty( $tree ) ) {
			return;
		}
		?>
		<ul class="pixva-drawer-nav pixva-drawer-nav--level-<?php echo esc_attr( (string) $level ); ?>" data-pixva-drawer-nav>
			<?php foreach ( $tree as $item ) : ?>
				<?php
				$has_children = ! empty( $item['children'] );
				$panel_id     = $id_prefix . '-' . (int) $item['id'];
				$classes      = array( 'pixva-drawer-nav__item' );
				if ( $has_children ) {
					$classes[] = 'has-children';
				}
				if ( ! empty( $item['current'] ) ) {
					$classes[] = 'is-current';
				}
				?>
				<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<div class="pixva-drawer-nav__row">
						<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $item['title'] ); ?></a>
						<?php if ( $has_children ) : ?>
							<button type="button" class="pixva-drawer-nav__toggle" data-drawer-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'زیرمنوی %s', 'pixva' ), $item['title'] ) ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
							</button>
						<?php endif; ?>
					</div>
					<?php if ( $has_children ) : ?>
						<div class="pixva-drawer-nav__panel" id="<?php echo esc_attr( $panel_id ); ?>" data-drawer-panel>
							<div class="pixva-drawer-nav__inner">
								<?php pixva_render_drawer_nav( (array) $item['children'], $panel_id, $level + 1 ); ?>
							</div>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}

if ( ! function_exists( 'pixva_active_sidebar' ) ) {
	/**
	 * سایدبار فعال بر اساس بافت صفحه جاری.
	 *
	 * @return string شناسه ناحیه ابزارک.
	 */
	function pixva_active_sidebar() {
		$sidebar = 'blog-sidebar';

		if ( is_singular( array( 'tv_services', 'tv_brands', 'repair_cases' ) )
			|| is_post_type_archive( array( 'tv_services', 'tv_brands', 'repair_cases' ) )
			|| is_tax( array( 'tv_problem', 'tv_tech' ) ) ) {
			$sidebar = 'services-sidebar';
		} elseif ( is_page() ) {
			$sidebar = 'page-sidebar';
		}

		/**
		 * فیلتر سایدبار فعال.
		 *
		 * @param string $sidebar شناسه ناحیه.
		 */
		return apply_filters( 'pixva_active_sidebar', $sidebar );
	}
}

if ( ! function_exists( 'pixva_sidebar_widgets' ) ) {
	/**
	 * خروجی ناحیه ابزارک بدون پوسته <aside> (برای قالب‌هایی که aside خودشان را دارند).
	 *
	 * @param string $sidebar شناسه ناحیه ابزارک.
	 * @return void
	 */
	function pixva_sidebar_widgets( $sidebar = '' ) {
		$sidebar = '' !== $sidebar ? (string) $sidebar : pixva_active_sidebar();

		if ( is_active_sidebar( $sidebar ) ) {
			dynamic_sidebar( $sidebar );
			return;
		}

		pixva_sidebar_fallback( $sidebar );
	}
}

if ( ! function_exists( 'pixva_sidebar_fallback' ) ) {
	/**
	 * محتوای جایگزین سایدبار وقتی هیچ ابزارکی چیده نشده است.
	 *
	 * همه بخش‌ها از داده‌های زنده سایت ساخته می‌شوند (تنظیمات، خدمات، هاب‌ها).
	 *
	 * @param string $sidebar شناسه ناحیه.
	 * @return void
	 */
	function pixva_sidebar_fallback( $sidebar = 'blog-sidebar' ) {
		$phone   = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
		$control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		$hours   = isset( $control['hub_eta_hours'] ) ? $control['hub_eta_hours'] : '';
		?>
		<section class="pixva-card pixva-widget pixva-widget--search">
			<h3 class="pixva-widget__title"><?php esc_html_e( 'جستجو', 'pixva' ); ?></h3>
			<?php get_search_form(); ?>
		</section>

		<?php if ( 'services-sidebar' === $sidebar ) : ?>
			<section class="pixva-card pixva-widget pixva-widget--consult">
				<h3 class="pixva-widget__title"><?php esc_html_e( 'مشاوره سریع', 'pixva' ); ?></h3>
				<p><?php echo esc_html( (string) pixva_option( 'pixva_sidebar_consult_text', __( 'علامت خرابی را بگویید تا کارشناس همان بخش، هزینه و زمان را اعلام کند.', 'pixva' ) ) ); ?></p>
				<?php if ( '' !== $phone ) : ?>
					<a class="pixva-btn pixva-btn--gradient pixva-btn--sm" href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
						<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( pixva_fa_num( $phone ) ); ?></span>
					</a>
					<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" href="<?php echo esc_url( pixva_whatsapp_url( (string) pixva_option( 'pixva_sidebar_whatsapp_text', __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $hours ) : ?>
					<p class="pixva-widget__meta"><?php echo esc_html( sprintf( __( 'اعزام اورژانسی زیر %s', 'pixva' ), $hours ) ); ?></p>
				<?php endif; ?>
			</section>

			<?php pixva_sidebar_services_list(); ?>
		<?php else : ?>
			<?php // ابزارک‌های تخصصی مجله: جست‌وجوی کد خطا، آخرین آموزش‌ها و استعلام سریع هزینه. ?>
			<?php if ( function_exists( 'pixva_blog_sidebar_extras' ) ) : ?>
				<?php pixva_blog_sidebar_extras(); ?>
			<?php else : ?>
				<section class="pixva-card pixva-widget pixva-widget--calc">
					<h3 class="pixva-widget__title"><?php esc_html_e( 'عیب را خودتان قیمت بگیرید', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'بازه هزینه و زمان تعمیر را در چند مرحله ببینید.', 'pixva' ); ?></p>
					<a class="pixva-btn pixva-btn--gradient pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه‌گر هزینه', 'pixva' ); ?></a>
					<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری دستگاه', 'pixva' ); ?></a>
				</section>
			<?php endif; ?>
		<?php endif; ?>

		<?php pixva_sidebar_hubs_list(); ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_sidebar_services_list' ) ) {
	/**
	 * فهرست خدمات تعمیرات از نوع محتوای tv_services (کاملاً داینامیک).
	 *
	 * @return void
	 */
	function pixva_sidebar_services_list() {
		if ( ! post_type_exists( 'tv_services' ) ) {
			return;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'tv_services',
				'posts_per_page' => (int) apply_filters( 'pixva_sidebar_services_count', 6 ),
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}
		?>
		<section class="pixva-card pixva-widget pixva-widget--list">
			<h3 class="pixva-widget__title"><?php esc_html_e( 'دسته‌بندی خدمات', 'pixva' ); ?></h3>
			<ul class="pixva-widget__links">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					?>
					<li>
						<a href="<?php the_permalink(); ?>">
							<span><?php the_title(); ?></span>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
						</a>
					</li>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</ul>
			<a class="pixva-widget__more" href="<?php echo esc_url( get_post_type_archive_link( 'tv_services' ) ); ?>"><?php esc_html_e( 'همه خدمات', 'pixva' ); ?></a>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_sidebar_hubs_list' ) ) {
	/**
	 * فهرست هاب‌های تخصصی برای سایدبارها.
	 *
	 * @return void
	 */
	function pixva_sidebar_hubs_list() {
		if ( ! function_exists( 'pixva_hubs' ) ) {
			return;
		}
		?>
		<section class="pixva-card pixva-widget pixva-widget--hubs">
			<h3 class="pixva-widget__title"><?php esc_html_e( 'هاب‌های تخصصی', 'pixva' ); ?></h3>
			<ul class="pixva-widget__links">
				<?php foreach ( pixva_hubs() as $hub ) : ?>
					<li>
						<a href="<?php echo esc_url( pixva_hub_url( $hub['slug'] ) ); ?>">
							<span><?php echo esc_html( $hub['title'] ); ?></span>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_mega_service_url' ) ) {
	/**
	 * آدرس یک خدمت تعمیراتی برای منوی مگا.
	 *
	 * اگر خدمت هم‌نام در نوع محتوای «خدمات» ساخته شده باشد آدرس همان نوشته
	 * برمی‌گردد؛ در غیر این صورت به محاسبه‌گر با پارامتر problem می‌رود تا
	 * کاربر برآورد قیمت همان خدمت را ببیند.
	 *
	 * @param string $key کلید خدمت (backlight|mainboard|panel|...).
	 * @return string
	 */
	function pixva_mega_service_url( $key ) {
		if ( post_type_exists( 'tv_services' ) ) {
			$post = get_page_by_path( $key, OBJECT, 'tv_services' );
			if ( $post instanceof WP_Post ) {
				return (string) get_permalink( $post );
			}
		}

		return add_query_arg( 'problem', $key, pixva_page_url( 'calculator' ) );
	}
}

if ( ! function_exists( 'pixva_mega_service_title' ) ) {
	/**
	 * عنوان یک خدمت برای منوی مگا.
	 *
	 * اگر نوشته‌ای هم‌نام در نوع محتوای «خدمات» ساخته شده باشد عنوان همان
	 * نوشته استفاده می‌شود؛ در غیر این صورت برچسب پیش‌فرض.
	 *
	 * @param string $key     کلید خدمت.
	 * @param string $default برچسب پیش‌فرض.
	 * @return string
	 */
	function pixva_mega_service_title( $key, $default ) {
		if ( post_type_exists( 'tv_services' ) ) {
			$post = get_page_by_path( $key, OBJECT, 'tv_services' );
			if ( $post instanceof WP_Post ) {
				$title = trim( (string) get_the_title( $post ) );
				if ( '' !== $title ) {
					return $title;
				}
			}
		}

		return $default;
	}
}

if ( ! function_exists( 'pixva_mega_groups' ) ) {
	/**
	 * چهار گروه منوی مگا طبق Master Prompt v6.
	 *
	 * گروه‌ها: خدمات تعمیرات، کدهای خطا و عیب‌یابی، استعلام و گارانتی و ورود
	 * تعمیرکاران. عنوان و آدرس‌ها از داده‌های واقعی سایت (کاتالوگ برندها،
	 * برگه‌های قالب، نوع محتوای خدمات) ساخته می‌شود و با فیلتر
	 * pixva_mega_groups یا گزینه‌های پیکسوا قابل بازنویسی است.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_mega_groups() {
		$brands   = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$fallback = function_exists( 'pixva_service_fallbacks' ) ? pixva_service_fallbacks() : array();
		$errors   = pixva_page_url( 'error-codes' );
		$days     = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;

		$service_map = array();
		foreach ( $fallback as $item ) {
			if ( isset( $item['key'] ) ) {
				$service_map[ $item['key'] ] = $item;
			}
		}

		/**
		 * عنوان و توضیح هر خدمت کلیدی منو (طبق Master Prompt v6).
		 *
		 * @var array<string, array{title:string, text:string, icon:string}>
		 */
		$services = array(
			'backlight' => array(
				'title' => pixva_mega_service_title( 'backlight', __( 'تعمیر بک‌لایت', 'pixva' ) ),
				'text'  => __( 'رفع تاریکی موضعی و هاله نور با نوار LED اصلی', 'pixva' ),
				'icon'  => isset( $service_map['backlight']['icon'] ) ? $service_map['backlight']['icon'] : 'sun',
			),
			'mainboard' => array(
				'title' => pixva_mega_service_title( 'mainboard', __( 'تعمیر برد اصلی', 'pixva' ) ),
				'text'  => __( 'عیب‌یابی مین‌برد، HDMI و بخش پردازش تصویر', 'pixva' ),
				'icon'  => isset( $service_map['mainboard']['icon'] ) ? $service_map['mainboard']['icon'] : 'cpu',
			),
			'panel'     => array(
				'title' => pixva_mega_service_title( 'panel', __( 'تعمیر پنل OLED/LED', 'pixva' ) ),
				'text'  => __( 'بندینگ COF و ترمیم خطوط بدون تعویض شیشه', 'pixva' ),
				'icon'  => isset( $service_map['panel']['icon'] ) ? $service_map['panel']['icon'] : 'panel',
			),
		);

		$service_items = array();
		foreach ( $services as $key => $item ) {
			$service_items[] = array(
				'title' => (string) pixva_option( 'pixva_mega_service_' . $key, $item['title'] ),
				'text'  => $item['text'],
				'icon'  => $item['icon'],
				'url'   => pixva_mega_service_url( $key ),
			);
		}

		$error_items = array();
		foreach ( array( 'sony', 'samsung', 'lg' ) as $brand_key ) {
			$name = isset( $brands[ $brand_key ]['fa'] ) ? $brands[ $brand_key ]['fa'] : strtoupper( $brand_key );
			/* translators: %s: نام برند */
			$error_items[] = array(
				'title' => sprintf( __( 'راهنمای کدهای خطای %s', 'pixva' ), $name ),
				'text'  => __( 'جدول کد خطا، شمارش چشمک و راه‌حل خانگی', 'pixva' ),
				'icon'  => 'doc',
				'url'   => add_query_arg( 'brand', $brand_key, $errors ),
			);
		}

		$warranty_items = array(
			array(
				'title' => (string) pixva_option( 'pixva_mega_parts_title', __( 'استعلام اصالت قطعه', 'pixva' ) ),
				'text'  => __( 'موجودی انبار و سریال قطعه فابریک', 'pixva' ),
				'icon'  => 'box',
				'url'   => pixva_page_url( 'parts-stock' ),
			),
			array(
				'title' => (string) pixva_option( 'pixva_mega_tracking_title', __( 'پیگیری سفارش', 'pixva' ) ),
				'text'  => __( 'وضعیت زنده پرونده با کد رهگیری یا شماره تماس', 'pixva' ),
				'icon'  => 'route',
				'url'   => pixva_page_url( 'tracking' ),
			),
			array(
				'title' => (string) pixva_option( 'pixva_mega_warranty_title', __( 'مشاهده کارت گارانتی', 'pixva' ) ),
				/* translators: %s: روز گارانتی */
				'text'  => sprintf( __( 'کارت دیجیتال با هولوگرام و گارانتی %s روزه', 'pixva' ), function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $days ) : $days ),
				'icon'  => 'cert',
				'url'   => pixva_page_url( 'client-hub' ),
			),
		);

		$tech_page = get_page_by_path( 'technician-dashboard' );
		if ( ! $tech_page instanceof WP_Post ) {
			$tech_page = get_page_by_path( 'technician' );
		}
		$tech_url = $tech_page instanceof WP_Post ? (string) get_permalink( $tech_page ) : home_url( '/technician-dashboard/' );

		$groups = array(
			array(
				'id'     => 'services',
				'title'  => (string) pixva_option( 'pixva_mega_group_services', __( 'خدمات تعمیرات', 'pixva' ) ),
				'text'   => __( 'تعمیر تخصصی پنل، بک‌لایت و برد اصلی با قطعه فابریک و گزارش فنی کتبی.', 'pixva' ),
				'icon'   => 'tool',
				'url'    => '',
				'items'  => $service_items,
				'cta'    => array(
					'title' => __( 'برآورد هزینه تعمیر', 'pixva' ),
					'url'   => pixva_page_url( 'calculator' ),
					'icon'  => 'calculator',
				),
				'note'   => array(
					'title' => __( 'همه خدمات کارگاه', 'pixva' ),
					'url'   => post_type_exists( 'tv_services' ) ? (string) get_post_type_archive_link( 'tv_services' ) : pixva_page_url( 'rates' ),
				),
			),
			array(
				'id'     => 'errors',
				'title'  => (string) pixva_option( 'pixva_mega_group_errors', __( 'کدهای خطا و عیب‌یابی', 'pixva' ) ),
				'text'   => __( 'کد خطا یا شمارش چشمک چراغ پاور را پیدا کنید؛ راه‌حل خانگی پیش از اعزام کارشناس.', 'pixva' ),
				'icon'   => 'search',
				'url'    => '',
				'items'  => $error_items,
				'cta'    => array(
					'title' => __( 'پایگاه کامل کدهای خطا', 'pixva' ),
					'url'   => $errors,
					'icon'  => 'search',
				),
				'note'   => array(
					'title' => __( 'عیب‌یابی هوشمند با هوش مصنوعی', 'pixva' ),
					'url'   => function_exists( 'pixva_hub_url' ) ? pixva_hub_url( 'ai-diagnostics' ) : pixva_page_url( 'ai-diagnostics' ),
				),
			),
			array(
				'id'     => 'warranty',
				'title'  => (string) pixva_option( 'pixva_mega_group_warranty', __( 'استعلام و گارانتی', 'pixva' ) ),
				'text'   => __( 'استعلام اصالت قطعه، وضعیت لحظه‌ای پرونده و کارت گارانتی دیجیتال با هولوگرام.', 'pixva' ),
				'icon'   => 'shield',
				'url'    => '',
				'items'  => $warranty_items,
				'cta'    => array(
					'title' => __( 'ثبت سفارش تعمیر', 'pixva' ),
					'url'   => pixva_page_url( 'contact' ),
					'icon'  => 'send',
				),
				'note'   => array(
					'title' => __( 'نرخ‌نامه مصوب ۱۴۰۵', 'pixva' ),
					'url'   => pixva_page_url( 'rates' ),
				),
			),
			array(
				'id'     => 'technician',
				'title'  => (string) pixva_option( 'pixva_mega_group_technician', __( 'ورود تعمیرکاران', 'pixva' ) ),
				'icon'   => 'user',
				'url'    => $tech_url,
				'items'  => array(),
				'cta'    => array(),
				'note'   => array(),
			),
		);

		/**
		 * فیلتر گروه‌های منوی مگا.
		 *
		 * @param array $groups گروه‌ها.
		 */
		return apply_filters( 'pixva_mega_groups', $groups );
	}
}

if ( ! function_exists( 'pixva_render_mega_group_links' ) ) {
	/**
	 * چاپ فهرست پیوندهای یک گروه مگا.
	 *
	 * @param array<string, mixed> $group گروه.
	 * @return void
	 */
	function pixva_render_mega_group_links( $group ) {
		if ( empty( $group['items'] ) || ! is_array( $group['items'] ) ) {
			return;
		}
		?>
		<div class="pixva-mega__col pixva-mega__col--links">
			<h4><?php echo esc_html( isset( $group['title'] ) ? $group['title'] : '' ); ?></h4>
			<ul class="pixva-mega__links">
				<?php foreach ( $group['items'] as $item ) : ?>
					<li>
						<a href="<?php echo esc_url( isset( $item['url'] ) ? $item['url'] : home_url( '/' ) ); ?>" data-ripple>
							<?php echo pixva_icon( isset( $item['icon'] ) ? $item['icon'] : 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<strong><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></strong>
								<?php if ( ! empty( $item['text'] ) ) : ?>
									<small><?php echo esc_html( $item['text'] ); ?></small>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_drawer_groups' ) ) {
	/**
	 * منوی آکاردئونی دروئر موبایل بر پایه همان چهار گروه مگا.
	 *
	 * @param string $id_prefix پیشوند شناسه پنل‌ها.
	 * @return void
	 */
	function pixva_render_drawer_groups( $id_prefix = 'drawer-group' ) {
		$groups = function_exists( 'pixva_mega_groups' ) ? pixva_mega_groups() : array();
		if ( empty( $groups ) ) {
			return;
		}

		echo '<ul class="pixva-nav__list pixva-drawer-hubs pixva-drawer-groups">';
		$index = 0;
		foreach ( $groups as $group ) {
			$index++;
			$title = isset( $group['title'] ) ? (string) $group['title'] : '';
			$url   = isset( $group['url'] ) ? (string) $group['url'] : '';

			if ( empty( $group['items'] ) ) {
				echo '<li class="pixva-drawer-hub pixva-drawer-hub--direct">';
				echo '<a class="pixva-drawer-hub__title" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
				echo '</li>';
				continue;
			}

			$panel_id = $id_prefix . '-' . $index;
			echo '<li class="pixva-drawer-hub" data-pixva-accordion-item>';
			if ( '' !== $url ) {
				echo '<a class="pixva-drawer-hub__title" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
			} else {
				echo '<span class="pixva-drawer-hub__title">' . esc_html( $title ) . '</span>';
			}
			echo '<button type="button" class="pixva-drawer-hub__toggle" data-pixva-accordion aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '" aria-label="' . esc_attr( sprintf( __( 'زیرمنوی %s', 'pixva' ), $title ) ) . '">';
			echo pixva_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</button>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="pixva-drawer-hub__panel" hidden><ul>';
			foreach ( $group['items'] as $item ) {
				echo '<li><a href="' . esc_url( isset( $item['url'] ) ? $item['url'] : home_url( '/' ) ) . '">' . esc_html( isset( $item['title'] ) ? $item['title'] : '' ) . '</a></li>';
			}
			if ( ! empty( $group['cta']['url'] ) ) {
				echo '<li class="pixva-drawer-hub__cta"><a href="' . esc_url( (string) $group['cta']['url'] ) . '">' . esc_html( isset( $group['cta']['title'] ) ? (string) $group['cta']['title'] : '' ) . '</a></li>';
			}
			echo '</ul></div></li>';
		}
		echo '</ul>';
	}
}
