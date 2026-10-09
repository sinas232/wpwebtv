<?php
/**
 * Presentation helpers used by templates. Pure output helpers; no business
 * logic. Every helper that prints business data reads pixva_claim() and
 * prints nothing when unset (§35).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * Branding & navigation
 * ---------------------------------------------------------------------------
 */

/**
 * Site logo (custom logo or text mark).
 *
 * @return void
 */
function pixva_logo() {
	echo '<a class="brand" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	if ( has_custom_logo() ) {
		$id = (int) get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image(
			$id,
			'full',
			false,
			array(
				'class'    => 'brand__img',
				'alt'      => get_bloginfo( 'name' ),
				'loading'  => 'eager',
				'decoding' => 'async',
			)
		);
	} else {
		echo '<span class="brand__mark" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="currentColor"/><path class="brand__bolt" d="M9 18 16 6l-1.5 9H23l-9 11 2-8z"/></svg></span>';
		echo '<span class="brand__text"><span class="brand__name">' . esc_html( get_bloginfo( 'name' ) ) . '</span><span class="brand__tag">' . esc_html__( 'تشخیص و تعمیر تلویزیون', 'pixva' ) . '</span></span>';
	}
	echo '</a>';
}

/**
 * Primary navigation (menu location or route-based fallback).
 *
 * @param string $location Menu location.
 * @param string $id       Element id prefix.
 * @return void
 */
function pixva_nav( $location, $id ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'nav__list',
				'menu_id'        => $id,
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$items = array( 'services', 'tools', 'problems', 'error_codes', 'blog', 'contact' );
	$route = pixva_current_route();
	echo '<ul class="nav__list" id="' . esc_attr( $id ) . '">';
	foreach ( $items as $key ) {
		$def   = pixva_routes()[ $key ];
		$cur   = $route === $key || ( 'tools' === $key && in_array( $route, array( 'diagnosis', 'price_calculator', 'pixel_test' ), true ) );
		$label = array(
			'services'    => __( 'خدمات', 'pixva' ),
			'tools'       => __( 'ابزارها', 'pixva' ),
			'problems'    => __( 'مشکلات رایج', 'pixva' ),
			'error_codes' => __( 'کدهای خطا', 'pixva' ),
			'blog'        => __( 'مجله', 'pixva' ),
			'contact'     => __( 'تماس', 'pixva' ),
		)[ $key ] ?? $def['title'];
		echo '<li><a href="' . esc_url( pixva_route_url( $key ) ) . '"' . ( $cur ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/*
 * ---------------------------------------------------------------------------
 * Breadcrumbs
 * ---------------------------------------------------------------------------
 */

/**
 * Breadcrumb trail for the current request: [{title, url}] (last has url '').
 *
 * @return array<int,array{title:string,url:string}>
 */
function pixva_breadcrumb_items() {
	$items       = array(
		array(
			'title' => __( 'خانه', 'pixva' ),
			'url'   => home_url( '/' ),
		),
	);
	$add         = static function ( $title, $url = '' ) use ( &$items ) {
		$items[] = array(
			'title' => wp_strip_all_tags( (string) $title ),
			'url'   => (string) $url,
		);
	};
	$route_crumb = static function ( $key ) use ( $add ) {
		$add( pixva_routes()[ $key ]['title'], pixva_route_url( $key ) );
	};
	if ( is_front_page() ) {
		return $items;
	}
	if ( is_home() ) {
		$add( pixva_routes()['blog']['title'] );
	} elseif ( is_singular( 'post' ) ) {
		$route_crumb( 'blog' );
		$cats = get_the_category();
		if ( $cats ) {
			$add( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$add( get_the_title() );
	} elseif ( is_singular( 'tv_model' ) ) {
		$route_crumb( 'brands' );
		$brand = pixva_model_brand( get_queried_object_id() );
		if ( $brand ) {
			$add( get_the_title( $brand ), get_permalink( $brand ) );
		}
		$add( get_the_title() );
	} elseif ( is_singular( array( 'tv_services', 'tv_brands', 'pixva_error', 'repair_cases' ) ) ) {
		$map = array(
			'tv_services'  => 'services',
			'tv_brands'    => 'brands',
			'pixva_error'  => 'error_codes',
			'repair_cases' => 'portfolio',
		);
		$route_crumb( $map[ get_post_type() ] );
		$add( get_the_title() );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $anc ) {
			$add( get_the_title( $anc ), get_permalink( $anc ) );
		}
		$add( get_the_title() );
	} elseif ( is_post_type_archive() ) {
		$route = pixva_current_route();
		$add( $route ? pixva_routes()[ $route ]['title'] : post_type_archive_title( '', false ) );
	} elseif ( is_category() ) {
		$route_crumb( 'blog' );
		$add( single_cat_title( '', false ) );
	} elseif ( is_tax( 'tv_problem' ) ) {
		$route_crumb( 'problems' );
		$add( single_term_title( '', false ) );
	} elseif ( is_search() ) {
		$add( __( 'جست‌وجو', 'pixva' ) );
	} elseif ( is_404() ) {
		$add( __( 'صفحه پیدا نشد', 'pixva' ) );
	} elseif ( is_archive() ) {
		$add( wp_strip_all_tags( get_the_archive_title() ) );
	}
	return $items;
}

/**
 * Print breadcrumbs.
 *
 * @return void
 */
function pixva_breadcrumbs() {
	$items = pixva_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="crumbs" aria-label="' . esc_attr__( 'مسیر صفحه', 'pixva' ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $c ) {
		if ( $i === $last || '' === $c['url'] ) {
			echo '<li><span aria-current="page">' . esc_html( $c['title'] ) . '</span></li>';
		} else {
			echo '<li><a href="' . esc_url( $c['url'] ) . '">' . esc_html( $c['title'] ) . '</a></li>';
		}
	}
	echo '</ol></nav>';
}

/*
 * ---------------------------------------------------------------------------
 * Page structure
 * ---------------------------------------------------------------------------
 */

/**
 * Page header with breadcrumbs, H1 and optional lead.
 *
 * @param string $title Title.
 * @param string $lead  Lead text (plain).
 * @param string $eyebrow Small label above title.
 * @return void
 */
function pixva_page_header( $title, $lead = '', $eyebrow = '' ) {
	echo '<header class="page-head"><div class="container">';
	pixva_breadcrumbs();
	if ( '' !== $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h1 class="page-head__title">' . esc_html( $title ) . '</h1>';
	if ( '' !== trim( $lead ) ) {
		echo '<p class="page-head__lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</div></header>';
}

/**
 * Editor intro content of the current page (if any).
 *
 * @return void
 */
function pixva_page_intro() {
	$post = get_post();
	if ( $post && '' !== trim( $post->post_content ) ) {
		echo '<div class="entry-content page-intro">';
		the_content();
		echo '</div>';
	}
}

/**
 * Status / notice box.
 *
 * @param string $type    info|success|warning|error.
 * @param string $message Message (plain).
 * @param string $title   Optional title.
 * @param bool   $live    Whether to announce (role=status/alert).
 * @return void
 */
function pixva_notice( $type, $message, $title = '', $live = false ) {
	$icon = array(
		'info'    => 'info',
		'success' => 'check',
		'warning' => 'alert',
		'error'   => 'alert',
	)[ $type ] ?? 'info';
	$role = $live ? ( 'error' === $type ? ' role="alert"' : ' role="status"' ) : '';
	echo '<div class="notice notice--' . esc_attr( $type ) . '"' . $role . '>' . wp_kses( pixva_icon( $icon ), pixva_svg_allowed() ) . '<div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute.
	if ( '' !== $title ) {
		echo '<strong class="notice__title">' . esc_html( $title ) . '</strong>';
	}
	echo '<p>' . esc_html( $message ) . '</p></div></div>';
}

/**
 * Empty state with optional actions.
 *
 * @param string $title   Title.
 * @param string $text    Text.
 * @param array  $actions [label => url].
 * @return void
 */
function pixva_empty_state( $title, $text, $actions = array() ) {
	echo '<div class="empty"><p class="empty__title">' . esc_html( $title ) . '</p>';
	if ( '' !== $text ) {
		echo '<p class="empty__text">' . esc_html( $text ) . '</p>';
	}
	if ( $actions ) {
		echo '<p class="empty__actions">';
		$first = true;
		foreach ( $actions as $label => $url ) {
			echo '<a class="btn ' . ( $first ? 'btn--primary' : 'btn--ghost' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
			$first = false;
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Call-to-action box: diagnosis + booking, and the phone only if configured.
 *
 * @param string $title Title.
 * @param string $text  Text.
 * @param string $from  Analytics location.
 * @return void
 */
function pixva_cta_box( $title = '', $text = '', $from = 'cta_box' ) {
	$title = '' !== $title ? $title : __( 'مطمئن نیستید ایراد از کجاست؟', 'pixva' );
	$text  = '' !== $text ? $text : __( 'با ابزار تشخیص علت‌های محتمل را ببینید یا مستقیم درخواست بررسی ثبت کنید.', 'pixva' );
	$phone = pixva_primary_phone();
	echo '<aside class="cta reveal" aria-label="' . esc_attr( $title ) . '">';
	echo '<span class="cta__icon" aria-hidden="true">' . wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ) . '</span>';
	echo '<div class="cta__body"><h2 class="cta__title">' . esc_html( $title ) . '</h2><p>' . esc_html( $text ) . '</p></div><div class="cta__actions">';
	echo '<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="' . esc_attr( $from ) . '" href="' . esc_url( pixva_route_url( 'booking' ) ) . '">' . esc_html__( 'ثبت درخواست تعمیر', 'pixva' ) . '</a>';
	echo '<a class="btn btn--ghost-light" data-track="cta_click" data-track-label="diagnosis" data-track-location="' . esc_attr( $from ) . '" href="' . esc_url( pixva_route_url( 'diagnosis' ) ) . '">' . esc_html__( 'تشخیص آنلاین', 'pixva' ) . '</a>';
	if ( '' !== $phone ) {
		echo '<a class="btn btn--ghost-light" data-track="cta_click" data-track-label="phone" data-track-location="' . esc_attr( $from ) . '" href="' . esc_url( pixva_tel_href( $phone ) ) . '">' . wp_kses( pixva_icon( 'phone' ), pixva_svg_allowed() ) . '<span dir="ltr">' . esc_html( pixva_fa_num( $phone ) ) . '</span></a>';
	}
	echo '</div></aside>';
}

/*
 * ---------------------------------------------------------------------------
 * Forms
 * ---------------------------------------------------------------------------
 */

/**
 * Accessible field: label, control, help, error (aria-describedby/invalid).
 *
 * @param array      $f Field: name, label, type, required, value, help, options,
 *                      attrs (array), id, rows, placeholder.
 * @param array|null $r Form result (for errors / old input).
 * @return void
 */
function pixva_field( $f, $r = null ) {
	$name  = $f['name'];
	$key   = str_replace( '[]', '', $name );
	$id    = $f['id'] ?? 'f-' . sanitize_html_class( $key );
	$type  = $f['type'] ?? 'text';
	$err   = pixva_field_error( $r, $key );
	$value = pixva_old( $r, $key, (string) ( $f['value'] ?? '' ) );
	$req   = ! empty( $f['required'] );
	$desc  = array();
	if ( ! empty( $f['help'] ) ) {
		$desc[] = $id . '-help';
	}
	if ( '' !== $err ) {
		$desc[] = $id . '-err';
	}
	$attrs = '';
	foreach ( (array) ( $f['attrs'] ?? array() ) as $k => $v ) {
		$attrs .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	$common = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . ( $req ? ' required aria-required="true"' : '' ) . ( $desc ? ' aria-describedby="' . esc_attr( implode( ' ', $desc ) ) . '"' : '' ) . ( '' !== $err ? ' aria-invalid="true"' : '' ) . $attrs;

	echo '<div class="field field--' . esc_attr( $type ) . ( '' !== $err ? ' field--error' : '' ) . '" data-field="' . esc_attr( $key ) . '">';
	if ( 'checkbox' === $type ) {
		$label_html = wp_kses(
			$f['label'],
			array(
				'a' => array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
				),
			)
		);
		echo '<label class="check" for="' . esc_attr( $id ) . '"><input type="checkbox" value="1"' . $common . checked( '1', $value, false ) . '> <span>' . $label_html . '</span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from esc_attr() parts; $label_html is wp_kses() output.
	} else {
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . ( $req ? ' <span class="req" aria-hidden="true">*</span>' : ' <span class="opt">' . esc_html__( '(اختیاری)', 'pixva' ) . '</span>' ) . '</label>';
		if ( 'textarea' === $type ) {
			echo '<textarea rows="' . (int) ( $f['rows'] ?? 4 ) . '"' . $common . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'select' === $type ) {
			echo '<select' . $common . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			foreach ( (array) $f['options'] as $k => $label ) {
				echo '<option value="' . esc_attr( (string) $k ) . '" ' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		} else {
			$ph = isset( $f['placeholder'] ) ? ' placeholder="' . esc_attr( $f['placeholder'] ) . '"' : '';
			echo '<input type="' . esc_attr( $type ) . '" value="' . esc_attr( 'password' === $type || 'file' === $type ? '' : $value ) . '"' . $common . $ph . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	if ( ! empty( $f['help'] ) ) {
		echo '<p class="field__help" id="' . esc_attr( $id ) . '-help">' . esc_html( $f['help'] ) . '</p>';
	}
	echo '<p class="field__error" id="' . esc_attr( $id ) . '-err"' . ( '' === $err ? ' hidden' : '' ) . '>' . esc_html( $err ) . '</p>';
	echo '</div>';
}

/**
 * Form-level status region (server result on no-JS, JS fills it otherwise).
 *
 * @param array|null $r Result.
 * @return void
 */
function pixva_form_status( $r ) {
	echo '<div class="form-status" data-form-status tabindex="-1" aria-live="polite">';
	if ( is_array( $r ) && ! $r['ok'] ) {
		$msg = $r['errors']['_form'] ?? __( 'لطفاً خطاهای مشخص‌شده را برطرف کنید.', 'pixva' );
		pixva_notice( 'error', $msg, '', true );
	}
	echo '</div>';
}

/**
 * Privacy consent label with link to the privacy page (if published).
 *
 * @param string $text Text.
 * @return string Safe HTML.
 */
function pixva_consent_label( $text ) {
	$url = get_privacy_policy_url();
	return esc_html( $text ) . ( $url ? ' <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( '(سیاست حریم خصوصی)', 'pixva' ) . '</a>' : '' );
}

/*
 * ---------------------------------------------------------------------------
 * Cards & lists
 * ---------------------------------------------------------------------------
 */

/**
 * Generic content card.
 *
 * @param WP_Post|int $post  Post.
 * @param string      $meta  Small meta line.
 * @param string      $level Heading level h2|h3.
 * @return void
 */
function pixva_card( $post, $meta = '', $level = 'h3' ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$level = in_array( $level, array( 'h2', 'h3' ), true ) ? $level : 'h3';
	echo '<article class="card">';
	if ( has_post_thumbnail( $post ) ) {
		echo '<div class="card__media">' . get_the_post_thumbnail(
			$post,
			'medium_large',
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			)
		) . '</div>';
	}
	echo '<div class="card__body">';
	if ( '' !== $meta ) {
		echo '<p class="card__meta">' . esc_html( $meta ) . '</p>';
	}
	echo '<' . $level . ' class="card__title"><a class="card__link" href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></' . $level . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted tag.
	$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 22 );
	if ( '' !== trim( $excerpt ) ) {
		echo '<p class="card__text">' . esc_html( $excerpt ) . '</p>';
	}
	echo '</div></article>';
}

/**
 * Grid of cards for a list of post ids/objects.
 *
 * @param array  $posts Posts.
 * @param string $level Heading level.
 * @return void
 */
function pixva_card_grid( $posts, $level = 'h3' ) {
	if ( ! $posts ) {
		return;
	}
	echo '<div class="grid grid--cards">';
	foreach ( $posts as $p ) {
		pixva_card( $p, '', $level );
	}
	echo '</div>';
}

/**
 * Related block (title + list of links). Prints nothing for empty lists.
 *
 * @param string $title Title.
 * @param array  $posts Posts (ids or objects).
 * @return void
 */
function pixva_related_links( $title, $posts ) {
	$posts = array_filter( array_map( 'get_post', (array) $posts ) );
	if ( ! $posts ) {
		return;
	}
	echo '<section class="related"><h2 class="related__title">' . esc_html( $title ) . '</h2><ul class="link-list">';
	foreach ( $posts as $p ) {
		echo '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
	}
	echo '</ul></section>';
}

/**
 * Published posts of a type linked by a meta value.
 *
 * @param string $type  Post type.
 * @param string $key   Meta key.
 * @param mixed  $value Value.
 * @param int    $limit Limit.
 * @param array  $extra Extra args.
 * @return WP_Post[]
 */
function pixva_posts_by_meta( $type, $key, $value, $limit = 6, $extra = array() ) {
	return get_posts(
		array_merge(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'no_found_rows'  => true,
				'meta_key'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			$extra
		)
	);
}

/**
 * Published posts sharing problem terms.
 *
 * @param string|string[] $types   Types.
 * @param int[]           $terms   Term ids.
 * @param int             $exclude Post id to exclude.
 * @param int             $limit   Limit.
 * @return WP_Post[]
 */
function pixva_posts_by_problem( $types, $terms, $exclude = 0, $limit = 6 ) {
	if ( ! $terms ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => $types,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'post__not_in'   => array( (int) $exclude ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'tv_problem',
					'terms'    => array_map( 'intval', $terms ),
				),
			), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
}

/**
 * Problem term ids of a post.
 *
 * @param int $post_id Post.
 * @return int[]
 */
function pixva_post_problem_ids( $post_id ) {
	$ids = wp_get_post_terms( $post_id, 'tv_problem', array( 'fields' => 'ids' ) );
	return is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
}

/**
 * Problem term chips of a post.
 *
 * @param int $post_id Post.
 * @return void
 */
function pixva_problem_chips( $post_id ) {
	$terms = get_the_terms( $post_id, 'tv_problem' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}
	echo '<ul class="chips" aria-label="' . esc_attr__( 'مشکلات مرتبط', 'pixva' ) . '">';
	foreach ( $terms as $t ) {
		echo '<li><a class="chip" href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Bulleted list from lines (escaped). Prints nothing for empty.
 *
 * @param string[] $lines Lines.
 * @param string   $css_class Class.
 * @return void
 */
function pixva_list( $lines, $css_class = 'bullets' ) {
	if ( ! $lines ) {
		return;
	}
	echo '<ul class="' . esc_attr( $css_class ) . '">';
	foreach ( $lines as $l ) {
		echo '<li>' . esc_html( $l ) . '</li>';
	}
	echo '</ul>';
}

/**
 * FAQ items from pixva_faq posts (optionally by topic).
 *
 * @param string $topic Topic key or ''.
 * @return array<int,array{q:string,a:string,topic:string}>
 */
function pixva_faq_items( $topic = '' ) {
	$args = array(
		'post_type'      => 'pixva_faq',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'ASC',
		),
		'no_found_rows'  => true,
	);
	if ( '' !== $topic ) {
		$args['meta_key']   = '_pixva_faq_topic'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$args['meta_value'] = $topic; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	}
	$out = array();
	foreach ( get_posts( $args ) as $p ) {
		$out[] = array(
			'q'     => get_the_title( $p ),
			'a'     => (string) apply_filters( 'the_content', $p->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
			'topic' => (string) get_post_meta( $p->ID, '_pixva_faq_topic', true ),
		);
	}
	return $out;
}

/**
 * Accordion of Q&A (native details/summary: keyboard + screen reader friendly).
 *
 * @param array $items [{q, a(html|plain)}].
 * @param bool  $html  Whether answers are HTML (FAQ CPT) or plain.
 * @return void
 */
function pixva_faq_list( $items, $html = false ) {
	if ( ! $items ) {
		return;
	}
	echo '<div class="faq">';
	foreach ( $items as $it ) {
		echo '<details class="faq__item"><summary>' . esc_html( $it['q'] ) . '</summary><div class="faq__a">';
		echo $html ? wp_kses_post( $it['a'] ) : '<p>' . esc_html( $it['a'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped on both branches.
		echo '</div></details>';
	}
	echo '</div>';
}

/**
 * Pagination for the main query.
 *
 * @return void
 */
function pixva_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'array',
			'prev_text' => __( 'قبلی', 'pixva' ),
			'next_text' => __( 'بعدی', 'pixva' ),
			'mid_size'  => 1,
		)
	);
	if ( ! $links ) {
		return;
	}
	echo '<nav class="pagination" aria-label="' . esc_attr__( 'صفحه‌بندی', 'pixva' ) . '"><ul>';
	foreach ( $links as $l ) {
		echo '<li>' . wp_kses_post( pixva_fa_num( $l ) ) . '</li>';
	}
	echo '</ul></nav>';
}

/**
 * Post meta line: date (Jalali), reading time, reviewer (if set).
 *
 * @return void
 */
function pixva_post_meta_line() {
	$parts   = array();
	$parts[] = '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( pixva_format_date( (int) get_the_date( 'U' ) ) ) . '</time>';
	if ( get_the_modified_date( 'Y-m-d' ) !== get_the_date( 'Y-m-d' ) ) {
		/* translators: %s: date. */
		$parts[] = esc_html( sprintf( __( 'به‌روزرسانی: %s', 'pixva' ), pixva_format_date( (int) get_the_modified_date( 'U' ) ) ) );
	}
	/* translators: %s: minutes. */
	$parts[] = esc_html( sprintf( __( '%s دقیقه مطالعه', 'pixva' ), pixva_fa_num( pixva_reading_time( get_post_field( 'post_content' ) ) ) ) );
	$rev     = (string) get_post_meta( get_the_ID(), '_pixva_reviewed_by', true );
	if ( '' !== $rev ) {
		/* translators: %s: reviewer. */
		$parts[] = esc_html( sprintf( __( 'بازبینی فنی: %s', 'pixva' ), $rev ) );
	}
	echo '<p class="post-meta">' . implode( ' <span aria-hidden="true">·</span> ', $parts ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped.
}

/**
 * Contact details block (only configured claims).
 *
 * @return bool Whether anything was printed.
 */
function pixva_contact_details() {
	$rows = array();
	foreach ( array(
		'phone'  => __( 'تلفن', 'pixva' ),
		'mobile' => __( 'همراه', 'pixva' ),
	) as $k => $label ) {
		if ( pixva_has_claim( $k ) ) {
			$rows[] = '<dt>' . esc_html( $label ) . '</dt><dd><a dir="ltr" href="' . esc_url( pixva_tel_href( (string) pixva_claim( $k ) ) ) . '">' . esc_html( pixva_fa_num( (string) pixva_claim( $k ) ) ) . '</a></dd>';
		}
	}
	if ( pixva_has_claim( 'whatsapp' ) ) {
		$rows[] = '<dt>' . esc_html__( 'واتس‌اپ', 'pixva' ) . '</dt><dd><a dir="ltr" rel="noopener" href="' . esc_url( 'https://wa.me/' . preg_replace( '/[^0-9]/', '', (string) pixva_claim( 'whatsapp' ) ) ) . '">' . esc_html( pixva_fa_num( (string) pixva_claim( 'whatsapp' ) ) ) . '</a></dd>';
	}
	if ( pixva_has_claim( 'email' ) ) {
		$rows[] = '<dt>' . esc_html__( 'ایمیل', 'pixva' ) . '</dt><dd><a dir="ltr" href="' . esc_url( 'mailto:' . pixva_claim( 'email' ) ) . '">' . esc_html( (string) pixva_claim( 'email' ) ) . '</a></dd>';
	}
	if ( pixva_has_claim( 'address' ) ) {
		$addr   = trim( (string) pixva_claim( 'address' ) );
		$rows[] = '<dt>' . esc_html__( 'نشانی', 'pixva' ) . '</dt><dd><address>' . nl2br( esc_html( $addr ) ) . '</address>' . ( pixva_has_claim( 'map_url' ) ? ' <a rel="noopener" href="' . esc_url( (string) pixva_claim( 'map_url' ) ) . '">' . esc_html__( 'مشاهده روی نقشه', 'pixva' ) . '</a>' : '' ) . '</dd>';
	}
	$hours = pixva_claim_hours();
	if ( $hours ) {
		$h = '<ul class="hours">';
		foreach ( $hours as $row ) {
			$h .= '<li><span>' . esc_html( $row['label'] ) . '</span>' . ( $row['open'] && $row['close'] ? ' <span dir="ltr">' . esc_html( pixva_fa_num( $row['open'] . '–' . $row['close'] ) ) . '</span>' : '' ) . '</li>';
		}
		$rows[] = '<dt>' . esc_html__( 'ساعات کاری', 'pixva' ) . '</dt><dd>' . $h . '</ul></dd>';
	}
	if ( pixva_has_claim( 'service_area' ) ) {
		$rows[] = '<dt>' . esc_html__( 'محدوده خدمت', 'pixva' ) . '</dt><dd>' . esc_html( (string) pixva_claim( 'service_area' ) ) . '</dd>';
	}
	if ( ! $rows ) {
		return false;
	}
	echo '<dl class="details">' . implode( '', $rows ) . '</dl>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows escaped.
	return true;
}

/**
 * Severity badge.
 *
 * @param string $severity Key.
 * @return void
 */
function pixva_severity_badge( $severity ) {
	$levels = pixva_severity_levels();
	if ( ! isset( $levels[ $severity ] ) ) {
		return;
	}
	$short = array(
		'info'     => __( 'اطلاع', 'pixva' ),
		'low'      => __( 'کم', 'pixva' ),
		'medium'   => __( 'متوسط', 'pixva' ),
		'high'     => __( 'زیاد', 'pixva' ),
		'critical' => __( 'بحرانی', 'pixva' ),
	);
	/* translators: %s: severity. */
	echo '<span class="badge badge--sev-' . esc_attr( $severity ) . '">' . esc_html( sprintf( __( 'شدت: %s', 'pixva' ), $short[ $severity ] ) ) . '</span>';
}

/**
 * Comment item callback.
 *
 * @param WP_Comment $comment Comment.
 * @param array      $args    Args.
 * @param int        $depth   Depth.
 * @return void
 */
function pixva_comment_item( $comment, $args, $depth ) {
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment' ); ?>>
		<article class="comment__body">
			<header class="comment__head">
				<strong><?php comment_author(); ?></strong>
				<time datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( pixva_format_date( (int) get_comment_date( 'U' ) ) ); ?></time>
			</header>
			<?php if ( '0' === $comment->comment_approved ) : ?>
				<p class="comment__pending"><?php esc_html_e( 'دیدگاه شما پس از بررسی نمایش داده می‌شود.', 'pixva' ); ?></p>
			<?php endif; ?>
			<div class="entry-content"><?php comment_text(); ?></div>
			<?php
			comment_reply_link(
				array_merge(
					$args,
					array(
						'depth'     => $depth,
						'max_depth' => $args['max_depth'],
					)
				)
			);
			?>
		</article>
	<?php
}

/**
 * Published linked post from a "post" meta field, or null.
 *
 * @param int    $post_id Post.
 * @param string $key     Meta key.
 * @param string $type    Expected post type.
 * @return WP_Post|null
 */
function pixva_linked_post( $post_id, $key, $type ) {
	$id = absint( get_post_meta( $post_id, $key, true ) );
	if ( ! $id || get_post_type( $id ) !== $type || 'publish' !== get_post_status( $id ) ) {
		return null;
	}
	return get_post( $id );
}

/**
 * Section wrapper open/close with heading, optional eyebrow, lead and
 * "see all" link.
 *
 * @param string $id      Section id (for aria-labelledby).
 * @param string $title   Title.
 * @param string $more    "See all" URL.
 * @param string $lead    Lead text.
 * @param string $eyebrow Small label above the title.
 * @param string $class   Extra section class (e.g. section--white, band band--dark band--grid).
 * @return void
 */
function pixva_section_open( $id, $title, $more = '', $lead = '', $eyebrow = '', $class = '' ) {
	$class = trim( 'section ' . $class );
	echo '<section class="' . esc_attr( $class ) . '" aria-labelledby="' . esc_attr( $id ) . '"><div class="container"><div class="section__head"><div class="section__head-main">';
	if ( '' !== $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h2 class="section__title" id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h2>';
	if ( '' !== $lead ) {
		echo '<p class="section__lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</div>';
	if ( '' !== $more ) {
		echo '<a class="link-more" href="' . esc_url( $more ) . '">' . esc_html__( 'مشاهده همه', 'pixva' ) . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</a>';
	}
	echo '</div>';
}

/**
 * Close a section opened with pixva_section_open().
 *
 * @return void
 */
function pixva_section_close() {
	echo '</div></section>';
}

/**
 * Tool descriptors (real, implemented tools only).
 *
 * @return array<string,array{title:string,desc:string,icon:string,url:string}>
 */
function pixva_tools() {
	return array(
		'diagnosis'        => array(
			'title' => __( 'تشخیص آنلاین ایراد', 'pixva' ),
			'desc'  => __( 'با چند پرسش ساده، علت‌های محتمل خرابی و بررسی‌های ایمن را ببینید.', 'pixva' ),
			'icon'  => 'pulse',
			'url'   => pixva_route_url( 'diagnosis' ),
		),
		'price_calculator' => array(
			'title' => __( 'برآورد هزینه تعمیر', 'pixva' ),
			'desc'  => pixva_pricing_active() ? __( 'بازه تقریبی هزینه را بر اساس نوع تعمیر و اندازه صفحه ببینید.', 'pixva' ) : __( 'وضعیت قیمت‌گذاری و هزینه کارشناسی را ببینید و درخواست بررسی ثبت کنید.', 'pixva' ),
			'icon'  => 'calc',
			'url'   => pixva_route_url( 'price_calculator' ),
		),
		'pixel_test'       => array(
			'title' => __( 'تست پیکسل و رنگ صفحه', 'pixva' ),
			'desc'  => __( 'پیکسل سوخته، لکه نور و یکنواختی رنگ را با صفحه‌های تمام‌رنگ بررسی کنید.', 'pixva' ),
			'icon'  => 'grid',
			'url'   => pixva_route_url( 'pixel_test' ),
		),
		'error_codes'      => array(
			'title' => __( 'پایگاه کدهای خطا', 'pixva' ),
			'desc'  => __( 'معنی کد خطا یا تعداد چشمک چراغ پاور را بر اساس برند جست‌وجو کنید.', 'pixva' ),
			'icon'  => 'code',
			'url'   => pixva_route_url( 'error_codes' ),
		),
	);
}

/**
 * Tool cards as a bento composition: one dominant dark card (diagnosis),
 * two medium cards and one wide compact card (error codes). Visual weight
 * follows importance — no four identical boxes.
 *
 * @param string $exclude Tool key to skip (current page).
 * @return void
 */
function pixva_tool_cards( $exclude = '' ) {
	$size = array(
		'diagnosis'        => 'xl',
		'price_calculator' => 'md',
		'pixel_test'       => 'md',
		'error_codes'      => 'wide',
	);
	$kicker = array(
		'diagnosis'        => __( 'ابزار اصلی', 'pixva' ),
		'price_calculator' => __( 'برآورد', 'pixva' ),
		'pixel_test'       => __( 'تست صفحه', 'pixva' ),
		'error_codes'      => __( 'دانشنامه', 'pixva' ),
	);
	echo '<ul class="bento">';
	foreach ( pixva_tools() as $key => $t ) {
		if ( $key === $exclude ) {
			continue;
		}
		$mod = $size[ $key ] ?? 'md';
		echo '<li class="bento__item bento__item--' . esc_attr( $mod ) . ' reveal">';
		echo '<a class="bento__link" href="' . esc_url( $t['url'] ) . '">';
		echo '<span class="bento__icon" aria-hidden="true">' . wp_kses( pixva_icon( $t['icon'] ), pixva_svg_allowed() ) . '</span>';
		if ( 'xl' === $mod ) {
			echo '<span class="bento__kicker">' . esc_html( $kicker[ $key ] ?? '' ) . '</span>';
			echo '<h3 class="bento__title">' . esc_html( $t['title'] ) . '</h3>';
			echo '<span class="bento__text">' . esc_html( $t['desc'] ) . '</span>';
			echo '<span class="bento__steps" aria-hidden="true"><span class="bento__step">' . esc_html__( '۱ · دستگاه', 'pixva' ) . '</span><span class="bento__step">' . esc_html__( '۲ · نشانه‌ها', 'pixva' ) . '</span><span class="bento__step">' . esc_html__( '۳ · نتیجه', 'pixva' ) . '</span></span>';
			echo '<svg class="bento__art" viewBox="0 0 200 120" fill="none" aria-hidden="true"><path d="M0 70 H50 L62 70 74 22 92 108 106 44 118 70 H200" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		} else {
			echo '<span class="bento__body">';
			echo '<span class="bento__kicker">' . esc_html( $kicker[ $key ] ?? '' ) . '</span>';
			echo '<h3 class="bento__title">' . esc_html( $t['title'] ) . '</h3>';
			echo '<span class="bento__text">' . esc_html( $t['desc'] ) . '</span>';
			echo '</span>';
		}
		echo '<span class="bento__go" aria-hidden="true">' . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
		echo '</a></li>';
	}
	echo '</ul>';
}

/**
 * Problem term tiles (only terms with content or posts).
 *
 * @param int $limit Limit (0 = all).
 * @return bool Whether anything was printed.
 */
function pixva_problem_tiles( $limit = 0 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'tv_problem',
			'hide_empty' => false,
			'number'     => $limit,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return false;
	}
	$terms = array_filter( $terms, static fn( $t ) => ! pixva_term_is_thin( $t ) );
	if ( ! $terms ) {
		return false;
	}
	echo '<ul class="grid grid--tiles">';
	foreach ( $terms as $t ) {
		echo '<li class="tile"><a href="' . esc_url( get_term_link( $t ) ) . '"><span class="tile__title">' . esc_html( $t->name ) . '</span>';
		if ( '' !== trim( $t->description ) ) {
			echo '<span class="tile__text">' . esc_html( wp_trim_words( wp_strip_all_tags( $t->description ), 14 ) ) . '</span>';
		}
		echo '</a></li>';
	}
	echo '</ul>';
	return true;
}

/*
 * ---------------------------------------------------------------------------
 * v2.1.0 compositions: problem selector, journey, brand wall, editorial
 * feature/rows. Same data sources as v2.0.0 — only the presentation changed.
 * ---------------------------------------------------------------------------
 */

/**
 * Icon for a diagnosis problem key.
 *
 * @param string $key Problem key.
 * @return string Icon name.
 */
function pixva_problem_icon( $key ) {
	$map = array(
		'no_power'   => 'power',
		'no_picture' => 'picture',
		'lines'      => 'lines',
		'dim_dark'   => 'sun',
		'blink'      => 'blink',
		'no_sound'   => 'sound',
		'spots'      => 'grid',
		'water'      => 'droplet',
		'physical'   => 'crack',
		'restart'    => 'refresh',
		'smart'      => 'cpu',
		'remote'     => 'remote',
	);
	return $map[ $key ] ?? 'tv';
}

/**
 * Diagnostic problem selector: icon tiles that link straight into the wizard
 * with the problem preselected, plus one "other problem" tile.
 *
 * @param int $limit Number of problem tiles.
 * @return void
 */
function pixva_problem_selector( $limit = 8 ) {
	$order = array( 'no_power', 'no_picture', 'lines', 'dim_dark', 'blink', 'no_sound', 'spots', 'restart' );
	$rules = pixva_diagnosis_problems();
	$keys  = array();
	foreach ( $order as $k ) {
		if ( isset( $rules[ $k ] ) ) {
			$keys[] = $k;
		}
	}
	foreach ( array_keys( $rules ) as $k ) {
		if ( ! in_array( $k, $keys, true ) ) {
			$keys[] = $k;
		}
	}
	$keys = array_slice( $keys, 0, max( 1, (int) $limit ) );
	echo '<ul class="problem-grid">';
	$i = 0;
	foreach ( $keys as $k ) {
		$p = $rules[ $k ];
		++$i;
		echo '<li class="reveal" style="--d:' . (int) $i . '">';
		echo '<a class="problem-tile__link" href="' . esc_url( pixva_route_url( 'diagnosis', array( 'problem' => $k, 'step' => '1' ) ) ) . '">';
		echo '<span class="problem-tile__icon" aria-hidden="true">' . wp_kses( pixva_icon( pixva_problem_icon( $k ) ), pixva_svg_allowed() ) . '</span>';
		echo '<span class="problem-tile__title">' . esc_html( $p['label'] ) . '</span>';
		echo '<span class="problem-tile__text">' . esc_html( $p['desc'] ) . '</span>';
		echo '<span class="problem-tile__go" aria-hidden="true">' . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
		echo '</a></li>';
	}
	echo '<li class="problem-tile problem-tile--more reveal" style="--d:' . (int) ( $i + 1 ) . '">';
	echo '<a class="problem-tile__link" href="' . esc_url( pixva_route_url( 'problems' ) ) . '">';
	echo '<span class="problem-tile__icon" aria-hidden="true">' . wp_kses( pixva_icon( 'plus' ), pixva_svg_allowed() ) . '</span>';
	echo '<span class="problem-tile__title">' . esc_html__( 'مشکل دیگری دارم', 'pixva' ) . '</span>';
	echo '<span class="problem-tile__text">' . esc_html__( 'فهرست کامل مشکلات رایج را در صفحه مشکلات ببینید.', 'pixva' ) . '</span>';
	echo '<span class="problem-tile__go" aria-hidden="true">' . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
	echo '</a></li>';
	echo '</ul>';
}

/**
 * Process journey: numbered steps with icons on a connecting line
 * (horizontal on desktop, vertical on mobile — CSS handles the switch).
 *
 * @param array $steps [{icon, title, text}].
 * @return void
 */
function pixva_journey( $steps ) {
	if ( ! $steps ) {
		return;
	}
	echo '<ol class="journey">';
	$i = 0;
	foreach ( $steps as $s ) {
		++$i;
		echo '<li class="journey__step reveal" style="--d:' . (int) $i . '">';
		echo '<span class="journey__marker" aria-hidden="true">' . wp_kses( pixva_icon( $s['icon'] ?? 'check' ), pixva_svg_allowed() ) . '</span>';
		echo '<span class="journey__num">' . esc_html( pixva_fa_num( sprintf( '%02d', $i ) ) ) . '</span>';
		echo '<h3 class="journey__title">' . esc_html( $s['title'] ) . '</h3>';
		echo '<p class="journey__text">' . esc_html( $s['text'] ) . '</p>';
		echo '</li>';
	}
	echo '</ol>';
}

/**
 * Dark full-bleed journey section (front page + repair hub).
 *
 * @param string $id    Section id.
 * @param string $title Title.
 * @param array  $steps Journey steps (see pixva_journey()).
 * @param string $lead  Lead text.
 * @return void
 */
function pixva_journey_section( $id, $title, $steps, $lead = '' ) {
	pixva_section_open( $id, $title, '', $lead, __( 'روند کار', 'pixva' ), 'band band--dark band--grid' );
	pixva_journey( $steps );
	pixva_section_close();
}

/**
 * Short plain excerpt for a post (feature/rows compositions).
 *
 * @param WP_Post|int $post  Post.
 * @param int         $words Word limit.
 * @return string
 */
function pixva_plain_excerpt( $post, $words = 26 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	return has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $words );
}

/**
 * Featured service panel (large horizontal composition with media or a
 * branded icon panel when no thumbnail exists).
 *
 * @param WP_Post|int $post Post.
 * @return void
 */
function pixva_service_feature( $post, $number = 1 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	echo '<article class="feature reveal">';
	echo '<div class="feature__media">';
	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
	} else {
		echo '<span class="feature__media-icon" aria-hidden="true">' . wp_kses( pixva_icon( 'tool' ), pixva_svg_allowed() ) . '</span>';
	}
	echo '</div>';
	echo '<div class="feature__body">';
	echo '<p class="feature__kicker">' . esc_html__( 'خدمت تعمیر', 'pixva' ) . ' · ' . esc_html( pixva_fa_num( sprintf( '%02d', max( 1, (int) $number ) ) ) ) . '</p>';
	echo '<h2 class="feature__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h2>';
	$excerpt = pixva_plain_excerpt( $post, 30 );
	if ( '' !== trim( $excerpt ) ) {
		echo '<p class="feature__text">' . esc_html( $excerpt ) . '</p>';
	}
	echo '<span class="feature__more" aria-hidden="true">' . esc_html__( 'مشاهده خدمت', 'pixva' ) . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
	echo '</div></article>';
}

/**
 * Compact numbered service rows (editorial list, not cards).
 *
 * @param array $posts Service posts.
 * @param int   $start Number of the first row (the feature card is 01).
 * @return void
 */
function pixva_service_rows( $posts, $start = 1 ) {
	$posts = array_filter( array_map( 'get_post', (array) $posts ) );
	if ( ! $posts ) {
		return;
	}
	echo '<ul class="rows">';
	$i = max( 1, (int) $start ) - 1;
	foreach ( $posts as $p ) {
		++$i;
		echo '<li><a class="rows__item reveal" style="--d:' . (int) min( $i, 6 ) . '" href="' . esc_url( get_permalink( $p ) ) . '">';
		echo '<span class="rows__head">';
		echo '<span class="rows__num" aria-hidden="true">' . esc_html( pixva_fa_num( sprintf( '%02d', $i ) ) ) . '</span>';
		echo '<span class="rows__body"><span class="rows__title">' . esc_html( get_the_title( $p ) ) . '</span>';
		$excerpt = pixva_plain_excerpt( $p, 16 );
		if ( '' !== trim( $excerpt ) ) {
			echo '<span class="rows__meta">' . esc_html( $excerpt ) . '</span>';
		}
		echo '</span></span>';
		echo '<span class="rows__go" aria-hidden="true">' . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
		echo '</a></li>';
	}
	echo '</ul>';
}

/**
 * Premium brand wall: typographic tiles from real brand data (no invented
 * logos; the Latin name renders only when stored).
 *
 * @param array $brands Brand posts.
 * @return void
 */
function pixva_brand_wall( $brands ) {
	$brands = array_filter( array_map( 'get_post', (array) $brands ) );
	if ( ! $brands ) {
		return;
	}
	echo '<ul class="brand-wall">';
	$i = 0;
	foreach ( $brands as $b ) {
		++$i;
		$en = (string) get_post_meta( $b->ID, '_pixva_brand_en', true );
		$logo = absint( get_post_meta( $b->ID, '_pixva_brand_logo', true ) );
		echo '<li class="brand-wall__tile reveal" style="--d:' . (int) min( $i, 8 ) . '"><a href="' . esc_url( get_permalink( $b ) ) . '">';
		if ( $logo ) {
			echo wp_get_attachment_image( $logo, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'class' => 'brand-wall__logo' ) );
		}
		echo '<span class="brand-wall__name">' . esc_html( trim( (string) preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title( $b ) ) ) ) . '</span>';
		if ( '' !== $en ) {
			echo '<span class="brand-wall__en" lang="en" dir="ltr">' . esc_html( $en ) . '</span>';
		}
		echo '</a></li>';
	}
	echo '</ul>';
}

/**
 * Editorial feature card (large media + body) for portfolio cases and
 * featured articles.
 *
 * @param WP_Post|int $post   Post.
 * @param string      $kicker Eyebrow text.
 * @param string      $meta   Meta line (plain).
 * @param string[]    $chips  Overlay chips (plain).
 * @return void
 */
function pixva_feature_card( $post, $kicker, $meta = '', $chips = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	echo '<article class="feature reveal">';
	echo '<div class="feature__media">';
	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
	} else {
		echo '<span class="feature__media-icon" aria-hidden="true">' . wp_kses( pixva_icon( 'picture' ), pixva_svg_allowed() ) . '</span>';
	}
	if ( $chips ) {
		echo '<span class="feature__chips">';
		foreach ( $chips as $c ) {
			echo '<span class="feature__chip">' . esc_html( $c ) . '</span>';
		}
		echo '</span>';
	}
	echo '</div>';
	echo '<div class="feature__body">';
	echo '<p class="feature__kicker">' . esc_html( $kicker ) . '</p>';
	echo '<h3 class="feature__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
	$excerpt = pixva_plain_excerpt( $post, 26 );
	if ( '' !== trim( $excerpt ) ) {
		echo '<p class="feature__text">' . esc_html( $excerpt ) . '</p>';
	}
	if ( '' !== $meta ) {
		echo '<p class="post-meta">' . esc_html( $meta ) . '</p>';
	}
	echo '<span class="feature__more" aria-hidden="true">' . esc_html__( 'مشاهده', 'pixva' ) . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
	echo '</div></article>';
}

/**
 * Editorial rows (thumb or number + title + meta) for portfolio cases and
 * article lists.
 *
 * @param array    $posts   Posts.
 * @param callable $meta_cb Optional callback (WP_Post): string meta line.
 * @return void
 */
function pixva_rows( $posts, $meta_cb = null ) {
	$posts = array_filter( array_map( 'get_post', (array) $posts ) );
	if ( ! $posts ) {
		return;
	}
	echo '<ul class="rows">';
	$i = 0;
	foreach ( $posts as $p ) {
		++$i;
		$meta = is_callable( $meta_cb ) ? (string) call_user_func( $meta_cb, $p ) : '';
		echo '<li><a class="rows__item reveal" style="--d:' . (int) min( $i, 6 ) . '" href="' . esc_url( get_permalink( $p ) ) . '">';
		echo '<span class="rows__head">';
		if ( has_post_thumbnail( $p ) ) {
			echo '<span class="rows__thumb">' . get_the_post_thumbnail( $p, 'thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ) . '</span>';
		} else {
			echo '<span class="rows__num" aria-hidden="true">' . esc_html( pixva_fa_num( sprintf( '%02d', $i ) ) ) . '</span>';
		}
		echo '<span class="rows__body"><span class="rows__title">' . esc_html( get_the_title( $p ) ) . '</span>';
		if ( '' !== $meta ) {
			echo '<span class="rows__meta">' . esc_html( $meta ) . '</span>';
		}
		echo '</span></span>';
		echo '<span class="rows__go" aria-hidden="true">' . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</span>';
		echo '</a></li>';
	}
	echo '</ul>';
}

/**
 * Render a public order view (tracking / account). No PII is included in
 * the view array (pixva_order_public_view()).
 *
 * @param array $v View.
 * @return void
 */
function pixva_order_view( $v ) {
	echo '<article class="order" aria-labelledby="order-' . esc_attr( $v['code'] ) . '">';
	echo '<header class="order__head"><h2 class="order__title" id="order-' . esc_attr( $v['code'] ) . '"><span dir="ltr">' . esc_html( $v['code'] ) . '</span></h2>';
	echo '<span class="badge badge--status-' . esc_attr( $v['status'] ) . '">' . esc_html( $v['label'] ) . '</span></header>';
	if ( '' !== $v['device'] ) {
		echo '<p class="order__device" dir="auto">' . esc_html( $v['device'] ) . '</p>';
	}
	echo '<p>' . esc_html( $v['description'] ) . '</p>';
	if ( 'cancelled' !== $v['status'] ) {
		echo '<ol class="timeline" aria-label="' . esc_attr__( 'مراحل', 'pixva' ) . '">';
		foreach ( $v['milestones'] as $step => $label ) {
			$state = $step < $v['step'] ? 'done' : ( $step === $v['step'] ? 'current' : 'todo' );
			echo '<li class="timeline__item is-' . esc_attr( $state ) . '"' . ( 'current' === $state ? ' aria-current="step"' : '' ) . '><span>' . esc_html( $label ) . '</span></li>';
		}
		echo '</ol>';
	}
	if ( '' !== $v['estimate'] ) {
		echo '<p><strong>' . esc_html__( 'هزینه اعلام‌شده:', 'pixva' ) . '</strong> ' . esc_html( $v['estimate'] ) . '</p>';
	}
	if ( 'none' !== $v['warranty']['state'] ) {
		$txt = 'active' === $v['warranty']['state']
			/* translators: 1: date, 2: days. */
			? sprintf( __( 'گارانتی فعال تا %1$s (%2$s روز مانده)', 'pixva' ), $v['warranty']['until'], pixva_fa_num( (int) $v['warranty']['left'] ) )
			/* translators: %s: date. */
			: sprintf( __( 'گارانتی در %s به پایان رسیده است.', 'pixva' ), $v['warranty']['until'] );
		echo '<p class="order__warranty">' . wp_kses( pixva_icon( 'shield' ), pixva_svg_allowed() ) . ' ' . esc_html( $txt ) . '</p>';
	}
	if ( $v['history'] ) {
		echo '<details class="order__history"><summary>' . esc_html__( 'سابقه تغییرات', 'pixva' ) . '</summary><ul>';
		foreach ( $v['history'] as $h ) {
			echo '<li><time datetime="' . esc_attr( $h['iso'] ) . '">' . esc_html( $h['date'] ) . '</time> — ' . esc_html( $h['label'] ) . ( '' !== $h['note'] ? '<br><span class="field__help">' . esc_html( $h['note'] ) . '</span>' : '' ) . '</li>';
		}
		echo '</ul></details>';
	}
	/* translators: %s: date. */
	echo '<p class="field__help">' . esc_html( sprintf( __( 'تاریخ ثبت: %s', 'pixva' ), $v['created'] ) ) . '</p>';
	echo '</article>';
}

/**
 * Handle a same-page code+phone lookup (no-JS path for tracking/warranty).
 * Returns null when no lookup was posted, WP_Error on failure, or the view.
 *
 * @param string $nonce_action Nonce action.
 * @return array|WP_Error|null
 */
function pixva_handle_page_lookup( $nonce_action ) {
	if ( 'POST' !== strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) || ! isset( $_POST['pixva_lookup'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified below.
		return null;
	}
	if ( ! wp_verify_nonce( pixva_get_post_var( '_pixva_nonce' ), $nonce_action ) ) {
		return new WP_Error( 'nonce', __( 'نشست فرم منقضی شده است. صفحه را تازه کنید و دوباره تلاش کنید.', 'pixva' ) );
	}
	$id = pixva_verify_order_access( pixva_get_post_var( 'code' ), pixva_get_post_var( 'phone' ) );
	return is_wp_error( $id ) ? $id : pixva_order_public_view( $id );
}

/**
 * Code + phone lookup form.
 *
 * @param string $nonce_action Nonce action.
 * @param string $endpoint     REST endpoint for JS (track|warranty).
 * @param string $button       Button label.
 * @param string $event        Analytics event.
 * @return void
 */
function pixva_lookup_form( $nonce_action, $endpoint, $button, $event ) {
	echo '<form class="panel lookup" method="post" action="' . esc_url( pixva_current_url() ) . '" data-lookup="' . esc_attr( $endpoint ) . '" data-track-event="' . esc_attr( $event ) . '" novalidate>';
	wp_nonce_field( $nonce_action, '_pixva_nonce', false );
	echo '<input type="hidden" name="pixva_lookup" value="1">';
	echo '<div class="form-grid">';
	pixva_field(
		array(
			'name'        => 'code',
			'label'       => __( 'کد پیگیری', 'pixva' ),
			'required'    => true,
			'placeholder' => 'PXV-XXXX-XXXX',
			'value'       => pixva_get_post_var( 'code' ),
			'attrs'       => array(
				'dir'            => 'ltr',
				'autocomplete'   => 'off',
				'maxlength'      => '20',
				'autocapitalize' => 'characters',
			),
		)
	);
	pixva_field(
		array(
			'name'     => 'phone',
			'label'    => __( 'شماره همراه ثبت‌شده', 'pixva' ),
			'type'     => 'tel',
			'required' => true,
			'attrs'    => array(
				'dir'          => 'ltr',
				'autocomplete' => 'tel',
				'inputmode'    => 'tel',
				'maxlength'    => '14',
			),
		)
	);
	echo '</div><div class="form__actions"><button class="btn btn--primary" type="submit" data-submit>' . esc_html( $button ) . '</button></div>';
	echo '<p class="field__help">' . esc_html__( 'برای حفظ حریم خصوصی، اطلاعات فقط با کد و شماره همراه ثبت‌شده نمایش داده می‌شود.', 'pixva' ) . '</p>';
	echo '</form>';
}

/**
 * Capture the output of a render function (used by REST to return the same
 * server-rendered markup the no-JS templates print — one markup source).
 *
 * @param callable $cb      Renderer.
 * @param mixed    ...$args Arguments.
 * @return string
 */
function pixva_capture( $cb, ...$args ) {
	ob_start();
	call_user_func_array( $cb, $args );
	return (string) ob_get_clean();
}

/**
 * Warranty lookup result (PII-free public view).
 *
 * @param array $v pixva_order_public_view() result.
 * @return void
 */
function pixva_warranty_view( $v ) {
	echo '<div class="panel lookup__panel"><p><strong dir="ltr">' . esc_html( $v['code'] ) . '</strong>';
	if ( '' !== $v['device'] ) {
		echo ' — <span dir="auto">' . esc_html( $v['device'] ) . '</span>';
	}
	echo '</p>';
	$w = $v['warranty'];
	if ( 'active' === $w['state'] ) {
		/* translators: 1: date, 2: days. */
		pixva_notice( 'success', sprintf( __( 'گارانتی این تعمیر تا %1$s فعال است (%2$s روز مانده).', 'pixva' ), $w['until'], pixva_fa_num( (int) $w['left'] ) ) );
	} elseif ( 'expired' === $w['state'] ) {
		/* translators: %s: date. */
		pixva_notice( 'warning', sprintf( __( 'گارانتی این تعمیر در %s به پایان رسیده است.', 'pixva' ), $w['until'] ) );
	} else {
		pixva_notice( 'info', __( 'برای این پرونده گارانتی ثبت نشده است (ممکن است تعمیر هنوز تحویل نشده باشد).', 'pixva' ) );
	}
	echo '</div>';
}

/**
 * Price calculator result region content.
 *
 * @param array|null $est pixva_estimate() result or null (nothing chosen).
 * @return void
 */
function pixva_calc_result( $est ) {
	if ( ! $est ) {
		return;
	}
	if ( ! empty( $est['available'] ) ) {
		echo '<div data-track-view="price_calculator_completed"><p class="calc__label">' . esc_html( $est['label'] ) . '</p><p class="price">' . esc_html( pixva_format_range( $est['min'], $est['max'], $est['currency'] ) ) . '</p></div>';
		return;
	}
	pixva_notice( 'info', __( 'برای این انتخاب برآورد آنلاین در دسترس نیست؛ هزینه پس از کارشناسی اعلام می‌شود.', 'pixva' ) );
}

/**
 * Front page lead: the home page excerpt when set, otherwise a factual
 * description of what the site does (shared by the hero and the meta
 * description).
 *
 * @return string
 */
function pixva_front_lead() {
	$front = (int) get_option( 'page_on_front' );
	if ( $front && has_excerpt( $front ) ) {
		return get_the_excerpt( $front );
	}
	return __( 'ایراد تلویزیون را آنلاین بررسی کنید، علت‌های محتمل و بررسی‌های ایمن را ببینید، و در صورت نیاز درخواست تعمیر ثبت و وضعیت آن را پیگیری کنید.', 'pixva' );
}

/**
 * Latest published posts of a post type, for template loops.
 *
 * Returns real CMS content only; an empty array means the template must
 * render its own empty state (no placeholder content is ever generated).
 *
 * @param string $post_type Post type slug (e.g. tv_services, tv_brands, post).
 * @param int    $limit     Maximum number of posts.
 * @return WP_Post[]
 */
function pixva_posts( $post_type, $limit = 6 ) {
	if ( ! post_type_exists( $post_type ) ) {
		return array();
	}
	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, (int) $limit ),
			'orderby'                => 'menu_order date',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	return $query->posts;
}

/**
 * Editorial blog layout: lead story + dated list. Expects an array of WP_Post (may be empty).
 * Uses only real post fields (title, date, excerpt, thumbnail); no placeholder content.
 */
function pixva_editorial_list( $posts ) {
	$posts = array_values( array_filter( (array) $posts ) );
	if ( ! $posts ) {
		return;
	}
	$lead = array_shift( $posts );
	?>
	<?php if ( $lead ) : ?>
	<article class="ed-lead reveal">
		<a class="ed-lead__link" href="<?php echo esc_url( get_permalink( $lead ) ); ?>">
			<?php if ( has_post_thumbnail( $lead ) ) : ?>
			<span class="ed-lead__media"><?php echo get_the_post_thumbnail( $lead, 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?></span>
			<?php endif; ?>
			<span class="ed-lead__body">
		<span class="ed-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c', $lead ) ); ?>"><?php echo esc_html( get_the_date( '', $lead ) ); ?></time></span>
		<span class="ed-lead__title"><?php echo esc_html( get_the_title( $lead ) ); ?></span>
		<?php $pixva_ex = pixva_plain_excerpt( $lead, 28 ); if ( '' !== trim( $pixva_ex ) ) : ?>
		<span class="ed-lead__text"><?php echo esc_html( $pixva_ex ); ?></span>
		<?php endif; ?>
		<span class="ed-more" aria-hidden="true"><?php esc_html_e( 'ادامه مطلب', 'pixva' ); ?></span>
			</span>
		</a>
	</article>
	<?php endif; ?>
	<?php if ( $posts ) : ?>
	<ol class="ed-list" aria-label="<?php esc_attr_e( 'مطالب بیشتر', 'pixva' ); ?>">
		<?php foreach ( $posts as $pixva_n => $pixva_p ) : ?>
		<li class="ed-item reveal" style="--d:<?php echo (int) min( $pixva_n + 1, 6 ); ?>">
			<a class="ed-item__link" href="<?php echo esc_url( get_permalink( $pixva_p ) ); ?>">
		<span class="ed-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c', $pixva_p ) ); ?>"><?php echo esc_html( get_the_date( '', $pixva_p ) ); ?></time></span>
		<span class="ed-item__title"><?php echo esc_html( get_the_title( $pixva_p ) ); ?></span>
		<?php $pixva_ex = pixva_plain_excerpt( $pixva_p, 18 ); if ( '' !== trim( $pixva_ex ) ) : ?>
		<span class="ed-item__text"><?php echo esc_html( $pixva_ex ); ?></span>
		<?php endif; ?>
			</a>
		</li>
		<?php endforeach; ?>
	</ol>
	<?php endif; ?>
	<?php
}
