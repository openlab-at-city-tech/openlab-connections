<?php
/**
 * Connection object.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Connection object.
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
	 * Sets the connection ID for this connection.
	 *
	 * @param int $connection_id Connection ID.
	 */
	public function set_connection_id( $connection_id ) {
		$this->connection_id = (int) $connection_id;
	}

	/**
	 * Sets the ID of the first group in this connection.
	 *
	 * @param int $group_1_id Group 1 ID.
	 */
	public function set_group_1_id( $group_1_id ) {
		$this->group_1_id = (int) $group_1_id;
	}

	/**
	 * Sets the ID of the second group in this connection.
	 *
	 * @param int $group_2_id Group 2 ID.
	 */
	public function set_group_2_id( $group_2_id ) {
		$this->group_2_id = (int) $group_2_id;
	}

	/**
	 * Sets the date_created for this connection.
	 *
	 * @param string $date_created Date created, in MySQL format.
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * Gets the connection ID for this connection.
	 *
	 * @return int
	 */
	public function get_connection_id() {
		return (int) $this->connection_id;
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
	 * @return null|OpenLab_Group_Connection
	 */
	public static function get_instance( $connection_id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %s WHERE connection_id = %d", self::get_table_name(), $connection_id ) );

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

		$result = $wpdb->delete(
			$table_name,
			[
				'connection_id' => $this->connection_id,
			],
			[
				'%d',
			]
		);

		return (bool) $result;
	}
}
