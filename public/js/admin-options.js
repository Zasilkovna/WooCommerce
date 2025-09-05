(function ($) {
	$(function () {
		$( '[data-packetery-select2]' ).select2({
			dropdownParent: $('.custom-select2-wrapper')
		});

		$('.js-packetery-delete-log').on('click', function(e) {
			if (!confirm(translationsAdminOptions.confirmLogDeletion)) {
				e.preventDefault();
				return false;
			}
		});
	});
})(jQuery);
