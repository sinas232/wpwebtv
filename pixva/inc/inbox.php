<?php
/**
 * Contact inbox: one message per submission id, and at-least-once staff email.
 *
 * Why a reservation row: the claim (pixva_claim_submission) already stops two
 * requests running the handler at once, but a claim expires after
 * PIXVA_CLAIM_PENDING_TTL. A crash after the post was inserted and before the
 * claim finished would otherwise let a resubmission create a second message.
 * The reservation row is the atomic primitive (UNIQUE create, compare-and-replace),
 * the same pattern as pixva_place_order_once().
 *
 * Reservation row pixva_msg_<sid> in wp_options:
 *   creating: {s, k (token), t}      an attempt is inserting the message
 *   linked:   {s, o (post id), t}    the message is final
 *   mailed:   {s, o, t, m: 1}        the staff email was sent (see below)
 *
 * Message identity: the inbox post's post_name is the submission id, so an
 * insert that was not yet linked can be found again.
 *
 * Email: the email is sent BEFORE the mailed marker is written. Therefore:
 *   - a crash after the mail call and before the marker sends the email again
 *     on the next attempt (possible DUPLICATE email);
 *   - a crash before the mail call sends it on the next attempt (no loss).
 * The email is NOT exactly-once. It is at-least-once within those crash windows.
 *
 * @package pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Find inbox posts carrying a submission id as their post_name.
 *
 * @param string $sid Submission id.
 * @return int[]
 */
function pixva_inbox_by_submission( $sid ) {
	global $wpdb;
	$sid = pixva_normalize_submission_id( $sid );
	if ( '' === $sid ) {
		return array();
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- LIKE prefix lookup by id; not cacheable across requests.
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name LIKE %s ORDER BY ID ASC LIMIT 20", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pixva_inbox',
			$wpdb->esc_like( $sid ) . '%'
		) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	);
	return array_map( 'intval', (array) $ids );
}

/**
 * Whether an inbox post exists.
 *
 * @param int $id Post id.
 * @return bool
 */
function pixva_inbox_exists( $id ) {
	$post = get_post( (int) $id );
	return $post && 'pixva_inbox' === $post->post_type;
}

/**
 * Write the message fields. Idempotent: an adopted message is rewritten with
 * the same values.
 *
 * @param int   $id     Post id.
 * @param array $fields name, phone, email, body (already sanitised).
 * @return void
 */
function pixva_inbox_write_fields( $id, array $fields ) {
	update_post_meta( $id, '_pixva_msg_name', wp_slash( (string) $fields['name'] ) );
	update_post_meta( $id, '_pixva_msg_phone', wp_slash( (string) $fields['phone'] ) );
	update_post_meta( $id, '_pixva_msg_email', wp_slash( (string) $fields['email'] ) );
	update_post_meta( $id, '_pixva_msg_body', wp_slash( (string) $fields['body'] ) );
}

/**
 * Place the inbox message for a submission id exactly once. Returns the post id.
 *
 * Must be called while holding the submission claim (the handler does this via
 * pixva_dispatch_form). Steps:
 *  1. Linked to an existing message: return it (no insert, no second email).
 *  2. Otherwise take the reservation (UNIQUE create, or compare-and-replace of a
 *     stale creating/linked-to-missing row).
 *  3. Adopt a message a previous attempt inserted (post_name = sid), else insert.
 *  4. Link: compare-and-replace our creating row with linked. A fenced-out
 *     attempt fails here and retries the read.
 *
 * @param string $sid    Submission id.
 * @param array  $fields name, phone, email, body.
 * @return int|WP_Error Post id.
 */
function pixva_place_inbox_once( $sid, array $fields ) {
	$sid = pixva_normalize_submission_id( $sid );
	if ( '' === $sid ) {
		return new WP_Error( 'sid', __( 'شناسه ارسال نامعتبر است.', 'pixva' ), array( 'status' => 400 ) );
	}
	$name  = 'pixva_msg_' . $sid;
	$token = wp_generate_password( 24, false );
	$mine  = (string) wp_json_encode(
		array(
			's' => 'creating',
			'k' => $token,
			't' => pixva_now(),
		)
	);

	for ( $try = 0; $try < 5; $try++ ) {
		$held = pixva_option_value( $name );
		if ( null === $held ) {
			if ( ! pixva_create_once( $name, $mine ) ) {
				continue; // Created by someone else between the two reads: re-read.
			}
		} else {
			$row = json_decode( $held, true );
			if ( is_array( $row ) && 'linked' === ( $row['s'] ?? '' ) && pixva_inbox_exists( (int) ( $row['o'] ?? 0 ) ) ) {
				return (int) $row['o'];
			}
			// A live "creating" attempt owns this id. Do not take it over: that could insert a
			// second message while the first attempt is still between insert and link.
			if ( is_array( $row ) && 'creating' === ( $row['s'] ?? '' ) && (int) ( $row['t'] ?? 0 ) + PIXVA_CLAIM_PENDING_TTL > pixva_now() ) {
				return new WP_Error( 'busy', __( 'ثبت پیام ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 409 ) );
			}
			// Expired "creating" row, or "linked" to a message that no longer exists: take over.
			if ( ! pixva_replace_option_row( $name, $held, $mine ) ) {
				continue;
			}
		}

		$found = pixva_inbox_by_submission( $sid );
		if ( $found ) {
			$id = $found[0];
		} else {
			do_action( 'pixva_inbox_before_insert', $sid );
			// Fence: if our reservation was taken over while we were stalled, insert nothing.
			// Residual: this check and the insert are not atomic (see docs/pixva-write-path-review.md).
			if ( pixva_option_value( $name ) !== $mine ) {
				continue;
			}
			$title = sprintf(
				/* translators: %s: date. */
				__( 'پیام %s', 'pixva' ),
				wp_date( 'Y-m-d H:i' )
			);
			$id = wp_insert_post(
				array(
					'post_type'   => 'pixva_inbox',
					'post_status' => 'private',
					'post_title'  => $title,
					'post_name'   => $sid,
					'post_author' => 0,
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				return new WP_Error( 'save', __( 'ارسال پیام ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
			}
			$id = (int) $id;
		}
		pixva_inbox_write_fields( $id, $fields );

		do_action( 'pixva_inbox_before_link', $sid, $id );
		$linked = (string) wp_json_encode(
			array(
				's' => 'linked',
				'o' => $id,
				't' => pixva_now(),
			)
		);
		if ( pixva_replace_option_row( $name, $mine, $linked ) ) {
			return $id;
		}
		// Taken over while we worked (fenced out). Re-read on the next pass.
	}
	return new WP_Error( 'busy', __( 'ثبت پیام ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 409 ) );
}

/**
 * Send the staff email for a linked message, at most once per successful send.
 *
 * Does nothing unless the reservation is still linked to $id. Sends with
 * $send($id) and then records the mailed marker with a compare-and-replace on
 * the value that was read. See the header for the crash windows.
 *
 * @param string   $sid  Submission id.
 * @param int      $id   Message post id.
 * @param callable $send Receives the post id; sends the email; returns bool.
 * @return bool True when the message is mailed (now or earlier).
 */
function pixva_inbox_mail_once( $sid, $id, $send ) {
	$sid  = pixva_normalize_submission_id( $sid );
	$name = 'pixva_msg_' . $sid;
	$held = pixva_option_value( $name );
	$row  = null === $held ? null : json_decode( $held, true );
	if ( ! is_array( $row ) || 'linked' !== ( $row['s'] ?? '' ) || (int) ( $row['o'] ?? 0 ) !== (int) $id ) {
		return false; // Not (or no longer) the linked message: another attempt owns the mail.
	}
	if ( ! empty( $row['m'] ) ) {
		return true; // Already mailed.
	}
	do_action( 'pixva_inbox_before_mail', $sid, (int) $id );
	call_user_func( $send, (int) $id );
	do_action( 'pixva_inbox_before_mark', $sid, (int) $id );
	$mailed = (string) wp_json_encode(
		array(
			's' => 'linked',
			'o' => (int) $id,
			't' => (int) ( $row['t'] ?? pixva_now() ),
			'm' => 1,
		)
	);
	pixva_replace_option_row( $name, $held, $mailed );
	return true;
}
