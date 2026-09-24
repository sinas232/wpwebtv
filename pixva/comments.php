<?php
/**
 * دیدگاه‌های مقاله
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="pixva-comments">
	<?php if ( have_comments() ) : ?>
		<h2>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: تعداد دیدگاه */
					__( 'دیدگاه‌ها (%s)', 'pixva' ),
					pixva_fa_num( (string) get_comments_number() )
				)
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
					'callback'    => 'pixva_comment_item',
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'          => __( 'تجربه تعمیرتان را بنویسید', 'pixva' ),
			'label_submit'         => __( 'ارسال دیدگاه', 'pixva' ),
			'comment_notes_before' => '',
			'comment_notes_after'  => '',
			'class_submit'         => 'submit',
			'comment_field'        => '<div class="pixva-field"><label for="comment">' . esc_html__( 'متن دیدگاه', 'pixva' ) . '</label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></div>',
		)
	);
	?>
</section>
