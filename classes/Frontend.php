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
		add_action( 'bp_actions', [ $this, 'register_template_stack' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

		add_action( 'bp_group_options_nav', [ $this, 'group_sidebar' ], 15 );

		add_action( 'bp_actions', [ $this, 'process_invitation_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_delete_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_accept_request' ] );
		add_action( 'bp_actions', [ $this, 'process_invitation_reject_request' ] );

		add_action( 'bp_actions', [ $this, 'process_disconnect_request' ] );

		add_filter( 'bp_after_activity_get_parse_args', [ $this, 'add_activity_scope_support' ] );

		add_action( 'wp_ajax_openlab_connection_group_search', [ $this, 'process_group_search_ajax' ] );
		add_action( 'wp_ajax_openlab_connections_save_connection_settings', [ $this, 'process_save_connection_settings' ] );
	}

	/**
	 * Register our theme template directory with BuddyPress.
	 *
	 * @return void
	 */
	public function register_template_stack() {
		bp_register_template_stack(
			function () {
				return OPENLAB_CONNECTIONS_PLUGIN_DIR . 'templates';
			},
			20
		);
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
				$messages .= sprintf( 'Successfully sent invitation for the group "%s".' . "\n", $group->name );
			} else {
				switch ( $retval['status'] ) {
					case 'invitation_exists' :
						$messages .= sprintf( 'An invitation for the group "%s" already exists.' . "\n", $group->name );
						break;

					default :
						$messages .= sprintf( 'Could not send invitation for the group "%s".' . "\n", $group->name );
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

		$group_format_callback = function ( $group ) {
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
			$group_id   = null;
			if ( str_starts_with( $term, $group_base ) ) {
				$url_tail   = str_replace( $group_base, '', $term );
				$tail_parts = explode( '/', $url_tail );
				$group_slug = $tail_parts[0];

				$group_id = groups_get_id( $group_slug );
			} else {
				$parts = wp_parse_url( $term );

				if ( ! empty( $parts['host'] ) && ! empty( $parts['path'] ) ) {
					$site = get_site_by_path( $parts['host'], $parts['path'] );
					if ( $site && 1 !== $site->blog_id ) {
						$group_id = openlab_get_group_id_by_blog_id( $site->blog_id );
					}
				}
			}

			if ( $group_id && bp_get_current_group_id() !== $group_id ) {
				$group    = groups_get_group( $group_id );
				$retval[] = $group_format_callback( $group );
			}
		} else {
			$groups = groups_get_groups(
				[
					'search_terms' => addslashes( $term ),
					'exclude'      => [ bp_get_current_group_id() ],
					'status'       => [ 'public', 'private' ],
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

		$settings['exclude_comments'] = ! empty( $_POST['excludeComments'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['excludeComments'] ) );

		$saved = groups_update_groupmeta( $group_id, 'connection_settings_' . $connection_id, $settings );

		if ( $saved ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Adds support for openlab-connections activity 'scope' values to bp_activity_get().
	 *
	 * @param mixed[] $args Arguments from bp_activity_get().
	 * @return mixed[]
	 */
	public function add_activity_scope_support( $args ) {
		if ( 'connected-groups' !== $args['scope'] && 'this-group-and-connected-groups' !== $args['scope'] ) {
			return $args;
		}

		$group_id = null;
		if ( isset( $args['filter']['primary_id'] ) ) {
			$group_id = (int) $args['filter']['primary_id'];
		} else {
			$group_id = bp_get_current_group_id();
		}

		$passed_activity_types = ! empty( $args['filter']['action'] ) ? $args['filter']['action'] : [];
		if ( ! is_array( $passed_activity_types ) ) {
			$passed_activity_types = explode( ',', $passed_activity_types );
		}

		$allow_new_blog_post    = empty( $passed_activity_types ) || in_array( 'new_blog_post', $passed_activity_types, true );
		$allow_new_blog_comment = empty( $passed_activity_types ) || in_array( 'new_blog_comment', $passed_activity_types, true );

		$connections = \OpenLab\Connections\Connection::get( [ 'group_id' => $group_id ] );

		$connected_group_clauses = array_map(
			function ( $connection ) use ( $allow_new_blog_post, $allow_new_blog_comment, $group_id ) {
				$c_group_ids        = $connection->get_group_ids();
				$connected_group_id = null;
				foreach ( $c_group_ids as $c_group_id ) {
					if ( $c_group_id !== $group_id ) {
						$connected_group_id = $c_group_id;
						break;
					}
				}

				if ( ! $connected_group_id ) {
					return [];
				}

				// Content from non-public group sites is never shared.
				if ( ! Util::group_has_public_site( $connected_group_id ) ) {
					return [];
				}

				$connected_group_settings = $connection->get_group_settings( $connected_group_id );

				if ( empty( $connected_group_settings['categories'] ) ) {
					return [];
				}

				$limit_to_posts    = [];
				$limit_to_comments = [];
				if ( is_array( $connected_group_settings['categories'] ) ) {
					$group_site_id = openlab_get_site_id_by_group_id( $connected_group_id );
					if ( $group_site_id ) {
						switch_to_blog( $group_site_id );

						// Secondary sites don't run taxonomy-terms-order.
						$taxonomy_terms_order_is_active = function_exists( 'TO_apply_order_filter' );
						if ( $taxonomy_terms_order_is_active ) {
							remove_filter( 'terms_clauses', 'TO_apply_order_filter', 10 );
						}

						$limit_to_posts = get_posts(
							[
								'fields'         => 'ids',
								'posts_per_page' => -1,

								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								'tax_query'      => [
									[
										'taxonomy' => 'category',
										'terms'    => $connected_group_settings['categories'],
										'field'    => 'term_id',
									],
								],
							]
						);

						$has_limit_to_posts = true;
						if ( ! $limit_to_posts ) {
							$limit_to_posts     = [ 0 ];
							$has_limit_to_posts = false;
						}

						$limit_to_comments = [ 0 ];
						if ( ! $connected_group_settings['exclude_comments'] && $has_limit_to_posts ) {
							$limit_to_comments = get_comments(
								[
									'fields'         => 'ids',
									'posts_per_page' => -1,
									'post__in'       => $limit_to_posts,
								]
							);

							if ( ! $limit_to_comments ) {
								$limit_to_comments = [ 0 ];
							}
						}

						if ( $taxonomy_terms_order_is_active ) {
							// @phpstan-ignore-next-line
							add_filter( 'terms_clauses', 'TO_apply_order_filter', 10, 3 );
						}

						restore_current_blog();
					}
				}

				$group_query = [
					'relation' => 'AND',
					[
						'column' => 'component',
						'value'  => 'groups',
					],
					[
						'column'  => 'item_id',
						'value'   => [ $connected_group_id ],
						'compare' => 'IN',
					],
				];

				if ( $limit_to_posts || $limit_to_comments ) {
					$type_query = [
						'relation' => 'OR',
					];

					$type_query[] = [
						[
							'column' => 'type',
							'value'  => $allow_new_blog_post ? 'new_blog_post' : '',
						],
						[
							'column'  => 'secondary_item_id',
							'value'   => $limit_to_posts,
							'compare' => 'IN',
						],
					];

					$type_query[] = [
						[
							'column' => 'type',
							'value'  => $allow_new_blog_comment ? 'new_blog_comment' : '',
						],
						[
							'column'  => 'secondary_item_id',
							'value'   => $limit_to_comments,
							'compare' => 'IN',
						],
					];

					$group_query[] = $type_query;
				} else {
					$activity_types = [ '' ];

					if ( $allow_new_blog_post ) {
						$activity_types[] = 'new_blog_post';
					}

					if ( $allow_new_blog_comment && empty( $connected_group_settings['exclude_comments'] ) ) {
						$activity_types[] = 'new_blog_comment';
					}

					$group_query[] = [
						'column'  => 'type',
						'value'   => $activity_types,
						'compare' => 'IN',
					];
				}

				return $group_query;
			},
			$connections
		);

		$connected_group_clauses = array_filter( $connected_group_clauses );

		if ( 'this-group-and-connected-groups' === $args['scope'] ) {
			$connected_group_clauses[] = [
				[
					'column' => 'component',
					'value'  => 'groups',
				],
				[
					'column' => 'item_id',
					'value'  => $group_id,
				],
				[
					'column'  => 'type',
					'value'   => $passed_activity_types,
					'compare' => 'IN',
				],
			];
		}

		if ( $connected_group_clauses ) {
			$connected_group_clauses['relation'] = 'OR';

			$args['filter_query'] = $connected_group_clauses;
		} else {
			$args['in'] = [ 0 ];
		}

		$args['scope']                = false;
		$args['primary_id']           = false;
		$args['filter']['primary_id'] = false;
		$args['filter']['action']     = false;

		return $args;
	}
}
