jQuery(function ($) {
	// Handle bulk checkboxes on folder and template rows
	$(document).on("change", ".wizard-table-bulk-check > input", function () {
		if ($(this).is(":checked")) {
			$("#bulk-actions").removeClass("disabled");
			$("#bulk-actions button").prop("disabled", false);
		} else {
			if ($(".wizard-table-bulk-check > input:checked").length === 0) {
				$("#bulk-actions").addClass("disabled");
				$("#bulk-actions button").prop("disabled", true);
			}
		}
	});

	// Handle check-all checkbox
	$(document).on("change", ".wizard-table-bulk-check-all > input", function () {
		// Check all .wizard-table-bulk-check boxes if we're checking the check-all box
		$(".wizard-table-bulk-check > input:visible").prop("checked", this.checked);
	});

	$("#delete-selected").on("click", function () {
		let selectedItems = $(".wizard-table-bulk-check > input:checked");
		bulk_delete_folders_or_templates(selectedItems);
	});

	// Move selected folders and/or templates somewhere else
	$("#move-selected").on("click", function () {
		let selectedItems = $(".wizard-table-bulk-check > input:checked");
		bulk_move_folders_or_templates(selectedItems);
	});
});

// Bulk move folders or templates
function bulk_move_folders_or_templates(selectedItems) {
	let templateIds = [];
	let folderIds = [];

	selectedItems.each(function () {
		let itemId = jQuery(this).val();
		let itemType = jQuery(this).data("type");
		if (itemType === "template") {
			templateIds.push(itemId);
		} else if (itemType === "folder") {
			folderIds.push(itemId);
		}
	});

	openFolderSelectionModal((selectedFolderId) => {
		// Prepare promises for moving templates
		let templateMovePromises = templateIds.map((templateId) => moveSingleTemplate(templateId, selectedFolderId));

		// Prepare promises for moving folders
		let folderMovePromises = folderIds.map((folderId) => moveSingleFolder(folderId, selectedFolderId));

		// Execute all promises and show confirmation once all have succeeded
		return Promise.all([...templateMovePromises, ...folderMovePromises])
			.then(() => {
				Swal.fire({
					title: "Items Moved",
					text: "The selected items were successfully moved.",
					icon: "success",
				}).then(() => location.reload());
			})
			.catch((error) => {
				console.error("An error occurred during the move operation", error);
				Swal.fire({
					title: "Error moving items",
					text: "Whoops, there was an error moving the items!",
					icon: "error",
				});
			});
	});
}

function bulk_delete_folders_or_templates(selectedItems) {
	// First, show a confirmation Swal
	Swal.fire({
		title: "Delete Selected?",
		html: "Folders will be <strong>permanently</strong> deleted . Templates can be restored from the trash later. Any templates inside selected folders will be moved into the deleted folder's parent.",
		icon: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		confirmButtonText: "Yes, delete them!",
	}).then((result) => {
		if (result.isConfirmed) {
			// Proceed with deletion if confirmed
			let deletePromises = [];

			selectedItems.each(function () {
				let itemId = jQuery(this).val();
				let itemType = jQuery(this).data("type");
				let ajaxData = {
					url: wizard.ajaxurl,
					method: "POST",
					data: {
						nonce: wizard.nonce,
					},
				};

				if (itemType == "folder") {
					ajaxData.data.action = "delete_wizard_user_folder";
					ajaxData.data.folder_id = itemId;
				} else {
					ajaxData.data.action = "delete_wizard_user_template";
					ajaxData.data.template_id = itemId;
				}

				// Push each delete operation promise to the array
				deletePromises.push(
					new Promise((resolve, reject) => {
						jQuery.ajax(ajaxData).done(resolve).fail(reject);
					})
				);
			});

			// Wait for all delete operations to complete
			Promise.all(deletePromises)
				.then(() => {
					Swal.fire("Deleted!", "All selected items have been deleted.", "success").then(() => {
						location.reload(); // Reload the page
					});
				})
				.catch((error) => {
					// Handle any errors that occurred during the delete operations
					Swal.fire("Error!", "An error occurred during the delete operations. Error: " + error, "error");
				});
		}
	});
}
