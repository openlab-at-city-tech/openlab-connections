import './frontend.scss'

(function($){
	/* Make a Connection panel */
	const $sendInvitationsWrap = $('#send-invitations');
	const $sendInvitationsList = $('#send-invitations-list');

	let debounceTimer

	const $newConnectionSearch = $( '#new-connection-search' );
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

	const processNoneCheckboxes = () => {
		const connections = document.querySelectorAll( '.connection-settings' )

		const currentGroupStatus = document.getElementById( 'current-group-site-status' )?.value

		connections.forEach( ( connection ) => {
			const noneCheckbox = connection.querySelector( '.connection-setting-none' )

			if ( ! noneCheckbox ) {
				return
			}

			// Non-public groups always have None checked and other fields disabled.
			if ( currentGroupStatus && 'public' !== currentGroupStatus ) {
				connection.classList.add( 'disabled' )
				connection.querySelector( '.connection-tax-term-selector' ).disabled = true
				connection.querySelector( '.connection-setting-exclude-comments' ).disabled = true
				$( connection ).find( '.connection-setting' ).addClass( 'is-disabled' )

				noneCheckbox.checked = true
				noneCheckbox.disabled = true
//				connection.querySelector( '.connection-private-group-notice' ).classList.remove( 'is-hidden' )
			} else {
				if ( noneCheckbox.checked ) {
					connection.classList.add( 'disabled' )
					connection.querySelector( '.connection-tax-term-selector' ).disabled = true
					connection.querySelector( '.connection-setting-exclude-comments' ).disabled = true
				} else {
					connection.classList.remove( 'disabled' )
					connection.querySelector( '.connection-tax-term-selector' ).disabled = false
					connection.querySelector( '.connection-setting-exclude-comments' ).disabled = false
				}
			}
		} )
	}

	processNoneCheckboxes()

	// When unchecking None, set Categories to 'All Categories'.
	$( '.connection-setting-none' ).on( 'change', ( e ) => {
		const connectionCategories = $( e.target ).closest( '.connection-settings' ).find( '.connection-tax-term-selector' )
		if ( ! connectionCategories ) {
			return
		}

		if ( e.target.checked ) {
			connectionCategories.val( [] ).trigger( 'change' )
		} else {
			connectionCategories.val( [ '_all' ] ).trigger( 'change' )
		}

		processNoneCheckboxes()
	} )

	const saveConnectionSettings = ( connectionId ) => {
		const connectionSettings = document.getElementById( 'connection-settings-' + connectionId )

		const selectedPostCategories = $( '#connection-' + connectionId + '-categories' ).val()
		const excludeComments = document.getElementById( 'connection-' + connectionId + '-exclude-comments' ).checked

		const nonce = $( '#connection-settings-' + connectionId + '-nonce' ).val()

		const groupId = connectionSettings.closest( '.connections-settings' ).dataset.groupId

		const data = {
			connectionId,
			excludeComments,
			selectedPostCategories,
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

	// Actions that trigger asynchronous settings save.
	document.querySelectorAll( '.connection-settings input[type="checkbox"]' ).forEach( (checkbox) => {
		checkbox.addEventListener(
			'change',
			() => {
				processNoneCheckboxes()

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

	$( '.connection-settings select' ).on( 'select2:selecting', (e) => {
		const currentSelected = $( e.target ).val()
		const newSelection = e.params.args.data.id

		let newSelected
		if ( '_all' === newSelection ) {
			newSelected = [ '_all' ]
		} else {
			newSelected = currentSelected.filter( ( selectedValue ) => selectedValue !== '_all' )
			newSelected.push( newSelection )
		}

		$( e.target ).val( newSelected ).trigger( 'change' )
	} )

	$( '.connection-settings select' ).on( 'change.select2', (e) => {
		processNoneCheckboxes()

		clearTimeout( debounceTimer )
		debounceTimer = setTimeout(
			() => {
				saveConnectionSettings( e.target.closest( '.connection-settings' ).dataset.connectionId )
			},
			500
		)
	} )

	// On blur, if the dropdown is empty, select 'All Categories'.
	$( '.connection-settings select' ).on( 'select2:close', (e) => {
		if ( 0 === $( e.target ).val().length ) {
			$( e.target ).val( [ '_all' ] ).trigger( 'change' )
		}
	} )

	// Unhide connection privacy notices.
	$( '.connection-privacy-notices' ).each( ( index, noticesEl ) => {
		console.log(noticesEl)
		if ( noticesEl.querySelectorAll( 'p' ).length ) {
			noticesEl.classList.remove( 'is-hidden' )
		}
	} )

})(jQuery)
