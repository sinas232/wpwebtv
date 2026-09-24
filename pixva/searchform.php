<?php
/**
 * فرم جستجو
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="pixva-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="pixva-search"><?php esc_html_e( 'جستجو', 'pixva' ); ?></label>
	<input id="pixva-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'جستجو در خدمات و مجله…', 'pixva' ); ?>">
	<button class="pixva-btn pixva-btn--primary pixva-btn--sm" type="submit"><?php esc_html_e( 'جستجو', 'pixva' ); ?></button>
</form>
