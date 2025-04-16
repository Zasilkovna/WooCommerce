( function ( wp ) {
	var el = wp.element.createElement;

	wp.blocks.registerBlockType( 'tutorial/new-product-form-field', {
		title: 'Product form field',
		attributes: {},
		edit: function () {
			return el( 'p', {}, 'Hello World (from the editor).' );
		},
	} );
} )( window.wp );
