<?php
/**
 * Search form.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pixva_sid = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $pixva_sid ); ?>"><?php esc_html_e( 'جست‌وجو در سایت', 'pixva' ); ?></label>
	<div class="input-group">
		<input type="search" id="<?php echo esc_attr( $pixva_sid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'مثلاً «خطوط عمودی» یا «چشمک سونی»', 'pixva' ); ?>" required minlength="2">
		<button class="btn btn--primary" type="submit"><?php echo wp_kses( pixva_icon( 'search' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'جست‌وجو', 'pixva' ); ?></span></button>
	</div>
</form>
