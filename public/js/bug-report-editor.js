jQuery(document).ready(function($) {
	if (typeof wp === 'undefined' || typeof wp.editor === 'undefined') {
		return;
	}

	wp.editor.initialize('packetery-js-bug-report-form-message', {
		tinymce: {
			height: 200,
			width: 800
		},
	});
});
