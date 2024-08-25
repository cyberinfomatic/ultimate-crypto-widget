(function (){
	jQuery(document).ready(function ($) {
		let ucwpQuickWidgetLastRender = Date.now() - 2000;
		const widget_type_element = document.getElementById('ucwp_widget_type');
		const cnt = document.getElementById('ucwp_widget');
		widget_type_element?.addEventListener('change', showPreview);
		const cnt_inputs = cnt?.querySelectorAll('.ucwpmetabox-field input, .ucwpmetabox-field select, .ucwpmetabox-field textarea');
		cnt_inputs?.forEach(function (input) {
			input.addEventListener('input', showPreview);
		});
		// also listen to any chosen.js change for ucwp-chosen-select but inside a jquery event
		$('.ucwp-chosen-select').on('change', showPreview);
		showPreview();

		//  for the copy button
		const copyButton = document.querySelector('.ucwp-copy-shortcode');
		copyButton?.addEventListener('click', function () {
			const preview = document.querySelector('.ucwp-short-code-preview');
			const message = document.querySelector('.ucwp-copy-shortcode-message');
			if(navigator.clipboard) {
				navigator.clipboard.writeText(preview.innerText);
				message.innerText = 'Shortcode copied';
			}
			else {
				message.innerText = 'Copy not supported';
			}
			setTimeout(function () {
				message.innerText = '';
			}, 2000);

		});


		function showPreview(){
			const widget_type = document.getElementById('ucwp_widget_type')?.value;
			const widget_form_container = document.getElementById('ucwp_widget');
			let widget_type_inputs = widget_form_container?.querySelectorAll('.ucwpmetabox-field') || [];
			// remove the first element with a child element of id 'ucwp_widget_type'
			widget_type_inputs = Array.from(widget_type_inputs).filter(function (input) {
				return input.querySelector('#ucwp_widget_type') === null;
			});
			let newpreview = '[ucwp_widget type="' + widget_type + '"';
			let atts = {
				type : widget_type
			}
			widget_type_inputs.forEach(function (input) {
				const show_input = input.querySelector(`[ucwp-show-only*="${widget_type}"]`)
				if (show_input) {
					let name = show_input.getAttribute('id') ?? show_input.getAttribute('name');
					if (!name) {
						return;
					}
					let value = ''
					//  if name start with ucwp_widget_ remove it
					name = name.replace('ucwp_widget_', '');
					if(!show_input.multiple){
						value = show_input.value;
					}
					else {
						value = Array.from(show_input.selectedOptions).map(function (option) {
							return option.value;
						});
					}
					newpreview += ' ' + name + '="' + value + '"';
					atts[name] = value;
				}
			});
			newpreview += ']';
			const preview_container = document.querySelector('.ucwp-short-code-preview');
			preview_container.innerHTML = newpreview;
			// getShortCodeRender('ucwp_widget', atts);
		}



		// a function to send post request to /shortcode to get the render shortcode HTML and append it to the shortcode preview container,
		function getShortCodeRender(shortcode = null, atts = {}){
			// last render should be at least 2 second ago
			if(Date.now() - ucwpQuickWidgetLastRender < 2000){
				return setTimeout(function () {
					getShortCodeRender(shortcode);
				}, 2000);
			}
			const preview_container = document.getElementById('ucwp_widget_shortcode_render');
			const preview = document.querySelector('.ucwp-short-code-preview');
			const s = shortcode || preview.innerText;
			preview_container.innerHTML = `Loading...`
			const data = new FormData();
			data.append('shortcode', s);
			data.append('atts', JSON.stringify(atts));
			fetch('/wp-json/ultimate-crypto-widget/v1/load-shortcode', {
				method: 'POST',
				body: data
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (d) {
					console.log(d)
					preview_container.innerHTML = d.content;
					ucwpQuickWidgetLastRender = Date.now();
				})
				.catch(function (error) {
					console.error(error);
				});
		}
	})

})()

