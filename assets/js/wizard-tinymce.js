function init_editable_folder_titles() {
	tinymce.init({
		selector: ".editable.folder-title",
		inline: true,
		menubar: false,
		toolbar: false,
		setup: function (editor) {
			editor.on("change", function (e) {
				var editableTitle = editor.getElement();
				var folderId = editableTitle.getAttribute("data-folder-id");
				var folderTitle = editor.getContent();
				renameSingleFolder(folderId, folderTitle);
			});
		},
	});
}

function init_editable_template_data_fields() {
	tinymce.init({
		selector: ".editable.template-data-fieldgroup-value",
		inline: true,
		menubar: false,
		toolbar: false,
		setup: function (editor) {
			editor.on("change", function (e) {
			
				var newValue = editor.getContent();
				jQuery
					.post(wizard.ajaxurl, {
						action: "edit_template_data_field",
						nonce: wizard.nonce,
						new_value: newValue,
					})
					.done(function (response) {
						if (response.success) {
							//location.reload();
							do_wiz_notif({message: "Field updated successfully"});
						} else {
							console.error("Failed to update field:", response.data);
							Swal.fire("Error!", "Failed to update field.", "error");
						}
					})
					.fail(function () {
						console.error("Error updating field");
						Swal.fire("Error!", "An error occurred while updating the field.", "error");
					});
				
			});
		},
	});
}