<?php
/**
 * 'Make a Connection' template.
 *
 * @package openlab-connections
 */

$group_label_uc = openlab_get_group_type_label( 'case=upper' );

$sent_invites = \OpenLab\Connections\Invitation::get(
	[
		'inviter_group_id' => bp_get_current_group_id(),
		'pending_only'     => true,
	]
);

?>

<?php do_action( 'template_notices' ); ?>

<div class="openlab-connections">
	<?php get_template_part( 'buddypress/groups/single/connections/header' ); ?>

	<form method="post" class="form-panel">
		<div class="panel panel-default">
			<div class="panel-heading"><?php esc_html_e( 'Make a Connection', 'openlab-connections' ); ?></div>

			<div class="panel-body">
				<p><strong><?php esc_html_e( 'Search for an OpenLab group', 'openlab-connections' ); ?></strong></p>
				<p><?php esc_html_e( 'Start typing the name of the OpenLab group or copy/paste the group\'s URL.', 'openlab-connections' ); ?></p>
				<label for="new-connection-search" class="sr-only"><?php esc_html_e( 'Type Group Name or Paste URL', 'openlab-connections' ); ?></label>
				<input type="text" class="form-control" id="new-connection-search" />
				<input id="new-connection-group-id" name="new-connection-group-id" type="hidden" value="" />

				<div id="send-invitations" style="display: none;">
					<p><strong><?php esc_html_e( 'Send Invitations', 'openlab-connections' ); ?></strong></p>
					<?php // @todo Improve for i18n. ?>
					<?php // translators: Group type. ?>
					<p><?php printf( esc_html__( 'These groups will be sent an invitation to connect to your %s.', 'openlab-connections' ), esc_html( $group_label_uc ) ); ?></p>
					<div id="send-invitations-list" class="invites group-list item-list row"></div>

					<?php wp_nonce_field( 'openlab-connection-invitations', 'openlab-connection-invitations-nonce' ); ?>
					<input type="submit" value="<?php esc_attr_e( 'Send Invites', 'openlab-connections' ); ?>" class="btn btn-primary btn-margin btn-margin-top" />
				</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading"><?php esc_html_e( 'Pending Invites', 'openlab-connections' ); ?></div>

			<div class="panel-body">

				<?php if ( $sent_invites ) : ?>
					<p><?php esc_html_e( 'You have sent invitations to the following groups:', 'openlab-connections' ); ?></p>
						<div class="sent-invitations connection-invitations">
							<div class="sent-invitation connection-invitation connection-invitation-header">
								<div class="actions"><span class="sr-only"><?php esc_html_e( 'Delete', 'openlab-connections' ); ?></span></div>
								<div class="group"><?php esc_html_e( 'Group', 'openlab-connections' ); ?></div>
								<div class="sent"><?php esc_html_e( 'Sent', 'openlab-connections' ); ?></div>
							</div>

							<?php foreach ( $sent_invites as $invite ) : ?>
								<?php

								$group = groups_get_group( $invite->get_invitee_group_id() );

								$date_format = get_option( 'date_format' );
								if ( ! is_string( $date_format ) ) {
									$date_format = 'F j, Y';
								}

								$date_sent = '0000-00-00 00:00:00' === $invite->get_date_created() ? '' : date_i18n( $date_format, strtotime( $invite->get_date_created() ) );

								$delete_url = bp_get_group_permalink( groups_get_current_group() ) . 'connections/new/';
								$delete_url = add_query_arg( 'delete-invitation', $invite->get_invitation_id(), $delete_url );
								$delete_url = wp_nonce_url( $delete_url, 'delete-invitation-' . $invite->get_invitation_id() );

								?>

								<div class="sent-invitation connection-invitation">
									<div class="actions"><a href="<?php echo esc_url( $delete_url ); ?>" class="delete-invite" onclick="return confirm(<?php echo esc_attr( __( 'Are you sure you want to delete this invitation?', 'openlab-connections' ) ); ?>)" data-invitation-id="<?php echo esc_attr( (string) $invite->get_invitation_id() ); ?>"><span class="sr-only"><?php esc_html_e( 'Delete Invitation', 'openlab-connections' ); ?></span></a></div>
									<div class="group"><a href="<?php echo esc_url( bp_get_group_permalink( $group ) ); ?>"><?php echo esc_html( $group->name ); ?></a></div>
									<div class="sent"><?php echo esc_html( $date_sent ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
				<?php else : ?>
					<p><?php esc_html_e( 'None of your connection invitations are awaiting a response.', 'openlab-connections' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>

<script type="text/html" id="tmpl-openlab-connection-invitation">
	<div id="connection-invitation-group-{{ data.groupId }}" class="group-item col-xs-12">
		<div class="group-item-wrapper">
			<div class="row info-row">
				<div class="item-avatar alignleft col-xs-7">
					<a href="{{ data.groupUrl }}"><img class="img-responsive" src="{{ data.groupAvatar }}"" alt="{{ data.groupName }}"></a>
				</div>
				<div class="item col-xs-17">
					<p class="item-title h2"><a class="no-deco truncate-on-the-fly" href="{{ data.groupUrl }}" data-basevalue="65" data-minvalue="20" data-basewidth="280" style="opacity: 1;">{{ data.groupName }}</a></p>

					<div class="action invite-member-actions">
						<button class="remove-connection-invitation link-button"><?php echo esc_html_e( 'Remove invite', 'openlab-connections' ); ?></a>
					</div>
				</div>

				<input type="hidden" name="invitation-group-ids[]" value="{{ data.groupId }}" />
			</div>
		</div>
	</div>
</script>
