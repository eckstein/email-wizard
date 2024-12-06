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
	// Remove the row
	row.remove();

	// Reindex remaining rows
	const rows = repeater.querySelectorAll(".repeater-row:not(.repeater-row-template)");
	rows.forEach((row, index) => {
		// Update all input names in this row to use the new index
		const inputs = row.querySelectorAll('input, select, textarea');
		inputs.forEach(input => {
			const name = input.getAttribute('name');
			if (name) {
				input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
			}
		});
	});

	// Show the empty repeater message if there are no rows left
	if (rows.length === 0) {
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
	// Check if the clicked element is within a .remove-row element
	if (e.target.closest(".wizard-form-fieldgroup.repeater .remove-row:not(.disabled)")) {
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

