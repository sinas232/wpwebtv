<?php
/**
 * پنل مدیریت کل / دیسپچر سفارشات (inc/crm-dispatcher.php)
 *
 * صفحه پیشخوان برای مدیر عملیات (نقش pixva_manager) و مدیر کل:
 *  - آمار زنده صف (پرونده‌های بدون تخصیص، در حال تعمیر، تست کیفیت، آماده تحویل، کارکرد ریالی).
 *  - جدول سفارشات با فیلتر وضعیت/تعمیرکار/جست‌وجو و صفحه‌بندی.
 *  - ارجاع (Assign) به تعمیرکار از فهرست معرفی‌شده + تغییر وضعیت هوشمند با گذارهای مجاز.
 *  - نمای جزئیات هر پرونده: گزارش فنی، فعالیت‌ها، فاکتور و کارت گارانتی.
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_crm_admin_menu' ) ) {
	/**
	 * ثبت منوی دیسپچ در پیشخوان (برای مدیر کل و مدیر عملیات).
	 *
	 * @return void
	 */
	function pixva_crm_admin_menu() {
		add_menu_page(
			esc_html__( 'دیسپچ و اتوماسیون تعمیرات پیکسوا', 'pixva' ),
			esc_html__( 'دیسپچ سفارشات', 'pixva' ),
			'pixva_view_crm_panel',
			'pixva-dispatch',
			'pixva_render_dispatcher_page',
			'dashicons-hammer',
			4
		);

		add_submenu_page(
			'pixva-dispatch',
			esc_html__( 'صف پرونده‌ها', 'pixva' ),
			esc_html__( 'صف پرونده‌ها', 'pixva' ),
			'pixva_view_crm_panel',
			'pixva-dispatch',
			'pixva_render_dispatcher_page'
		);

		add_submenu_page(
			'pixva-dispatch',
			esc_html__( 'تعمیرکاران و بار کاری', 'pixva' ),
			esc_html__( 'تعمیرکاران', 'pixva' ),
			'pixva_dispatch_orders',
			'pixva-dispatch&tab=team',
			'pixva_render_dispatcher_page'
		);

		add_submenu_page(
			'pixva-dispatch',
			esc_html__( 'گارانتی‌های صادرشده', 'pixva' ),
			esc_html__( 'گارانتی‌ها', 'pixva' ),
			'pixva_view_crm_panel',
			'pixva-dispatch&tab=warranties',
			'pixva_render_dispatcher_page'
		);

		add_submenu_page(
			'pixva-dispatch',
			esc_html__( 'گزارش پیامک‌ها', 'pixva' ),
			esc_html__( 'پیامک‌ها', 'pixva' ),
			'pixva_dispatch_orders',
			'pixva-dispatch&tab=sms',
			'pixva_render_dispatcher_page'
		);
	}
}
add_action( 'admin_menu', 'pixva_crm_admin_menu', 11 );

if ( ! function_exists( 'pixva_crm_admin_tabs' ) ) {
	/**
	 * تب‌های پنل دیسپچ.
	 *
	 * @return array<string, string>
	 */
	function pixva_crm_admin_tabs() {
		return array(
			'queue'      => __( 'صف پرونده‌ها', 'pixva' ),
			'team'       => __( 'تعمیرکاران', 'pixva' ),
			'warranties' => __( 'گارانتی‌ها', 'pixva' ),
			'sms'        => __( 'پیامک‌ها', 'pixva' ),
		);
	}
}

if ( ! function_exists( 'pixva_crm_admin_assets' ) ) {
	/**
	 * بارگذاری دارایی‌های پنل دیسپچ فقط روی همان صفحه.
	 *
	 * @param string $hook هوک صفحه.
	 * @return void
	 */
	function pixva_crm_admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'pixva-dispatch' ) ) {
			return;
		}

		wp_enqueue_style( 'pixva-style', get_stylesheet_uri(), array(), PIXVA_VERSION );
		wp_enqueue_style( 'pixva-2026', PIXVA_URI . '/assets/css/pixva-2026.css', array( 'pixva-style' ), PIXVA_VERSION );

		wp_enqueue_script(
			'pixva-crm',
			PIXVA_URI . '/assets/js/crm-engine.js',
			array(),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script( 'pixva-crm', 'pixvaCrm', pixva_crm_localize() );
	}
}
add_action( 'admin_enqueue_scripts', 'pixva_crm_admin_assets' );

if ( ! function_exists( 'pixva_crm_admin_body_class' ) ) {
	/**
	 * افزودن کلاس اختصاصی به body پنل دیسپچ.
	 *
	 * چون pixva-2026.css قواعد سراسری body دارد، این کلاس اجازه می‌دهد لایه ۲۷
	 * همان قواعد را برای محیط پیشخوان خنثی کند (پس‌زمینه و تایپوگرافی وردپرس).
	 *
	 * @param string $classes کلاس‌های body.
	 * @return string
	 */
	function pixva_crm_admin_body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && false !== strpos( (string) $screen->id, 'pixva-dispatch' ) ) {
			$classes .= ' pixva-crm-admin';
		}

		return $classes;
	}
}
add_filter( 'admin_body_class', 'pixva_crm_admin_body_class' );

if ( ! function_exists( 'pixva_render_dispatcher_page' ) ) {
	/**
	 * رندر صفحه دیسپچ در پیشخوان.
	 *
	 * @return void
	 */
	function pixva_render_dispatcher_page() {
		if ( ! current_user_can( 'pixva_view_crm_panel' ) ) {
			wp_die( esc_html__( 'اجازه دسترسی به پنل دیسپچ را ندارید.', 'pixva' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- فقط فیلتر نمایشی (GET).
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'queue';
		$status = isset( $_GET['crm_status'] ) ? sanitize_key( wp_unslash( $_GET['crm_status'] ) ) : '';
		$tech   = isset( $_GET['crm_tech'] ) ? (int) $_GET['crm_tech'] : 0;
		$search = isset( $_GET['crm_q'] ) ? sanitize_text_field( wp_unslash( $_GET['crm_q'] ) ) : '';
		$paged  = isset( $_GET['crm_page'] ) ? max( 1, (int) $_GET['crm_page'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! array_key_exists( $tab, pixva_crm_admin_tabs() ) ) {
			$tab = 'queue';
		}

		$stats = pixva_crm_stats();
		?>
		<div class="wrap pixva-crm pixva-crm--admin" data-crm-panel data-crm-view="dispatch" data-crm-role="manager">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'دیسپچ و اتوماسیون چرخه تعمیرات', 'pixva' ); ?></h1>
			<hr class="wp-header-end">

			<nav class="nav-tab-wrapper pixva-crm__tabs">
				<?php foreach ( pixva_crm_admin_tabs() as $key => $label ) : ?>
					<a class="nav-tab<?php echo $tab === $key ? ' nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-dispatch&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<p class="pixva-notice" data-crm-message role="status" aria-live="polite" hidden></p>

			<?php
			switch ( $tab ) {
				case 'team':
					pixva_render_dispatcher_team();
					break;
				case 'warranties':
					pixva_render_dispatcher_warranties();
					break;
				case 'sms':
					pixva_render_dispatcher_sms();
					break;
				default:
					pixva_render_dispatcher_queue( $stats, $status, $tech, $search, $paged );
					break;
			}
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatcher_queue' ) ) {
	/**
	 * تب صف پرونده‌ها: آمار + فیلتر + جدول تخصیص.
	 *
	 * @param array  $stats  آمار کارگاه.
	 * @param string $status فیلتر وضعیت.
	 * @param int    $tech   فیلتر تعمیرکار.
	 * @param string $search جست‌وجو.
	 * @param int    $paged  صفحه.
	 * @return void
	 */
	function pixva_render_dispatcher_queue( $stats, $status = '', $tech = 0, $search = '', $paged = 1 ) {
		$result = pixva_crm_orders(
			array(
				'status'     => $status,
				'technician' => (int) $tech,
				'search'     => $search,
				'limit'      => 25,
				'page'       => (int) $paged,
			)
		);
		?>
		<div class="pixva-crm__kpis pixva-crm__kpis--admin">
			<div><dt><?php esc_html_e( 'کل پرونده‌ها', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['total'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'در انتظار بررسی', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['counts']['pending'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'در حال تعمیر', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['counts']['repairing'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'تست کیفیت', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['counts']['qc'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'آماده تحویل', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['counts']['ready'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'گارانتی صادرشده', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['warranties'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'کارکرد ریالی', 'pixva' ); ?></dt><dd><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $stats['revenue'] ) : pixva_fa_num( (string) $stats['revenue'] ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'میانگین زمان تعمیر', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) round( $stats['avgMinutes'] / 60, 1 ) ) ); ?> <?php esc_html_e( 'ساعت', 'pixva' ); ?></dd></div>
		</div>

		<form class="pixva-crm__filters pixva-crm__filters--admin" method="get" action="">
			<input type="hidden" name="page" value="pixva-dispatch">
			<input type="hidden" name="tab" value="queue">

			<label>
				<span><?php esc_html_e( 'وضعیت', 'pixva' ); ?></span>
				<select name="crm_status">
					<option value=""><?php esc_html_e( 'همه وضعیت‌ها', 'pixva' ); ?></option>
					<?php echo pixva_crm_status_options( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</select>
			</label>

			<label>
				<span><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></span>
				<select name="crm_tech">
					<option value="0"><?php esc_html_e( 'همه تعمیرکاران', 'pixva' ); ?></option>
					<?php echo pixva_crm_technician_options( (int) $tech ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</select>
			</label>

			<label>
				<span><?php esc_html_e( 'جست‌وجو', 'pixva' ); ?></span>
				<input type="search" name="crm_q" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'کد پیگیری، برند یا شماره', 'pixva' ); ?>">
			</label>

			<button type="submit" class="button button-primary"><?php esc_html_e( 'اعمال فیلتر', 'pixva' ); ?></button>
		</form>

		<?php if ( empty( $result['items'] ) ) : ?>
			<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'پرونده‌ای با این فیلترها پیدا نشد.', 'pixva' ); ?></p>
		<?php else : ?>
			<table class="widefat striped pixva-crm__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'کد', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'مشتری', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'دستگاه / خرابی', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'برآورد', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'گزارش فنی', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'گارانتی', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'عملیات', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['items'] as $post ) : ?>
						<?php pixva_render_dispatcher_row( $post ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $result['pages'] > 1 ) : ?>
				<p class="pixva-crm__pager">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'crm_page', '%#%' ),
								'total'     => $result['pages'],
								'current'   => $result['page'],
								'prev_text' => __( 'قبلی', 'pixva' ),
								'next_text' => __( 'بعدی', 'pixva' ),
							)
						)
					);
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatcher_row' ) ) {
	/**
	 * یک ردیف جدول دیسپچ با کنترل‌های تخصیص و وضعیت.
	 *
	 * @param WP_Post $post پرونده.
	 * @return void
	 */
	function pixva_render_dispatcher_row( $post ) {
		$data     = pixva_crm_order( $post );
		if ( ! $data ) {
			return;
		}
		$report   = $data['report'];
		$warranty = $data['warranty'];
		$customer = $data['customer'];
		?>
		<tr class="pixva-crm-row" data-crm-order="<?php echo esc_attr( (string) $data['id'] ); ?>" data-crm-status="<?php echo esc_attr( $data['status'] ); ?>">
			<td><strong dir="ltr"><?php echo esc_html( $data['code'] ); ?></strong><br><small><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $data['created'] ) ) ); ?></small></td>
			<td>
				<?php echo esc_html( $customer['name'] ? $customer['name'] : $data['name'] ); ?><br>
				<small dir="ltr"><?php echo esc_html( pixva_fa_num( $data['phone'] ) ); ?></small>
				<?php if ( ! empty( $customer['zone'] ) ) : ?><br><small><?php echo esc_html( $customer['zone'] ); ?></small><?php endif; ?>
			</td>
			<td><?php echo esc_html( trim( $data['brand'] . ' ' . $data['model'] ) ); ?><br><small><?php echo esc_html( $data['problem'] ); ?></small></td>
			<td><small><?php echo esc_html( $data['estimate'] ); ?></small></td>
			<td>
				<select data-crm-field="technician" aria-label="<?php esc_attr_e( 'تخصیص تعمیرکار', 'pixva' ); ?>">
					<?php echo pixva_crm_technician_options( $data['technician'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</select>
			</td>
			<td>
				<select data-crm-field="status" aria-label="<?php esc_attr_e( 'وضعیت پرونده', 'pixva' ); ?>">
					<?php echo pixva_crm_status_options( $data['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</select>
			</td>
			<td>
				<?php if ( $report['savedAt'] ) : ?>
					<small><?php echo esc_html( pixva_fa_num( (string) count( $report['parts'] ) ) ); ?> <?php esc_html_e( 'قطعه', 'pixva' ); ?><br>
					<?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $report['total'] ) : pixva_fa_num( (string) $report['total'] ) ); ?><br>
					<?php echo esc_html( pixva_fa_num( (string) $report['minutes'] ) ); ?> <?php esc_html_e( 'دقیقه', 'pixva' ); ?></small>
				<?php else : ?>
					<small class="pixva-muted"><?php esc_html_e( 'ثبت نشده', 'pixva' ); ?></small>
				<?php endif; ?>
			</td>
			<td>
				<?php if ( $warranty['serial'] ) : ?>
					<strong dir="ltr"><?php echo esc_html( $warranty['serial'] ); ?></strong><br>
					<small><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ) ); ?></small>
				<?php else : ?>
					<small class="pixva-muted">—</small>
				<?php endif; ?>
			</td>
			<td>
				<button type="button" class="button button-primary" data-crm-action="save"><?php esc_html_e( 'ذخیره تخصیص', 'pixva' ); ?></button>
				<button type="button" class="button" data-crm-toggle="dispatch-<?php echo esc_attr( (string) $data['id'] ); ?>" aria-expanded="false"><?php esc_html_e( 'جزئیات', 'pixva' ); ?></button>
				<div class="pixva-crm-row__details" id="dispatch-<?php echo esc_attr( (string) $data['id'] ); ?>" hidden>
					<?php if ( $report['diagnosis'] ) : ?><p><strong><?php esc_html_e( 'عیب‌یابی:', 'pixva' ); ?></strong> <?php echo esc_html( $report['diagnosis'] ); ?></p><?php endif; ?>
					<?php if ( $report['actions'] ) : ?><p><strong><?php esc_html_e( 'اقدامات:', 'pixva' ); ?></strong> <?php echo esc_html( $report['actions'] ); ?></p><?php endif; ?>
					<?php if ( $customer['address'] ) : ?><p><strong><?php esc_html_e( 'آدرس:', 'pixva' ); ?></strong> <?php echo esc_html( $customer['address'] ); ?></p><?php endif; ?>
					<?php if ( $data['notes'] ) : ?><p><strong><?php esc_html_e( 'یادداشت:', 'pixva' ); ?></strong> <?php echo esc_html( $data['notes'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $data['activity'] ) ) : ?>
						<ol class="pixva-crm-timeline pixva-crm-timeline--mini">
							<?php foreach ( array_slice( $data['activity'], 0, 6 ) as $row ) : ?>
								<li><time><?php echo esc_html( pixva_fa_num( wp_date( 'm/d H:i', (int) $row['at'] ) ) ); ?></time> <?php echo esc_html( $row['label'] ); ?> <em>(<?php echo esc_html( $row['who'] ); ?>)</em></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
					<?php if ( $warranty['serial'] ) : ?>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-dispatch&tab=warranties&serial=' . rawurlencode( $warranty['serial'] ) ) ); ?>"><?php esc_html_e( 'کارت گارانتی و فاکتور', 'pixva' ); ?></a></p>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatcher_team' ) ) {
	/**
	 * تب تعمیرکاران: بار کاری و تخصص هر تکنسین.
	 *
	 * @return void
	 */
	function pixva_render_dispatcher_team() {
		$technicians = pixva_crm_technicians();

		if ( empty( $technicians ) ) {
			echo '<p class="pixva-notice pixva-notice--warning">' . esc_html__( 'هیچ کاربری با نقش «تکنسین تعمیرگاه پیکسوا» وجود ندارد. از بخش کاربران یک حساب با این نقش بسازید.', 'pixva' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped pixva-crm__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'نام کاربری', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'تماس', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'تخصص', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'پرونده باز', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'عملیات', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $technicians as $tech ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $tech['name'] ); ?></strong></td>
						<td dir="ltr"><?php echo esc_html( $tech['login'] ); ?></td>
						<td dir="ltr"><?php echo esc_html( $tech['phone'] ? pixva_fa_num( $tech['phone'] ) : '—' ); ?></td>
						<td><?php echo esc_html( $tech['skill'] ? $tech['skill'] : '—' ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( (string) $tech['openJobs'] ) ); ?></td>
						<td>
							<a class="button" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $tech['id'] ) ); ?>"><?php esc_html_e( 'ویرایش حساب', 'pixva' ); ?></a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-dispatch&tab=queue&crm_tech=' . $tech['id'] ) ); ?>"><?php esc_html_e( 'پرونده‌های او', 'pixva' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'تخصص و شماره تماس هر تعمیرکار از فیلدهای پروفایل کاربری (pixva_tech_skill و pixva_tech_phone) خوانده می‌شود.', 'pixva' ); ?></p>
		<?php
	}
}

if ( ! function_exists( 'pixva_crm_team_profile_fields' ) ) {
	/**
	 * افزودن فیلد تخصص و تماس تعمیرکار به پروفایل کاربری.
	 *
	 * @param WP_User $user کاربر جاری.
	 * @return void
	 */
	function pixva_crm_team_profile_fields( $user ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		wp_nonce_field( 'pixva_crm_profile', 'pixva_crm_profile_nonce' );
		?>
		<h2><?php esc_html_e( 'اطلاعات تعمیرکار پیکسوا', 'pixva' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="pixva_tech_phone"><?php esc_html_e( 'شماره تماس تعمیرکار', 'pixva' ); ?></label></th>
				<td><input type="text" name="pixva_tech_phone" id="pixva_tech_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pixva_tech_phone', true ) ); ?>" class="regular-text" dir="ltr"></td>
			</tr>
			<tr>
				<th><label for="pixva_tech_skill"><?php esc_html_e( 'تخصص', 'pixva' ); ?></label></th>
				<td><input type="text" name="pixva_tech_skill" id="pixva_tech_skill" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pixva_tech_skill', true ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'بندینگ پنل، برد پاور، OLED', 'pixva' ); ?>"></td>
			</tr>
		</table>
		<?php
	}
}
add_action( 'show_user_profile', 'pixva_crm_team_profile_fields' );
add_action( 'edit_user_profile', 'pixva_crm_team_profile_fields' );

if ( ! function_exists( 'pixva_crm_save_team_profile' ) ) {
	/**
	 * ذخیره فیلدهای پروفایل تعمیرکار.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return void
	 */
	function pixva_crm_save_team_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( ! isset( $_POST['pixva_crm_profile_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_crm_profile_nonce'] ) ), 'pixva_crm_profile' ) ) {
			return;
		}

		if ( isset( $_POST['pixva_tech_phone'] ) ) {
			update_user_meta( $user_id, 'pixva_tech_phone', pixva_normalize_mobile( wp_unslash( $_POST['pixva_tech_phone'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		if ( isset( $_POST['pixva_tech_skill'] ) ) {
			update_user_meta( $user_id, 'pixva_tech_skill', sanitize_text_field( wp_unslash( $_POST['pixva_tech_skill'] ) ) );
		}
	}
}
add_action( 'personal_options_update', 'pixva_crm_save_team_profile' );
add_action( 'edit_user_profile_update', 'pixva_crm_save_team_profile' );

if ( ! function_exists( 'pixva_render_dispatcher_warranties' ) ) {
	/**
	 * تب گارانتی‌ها: فهرست کارت‌های صادرشده + نمایش کارت/فاکتور یک سریال.
	 *
	 * @return void
	 */
	function pixva_render_dispatcher_warranties() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$serial = isset( $_GET['serial'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['serial'] ) ) ) : '';

		if ( '' !== $serial ) {
			$warranty = pixva_crm_warranty_of( $serial );
			if ( ! $warranty ) {
				echo '<p class="pixva-notice pixva-notice--error">' . esc_html__( 'گارانتی‌ای با این سریال پیدا نشد.', 'pixva' ) . '</p>';
				return;
			}

			$ids = get_posts(
				array(
					'post_type'      => 'pixva_orders',
					'post_status'    => array( 'private', 'publish' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_pixva_order_warranty_serial',
							'value' => $serial,
						),
					),
				)
			);

			if ( ! empty( $ids ) ) {
				echo '<div class="pixva-crm__card-wrap">';
				pixva_render_warranty_card( (int) $ids[0] );
				pixva_render_invoice( (int) $ids[0] );
				echo '</div>';
			}
			return;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 60,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_pixva_order_warranty_serial',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $ids ) ) {
			echo '<p class="pixva-notice pixva-notice--info">' . esc_html__( 'هنوز گارانتی دیجیتال صادر نشده است.', 'pixva' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped pixva-crm__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'سریال گارانتی', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'پرونده', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'صدور', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'انقضا', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'کارت', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $ids as $id ) {
					$data = pixva_crm_order( (int) $id );
					if ( ! $data || empty( $data['warranty']['serial'] ) ) {
						continue;
					}
					$warranty = $data['warranty'];
					$valid    = $warranty['expiresAt'] > time();
					?>
					<tr>
						<td dir="ltr"><strong><?php echo esc_html( $warranty['serial'] ); ?></strong></td>
						<td dir="ltr"><?php echo esc_html( $data['code'] ); ?></td>
						<td><?php echo esc_html( $warranty['techName'] ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['issuedAt'] ) ) ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ) ); ?></td>
						<td><?php echo $valid ? esc_html__( 'معتبر', 'pixva' ) : esc_html__( 'منقضی', 'pixva' ); ?></td>
						<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-dispatch&tab=warranties&serial=' . rawurlencode( $warranty['serial'] ) ) ); ?>"><?php esc_html_e( 'نمایش کارت و فاکتور', 'pixva' ); ?></a></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatcher_sms' ) ) {
	/**
	 * تب پیامک‌ها: آخرین پیام‌های ارسالی/در صف و وضعیت درگاه.
	 *
	 * @return void
	 */
	function pixva_render_dispatcher_sms() {
		$opts = pixva_sms_options();
		$log  = get_option( 'pixva_sms_log', array() );
		$log  = is_array( $log ) ? array_reverse( $log ) : array();
		?>
		<p class="pixva-notice pixva-notice--info">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: نام درگاه پیامک */
					__( 'درگاه فعال: %s — برای ارسال خودکار، کلید API و شماره ارسال‌کننده را در مرکز کنترل پیکسوا وارد کنید.', 'pixva' ),
					'none' === $opts['provider'] ? __( 'غیرفعال (پیام‌ها در صف ثبت می‌شوند)', 'pixva' ) : $opts['provider']
				)
			);
			?>
		</p>

		<?php if ( empty( $log ) ) : ?>
			<p class="pixva-notice"><?php esc_html_e( 'هنوز پیامکی ثبت نشده است.', 'pixva' ); ?></p>
		<?php else : ?>
			<table class="widefat striped pixva-crm__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'زمان', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'شماره', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'رویداد', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'متن', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $log, 0, 40 ) as $row ) : ?>
						<tr>
							<td><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d H:i', (int) $row['at'] ) ) ); ?></td>
							<td dir="ltr"><?php echo esc_html( pixva_fa_num( (string) $row['to'] ) ); ?></td>
							<td><?php echo esc_html( (string) $row['context'] ); ?></td>
							<td><small><?php echo esc_html( (string) $row['message'] ); ?></small></td>
							<td><?php echo esc_html( (string) $row['state'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_crm_admin_bar_shortcut' ) ) {
	/**
	 * میان‌بر دیسپچ در نوار ابزار وردپرس (برای مدیران واردشده).
	 *
	 * @param WP_Admin_Bar $bar نوار ابزار.
	 * @return void
	 */
	function pixva_crm_admin_bar_shortcut( $bar ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'pixva_view_crm_panel' ) ) {
			return;
		}

		$pending = pixva_crm_stats();

		$bar->add_node(
			array(
				'id'    => 'pixva-dispatch',
				'title' => sprintf(
					/* translators: %s: تعداد پرونده در صف */
					esc_html__( 'دیسپچ پیکسوا (%s)', 'pixva' ),
					pixva_fa_num( (string) $pending['unassigned'] )
				),
				'href'  => admin_url( 'admin.php?page=pixva-dispatch' ),
			)
		);

		if ( ! pixva_crm_can_dispatch() ) {
			$bar->add_node(
				array(
					'parent' => 'pixva-dispatch',
					'id'     => 'pixva-technician-panel',
					'title'  => esc_html__( 'ایستگاه کاری من', 'pixva' ),
					'href'   => pixva_crm_panel_url(),
				)
			);
		}
	}
}
add_action( 'admin_bar_menu', 'pixva_crm_admin_bar_shortcut', 90 );
