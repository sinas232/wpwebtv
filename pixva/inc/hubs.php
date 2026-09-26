<?php
/**
 * معماری اطلاعات پیکسوا: پنج هاب تخصصی، مگامنوی شیشه‌ای و نمایه ابزارها
 * (Master Specification v25.0 — inc/hubs.php)
 *
 * - تعریف ۵ هاب چندبرگه‌ای با نشانی‌های ثابت:
 *   /ai-diagnostics ، /pricing-calculator ، /tracking-warranty ، /error-codes ، /parts-b2b
 * - رندر مگامنوی هدر (شیشه‌ای، دسترس‌پذیر با کیبورد) و فهرست هاب‌ها در دروِر موبایل
 * - رندر نمایه ابزارهای هر هاب (کارت‌های ۶۰گانه)
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_hubs' ) ) {
	/**
	 * تعریف پنج هاب تخصصی پیکسوا.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_hubs() {
		$hubs = array(
			'ai-diagnostics'    => array(
				'no'          => 1,
				'slug'        => 'ai-diagnostics',
				'title'       => __( 'هوش مصنوعی و عیب‌یابی', 'pixva' ),
				'short'       => __( 'عیب‌یابی هوشمند', 'pixva' ),
				'description' => __( 'دستیار هوشمند Gemini با آنالیز متن، تصویر و صدا؛ جادوگر ثبت سفارش تعمیر، تستر پیکسل‌سوختگی و اسلایدر قبل/بعد.', 'pixva' ),
				'template'    => 'page-templates/page-hub-ai.php',
				'icon'        => 'ai',
				'from'        => 1,
				'to'          => 10,
			),
			'pricing-calculator' => array(
				'no'          => 2,
				'slug'        => 'pricing-calculator',
				'title'       => __( 'محاسبه‌گر و نرخ‌نامه', 'pixva' ),
				'short'       => __( 'محاسبه‌گر نرخ', 'pixva' ),
				'description' => __( 'محاسبه زنده هزینه تعمیر با نرخ‌نامه مصوب بازار ۱۴۰۵ و کف ۸ میلیون تومان؛ استعلام انبار، تایم‌لاین و اعزام اورژانسی.', 'pixva' ),
				'template'    => 'page-templates/page-hub-pricing.php',
				'icon'        => 'calculator',
				'from'        => 11,
				'to'          => 20,
			),
			'tracking-warranty' => array(
				'no'          => 3,
				'slug'        => 'tracking-warranty',
				'title'       => __( 'پیگیری و گارانتی', 'pixva' ),
				'short'       => __( 'پیگیری و گارانتی', 'pixva' ),
				'description' => __( 'پیگیری زنده پرونده تعمیر، کارت گارانتی دیجیتال با هش امنیتی، استعلام اصالت قطعه، رزرو نوبت و پورتال مشتریان.', 'pixva' ),
				'template'    => 'page-templates/page-hub-tracking.php',
				'icon'        => 'shield',
				'from'        => 21,
				'to'          => 30,
			),
			'error-codes'     => array(
				'no'          => 4,
				'slug'        => 'error-codes',
				'title'       => __( 'کدهای خطا و آموزش', 'pixva' ),
				'short'       => __( 'کدهای خطا', 'pixva' ),
				'description' => __( 'پایگاه چشمک چراغ و کدهای خطا به‌همراه ۱۵ ابزار آموزشی: بسته‌بندی، کالیبراسیون، مصرف برق، استهلاک بک‌لایت و راهنمای پورت‌ها.', 'pixva' ),
				'template'    => 'page-templates/page-error-codes.php',
				'icon'        => 'book',
				'from'        => 31,
				'to'          => 45,
			),
			'parts-b2b'       => array(
				'no'          => 5,
				'slug'        => 'parts-b2b',
				'title'       => __( 'انبار قطعات و B2B', 'pixva' ),
				'short'       => __( 'قطعات و B2B', 'pixva' ),
				'description' => __( 'استعلام موجودی قطعات فابریک، زمان تحویل قطعات وارداتی، خدمات سازمانی هتل‌ها و ارگان‌ها، اشتراک پیکسوا پلاس و گزارش کارشناسی.', 'pixva' ),
				'template'    => 'page-templates/page-hub-parts-b2b.php',
				'icon'        => 'box',
				'from'        => 46,
				'to'          => 60,
			),
		);

		return apply_filters( 'pixva_hubs', $hubs );
	}
}

if ( ! function_exists( 'pixva_hub_url' ) ) {
	/**
	 * نشانی یک هاب (برگه واقعی اگر ساخته شده باشد، در غیر این صورت مسیر ثابت).
	 *
	 * @param string $slug نامک هاب.
	 * @return string
	 */
	function pixva_hub_url( $slug ) {
		return pixva_page_url( $slug );
	}
}

if ( ! function_exists( 'pixva_hub_by_tool' ) ) {
	/**
	 * یافتن هاب یک ابزار بر اساس شناسه.
	 *
	 * @param int $tool_id شناسه ابزار (۱ تا ۶۰).
	 * @return array|null
	 */
	function pixva_hub_by_tool( $tool_id ) {
		$tool_id = (int) $tool_id;
		foreach ( pixva_hubs() as $hub ) {
			if ( $tool_id >= (int) $hub['from'] && $tool_id <= (int) $hub['to'] ) {
				return $hub;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'pixva_current_hub' ) ) {
	/**
	 * تشخیص هاب فعال بر اساس برگه جاری (نامک یا قالب صفحه).
	 *
	 * @return string نامک هاب یا رشته خالی.
	 */
	function pixva_current_hub() {
		if ( ! is_singular() ) {
			return '';
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$slug     = (string) $post->post_name;
		$template = (string) get_post_meta( $post->ID, '_wp_page_template', true );

		foreach ( pixva_hubs() as $key => $hub ) {
			if ( $slug === $key || $slug === $hub['slug'] ) {
				return (string) $key;
			}
			if ( '' !== $template && $template === $hub['template'] ) {
				return (string) $key;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'pixva_hub_tools' ) ) {
	/**
	 * فهرست ابزارهای یک هاب از رجیستری ۶۰ ابزار.
	 *
	 * @param string $hub_slug نامک هاب.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_hub_tools( $hub_slug ) {
		$hub = isset( pixva_hubs()[ $hub_slug ] ) ? pixva_hubs()[ $hub_slug ] : null;
		if ( ! $hub ) {
			return array();
		}
		$tools = function_exists( 'pixva_tools_registry' ) ? pixva_tools_registry() : array();
		$out   = array();
		foreach ( $tools as $id => $tool ) {
			if ( $id >= (int) $hub['from'] && $id <= (int) $hub['to'] ) {
				$out[ $id ] = $tool;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'pixva_render_mega_menu' ) ) {
	/**
	 * رندر مگامنوی شیشه‌ای هدر: ۵ هاب + منوی اصلی وردپرس با آبشاری چندسطحی.
	 *
	 * @param string $id_prefix پیشوند شناسه پنل‌ها (برای دروئر موبایل).
	 * @return void
	 */
	function pixva_render_mega_menu( $id_prefix = 'mega' ) {
		$hubs  = pixva_hubs();
		$index = 0;
		?>
		<nav class="pixva-mega" data-pixva-mega aria-label="<?php esc_attr_e( 'هاب‌های تخصصی پیکسوا', 'pixva' ); ?>">
			<ul class="pixva-mega__list">
				<li class="pixva-mega__item">
					<a class="pixva-mega__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'pixva' ); ?></a>
				</li>
				<?php foreach ( $hubs as $hub ) : ?>
					<?php
					++$index;
					$panel_id = $id_prefix . '-' . $index;
					$tools    = pixva_hub_tools( $hub['slug'] );
					$preview  = array_slice( $tools, 0, 6, true );
					?>
					<li class="pixva-mega__item" data-mega-item>
						<button type="button" class="pixva-mega__trigger" data-mega-trigger aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
							<span><?php echo esc_html( $hub['short'] ); ?></span>
							<svg class="pixva-mega__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
						</button>
						<div class="pixva-mega__panel" id="<?php echo esc_attr( $panel_id ); ?>" data-mega-panel>
							<div class="pixva-container pixva-mega__grid">
								<div class="pixva-mega__col">
									<h4><?php echo esc_html( sprintf( __( 'ابزارهای %d تا %d', 'pixva' ), (int) $hub['from'], min( (int) $hub['from'] + 4, (int) $hub['to'] ) ) ); ?></h4>
									<ul class="pixva-mega__tools">
										<?php foreach ( array_slice( $preview, 0, 3, true ) as $tool_id => $tool ) : ?>
											<li>
												<a href="<?php echo esc_url( pixva_tool_permalink( $tool_id ) ); ?>">
													<span class="pixva-tool-no"><?php echo esc_html( pixva_fa_num( (string) $tool_id ) ); ?></span>
													<span><?php echo esc_html( $tool['title'] ); ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="pixva-mega__col">
									<h4><?php echo esc_html( sprintf( __( 'ابزارهای %d تا %d', 'pixva' ), min( (int) $hub['from'] + 3, (int) $hub['to'] ), (int) $hub['to'] ) ); ?></h4>
									<ul class="pixva-mega__tools">
										<?php foreach ( array_slice( $preview, 3, 3, true ) as $tool_id => $tool ) : ?>
											<li>
												<a href="<?php echo esc_url( pixva_tool_permalink( $tool_id ) ); ?>">
													<span class="pixva-tool-no"><?php echo esc_html( pixva_fa_num( (string) $tool_id ) ); ?></span>
													<span><?php echo esc_html( $tool['title'] ); ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="pixva-mega__col">
									<h4><?php esc_html_e( 'دسترسی سریع', 'pixva' ); ?></h4>
									<ul class="pixva-mega__tools">
										<li><a href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><span><?php esc_html_e( 'محاسبه هزینه تعمیر', 'pixva' ); ?></span></a></li>
										<li><a href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><span><?php esc_html_e( 'پیگیری دستگاه', 'pixva' ); ?></span></a></li>
										<li><a href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>"><span><?php esc_html_e( 'نرخ‌نامه ۱۴۰۵', 'pixva' ); ?></span></a></li>
										<li><a href="<?php echo esc_url( pixva_page_url( 'contact' ) ); ?>"><span><?php esc_html_e( 'تماس با کارگاه', 'pixva' ); ?></span></a></li>
									</ul>
								</div>
								<aside class="pixva-mega__feature">
									<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( sprintf( __( 'هاب %d از ۵', 'pixva' ), (int) $hub['no'] ) ); ?></span>
									<h3><?php echo esc_html( $hub['title'] ); ?></h3>
									<p><?php echo esc_html( $hub['description'] ); ?></p>
									<a class="pixva-btn pixva-btn--primary pixva-btn--sm" href="<?php echo esc_url( pixva_hub_url( $hub['slug'] ) ); ?>"><?php esc_html_e( 'ورود به هاب', 'pixva' ); ?></a>
									<span class="pixva-mega__more"><?php echo esc_html( sprintf( __( '%d ابزار تخصصی', 'pixva' ), count( $tools ) ) ); ?></span>
								</aside>
							</div>
						</div>
					</li>
				<?php endforeach; ?>

				<?php
				// منوی اصلی وردپرس با آبشاری شیشه‌ای چندسطحی (بدون محدودیت عمق).
				pixva_render_nav_items( pixva_menu_tree( 'primary' ), $id_prefix . '-nav' );
				?>
			</ul>
		</nav>
		<?php
	}
}

if ( ! function_exists( 'pixva_primary_menu_items' ) ) {
	/**
	 * آیتم‌های سطح اول منوی اصلی وردپرس به‌جز برگه‌هایی که در مگامنو هستند.
	 *
	 * @return array<int, array{title:string, url:string}>
	 */
	function pixva_primary_menu_items() {
		$locations = get_nav_menu_locations();
		if ( empty( $locations['primary'] ) ) {
			return array();
		}
		$menu = wp_get_nav_menu_object( $locations['primary'] );
		if ( ! $menu ) {
			return array();
		}
		$items    = wp_get_nav_menu_items( $menu->term_id );
		$skip_ids = array();
		foreach ( pixva_hubs() as $hub ) {
			$page = get_page_by_path( $hub['slug'] );
			if ( $page instanceof WP_Post ) {
				$skip_ids[] = (int) $page->ID;
			}
		}
		$home_id  = (int) get_option( 'page_on_front' );
		$skip_ids = array_merge( $skip_ids, array( $home_id ) );

		$out = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( (int) $item->menu_item_parent !== 0 || in_array( (int) $item->object_id, $skip_ids, true ) ) {
					continue;
				}
				$title = trim( (string) $item->title );
				if ( '' === $title || empty( $item->url ) ) {
					continue;
				}
				$out[] = array(
					'title' => $title,
					'url'   => (string) $item->url,
				);
				if ( count( $out ) >= 4 ) {
					break;
				}
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'pixva_tool_permalink' ) ) {
	/**
	 * نشانی مستقیم یک ابزار: برگه هاب + لنگر ابزار.
	 *
	 * @param int $tool_id شناسه ابزار.
	 * @return string
	 */
	function pixva_tool_permalink( $tool_id ) {
		$tool_id = (int) $tool_id;
		$hub     = pixva_hub_by_tool( $tool_id );
		if ( ! $hub ) {
			return home_url( '/' );
		}
		return pixva_hub_url( $hub['slug'] ) . '#pixva-tool-' . $tool_id;
	}
}

if ( ! function_exists( 'pixva_render_hub_index' ) ) {
	/**
	 * نمایه کارت‌های ابزار یک هاب (برای صفحه‌های هاب و برگه‌های المنتوری).
	 *
	 * @param string $hub_slug نامک هاب.
	 * @param string $title    عنوان بخش (اختیاری).
	 * @return void
	 */
	function pixva_render_hub_index( $hub_slug, $title = '' ) {
		$tools = pixva_hub_tools( $hub_slug );
		if ( empty( $tools ) ) {
			return;
		}
		$hub = isset( pixva_hubs()[ $hub_slug ] ) ? pixva_hubs()[ $hub_slug ] : null;
		?>
		<section class="pixva-section pixva-hub-index" id="hub-<?php echo esc_attr( $hub_slug ); ?>">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( sprintf( __( 'هاب %d', 'pixva' ), $hub ? (int) $hub['no'] : 0 ) ); ?></span>
					<h2><?php echo esc_html( $title ? $title : ( $hub ? $hub['title'] : '' ) ); ?></h2>
					<p><?php echo esc_html( $hub ? $hub['description'] : '' ); ?></p>
				</div>
				<div class="pixva-tool-grid">
					<?php foreach ( $tools as $tool_id => $tool ) : ?>
						<a class="pixva-tool-card pixva-reveal" href="<?php echo esc_url( pixva_tool_permalink( $tool_id ) ); ?>" data-tool-id="<?php echo esc_attr( $tool_id ); ?>">
							<span class="pixva-tool-card__head">
								<span class="pixva-tool-card__no"><?php echo esc_html( pixva_fa_num( (string) $tool_id ) ); ?></span>
								<h3><?php echo esc_html( $tool['title'] ); ?></h3>
							</span>
							<p><?php echo esc_html( $tool['summary'] ); ?></p>
							<span class="pixva-tool-card__meta">
								<span class="pixva-badge"><?php echo esc_html( $tool['group'] ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_hub_tools' ) ) {
	/**
	 * رندر آکاردئونی ابزارهای یک هاب در صفحه هاب (ابزارها با context inline).
	 *
	 * هر پنل شناسه #pixva-tool-N دارد تا پیوندهای مگامنو و کارت‌های نمایه
	 * مستقیماً به همان ابزار برسند.
	 *
	 * @param string $hub_slug   نامک هاب.
	 * @param bool   $open_first باز بودن نخستین ابزار به‌صورت پیش‌فرض.
	 * @return void
	 */
	function pixva_render_hub_tools( $hub_slug, $open_first = true ) {
		$tools = pixva_hub_tools( $hub_slug );
		if ( empty( $tools ) || ! function_exists( 'pixva_render_tool_by_id' ) ) {
			return;
		}
		$hub   = isset( pixva_hubs()[ $hub_slug ] ) ? pixva_hubs()[ $hub_slug ] : null;
		$first = true;
		?>
		<section class="pixva-section pixva-section--alt pixva-hub-tools" id="hub-tools-<?php echo esc_attr( $hub_slug ); ?>">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( sprintf( __( 'ابزار %1$d تا %2$d', 'pixva' ), $hub ? (int) $hub['from'] : 0, $hub ? (int) $hub['to'] : 0 ) ); ?></span>
					<h2><?php esc_html_e( 'ابزارهای این هاب را باز کنید', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'هر ابزار به‌صورت مستقل کار می‌کند؛ داده‌ها از موتور نرخ‌نامه، انبار قطعات و سامانه پیگیری پرونده می‌آید.', 'pixva' ); ?></p>
				</div>

				<div class="pixva-accordion pixva-accordion--tools">
					<?php foreach ( $tools as $tool_id => $tool ) : ?>
						<?php
						$panel_id = 'hub-tool-panel-' . (int) $tool_id;
						$is_open  = $open_first && $first;
						$first    = false;
						?>
						<article class="pixva-accordion__item<?php echo $is_open ? ' is-open' : ''; ?>" id="pixva-tool-<?php echo esc_attr( (int) $tool_id ); ?>" data-pixva-accordion-item>
							<h3 class="pixva-accordion__heading">
								<button type="button" class="pixva-accordion__toggle" data-pixva-accordion aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
									<span class="pixva-tool-card__no"><?php echo esc_html( pixva_fa_num( (string) $tool_id ) ); ?></span>
									<span class="pixva-accordion__text">
										<strong><?php echo esc_html( $tool['title'] ); ?></strong>
										<small><?php echo esc_html( $tool['summary'] ); ?></small>
									</span>
									<span class="pixva-faq__icon" aria-hidden="true"></span>
								</button>
							</h3>
							<div id="<?php echo esc_attr( $panel_id ); ?>" class="pixva-accordion__panel"<?php echo $is_open ? '' : ' hidden'; ?>>
								<?php echo pixva_render_tool_by_id( (int) $tool_id, 'inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_hub_accordions' ) ) {
	/**
	 * فهرست آکاردئونی هاب‌ها برای دروئر موبایل.
	 *
	 * @param string $id_prefix پیشوند شناسه‌ها.
	 * @return void
	 */
	function pixva_render_hub_accordions( $id_prefix = 'drawer-hub' ) {
		$index = 0;
		echo '<ul class="pixva-nav__list pixva-drawer-hubs">';
		foreach ( pixva_hubs() as $hub ) {
			++$index;
			$panel_id = $id_prefix . '-' . $index;
			echo '<li class="pixva-drawer-hub" data-pixva-accordion-item>';
			echo '<a class="pixva-drawer-hub__title" href="' . esc_url( pixva_hub_url( $hub['slug'] ) ) . '">' . esc_html( $hub['title'] ) . '</a>';
			echo '<button type="button" class="pixva-drawer-hub__toggle" data-pixva-accordion aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '" aria-label="' . esc_attr( sprintf( __( 'ابزارهای %s', 'pixva' ), $hub['title'] ) ) . '>';
			echo pixva_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</button>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="pixva-drawer-hub__panel" hidden><ul>';
			foreach ( pixva_hub_tools( $hub['slug'] ) as $tool_id => $tool ) {
				echo '<li><a href="' . esc_url( pixva_tool_permalink( $tool_id ) ) . '"><span class="pixva-tool-no">' . esc_html( pixva_fa_num( (string) $tool_id ) ) . '</span> ' . esc_html( $tool['title'] ) . '</a></li>';
			}
			echo '</ul></div></li>';
		}
		echo '</ul>';
	}
}
