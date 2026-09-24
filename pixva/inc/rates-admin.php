<?php
/**
 * صفحه نرخ‌نامه ۱۴۰۵ در پیشخوان
 *
 * مسیر: پیشخوان ← پیگیری تعمیرات ← نرخ‌نامه
 * قابل تنظیم: ضریب کلی ۰٫۵ تا ۳، هزینه کارشناسی، کف و سقف هر خدمت، ضریب هر برند.
 * دکمه بازگشت به پیش‌فرض ۱۴۰۵ نیز همین‌جاست.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت زیرمنوی نرخ‌نامه زیر «پیگیری تعمیرات».
 *
 * @return void
 */
function pixva_register_rates_menu() {
	add_submenu_page(
		'edit.php?post_type=pixva_orders',
		esc_html__( 'نرخ‌نامه ۱۴۰۵', 'pixva' ),
		esc_html__( 'نرخ‌نامه', 'pixva' ),
		'manage_options',
		'pixva-rates',
		'pixva_render_rates_page'
	);
}
add_action( 'admin_menu', 'pixva_register_rates_menu' );

/**
 * ذخیره نرخ‌ها پس از ارسال فرم.
 *
 * @return array{notice:string,error:string}
 */
function pixva_handle_rates_save() {
	$result = array(
		'notice' => '',
		'error'  => '',
	);
	if ( ! isset( $_POST['pixva_rates_nonce'] ) ) {
		return $result;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		$result['error'] = esc_html__( 'دسترسی غیرمجاز.', 'pixva' );
		return $result;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_rates_nonce'] ) ), 'pixva_rates_save' ) ) {
		$result['error'] = esc_html__( 'نشست امنیتی منقضی شده است؛ دوباره تلاش کنید.', 'pixva' );
		return $result;
	}
	$defaults = pixva_default_rates_1405();

	// دکمه بازگشت به پیش‌فرض ۱۴۰۵.
	if ( isset( $_POST['pixva_rates_reset'] ) ) {
		delete_option( 'pixva_rates_1405' );
		$result['notice'] = esc_html__( 'نرخ‌ها به پیش‌فرض سال ۱۴۰۵ برگشت.', 'pixva' );
		return $result;
	}

	$new = array();

	// ضریب کلی بین ۰٫۵ تا ۳.
	$global = isset( $_POST['pixva_global'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['pixva_global'] ) ) : 1.0;
	if ( $global < 0.5 ) {
		$global = 0.5;
	}
	if ( $global > 3 ) {
		$global = 3;
	}
	$new['global'] = round( $global, 2 );

	// هزینه کارشناسی.
	$new['expert_min'] = isset( $_POST['pixva_expert_min'] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_POST['pixva_expert_min'] ) ) ) : $defaults['expert_min'];
	$new['expert_max'] = isset( $_POST['pixva_expert_max'] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_POST['pixva_expert_max'] ) ) ) : $defaults['expert_max'];
	if ( $new['expert_max'] < $new['expert_min'] ) {
		$new['expert_max'] = $new['expert_min'];
	}

	// کف و سقف هر خدمت.
	$new['base'] = array();
	foreach ( $defaults['base'] as $key => $pair ) {
		$min_key = 'pixva_base_' . $key . '_min';
		$max_key = 'pixva_base_' . $key . '_max';
		$min     = isset( $_POST[ $min_key ] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_POST[ $min_key ] ) ) ) : (int) $pair[0];
		$max     = isset( $_POST[ $max_key ] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_POST[ $max_key ] ) ) ) : (int) $pair[1];
		if ( $max < $min ) {
			$max = $min;
		}
		$new['base'][ $key ] = array( $min, $max );
	}

	// ضریب هر برند بین ۰٫۵ تا ۳.
	$new['brand'] = array();
	foreach ( $defaults['brand'] as $key => $coef ) {
		$field = 'pixva_brand_' . $key;
		$value = isset( $_POST[ $field ] ) ? (float) sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : (float) $coef;
		if ( $value < 0.5 ) {
			$value = 0.5;
		}
		if ( $value > 3 ) {
			$value = 3;
		}
		$new['brand'][ $key ] = round( $value, 2 );
	}

	update_option( 'pixva_rates_1405', $new );
	$result['notice'] = esc_html__( 'نرخ‌نامه ذخیره شد و محاسبه‌گر از همین نرخ‌ها استفاده می‌کند.', 'pixva' );
	return $result;
}

/**
 * نمایش صفحه نرخ‌نامه.
 *
 * @return void
 */
function pixva_render_rates_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'دسترسی غیرمجاز.', 'pixva' ) );
	}
	$feedback = pixva_handle_rates_save();
	$rates    = pixva_get_rates();
	$problems = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();
	$brands   = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'نرخ‌نامه تعمیرات — سطح سال ۱۴۰۵', 'pixva' ); ?></h1>
		<p><?php esc_html_e( 'این جدول تنها منبع حقیقت قیمت محاسبه‌گر است. قیمت فقط سمت سرور محاسبه می‌شود و در جاوااسکریپت تکرار نشده است.', 'pixva' ); ?></p>
		<?php if ( '' !== $feedback['notice'] ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $feedback['notice'] ); ?></p></div>
		<?php endif; ?>
		<?php if ( '' !== $feedback['error'] ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $feedback['error'] ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="">
			<?php wp_nonce_field( 'pixva_rates_save', 'pixva_rates_nonce' ); ?>
			<h2><?php esc_html_e( 'تنظیمات کلی', 'pixva' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="pixva_global"><?php esc_html_e( 'ضریب کلی (۰٫۵ تا ۳)', 'pixva' ); ?></label></th>
						<td><input type="number" step="0.05" min="0.5" max="3" id="pixva_global" name="pixva_global" value="<?php echo esc_attr( $rates['global'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'هزینه کارشناسی (تومان)', 'pixva' ); ?></th>
						<td>
							<label><?php esc_html_e( 'کف', 'pixva' ); ?> <input type="number" min="0" step="10000" name="pixva_expert_min" value="<?php echo esc_attr( $rates['expert_min'] ); ?>"></label>
							<label><?php esc_html_e( 'سقف', 'pixva' ); ?> <input type="number" min="0" step="10000" name="pixva_expert_max" value="<?php echo esc_attr( $rates['expert_max'] ); ?>"></label>
							<p class="description"><?php esc_html_e( 'پیش‌فرض ۱۸۰ تا ۳۵۰ هزار تومان. تعویض کامل پنل خارج از جدول است و اغلب از ۱۰ میلیون تومان شروع می‌شود.', 'pixva' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'کف و سقف پایه هر خدمت (تومان، مبنای ۳۲ اینچ)', 'pixva' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'کف', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'سقف', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'اثر سایز', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rates['base'] as $key => $pair ) : ?>
						<tr>
							<td><?php echo esc_html( isset( $problems[ $key ] ) ? $problems[ $key ] : $key ); ?></td>
							<td><input type="number" min="0" step="50000" name="<?php echo esc_attr( 'pixva_base_' . $key . '_min' ); ?>" value="<?php echo esc_attr( $pair[0] ); ?>"></td>
							<td><input type="number" min="0" step="50000" name="<?php echo esc_attr( 'pixva_base_' . $key . '_max' ); ?>" value="<?php echo esc_attr( $pair[1] ); ?>"></td>
							<td><?php echo esc_html( isset( $rates['size_mode'][ $key ] ) && 'low' === $rates['size_mode'][ $key ] ? __( 'ملایم (برد و صدا)', 'pixva' ) : __( 'کامل (بک‌لایت و پنل)', 'pixva' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'ضریب هر برند (۰٫۵ تا ۳)', 'pixva' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'برند', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'ضریب', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rates['brand'] as $key => $coef ) : ?>
						<tr>
							<td><?php echo esc_html( isset( $brands[ $key ] ) ? $brands[ $key ]['fa'] : $key ); ?></td>
							<td><input type="number" step="0.01" min="0.5" max="3" name="<?php echo esc_attr( 'pixva_brand_' . $key ); ?>" value="<?php echo esc_attr( $coef ); ?>"></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="submit">
				<?php submit_button( esc_html__( 'ذخیره نرخ‌نامه', 'pixva' ), 'primary', 'submit', false ); ?>
				<input type="submit" name="pixva_rates_reset" class="button button-secondary" value="<?php esc_attr_e( 'بازگشت به پیش‌فرض ۱۴۰۵', 'pixva' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'همه نرخ‌ها به پیش‌فرض ۱۴۰۵ برگردد؟', 'pixva' ) ); ?>');">
			</p>
		</form>
		<h2><?php esc_html_e( 'نمونه صحت', 'pixva' ); ?></h2>
		<p><?php esc_html_e( 'سامسونگ ۵۵ اینچ LED، تعویض بک‌لایت: حدود ۶٫۸ تا ۱۱٫۶ میلیون تومان. برد پاور همان سایز: حدود ۲٫۳ تا ۵٫۲ میلیون تومان.', 'pixva' ); ?></p>
	</div>
	<?php
}
