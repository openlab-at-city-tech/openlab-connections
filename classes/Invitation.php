<?php
/**
 * Invitation object.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Connection Invitation object.
 */
class Invitation {
	/**
	 * Invitation ID.
	 *
	 * @var int
	 */
	protected $invitation_id;

	/**
	 * Inviter group ID.
	 *
	 * @var int
	 */
	protected $inviter_group_id;

	/**
	 * Invitee group ID.
	 *
	 * @var int
	 */
	protected $invitee_group_id;

	/**
	 * Inviter user ID.
	 *
	 * @var int
	 */
	protected $inviter_user_id;

	/**
	 * Connection user ID.
	 *
	 * @var int
	 */
	protected $connection_id;

	/**
	 * Date created.
	 *
	 * @var string
	 */
	protected $date_created = '0000-00-00 00:00:00';

	/**
	 * Date accepted.
	 *
	 * @var string
	 */
	protected $date_accepted = '0000-00-00 00:00:00';

	/**
	 * Date rejected.
	 *
	 * @var string
	 */
	protected $date_rejected = '0000-00-00 00:00:00';

	/**
	 * Gets the invitation ID for this invitation.
	 *
	 * @return int
	 */
	public function get_invitation_id() {
		return (int) $this->invitation_id;
	}

	/**
	 * Gets the invitee group ID for this invitation.
	 *
	 * @return int
	 */
	public function get_invitee_group_id() {
		return (int) $this->invitee_group_id;
	}

	/**
	 * Gets the inviter group ID for this invitation.
	 *
	 * @return int
	 */
	public function get_inviter_group_id() {
		return (int) $this->inviter_group_id;
	}

	/**
	 * Gets the inviter user ID for this invitation.
	 *
	 * @return int
	 */
	public function get_inviter_user_id() {
		return (int) $this->inviter_user_id;
	}

	/**
	 * Gets the date created for this invitation.
	 *
	 * @return string
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Sets the invitation ID for this invitation.
	 *
	 * @param int $invitation_id Invitation ID.
	 * @return void
	 */
	public function set_invitation_id( $invitation_id ) {
		$this->invitation_id = (int) $invitation_id;
	}

	/**
	 * Sets the inviter group ID for this invitation.
	 *
	 * @param int $inviter_group_id Inviter group ID.
	 * @return void
	 */
	public function set_inviter_group_id( $inviter_group_id ) {
		$this->inviter_group_id = (int) $inviter_group_id;
	}

	/**
	 * Sets the invitee group ID for this invitation.
	 *
	 * @param int $invitee_group_id Invitee group ID.
	 * @return void
	 */
	public function set_invitee_group_id( $invitee_group_id ) {
		$this->invitee_group_id = (int) $invitee_group_id;
	}

	/**
	 * Sets the inviter user ID for this invitation.
	 *
	 * @param int $inviter_user_id Inviter user ID.
	 * @return void
	 */
	public function set_inviter_user_id( $inviter_user_id ) {
		$this->inviter_user_id = (int) $inviter_user_id;
	}

	/**
	 * Sets the connection ID for this invitation.
	 *
	 * @param int $connection_id Connection ID.
	 * @return void
	 */
	public function set_connection_id( $connection_id ) {
		$this->connection_id = (int) $connection_id;
	}

	/**
	 * Sets the date_created for this invitation.
	 *
	 * @param string $date_created Date created, in MySQL format.
	 * @return void
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * Sets the date_accepted for this invitation.
	 *
	 * @param string $date_accepted Date accepted, in MySQL format.
	 * @return void
	 */
	public function set_date_accepted( $date_accepted ) {
		$this->date_accepted = $date_accepted;
	}

	/**
	 * Saves the invitation.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$table_name = self::get_table_name();

		$retval = false;
		if ( $this->invitation_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$updated = $wpdb->update(
				$table_name,
				[
					'inviter_group_id' => $this->inviter_group_id,
					'invitee_group_id' => $this->invitee_group_id,
					'inviter_user_id'  => $this->inviter_user_id,
					'connection_id'    => $this->connection_id,
					'date_created'     => $this->date_created,
					'date_accepted'    => $this->date_accepted,
					'date_rejected'    => $this->date_rejected,
				],
				[
					'invitation_id' => $this->invitation_id,
				],
				[
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				],
				[
					'%d',
				]
			);

			$retval = (bool) $updated;
		} else {
			$this->set_date_created( current_time( 'mysql' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$inserted = $wpdb->insert(
				$table_name,
				[
					'inviter_group_id' => $this->inviter_group_id,
					'invitee_group_id' => $this->invitee_group_id,
					'inviter_user_id'  => $this->inviter_user_id,
					'connection_id'    => $this->connection_id,
					'date_created'     => $this->date_created,
					'date_accepted'    => $this->date_accepted,
					'date_rejected'    => $this->date_rejected,
				],
				[
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				]
			);

			if ( $inserted ) {
				$retval = true;
				$this->set_invitation_id( $wpdb->insert_id );
			}
		}

		wp_cache_delete( $this->invitation_id, 'openlab_connection_invitations' );

		return $retval;
	}

	/**
	 * Deletes the invitation.
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		$table_name = self::get_table_name();

		// Delete the invitation from the database.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$deleted = $wpdb->delete(
			$table_name,
			array(
				'invitation_id' => $this->invitation_id,
			),
			array(
				'%d',
			)
		);

		// Invalidate the cache for the deleted invitation.
		wp_cache_delete( $this->invitation_id, 'openlab_connection_invitations' );

		return (bool) $deleted;
	}

	/**
	 * Gets the URL for the Accept action.
	 *
	 * @return string
	 */
	public function get_accept_url() {
		$group = groups_get_group( $this->get_invitee_group_id() );
		$base  = bp_get_group_permalink( $group ) . 'connections/invitations';
		return add_query_arg( 'accept-invitation', $this->get_invitation_id(), $base );
	}

	/**
	 * Gets the URL for the Reject action.
	 *
	 * @return string
	 */
	public function get_reject_url() {
		$group = groups_get_group( $this->get_invitee_group_id() );
		$base  = bp_get_group_permalink( $group ) . 'connections/invitations';
		return add_query_arg( 'reject-invitation', $this->get_invitation_id(), $base );
	}

	/**
	 * Accepts an invitation.
	 *
	 * Marks the invitation as accepted, and handles the creation of a connection.
	 *
	 * @return bool
	 */
	public function accept() {
		$current_time = current_time( 'mysql' );

		$connection = new Connection();
		$connection->set_group_1_id( $this->get_invitee_group_id() );
		$connection->set_group_2_id( $this->get_inviter_group_id() );
		$connection->set_date_created( $current_time );

		$saved = $connection->save();

		if ( ! $saved ) {
			return false;
		}

		$this->date_accepted = $current_time;
		$this->connection_id = $connection->get_connection_id();

		return $this->save();
	}

	/**
	 * Rejects an invitation.
	 *
	 * @return bool
	 */
	public function reject() {
		$this->date_rejected = current_time( 'mysql' );
		return $this->save();
	}

	/**
	 * Gets the table name for the invitations table.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		global $wpdb;

		$table_prefix = $wpdb->get_blog_prefix( get_main_site_id() );

		return "{$table_prefix}openlab_connection_invitations";
	}

	/**
	 * Checks whether an invitation exists for a given group pair.
	 *
	 * @param int $inviter_group_id Inviter group ID.
	 * @param int $invitee_group_id Invitee group ID.
	 * @return bool
	 */
	public static function invitation_exists( $inviter_group_id, $invitee_group_id ) {
		$found = self::get(
			[
				'inviter_group_id' => $inviter_group_id,
				'invitee_group_id' => $invitee_group_id,
			]
		);

		return ! empty( $found );
	}

	/**
	 * Returns an instance based on invitation_id.
	 *
	 * @param int $invitation_id ID of the invitation.
	 * @return null|\OpenLab\Connections\Invitation
	 */
	public static function get_instance( $invitation_id ) {
		global $wpdb;

		$cached = wp_cache_get( $invitation_id, 'openlab_connection_invitations' );
		if ( is_array( $cached ) ) {
			$row = $cached;
		} else {
			// phpcs:ignore WordPress.DB
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE invitation_id = %d', self::get_table_name(), $invitation_id ) );

			wp_cache_set( $invitation_id, $row, 'openlab_connection_invitations' );
		}

		if ( ! $row ) {
			return null;
		}

		$invitation = new self();
		$invitation->set_invitation_id( (int) $row->invitation_id );
		$invitation->set_inviter_group_id( (int) $row->inviter_group_id );
		$invitation->set_invitee_group_id( (int) $row->invitee_group_id );
		$invitation->set_inviter_user_id( (int) $row->inviter_user_id );
		$invitation->set_date_created( $row->date_created );
		$invitation->set_date_accepted( $row->date_accepted );

		return $invitation;
	}

	/**
	 * Fetches invitations based on parameters.
	 *
	 * @param mixed[] $args {
	 *   Array of optional query arguments.
	 *   @var int  $inviter_group_id Inviter group ID.
	 *   @var int  $invitee_group_id Invitee group ID.
	 *   @var bool $pending_only     True to return only the pending records.
	 * }
	 * @return \OpenLab\Connections\Invitation[]
	 */
	public static function get( $args = [] ) {
		global $wpdb;

		$r = array_merge(
			[
				'invitation_id'    => null,
				'inviter_group_id' => null,
				'invitee_group_id' => null,
				'pending_only'     => false,
			],
			$args
		);

		$table_name = self::get_table_name();

		$sql = [
			// phpcs:ignore WordPress.DB
			'select' => $wpdb->prepare( 'SELECT invitation_id FROM %i', $table_name ),
			'where'  => [],
		];

		$int_fields = [ 'invitation_id', 'inviter_group_id', 'invitee_group_id' ];
		foreach ( $int_fields as $int_field ) {
			if ( null === $r[ $int_field ] ) {
				continue;
			}

			if ( is_array( $r[ $int_field ] ) ) {
				$ints_sql = implode( ',', array_map( 'intval', $r[ $int_field ] ) );

				// phpcs:ignore WordPress.DB
				$sql['where'][ $int_field ] = $wpdb->prepare( "%i IN ({$ints_sql})", $int_field );
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$sql['where'][ $int_field ] = $wpdb->prepare( '%i = %d', $int_field, $r[ $int_field ] );
			}
		}

		if ( $r['pending_only'] ) {
			$sql['where']['pending_only'] = 'date_accepted = "0000-00-00 00:00:00" AND date_rejected = "0000-00-00 00:00:00"';
		}

		$sql_statement = "{$sql['select']} WHERE " . implode( ' AND ', $sql['where'] );

		// phpcs:ignore WordPress.DB
		$invitation_ids = $wpdb->get_col( $sql_statement );

		$invitations = array_map(
			function( $invitation_id ) {
				return self::get_instance( $invitation_id );
			},
			$invitation_ids
		);

		return array_filter( $invitations );
	}

	/**
	 * Sends notifications to admins of the invited group.
	 *
	 * @return int[] IDs of the users who received the invitation notification.
	 */
	public function send_notifications() {
		$admins = groups_get_group_admins( $this->get_invitee_group_id() );

		$invitee_group = groups_get_group( $this->get_invitee_group_id() );
		$inviter_group = groups_get_group( $this->get_inviter_group_id() );

		$email_args = [
			'tokens' => array(
				'ol.invitee-group-name' => stripslashes( $invitee_group->name ),
				'ol.invitee-group-url'  => bp_get_group_permalink( $invitee_group ),
				'ol.inviter-group-name' => stripslashes( $inviter_group->name ),
				'ol.inviter-group-url'  => bp_get_group_permalink( $inviter_group ),
				'ol.manage-invites-url' => bp_get_group_permalink( $invitee_group ) . 'connections/invitations/',
			),
		];

		$retval = [];
		foreach ( $admins as $admin ) {
			$admin_data = get_userdata( $admin->user_id );
			if ( ! $admin_data ) {
				continue;
			}

			$sent = bp_send_email(
				'openlab-connection-invitation',
				$admin_data->user_email,
				$email_args
			);

			if ( $sent && ! is_wp_error( $sent ) ) {
				$retval[] = $admin->user_id;
			}
		}

		return $retval;
	}

	/**
	 * Sends notifications to inviter when an invitation is accepted.
	 *
	 * @return bool
	 */
	public function send_accepted_notification() {
		$inviter = get_userdata( $this->get_inviter_user_id() );

		if ( ! $inviter ) {
			return;
		}

		$invitee_group = groups_get_group( $this->get_invitee_group_id() );
		$inviter_group = groups_get_group( $this->get_inviter_group_id() );

		$email_args = [
			'tokens' => array(
				'ol.invitee-group-name' => stripslashes( $invitee_group->name ),
				'ol.invitee-group-url'  => bp_get_group_permalink( $invitee_group ),
				'ol.inviter-group-name' => stripslashes( $inviter_group->name ),
				'ol.inviter-group-url'  => bp_get_group_permalink( $inviter_group ),
				'ol.manage-url'         => bp_get_group_permalink( $invitee_group ) . 'connections/',
			),
		];

		$sent = bp_send_email(
			'openlab-connection-invitation-accepted',
			$inviter_data->user_email,
			$email_args
		);

		return $sent;
	}
}
