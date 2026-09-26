<?php
/**
 * موتور گارانتی دیجیتال، هولوگرام و فاکتور رسمی کارگاه (inc/crm-warranty.php)
 *
 * - کارت گارانتی با مهر هولوگرافیک متحرک (فقط CSS: transform/opacity و گرادیان مخروطی).
 * - مشخصات فاکتور: تعمیرکار، سریال قطعه، تاریخ صدور و انقضا (۱۸۰ روز پیش‌فرض)، QR استعلام اصالت.
 * - فاکتور رسمی قابل چاپ/ذخیره PDF با مهر و امضای دیجیتال (اثرانگشت HMAC پرونده).
 * - استعلام اصالت با سریال (REST: pixva/v1/crm/warranty/{serial}).
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_crm_workshop_identity' ) ) {
	/**
	 * هویت کارگاه برای سربرگ فاکتور و کارت گارانتی (از مرکز کنترل).
	 *
	 * @return array<string, string>
	 */
	function pixva_crm_workshop_identity() {
		$control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();

		$identity = array(
			'name'    => (string) get_bloginfo( 'name' ),
			'address' => isset( $control['hub_address'] ) ? (string) $control['hub_address'] : '',
			'phone'   => isset( $control['hub_phone'] ) ? (string) $control['hub_phone'] : ( function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '' ),
			'hours'   => isset( $control['hub_hours'] ) ? (string) $control['hub_hours'] : '',
			'tax'     => (string) pixva_option( 'pixva_workshop_tax_id', '' ),
			'registry'=> (string) pixva_option( 'pixva_workshop_registry', '' ),
			'signatory' => (string) pixva_option( 'pixva_workshop_signatory', __( 'مدیر فنی کارگاه مرکزی', 'pixva' ) ),
		);

		/**
		 * فیلتر هویت کارگاه در سربرگ فاکتور/گارانتی.
		 *
		 * @param array $identity هویت کارگاه.
		 */
		return apply_filters( 'pixva_crm_workshop_identity', $identity );
	}
}

if ( ! function_exists( 'pixva_warranty_scope_terms' ) ) {
	/**
	 * بندهای پوشش گارانتی (قابل ویرایش با فیلتر).
	 *
	 * @return string[]
	 */
	function pixva_warranty_scope_terms() {
		$terms = array(
			__( 'گارانتی صرفاً قطعات ثبت‌شده در همین کارت و ایراد رفع‌شده را پوشش می‌دهد.', 'pixva' ),
			__( 'ضربه، آب‌خوردگی، نوسان شدید برق و دستکاری توسط شخص ثالث گارانتی را باطل می‌کند.', 'pixva' ),
			__( 'ارائه این کارت (یا سریال دیجیتال آن) هنگام مراجعه الزامی است.', 'pixva' ),
			__( 'ایاب و ذهاب در دوره گارانتی برای همان ایراد، رایگان است.', 'pixva' ),
		);

		/**
		 * فیلتر بندهای گارانتی.
		 *
		 * @param string[] $terms بندها.
		 */
		return apply_filters( 'pixva_warranty_scope_terms', $terms );
	}
}

if ( ! function_exists( 'pixva_render_warranty_card' ) ) {
	/**
	 * رندر کارت گارانتی دیجیتال با هولوگرام متحرک.
	 *
	 * @param int|WP_Post $order پرونده یا شناسه آن.
	 * @param array       $args  گزینه‌ها: title, show_invoice, variant, compact.
	 * @return void
	 */
	function pixva_render_warranty_card( $order, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'        => '',
				'show_invoice' => true,
				'variant'      => 'dark',
				'compact'      => false,
			)
		);

		$data = function_exists( 'pixva_crm_order' ) ? pixva_crm_order( $order ) : null;
		if ( ! $data || empty( $data['warranty']['serial'] ) ) {
			echo '<p class="pixva-notice pixva-notice--info">' . esc_html__( 'گارانتی این پرونده هنوز صادر نشده است؛ پس از تأیید نهایی تعمیرکار، کارت به‌صورت خودکار ساخته می‌شود.', 'pixva' ) . '</p>';
			return;
		}

		$warranty   = $data['warranty'];
		$report     = $data['report'];
		$identity   = pixva_crm_workshop_identity();
		$title      = '' !== $args['title'] ? $args['title'] : __( 'گارانتی دیجیتال پیکسوا', 'pixva' );
		$fingerprint= strtoupper( substr( (string) $warranty['hash'], 0, 16 ) );
		$qr_payload = $warranty['verifyUrl'] ? $warranty['verifyUrl'] : home_url( '/' );
		?>
		<article class="pixva-warranty pixva-warranty--<?php echo esc_attr( sanitize_html_class( $args['variant'] ) ); ?><?php echo $args['compact'] ? ' pixva-warranty--compact' : ''; ?>"
			data-pixva-warranty data-warranty-serial="<?php echo esc_attr( $warranty['serial'] ); ?>"
			style="--holo-x: 50%; --holo-y: 50%;">

			<div class="pixva-holo" data-pixva-holo aria-hidden="true">
				<span class="pixva-holo__foil"></span>
				<span class="pixva-holo__sheen"></span>
				<span class="pixva-holo__grid"></span>
				<span class="pixva-holo__ring"></span>
				<span class="pixva-holo__core">
					<?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>

			<div class="pixva-warranty__body">
				<header class="pixva-warranty__head">
					<div>
						<span class="pixva-badge pixva-badge--success"><?php echo esc_html( $title ); ?></span>
						<h3 class="pixva-warranty__serial" dir="ltr"><?php echo esc_html( $warranty['serial'] ); ?></h3>
					</div>
					<div class="pixva-warranty__validity">
						<span><?php esc_html_e( 'اعتبار', 'pixva' ); ?></span>
						<strong><?php echo esc_html( pixva_fa_num( (string) $warranty['days'] ) ); ?> <?php esc_html_e( 'روز', 'pixva' ); ?></strong>
						<small><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ) ); ?></small>
					</div>
				</header>

				<dl class="pixva-warranty__facts">
					<div>
						<dt><?php esc_html_e( 'پرونده', 'pixva' ); ?></dt>
						<dd dir="ltr"><?php echo esc_html( $data['code'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'دستگاه', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( trim( $data['brand'] . ' ' . $data['model'] ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'تعمیرکار', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( $warranty['techName'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'تاریخ صدور', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['issuedAt'] ) ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'انقضا', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'اثرانگشت دیجیتال', 'pixva' ); ?></dt>
						<dd dir="ltr"><code><?php echo esc_html( $fingerprint ); ?></code></dd>
					</div>
				</dl>

				<?php if ( ! empty( $warranty['covers'] ) ) : ?>
					<div class="pixva-warranty__covers">
						<h4><?php esc_html_e( 'قطعات و خدمات تحت پوشش', 'pixva' ); ?></h4>
						<ul>
							<?php foreach ( $warranty['covers'] as $cover ) : ?>
								<li><?php echo esc_html( $cover ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $report['parts'] ) ) : ?>
					<div class="pixva-warranty__serials">
						<h4><?php esc_html_e( 'سریال قطعات نصب‌شده', 'pixva' ); ?></h4>
						<table class="pixva-table pixva-table--tight">
							<thead>
								<tr>
									<th><?php esc_html_e( 'قطعه', 'pixva' ); ?></th>
									<th><?php esc_html_e( 'مشخصات', 'pixva' ); ?></th>
									<th><?php esc_html_e( 'سریال', 'pixva' ); ?></th>
									<th><?php esc_html_e( 'تعداد', 'pixva' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $report['parts'] as $part ) : ?>
									<tr>
										<td><?php echo esc_html( $part['name'] ); ?></td>
										<td><?php echo esc_html( $part['spec'] ); ?></td>
										<td dir="ltr"><?php echo esc_html( $part['serial'] ? $part['serial'] : '—' ); ?></td>
										<td><?php echo esc_html( pixva_fa_num( (string) $part['qty'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<div class="pixva-warranty__terms">
					<h4><?php esc_html_e( 'شرایط پوشش', 'pixva' ); ?></h4>
					<ol>
						<?php foreach ( pixva_warranty_scope_terms() as $term ) : ?>
							<li><?php echo esc_html( $term ); ?></li>
						<?php endforeach; ?>
					</ol>
				</div>

				<div class="pixva-warranty__foot">
					<div class="pixva-qr-card pixva-qr-card--warranty" data-pixva-qr data-qr-value="<?php echo esc_url( $qr_payload ); ?>">
						<span class="pixva-qr-card__code" data-qr-target aria-hidden="true"></span>
						<small><?php esc_html_e( 'استعلام اصالت گارانتی', 'pixva' ); ?></small>
					</div>
					<div class="pixva-warranty__actions">
						<button type="button" class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" data-copy="<?php echo esc_attr( $warranty['serial'] ); ?>">
							<?php echo pixva_icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'کپی سریال', 'pixva' ); ?></span>
						</button>
						<?php if ( $warranty['verifyUrl'] ) : ?>
							<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" href="<?php echo esc_url( $warranty['verifyUrl'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'استعلام آنلاین', 'pixva' ); ?></a>
						<?php endif; ?>
						<?php if ( $args['show_invoice'] ) : ?>
							<button type="button" class="pixva-btn pixva-btn--gradient pixva-btn--sm" data-pixva-print data-print-area="invoice-<?php echo esc_attr( (string) $data['id'] ); ?>">
								<?php echo pixva_icon( 'printer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'چاپ فاکتور رسمی / PDF', 'pixva' ); ?></span>
							</button>
						<?php endif; ?>
					</div>
					<p class="pixva-warranty__issuer">
						<?php echo esc_html( $identity['signatory'] ); ?> — <?php echo esc_html( $identity['name'] ); ?>
						<?php if ( $identity['address'] ) : ?>
							<small><?php echo esc_html( $identity['address'] ); ?></small>
						<?php endif; ?>
					</p>
				</div>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_invoice' ) ) {
	/**
	 * رندر فاکتور رسمی کارگاه (قابل چاپ و ذخیره PDF از مرورگر).
	 *
	 * @param int|WP_Post $order پرونده یا شناسه آن.
	 * @param array       $args  گزینه‌ها: title, note.
	 * @return void
	 */
	function pixva_render_invoice( $order, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title' => '',
				'note'  => '',
			)
		);

		$data = function_exists( 'pixva_crm_order' ) ? pixva_crm_order( $order ) : null;
		if ( ! $data ) {
			echo '<p class="pixva-notice pixva-notice--info">' . esc_html__( 'پرونده‌ای برای صدور فاکتور پیدا نشد.', 'pixva' ) . '</p>';
			return;
		}

		$identity   = pixva_crm_workshop_identity();
		$report     = $data['report'];
		$warranty   = $data['warranty'];
		$customer   = $data['customer'];
		$title      = '' !== $args['title'] ? $args['title'] : __( 'فاکتور رسمی تعمیر', 'pixva' );
		$number     = 'INV-' . str_replace( 'PXV-', '', $data['code'] );
		$issued     = $warranty['issuedAt'] ? $warranty['issuedAt'] : time();
		?>
		<article class="pixva-invoice" data-print-area="invoice-<?php echo esc_attr( (string) $data['id'] ); ?>" id="invoice-<?php echo esc_attr( (string) $data['id'] ); ?>">
			<header class="pixva-invoice__head">
				<div class="pixva-invoice__brand">
					<strong><?php echo esc_html( $identity['name'] ); ?></strong>
					<?php if ( $identity['address'] ) : ?><span><?php echo esc_html( $identity['address'] ); ?></span><?php endif; ?>
					<?php if ( $identity['phone'] ) : ?><span dir="ltr"><?php echo esc_html( pixva_fa_num( $identity['phone'] ) ); ?></span><?php endif; ?>
					<?php if ( $identity['tax'] ) : ?><span><?php esc_html_e( 'شناسه مالیاتی:', 'pixva' ); ?> <?php echo esc_html( pixva_fa_num( $identity['tax'] ) ); ?></span><?php endif; ?>
				</div>
				<div class="pixva-invoice__meta">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php esc_html_e( 'شماره فاکتور:', 'pixva' ); ?> <strong dir="ltr"><?php echo esc_html( $number ); ?></strong></p>
					<p><?php esc_html_e( 'تاریخ صدور:', 'pixva' ); ?> <strong><?php echo esc_html( pixva_fa_num( wp_date( 'Y/m/d', $issued ) ) ); ?></strong></p>
					<p><?php esc_html_e( 'کد پرونده:', 'pixva' ); ?> <strong dir="ltr"><?php echo esc_html( $data['code'] ); ?></strong></p>
				</div>
			</header>

			<div class="pixva-invoice__parties">
				<section>
					<h3><?php esc_html_e( 'مشتری', 'pixva' ); ?></h3>
					<p><?php echo esc_html( $customer['name'] ? $customer['name'] : $data['name'] ); ?></p>
					<p dir="ltr"><?php echo esc_html( pixva_fa_num( $data['phone'] ) ); ?></p>
					<?php if ( ! empty( $customer['address'] ) ) : ?><p><?php echo esc_html( $customer['address'] ); ?></p><?php endif; ?>
				</section>
				<section>
					<h3><?php esc_html_e( 'دستگاه', 'pixva' ); ?></h3>
					<p><?php echo esc_html( trim( $data['brand'] . ' ' . $data['model'] ) ); ?></p>
					<p><?php echo esc_html( $data['problem'] ); ?></p>
					<p><?php esc_html_e( 'برآورد اولیه:', 'pixva' ); ?> <?php echo esc_html( $data['estimate'] ); ?></p>
				</section>
				<section>
					<h3><?php esc_html_e( 'تعمیرکار مسئول', 'pixva' ); ?></h3>
					<p><?php echo esc_html( $data['techName'] ? $data['techName'] : __( 'کارگاه مرکزی', 'pixva' ) ); ?></p>
					<?php if ( $report['minutes'] ) : ?>
						<p><?php esc_html_e( 'زمان صرف‌شده:', 'pixva' ); ?> <?php echo esc_html( pixva_fa_num( (string) round( $report['minutes'] / 60, 1 ) ) ); ?> <?php esc_html_e( 'ساعت', 'pixva' ); ?></p>
					<?php endif; ?>
					<?php if ( $warranty['serial'] ) : ?>
						<p><?php esc_html_e( 'گارانتی:', 'pixva' ); ?> <strong dir="ltr"><?php echo esc_html( $warranty['serial'] ); ?></strong></p>
					<?php endif; ?>
				</section>
			</div>

			<?php if ( $report['diagnosis'] || $report['actions'] ) : ?>
				<section class="pixva-invoice__report">
					<h3><?php esc_html_e( 'گزارش فنی', 'pixva' ); ?></h3>
					<?php if ( $report['diagnosis'] ) : ?>
						<p><strong><?php esc_html_e( 'عیب‌یابی:', 'pixva' ); ?></strong> <?php echo esc_html( $report['diagnosis'] ); ?></p>
					<?php endif; ?>
					<?php if ( $report['actions'] ) : ?>
						<p><strong><?php esc_html_e( 'اقدامات:', 'pixva' ); ?></strong> <?php echo esc_html( $report['actions'] ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<table class="pixva-table pixva-invoice__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ردیف', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'شرح قطعه / خدمت', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'سریال', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'تعداد', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'مبلغ (تومان)', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php $row = 0; foreach ( $report['parts'] as $part ) : $row++; ?>
						<tr>
							<td><?php echo esc_html( pixva_fa_num( (string) $row ) ); ?></td>
							<td><?php echo esc_html( trim( $part['name'] . ' ' . $part['spec'] ) ); ?></td>
							<td dir="ltr"><?php echo esc_html( $part['serial'] ? $part['serial'] : '—' ); ?></td>
							<td><?php echo esc_html( pixva_fa_num( (string) $part['qty'] ) ); ?></td>
							<td><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $part['price'] * $part['qty'] ) : pixva_fa_num( (string) ( $part['price'] * $part['qty'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<tr class="pixva-invoice__labor">
						<td><?php echo esc_html( pixva_fa_num( (string) ( $row + 1 ) ) ); ?></td>
						<td><?php esc_html_e( 'دستمزد تخصصی تعمیر و تست', 'pixva' ); ?></td>
						<td>—</td>
						<td><?php echo esc_html( pixva_fa_num( '1' ) ); ?></td>
						<td><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $report['labor'] ) : pixva_fa_num( (string) $report['labor'] ) ); ?></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="4"><?php esc_html_e( 'جمع کل قابل پرداخت', 'pixva' ); ?></th>
						<td><strong><?php echo esc_html( function_exists( 'pixva_price' ) ? pixva_price( $report['total'] ) : pixva_fa_num( (string) $report['total'] ) ); ?></strong></td>
					</tr>
				</tfoot>
			</table>

			<footer class="pixva-invoice__foot">
				<div class="pixva-invoice__sign">
					<span class="pixva-invoice__stamp" aria-hidden="true">
						<?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<p><?php echo esc_html( $identity['signatory'] ); ?></p>
					<small><?php esc_html_e( 'امضا و مهر دیجیتال کارگاه', 'pixva' ); ?></small>
				</div>
				<div class="pixva-invoice__verify">
					<p><?php esc_html_e( 'اثرانگشت اصالت سند:', 'pixva' ); ?> <code dir="ltr"><?php echo esc_html( strtoupper( substr( (string) ( $warranty['hash'] ? $warranty['hash'] : wp_hash( $data['code'] ) ), 0, 24 ) ) ); ?></code></p>
					<p><?php esc_html_e( 'استعلام آنلاین:', 'pixva' ); ?> <span dir="ltr"><?php echo esc_html( $warranty['verifyUrl'] ? $warranty['verifyUrl'] : home_url( '/' ) ); ?></span></p>
					<?php if ( '' !== $args['note'] ) : ?><p class="pixva-muted"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
				</div>
			</footer>

			<div class="pixva-invoice__actions" data-print-hide>
				<button type="button" class="pixva-btn pixva-btn--gradient" data-pixva-print data-print-area="invoice-<?php echo esc_attr( (string) $data['id'] ); ?>">
					<?php echo pixva_icon( 'printer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'چاپ / ذخیره PDF', 'pixva' ); ?></span>
				</button>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'pixva_warranty_shortcode' ) ) {
	/**
	 * شورت‌کد [pixva_warranty serial="PXV-G-..." code="PXV-..." phone="09..."].
	 *
	 * @param array $atts ویژگی‌ها.
	 * @return string
	 */
	function pixva_warranty_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'serial'  => '',
				'code'    => '',
				'phone'   => '',
				'order'   => 0,
				'invoice' => '',
			),
			$atts,
			'pixva_warranty'
		);

		$order_id = 0;

		if ( $atts['order'] ) {
			$order_id = (int) $atts['order'];
		} elseif ( '' !== $atts['serial'] ) {
			$serial = strtoupper( sanitize_text_field( $atts['serial'] ) );
			$ids    = get_posts(
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
			$order_id = empty( $ids ) ? 0 : (int) $ids[0];
		} elseif ( '' !== $atts['code'] && '' !== $atts['phone'] && function_exists( 'pixva_find_order' ) ) {
			$found    = pixva_find_order( sanitize_text_field( $atts['code'] ), $atts['phone'] );
			$order_id = $found instanceof WP_Post ? (int) $found->ID : 0;
		}

		if ( ! $order_id ) {
			return '<p class="pixva-notice pixva-notice--info">' . esc_html__( 'سریال یا کد پیگیری معتبری وارد نشده است.', 'pixva' ) . '</p>';
		}

		ob_start();
		if ( ! empty( $atts['invoice'] ) && '0' !== $atts['invoice'] ) {
			pixva_render_invoice( $order_id );
		}
		pixva_render_warranty_card( $order_id );
		return (string) ob_get_clean();
	}
}
add_shortcode( 'pixva_warranty', 'pixva_warranty_shortcode' );

if ( ! function_exists( 'pixva_needs_warranty_qr' ) ) {
	/**
	 * کارت گارانتی QR دارد؛ پس کتابخانه QR در این صفحه‌ها لازم است.
	 *
	 * @param bool $needed وضعیت فعلی.
	 * @return bool
	 */
	function pixva_needs_warranty_qr( $needed ) {
		if ( $needed ) {
			return true;
		}

		if ( is_page_template( array( 'page-templates/page-technician.php', 'page-templates/page-client-hub.php' ) ) ) {
			return true;
		}

		return is_user_logged_in() && current_user_can( 'pixva_view_crm_panel' );
	}
}
add_filter( 'pixva_needs_qrcode_js', 'pixva_needs_warranty_qr' );
