<?php
/**
 * ایستگاه کاری تعمیرکار — پنل فرانت‌اند (inc/crm-technician.php)
 *
 * محیط مجزا و تمیز برای تعمیرکاران:
 *  - فهرست پرونده‌های تخصیص‌یافته به خودش با فیلتر وضعیت.
 *  - فرم گزارش فنی: قطعات تعویضی (نام، مشخصات، سریال، تعداد، قیمت)، شرح عیب‌یابی،
 *    اقدامات، زمان صرف‌شده و دستمزد — جمع کل سمت سرور محاسبه می‌شود.
 *  - دکمه «تأیید نهایی و صدور گارانتی» که کارت هولوگرافیک را خودکار می‌سازد.
 *  - گزارش فعالیت پرونده و پیش‌نمایش فاکتور رسمی.
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_crm_my_workload' ) ) {
	/**
	 * آمار کاری تعمیرکار جاری.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array<string, int>
	 */
	function pixva_crm_my_workload( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		$stats = array(
			'open'      => 0,
			'repairing' => 0,
			'qc'        => 0,
			'ready'     => 0,
			'done'      => 0,
			'earnings'  => 0,
			'minutes'   => 0,
		);

		if ( ! $user_id ) {
			return $stats;
		}

		$result = pixva_crm_orders( array( 'technician' => $user_id, 'limit' => 100 ) );
		foreach ( $result['items'] as $post ) {
			$order = pixva_crm_order( $post );
			if ( ! $order ) {
				continue;
			}

			switch ( $order['status'] ) {
				case 'assigned':
					$stats['open']++;
					break;
				case 'repairing':
					$stats['repairing']++;
					$stats['open']++;
					break;
				case 'qc':
					$stats['qc']++;
					$stats['open']++;
					break;
				case 'ready':
					$stats['ready']++;
					break;
				case 'delivered':
					$stats['done']++;
					break;
			}

			$stats['earnings'] += (int) $order['report']['total'];
			$stats['minutes']  += (int) $order['report']['minutes'];
		}

		return $stats;
	}
}

if ( ! function_exists( 'pixva_crm_panel_filters' ) ) {
	/**
	 * فیلترهای وضعیت در پنل تعمیرکار.
	 *
	 * @return array<string, string>
	 */
	function pixva_crm_panel_filters() {
		$filters = array( '' => __( 'همه پرونده‌ها', 'pixva' ) );
		foreach ( pixva_crm_statuses() as $key => $label ) {
			$filters[ $key ] = $label;
		}

		/**
		 * فیلتر فهرست فیلترهای پنل تعمیرکار.
		 *
		 * @param array<string, string> $filters فیلترها.
		 */
		return apply_filters( 'pixva_crm_panel_filters', $filters );
	}
}

if ( ! function_exists( 'pixva_render_technician_panel' ) ) {
	/**
	 * رندر کامل ایستگاه کاری تعمیرکار.
	 *
	 * @param array $args گزینه‌ها: status, limit, title.
	 * @return void
	 */
	function pixva_render_technician_panel( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'status' => '',
				'limit'  => 30,
				'title'  => '',
			)
		);

		if ( ! is_user_logged_in() ) {
			$redirect = (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ? '/' . $GLOBALS['wp']->request : '/' ) );
			echo '<div class="pixva-notice pixva-notice--warning">';
			echo esc_html__( 'برای مشاهده پرونده‌های تخصیص‌یافته باید با حساب تعمیرکار وارد شوید.', 'pixva' );
			echo ' <a class="pixva-btn pixva-btn--primary pixva-btn--sm" href="' . esc_url( wp_login_url( $redirect ) ) . '">' . esc_html__( 'ورود به پنل تعمیرکار', 'pixva' ) . '</a>';
			echo '</div>';
			return;
		}

		if ( ! current_user_can( 'pixva_view_crm_panel' ) ) {
			echo '<p class="pixva-notice pixva-notice--error">' . esc_html__( 'حساب کاربری شما دسترسی ایستگاه کاری تعمیرکار ندارد؛ با مدیر کارگاه هماهنگ کنید.', 'pixva' ) . '</p>';
			return;
		}

		$user_id  = get_current_user_id();
		$is_admin = pixva_crm_can_dispatch();
		$stats    = pixva_crm_my_workload( $user_id );
		$title    = '' !== $args['title'] ? $args['title'] : __( 'ایستگاه کاری تعمیرکار', 'pixva' );

		$query_args = array(
			'limit' => (int) $args['limit'],
			'status'=> sanitize_key( (string) $args['status'] ),
		);
		if ( ! $is_admin ) {
			$query_args['technician'] = $user_id;
		}

		// فیلتر وضعیت از کوئری‌استرینگ (بدون JS هم کار می‌کند).
		$requested = isset( $_GET['crm_status'] ) ? sanitize_key( wp_unslash( $_GET['crm_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $requested && array_key_exists( $requested, pixva_crm_statuses() ) ) {
			$query_args['status'] = $requested;
		}

		$result = pixva_crm_orders( $query_args );
		?>
		<div class="pixva-crm pixva-crm--technician" data-crm-panel data-crm-view="technician" data-crm-role="<?php echo $is_admin ? 'manager' : 'technician'; ?>">

			<header class="pixva-crm__head">
				<div>
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'پنل تعمیرکار', 'pixva' ); ?></span>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p class="pixva-muted">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: نام کاربر */
								__( 'سلام %s؛ پرونده‌های زیر به شما تخصیص داده شده‌اند.', 'pixva' ),
								pixva_crm_user_label( $user_id )
							)
						);
						?>
					</p>
				</div>
				<dl class="pixva-crm__kpis">
					<div><dt><?php esc_html_e( 'پرونده باز', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['open'] ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'در حال تعمیر', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['repairing'] ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'تست کیفیت', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['qc'] ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'گارانتی صادرشده', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( (string) $stats['done'] ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'کارکرد ریالی', 'pixva' ); ?></dt><dd><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $stats['earnings'] ) : pixva_fa_num( (string) $stats['earnings'] ) ); ?></dd></div>
				</dl>
			</header>

			<nav class="pixva-crm__filters" aria-label="<?php esc_attr_e( 'فیلتر وضعیت پرونده‌ها', 'pixva' ); ?>">
				<?php foreach ( pixva_crm_panel_filters() as $key => $label ) : ?>
					<a class="pixva-crm__chip<?php echo $query_args['status'] === $key ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'crm_status', $key ) ); ?>"
						data-crm-filter="<?php echo esc_attr( (string) $key ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<p class="pixva-notice" data-crm-message role="status" aria-live="polite" hidden></p>

			<?php if ( empty( $result['items'] ) ) : ?>
				<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'پرونده‌ای در این وضعیت ندارید. با تغییر فیلتر یا مراجعه بعدی دوباره بررسی کنید.', 'pixva' ); ?></p>
			<?php else : ?>
				<div class="pixva-crm__list">
					<?php foreach ( $result['items'] as $post ) : ?>
						<?php pixva_render_technician_order( $post ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( $result['pages'] > 1 ) : ?>
					<nav class="pixva-crm__pager" aria-label="<?php esc_attr_e( 'صفحه‌بندی پرونده‌ها', 'pixva' ); ?>">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'total'   => $result['pages'],
									'current' => $result['page'],
									'prev_text' => __( 'قبلی', 'pixva' ),
									'next_text' => __( 'بعدی', 'pixva' ),
								)
							)
						);
						?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_technician_order' ) ) {
	/**
	 * کارت یک پرونده در ایستگاه کاری تعمیرکار (با فرم گزارش و گارانتی).
	 *
	 * @param int|WP_Post $order پرونده.
	 * @return void
	 */
	function pixva_render_technician_order( $order ) {
		$data = pixva_crm_order( $order );
		if ( ! $data ) {
			return;
		}

		$report   = $data['report'];
		$warranty = $data['warranty'];
		$customer = $data['customer'];
		$can_work = current_user_can( 'pixva_edit_order', $data['id'] );
		$can_warr = current_user_can( 'pixva_issue_order_warranty', $data['id'] );
		$next     = pixva_crm_next_statuses( $data['status'] );
		?>
		<article class="pixva-crm-order<?php echo $warranty['serial'] ? ' is-warrantied' : ''; ?>" data-crm-order="<?php echo esc_attr( (string) $data['id'] ); ?>" data-crm-status="<?php echo esc_attr( $data['status'] ); ?>">

			<header class="pixva-crm-order__head">
				<div class="pixva-crm-order__id">
					<strong dir="ltr"><?php echo esc_html( $data['code'] ); ?></strong>
					<span class="pixva-crm-order__status pixva-crm-order__status--<?php echo esc_attr( $data['status'] ); ?>"><?php echo esc_html( $data['statusLabel'] ); ?></span>
				</div>
				<p class="pixva-crm-order__device"><?php echo esc_html( trim( $data['brand'] . ' ' . $data['model'] ) ); ?></p>
				<p class="pixva-crm-order__problem"><?php echo esc_html( $data['problem'] ); ?></p>
			</header>

			<dl class="pixva-crm-order__facts">
				<div><dt><?php esc_html_e( 'مشتری', 'pixva' ); ?></dt><dd><?php echo esc_html( $customer['name'] ? $customer['name'] : $data['name'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'تماس', 'pixva' ); ?></dt><dd dir="ltr"><?php echo esc_html( pixva_fa_num( $data['phone'] ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'برآورد اولیه', 'pixva' ); ?></dt><dd><?php echo esc_html( $data['estimate'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'تخصیص', 'pixva' ); ?></dt><dd><?php echo esc_html( $data['assignedAt'] ? pixva_fa_num( wp_date( 'Y/m/d', $data['assignedAt'] ) ) : '—' ); ?></dd></div>
				<?php if ( ! empty( $customer['zone'] ) || ! empty( $customer['preferred'] ) ) : ?>
					<div><dt><?php esc_html_e( 'مراجعه', 'pixva' ); ?></dt><dd><?php echo esc_html( trim( $customer['zone'] . ' — ' . $customer['preferred'] ) ); ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $customer['address'] ) ) : ?>
					<div><dt><?php esc_html_e( 'آدرس', 'pixva' ); ?></dt><dd><?php echo esc_html( $customer['address'] ); ?></dd></div>
				<?php endif; ?>
			</dl>

			<?php if ( $can_work ) : ?>
				<div class="pixva-crm-order__actions" data-crm-actions>
					<?php foreach ( $next as $status_key ) : ?>
						<button type="button" class="pixva-btn pixva-btn--ghost pixva-btn--sm" data-crm-action="status" data-status="<?php echo esc_attr( $status_key ); ?>">
							<?php echo esc_html( pixva_crm_status_label( $status_key ) ); ?>
						</button>
					<?php endforeach; ?>
					<button type="button" class="pixva-btn pixva-btn--ghost pixva-btn--sm" data-crm-toggle="report-<?php echo esc_attr( (string) $data['id'] ); ?>" aria-expanded="false">
						<?php esc_html_e( 'گزارش فنی', 'pixva' ); ?>
					</button>
					<?php if ( $can_warr && ! $warranty['serial'] ) : ?>
						<button type="button" class="pixva-btn pixva-btn--gradient pixva-btn--sm" data-crm-action="warranty">
							<?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'تأیید نهایی و صدور گارانتی', 'pixva' ); ?></span>
						</button>
					<?php endif; ?>
				</div>

				<form class="pixva-crm-report" data-crm-report id="report-<?php echo esc_attr( (string) $data['id'] ); ?>" hidden>
					<h4><?php esc_html_e( 'گزارش فنی و قطعات تعویضی', 'pixva' ); ?></h4>

					<div class="pixva-crm-report__parts" data-crm-parts>
						<div class="pixva-crm-report__row" data-crm-part-row>
							<input type="text" name="parts[0][name]" placeholder="<?php esc_attr_e( 'قطعه (مثلاً بک‌لایت)', 'pixva' ); ?>" data-crm-part="name">
							<input type="text" name="parts[0][spec]" placeholder="<?php esc_attr_e( 'مشخصات (۶۵ اینچ LG)', 'pixva' ); ?>" data-crm-part="spec">
							<input type="text" name="parts[0][serial]" placeholder="<?php esc_attr_e( 'سریال قطعه', 'pixva' ); ?>" dir="ltr" data-crm-part="serial">
							<input type="number" name="parts[0][qty]" min="1" value="1" aria-label="<?php esc_attr_e( 'تعداد', 'pixva' ); ?>" data-crm-part="qty">
							<input type="number" name="parts[0][price]" min="0" step="1000" value="0" aria-label="<?php esc_attr_e( 'قیمت واحد (تومان)', 'pixva' ); ?>" data-crm-part="price" data-crm-price>
							<button type="button" class="pixva-crm-report__remove" data-crm-remove-part aria-label="<?php esc_attr_e( 'حذف ردیف', 'pixva' ); ?>">×</button>
						</div>
					</div>
					<button type="button" class="pixva-btn pixva-btn--ghost pixva-btn--sm" data-crm-add-part><?php esc_html_e( 'افزودن قطعه', 'pixva' ); ?></button>

					<div class="pixva-field">
						<label for="diagnosis-<?php echo esc_attr( (string) $data['id'] ); ?>"><?php esc_html_e( 'شرح عیب‌یابی', 'pixva' ); ?></label>
						<textarea id="diagnosis-<?php echo esc_attr( (string) $data['id'] ); ?>" name="diagnosis" rows="2" maxlength="600"><?php echo esc_textarea( $report['diagnosis'] ); ?></textarea>
					</div>
					<div class="pixva-field">
						<label for="actions-<?php echo esc_attr( (string) $data['id'] ); ?>"><?php esc_html_e( 'اقدامات انجام‌شده', 'pixva' ); ?></label>
						<textarea id="actions-<?php echo esc_attr( (string) $data['id'] ); ?>" name="actions" rows="2" maxlength="600"><?php echo esc_textarea( $report['actions'] ); ?></textarea>
					</div>

					<div class="pixva-crm-report__numbers">
						<div class="pixva-field">
							<label for="minutes-<?php echo esc_attr( (string) $data['id'] ); ?>"><?php esc_html_e( 'زمان صرف‌شده (دقیقه)', 'pixva' ); ?></label>
							<input type="number" id="minutes-<?php echo esc_attr( (string) $data['id'] ); ?>" name="minutes" min="0" step="5" value="<?php echo esc_attr( (string) $report['minutes'] ); ?>">
						</div>
						<div class="pixva-field">
							<label for="labor-<?php echo esc_attr( (string) $data['id'] ); ?>"><?php esc_html_e( 'دستمزد (تومان)', 'pixva' ); ?></label>
							<input type="number" id="labor-<?php echo esc_attr( (string) $data['id'] ); ?>" name="labor" min="0" step="10000" value="<?php echo esc_attr( (string) $report['labor'] ); ?>" data-crm-labor>
						</div>
						<p class="pixva-crm-report__total"><?php esc_html_e( 'جمع کل:', 'pixva' ); ?> <strong data-crm-total><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $report['total'] ) : pixva_fa_num( (string) $report['total'] ) ); ?></strong></p>
					</div>

					<label class="pixva-crm-report__qc">
						<input type="checkbox" name="qc_passed" value="1" <?php checked( $report['qcPassed'] ); ?>>
						<span><?php esc_html_e( 'تست کیفیت با الگوی کالیبراسیون انجام و قبول شد.', 'pixva' ); ?></span>
					</label>

					<div class="pixva-crm-report__nav">
						<button type="submit" class="pixva-btn pixva-btn--primary pixva-btn--sm" data-crm-action="report"><?php esc_html_e( 'ذخیره گزارش فنی', 'pixva' ); ?></button>
						<?php if ( $report['savedAt'] ) : ?>
							<small class="pixva-muted"><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d H:i', $report['savedAt'] ) ) ); ?></small>
						<?php endif; ?>
					</div>
				</form>
			<?php else : ?>
				<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'این پرونده به شما تخصیص داده نشده است؛ فقط برای مشاهده فهرست شده است.', 'pixva' ); ?></p>
			<?php endif; ?>

			<?php if ( $warranty['serial'] ) : ?>
				<div class="pixva-crm-order__warranty" data-crm-warranty-host>
					<?php pixva_render_warranty_card( $data['id'], array( 'compact' => true ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $data['activity'] ) ) : ?>
				<details class="pixva-crm-order__log">
					<summary><?php esc_html_e( 'گزارش فعالیت پرونده', 'pixva' ); ?></summary>
					<ol class="pixva-crm-timeline">
						<?php foreach ( array_slice( $data['activity'], 0, 10 ) as $row ) : ?>
							<li>
								<time datetime="<?php echo esc_attr( gmdate( 'c', (int) $row['at'] ) ); ?>"><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d H:i', (int) $row['at'] ) ) ); ?></time>
								<span><?php echo esc_html( $row['label'] ); ?></span>
								<em><?php echo esc_html( $row['who'] ); ?></em>
							</li>
						<?php endforeach; ?>
					</ol>
				</details>
			<?php endif; ?>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_crm_next_statuses' ) ) {
	/**
	 * وضعیت‌های مجاز بعدی برای نمایش دکمه‌ها.
	 *
	 * @param string $status وضعیت فعلی.
	 * @return string[]
	 */
	function pixva_crm_next_statuses( $status ) {
		$status    = pixva_crm_normalize_status( $status );
		$map       = pixva_crm_transitions();
		$forward   = isset( $map[ $status ] ) ? $map[ $status ] : array();

		// فقط حرکت رو به جلو برای تعمیرکار (بازگشت با مدیر است).
		$order     = array_keys( pixva_crm_statuses() );
		$index     = array_search( $status, $order, true );
		$allowable = array();
		foreach ( $forward as $candidate ) {
			$candidate_index = array_search( $candidate, $order, true );
			if ( false !== $candidate_index && ( pixva_crm_can_dispatch() || $candidate_index > $index ) ) {
				$allowable[] = $candidate;
			}
		}

		return $allowable;
	}
}
