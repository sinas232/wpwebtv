<?php
/**
 * Centralised business claims (§34–§36).
 *
 * Every public statement about the business (contact data, address, hours,
 * warranty policy, service modes, response time, social profiles) lives in
 * ONE option: `pixva_business_claims`. All defaults are empty. Templates
 * must call pixva_claim()/pixva_has_claim() and render nothing when a value
 * is unset — there are no fallbacks to invented values anywhere in the theme.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field schema: key => [type, label, group, help].
 * Types: text, textarea, tel, email, url, int, bool, float, html, lines.
 *
 * @return array<string,array>
 */
function pixva_claim_fields() {
	$fields = array(
		// Identity.
		'legal_name'        => array( 'text', __( 'نام رسمی کسب‌وکار', 'pixva' ), 'identity', __( 'برای اسکیما و فوتر. خالی بماند، نام سایت فقط به‌عنوان عنوان نمایش داده می‌شود.', 'pixva' ) ),
		'tagline'           => array( 'text', __( 'شعار کوتاه', 'pixva' ), 'identity', '' ),
		'founded_year'      => array( 'int', __( 'سال شروع فعالیت (میلادی)', 'pixva' ), 'identity', __( 'فقط اگر دقیق است.', 'pixva' ) ),
		// Contact.
		'phone'             => array( 'tel', __( 'تلفن ثابت', 'pixva' ), 'contact', '' ),
		'mobile'            => array( 'tel', __( 'تلفن همراه', 'pixva' ), 'contact', '' ),
		'whatsapp'          => array( 'tel', __( 'شماره واتس‌اپ (با کد کشور، مثل 98912…)', 'pixva' ), 'contact', '' ),
		'telegram'          => array( 'text', __( 'نام کاربری تلگرام (بدون @)', 'pixva' ), 'contact', '' ),
		'email'             => array( 'email', __( 'ایمیل عمومی', 'pixva' ), 'contact', '' ),
		'notify_email'      => array( 'email', __( 'ایمیل دریافت اعلان درخواست‌ها (غیرعمومی)', 'pixva' ), 'contact', __( 'خالی بماند، ایمیل مدیر سایت استفاده می‌شود.', 'pixva' ) ),
		// Location.
		'address'           => array( 'textarea', __( 'نشانی کامل', 'pixva' ), 'location', __( 'اسکیمای LocalBusiness فقط وقتی نشانی و شهر ثبت شده باشد تولید می‌شود.', 'pixva' ) ),
		'city'              => array( 'text', __( 'شهر', 'pixva' ), 'location', '' ),
		'region'            => array( 'text', __( 'استان', 'pixva' ), 'location', '' ),
		'postal_code'       => array( 'text', __( 'کد پستی', 'pixva' ), 'location', '' ),
		'latitude'          => array( 'float', __( 'عرض جغرافیایی', 'pixva' ), 'location', '' ),
		'longitude'         => array( 'float', __( 'طول جغرافیایی', 'pixva' ), 'location', '' ),
		'map_url'           => array( 'url', __( 'پیوند نقشه (نشان، بلد، گوگل)', 'pixva' ), 'location', '' ),
		'hours'             => array( 'lines', __( 'ساعات کاری (هر خط یک ردیف، مثل «شنبه تا چهارشنبه | 09:00 | 18:00»)', 'pixva' ), 'location', '' ),
		'service_area'      => array( 'text', __( 'محدوده خدمت‌رسانی', 'pixva' ), 'location', '' ),
		// Service promises.
		'mode_dropoff'      => array( 'bool', __( 'تحویل دستگاه در محل کارگاه', 'pixva' ), 'service', '' ),
		'mode_pickup'       => array( 'bool', __( 'دریافت دستگاه از محل مشتری', 'pixva' ), 'service', '' ),
		'mode_onsite'       => array( 'bool', __( 'بازدید در محل مشتری', 'pixva' ), 'service', '' ),
		'response_time'     => array( 'text', __( 'زمان پاسخ‌گویی به درخواست', 'pixva' ), 'service', __( 'فقط تعهدی که واقعاً رعایت می‌شود.', 'pixva' ) ),
		'inspection_fee'    => array( 'text', __( 'توضیح هزینه کارشناسی', 'pixva' ), 'service', __( 'مثلاً «رایگان در صورت انجام تعمیر». خالی = نمایش داده نمی‌شود.', 'pixva' ) ),
		// Warranty (§13).
		'warranty_enabled'  => array( 'bool', __( 'ارائه گارانتی تعمیر', 'pixva' ), 'warranty', '' ),
		'warranty_days'     => array( 'int', __( 'مدت پیش‌فرض گارانتی (روز)', 'pixva' ), 'warranty', __( 'هنگام تحویل روی تعمیر ثبت می‌شود. صفر یا خالی = بدون مدت پیش‌فرض.', 'pixva' ) ),
		'warranty_terms'    => array( 'html', __( 'شرایط و پوشش گارانتی', 'pixva' ), 'warranty', '' ),
		'warranty_excluded' => array( 'html', __( 'موارد خارج از پوشش', 'pixva' ), 'warranty', '' ),
		// Social.
		'instagram'         => array( 'url', __( 'اینستاگرام', 'pixva' ), 'social', '' ),
		'aparat'            => array( 'url', __( 'آپارات', 'pixva' ), 'social', '' ),
		'youtube'           => array( 'url', __( 'یوتیوب', 'pixva' ), 'social', '' ),
		'linkedin'          => array( 'url', __( 'لینکدین', 'pixva' ), 'social', '' ),
		// Trust.
		'trust_seal_url'    => array( 'url', __( 'پیوند نماد اعتماد (اینماد)', 'pixva' ), 'trust', __( 'فقط اگر نماد معتبر صادر شده است.', 'pixva' ) ),
		'trust_seal_image'  => array( 'url', __( 'نشانی تصویر نماد اعتماد', 'pixva' ), 'trust', '' ),
	);
	return apply_filters( 'pixva_claim_fields', $fields );
}

/**
 * Group labels for the admin screen.
 *
 * @return array<string,string>
 */
function pixva_claim_groups() {
	return array(
		'identity' => __( 'هویت', 'pixva' ),
		'contact'  => __( 'راه‌های تماس', 'pixva' ),
		'location' => __( 'نشانی و ساعات کاری', 'pixva' ),
		'service'  => __( 'شیوه‌های خدمت', 'pixva' ),
		'warranty' => __( 'سیاست گارانتی', 'pixva' ),
		'social'   => __( 'شبکه‌های اجتماعی', 'pixva' ),
		'trust'    => __( 'نماد اعتماد', 'pixva' ),
	);
}

/**
 * All claims, with every key present (empty when unset).
 *
 * @return array<string,mixed>
 */
function pixva_claims() {
	$saved = get_option( 'pixva_business_claims', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$out   = array();
	foreach ( pixva_claim_fields() as $key => $def ) {
		$out[ $key ] = array_key_exists( $key, $saved ) ? $saved[ $key ] : ( 'bool' === $def[0] ? false : '' );
	}
	return $out;
}

/**
 * One claim value ('' / false when unset).
 *
 * @param string $key Field key.
 * @return mixed
 */
function pixva_claim( $key ) {
	$all = pixva_claims();
	return $all[ $key ] ?? '';
}

/**
 * Whether a claim is set (non-empty / true / > 0).
 *
 * @param string $key Field key.
 * @return bool
 */
function pixva_has_claim( $key ) {
	$v = pixva_claim( $key );
	if ( is_bool( $v ) ) {
		return $v;
	}
	if ( is_numeric( $v ) ) {
		return (float) $v > 0;
	}
	return '' !== trim( (string) $v );
}

/**
 * Sanitize the full claims array (Settings API callback).
 *
 * @param mixed $input Raw input.
 * @return array
 */
function pixva_sanitize_claims( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( pixva_claim_fields() as $key => $def ) {
		$raw = $input[ $key ] ?? '';
		switch ( $def[0] ) {
			case 'bool':
				$out[ $key ] = ! empty( $raw );
				break;
			case 'int':
				$raw         = pixva_en_num( (string) $raw );
				$out[ $key ] = '' === trim( $raw ) ? '' : max( 0, (int) $raw );
				break;
			case 'float':
				$raw         = pixva_en_num( (string) $raw );
				$out[ $key ] = is_numeric( $raw ) ? (string) (float) $raw : '';
				break;
			case 'tel':
				$out[ $key ] = preg_replace( '/[^0-9+]/', '', pixva_en_num( (string) $raw ) );
				break;
			case 'email':
				$out[ $key ] = sanitize_email( (string) $raw );
				break;
			case 'url':
				$out[ $key ] = esc_url_raw( (string) $raw, array( 'http', 'https' ) );
				break;
			case 'html':
				$out[ $key ] = wp_kses( (string) $raw, pixva_kses_content() );
				break;
			case 'textarea':
			case 'lines':
				$out[ $key ] = sanitize_textarea_field( (string) $raw );
				break;
			default:
				$out[ $key ] = sanitize_text_field( (string) $raw );
		}
	}
	$out['telegram'] = ltrim( (string) $out['telegram'], '@' );
	if ( '' !== $out['latitude'] && ( abs( (float) $out['latitude'] ) > 90 ) ) {
		$out['latitude'] = '';
	}
	if ( '' !== $out['longitude'] && ( abs( (float) $out['longitude'] ) > 180 ) ) {
		$out['longitude'] = '';
	}
	return $out;
}

/**
 * Parsed opening hours rows: [label, open, close]. Rows that are not in the
 * "label | HH:MM | HH:MM" format are kept as label-only display rows.
 *
 * @return array<int,array{label:string,open:string,close:string}>
 */
function pixva_claim_hours() {
	$rows = array();
	foreach ( preg_split( '/\r?\n/', (string) pixva_claim( 'hours' ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts  = array_map( 'trim', explode( '|', $line ) );
		$open   = isset( $parts[1] ) && preg_match( '/^\d{1,2}:\d{2}$/', pixva_en_num( $parts[1] ) ) ? pixva_en_num( $parts[1] ) : '';
		$close  = isset( $parts[2] ) && preg_match( '/^\d{1,2}:\d{2}$/', pixva_en_num( $parts[2] ) ) ? pixva_en_num( $parts[2] ) : '';
		$rows[] = array(
			'label' => $parts[0],
			'open'  => $open,
			'close' => $close,
		);
	}
	return $rows;
}

/**
 * Primary phone for CTAs (landline first, then mobile). '' when none.
 *
 * @return string
 */
function pixva_primary_phone() {
	$phone = pixva_has_claim( 'phone' ) ? (string) pixva_claim( 'phone' ) : (string) pixva_claim( 'mobile' );
	return (string) apply_filters( 'pixva_primary_phone', $phone );
}

/**
 * Display name of the business: legal name if set, else site title.
 *
 * @return string
 */
function pixva_business_name() {
	$legal = (string) pixva_claim( 'legal_name' );
	return '' !== $legal ? $legal : (string) get_bloginfo( 'name' );
}

/**
 * Whether enough verified data exists for LocalBusiness schema (§20).
 *
 * @return bool
 */
function pixva_has_local_business() {
	return pixva_has_claim( 'address' ) && pixva_has_claim( 'city' ) && ( pixva_has_claim( 'phone' ) || pixva_has_claim( 'mobile' ) );
}

/**
 * Whether a warranty policy with a real duration is configured.
 *
 * @return bool
 */
function pixva_warranty_policy_days() {
	return pixva_claim( 'warranty_enabled' ) ? max( 0, (int) pixva_claim( 'warranty_days' ) ) : 0;
}

/**
 * Enabled service modes (key => label). Empty array when none configured.
 *
 * @return array<string,string>
 */
function pixva_service_modes() {
	$modes = array();
	if ( pixva_claim( 'mode_dropoff' ) ) {
		$modes['dropoff'] = __( 'خودم دستگاه را می‌آورم', 'pixva' );
	}
	if ( pixva_claim( 'mode_pickup' ) ) {
		$modes['pickup'] = __( 'دستگاه از محل من دریافت شود', 'pixva' );
	}
	if ( pixva_claim( 'mode_onsite' ) ) {
		$modes['onsite'] = __( 'بازدید در محل', 'pixva' );
	}
	return $modes;
}

/**
 * Social profile links that are set: key => [label, url].
 *
 * @return array<string,array{0:string,1:string}>
 */
function pixva_social_links() {
	$labels = array(
		'instagram' => __( 'اینستاگرام', 'pixva' ),
		'aparat'    => __( 'آپارات', 'pixva' ),
		'youtube'   => __( 'یوتیوب', 'pixva' ),
		'linkedin'  => __( 'لینکدین', 'pixva' ),
	);
	$out    = array();
	foreach ( $labels as $key => $label ) {
		if ( pixva_has_claim( $key ) ) {
			$out[ $key ] = array( $label, (string) pixva_claim( $key ) );
		}
	}
	if ( pixva_has_claim( 'telegram' ) ) {
		$out['telegram'] = array( __( 'تلگرام', 'pixva' ), 'https://t.me/' . rawurlencode( (string) pixva_claim( 'telegram' ) ) );
	}
	return $out;
}

/**
 * Notification recipient for new requests (private).
 *
 * @return string
 */
function pixva_notify_email() {
	$email = (string) pixva_claim( 'notify_email' );
	return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
}

/**
 * Values shipped as defaults by v1.x that are known to be fabricated. The
 * migration refuses to import these into the claims store (§34).
 *
 * @return array<string,string[]>
 */
function pixva_known_fabricated_claims() {
	return array(
		'phone'     => array( '02191009990', '09120000000', '0919000000', '+982191009990' ),
		'mobile'    => array( '09120000000', '09121234567' ),
		'whatsapp'  => array( '989120000000', '+989120000000' ),
		'address'   => array( 'پاساژ علاءالدین' ),
		'latitude'  => array( '35.6841' ),
		'longitude' => array( '51.4277' ),
	);
}
