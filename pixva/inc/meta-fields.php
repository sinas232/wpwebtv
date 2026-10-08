<?php
/**
 * Declarative meta-field framework (§37, §47, §53).
 *
 * Content types declare their structured fields once via the
 * `pixva_meta_schema` filter (see content-model.php). This module then:
 *  - registers each key with register_post_meta (typed, sanitized,
 *    auth_callback → edit_post), exposed to REST only when public;
 *  - renders an accessible meta box (labels, help text, required hints);
 *  - saves with nonce + capability + autosave/revision guards.
 *
 * Field types: text, textarea, html, int, url, select, checkbox, post,
 * posts, image, lines.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full schema: post_type => [ box_id => [title, fields => [key => def]] ].
 *
 * Field def keys: type, label, help, options (select), post_type (post/posts),
 * public (expose in REST), required (UI hint + content-health check).
 *
 * @return array
 */
function pixva_meta_schema() {
	static $schema = null;
	if ( null === $schema ) {
		$schema = (array) apply_filters( 'pixva_meta_schema', array() );
	}
	return $schema;
}

/**
 * Flattened field defs for a post type: key => def.
 *
 * @param string $post_type Post type.
 * @return array
 */
function pixva_meta_fields_for( $post_type ) {
	$out = array();
	foreach ( pixva_meta_schema()[ $post_type ] ?? array() as $box ) {
		foreach ( $box['fields'] as $key => $def ) {
			$out[ $key ] = $def;
		}
	}
	return $out;
}

/**
 * Sanitize one value according to its field def.
 *
 * @param mixed $value Raw (unslashed) value.
 * @param array $def   Field def.
 * @return mixed
 */
function pixva_sanitize_meta_value( $value, $def ) {
	switch ( $def['type'] ) {
		case 'int':
		case 'post':
		case 'image':
			return max( 0, (int) pixva_en_num( is_scalar( $value ) ? (string) $value : '' ) );
		case 'checkbox':
			return empty( $value ) ? 0 : 1;
		case 'url':
			return esc_url_raw( is_scalar( $value ) ? (string) $value : '', array( 'http', 'https' ) );
		case 'html':
			return wp_kses( is_scalar( $value ) ? (string) $value : '', pixva_kses_content() );
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( is_scalar( $value ) ? (string) $value : '' );
		case 'select':
			$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );
			return array_key_exists( $value, (array) ( $def['options'] ?? array() ) ) ? $value : '';
		case 'posts':
			$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			return implode( ',', $ids );
		default:
			return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
	}
}

/**
 * Register all declared meta keys.
 *
 * @return void
 */
function pixva_register_declared_meta() {
	foreach ( pixva_meta_schema() as $post_type => $boxes ) {
		foreach ( pixva_meta_fields_for( $post_type ) as $key => $def ) {
			$is_int = in_array( $def['type'], array( 'int', 'post', 'image', 'checkbox' ), true );
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $is_int ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => ! empty( $def['public'] ),
					'sanitize_callback' => static function ( $value ) use ( $def ) {
						return pixva_sanitize_meta_value( $value, $def );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'pixva_register_declared_meta', 20 );

/**
 * Add meta boxes for declared schemas.
 *
 * @param string $post_type Current post type.
 * @return void
 */
function pixva_add_declared_meta_boxes( $post_type ) {
	foreach ( pixva_meta_schema()[ $post_type ] ?? array() as $box_id => $box ) {
		add_meta_box(
			'pixva_' . $box_id,
			$box['title'],
			'pixva_render_meta_box',
			$post_type,
			$box['context'] ?? 'normal',
			'high',
			array( 'box' => $box_id )
		);
	}
}
add_action( 'add_meta_boxes', 'pixva_add_declared_meta_boxes' );

/**
 * Options for a post / posts field.
 *
 * @param string $post_type Target post type.
 * @return array<int,string>
 */
function pixva_meta_post_choices( $post_type ) {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'   => 500,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);
	$out   = array();
	foreach ( $posts as $p ) {
		$out[ (int) $p->ID ] = get_the_title( $p ) . ( 'publish' !== $p->post_status ? ' (' . $p->post_status . ')' : '' );
	}
	return $out;
}

/**
 * Render a declared meta box.
 *
 * @param WP_Post $post Post.
 * @param array   $mb   Meta box args.
 * @return void
 */
function pixva_render_meta_box( $post, $mb ) {
	$box_id = $mb['args']['box'];
	$box    = pixva_meta_schema()[ $post->post_type ][ $box_id ] ?? null;
	if ( ! $box ) {
		return;
	}
	wp_nonce_field( 'pixva_meta_' . $post->post_type, 'pixva_meta_nonce' );
	if ( ! empty( $box['intro'] ) ) {
		echo '<p class="description">' . esc_html( $box['intro'] ) . '</p>';
	}
	echo '<div class="pixva-meta-grid">';
	foreach ( $box['fields'] as $key => $def ) {
		$id    = 'pixva-meta-' . sanitize_html_class( $key );
		$value = get_post_meta( $post->ID, $key, true );
		$help  = $def['help'] ?? '';
		$req   = ! empty( $def['required'] );
		echo '<div class="pixva-meta-field pixva-meta-field--' . esc_attr( $def['type'] ) . '">';
		if ( 'checkbox' !== $def['type'] ) {
			echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $def['label'] ) . '</strong>' . ( $req ? ' <span class="pixva-meta-req">' . esc_html__( '(برای صفحه کامل لازم است)', 'pixva' ) . '</span>' : '' ) . '</label>';
		}
		$describedby = $help ? ' aria-describedby="' . esc_attr( $id ) . '-help"' : '';
		switch ( $def['type'] ) {
			case 'textarea':
			case 'lines':
				echo '<textarea class="widefat" rows="' . ( 'lines' === $def['type'] ? 5 : 3 ) . '" id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']"' . $describedby . '>' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute escaped above.
				break;
			case 'html':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => 'pixva_meta[' . $key . ']',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'teeny'         => true,
					)
				);
				break;
			case 'checkbox':
				echo '<label for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']" value="1" ' . checked( (int) $value, 1, false ) . $describedby . '> ' . esc_html( $def['label'] ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']"' . $describedby . '><option value="">' . esc_html__( '— انتخاب —', 'pixva' ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( (array) $def['options'] as $opt => $label ) {
					echo '<option value="' . esc_attr( $opt ) . '" ' . selected( (string) $value, (string) $opt, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'post':
				echo '<select id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']"' . $describedby . '><option value="0">' . esc_html__( '— هیچ —', 'pixva' ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( pixva_meta_post_choices( $def['post_type'] ) as $pid => $title ) {
					echo '<option value="' . esc_attr( $pid ) . '" ' . selected( (int) $value, $pid, false ) . '>' . esc_html( $title ) . '</option>';
				}
				echo '</select>';
				break;
			case 'posts':
				$selected = array_map( 'absint', array_filter( explode( ',', (string) $value ) ) );
				echo '<select multiple size="6" class="widefat" id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . '][]"' . $describedby . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( pixva_meta_post_choices( $def['post_type'] ) as $pid => $title ) {
					echo '<option value="' . esc_attr( $pid ) . '" ' . selected( in_array( $pid, $selected, true ), true, false ) . '>' . esc_html( $title ) . '</option>';
				}
				echo '</select><input type="hidden" name="pixva_meta_present[' . esc_attr( $key ) . ']" value="1">';
				break;
			case 'image':
				$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
				echo '<div class="pixva-image-field" data-pixva-image>';
				echo '<input type="number" min="0" id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) (int) $value ) . '"' . $describedby . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' <button type="button" class="button" data-pixva-image-pick>' . esc_html__( 'انتخاب تصویر', 'pixva' ) . '</button>';
				echo ' <button type="button" class="button-link" data-pixva-image-clear>' . esc_html__( 'حذف', 'pixva' ) . '</button>';
				echo '<div class="pixva-image-preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '' ) . '</div></div>';
				break;
			default:
				$type = 'url' === $def['type'] ? 'url' : ( 'int' === $def['type'] ? 'number' : 'text' );
				echo '<input class="widefat" type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="pixva_meta[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $value ) . '"' . $describedby . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( $help ) {
			echo '<p class="description" id="' . esc_attr( $id ) . '-help">' . esc_html( $help ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Save declared meta.
 *
 * @param int     $post_id Post id.
 * @param WP_Post $post    Post.
 * @return void
 */
function pixva_save_declared_meta( $post_id, $post ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$fields = pixva_meta_fields_for( $post->post_type );
	if ( ! $fields || ! isset( $_POST['pixva_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pixva_meta_nonce'] ) ), 'pixva_meta_' . $post->post_type ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$input   = isset( $_POST['pixva_meta'] ) && is_array( $_POST['pixva_meta'] ) ? wp_unslash( $_POST['pixva_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field.
	$present = isset( $_POST['pixva_meta_present'] ) && is_array( $_POST['pixva_meta_present'] ) ? array_map( 'sanitize_key', array_keys( wp_unslash( $_POST['pixva_meta_present'] ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( $fields as $key => $def ) {
		if ( ! empty( $def['readonly'] ) ) {
			continue;
		}
		if ( ! array_key_exists( $key, $input ) && 'checkbox' !== $def['type'] && ! in_array( sanitize_key( $key ), $present, true ) ) {
			continue;
		}
		$value = pixva_sanitize_meta_value( $input[ $key ] ?? '', $def );
		if ( '' === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, wp_slash( $value ) ); // Meta API unslashes.
		}
	}
	do_action( 'pixva_meta_saved', $post_id, $post );
}
add_action( 'save_post', 'pixva_save_declared_meta', 10, 2 );

/**
 * Typed getter.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @return mixed
 */
function pixva_meta( $post_id, $key ) {
	return get_post_meta( (int) $post_id, $key, true );
}

/**
 * Non-empty lines from a "lines" field.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @return string[]
 */
function pixva_meta_lines( $post_id, $key ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) get_post_meta( (int) $post_id, $key, true ) ) ) ) );
}

/**
 * Published post ids from a "posts" field.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @return int[]
 */
function pixva_meta_ids( $post_id, $key ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( (int) $post_id, $key, true ) ) ) );
	return array_values( array_filter( $ids, static fn( $id ) => 'publish' === get_post_status( $id ) ) );
}
