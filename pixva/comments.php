<?php
/**
 * Comments.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) || post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments" aria-labelledby="comments-title">
	<?php if ( have_comments() ) : ?>
		<h2 id="comments-title"><?php echo esc_html( sprintf( /* translators: %s: count. */ __( '%s دیدگاه', 'pixva' ), pixva_fa_num( get_comments_number() ) ) ); ?></h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'callback' => 'pixva_comment_item',
					'style'    => 'ol',
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php else : ?>
		<h2 id="comments-title" class="screen-reader-text"><?php esc_html_e( 'دیدگاه‌ها', 'pixva' ); ?></h2>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'          => __( 'دیدگاه یا پرسش خود را بنویسید', 'pixva' ),
			'label_submit'         => __( 'ارسال دیدگاه', 'pixva' ),
			'comment_notes_before' => '<p class="field__help">' . esc_html__( 'ایمیل شما منتشر نمی‌شود. شماره تماس در متن دیدگاه ننویسید.', 'pixva' ) . '</p>',
			'class_submit'         => 'btn btn--primary',
		)
	);
	?>
</section>
