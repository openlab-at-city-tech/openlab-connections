<?php
/**
 * Implementation of BP_Groups_Extension.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Connections group extension.
 */
class GroupExtension extends \BP_Group_Extension {
	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$access = Util::user_can_initiate_group_connections() ? 'public' : 'noone';

		parent::init(
			[
				'slug'              => 'connections',
				'name'              => __( 'Connections Settings', 'openlab-connections' ),
				'access'            => $access,
				'nav_item_position' => 105,
			]
		);
	}

	/**
	 * Template loader.
	 *
	 * @param int $group_id ID of the group.
	 * @return void
	 */
	public function display( $group_id = null ) {
		wp_enqueue_script( 'openlab-connections-frontend' );

		switch ( bp_action_variable( 0 ) ) {
			case 'new' :
			case 'invitations' :
				$template_name = bp_action_variable( 0 );
				break;

			default :
				$template_name = 'index';
				break;
		}

		bp_get_template_part( 'groups/single/connections/' . $template_name );
	}
}
