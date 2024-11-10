export {
	add_repeater_row,
	remove_repeater_row
};

function add_repeater_row(repeater) {
	const row_template = repeater.querySelector(".repeater-row-template");
	const rows = repeater.querySelector(".repeater-rows");
	const row_count = rows.querySelectorAll(".repeater-row").length;

	// Clone the row template and update the row index
	const new_row = row_template.cloneNode(true);
	new_row.classList.remove("repeater-row-template");
	new_row.classList.add("repeater-row");
	new_row.innerHTML = new_row.innerHTML.replace(/\{\{row\}\}/g, row_count);

	// Append the new row to the repeater
	rows.appendChild(new_row);

	// Hide the empty repeater message if it exists
	const empty_message = repeater.querySelector(".empty-repeater-message");
	if (empty_message) {
		empty_message.style.display = 'none';
	}
}

function remove_repeater_row(repeater, row) {
	// Remove the row from the repeater
	row.remove();

	// Show the empty repeater message if there are no rows left
	const row_count = repeater.querySelectorAll(".repeater-row").length;
	if (row_count === 0) {
		const empty_message = repeater.querySelector(".empty-repeater-message");
		if (empty_message) {
			empty_message.style.display = 'block';
		}
	}
}

// Adding a new row
document.addEventListener("click", function(e) {
	if (e.target.matches(".wizard-form-fieldgroup.repeater .add-row:not(.disabled)")) {
		e.preventDefault();
		const repeater = e.target.closest(".wizard-form-fieldgroup.repeater");
		add_repeater_row(repeater);
	}
});

// Removing a row
document.addEventListener("click", function(e) {
	if (e.target.matches(".wizard-form-fieldgroup.repeater .remove-row:not(.disabled)")) {
		e.preventDefault();
		const repeater = e.target.closest(".wizard-form-fieldgroup.repeater");
		const row = e.target.closest(".repeater-row");
		remove_repeater_row(repeater, row);
	}
});

// Intercept click on empty link
document.addEventListener("click", function(e) {
	if (e.target.matches(".wizard-form-fieldgroup.repeater .disabled")) {
		e.preventDefault();
	}
});

