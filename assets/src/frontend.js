(function($){
	/* Make a Connection panel */
	const $sendInvitationsWrap = $('#send-invitations');
	const $sendInvitationsList = $('#send-invitations-list');

	let debounceTimer

	$newConnectionSearch = $( '#new-connection-search' );
	if ( $newConnectionSearch.length ) {
		$newConnectionSearch.autocomplete({
			minLength: 2,
			source: ajaxurl + '?action=openlab_connection_group_search',
			select: function( event, ui ) {
				// Don't allow dupes.
				if ( document.getElementById( 'connection-invitation-group-' + ui.item.groupId ) ) {
					return false;
				}

				const inviteTemplate = wp.template( 'openlab-connection-invitation' );
				const inviteMarkup = inviteTemplate( ui.item );
				$sendInvitationsList.append( inviteMarkup );

				showHideSendInvitations();

				return false;
			}
		})
		.autocomplete( "instance" )._renderItem = function( ul, item ) {
			return $( "<li>" )
				.append( "<div><strong>" + item.groupName + "</strong><br>" + item.groupUrl + "</div>" )
				.appendTo( ul );
		};
	}

	$sendInvitationsList.on( 'click', '.remove-connection-invitation', function( e ) {
		e.target.closest( '.group-item' ).remove();

		showHideSendInvitations();

		return false;
	} );

	const showHideSendInvitations = () => {
		if ( $sendInvitationsList.children().length > 0 ) {
			$sendInvitationsWrap.show();
		} else {
			$sendInvitationsWrap.hide();
		}
	}

	/* Connected Groups panel */

	// Find all accordion elements
	const accordions = document.querySelectorAll('.accordion');

	// Add event listeners to toggle the accordion
	accordions.forEach((accordion) => {
		const toggle = accordion.querySelector('.accordion-toggle');
		const content = accordion.querySelector('.accordion-content');

		toggle.addEventListener('click', () => {
			const expanded = toggle.getAttribute('aria-expanded') === 'true' || false;

			toggle.setAttribute('aria-expanded', !expanded);
			content.style.display = expanded ? 'none' : 'block';
		});
	});

	const disconnectButtons = document.querySelectorAll('.disconnect-button')
	disconnectButtons.forEach((disconnectButton) => {
		const disconnectText = disconnectButton.getAttribute( 'aria-label' )
		const connectedText = disconnectButton.innerHTML

		disconnectButton.addEventListener( 'mouseenter', () => {
			disconnectButton.textContent = disconnectText
		} )

		disconnectButton.addEventListener( 'mouseleave', () => {
			disconnectButton.textContent = connectedText
		} )
	})

	$('.connection-tax-term-selector').select2({
		width: '80%'
	});

	const saveConnectionSettings = ( connectionId ) => {
		const connectionSettings = document.getElementById( 'connection-settings-' + connectionId )

		const postToggle = document.getElementById( 'connection-setting-' + connectionId + '-post' ).checked
		const commentToggle = document.getElementById( 'connection-setting-' + connectionId + '-comment' ).checked

		const selectedPostCategories = $( '#connection-setting-' + connectionId + '-category-terms' ).val()
		const selectedPostTags = $( '#connection-setting-' + connectionId + '-tag-terms' ).val()

		const nonce = $( '#connection-settings-' + connectionId + '-nonce' ).val()

		const groupId = connectionSettings.closest( '.connections-settings' ).dataset.groupId

		const data = {
			connectionId,
			postToggle,
			commentToggle,
			selectedPostCategories,
			selectedPostTags,
			groupId,
			nonce
		}

		$.post(
			{
				url: ajaxurl + '?action=openlab_connections_save_connection_settings',
				data
			}
		);
	}

	document.querySelectorAll( '.connection-settings input[type="checkbox"]' ).forEach( (checkbox) => {
		checkbox.addEventListener(
			'change',
			() => {
				clearTimeout( debounceTimer )
				debounceTimer = setTimeout(
					() => {
						saveConnectionSettings( checkbox.closest( '.connection-settings' ).dataset.connectionId )
					},
					500
				)
			}
		)
	})

	$( '.connection-settings select' ).on( 'select2:select', (e) => {
		clearTimeout( debounceTimer )
		debounceTimer = setTimeout(
			() => {
				saveConnectionSettings( e.target.closest( '.connection-settings' ).dataset.connectionId )
			},
			500
		)
	} )

})(jQuery)
