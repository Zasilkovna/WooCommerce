document.addEventListener('DOMContentLoaded', function () {
	const link = document.querySelector('.js-packetery-deactivate');
	if (link) {
		link.addEventListener('click', function (event) {
			if (!confirm(translationsDeactivation.confirmDeactivation)) {
				event.preventDefault();
			}
		});
	}
});
