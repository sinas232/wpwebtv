<?php
/**
 * سایدبار داینامیک پیکسوا
 *
 * ناحیه ابزارک بر اساس بافت صفحه انتخاب می‌شود (pixva_active_sidebar):
 * مقاله‌ها و آرشیوها → blog-sidebar، خدمات/برند/نمونه‌کار/عیب → services-sidebar
 * و برگه‌ها → page-sidebar. اگر هیچ ابزارکی چیده نشده باشد، محتوای جایگزین
 * از داده‌های زنده سایت (جستجو، خدمات، هاب‌ها، تنظیمات تماس) ساخته می‌شود.
 *
 * @package Pixva
 * @since   1.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_sidebar = isset( $pixva_sidebar ) && '' !== $pixva_sidebar ? (string) $pixva_sidebar : pixva_active_sidebar();
?>
<aside class="pixva-sidebar" id="secondary" aria-label="<?php esc_attr_e( 'ستون کناری', 'pixva' ); ?>" data-sidebar="<?php echo esc_attr( $pixva_sidebar ); ?>">
	<div class="pixva-sidebar__inner">
		<?php pixva_sidebar_widgets( $pixva_sidebar ); ?>
	</div>
</aside>
