<?php
/**
 * Template Name: PIXVA — تماس
 *
 * Contact details render only when configured; the form is always
 * available (nonce, honeypot, rate limit, PRG / AJAX — inc/forms.php).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_r = pixva_form_result( 'pixva_contact' );
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container section layout-aside ct">
		<div>
			<?php pixva_page_intro(); ?>
			<div id="pixva-contact" class="form-wrap ct-form" tabindex="-1">
				<?php if ( is_array( $pixva_r ) && $pixva_r['ok'] ) : ?>
					<div class="success">
						<?php pixva_notice( 'success', $pixva_r['payload']['message'], __( 'پیام شما ثبت شد', 'pixva' ), true ); ?>
					</div>
				<?php else : ?>
					<h2><?php esc_html_e( 'ارسال پیام', 'pixva' ); ?></h2>
					<p class="field__help"><?php esc_html_e( 'برای ثبت تعمیر از فرم درخواست تعمیر استفاده کنید تا کد پیگیری بگیرید.', 'pixva' ); ?> <a href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a></p>
					<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-replace-on-success novalidate>
						<?php pixva_form_fields( 'pixva_contact' ); ?>
						<?php pixva_form_status( $pixva_r ); ?>
						<div class="form-grid">
							<?php
							pixva_field(
								array(
									'name'     => 'name',
									'id'       => 'ct-name',
									'label'    => __( 'نام', 'pixva' ),
									'required' => true,
									'attrs'    => array( 'autocomplete' => 'name' ),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'  => 'phone',
									'id'    => 'ct-phone',
									'label' => __( 'شماره همراه', 'pixva' ),
									'type'  => 'tel',
									'help'  => __( 'شماره همراه یا ایمیل؛ دست‌کم یکی لازم است.', 'pixva' ),
									'attrs' => array(
										'autocomplete' => 'tel',
										'dir'          => 'ltr',
										'inputmode'    => 'tel',
									),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'  => 'email',
									'id'    => 'ct-email',
									'label' => __( 'ایمیل', 'pixva' ),
									'type'  => 'email',
									'attrs' => array(
										'autocomplete' => 'email',
										'dir'          => 'ltr',
									),
								),
								$pixva_r
							);
							?>
						</div>
						<?php
						pixva_field(
							array(
								'name'     => 'message',
								'id'       => 'ct-message',
								'label'    => __( 'پیام', 'pixva' ),
								'type'     => 'textarea',
								'required' => true,
								'attrs'    => array(
									'rows'      => '5',
									'maxlength' => '3000',
								),
							),
							$pixva_r
						);
						pixva_field(
							array(
								'name'     => 'consent',
								'id'       => 'ct-consent',
								'type'     => 'checkbox',
								'required' => true,
								'label'    => pixva_consent_label( __( 'با ذخیره اطلاعاتم برای پاسخ‌گویی موافقم.', 'pixva' ) ),
							),
							$pixva_r
						);
						?>
						<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'ارسال', 'pixva' ); ?></button></div>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<aside class="panel ct-aside" aria-labelledby="ct-details">
			<h2 class="panel__title" id="ct-details"><?php esc_html_e( 'راه‌های ارتباط', 'pixva' ); ?></h2>
			<?php if ( ! pixva_contact_details() ) : ?>
				<p><?php esc_html_e( 'اطلاعات تماس هنوز منتشر نشده است؛ از فرم استفاده کنید.', 'pixva' ); ?></p>
			<?php endif; ?>
			<?php if ( pixva_has_claim( 'response_time' ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: %s: response time. */ __( 'زمان پاسخ‌گویی: %s', 'pixva' ), (string) pixva_claim( 'response_time' ) ) ); ?></p>
			<?php endif; ?>
		</aside>
	</div>
</main>
<?php
get_footer();
