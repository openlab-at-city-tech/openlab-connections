<?php
/**
 * Schema definitions.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Connection Invitation object.
 */
class Schema {
	/**
	 * Private constructor.
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Gets the singleton instance.
	 *
	 * @return \OpenLab\Connections\Schema
	 */
	public static function get_instance() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Creates database tables.
	 *
	 * @return string[]
	 */
	public static function create_tables() {
		global $wpdb;

		$sql = array();

		$charset_collate = $wpdb->get_charset_collate();

		$table_prefix = $wpdb->get_blog_prefix( get_main_site_id() );

		$invitation_table_name = "{$table_prefix}openlab_connection_invitations";
		$connection_table_name = "{$table_prefix}openlab_connections";
		$metadata_table_name   = "{$table_prefix}openlab_connection_metadata";

		$sql[] = "CREATE TABLE {$invitation_table_name} (
					invitation_id bigint( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inviter_user_id bigint( 20 ) NOT NULL,
					inviter_group_id bigint( 20 ) NOT NULL,
					invitee_group_id bigint( 20 ) NOT NULL,
					connection_id bigint( 20 ),
					date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					date_accepted datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					date_rejected datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					KEY inviter_user_id ( inviter_user_id ),
					KEY inviter_group_id ( inviter_group_id ),
					KEY invitee_group_id ( invitee_group_id )
				) {$charset_collate};";

		$sql[] = "CREATE TABLE {$connection_table_name} (
					connection_id bigint( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					group_1_id bigint( 20 ) NOT NULL,
					group_2_id bigint( 20 ) NOT NULL,
					date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					KEY connection_id ( connection_id ),
					KEY group_1_id ( group_1_id ),
					KEY group_2_id ( group_2_id )
				) {$charset_collate};";

		$sql[] = "CREATE TABLE {$metadata_table_name} (
					meta_id bigint( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					connection_id bigint( 20 ) NOT NULL,
					group_id bigint( 20 ) NOT NULL,
					meta_key varchar( 255 ) NOT NULL,
					meta_value longtext,
					UNIQUE KEY idx_group_connection ( group_id,connection_id ),
					KEY meta_key ( meta_key )
				) {$charset_collate};";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}

		return dbDelta( $sql );
	}

	/**
	 * Creates BP emails for the Connection feature.
	 *
	 * @return void
	 */
	public static function create_emails() {
		$emails = [
			[
				'type'      => 'openlab-connection-invitation',
				'subject'   => 'Your group {{{ol.invitee-group-name}}} has received a connection invitation from {{{ol.inviter-group-name}}}',
				'content'   => 'Your group <a href="{{{ol.invitee-group-url}}}">{{{ol.invitee-group-name}}}</a> has received a connection invitation from <a href="{{{ol.inviter-group-url}}}">{{{ol.inviter-group-name}}}</a>.

<a href="{{{ol.manage-invites-url}}}">Accept or manage your connection invitations</a>',
				'plaintext' => 'Your group {{{ol.invitee-group-name}}} has received a connection invitation from {{{ol.inviter-group-name}}} ( {{{ol.inviter-group-url}}} ).

Accept or manage your connection invitations: {{{ol.manage-invites-url}}}',
				'desc'      => 'A group is invited to a connection.',
			],
			[
				'type'      => 'openlab-connection-invitation-accepted',
				'subject'   => 'Your connection invitation for {{{ol.invitee-group-name}}} has been accepted',
				'content'   => 'Your connection invitation for <a href="{{{ol.invitee-group-url}}}">{{{ol.invitee-group-name}}}</a> has been accepted.

<a href="{{{ol.manage-url}}}">Manage your connections</a>',
				'plaintext' => 'Your connection invitation for {{{ol.invitee-group-name}}} has been accepted.

Manage your connections: {{{ol.manage-url}}}',
				'desc'      => 'A connection invitation is accepted.',
			],
		];

		foreach ( $emails as $email ) {
			$term = get_term_by( 'name', $email['type'] );
			if ( $term ) {
				continue;
			}

			$post_args = [
				'post_status'  => 'publish',
				'post_type'    => bp_get_email_post_type(),
				'post_title'   => $email['subject'],
				'post_content' => $email['content'],
				'post_excerpt' => $email['plaintext'],
			];

			$post_id = wp_insert_post( $post_args );

			if ( $post_id ) {
				$tt_ids = wp_set_object_terms( $post_id, $email['type'], bp_get_email_tax_type() );
				if ( ! is_wp_error( $tt_ids ) ) {
					$term = get_term_by( 'term_taxonomy_id', (int) $tt_ids[0], bp_get_email_tax_type() );
					if ( $term instanceof \WP_Term ) {
						wp_update_term(
							(int) $term->term_id,
							bp_get_email_tax_type(),
							[
								'description' => $email['desc'],
							]
						);
					}
				}
			}
		}
	}
}
