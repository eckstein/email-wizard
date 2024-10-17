function addRepeaterRow(repeater) {
	var rowTemplate = jQuery(repeater).find(".repeater-row-template").first();
	var rows = jQuery(repeater).find(".repeater-rows");
	var rowCount = rows.find(".repeater-row").length;

	// Clone the row template and update the row index
	var newRow = rowTemplate.clone();
	newRow.removeClass("repeater-row-template").addClass("repeater-row");
	newRow.html(newRow.html().replace(/\{\{row\}\}/g, rowCount));

	// Append the new row to the repeater
	rows.append(newRow);

	// Hide the empty repeater message if it exists
	jQuery(repeater).find(".empty-repeater-message").hide();
}

// Function to remove a row from the repeater
function removeRepeaterRow(repeater, row) {
	// Remove the row from the repeater
	jQuery(row).remove();

	// Show the empty repeater message if there are no rows left
	var rowCount = jQuery(repeater).find(".repeater-row").length;
	if (rowCount === 0) {
		jQuery(repeater).find(".empty-repeater-message").show();
	}
}

// Adding a new row
jQuery(document).on("click", ".wizard-form-fieldgroup.repeater .add-row:not(.disabled)", function (e) {
	e.preventDefault();
	var repeater = jQuery(this).closest(".wizard-form-fieldgroup.repeater");
	addRepeaterRow(repeater);
});

// Removing a row
jQuery(document).on("click", ".wizard-form-fieldgroup.repeater .remove-row:not(.disabled)", function (e) {
	e.preventDefault();
	var repeater = jQuery(this).closest(".wizard-form-fieldgroup.repeater");
	var row = jQuery(this).closest(".repeater-row");
	removeRepeaterRow(repeater, row);
});

// Intercept click on empty link
jQuery(document).on("click", ".wizard-form-fieldgroup.repeater .disabled", function (e) {
    e.preventDefault();
});

