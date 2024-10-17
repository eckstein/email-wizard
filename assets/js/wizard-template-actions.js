jQuery(function ($) {
	// Delete a single template when .delete-template is clicked
	$(".delete-template").on("click", function () {
		const template_id = $(this).data("template-id");
		Swal.fire({
			title: "Delete Template?",
			html: "Template can be restored from the trash later.",
			icon: "warning",
			showCancelButton: true,
			confirmButtonText: "Delete",
		}).then((result) => {
			if (result.isConfirmed) {
				$.post(wizard.ajaxurl, {
					action: "delete_wizard_user_template",
					nonce: wizard.nonce,
					template_id: template_id,
				})
					.done(function (response) {
						if (response.success) {
							Swal.fire({
								title: "Template Deleted",
								text: "The template was successfully deleted.",
								icon: "success",
							}).then(function () {
								location.reload();
							});
						} else {
							console.error("Failed to delete template:", response.data);
							Swal.fire("Error!", "Failed to delete template.", "error");
						}
					})
					.fail(function () {
						console.error("Error deleting folder");
						Swal.fire("Error!", "An error occurred while deleting the template.", "error");
					});
			}
		});
	});

	$(".move-template").on("click", function () {
		let templateId = $(this).data("template-id");
		openFolderSelectionModal((newFolderId) => moveSingleTemplate(templateId, newFolderId));
	});

	$(".duplicate-template").on("click", function () {
		let templateId = $(this).data("template-id");
		// do ajax call do duplicate_wizard_template
		$.post(wizard.ajaxurl, {
			action: "duplicate_wizard_template",
			nonce: wizard.nonce,
			template_id: templateId,
		}).done(function (response) {
			if (response.success) {
				Swal.fire({
					title: "Template Duplicated",
					text: "The template was successfully duplicated.",
					icon: "success",
				}).then(function () {
					location.reload();
				});
			}
		});
	});
});

function moveSingleTemplate(templateId, newFolderId) {
	return jQuery.ajax({
		url: wizard.ajaxurl,
		method: "POST",
		data: {
			action: "ajax_update_template_wizard_user_folder",
			nonce: wizard.nonce,
			template_id: templateId,
			folder_id: newFolderId,
		},
	});
}
