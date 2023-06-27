<?php
/**
 * Utilities.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Utilities.
 */
class Util {
	/**
	 * Checks whether the Connections feature is enabled for a group.
	 *
	 * Defaults to true, except for Portfolios.
	 *
	 * @param int $group_id Group ID. Defaults to current group.
	 * @return bool
	 */
	public static function is_connections_enabled_for_group( $group_id = null ) {
		if ( null === $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		// Default to false in case no value is found.
		if ( ! $group_id ) {
			return false;
		}

		$is_disabled = groups_get_groupmeta( $group_id, 'openlab_connections_disabled' );

		// Empty value should default to disabled for portfolios.
		if ( '' === $is_disabled && openlab_is_portfolio( $group_id ) ) {
			$is_disabled = true;
		}

		return empty( $is_disabled );
	}

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

		return user_can( $user_id, 'bp_moderate' ) || groups_is_user_admin( $user_id, $group_id );
	}

	/**
	 * Sends a connection invitation.
	 *
	 * @param array $args {
	 *   Array of arguments.
	 *   @var int $inviter_group_id ID of the group initiating the invitation.
	 *   @var int $invitee_group_id ID of the group receiving the invitation.
	 *   @var int $inviter_user_id  ID of the user initiating the invitation.
	 * }
	 * @return {
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
}
