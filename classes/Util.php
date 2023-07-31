<?php
/**
 * Utilities.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Utility methods.
 */
class Util {
	/**
	 * Checks whether a user can initiate connections for a group.
	 *
	 * @param int $user_id  ID of the user. Defaults to logged-in user.
	 * @param int $group_id ID of the group. Defaults to current group.
	 * @return bool
	 */
	public static function user_can_initiate_group_connections( $user_id = null, $group_id = null ) {
		if ( ! $user_id ) {
			$user_id = bp_loggedin_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		if ( ! $group_id ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		return user_can( $user_id, 'bp_moderate' ) || groups_is_user_admin( $user_id, $group_id );
	}

	/**
	 * Sends a connection invitation.
	 *
	 * @param array{'inviter_group_id': int, 'invitee_group_id': int, 'inviter_user_id': int} $args {
	 *   Array of arguments.
	 *   @var int $inviter_group_id ID of the group initiating the invitation.
	 *   @var int $invitee_group_id ID of the group receiving the invitation.
	 *   @var int $inviter_user_id  ID of the user initiating the invitation.
	 * }
	 * @return array{'success': bool, 'status': string} {
	 *   @var bool   $success Whether the invitation was sent.
	 *   @var string $status  Status code. 'success', 'invitation_exists', 'connection_exists', 'failure'.
	 * }
	 */
	public static function send_connection_invitation( $args ) {
		global $wpdb;

		$retval = [
			'success' => false,
			'status'  => 'failure',
		];

		// First check for an existing invitation.
		if ( Invitation::invitation_exists( $args['inviter_group_id'], $args['invitee_group_id'] ) ) {
			$retval['status'] = 'invitation_exists';
			return $retval;
		}

		$invitation = new Invitation();
		$invitation->set_inviter_group_id( $args['inviter_group_id'] );
		$invitation->set_invitee_group_id( $args['invitee_group_id'] );
		$invitation->set_inviter_user_id( $args['inviter_user_id'] );

		$saved = $invitation->save();

		if ( ! $saved ) {
			return $retval;
		}

		$retval['success'] = true;
		$retval['status']  = 'success';

		$invitation->send_notifications();

		return $retval;
	}

	/**
	 * Fetches a list of taxonomy terms from a site.
	 *
	 * @param string $site_url URL of the site.
	 * @param string $taxonomy Taxonomy name. 'post_tag' or 'taxonomy'.
	 * @return mixed[]
	 */
	public static function fetch_taxonomy_terms_for_site( $site_url, $taxonomy ) {
		$tax_slug = 'post_tag' === $taxonomy ? 'tags' : 'categories';

		$request_url = trailingslashit( $site_url ) . 'wp-json/wp/v2/' . $tax_slug;

		// Make the request using wp_remote_get() function.
		$response = wp_remote_get( $request_url );

		if ( is_wp_error( $response ) ) {
			// Request failed, return the error.
			return [];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return [];
		}

		$categories = json_decode( $response_body, true );

		if ( is_array( $categories ) ) {
			return $categories;
		}

		return [];
	}

	/**
	 * Determines whether a group's site is public.
	 *
	 * This is very OpenLab-specific. The group-site lookup is particular to the
	 * City Tech OpenLab, and the idea that -1 (registered members of the network)
	 * is "public" is quite specific to the needs of the City Tech OpenLab. This
	 * will need rethinking when rolling into CBOX-OL.
	 *
	 * @param int $group_id ID of the group.
	 * @return bool
	 */
	public static function group_has_public_site( $group_id ) {
		$group_site_id   = openlab_get_site_id_by_group_id( $group_id );
		$has_public_site = false;
		if ( $group_site_id ) {
			$blog_public = get_blog_option( $group_site_id, 'blog_public' );
			if ( is_numeric( $blog_public ) && (int) $blog_public > -2 ) {
				$has_public_site = true;
			}
		}

		return $has_public_site;
	}
}
