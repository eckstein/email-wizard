import Swal from "sweetalert2";
import NiceSelect from "nice-select2";
import { init_file_explorer } from "./fileExplorer-init.js";
import { highlight_element, handleFetchError, handleHTTPResponse, show_error_toast, show_success_toast, highlight_and_remove } from "../../utils/functions.js";

import { move_single_item, delete_item_from_server, remove_item_from_ui, show_delete_item_confirm } from "./common.js";

import { templateTableAPI } from "./template-table-api.js";

export { open_folder_title_editor, create_new_wizard_folder, rename_single_folder, select_folder, remove_item_from_ui };

function create_new_wizard_folder(parentFolderId = "root") {
	show_create_folder_dialog()
		.then((result) => {
			if (!result.isConfirmed) {
				// User cancelled - throw error to break the chain
				throw new Error("User cancelled");
			}
			const folderName = result.value.folderName;
			return create_folder(parentFolderId, folderName);
		})
		.then((newFolderId) => {
			// Only proceed if we have a newFolderId
			if (newFolderId) {
				return get_folder_row_html(parentFolderId, newFolderId);
			}
			throw new Error("No folder ID returned");
		})
		.then((data) => {
			if (data.success && data.data) {
				add_folder_to_table(data.data.html);
			} else {
				throw new Error(data.data || "Failed to update folder list");
			}
		})
		.catch((error) => {
			if (error.message !== "User cancelled") {
				handleFetchError(error);
			}
		});
}

async function get_folder_row_html(parentFolderId, newFolderId) {
	const urlParams = new URLSearchParams(window.location.search);
	const args = {
		orderby: urlParams.get("orderby"),
		order: urlParams.get("order"),
	};

	const response = await fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "generate_template_table_part",
			nonce: wizard.nonce,
			part: "folder_row",
			current_folder: parentFolderId,
			user_id: wizard.current_user_id,
			item_id: newFolderId,
			args: JSON.stringify(args),
		}),
	});
	return handleHTTPResponse(response);
}

function add_folder_to_table(htmlData) {
	const folderTable = document.querySelector(".wizard-folders-table tbody.subfolders");
	
	// Remove any existing "no items" message
	templateTableAPI.removeEmptyState();
	
	folderTable.insertAdjacentHTML("beforeend", htmlData.trim());
	const newRow = folderTable.lastElementChild;

	if (newRow && newRow.tagName === "TR") {
		init_file_explorer();
		show_success_toast("Folder created successfully");
		setTimeout(() => {
			highlight_element("#" + newRow.id, 2000);
		}, 300);
	} else {
		throw new Error("Unexpected HTML structure returned");
	}
}

function show_create_folder_dialog() {
	return Swal.fire({
		title: "Create New Folder",
		html: '<input id="swal-input1" class="swal2-input" placeholder="Enter folder name">',
		focusConfirm: false,
		showCancelButton: true,
		confirmButtonText: "Create",
		cancelButtonText: "Cancel",
		allowEnterKey: true,
		allowEscapeKey: true,
		preConfirm: () => {
			const folderName = Swal.getPopup().querySelector("#swal-input1").value;
			if (!folderName) {
				Swal.showValidationMessage("Please enter a folder name");
			}
			return { folderName: folderName };
		},
	});
}

async function create_folder(parentFolderId, folderName) {
	const response = await fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "add_wizard_user_folder",
			nonce: wizard.nonce,
			parent_id: parentFolderId,
			folder_name: folderName,
		}),
	});
	const data = await handleHTTPResponse(response);
	if (data.success) {
		return data.data.folder_id;
	} else {
		throw new Error(data.data || "Failed to create folder");
	}
}

async function rename_single_folder(folderId, newName) {
	return fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "update_wizard_user_folder_name",
			nonce: wizard.nonce,
			folder_id: folderId,
			folder_name: newName,
		}),
	}).then((response) => {
		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}
		return response.json();
	});
}
function open_folder_title_editor(folderId, existingName) {
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
			rename_single_folder(folderId, result.value.folderTitle);
			const folderTitleElement = document.querySelector(`tr[data-id="${folderId}"] .wizard-table-folder-name-link`);
			if (folderTitleElement) {
				folderTitleElement.textContent = result.value.folderTitle;
				// Highlight <td> of title
				highlight_element(`tr[data-id="${folderId}"] .wizard-table-template-name`, 2000);
			}
		}
	});
}
async function select_folder(title = "Select folder") {
	return new Promise(async (resolve, reject) => {
		try {
			const currentFolderId = wizard.current_folder_id;
			
			// Get folders excluding current folder
			const foldersResponse = await get_user_folders([currentFolderId]);
			let availableFolders = foldersResponse.data || [];

			// Add root folder if not currently in root
			if (currentFolderId && currentFolderId !== "root") {
				availableFolders.unshift({
					id: "root",
					name: "Root",
				});
			}

			if (availableFolders.length > 0) {
				let folderOptions = availableFolders.map((folder) => 
					`<option value="${folder.id}">${folder.name}</option>`
				);

				Swal.fire({
					title: title,
					html: `<select id="folder-select" class="swal2-input">${folderOptions.join("")}</select>`,
					confirmButtonText: "Select",
					showCancelButton: true,
					customClass: {
						container: "swal-with-folder-select",
					},
					preConfirm: () => {
						return document.getElementById("folder-select").value;
					},
					didOpen: () => {
						new NiceSelect(document.getElementById("folder-select"), {
							searchable: true,
						});
					},
				})
				.then(resolve)
				.catch(reject);
			} else {
				reject(new Error("No available folders to select from"));
			}
		} catch (error) {
			reject(error);
		}
	});
}

async function get_user_folders(exclude = []) {
	const response = await fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "get_wizard_user_folders",
			exclude: JSON.stringify(exclude),
			nonce: wizard.nonce,
		}),
	});
	return handleHTTPResponse(response);
}
