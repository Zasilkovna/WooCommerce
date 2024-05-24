var PacketeryMultiplier = function( wrapperSelector ) {
	var $wrapper = jQuery( wrapperSelector ),
		multiplier = this;

	this.registerListeners = function() {
		$wrapper
			.on( 'click', '[data-replication-add]', function() {
				multiplier.addItem( this );
			} )
			.on( 'click', '[data-replication-delete]', function() {
				multiplier.deleteItem( this );
			} )
			.each( function() {
				multiplier.toggleDeleteButton( jQuery( this ).find( '[data-replication-item-container]' ) );
			} );
	};

	this.addItem = function( button ) {
		var $container = jQuery( button ).closest( wrapperSelector ).find( '[data-replication-item-container]' ),
			$template = getTemplateClone( $container );

		updateIds( $template, newId++ );
		$container.append( $template );
		$template.find( '[data-nette-rules]' ).each( function() {
			this.removeAttribute( 'data-lfv-initialized' );
			LiveForm.setupHandlers( this );
		} );

		this.toggleDeleteButton( $container );
	};

	this.deleteItem = function( button ) {
		var $row = jQuery( button ).closest( '[data-replication-item]' ),
			$container = $row.closest( '[data-replication-item-container]' );

		$row.remove();
		this.toggleDeleteButton( $container );
	};

	this.toggleDeleteButton = function( $container ) {
		var optionsCount = $container.find( '[data-replication-item]' ).length,
			$buttons = $container.find( '[data-replication-delete]' ),
			minItems = parseInt( $container.data( 'replication-min-items' ) );

		(optionsCount > minItems ? $buttons.show() : $buttons.hide());
	};

	/**
	 * Find the highest counter in the rendered form (invalid form gets re-rendered with its submitted new_* form items)
	 */
	function findMaxNewId() {
		var $newInputs = jQuery( wrapperSelector + ' [name*=' + prefix + ']' ),
			maxNewId = 1;

		$newInputs.each( function() {
			var newIdMatch = jQuery( this ).attr( 'name' ).match( '\\[' + prefix + '(\\d+)\\]' );
			var counter = parseInt( newIdMatch[ 1 ] );
			maxNewId = Math.max( maxNewId, counter + 1 );
		} );

		return maxNewId;
	}

	var prefix = 'new_',
		newId = findMaxNewId();

	function getTemplateClone( container ) {
		var formId = container.closest( '[data-replication-form-id]' ).data( 'replication-form-id' );
		if ( !formId ) {
			formId = container.closest( 'form' ).attr( 'id' );
		}

		var formTemplateId = formId + '_template';
		return jQuery( '#' + formTemplateId ).find( wrapperSelector ).find( '[data-replication-item]' ).first().clone(); // table tr is currently the replication item
	}

	/**
	 * Update references to element names to make them unique; the value itself doesn't matter: [0] -> [new_234]
	 */
	function updateIds( $html, id ) {
		jQuery( 'input, select, label, .packetery-input-validation-message', $html ).each( function( i, element ) {
			var $element = jQuery( element );

			updateId( $element, 'name', id, ['[', ']'], '' );
			updateId( $element, 'data-lfv-message-id', id, ['-', '-'], '' );
			updateId( $element, 'data-nette-rules', id, ['[', ']'], 'g' );
			updateId( $element, 'for', id, ['-', '-'], '' );
			updateId( $element, 'id', id, ['-', '-'], '' );
		} );
	}

	function updateId( $element, attrName, id, delimiters, flags ) {
		var value = $element.attr( attrName );
		if ( !value ) {
			return;
		}

		// don't use data() because we want the raw values, not parsed json arrays/objects
		var regExp = new RegExp( '\\' + delimiters[ 0 ] + '0\\' + delimiters[ 1 ], flags );
		$element.attr( attrName, value.replace( regExp, delimiters[ 0 ] + prefix + id + delimiters[ 1 ] ) );
	}

	this.registerListeners();
};
