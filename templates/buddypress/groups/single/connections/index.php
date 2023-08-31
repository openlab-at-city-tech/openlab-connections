<?php
/**
 * 'Connected Groups' template.
 *
 * @package openlab-connections
 */

// @todo Pagination.
$connections = \OpenLab\Connections\Connection::get( [ 'group_id' => bp_get_current_group_id() ] );

$group_site_id  = openlab_get_site_id_by_group_id( bp_get_current_group_id() );
$group_site_url = get_site_url( $group_site_id );

$site_categories = OpenLab\Connections\Util::fetch_taxonomy_terms_for_site( $group_site_url, 'category' );
$site_tags       = OpenLab\Connections\Util::fetch_taxonomy_terms_for_site( $group_site_url, 'post_tag' );

$current_group_type_label = openlab_get_group_type_label( [ 'case' => 'upper' ] );

$current_group_site_status = OpenLab\Connections\Util::group_has_public_site( bp_get_current_group_id() ) ? 'public' : 'private';

switch ( $current_group_site_status ) {
	case 'private' :
		// @todo Probably not good for translation.
		// translators: Group type.
		$group_status_text = sprintf( __( 'Your %s Site is only visible to its members, so activity will not be shared with Connections.', 'openlab-connections' ), $current_group_type_label );
		break;

	default :
		$group_status_text = '';
		break;
}

// We use the existence of pending invites to determine the text of the 'no connections' message.
$pending_invites_sent = \OpenLab\Connections\Invitation::get(
	[
		'inviter_group_id' => bp_get_current_group_id(),
		'pending_only'     => true,
	]
);

// We use the existence of pending invites to determine the text of the 'no connections' message.
$pending_invites_received = \OpenLab\Connections\Invitation::get(
	[
		'invitee_group_id' => bp_get_current_group_id(),
		'pending_only'     => true,
	]
);

?>

<?php do_action( 'template_notices' ); ?>

<div class="openlab-connections">
	<?php if ( $connections ) : ?>
		<div class="connections-settings" data-group-id="<?php echo esc_attr( bp_get_current_group_id() ); ?>">
			<?php get_template_part( 'buddypress/groups/single/connections/header' ); ?>

			<input type="hidden" id="current-group-site-status" value="<?php echo esc_attr( $current_group_site_status ); ?>" />

			<?php foreach ( $connections as $connection ) : ?>
				<?php
				$other_group_id_array = array_filter(
					$connection->get_group_ids(),
					function ( $group_id ) {
						return bp_get_current_group_id() !== $group_id;
					}
				);

				$connected_group_id = reset( $other_group_id_array );

				$connected_group = groups_get_group( $connected_group_id );

				$connected_group_url = bp_get_group_permalink( $connected_group );

				$connected_group_avatar = bp_core_fetch_avatar(
					[
						'item_id' => $connected_group_id,
						'object'  => 'group',
						'type'    => 'full',
					]
				);

				$connected_group_type = openlab_get_group_type( $connected_group_id );
				switch ( $connected_group_type ) {
					case 'portfolio' :
						$contact_link = bp_core_get_userlink( openlab_get_user_id_from_portfolio_group_id( $connected_group_id ) );
						break;

					default :
						$contact_link = openlab_output_group_contact_line( $connected_group_id );
						break;
				}

				$connected_group_type_label = openlab_get_group_type_label(
					[
						'group_id' => $connected_group_id,
						'case'     => 'upper',
					]
				);

				$connection_settings = $connection->get_group_settings( bp_get_current_group_id() );

				$connected_group_site_id = openlab_get_site_id_by_group_id( $connected_group_id );

				$connected_group_blog_public_raw = get_blog_option( $connected_group_site_id, 'blog_public' );
				$connected_group_blog_public     = is_numeric( $connected_group_blog_public_raw ) ? (int) $connected_group_blog_public_raw : 1;

				$selected_categories = [];
				if ( isset( $connection_settings['categories'] ) ) {
					if ( 'all' === $connection_settings['categories'] ) {
						$selected_categories = 'all';
					} else {
						$saved_categories    = is_array( $connection_settings['categories'] ) ? $connection_settings['categories'] : [];
						$selected_categories = array_map( 'intval', $saved_categories );
					}
				}

				$connection_id = (string) $connection->get_connection_id();

				?>
				<div class="connection-settings" id="connection-settings-<?php echo esc_attr( $connection_id ); ?>" data-connection-id="<?php echo esc_attr( $connection_id ); ?>">
					<div class="avatar-column">
						<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a href="<?php echo esc_url( $connected_group_url ); ?>"><?php echo $connected_group_avatar; ?></a>
					</div>

					<div class="primary-column">
						<div class="connected-group-link item-title h2">
							<a class="no-deco" href="<?php echo esc_url( $connected_group_url ); ?>"><?php echo esc_html( $connected_group->name ); ?></a><br />

							<?php if ( $contact_link ) : ?>
								<div class="info-line uppercase">
									<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo $contact_link; ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="connection-privacy-notices is-hidden">
							<?php if ( $connected_group_blog_public < -1 ) : ?>
								<p class="connection-private-group-notice">
									<?php // @todo Needs i18n improvement ?>
									<?php // translators: 1. Group type; 2. Group type. ?>
									<?php printf( esc_html__( 'This connected %1$s\'s Site is only visible to its members. Activity will not be shared with your %2$s.', 'openlab-connections' ), esc_html( $connected_group_type_label ), esc_html( $current_group_type_label ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $group_status_text ) : ?>
								<p class="connection-private-group-notice">
									<?php echo esc_html( $group_status_text ); ?>
								</p>
							<?php endif; ?>
						</div>

						<div class="accordion">
							<button class="accordion-toggle" aria-expanded="false" aria-controls="accordion-content">
								<span class="accordion-caret"></span>
								<span class="sr-only">Expand</span>

								<?php echo wp_kses_post( __( 'Manage the content you are sharing with this connection.', 'openlab-connections' ) ); ?>
							</button>

							<div class="accordion-content">
								<div class="connection-setting">
									<label for="connection-<?php echo esc_attr( $connection_id ); ?>-categories"><?php esc_html_e( 'Include posts and comments from the following categories:', 'openlab-connections' ); ?></label>
									<select multiple id="connection-<?php echo esc_attr( $connection_id ); ?>-categories" class="connection-tax-term-selector">
										<option value="_all" <?php selected( 'all' === $selected_categories ); ?>><?php esc_html_e( 'All categories', 'openlab-connections' ); ?></option>

										<?php foreach ( $site_categories as $site_category ) : ?>
											<?php
											$site_category_id   = is_array( $site_category ) && isset( $site_category['id'] ) ? $site_category['id'] : 0;
											$site_category_name = is_array( $site_category ) && isset( $site_category['name'] ) && is_string( $site_category['name'] ) ? $site_category['name'] : '';
											?>

											<option value="<?php echo esc_attr( (string) $site_category_id ); ?>" <?php selected( is_array( $selected_categories ) && in_array( $site_category_id, $selected_categories, true ) ); ?>><?php echo esc_html( $site_category_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="connection-setting connection-setting-checkbox">
									<input type="checkbox" <?php checked( $connection_settings['exclude_comments'] ); ?> class="connection-setting-exclude-comments" id="connection-<?php echo esc_attr( $connection_id ); ?>-exclude-comments" name="connection-settings[content-type][comment]" value="1" /> <label for="connection-<?php echo esc_attr( $connection_id ); ?>-exclude-comments"><?php esc_html_e( 'Do not include comments', 'openlab-connections' ); ?>
								</div>

								<div class="connection-setting connection-setting-checkbox">
									<input type="checkbox" <?php checked( empty( $selected_categories ) ); ?> class="connection-setting-none" id="connection-<?php echo esc_attr( $connection_id ); ?>-none" /> <label for="connection-<?php echo esc_attr( $connection_id ); ?>-none"><?php esc_html_e( 'Do not share any content with this connection', 'openlab-connections' ); ?>
								</div>
							</div><!-- .accordion-content -->
						</div>
					</div>

					<?php // translators: Name of the group. ?>
					<a href="<?php echo esc_url( wp_nonce_url( $connection->get_disconnect_url( bp_get_current_group_id() ), 'disconnect-' . $connection->get_connection_id() ) ); ?>" onclick="return confirm( '<?php echo esc_js( sprintf( __( 'Are you sure you want to disconnect from %s?', 'openlab-connections' ), $connected_group->name ) ); ?>' )" class="disconnect-button no-deco btn btn-primary" aria-label="<?php esc_attr_e( 'Disconnect', 'openlab-connections' ); ?>"><?php esc_html_e( 'Connected', 'openlab-connections' ); ?></a>

					<div class="connection-settings-save-status" id="connection-settings-save-status-<?php echo esc_attr( $connection_id ); ?>"></div>

					<?php wp_nonce_field( 'connection-settings-' . $connection_id, 'connection-settings-' . $connection_id . '-nonce', false ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p><?php echo wp_kses_post( __( 'This feature connects related spaces on the Openlab. It is useful for sharing site activity with cohorts, collaborators, and across course sections. Visit <a href="tk">OpenLab Help</a> for more information.', 'openlab-connections' ) ); ?></p>

		<?php if ( $pending_invites_sent ) : ?>
			<p><?php esc_html_e( 'You have sent Connections invitations.', 'openlab-connections' ); ?> <strong><a href="<?php echo esc_url( bp_get_group_permalink( groups_get_current_group() ) ); ?>connections/new/"><?php esc_html_e( 'View Pending Invitations', 'openlab-connections' ); ?></a></strong>.</p>
		<?php elseif ( $pending_invites_received ) : ?>
			<p><?php esc_html_e( 'You have been sent Connections invitations.', 'openlab-connections' ); ?> <strong><a href="<?php echo esc_url( bp_get_group_permalink( groups_get_current_group() ) ); ?>connections/invitations/"><?php esc_html_e( 'View Pending Invitations', 'openlab-connections' ); ?></a></strong>.</p>
		<?php else : ?>
			<p><?php esc_html_e( "You haven't made any Connections yet.", 'openlab-connections' ); ?> <strong><a href="<?php echo esc_url( bp_get_group_permalink( groups_get_current_group() ) ); ?>connections/new/"><?php esc_html_e( 'Make a Connection', 'openlab-connections' ); ?></a></strong>.</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
