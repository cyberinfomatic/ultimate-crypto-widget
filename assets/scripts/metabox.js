(function () {
	jQuery(document).ready(function ($) {
		const widget_type_element = document.getElementById('ucwp_widget_type');
		widget_type_element?.addEventListener('change', showWidgetForm);
		showWidgetForm();

		$('.ucwp-chosen-select').each(function() {
			const $this = $(this);
			// Check if the element has the 'data-max-selected' attribute
			const maxSelected = $this.data('max-selected') || undefined;
			// Initialize 'chosen' for each element
			$this.chosen({
				no_results_text: "Oops, nothing found!",
				width: "100%",
				// If maxSelected is present, set the 'max_selected_options' property
				max_selected_options: maxSelected
			});
		});

	});
	function showWidgetForm() {
		const widget_type = document.getElementById('ucwp_widget_type')?.value;
		const widget_form_container = document.getElementById('ucwp_widget');
		let widget_type_inputs = widget_form_container?.querySelectorAll('.ucwpmetabox-field') || [];
		// remove the first element with a child element of id 'ucwp_widget_type'
		widget_type_inputs = Array.from(widget_type_inputs).filter(function (input) {
			return input.querySelector('#ucwp_widget_type') === null;
		});
		widget_type_inputs.forEach(function (input) {
			const show_input = input.querySelector(`[ucwp-show-only*="${widget_type}"]`)
			if (show_input) {
				input.style.display = 'block';
			} else {
				input.style.display = 'none';
			}
		});
	}
})();
