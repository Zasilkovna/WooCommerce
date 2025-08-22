jQuery(document).ready(function($) {
	if (typeof wp === 'undefined' || typeof wp.editor === 'undefined') {
		return;
	}

	wp.editor.initialize('packetery-bug-report-form-message', {
		tinymce: {
			height: 200,
		},
	});
});
