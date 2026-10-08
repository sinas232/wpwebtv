<?php
/**
 * Template Name: PIXVA — داشبورد عملیات
 *
 * Role-aware dashboard (§15, §50). Access requires pixva_view_dashboard
 * (enforced with a 403 in inc/account.php); each panel checks its own
 * capability. Real data only — empty panels say so instead of showing
 * placeholder numbers. Noindex + no-store.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_user = wp_get_current_user();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container section">
		<?php if ( ! $pixva_user->exists() ) : ?>
			<section class="panel form-wrap" aria-labelledby="db-login">
				<h2 class="panel__title" id="db-login"><?php esc_html_e( 'ورود کارکنان', 'pixva' ); ?></h2>
				<?php
				wp_login_form(
					array(
						'redirect'       => pixva_current_url(),
						'label_username' => __( 'ایمیل یا نام کاربری', 'pixva' ),
						'label_password' => __( 'رمز عبور', 'pixva' ),
						'label_log_in'   => __( 'ورود', 'pixva' ),
						'id_submit'      => 'db-login-submit',
					)
				);
				?>
			</section>

		<?php elseif ( ! current_user_can( 'pixva_view_dashboard' ) ) : ?>
			<?php pixva_empty_state( __( 'دسترسی ندارید', 'pixva' ), __( 'این بخش مخصوص کارکنان است. درخواست‌های خود را در حساب کاربری ببینید.', 'pixva' ), array( __( 'حساب کاربری', 'pixva' ) => pixva_route_url( 'account' ) ) ); ?>

		<?php else : ?>
			<?php
			$pixva_ru = pixva_form_result( 'pixva_order_update' );
			if ( $pixva_ru ) :
				?>
				<div id="pixva-order-update" tabindex="-1">
					<?php
					if ( $pixva_ru['ok'] ) {
						pixva_notice( 'success', $pixva_ru['payload']['message'], '', true );
					} else {
						pixva_form_status( $pixva_ru );
					}
					?>
				</div>
			<?php endif; ?>

			<?php if ( current_user_can( 'pixva_manage_orders' ) ) : ?>
				<?php
				$pixva_counts = pixva_order_status_counts();
				$pixva_list   = admin_url( 'edit.php?post_type=pixva_orders' );
				?>
				<section class="section--tight" aria-labelledby="db-orders">
					<h2 id="db-orders"><?php esc_html_e( 'درخواست‌ها بر اساس وضعیت', 'pixva' ); ?></h2>
					<?php if ( array_sum( $pixva_counts ) ) : ?>
						<ul class="stats">
							<?php foreach ( pixva_order_statuses() as $pixva_k => $pixva_s ) : ?>
								<li class="badge--status-<?php echo esc_attr( $pixva_k ); ?>"><span class="stats__num"><?php echo esc_html( pixva_fa_num( $pixva_counts[ $pixva_k ] ?? 0 ) ); ?></span> <span><?php echo esc_html( $pixva_s['label'] ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php esc_html_e( 'هنوز درخواستی ثبت نشده است.', 'pixva' ); ?></p>
					<?php endif; ?>
					<p><a class="btn btn--ghost" href="<?php echo esc_url( $pixva_list ); ?>"><?php esc_html_e( 'مدیریت درخواست‌ها', 'pixva' ); ?></a></p>
				</section>

				<div class="grid grid--2">
					<section class="panel" aria-labelledby="db-unassigned">
						<h2 class="panel__title" id="db-unassigned"><?php esc_html_e( 'بدون تکنسین', 'pixva' ); ?></h2>
						<?php $pixva_ids = pixva_unassigned_orders(); ?>
						<?php if ( $pixva_ids ) : ?>
							<ul class="link-list">
								<?php foreach ( $pixva_ids as $pixva_id ) : ?>
									<li><a href="<?php echo esc_url( (string) get_edit_post_link( $pixva_id, 'raw' ) ); ?>" dir="ltr"><?php echo esc_html( (string) get_post_meta( $pixva_id, '_pixva_order_code', true ) ); ?></a> — <?php echo esc_html( pixva_order_statuses()[ (string) get_post_meta( $pixva_id, '_pixva_order_status', true ) ]['label'] ?? '' ); ?> — <?php echo esc_html( pixva_format_date( get_post_time( 'U', true, $pixva_id ) ) ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php esc_html_e( 'همه درخواست‌های باز تکنسین دارند.', 'pixva' ); ?></p>
						<?php endif; ?>
					</section>
					<section class="panel" aria-labelledby="db-warranty">
						<h2 class="panel__title" id="db-warranty"><?php esc_html_e( 'گارانتی‌های رو به پایان (۳۰ روز)', 'pixva' ); ?></h2>
						<?php $pixva_ids = pixva_expiring_warranties( 30 ); ?>
						<?php if ( $pixva_ids ) : ?>
							<ul class="link-list">
								<?php foreach ( $pixva_ids as $pixva_id ) : ?>
									<?php $pixva_w = pixva_order_warranty( $pixva_id ); ?>
									<li><a href="<?php echo esc_url( (string) get_edit_post_link( $pixva_id, 'raw' ) ); ?>" dir="ltr"><?php echo esc_html( (string) get_post_meta( $pixva_id, '_pixva_order_code', true ) ); ?></a> — <?php echo esc_html( '' !== $pixva_w['until'] ? pixva_format_date( (int) strtotime( $pixva_w['until'] ) ) : '' ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php esc_html_e( 'موردی نیست.', 'pixva' ); ?></p>
						<?php endif; ?>
					</section>
				</div>
				<?php if ( current_user_can( 'edit_pixva_messages' ) ) : ?>
					<?php $pixva_msgs = wp_count_posts( 'pixva_inbox' ); ?>
					<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_inbox' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: count. */ __( 'پیام‌های تماس: %s', 'pixva' ), pixva_fa_num( (int) ( $pixva_msgs->private ?? 0 ) ) ) ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( current_user_can( 'pixva_work_orders' ) && ! current_user_can( 'pixva_manage_orders' ) ) : ?>
				<?php $pixva_ids = pixva_technician_orders( $pixva_user->ID ); ?>
				<section class="section--tight" aria-labelledby="db-mine">
					<h2 id="db-mine"><?php esc_html_e( 'پرونده‌های من', 'pixva' ); ?></h2>
					<?php if ( ! $pixva_ids ) : ?>
						<p><?php esc_html_e( 'پرونده بازی به شما ارجاع نشده است.', 'pixva' ); ?></p>
					<?php endif; ?>
					<?php foreach ( $pixva_ids as $pixva_id ) : ?>
						<?php
						if ( ! current_user_can( 'pixva_work_order', $pixva_id ) ) {
							continue;
						}
						$pixva_v     = pixva_order_public_view( $pixva_id );
						$pixva_desc  = (string) get_post_meta( $pixva_id, '_pixva_order_description', true );
						$pixva_notes = (string) get_post_meta( $pixva_id, '_pixva_order_notes', true );
						?>
						<article class="panel order order--staff" aria-labelledby="ord-<?php echo (int) $pixva_id; ?>">
							<h3 class="panel__title" id="ord-<?php echo (int) $pixva_id; ?>"><span dir="ltr"><?php echo esc_html( $pixva_v['code'] ); ?></span> — <span dir="auto"><?php echo esc_html( $pixva_v['device'] ); ?></span></h3>
							<p><span class="badge badge--status-<?php echo esc_attr( $pixva_v['status'] ); ?>"><?php echo esc_html( $pixva_v['label'] ); ?></span> · <?php esc_html_e( 'تماس:', 'pixva' ); ?> <span dir="ltr"><?php echo esc_html( pixva_mask_phone( (string) get_post_meta( $pixva_id, '_pixva_order_phone', true ) ) ); ?></span></p>
							<?php if ( '' !== $pixva_desc ) : ?>
								<p dir="auto"><?php echo nl2br( esc_html( $pixva_desc ) ); ?></p>
							<?php endif; ?>
							<?php $pixva_photos = pixva_order_photos( $pixva_id ); ?>
							<?php if ( $pixva_photos ) : ?>
								<ul class="link-list">
									<?php foreach ( $pixva_photos as $pixva_i => $pixva_file ) : ?>
										<li><a href="<?php echo esc_url( pixva_order_photo_url( $pixva_id, $pixva_file ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( sprintf( /* translators: %s: number. */ __( 'تصویر %s', 'pixva' ), pixva_fa_num( $pixva_i + 1 ) ) ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( '' !== $pixva_notes ) : ?>
								<details><summary><?php esc_html_e( 'یادداشت‌های داخلی', 'pixva' ); ?></summary><p dir="auto"><?php echo nl2br( esc_html( $pixva_notes ) ); ?></p></details>
							<?php endif; ?>
							<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-reload-on-success novalidate>
								<?php pixva_form_fields( 'pixva_order_update' ); ?>
								<input type="hidden" name="order" value="<?php echo (int) $pixva_id; ?>">
								<div class="form-grid">
									<?php
									$pixva_opts = array();
									foreach ( pixva_order_statuses() as $pixva_k => $pixva_s ) {
										$pixva_opts[ $pixva_k ] = $pixva_s['label'];
									}
									pixva_field(
										array(
											'name'    => 'status',
											'id'      => 'st-' . $pixva_id,
											'type'    => 'select',
											'label'   => __( 'وضعیت', 'pixva' ),
											'options' => $pixva_opts,
											'value'   => $pixva_v['status'],
										),
										null
									);
									pixva_field(
										array(
											'name'  => 'note',
											'id'    => 'nt-' . $pixva_id,
											'type'  => 'textarea',
											'label' => __( 'یادداشت برای مشتری (نمایش در پیگیری)', 'pixva' ),
											'attrs' => array( 'rows' => '2' ),
										),
										null
									);
									pixva_field(
										array(
											'name'  => 'internal',
											'id'    => 'in-' . $pixva_id,
											'type'  => 'textarea',
											'label' => __( 'یادداشت داخلی (فقط کارکنان)', 'pixva' ),
											'attrs' => array( 'rows' => '2' ),
										),
										null
									);
									?>
								</div>
								<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'ثبت', 'pixva' ); ?></button></div>
							</form>
						</article>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'pixva_view_content_health' ) ) : ?>
				<?php $pixva_health = pixva_content_health(); ?>
				<section class="section--tight" aria-labelledby="db-health">
					<h2 id="db-health"><?php esc_html_e( 'سلامت محتوا', 'pixva' ); ?></h2>
					<?php if ( ! $pixva_health ) : ?>
						<p><?php esc_html_e( 'مشکلی در محتوای منتشرشده پیدا نشد.', 'pixva' ); ?></p>
					<?php endif; ?>
					<?php foreach ( $pixva_health as $pixva_group ) : ?>
						<details class="panel">
							<summary><?php echo esc_html( $pixva_group['label'] ); ?> (<?php echo esc_html( pixva_fa_num( count( $pixva_group['items'] ) ) ); ?>)</summary>
							<ul class="link-list">
								<?php foreach ( $pixva_group['items'] as $pixva_item ) : ?>
									<li><?php echo '' !== $pixva_item['url'] ? '<a href="' . esc_url( $pixva_item['url'] ) . '">' . esc_html( $pixva_item['title'] ) . '</a>' : esc_html( $pixva_item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline. ?></li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
