jQuery(function ($) {

	$('#template-data-form').on('submit', function (e) {
		e.preventDefault();
		var form = $(this);
		var formData = new FormData(form[0]);
		catch_wiz_template_formdata(formData);

	});
});
   

  function catch_wiz_template_formdata(formdata) {
	// Send the data via ajax to be saved in the database
	jQuery.ajax({
		url: wizard.ajaxurl,
		method: "POST",
		data: {
			action: "your_action_name",
			security: wizard.nonce,
			// other data...
		},
		success: function (response) {
			// Handle the response
		},
	});
  }