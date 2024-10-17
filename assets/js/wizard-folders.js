jQuery(function ($) {
	// Click events
	$(".create-wizard-folder").on("click", function () {
		var parentFolderId = "root";
		if ($(this).data("folder-id")) {
			parentFolderId = $(this).data("folder-id");
		}
		create_new_wizard_folder(parentFolderId);
	});

	$(".delete-folder").on("click", function () {
		const folderId = $(this).data("folder-id");
		delete_single_folder(folderId);
	});

	$(".move-folder").on("click", function () {
		let folderId = $(this).data("folder-id");
		openFolderSelectionModal((newFolderId) => moveSingleFolder(folderId, newFolderId));
	});

	$(".edit-folder-title").on('click', function() {
		let folderId = $(this).attr("data-editable");
		let existingName = $(`tr[data-id="${folderId}"] .wizard-table-template-name a`).text();

		openFolderTitleEditor(folderId, existingName);
	});

	function openFolderTitleEditor(folderId, existingName) {
		Swal.fire({
			title: "Rename folder",
			html: `<input id="folder-title" class="swal2-input" placeholder="Enter new folder title" value="${existingName}">`,
			focusConfirm: false,
			showCancelButton: true,
			confirmButtonText: "Rename",
			preConfirm: () => {
				const folderTitle = Swal.getPopup().querySelector("#folder-title").value;
				if (!folderTitle) {
					Swal.showValidationMessage("Please enter a folder title");
				}
				return { folderTitle: folderTitle };
			},
		}).then((result) => {
			if (result.isConfirmed) {
				renameSingleFolder(folderId, result.value.folderTitle);
				const folderTitleElement = document.querySelector(`tr[data-id="${folderId}"] .wizard-table-template-name a`);
				if (folderTitleElement) {
					folderTitleElement.textContent = result.value.folderTitle;
					// Highlight <td> of title with yellow and then fade it out after 1 second
					highlightElement(`tr[data-id="${folderId}"] .wizard-table-template-name`, 2000); 
				}
			}
		});
	}

});

// Create new folder with option parent ID
function create_new_wizard_folder(parentFolderId = "root") {
	Swal.fire({
		title: "Create New Folder",
		html: '<input id="swal-input1" class="swal2-input" placeholder="Enter folder name">',
		focusConfirm: false,
		showCancelButton: true,
		confirmButtonText: "Create",
		cancelButtonText: "Cancel",
		preConfirm: () => {
			const folderName = Swal.getPopup().querySelector("#swal-input1").value;
			if (!folderName) {
				Swal.showValidationMessage("Please enter a folder name");
			}
			return { folderName: folderName };
		},
	}).then((result) => {
		if (result.isConfirmed) {
			const folderName = result.value.folderName;

			jQuery
				.post(wizard.ajaxurl, {
					action: "add_wizard_user_folder",
					nonce: wizard.nonce,
					parent_id: parentFolderId,
					folder_name: folderName,
				})
				.done(function (response) {
					if (response.success) {
						location.reload();
						// Swal.fire({
						// 	title: "Folder Created",
						// 	text: "The folder was successfully created.",
						// 	icon: "success",
						// }).then(function () {
						// 	location.reload();
						// });
					} else {
						console.error("Failed to create folder:", response.data);
						Swal.fire("Error!", "Failed to create folder.", "error");
					}
				})
				.fail(function () {
					console.error("Error creating folder");
					Swal.fire("Error!", "An error occurred while creating the folder.", "error");
				});
		}
	});
}

// Delete single folder
function delete_single_folder(folderId) {
	Swal.fire({
		title: "Delete Folder?",
		html: "Are you sure? This folder will be <strong>permanently</strong> deleted. Any templates in this folder will be moved to the folder's parent folder.",
		icon: "warning",
		showCancelButton: true,
		confirmButtonText: "Delete",
	}).then((result) => {
		if (result.isConfirmed) {
			jQuery
				.post(wizard.ajaxurl, {
					action: "delete_wizard_user_folder",
					nonce: wizard.nonce,
					folder_id: folderId,
				})
				.done(function (response) {
					if (response.success) {
						Swal.fire({
							title: "Folder Deleted",
							text: "The folder was successfully deleted.",
							icon: "success",
						}).then(function () {
							location.reload();
						});
					} else {
						console.error("Failed to delete folder:", response.data);
						Swal.fire("Error!", "Failed to delete folder.", "error");
					}
				})
				.fail(function () {
					console.error("Error deleting folder");
					Swal.fire("Error!", "An error occurred while deleting the folder.", "error");
				});
		}
	});
}

function moveSingleFolder(folderId, newParentId) {
	return jQuery.ajax({
		url: wizard.ajaxurl,
		method: "POST",
		data: {
			action: "move_wizard_user_folder",
			nonce: wizard.nonce,
			folder_id: folderId,
			new_parent_id: newParentId,
		},
	});
}

function renameSingleFolder(folderId, newName) {
	return jQuery.ajax({
		url: wizard.ajaxurl,
		method: "POST",
		data: {
			action: "update_wizard_user_folder_name",
			nonce: wizard.nonce,
			folder_id: folderId,
			folder_name: newName,
		},
	});
}

function openFolderSelectionModal(moveActionCallback) {
	jQuery.ajax({
		url: wizard.ajaxurl,
		method: "GET",
		data: {
			action: "get_wizard_user_folders",
			nonce: wizard.nonce,
		},
		success: function (response) {
			if (response.success) {
				let folders = response.data.map((folder) => `<option value="${folder.id}">${folder.text}</option>`);
				Swal.fire({
					title: "Select destination folder",
					html: `<select id="folder-select" class="swal2-input">${folders.join("")}</select>`,
					confirmButtonText: "Move",
					showCancelButton: true,
					preConfirm: () => {
						return jQuery("#folder-select").val();
					},
					onOpen: () => {
						jQuery("#folder-select").select2();
					},
				}).then((result) => {
					if (result.value) {
						let selectedFolderId = result.value;
						moveActionCallback(selectedFolderId)
							.done(() => {
								Swal.fire("Moved", "The item has been moved successfully.", "success").then(() => location.reload());
							})
							.fail((error) => {
								console.error("Error moving item", error);
								do_wiz_notif({ message: "There was an error moving the item.", duration: 3000 });
							});
					}
				});
			}
		},
	});
}
