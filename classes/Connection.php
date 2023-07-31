<?php
/**
 * Connection object.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Connection object.
 *
 * @package openlab-connections
 */
class Connection {
	/**
	 * Connection ID.
	 *
	 * @var int
	 */
	protected $connection_id;

	/**
	 * Group 1 ID.
	 *
	 * @var int
	 */
	protected $group_1_id;

	/**
	 * Group 2 ID.
	 *
	 * @var int
	 */
	protected $group_2_id;

	/**
	 * Date created.
	 *
	 * @var string
	 */
	protected $date_created = '0000-00-00 00:00:00';

	/**
	 * Gets the connection ID for this connection.
	 *
	 * @return int
	 */
	public function get_connection_id() {
		return (int) $this->connection_id;
	}

	/**
	 * Gets the IDs of the groups in the connection.
	 *
	 * This is a convenience class, since usually we don't care which group is 1 and which is 2.
	 *
	 * @return int[]
	 */
	public function get_group_ids() {
		return [
			$this->group_1_id,
			$this->group_2_id,
		];
	}

	/**
	 * Sets the connection ID for this connection.
	 *
	 * @param int $connection_id Connection ID.
	 * @return void
	 */
	public function set_connection_id( $connection_id ) {
		$this->connection_id = (int) $connection_id;
	}

	/**
	 * Sets the ID of the first group in this connection.
	 *
	 * @param int $group_1_id Group 1 ID.
	 * @return void
	 */
	public function set_group_1_id( $group_1_id ) {
		$this->group_1_id = (int) $group_1_id;
	}

	/**
	 * Sets the ID of the second group in this connection.
	 *
	 * @param int $group_2_id Group 2 ID.
	 * @return void
	 */
	public function set_group_2_id( $group_2_id ) {
		$this->group_2_id = (int) $group_2_id;
	}

	/**
	 * Sets the date_created for this connection.
	 *
	 * @param string $date_created Date created, in MySQL format.
	 * @return void
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * Saves the connection.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$table_name = self::get_table_name();

		$retval = false;
		if ( $this->connection_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->update(
				$table_name,
				[
					'group_1_id'   => $this->group_1_id,
					'group_2_id'   => $this->group_2_id,
					'date_created' => $this->date_created,
				],
				[
					'connection_id' => $this->connection_id,
				],
				[
					'%d',
					'%d',
					'%s',
				],
				[
					'%d',
				]
			);

			$retval = (bool) $updated;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_name,
				[
					'group_1_id'   => $this->group_1_id,
					'group_2_id'   => $this->group_2_id,
					'date_created' => $this->date_created,
				],
				[
					'%d',
					'%d',
					'%s',
				]
			);

			if ( $inserted ) {
				$retval = true;
				$this->set_connection_id( $wpdb->insert_id );
			}
		}

		wp_cache_delete( $this->connection_id, 'openlab_connections' );

		return $retval;
	}

	/**
	 * Gets the table name for the connections table.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		global $wpdb;

		$table_prefix = $wpdb->get_blog_prefix( get_main_site_id() );

		return "{$table_prefix}openlab_connections";
	}

	/**
	 * Retrieves a connection instance based on the connection ID.
	 *
	 * @param int $connection_id Connection ID.
	 *
	 * @return null|\OpenLab\Connections\Connection
	 */
	public static function get_instance( $connection_id ) {
		global $wpdb;

		$cached = wp_cache_get( $connection_id, 'openlab_connections' );
		if ( false !== $cached && is_object( $cached ) ) {
			$row = $cached;
		} else {
			// phpcs:ignore WordPress.DB
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE connection_id = %d', self::get_table_name(), $connection_id ) );
		}

		if ( ! $row ) {
			return null;
		}

		$connection = new self();
		$connection->set_connection_id( (int) $row->connection_id );
		$connection->set_group_1_id( (int) $row->group_1_id );
		$connection->set_group_2_id( (int) $row->group_2_id );
		$connection->set_date_created( $row->date_created );

		return $connection;
	}

	/**
	 * Deletes the connection.
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! $this->connection_id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$table_name,
			[
				'connection_id' => $this->connection_id,
			],
			[
				'%d',
			]
		);

		wp_cache_delete( $this->connection_id, 'openlab_connections' );

		return (bool) $result;
	}

	/**
	 * Gets the connection settings for one of the groups in the connection.
	 *
	 * @param int $group_id ID of the group whose settings are being fetched.
	 * @return mixed[]
	 */
	public function get_group_settings( $group_id ) {
		$saved = groups_get_groupmeta( $group_id, 'connection_settings_' . $this->get_connection_id(), true );

		if ( ! $saved ) {
			$saved = [];
		}

		$settings = array_merge(
			[
				'categories'       => 'all',
				'exclude_comments' => false,
			],
			$saved
		);

		// Groups with non-public sites never share post content.
		$group_site_id     = openlab_get_site_id_by_group_id( $group_id );
		$group_site_status = 'public';
		if ( $group_site_id ) {
			$blog_public = get_blog_option( $group_site_id, 'blog_public' );
			if ( is_numeric( $blog_public ) && (int) $blog_public < -1 ) {
				$group_site_status = 'private';
			}
		}

		if ( 'public' !== $group_site_status ) {
			$settings['categories'] = [];
		}

		return $settings;
	}

	/**
	 * Gets the Disconnect URL for this connection, relative to a specific group.
	 *
	 * @param int $group_id ID of the group initiating the disconnect.
	 * @return string
	 */
	public function get_disconnect_url( $group_id ) {
		$group = groups_get_group( $group_id );
		$base  = bp_get_group_permalink( $group ) . 'connections/';
		return add_query_arg( 'disconnect', $this->get_connection_id(), $base );
	}

	/**
	 * Fetches connections based on parameters.
	 *
	 * @param mixed[] $args {
	 *   Array of optional query arguments.
	 *   @var int $group_id Limit to connections involving a specific group ID.
	 * }
	 * @return \OpenLab\Connections\Connection[]
	 */
	public static function get( $args = [] ) {
		global $wpdb;

		$r = array_merge(
			[
				'group_id' => null,
			],
			$args
		);

		$table_name = self::get_table_name();

		$sql = [
			// phpcs:ignore WordPress.DB
			'select' => $wpdb->prepare( 'SELECT connection_id FROM %i', $table_name ),
			'where'  => [],
		];

		if ( $r['group_id'] ) {
			$sql['where']['group_id'] = $wpdb->prepare( '(group_1_id = %d OR group_2_id = %d)', $r['group_id'], $r['group_id'] );
		}

		$sql_statement = "{$sql['select']} WHERE " . implode( ' AND ', $sql['where'] );

		// phpcs:ignore WordPress.DB
		$connection_ids = $wpdb->get_col( $sql_statement );

		$connections = array_map(
			function( $connection_id ) {
				return self::get_instance( $connection_id );
			},
			$connection_ids
		);

		$connections = array_filter( $connections );

		// Sort by group name.
		// Group names are pulled from the cache, though we may consider priming this in the future.
		if ( is_numeric( $r['group_id'] ) ) {
			$group_id = (int) $r['group_id'];

			usort(
				$connections,
				function( $connection_a, $connection_b ) use ( $group_id ) {
					$connection_a_group_ids = $connection_a->get_group_ids();
					$connection_b_group_ids = $connection_b->get_group_ids();

					$other_group_name_a = '';
					foreach ( $connection_a_group_ids as $connection_a_group_id ) {
						if ( $connection_a_group_id !== $group_id ) {
							$other_group_a      = groups_get_group( $connection_a_group_id );
							$other_group_name_a = $other_group_a->name;
							break;
						}
					}

					$other_group_name_b = '';
					foreach ( $connection_b_group_ids as $connection_b_group_id ) {
						if ( $connection_b_group_id !== $group_id ) {
							$other_group_b      = groups_get_group( $connection_b_group_id );
							$other_group_name_b = $other_group_b->name;
							break;
						}
					}

					return strcmp( $other_group_name_a, $other_group_name_b );
				}
			);
		}

		return $connections;
	}
}
