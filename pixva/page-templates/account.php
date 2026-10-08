<?php
/**
 * Template Name: PIXVA — حساب کاربری
 *
 * Customer account (§14): /account/, /account/repairs/, /account/warranty/,
 * /account/profile/. Noindex + no-store (account.php). Only the current
 * user's own orders are ever queried (_pixva_customer_id); attaching an
 * order requires code + registered phone proof.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_section = pixva_account_section();
$pixva_user    = wp_get_current_user();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container section">
		<?php if ( ! $pixva_user->exists() ) : ?>
			<?php $pixva_rr = pixva_form_result( 'pixva_register' ); ?>
			<div class="auth-grid">
				<section class="panel" aria-labelledby="acc-login">
					<h2 class="panel__title" id="acc-login"><?php esc_html_e( 'ورود', 'pixva' ); ?></h2>
					<?php
					wp_login_form(
						array(
							'redirect'       => pixva_current_url(),
							'label_username' => __( 'ایمیل یا نام کاربری', 'pixva' ),
							'label_password' => __( 'رمز عبور', 'pixva' ),
							'label_remember' => __( 'مرا به خاطر بسپار', 'pixva' ),
							'label_log_in'   => __( 'ورود', 'pixva' ),
							'id_submit'      => 'acc-login-submit',
						)
					);
					?>
					<p><a href="<?php echo esc_url( wp_lostpassword_url( pixva_current_url() ) ); ?>"><?php esc_html_e( 'رمز عبور را فراموش کرده‌اید؟', 'pixva' ); ?></a></p>
				</section>
				<?php if ( get_option( 'users_can_register' ) ) : ?>
					<section class="panel" id="pixva-register" aria-labelledby="acc-register">
						<h2 class="panel__title" id="acc-register"><?php esc_html_e( 'ساخت حساب', 'pixva' ); ?></h2>
						<p class="field__help"><?php esc_html_e( 'با حساب کاربری همه درخواست‌ها و گارانتی‌های خود را یک‌جا می‌بینید.', 'pixva' ); ?></p>
						<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form novalidate>
							<?php pixva_form_fields( 'pixva_register' ); ?>
							<?php pixva_form_status( $pixva_rr ); ?>
							<?php
							pixva_field(
								array(
									'name'     => 'name',
									'id'       => 'reg-name',
									'label'    => __( 'نام', 'pixva' ),
									'required' => true,
									'attrs'    => array( 'autocomplete' => 'name' ),
								),
								$pixva_rr
							);
							pixva_field(
								array(
									'name'     => 'email',
									'id'       => 'reg-email',
									'label'    => __( 'ایمیل', 'pixva' ),
									'type'     => 'email',
									'required' => true,
									'attrs'    => array(
										'autocomplete' => 'email',
										'dir'          => 'ltr',
									),
								),
								$pixva_rr
							);
							pixva_field(
								array(
									'name'  => 'phone',
									'id'    => 'reg-phone',
									'label' => __( 'شماره همراه', 'pixva' ),
									'type'  => 'tel',
									'attrs' => array(
										'autocomplete' => 'tel',
										'dir'          => 'ltr',
										'inputmode'    => 'tel',
									),
								),
								$pixva_rr
							);
							pixva_field(
								array(
									'name'     => 'password',
									'id'       => 'reg-pass',
									'label'    => __( 'رمز عبور', 'pixva' ),
									'type'     => 'password',
									'required' => true,
									'help'     => __( 'حداقل ۸ نویسه.', 'pixva' ),
									'attrs'    => array(
										'autocomplete' => 'new-password',
										'minlength'    => '8',
										'dir'          => 'ltr',
									),
								),
								$pixva_rr
							);
							pixva_field(
								array(
									'name'     => 'consent',
									'id'       => 'reg-consent',
									'type'     => 'checkbox',
									'required' => true,
									'label'    => pixva_consent_label( __( 'سیاست حریم خصوصی را خوانده‌ام و می‌پذیرم.', 'pixva' ) ),
								),
								$pixva_rr
							);
							?>
							<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'ساخت حساب', 'pixva' ); ?></button></div>
						</form>
					</section>
				<?php endif; ?>
			</div>
			<p class="field__help"><?php esc_html_e( 'برای پیگیری یک درخواست بدون ورود، از صفحه پیگیری استفاده کنید.', 'pixva' ); ?> <a href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></a></p>

		<?php else : ?>
			<?php $pixva_orders = pixva_customer_orders( $pixva_user->ID ); ?>
			<div class="account">
				<nav class="account__nav" aria-label="<?php esc_attr_e( 'بخش‌های حساب', 'pixva' ); ?>">
					<ul>
						<?php foreach ( pixva_account_nav() as $pixva_k => $pixva_item ) : ?>
							<li><a href="<?php echo esc_url( $pixva_item[1] ); ?>"<?php echo $pixva_k === $pixva_section ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $pixva_item[0] ); ?></a></li>
						<?php endforeach; ?>
						<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'خروج', 'pixva' ); ?></a></li>
					</ul>
				</nav>
				<div class="account__main">
					<?php if ( 'repairs' === $pixva_section ) : ?>
						<?php $pixva_rc = pixva_form_result( 'pixva_claim_order' ); ?>
						<?php if ( is_array( $pixva_rc ) && $pixva_rc['ok'] ) : ?>
							<?php pixva_notice( 'success', $pixva_rc['payload']['message'], '', true ); ?>
						<?php endif; ?>
						<?php if ( $pixva_orders ) : ?>
							<?php foreach ( $pixva_orders as $pixva_o ) : ?>
								<?php pixva_order_view( pixva_order_public_view( $pixva_o ) ); ?>
							<?php endforeach; ?>
						<?php else : ?>
							<?php pixva_empty_state( __( 'هنوز درخواستی در حساب شما نیست', 'pixva' ), __( 'اگر قبلاً بدون ورود درخواست ثبت کرده‌اید، با فرم زیر آن را به حساب اضافه کنید.', 'pixva' ), array( __( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ) ) ); ?>
						<?php endif; ?>
						<section class="panel" id="pixva-claim-order" aria-labelledby="acc-claim">
							<h2 class="panel__title" id="acc-claim"><?php esc_html_e( 'افزودن درخواست قبلی به حساب', 'pixva' ); ?></h2>
							<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-reload-on-success novalidate>
								<?php pixva_form_fields( 'pixva_claim_order' ); ?>
								<?php pixva_form_status( $pixva_rc ); ?>
								<div class="form-grid">
									<?php
									pixva_field(
										array(
											'name'     => 'code',
											'id'       => 'cl-code',
											'label'    => __( 'کد پیگیری', 'pixva' ),
											'required' => true,
											'attrs'    => array(
												'dir' => 'ltr',
												'autocomplete' => 'off',
											),
										),
										$pixva_rc
									);
									pixva_field(
										array(
											'name'     => 'phone',
											'id'       => 'cl-phone',
											'label'    => __( 'شماره همراه ثبت‌شده', 'pixva' ),
											'type'     => 'tel',
											'required' => true,
											'attrs'    => array(
												'dir' => 'ltr',
												'inputmode' => 'tel',
											),
										),
										$pixva_rc
									);
									?>
								</div>
								<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'افزودن', 'pixva' ); ?></button></div>
							</form>
						</section>

					<?php elseif ( 'warranty' === $pixva_section ) : ?>
						<?php
						$pixva_w = array();
						foreach ( $pixva_orders as $pixva_o ) {
							$pixva_v = pixva_order_public_view( $pixva_o );
							if ( 'none' !== $pixva_v['warranty']['state'] ) {
								$pixva_w[] = $pixva_v;
							}
						}
						?>
						<?php if ( $pixva_w ) : ?>
							<table class="table">
								<caption class="screen-reader-text"><?php esc_html_e( 'گارانتی‌های من', 'pixva' ); ?></caption>
								<thead><tr><th scope="col"><?php esc_html_e( 'کد', 'pixva' ); ?></th><th scope="col"><?php esc_html_e( 'دستگاه', 'pixva' ); ?></th><th scope="col"><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th><th scope="col"><?php esc_html_e( 'تا تاریخ', 'pixva' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $pixva_w as $pixva_v ) : ?>
										<tr>
											<td dir="ltr"><?php echo esc_html( $pixva_v['code'] ); ?></td>
											<td dir="auto"><?php echo esc_html( $pixva_v['device'] ); ?></td>
											<td><?php echo 'active' === $pixva_v['warranty']['state'] ? esc_html( sprintf( /* translators: %s: days. */ __( 'فعال (%s روز مانده)', 'pixva' ), pixva_fa_num( (int) $pixva_v['warranty']['left'] ) ) ) : esc_html__( 'پایان یافته', 'pixva' ); ?></td>
											<td><?php echo esc_html( $pixva_v['warranty']['until'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<?php pixva_empty_state( __( 'گارانتی ثبت‌شده‌ای ندارید', 'pixva' ), __( 'گارانتی هر تعمیر پس از تحویل دستگاه توسط کارشناس ثبت می‌شود.', 'pixva' ), array( __( 'سیاست گارانتی', 'pixva' ) => pixva_route_url( 'warranty' ) ) ); ?>
						<?php endif; ?>

					<?php elseif ( 'profile' === $pixva_section ) : ?>
						<?php $pixva_rp = pixva_form_result( 'pixva_profile' ); ?>
						<section class="panel" id="pixva-profile" aria-labelledby="acc-profile">
							<h2 class="panel__title" id="acc-profile"><?php esc_html_e( 'مشخصات من', 'pixva' ); ?></h2>
							<?php if ( is_array( $pixva_rp ) && $pixva_rp['ok'] ) : ?>
								<?php pixva_notice( 'success', $pixva_rp['payload']['message'], '', true ); ?>
							<?php endif; ?>
							<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form novalidate>
								<?php pixva_form_fields( 'pixva_profile' ); ?>
								<?php pixva_form_status( $pixva_rp ); ?>
								<?php
								pixva_field(
									array(
										'name'     => 'name',
										'id'       => 'pf-name',
										'label'    => __( 'نام', 'pixva' ),
										'required' => true,
										'value'    => $pixva_user->display_name,
										'attrs'    => array( 'autocomplete' => 'name' ),
									),
									$pixva_rp
								);
								pixva_field(
									array(
										'name'  => 'phone',
										'id'    => 'pf-phone',
										'label' => __( 'شماره همراه', 'pixva' ),
										'type'  => 'tel',
										'value' => (string) get_user_meta( $pixva_user->ID, 'pixva_phone', true ),
										'help'  => __( 'فقط برای پیش‌پر کردن فرم‌ها؛ دسترسی به پرونده‌ها با شماره همراه داده نمی‌شود.', 'pixva' ),
										'attrs' => array(
											'autocomplete' => 'tel',
											'dir'          => 'ltr',
											'inputmode'    => 'tel',
										),
									),
									$pixva_rp
								);
								pixva_field(
									array(
										'name'  => 'email',
										'id'    => 'pf-email',
										'label' => __( 'ایمیل', 'pixva' ),
										'type'  => 'email',
										'value' => $pixva_user->user_email,
										'attrs' => array(
											'autocomplete' => 'email',
											'dir'          => 'ltr',
										),
									),
									$pixva_rp
								);
								pixva_field(
									array(
										'name'  => 'current_password',
										'id'    => 'pf-pass',
										'label' => __( 'رمز فعلی (فقط برای تغییر ایمیل)', 'pixva' ),
										'type'  => 'password',
										'attrs' => array(
											'autocomplete' => 'current-password',
											'dir'          => 'ltr',
										),
									),
									$pixva_rp
								);
								?>
								<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'ذخیره', 'pixva' ); ?></button></div>
							</form>
							<p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'تغییر رمز عبور', 'pixva' ); ?></a></p>
						</section>

					<?php else : ?>
						<?php
						$pixva_active = array_filter( $pixva_orders, static fn( $o ) => ! in_array( (string) get_post_meta( $o, '_pixva_order_status', true ), array( 'delivered', 'cancelled' ), true ) );
						?>
						<h2><?php echo esc_html( sprintf( /* translators: %s: name. */ __( 'سلام %s', 'pixva' ), $pixva_user->display_name ) ); ?></h2>
						<ul class="stats">
							<li><span class="stats__num"><?php echo esc_html( pixva_fa_num( count( $pixva_active ) ) ); ?></span> <span><?php esc_html_e( 'درخواست در جریان', 'pixva' ); ?></span></li>
							<li><span class="stats__num"><?php echo esc_html( pixva_fa_num( count( $pixva_orders ) ) ); ?></span> <span><?php esc_html_e( 'کل درخواست‌ها', 'pixva' ); ?></span></li>
						</ul>
						<?php if ( $pixva_active ) : ?>
							<?php foreach ( array_slice( $pixva_active, 0, 3 ) as $pixva_o ) : ?>
								<?php pixva_order_view( pixva_order_public_view( $pixva_o ) ); ?>
							<?php endforeach; ?>
						<?php else : ?>
							<?php
							pixva_empty_state(
								__( 'درخواست در جریانی ندارید', 'pixva' ),
								'',
								array(
									__( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ),
									__( 'تشخیص آنلاین', 'pixva' ) => pixva_route_url( 'diagnosis' ),
								)
							);
							?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
