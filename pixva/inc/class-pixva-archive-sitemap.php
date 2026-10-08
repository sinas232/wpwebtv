<?php
/**
 * Sitemap provider for post type archive routes (/services/, /brands/,
 * /error-codes/, /portfolio/). Core sitemaps list singular items only; an
 * archive is listed here only when it has published items (an empty
 * archive is noindex, §48).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Archive routes provider.
 */
class Pixva_Archive_Sitemap extends WP_Sitemaps_Provider {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = 'pixvaarchives';
		$this->object_type = 'pixvaarchives';
	}

	/**
	 * URL list.
	 *
	 * @param int    $page_num       Page.
	 * @param string $object_subtype Unused.
	 * @return array
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		if ( 1 !== (int) $page_num ) {
			return array();
		}
		$urls = array();
		foreach ( pixva_routes() as $def ) {
			if ( 'archive' !== $def['source'] || empty( $def['index'] ) ) {
				continue;
			}
			$latest = get_posts(
				array(
					'post_type'        => $def['post_type'],
					'post_status'      => 'publish',
					'posts_per_page'   => 1,
					'orderby'          => 'modified',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);
			if ( ! $latest ) {
				continue;
			}
			$urls[] = array(
				'loc'     => get_post_type_archive_link( $def['post_type'] ),
				'lastmod' => get_post_modified_time( 'Y-m-d\TH:i:sP', true, $latest[0] ),
			);
		}
		return $urls;
	}

	/**
	 * Max pages.
	 *
	 * @param string $object_subtype Unused.
	 * @return int
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		return $this->get_url_list( 1 ) ? 1 : 0;
	}
}
