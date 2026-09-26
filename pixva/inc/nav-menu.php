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
			<section class="pixva-card pixva-widget pixva-widget--calc">
				<h3 class="pixva-widget__title"><?php esc_html_e( 'عیب را خودتان قیمت بگیرید', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'بازه هزینه و زمان تعمیر را در چند مرحله ببینید.', 'pixva' ); ?></p>
				<a class="pixva-btn pixva-btn--gradient pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه‌گر هزینه', 'pixva' ); ?></a>
				<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری دستگاه', 'pixva' ); ?></a>
			</section>
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
