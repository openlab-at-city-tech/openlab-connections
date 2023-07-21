<?php
/**
 * Frontend integration, including controller logic.
 *
 * @package openlab-connections
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
	 * @return \OpenLab\Connections\Frontend
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

		add_action( 'bp_group_options_nav', [ $this, 'group_sidebar' ], 15 );

		add_action( 'bp_actions', [ $this, 'process_invitation_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_delete_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_accept_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_reject_request' ] );

		add_action( 'bp_actions', [ $this, 'process_disconnect_request' ] );

		add_action( 'wp_ajax_openlab_connection_group_search', [ $this, 'process_group_search_ajax' ] );
		add_action( 'wp_ajax_openlab_connections_save_connection_settings', [ $this, 'process_save_connection_settings' ] );
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

		return $build_asset_file;
	}

	/**
	 * Registers static assets.
	 *
	 * @return void
	 */
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
	}

	/**
	 * Adds the Connections list to group sidebars.
	 *
	 * @return void
	 */
	public function group_sidebar() {
		if ( ! Util::is_connections_enabled_for_group() ) {
			return;
		}

		// Non-public groups shouldn't show this to non-members.
		$group = groups_get_current_group();
		if ( 'public' !== $group->status && empty( $group->user_has_access ) ) {
			return;
		}

		$connections = Connection::get(
			[
				'group_id' => bp_get_current_group_id(),
			]
		);

		// No connections to display.
		if ( empty( $connections ) ) {
			return;
		}

		?>

		<div class="group-connections-sidebar-widget" id="group-connections-sidebar-widget" class="sidebar-widget">
			<h2 class="sidebar-header">
				<span><?php esc_html_e( 'Connections', 'openlab-connections' ); ?></span>
				<i class="fa fa-rss connections-icon"></i>
			</h2>

			<div class="sidebar-block">
				<ul class="group-connection-list sidebar-sublinks inline-element-list group-data-list">
					<?php foreach ( $connections as $connection ) : ?>
						<?php

						$connected_group_id  = null;
						$connected_group_ids = $connection->get_group_ids();
						foreach ( $connected_group_ids as $check_group_id ) {
							if ( bp_get_current_group_id() !== $check_group_id ) {
								$connected_group_id = $check_group_id;
								break;
							}
						}

						$connected_group = groups_get_group( $connected_group_id );

						?>

						<li><a href="<?php echo esc_url( bp_get_group_permalink( $connected_group ) ); ?>"><?php echo esc_html( $connected_group->name ); ?></a></li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>

		<?php
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

		if ( ! Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_POST['invitation-group-ids'] ) ) {
			return;
		}

		$group_ids = array_map( 'intval', $_POST['invitation-group-ids'] );

		$messages = '';

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

		if ( ! empty( $messages ) ) {
			bp_core_add_message( $messages );
		}

		$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/new/';

		bp_core_redirect( $redirect_url );
	}

	/**
	 * Processes an invitation delete request.
	 *
	 * @return void
	 */
	public function process_invitation_delete_request() {
		if ( ! bp_is_group() || ! bp_is_current_action( 'connections' ) || ! bp_is_action_variable( 0, 'new' ) ) {
			return;
		}

		if ( ! Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_GET['delete-invitation'] ) ) {
			return;
		}

		$invitation_id = (int) $_GET['delete-invitation'];

		check_admin_referer( 'delete-invitation-' . $invitation_id );

		$invitation = Invitation::get_instance( $invitation_id );
		if ( ! $invitation ) {
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
	 * Processes an invitation accept request.
	 *
	 * @return void
	 */
	public function process_invitation_accept_request() {
		if ( ! bp_is_group() || ! bp_is_current_action( 'connections' ) || ! bp_is_action_variable( 0, 'invitations' ) ) {
			return;
		}

		if ( ! Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_GET['accept-invitation'] ) ) {
			return;
		}

		$invitation_id = (int) $_GET['accept-invitation'];

		check_admin_referer( 'accept-invitation-' . $invitation_id );

		$invitation = Invitation::get_instance( $invitation_id );
		if ( ! $invitation ) {
			return;
		}

		$accepted = $invitation->accept();

		$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/invitations/';

		if ( $accepted ) {
			$invitation->send_accepted_notification();
			bp_core_add_message( 'You have successfully accepted the invitation.', 'success' );
		} else {
			bp_core_add_message( 'The invitation could not be accepted.', 'error' );
		}

		bp_core_redirect( $redirect_url );
	}

	/**
	 * Processes an invitation rejection request.
	 *
	 * @return void
	 */
	public function process_invitation_reject_request() {
		if ( ! bp_is_group() || ! bp_is_current_action( 'connections' ) || ! bp_is_action_variable( 0, 'invitations' ) ) {
			return;
		}

		if ( ! Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_GET['reject-invitation'] ) ) {
			return;
		}

		$invitation_id = (int) $_GET['reject-invitation'];

		check_admin_referer( 'reject-invitation-' . $invitation_id );

		$invitation = Invitation::get_instance( $invitation_id );
		if ( ! $invitation ) {
			return;
		}

		$rejected = $invitation->reject();

		$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/invitations/';

		if ( $rejected ) {
			bp_core_add_message( 'You have successfully rejected the invitation.', 'success' );
		} else {
			bp_core_add_message( 'The invitation could not be rejected.', 'error' );
		}

		bp_core_redirect( $redirect_url );
	}

	/**
	 * Processes a disconnect request.
	 *
	 * @return void
	 */
	public function process_disconnect_request() {
		if ( ! bp_is_group() || ! bp_is_current_action( 'connections' ) || bp_action_variable( 0 ) ) {
			return;
		}

		if ( ! Util::is_connections_enabled_for_group() || ! Util::user_can_initiate_group_connections() ) {
			return;
		}

		if ( empty( $_GET['disconnect'] ) ) {
			return;
		}

		$connection_id = (int) $_GET['disconnect'];

		check_admin_referer( 'disconnect-' . $connection_id );

		$connection = Connection::get_instance( $connection_id );
		if ( ! $connection ) {
			return;
		}

		$deleted = $connection->delete();

		$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/';

		if ( $deleted ) {
			bp_core_add_message( 'You have successfully disconnected.', 'success' );
		} else {
			bp_core_add_message( 'The disconnection could not be processed.', 'error' );
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

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['term'] ) ) {
			echo wp_json_encode( [] );
			die;
		}

		$term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

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

				$group_id = groups_get_id( $group_slug );
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

	/**
	 * AJAX handler for saving connection settings.
	 *
	 * @return void
	 */
	public function process_save_connection_settings() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['connectionId'] ) || ! isset( $_POST['groupId'] ) ) {
			return;
		}

		$connection_id = (int) $_POST['connectionId'];

		check_admin_referer( 'connection-settings-' . $connection_id, 'nonce' );

		$group_id = (int) $_POST['groupId'];
		if ( ! Util::user_can_initiate_group_connections( get_current_user_id(), $group_id ) ) {
			return;
		}

		$settings = [
			'categories'       => [],
			'exclude_comments' => false,
		];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_categories   = isset( $_POST['selectedPostCategories'] ) ? wp_unslash( $_POST['selectedPostCategories'] ) : [];
		$selected_categories = [];
		if ( is_array( $posted_categories ) ) {
			if ( in_array( '_all', $posted_categories, true ) ) {
				$selected_categories = 'all';
			} else {
				$selected_categories = array_map( 'intval', $posted_categories );
			}
		}
		$settings['categories'] = $selected_categories;

		$exclude_comments = ! empty( $_POST['excludeComments'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['excludeComments'] ) );

		$saved = groups_update_groupmeta( $group_id, 'connection_settings_' . $connection_id, $settings );

		die;
	}
}
