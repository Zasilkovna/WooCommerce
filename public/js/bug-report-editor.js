jQuery(document).ready(function($) {
	if (typeof wp === 'undefined' || !wp.editor) {
		return;
	}

	wp.editor.initialize('packetery-bug-report-form-message', {
		tinymce: {
			height: 200,
			menubar: false,
			plugins: '',
			toolbar: 'bold italic',
			content_css: []
		},
		quicktags: false,
		mediaButtons: false
	});
});
