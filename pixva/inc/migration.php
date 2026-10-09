<?php
/**
 * Migration from v1.x to v2 (§37, §38, §64). Versioned, idempotent, non-destructive.
 *
 * Principles
 * - Nothing is deleted. Fabricated or obsolete content is moved to draft
 *   (reversible) and every action is written to `pixva_migration_log`
 *   (Admin → پیکسوا → گزارش ارتقا).
 * - Content written by humans is never rewritten. Theme-seeded content
 *   (v1 option `pixva_sample_content`, post_modified == post_date, i.e.
 *   never edited) may have unverifiable claims stripped; edited content
 *   with such claims is only flagged for review.
 * - Fabricated business data (v1 `pixva_workshop_settings`, header/footer
 *   phone, address, hours) is NEVER imported into business claims. It is
 *   parked under `pixva_legacy_workshop_settings` (not autoloaded, never
 *   rendered) so the owner can copy real values manually.
 * - Legacy prices are imported DISABLED (pricing.php).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PIXVA_DB_VERSION = '2.0.0';

/**
 * Append to the migration log.
 *
 * @param string $action Action.
 * @param string $item   Item label.
 * @param string $reason Reason.
 * @param int    $id     Post id (optional).
 * @return void
 */
function pixva_migration_log( $action, $item, $reason, $id = 0 ) {
	$log   = get_option( 'pixva_migration_log', array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array(
		'action' => $action,
		'item'   => $item,
		'reason' => $reason,
		'id'     => (int) $id,
		'time'   => time(),
	);
	update_option( 'pixva_migration_log', array_slice( $log, -500 ), false );
}

/**
 * Run pending migrations (on activation and, as fallback, on admin_init).
 *
 * @return void
 */
function pixva_run_migrations() {
	$from = (string) get_option( 'pixva_db_version', '' );
	if ( version_compare( $from ? $from : '0', PIXVA_DB_VERSION, '>=' ) ) {
		return;
	}
	// One migration run at a time. A second request returns and the first one finishes.
	$token = pixva_migration_lock_acquire();
	if ( null === $token ) {
		return;
	}
	try {
		pixva_run_migrations_locked( $from );
	} finally {
		pixva_migration_lock_release( $token );
	}
}

/**
 * Migrate one order from the v1 format. Only fills missing fields and converts
 * a legacy steps map to a list. Never clears a valid status or rewrites a list
 * that already exists, so running it twice changes nothing the second time.
 * Call under the order lock.
 *
 * @param int $oid Order id.
 * @return void
 */
function pixva_migrate_order_v2( $oid ) {
	if ( ! get_post_meta( $oid, '_pixva_warranty_source', true ) ) {
		update_post_meta( $oid, '_pixva_warranty_source', 'legacy' );
	}
	$phone = (string) get_post_meta( $oid, '_pixva_order_phone', true );
	if ( '' !== $phone && ! get_post_meta( $oid, '_pixva_phone_hash', true ) ) {
		update_post_meta( $oid, '_pixva_phone_hash', pixva_phone_hash( $phone ) );
	}
	$user = (int) get_post_meta( $oid, '_pixva_order_user', true );
	if ( $user && ! get_post_meta( $oid, '_pixva_customer_id', true ) ) {
		update_post_meta( $oid, '_pixva_customer_id', $user );
	}
	$steps = json_decode( (string) get_post_meta( $oid, '_pixva_order_steps', true ), true );
	if ( is_array( $steps ) && $steps && ! isset( $steps[0] ) ) {
		$list = array();
		foreach ( $steps as $s => $t ) {
			$list[] = array(
				's' => sanitize_key( $s ),
				't' => (int) $t,
				'n' => '',
			);
		}
		update_post_meta( $oid, '_pixva_order_steps', pixva_json_meta( $list ) );
	}
	if ( ! isset( pixva_order_statuses()[ (string) get_post_meta( $oid, '_pixva_order_status', true ) ] ) ) {
		update_post_meta( $oid, '_pixva_order_status', 'new' );
	}
}

if ( ! defined( 'PIXVA_MIGRATION_LOCK_TTL' ) ) {
	/** Seconds after which a migration lock is treated as abandoned. */
	define( 'PIXVA_MIGRATION_LOCK_TTL', 600 );
}

/**
 * Acquire the migration lock row. add_option() is an INSERT against the unique
 * option_name key, so only one request can create it. A lock older than
 * PIXVA_MIGRATION_LOCK_TTL is treated as left behind by a crashed run and is
 * taken over with a conditional delete (matches the old token only).
 *
 * @return string|null Token to release with, or null if another run holds it.
 */
function pixva_migration_lock_acquire() {
	global $wpdb;
	$name  = 'pixva_migration_lock';
	$token = wp_generate_password( 24, false ) . '|' . time();
	if ( add_option( $name, $token, '', 'no' ) ) {
		return $token;
	}
	$held  = (string) get_option( $name, '' );
	$taken = (int) substr( $held, (int) strrpos( $held, '|' ) + 1 );
	if ( '' !== $held && time() - $taken <= PIXVA_MIGRATION_LOCK_TTL ) {
		return null;
	}
	// Stale: delete only if the row still holds the value we just read.
	if ( '' !== $held ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- conditional delete (compare-and-take-over) has no WP API.
		$wpdb->delete(
			$wpdb->options,
			array(
				'option_name'  => $name,
				'option_value' => $held,
			)
		);
	}
	return add_option( $name, $token, '', 'no' ) ? $token : null;
}

/**
 * Release the migration lock, only if it still holds this run's token.
 *
 * @param string $token Token from pixva_migration_lock_acquire().
 * @return void
 */
function pixva_migration_lock_release( $token ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- release only if the row still holds our token; no WP API.
	$wpdb->delete(
		$wpdb->options,
		array(
			'option_name'  => 'pixva_migration_lock',
			'option_value' => (string) $token,
		)
	);
}

/**
 * The migration steps. Call only while holding the migration lock.
 *
 * @param string $from Version stored before this run.
 * @return void
 */
function pixva_run_migrations_locked( $from ) {
	$legacy = '' === $from && ( get_option( 'pixva_theme_version' ) || get_option( 'pixva_installed' ) );

	pixva_install_roles();
	pixva_register_content_model();
	if ( $legacy ) {
		pixva_migrate_v1();
	}
	pixva_install();
	update_option( 'pixva_db_version', PIXVA_DB_VERSION );
	update_option( 'pixva_flush_rewrites', 1 );
	if ( $legacy ) {
		pixva_migration_log( __( 'پایان', 'pixva' ), 'v' . get_option( 'pixva_theme_version', '1.x' ) . ' → v' . PIXVA_DB_VERSION, __( 'ارتقا کامل شد.', 'pixva' ) );
	}
}

/**
 * Fallback trigger when the theme files were updated in place.
 *
 * @return void
 */
function pixva_maybe_migrate() {
	if ( version_compare( (string) get_option( 'pixva_db_version', '0' ), PIXVA_DB_VERSION, '<' ) && current_user_can( 'switch_themes' ) ) {
		pixva_run_migrations();
	}
}
add_action( 'admin_init', 'pixva_maybe_migrate' );

/**
 * Whether a post was seeded by the v1 theme and never edited.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function pixva_is_untouched_seed( $post ) {
	return get_option( 'pixva_sample_content' ) && $post->post_modified_gmt === $post->post_date_gmt;
}

/**
 * Regex for unverifiable duration claims (warranty / turnaround / SLA).
 *
 * @return string
 */
function pixva_claim_duration_regex() {
	return '/([0-9۰-۹]+|یک|دو|سه|چهار|پنج|شش|هفت|ده)\s*(تا\s*([0-9۰-۹]+|دو|سه|چهار|پنج)\s*)?(روز|ساعت|ماه|سال)(\s*کاری)?/u';
}

/**
 * Strip "heading + paragraph" blocks that assert warranty/turnaround durations.
 *
 * @param string $html Content.
 * @return array{0:string,1:int} New content, removed block count.
 */
function pixva_strip_duration_claims( $html ) {
	$count = 0;
	$out   = preg_replace_callback(
		'#<h([2-4])[^>]*>([^<]*)</h\1>\s*(<p[^>]*>.*?</p>)#us',
		static function ( $m ) use ( &$count ) {
			$heading = $m[2];
			$para    = wp_strip_all_tags( $m[3] );
			if ( preg_match( '/گارانتی|ضمانت|برآورد|زمان تعمیر|تحویل/u', $heading ) && preg_match( pixva_claim_duration_regex(), $para ) ) {
				++$count;
				return '';
			}
			return $m[0];
		},
		$html
	);
	return array( null === $out ? $html : $out, $count );
}

/**
 * Steps for the v1.x → v2 migration.
 *
 * @return void
 */
function pixva_migrate_v1() {
	$imported_images = array_map( 'intval', array_values( (array) get_option( 'pixva_imported_images', array() ) ) );

	// 1. Business data: park fabricated v1 workshop settings, never import.
	$ws = get_option( 'pixva_workshop_settings', null );
	if ( null !== $ws ) {
		update_option( 'pixva_legacy_workshop_settings', $ws, false );
		delete_option( 'pixva_workshop_settings' );
		pixva_migration_log( __( 'کنار گذاشته شد', 'pixva' ), 'pixva_workshop_settings', __( 'تلفن، نشانی و ساعات نسخه قبل پیش‌فرض‌های ساختگی تم بودند و وارد اطلاعات کسب‌وکار نشدند. اطلاعات واقعی را در «اطلاعات کسب‌وکار» وارد کنید.', 'pixva' ) );
	}

	// 1b. Site tagline written by the v1 activation (not chosen by the owner,
	// unverified "specialist" claim). Only the exact v1 default is cleared.
	if ( 'مرکز تخصصی تعمیر تلویزیون و نمایشگر' === (string) get_option( 'blogdescription' ) ) {
		update_option( 'pixva_legacy_blogdescription', get_option( 'blogdescription' ), false );
		update_option( 'blogdescription', '' );
		pixva_migration_log( __( 'کنار گذاشته شد', 'pixva' ), __( 'معرفی کوتاه سایت', 'pixva' ), __( 'این متن را نسخه قبل تم به‌طور خودکار نوشته بود، نه مالک سایت. معرفی واقعی را در «تنظیمات ← عمومی» وارد کنید.', 'pixva' ) );
	}

	// 2. Prices: import disabled for review.
	if ( function_exists( 'pixva_import_legacy_pricing' ) && pixva_import_legacy_pricing() ) {
		pixva_migration_log( __( 'وارد شد (غیرفعال)', 'pixva' ), __( 'قیمت‌ها', 'pixva' ), __( 'قیمت‌های نسخه قبل غیرفعال وارد شدند تا پس از بازبینی فعال شوند.', 'pixva' ) );
	}

	// 3. Theme mods with claims / obsolete homepage section switches.
	$mods = get_theme_mods();
	if ( is_array( $mods ) ) {
		$removed = array();
		foreach ( array_keys( $mods ) as $key ) {
			if ( 0 === strpos( (string) $key, 'pixva_' ) ) {
				$removed[] = $key;
				remove_theme_mod( $key );
			}
		}
		if ( $removed ) {
			update_option( 'pixva_legacy_theme_mods', array_intersect_key( $mods, array_flip( $removed ) ), false );
			pixva_migration_log( __( 'حذف از تنظیمات ظاهری', 'pixva' ), implode( ', ', $removed ), __( 'بخش‌های صفحه اصلی نسخه قبل (نظرات، آمار، «گارانتی کتبی» و…) ادعای تأییدنشده داشتند. مقادیر در pixva_legacy_theme_mods نگه داشته شد.', 'pixva' ) );
		}
	}

	// 4. Pages: move pages to v2 routes before route pages are ensured.
	$moves = array(
		'calculator' => 'price_calculator',
		'diagnosis'  => 'diagnosis',
	);
	foreach ( $moves as $slug => $route ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page && ! get_post_meta( $page->ID, '_pixva_route', true ) ) {
			update_post_meta( $page->ID, '_pixva_route', $route );
			pixva_migration_log( __( 'منتقل شد', 'pixva' ), get_the_title( $page ), sprintf( '/%s/ → %s (301)', $slug, pixva_routes()[ $route ]['path'] ), $page->ID );
		}
	}
	$retire = array(
		'error-codes' => __( 'نشانی /error-codes/ اکنون آرشیو پایگاه کدهای خطاست؛ صفحه قدیمی تکراری بود.', 'pixva' ),
		'technician'  => __( 'میز تکنسین به /dashboard/ منتقل شد (301).', 'pixva' ),
		'rates'       => __( 'نرخ‌نامه ثابت ساختگی بود؛ برآورد فقط از قیمت‌گذاری قابل تنظیم (301 به محاسبه هزینه).', 'pixva' ),
		'b2b'         => __( 'خدمات سازمانی بدون پشتوانه واقعی بود (410).', 'pixva' ),
		'client-hub'  => __( 'پنل مشتری به /account/ منتقل شد (301).', 'pixva' ),
		'parts-stock' => __( 'استعلام انبار قطعات داده واقعی نداشت (410).', 'pixva' ),
	);
	foreach ( $retire as $slug => $reason ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page || 'publish' !== $page->post_status ) {
			continue;
		}
		$tpl = (string) get_post_meta( $page->ID, '_wp_page_template', true );
		if ( 0 !== strpos( $tpl, 'page-templates/page-' ) && '' !== trim( wp_strip_all_tags( $page->post_content ) ) ) {
			pixva_migration_log( __( 'نیاز به بررسی', 'pixva' ), get_the_title( $page ), __( 'نشانی این صفحه در نسخه ۲ کاربرد دیگری دارد ولی محتوای دستی دارد؛ دست‌نخورده ماند.', 'pixva' ), $page->ID );
			continue;
		}
		wp_update_post(
			array(
				'ID'          => $page->ID,
				'post_status' => 'draft',
			)
		);
		delete_post_meta( $page->ID, '_wp_page_template' );
		pixva_migration_log( __( 'پیش‌نویس شد', 'pixva' ), get_the_title( $page ), $reason, $page->ID );
	}

	// 5. Seeded content: strip unverifiable claims / draft fabricated cases.
	$seeded = get_posts(
		array(
			'post_type'      => array( 'tv_services', 'tv_brands', 'post', 'repair_cases', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);
	foreach ( $seeded as $post ) {
		$untouched = pixva_is_untouched_seed( $post );
		if ( 'repair_cases' === $post->post_type ) {
			$uses_stock = in_array( (int) get_post_meta( $post->ID, '_pixva_case_before', true ), $imported_images, true ) || in_array( (int) get_post_meta( $post->ID, '_pixva_case_after', true ), $imported_images, true );
			if ( $uses_stock && $untouched ) {
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'draft',
					)
				);
				pixva_migration_log( __( 'پیش‌نویس شد', 'pixva' ), get_the_title( $post ), __( 'نمونه‌کار نمایشی تم با تصاویر آماده بود، نه تعمیر واقعی.', 'pixva' ), $post->ID );
				continue;
			}
			if ( $uses_stock ) {
				pixva_migration_log( __( 'نیاز به بررسی', 'pixva' ), get_the_title( $post ), __( 'این نمونه‌کار از تصاویر آماده تم استفاده می‌کند.', 'pixva' ), $post->ID );
			}
		}
		list( $clean, $n ) = pixva_strip_duration_claims( $post->post_content );
		if ( $n ) {
			if ( $untouched ) {
				wp_save_post_revision( $post->ID );
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => $clean,
					)
				);
				pixva_migration_log( __( 'ادعا حذف شد', 'pixva' ), get_the_title( $post ), __( 'مدت گارانتی یا زمان تعمیر تأییدنشده از متن نمونه حذف شد (نسخه قبلی در بازبینی‌ها هست).', 'pixva' ), $post->ID );
			} else {
				pixva_migration_log( __( 'نیاز به بررسی', 'pixva' ), get_the_title( $post ), __( 'متن شامل مدت گارانتی یا زمان تعمیر است؛ اگر واقعی نیست اصلاح کنید.', 'pixva' ), $post->ID );
			}
		}
		$thumb = (int) get_post_thumbnail_id( $post );
		if ( $thumb && in_array( $thumb, $imported_images, true ) && 'repair_cases' !== $post->post_type ) {
			delete_post_thumbnail( $post );
			pixva_migration_log( __( 'تصویر شاخص برداشته شد', 'pixva' ), get_the_title( $post ), __( 'تصویر «قبل/بعد از تعمیر» آماده تم بود و به این مطلب ربطی نداشت.', 'pixva' ), $post->ID );
		}
	}

	// 6. Article brand: free text → brand post id (first match).
	$brands = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'tv_brands',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	) as $b ) {
		$name                    = trim( preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', $b->post_title ) );
		$brands[ $name ]         = $b->ID;
		$brands[ $b->post_name ] = $b->ID;
	}
	foreach ( get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_pixva_post_brand',
		)
	) as $post ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$raw = (string) get_post_meta( $post->ID, '_pixva_post_brand', true );
		if ( ctype_digit( $raw ) ) {
			continue;
		}
		$found = 0;
		foreach ( preg_split( '/[،,]+/u', $raw ) as $part ) {
			$part = trim( $part );
			if ( isset( $brands[ $part ] ) ) {
				$found = $brands[ $part ];
				break;
			}
		}
		update_post_meta( $post->ID, '_pixva_legacy_post_brand', $raw );
		if ( $found ) {
			update_post_meta( $post->ID, '_pixva_post_brand', $found );
		} else {
			delete_post_meta( $post->ID, '_pixva_post_brand' );
		}
		pixva_migration_log( __( 'تبدیل شد', 'pixva' ), get_the_title( $post ), sprintf( '«%s» → %s', $raw, $found ? get_the_title( $found ) : '—' ), $post->ID );
	}

	// 7. Orders from v1: mark warranty source and add phone hash for lookup.
	foreach ( get_posts(
		array(
			'post_type'      => 'pixva_orders',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $oid ) {
		$code = (string) get_post_meta( $oid, '_pixva_order_code', true );
		if ( 'PXV-DEMO-2401' === $code ) {
			// v1 activation seeded a demo order with a fake phone that anyone could track.
			wp_trash_post( $oid );
			pixva_migration_log( __( 'به زباله‌دان رفت', 'pixva' ), $code, __( 'سفارش نمایشی ساختگی نسخه قبل (شماره ۰۹۱۲۱۱۱۱۱۱۱).', 'pixva' ), $oid );
			continue;
		}
		// Under the order lock, re-reading inside it, so a status change made while
		// this run is going is not overwritten with the old legacy history.
		pixva_with_order_lock(
			$oid,
			static function () use ( $oid ) {
				pixva_migrate_order_v2( $oid );
				return true;
			}
		);
	}

	// 8. Roles: B2B role was a phantom feature → users become customers.
	if ( get_role( 'pixva_b2b_client' ) ) {
		foreach ( get_users(
			array(
				'role'   => 'pixva_b2b_client',
				'fields' => 'all',
			)
		) as $u ) {
			$u->remove_role( 'pixva_b2b_client' );
			$u->add_role( 'pixva_customer' );
		}
		remove_role( 'pixva_b2b_client' );
		pixva_migration_log( __( 'حذف نقش', 'pixva' ), 'pixva_b2b_client', __( 'کاربران این نقش به «مشتری پیکسوا» منتقل شدند.', 'pixva' ) );
	}

	// 9. Cron from v1 (warranty mailer with fabricated policy).
	wp_clear_scheduled_hook( 'pixva_daily_warranty_check' );

	// 10. Menu items: point to v2 routes, never to retired pages; trailing slash.
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) ) as $item ) {
			$target = '';
			if ( 'post_type' === $item->type && 'page' === $item->object && 'publish' !== get_post_status( (int) $item->object_id ) ) {
				$slug   = get_post_field( 'post_name', (int) $item->object_id );
				$target = array(
					'error-codes' => '/error-codes/',
					'technician'  => '/dashboard/',
					'client-hub'  => '/account/',
					'rates'       => '/tools/price-calculator/',
				)[ $slug ] ?? '';
				if ( '' === $target ) {
					wp_delete_post( $item->ID, true );
					continue;
				}
			} elseif ( 'custom' === $item->type && 0 === strpos( (string) $item->url, home_url() ) && ! preg_match( '~/$|[?#]~', (string) $item->url ) ) {
				$target = (string) wp_parse_url( $item->url, PHP_URL_PATH ) . '/';
			}
			if ( '' !== $target ) {
				update_post_meta( $item->ID, '_menu_item_type', 'custom' );
				update_post_meta( $item->ID, '_menu_item_object', 'custom' );
				update_post_meta( $item->ID, '_menu_item_object_id', $item->ID );
				update_post_meta( $item->ID, '_menu_item_url', esc_url_raw( home_url( $target ) ) );
			}
		}
	}

	// 11. Obsolete v1 flags.
	foreach ( array( 'pixva_show_setup_notice', 'pixva_nav_short', 'pixva_reading_checked', 'pixva_identity_checked', 'pixva_rewrite_flushed', 'pixva_ia_version' ) as $opt ) {
		delete_option( $opt );
	}
}
