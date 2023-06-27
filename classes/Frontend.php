<?php
/**
 * Frontend integration, including controller logic.
 */

namespace OpenLab\Connections;

/**
 * Frontend integration.
 */
class Frontend {
	/**
	 * Private constructor.
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Gets the singleton instance.
	 *
	 * @return \OpenLab\Modules\Schema
	 */
	public static function get_instance() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initializes WP hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

		add_action( 'bp_actions', [ $this, 'process_invitation_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_delete_request' ] );

		add_action( 'wp_ajax_openlab_connection_group_search', [ $this, 'process_group_search_ajax' ] );
	}

	/**
	 * Gets build asset info.
	 *
	 * Used when enqueuing assets, to help with cache busting during development.
	 *
	 * @return array{"dependencies": string[], "version": string}
	 */
	public static function get_build_asset_file() {
		$build_dir        = ROOT_DIR . '/build/';
		$build_asset_file = include $build_dir . 'frontend.asset.php';

		// Replace "wp-blockEditor" with "wp-block-editor".
		$build_asset_file['dependencies'] = array_replace(
			$build_asset_file['dependencies'],
			array_fill_keys(
				array_keys( $build_asset_file['dependencies'], 'wp-blockEditor', true ),
				'wp-block-editor'
			)
		);

		return $blocks_asset_file;
	}

	public function register_assets() {
		$build_asset_file = self::get_build_asset_file();

		wp_register_style(
			'openlab-connections-frontend',
			OPENLAB_CONNECTIONS_PLUGIN_URL . '/build/frontend.css',
			[],
			$build_asset_file['version']
		);

		wp_register_script(
			'openlab-connections-frontend',
			OPENLAB_CONNECTIONS_PLUGIN_URL . '/build/frontend.js',
			[ 'jquery', 'jquery-ui-autocomplete', 'wp-backbone' ],
			$build_asset_file['version'],
			true
		);

		return;

		wp_register_script(
			'openlab-group-connections',
			// @todo move internally
			get_stylesheet_directory_uri() . '/js/group-connections.js',
			[ 'jquery', 'jquery-ui-autocomplete', 'wp-backbone' ],
			OL_VERSION,
			true
		);
	}

	/**
	 * Processes invitation requests.
	 *
	 * @return void
	 */
	public function process_invitation_request() {
		if ( empty( $_POST['openlab-connection-invitations-nonce'] ) ) {
			return;
		}

		check_admin_referer( 'openlab-connection-invitations', 'openlab-connection-invitations-nonce' );

		if ( !  Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_POST['invitation-group-ids'] ) ) {
			return;
		}

		$group_ids = array_map( 'intval', $_POST['invitation-group-ids'] );

		$messages = ''; // Initialize the message string

		foreach ( $group_ids as $group_id ) {
			$retval = Util::send_connection_invitation(
				[
					'inviter_group_id' => bp_get_current_group_id(),
					'invitee_group_id' => $group_id,
					'inviter_user_id'  => bp_loggedin_user_id(),
				]
			);

			$group = groups_get_group( $group_id );

			if ( $retval['success'] ) {
				$messages .= sprintf( 'Successfully sent invitation to the group "%s".' . "\n", $group->name );
			} else {
				switch ( $retval['status'] ) {
					case 'invitation_exists' :
						$messages .= sprintf( 'An invitation for the group "%s" already exists.' . "\n", $group->name );
						break;

					default :
						$messages .= sprintf( 'Could not send invitation to the group "%s".' . "\n", $group->name );
						break;
				}
			}
		}

		if ( !  empty( $messages ) ) {
			bp_core_add_message( $messages );
		}

		bp_core_redirect( $_POST['_wp_http_referer'] );
	}

	/**
	 * Processes an invitation delete request.
	 *
	 * @return void
	 */
	public function process_invitation_delete_request() {
		if ( !  bp_is_group() || ! bp_is_current_action( 'connections' ) || ! bp_is_action_variable( 0, 'new' ) ) {
			return;
		}

		if ( !  Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_GET['delete-invitation'] ) ) {
			return;
		}

		$invitation_id = (int) $_GET['delete-invitation'];

		check_admin_referer( 'delete-invitation-' . $invitation_id );

		$invitation = Invitation::get_instance( $invitation_id );
		if ( !  $invitation ) {
			return;
		}

		$deleted = $invitation->delete();

		$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/new/';

		if ( $deleted ) {
			bp_core_add_message( 'You have successfully deleted the invitation.', 'success' );
		} else {
			bp_core_add_message( 'The invitation could not be deleted.', 'error' );
		}

		bp_core_redirect( $redirect_url );
	}

	/**
	 * AJAX handler for group search.
	 *
	 * @return void
	 */
	public function process_group_search_ajax() {
		global $wpdb;

		if ( !  isset( $_GET['term'] ) ) {
			echo wp_json_encode( [] );
			die;
		}

		$term = wp_unslash( $_GET['term'] );

		$group_format_callback = function( $group ) {
			return [
				'groupName'   => $group->name,
				'groupUrl'    => bp_get_group_permalink( $group ),
				'groupAvatar' => bp_get_group_avatar( [ 'html' => false ], $group ),
				'groupId'     => $group->id,
			];
		};

		$retval = [];
		if ( filter_var( $term, FILTER_VALIDATE_URL ) ) {
			$group_base = bp_get_groups_directory_permalink();
			if ( str_starts_with( $term, $group_base ) ) {
				$url_tail   = str_replace( $group_base, '', $term );
				$tail_parts = explode( '/', $url_tail );
				$group_slug = $tail_parts[0];

				$group_id = BP_Groups_Group::group_exists( $group_slug );
				if ( $group_id ) {
					$group    = groups_get_group( $group_id );
					$retval[] = $group_format_callback( $group );
				}
			}
		} else {
			$groups = groups_get_groups(
				[
					'search_terms' => $term,
					'exclude'      => [ bp_get_current_group_id() ],
				]
			);

			$retval = array_map( $group_format_callback, $groups['groups'] );
		}

		echo wp_json_encode( $retval );
		die;
	}
}
