<?php
/**
 * فرم جستجو — پوسته بنتو (نسخه ۴٫۰٫۰)
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="bx-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="bx-sr" for="bx-search-field"><?php esc_html_e( 'جستجو در سایت', 'pixva' ); ?></label>
	<input class="bx-input" id="bx-search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: بک‌لایت سامسونگ…', 'pixva' ); ?>" required>
	<button type="submit" class="bx-btn bx-btn--primary">
		<?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'جستجو', 'pixva' ); ?></span>
	</button>
</form>
