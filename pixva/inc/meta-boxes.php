<?php
/**
 * متاباکس‌های بومی وردپرس (بدون ACF) — لایه ۱٫۵٫۰
 *
 * همه فیلدهای پرونده تعمیر (pixva_orders) با توابع ناتیو وردپرس ساخته می‌شوند:
 * add_meta_box() برای رابط، register_post_meta() برای قرارداد داده و
 * update_post_meta() برای ذخیره. ذخیره‌سازی از طریق API موتور CRM انجام می‌شود
 * تا تایم‌لاین فعالیت، پیامک‌ها و گارانتی دیجیتال همگام بمانند:
 *
 *  - pixva_crm_assign()          تخصیص به تعمیرکار
 *  - pixva_crm_set_status()      انتقال وضعیت با ثبت رویداد
 *  - pixva_crm_save_report()     گزارش فنی، قطعات تعویضی و جمع هزینه
 *  - pixva_crm_issue_warranty()  صدور گارانتی ۱۸۰ روزه با سریال و اثرانگشت
 *
 * @package Pixva
 * @since   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * ۱) قرارداد داده: ثبت متاباکس‌ها و متاها
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_register' ) ) {
	/**
	 * ثبت متاباکس‌های پرونده تعمیر در پیشخوان.
	 *
	 * @return void
	 */
	function pixva_mb_register() {
		add_meta_box(
			'pixva_mb_customer',
			esc_html__( 'مشخصات مشتری و دستگاه', 'pixva' ),
			'pixva_mb_render_customer',
			'pixva_orders',
			'normal',
			'high'
		);

		add_meta_box(
			'pixva_mb_report',
			esc_html__( 'گزارش فنی و قطعات تعویضی', 'pixva' ),
			'pixva_mb_render_report',
			'pixva_orders',
			'normal',
			'default'
		);

		add_meta_box(
			'pixva_mb_warranty',
			esc_html__( 'گارانتی دیجیتال ۱۸۰ روزه', 'pixva' ),
			'pixva_mb_render_warranty',
			'pixva_orders',
			'normal',
			'default'
		);

		add_meta_box(
			'pixva_mb_dispatch',
			esc_html__( 'تخصیص و وضعیت پرونده', 'pixva' ),
			'pixva_mb_render_dispatch',
			'pixva_orders',
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'pixva_mb_register' );

if ( ! function_exists( 'pixva_mb_field_map' ) ) {
	/**
	 * نگاشت فیلدهای ساده فرم به کلید متا به‌همراه روش پاک‌سازی.
	 *
	 * @return array<string, array{0:string, 1:string}>
	 */
	function pixva_mb_field_map() {
		return array(
			'pixva_mb_name'     => array( '_pixva_order_name', 'text' ),
			'pixva_mb_phone'    => array( '_pixva_order_phone', 'text' ),
			'pixva_mb_brand'    => array( '_pixva_order_brand', 'text' ),
			'pixva_mb_model'    => array( '_pixva_order_model', 'text' ),
			'pixva_mb_problem'  => array( '_pixva_order_problem', 'text' ),
			'pixva_mb_estimate' => array( '_pixva_order_estimate', 'text' ),
			'pixva_mb_notes'    => array( '_pixva_order_notes', 'textarea' ),
		);
	}
}

if ( ! function_exists( 'pixva_mb_register_meta' ) ) {
	/**
	 * ثبت متاهای پرونده تا قرارداد داده برای REST و ابزارهای بیرونی روشن باشد.
	 *
	 * @return void
	 */
	function pixva_mb_register_meta() {
		$text_keys = array(
			'_pixva_order_code',
			'_pixva_order_name',
			'_pixva_order_phone',
			'_pixva_order_brand',
			'_pixva_order_model',
			'_pixva_order_problem',
			'_pixva_order_estimate',
			'_pixva_order_notes',
			'_pixva_order_status',
			'_pixva_order_technician',
			'_pixva_order_warranty_serial',
			'_pixva_order_source',
		);

		foreach ( $text_keys as $key ) {
			register_post_meta(
				'pixva_orders',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => current_user_can( 'pixva_view_crm_panel' ),
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_pixva_orders' );
					},
				)
			);
		}

		$json_keys = array(
			'_pixva_order_customer',
			'_pixva_order_report',
			'_pixva_order_warranty',
			'_pixva_order_activity',
			'_pixva_order_steps',
		);

		foreach ( $json_keys as $key ) {
			register_post_meta(
				'pixva_orders',
				$key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => static function () {
						return current_user_can( 'edit_pixva_orders' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'pixva_mb_register_meta', 20 );

/*
 * ---------------------------------------------------------------------------
 * ۲) ابزارهای کمکی رابط
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_styles' ) ) {
	/**
	 * سبک سبک و فشرده متاباکس‌ها (بدون فایل CSS اضافه در پیشخوان).
	 *
	 * @return void
	 */
	function pixva_mb_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'pixva_orders' !== $screen->post_type ) {
			return;
		}
		?>
		<style>
			.pixva-mb { direction: rtl; }
			.pixva-mb__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
			.pixva-mb__field { display: grid; gap: 4px; }
			.pixva-mb__field > label { font-weight: 600; font-size: 12px; color: rgb(60, 67, 74); }
			.pixva-mb__field input, .pixva-mb__field select, .pixva-mb__field textarea { width: 100%; }
			.pixva-mb__hint { margin: 4px 0 0; font-size: 12px; color: rgb(100, 116, 139); }
			.pixva-mb__readonly { background: rgb(246, 247, 247); border-color: rgb(220, 224, 228); color: rgb(80, 87, 94); }
			.pixva-mb__parts { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
			.pixva-mb__parts th { font-size: 12px; text-align: right; padding: 6px; background: rgb(246, 247, 247); border-bottom: 1px solid rgb(220, 224, 228); }
			.pixva-mb__parts td { padding: 4px; vertical-align: middle; }
			.pixva-mb__parts input { width: 100%; }
			.pixva-mb__parts .pixva-mb__num { max-width: 90px; }
			.pixva-mb__remove { color: rgb(179, 27, 27); border-color: rgb(220, 130, 130); }
			.pixva-mb__total { display: inline-block; padding: 6px 12px; border-radius: 8px; background: rgb(240, 246, 252); border: 1px solid rgb(190, 214, 236); font-weight: 700; }
			.pixva-mb__timeline { margin: 8px 0 0; padding: 0 18px 0 0; font-size: 12px; color: rgb(80, 87, 94); }
			.pixva-mb__timeline li { margin-bottom: 4px; }
			.pixva-mb__badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: rgb(237, 246, 240); border: 1px solid rgb(178, 214, 194); color: rgb(24, 92, 58); font-size: 12px; }
			.pixva-mb__row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 8px; }
		</style>
		<?php
	}
}
add_action( 'admin_head', 'pixva_mb_styles' );

if ( ! function_exists( 'pixva_mb_field' ) ) {
	/**
	 * رندر یک فیلد استاندارد.
	 *
	 * @param string $id       شناسه و نام فیلد.
	 * @param string $label    برچسب.
	 * @param string $value    مقدار.
	 * @param array  $args     گزینه‌ها: type, rows, readonly, dir, hint, class.
	 * @return void
	 */
	function pixva_mb_field( $id, $label, $value, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'     => 'text',
				'rows'     => 3,
				'readonly' => false,
				'dir'      => '',
				'hint'     => '',
				'class'    => 'regular-text',
			)
		);

		$attrs = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '"';
		$attrs .= $args['dir'] ? ' dir="' . esc_attr( $args['dir'] ) . '"' : '';
		$attrs .= $args['readonly'] ? ' readonly' : '';

		$class = trim( $args['class'] . ( $args['readonly'] ? ' pixva-mb__readonly' : '' ) );
		?>
		<div class="pixva-mb__field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php if ( 'textarea' === $args['type'] ) : ?>
				<textarea<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> rows="<?php echo esc_attr( (string) $args['rows'] ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_textarea( (string) $value ); ?></textarea>
			<?php else : ?>
				<input type="<?php echo esc_attr( $args['type'] ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> value="<?php echo esc_attr( (string) $value ); ?>" class="<?php echo esc_attr( $class ); ?>">
			<?php endif; ?>
			<?php if ( '' !== $args['hint'] ) : ?>
				<p class="pixva-mb__hint"><?php echo esc_html( $args['hint'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) متاباکس مشخصات مشتری و دستگاه
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_render_customer' ) ) {
	/**
	 * رندر پنل مشخصات مشتری و دستگاه.
	 *
	 * @param WP_Post $post پرونده جاری.
	 * @return void
	 */
	function pixva_mb_render_customer( $post ) {
		wp_nonce_field( 'pixva_mb_order', 'pixva_mb_nonce' );

		$customer = get_post_meta( $post->ID, '_pixva_order_customer', true );
		$customer = is_array( $customer ) ? $customer : array();
		$code     = (string) get_post_meta( $post->ID, '_pixva_order_code', true );
		$source   = (string) get_post_meta( $post->ID, '_pixva_order_source', true );
		?>
		<div class="pixva-mb">
			<div class="pixva-mb__grid">
				<?php
				pixva_mb_field( 'pixva_mb_code', __( 'کد پیگیری پرونده', 'pixva' ), $code, array( 'readonly' => true, 'dir' => 'ltr', 'hint' => __( 'این کد هنگام ثبت سفارش ساخته می‌شود و تغییرپذیر نیست.', 'pixva' ) ) );
				pixva_mb_field( 'pixva_mb_name', __( 'نام مشتری', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_name', true ) );
				pixva_mb_field( 'pixva_mb_phone', __( 'شماره همراه', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_phone', true ), array( 'dir' => 'ltr' ) );
				pixva_mb_field( 'pixva_mb_zone', __( 'منطقه', 'pixva' ), isset( $customer['zone'] ) ? (string) $customer['zone'] : '' );
				pixva_mb_field( 'pixva_mb_preferred', __( 'زمان مراجعه', 'pixva' ), isset( $customer['preferred'] ) ? (string) $customer['preferred'] : '' );
				pixva_mb_field( 'pixva_mb_address', __( 'آدرس', 'pixva' ), isset( $customer['address'] ) ? (string) $customer['address'] : '' );
				pixva_mb_field( 'pixva_mb_brand', __( 'برند دستگاه', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_brand', true ) );
				pixva_mb_field( 'pixva_mb_model', __( 'مدل دستگاه', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_model', true ), array( 'dir' => 'ltr', 'hint' => __( 'مثلاً UA55AU7000 یا OLED55C2', 'pixva' ) ) );
				pixva_mb_field( 'pixva_mb_estimate', __( 'برآورد اولیه (تومان)', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_estimate', true ) );
				pixva_mb_field( 'pixva_mb_source', __( 'منبع ثبت', 'pixva' ), $source, array( 'readonly' => true ) );
				?>
			</div>
			<div class="pixva-mb__grid" style="margin-top:12px">
				<?php
				pixva_mb_field( 'pixva_mb_problem', __( 'ایراد اعلامی مشتری', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_problem', true ), array( 'type' => 'textarea', 'rows' => 2 ) );
				pixva_mb_field( 'pixva_mb_notes', __( 'یادداشت داخلی کارگاه', 'pixva' ), (string) get_post_meta( $post->ID, '_pixva_order_notes', true ), array( 'type' => 'textarea', 'rows' => 2 ) );
				?>
			</div>
		</div>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۴) متاباکس گزارش فنی و قطعات تعویضی
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_render_report' ) ) {
	/**
	 * رندر فرم گزارش فنی با ردیف‌های قطعات تعویضی.
	 *
	 * @param WP_Post $post پرونده جاری.
	 * @return void
	 */
	function pixva_mb_render_report( $post ) {
		$report = get_post_meta( $post->ID, '_pixva_order_report', true );
		$report = is_array( $report ) ? $report : array();
		$parts  = isset( $report['parts'] ) && is_array( $report['parts'] ) ? $report['parts'] : array();

		if ( empty( $parts ) ) {
			$parts = array(
				array(
					'name'   => '',
					'spec'   => '',
					'serial' => '',
					'qty'    => 1,
					'price'  => 0,
				),
			);
		}

		$labor = isset( $report['labor'] ) ? (int) $report['labor'] : 0;
		$total = isset( $report['total'] ) ? (int) $report['total'] : 0;
		?>
		<div class="pixva-mb" data-pixva-mb-report>
			<table class="pixva-mb__parts">
				<thead>
					<tr>
						<th><?php esc_html_e( 'قطعه تعویضی', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'مشخصات', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'شماره سریال', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'تعداد', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'قیمت واحد (تومان)', 'pixva' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody data-pixva-mb-parts>
					<?php foreach ( $parts as $index => $part ) : ?>
						<tr data-pixva-mb-row>
							<td><input type="text" name="pixva_mb_parts[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( isset( $part['name'] ) ? (string) $part['name'] : '' ); ?>" placeholder="<?php esc_attr_e( 'بک‌لایت', 'pixva' ); ?>"></td>
							<td><input type="text" name="pixva_mb_parts[<?php echo esc_attr( (string) $index ); ?>][spec]" value="<?php echo esc_attr( isset( $part['spec'] ) ? (string) $part['spec'] : '' ); ?>" placeholder="<?php esc_attr_e( '۶۵ اینچ LG', 'pixva' ); ?>"></td>
							<td><input type="text" dir="ltr" name="pixva_mb_parts[<?php echo esc_attr( (string) $index ); ?>][serial]" value="<?php echo esc_attr( isset( $part['serial'] ) ? (string) $part['serial'] : '' ); ?>"></td>
							<td class="pixva-mb__num"><input type="number" min="1" step="1" data-pixva-mb-qty name="pixva_mb_parts[<?php echo esc_attr( (string) $index ); ?>][qty]" value="<?php echo esc_attr( isset( $part['qty'] ) ? (string) (int) $part['qty'] : '1' ); ?>"></td>
							<td class="pixva-mb__num"><input type="number" min="0" step="1000" data-pixva-mb-price name="pixva_mb_parts[<?php echo esc_attr( (string) $index ); ?>][price]" value="<?php echo esc_attr( isset( $part['price'] ) ? (string) (int) $part['price'] : '0' ); ?>"></td>
							<td><button type="button" class="button pixva-mb__remove" data-pixva-mb-remove>&times;</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" data-pixva-mb-add><?php esc_html_e( 'افزودن قطعه', 'pixva' ); ?></button>
			</p>

			<div class="pixva-mb__grid">
				<?php
				pixva_mb_field( 'pixva_mb_diagnosis', __( 'شرح عیب‌یابی', 'pixva' ), isset( $report['diagnosis'] ) ? (string) $report['diagnosis'] : '', array( 'type' => 'textarea', 'rows' => 2 ) );
				pixva_mb_field( 'pixva_mb_actions', __( 'اقدامات انجام‌شده', 'pixva' ), isset( $report['actions'] ) ? (string) $report['actions'] : '', array( 'type' => 'textarea', 'rows' => 2 ) );
				pixva_mb_field( 'pixva_mb_minutes', __( 'زمان صرف‌شده (دقیقه)', 'pixva' ), isset( $report['minutes'] ) ? (string) (int) $report['minutes'] : '0', array( 'type' => 'number' ) );
				pixva_mb_field( 'pixva_mb_labor', __( 'دستمزد تخصصی (تومان)', 'pixva' ), (string) $labor, array( 'type' => 'number' ) );
				?>
			</div>

			<p class="pixva-mb__row">
				<label for="pixva_mb_qc">
					<input type="checkbox" id="pixva_mb_qc" name="pixva_mb_qc" value="1" <?php checked( ! empty( $report['qcPassed'] ) ); ?>>
					<?php esc_html_e( 'تست کیفیت با الگوی کالیبراسیون انجام و قبول شد.', 'pixva' ); ?>
				</label>
			</p>

			<p class="pixva-mb__row">
				<span class="pixva-mb__total"><?php esc_html_e( 'جمع کل قابل پرداخت:', 'pixva' ); ?> <strong data-pixva-mb-total><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $total ) : number_format_i18n( $total ) ); ?></strong></span>
				<?php if ( ! empty( $report['savedAt'] ) ) : ?>
					<span class="pixva-mb__hint"><?php echo esc_html( sprintf( /* translators: %s: تاریخ ذخیره */ __( 'آخرین ذخیره گزارش: %s', 'pixva' ), function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d H:i', (int) $report['savedAt'] ) ) : wp_date( 'Y/m/d H:i', (int) $report['savedAt'] ) ) ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<script>
		(function () {
			var host = document.querySelector('[data-pixva-mb-report]');
			if (!host) { return; }
			var body = host.querySelector('[data-pixva-mb-parts]');
			var totalBox = host.querySelector('[data-pixva-mb-total]');
			var labor = host.querySelector('[id="pixva_mb_labor"]');

			function sum() {
				var parts = 0;
				Array.prototype.forEach.call(body.querySelectorAll('[data-pixva-mb-row]'), function (row) {
					var qty = parseInt(row.querySelector('[data-pixva-mb-qty]').value, 10) || 0;
					var price = parseInt(row.querySelector('[data-pixva-mb-price]').value, 10) || 0;
					parts += qty * price;
				});
				var laborValue = labor ? (parseInt(labor.value, 10) || 0) : 0;
				if (totalBox) { totalBox.textContent = (parts + laborValue).toLocaleString('en-US'); }
			}

			function reindex() {
				Array.prototype.forEach.call(body.querySelectorAll('[data-pixva-mb-row]'), function (row, index) {
					Array.prototype.forEach.call(row.querySelectorAll('input'), function (input) {
						input.name = input.name.replace(/pixva_mb_parts\[\d+\]/, 'pixva_mb_parts[' + index + ']');
					});
				});
				sum();
			}

			host.addEventListener('click', function (event) {
				var add = event.target.closest('[data-pixva-mb-add]');
				var remove = event.target.closest('[data-pixva-mb-remove]');

				if (add) {
					var first = body.querySelector('[data-pixva-mb-row]');
					if (!first) { return; }
					var clone = first.cloneNode(true);
					Array.prototype.forEach.call(clone.querySelectorAll('input'), function (input) {
						input.value = input.type === 'number' ? (input.getAttribute('data-pixva-mb-qty') !== null ? '1' : '0') : '';
					});
					body.appendChild(clone);
					reindex();
				}

				if (remove) {
					var rows = body.querySelectorAll('[data-pixva-mb-row]');
					if (rows.length <= 1) { return; }
					remove.closest('[data-pixva-mb-row]').remove();
					reindex();
				}
			});

			host.addEventListener('input', sum);
			sum();
		}());
		</script>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۵) متاباکس گارانتی دیجیتال
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_render_warranty' ) ) {
	/**
	 * رندر پنل گارانتی: سریال، اعتبار و صدور.
	 *
	 * @param WP_Post $post پرونده جاری.
	 * @return void
	 */
	function pixva_mb_render_warranty( $post ) {
		$warranty = get_post_meta( $post->ID, '_pixva_order_warranty', true );
		$warranty = is_array( $warranty ) ? $warranty : array();
		$serial   = (string) get_post_meta( $post->ID, '_pixva_order_warranty_serial', true );
		$days     = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;

		if ( ! $serial && ! empty( $warranty['serial'] ) ) {
			$serial = (string) $warranty['serial'];
		}
		?>
		<div class="pixva-mb">
			<div class="pixva-mb__grid">
				<?php
				pixva_mb_field(
					'pixva_mb_serial',
					__( 'شماره سریال گارانتی', 'pixva' ),
					$serial,
					array(
						'readonly' => true,
						'dir'      => 'ltr',
						'hint'     => $serial ? __( 'سریال صادرشده تغییرپذیر نیست.', 'pixva' ) : __( 'پس از صدور گارانتی، سریال یکتا اینجا نمایش داده می‌شود.', 'pixva' ),
					)
				);

				pixva_mb_field(
					'pixva_mb_warranty_days',
					__( 'مدت اعتبار (روز)', 'pixva' ),
					(string) ( ! empty( $warranty['days'] ) ? (int) $warranty['days'] : $days ),
					array( 'readonly' => true )
				);
				?>
			</div>

			<?php if ( ! empty( $warranty['issuedAt'] ) ) : ?>
				<p class="pixva-mb__row">
					<span class="pixva-mb__badge">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: تاریخ صدور, 2: تاریخ انقضا. */
								__( 'صادرشده در %1$s — اعتبار تا %2$s', 'pixva' ),
								function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d', (int) $warranty['issuedAt'] ) ) : wp_date( 'Y/m/d', (int) $warranty['issuedAt'] ),
								function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d', (int) $warranty['expiresAt'] ) ) : wp_date( 'Y/m/d', (int) $warranty['expiresAt'] )
							)
						);
						?>
					</span>
					<?php if ( ! empty( $warranty['techName'] ) ) : ?>
						<span class="pixva-mb__hint"><?php echo esc_html( sprintf( /* translators: %s: نام تعمیرکار */ __( 'تعمیرکار مسئول: %s', 'pixva' ), $warranty['techName'] ) ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $warranty['covers'] ) && is_array( $warranty['covers'] ) ) : ?>
				<p class="pixva-mb__hint"><?php echo esc_html( __( 'پوشش:', 'pixva' ) . ' ' . implode( '، ', array_map( 'sanitize_text_field', $warranty['covers'] ) ) ); ?></p>
			<?php endif; ?>

			<p class="pixva-mb__row">
				<label for="pixva_mb_issue_warranty">
					<input type="checkbox" id="pixva_mb_issue_warranty" name="pixva_mb_issue_warranty" value="1" <?php checked( '' === $serial ); ?>>
					<?php esc_html_e( 'صدور گارانتی دیجیتال (پس از ذخیره گزارش فنی)', 'pixva' ); ?>
				</label>
			</p>
			<p class="pixva-mb__hint">
				<?php esc_html_e( 'با صدور گارانتی، سریال یکتا، اثرانگشت دیجیتال، کارت هولوگرافیک و فاکتور رسمی قابل چاپ ساخته می‌شود و وضعیت پرونده به «آماده تحویل» می‌رسد.', 'pixva' ); ?>
			</p>
		</div>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۶) متاباکس تخصیص و وضعیت (ستون کناری)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_render_dispatch' ) ) {
	/**
	 * رندر پنل تخصیص تعمیرکار و انتقال وضعیت.
	 *
	 * @param WP_Post $post پرونده جاری.
	 * @return void
	 */
	function pixva_mb_render_dispatch( $post ) {
		$technicians = function_exists( 'pixva_crm_technicians' ) ? pixva_crm_technicians() : array();
		$statuses    = function_exists( 'pixva_crm_statuses' ) ? pixva_crm_statuses() : array();
		$current     = (string) get_post_meta( $post->ID, '_pixva_order_status', true );
		$tech_id     = (int) get_post_meta( $post->ID, '_pixva_order_technician', true );
		$assigned_at = (int) get_post_meta( $post->ID, '_pixva_order_assigned_at', true );
		$activity    = get_post_meta( $post->ID, '_pixva_order_activity', true );
		$activity    = is_array( $activity ) ? $activity : array();
		?>
		<div class="pixva-mb">
			<p>
				<label for="pixva_mb_technician"><strong><?php esc_html_e( 'تعمیرکار مسئول', 'pixva' ); ?></strong></label><br>
				<select id="pixva_mb_technician" name="pixva_mb_technician" style="width:100%">
					<option value="0"><?php esc_html_e( '— تخصیص نیافته —', 'pixva' ); ?></option>
					<?php foreach ( $technicians as $tech ) : ?>
						<option value="<?php echo esc_attr( (string) $tech['id'] ); ?>" <?php selected( $tech_id, (int) $tech['id'] ); ?>>
							<?php echo esc_html( $tech['name'] . ( isset( $tech['openJobs'] ) ? ' (' . $tech['openJobs'] . ')' : '' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="pixva_mb_status"><strong><?php esc_html_e( 'وضعیت سفارش', 'pixva' ); ?></strong></label><br>
				<select id="pixva_mb_status" name="pixva_mb_status" style="width:100%">
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="pixva_mb_note"><strong><?php esc_html_e( 'یادداشت انتقال وضعیت', 'pixva' ); ?></strong></label><br>
				<textarea id="pixva_mb_note" name="pixva_mb_note" rows="2" style="width:100%" placeholder="<?php esc_attr_e( 'اختیاری — در تایم‌لاین پرونده ثبت می‌شود.', 'pixva' ); ?>"></textarea>
			</p>

			<?php if ( $assigned_at ) : ?>
				<p class="pixva-mb__hint">
					<?php echo esc_html( sprintf( /* translators: %s: تاریخ تخصیص */ __( 'تخصیص‌یافته در: %s', 'pixva' ), function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d H:i', $assigned_at ) ) : wp_date( 'Y/m/d H:i', $assigned_at ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $activity ) ) : ?>
				<p><strong><?php esc_html_e( 'آخرین رویدادها', 'pixva' ); ?></strong></p>
				<ol class="pixva-mb__timeline">
					<?php foreach ( array_slice( array_reverse( $activity ), 0, 6 ) as $row ) : ?>
						<li>
							<?php
							$label = isset( $row['label'] ) ? (string) $row['label'] : ( isset( $row['status'] ) ? (string) $row['status'] : '' );
							echo esc_html( $label );
							if ( ! empty( $row['at'] ) ) {
								echo ' — ' . esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'm/d H:i', (int) $row['at'] ) ) : wp_date( 'm/d H:i', (int) $row['at'] ) );
							}
							?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<p class="pixva-mb__hint">
				<?php esc_html_e( 'ذخیره این پرونده، تخصیص و وضعیت را از طریق موتور CRM اعمال می‌کند و در صورت نیاز پیامک اطلاع‌رسانی می‌فرستد.', 'pixva' ); ?>
			</p>
		</div>
		<?php
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۷) ذخیره‌سازی از طریق API موتور CRM
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_save' ) ) {
	/**
	 * ذخیره متاباکس‌های پرونده تعمیر.
	 *
	 * @param int $post_id شناسه پرونده.
	 * @return void
	 */
	function pixva_mb_save( $post_id ) {
		$post_id = (int) $post_id;

		if ( ! isset( $_POST['pixva_mb_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_mb_nonce'] ) ), 'pixva_mb_order' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'pixva_orders' !== get_post_type( $post_id ) ) {
			return;
		}

		// ۱) فیلدهای ساده مشتری و دستگاه.
		foreach ( pixva_mb_field_map() as $field => $map ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$raw   = wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = 'textarea' === $map[1] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
			update_post_meta( $post_id, $map[0], $value );
		}

		// ۲) اطلاعات تکمیلی مشتری (منطقه، زمان مراجعه، آدرس).
		$customer = get_post_meta( $post_id, '_pixva_order_customer', true );
		$customer = is_array( $customer ) ? $customer : array();

		if ( isset( $_POST['pixva_mb_zone'] ) ) {
			$customer['zone'] = sanitize_text_field( wp_unslash( $_POST['pixva_mb_zone'] ) );
		}
		if ( isset( $_POST['pixva_mb_preferred'] ) ) {
			$customer['preferred'] = sanitize_text_field( wp_unslash( $_POST['pixva_mb_preferred'] ) );
		}
		if ( isset( $_POST['pixva_mb_address'] ) ) {
			$customer['address'] = sanitize_textarea_field( wp_unslash( $_POST['pixva_mb_address'] ) );
		}
		if ( isset( $customer['name'] ) || isset( $_POST['pixva_mb_name'] ) ) {
			$customer['name'] = isset( $_POST['pixva_mb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pixva_mb_name'] ) ) : ( isset( $customer['name'] ) ? $customer['name'] : '' );
		}

		update_post_meta( $post_id, '_pixva_order_customer', $customer );

		// ۳) تخصیص به تعمیرکار.
		if ( isset( $_POST['pixva_mb_technician'] ) && function_exists( 'pixva_crm_assign' ) ) {
			$tech_id = (int) $_POST['pixva_mb_technician'];
			$current = (int) get_post_meta( $post_id, '_pixva_order_technician', true );

			if ( $tech_id !== $current ) {
				pixva_crm_assign( $post_id, $tech_id );
			}
		}

		// ۴) گزارش فنی و قطعات تعویضی.
		if ( isset( $_POST['pixva_mb_labor'] ) && function_exists( 'pixva_crm_save_report' ) && pixva_crm_can_work_on( $post_id ) ) {
			$parts = array();

			if ( isset( $_POST['pixva_mb_parts'] ) && is_array( $_POST['pixva_mb_parts'] ) ) {
				foreach ( wp_unslash( $_POST['pixva_mb_parts'] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( ! is_array( $row ) ) {
						continue;
					}
					$parts[] = array(
						'name'   => isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '',
						'spec'   => isset( $row['spec'] ) ? sanitize_text_field( $row['spec'] ) : '',
						'serial' => isset( $row['serial'] ) ? sanitize_text_field( $row['serial'] ) : '',
						'qty'    => isset( $row['qty'] ) ? (int) $row['qty'] : 1,
						'price'  => isset( $row['price'] ) ? (int) $row['price'] : 0,
					);
				}
			}

			pixva_crm_save_report(
				$post_id,
				array(
					'parts'     => $parts,
					'diagnosis' => isset( $_POST['pixva_mb_diagnosis'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pixva_mb_diagnosis'] ) ) : '',
					'actions'   => isset( $_POST['pixva_mb_actions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pixva_mb_actions'] ) ) : '',
					'minutes'   => isset( $_POST['pixva_mb_minutes'] ) ? (int) $_POST['pixva_mb_minutes'] : 0,
					'labor'     => (int) $_POST['pixva_mb_labor'],
					'qc_passed' => ! empty( $_POST['pixva_mb_qc'] ),
				)
			);
		}

		// ۵) انتقال وضعیت (با ثبت رویداد و اطلاع‌رسانی).
		if ( isset( $_POST['pixva_mb_status'] ) && function_exists( 'pixva_crm_set_status' ) ) {
			$status = sanitize_key( wp_unslash( $_POST['pixva_mb_status'] ) );
			$note   = isset( $_POST['pixva_mb_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pixva_mb_note'] ) ) : '';
			pixva_crm_set_status( $post_id, $status, $note );
		}

		// ۶) صدور گارانتی دیجیتال.
		if ( ! empty( $_POST['pixva_mb_issue_warranty'] ) && function_exists( 'pixva_crm_issue_warranty' ) ) {
			pixva_crm_issue_warranty( $post_id );
		}

		/**
		 * پس از ذخیره متاباکس‌های پرونده.
		 *
		 * @param int $post_id شناسه پرونده.
		 */
		do_action( 'pixva_mb_order_saved', $post_id );
	}
}
add_action( 'save_post_pixva_orders', 'pixva_mb_save', 20 );

/*
 * ---------------------------------------------------------------------------
 * ۸) ستون‌های سریع در فهرست پرونده‌ها
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_mb_order_columns' ) ) {
	/**
	 * افزودن ستون‌های کاربردی به فهرست پرونده‌ها.
	 *
	 * @param array<string, string> $columns ستون‌ها.
	 * @return array<string, string>
	 */
	function pixva_mb_order_columns( $columns ) {
		$extra = array(
			'pixva_code'  => __( 'کد پیگیری', 'pixva' ),
			'pixva_tech'  => __( 'تعمیرکار', 'pixva' ),
			'pixva_total' => __( 'جمع کل', 'pixva' ),
			'pixva_warr'  => __( 'گارانتی', 'pixva' ),
		);

		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out = array_merge( $out, $extra );
			}
		}

		return $out;
	}
}
add_filter( 'manage_pixva_orders_posts_columns', 'pixva_mb_order_columns' );

if ( ! function_exists( 'pixva_mb_order_column_content' ) ) {
	/**
	 * پرکردن محتوای ستون‌های سفارشی.
	 *
	 * @param string $column  کلید ستون.
	 * @param int    $post_id شناسه پرونده.
	 * @return void
	 */
	function pixva_mb_order_column_content( $column, $post_id ) {
		if ( 'pixva_code' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_pixva_order_code', true ) );
			return;
		}

		if ( 'pixva_tech' === $column ) {
			$tech_id = (int) get_post_meta( $post_id, '_pixva_order_technician', true );
			echo $tech_id ? esc_html( function_exists( 'pixva_crm_user_label' ) ? pixva_crm_user_label( $tech_id ) : get_the_author_meta( 'display_name', $tech_id ) ) : esc_html__( 'تخصیص نیافته', 'pixva' );
			return;
		}

		if ( 'pixva_total' === $column ) {
			$report = get_post_meta( $post_id, '_pixva_order_report', true );
			$total  = is_array( $report ) && isset( $report['total'] ) ? (int) $report['total'] : 0;
			echo esc_html( $total ? ( function_exists( 'pixva_price' ) ? pixva_price( $total ) : number_format_i18n( $total ) ) : '—' );
			return;
		}

		if ( 'pixva_warr' === $column ) {
			$serial = (string) get_post_meta( $post_id, '_pixva_order_warranty_serial', true );
			echo $serial ? '<span dir="ltr">' . esc_html( $serial ) . '</span>' : esc_html__( 'صادر نشده', 'pixva' );
		}
	}
}
add_action( 'manage_pixva_orders_posts_custom_column', 'pixva_mb_order_column_content', 10, 2 );
