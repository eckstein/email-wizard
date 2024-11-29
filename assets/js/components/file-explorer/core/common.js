import Swal from "sweetalert2";
import { highlightAndRemove } from "../../../utils/dom-utils";
import { showSuccessToast, showErrorToast } from "../../../utils/notification-utils";
import { handleFetchError } from "../../../utils/http-utils";

import { templateTableAPI } from "../services/template-table-api.js";
import { selectFolder } from "../ui/folder-dialogs.js";

export { moveItems, deleteItems, showDeleteConfirm, showRestoreConfirm, removeItemFromUi, handleMoveItems };

async function performAction(actionType, items) {
	if (!["move", "delete"].includes(actionType)) {
		throw new Error('Invalid action type. Must be either "move" or "delete".');
	}

	if (!items || Object.keys(items).length === 0) {
		throw new Error("No items provided for the action.");
	}

	try {
		const response = await fetch(wizard.ajaxurl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: new URLSearchParams({
				action: "handle_template_folder_action",
				nonce: wizard.nonce,
				action_type: actionType,
				items: JSON.stringify(items),
			}),
		});

		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}

		const data = await response.json();

		if (!data.success) {
			throw new Error(data.error || "Unknown error occurred");
		}

		return data;
	} catch (error) {
		handleFetchError(error);
		throw error;
	}
}

async function moveItems(items, newParentId) {
	if (!items.length || !newParentId) {
		console.error("Items or new parent ID is missing.");
		return;
	}

	const moveData = {
		folders: {},
		templates: {},
	};

	items.forEach((item) => {
		if (item.dataset.type === "folder") {
			moveData.folders[item.value] = newParentId;
		} else if (item.dataset.type === "template") {
			moveData.templates[item.value] = newParentId;
		}
	});

	try {
		const response = await performAction("move", moveData);

		if (!response.success) {
			throw new Error("Server returned an unsuccessful response");
		}

		const result = response.data;
		const movedTemplates = result?.templates?.moved || [];
		const movedFolders = result?.folders?.moved || [];
		const totalMoved = movedTemplates.length + movedFolders.length;

		if (totalMoved > 0) {
			items.forEach((item) => {
				if (
					(item.dataset.type === "template" &&
						movedTemplates.includes(Number(item.value))) ||
					(item.dataset.type === "folder" && movedFolders.includes(Number(item.value)))
				) {
					removeItemFromUi(item.value, item.dataset.type);
				}
			});
			const folderUrl = `${wizard.site_url}/templates/?folder_id=${newParentId}`;
			showSuccessToast(
				`${totalMoved} items moved successfully. <a href='${folderUrl}'>Go to folder</a>`,
				10000,
				true
			);
			return true;
		} else {
			throw new Error("No items were moved");
		}
	} catch (error) {
		console.error("Error in moveItems:", error);
		showErrorToast(`Error moving items: ${error.message}`);
		return false;
	}
}

async function deleteItems(items) {
	if (!items.length) {
		console.error("No items to delete.");
		return;
	}

	const result = await showDeleteConfirm(items);
	if (result.isConfirmed) {
		const deleteData = {
			folders: [],
			templates: [],
		};

		items.forEach((item) => {
			if (item.dataset.type === "folder") {
				deleteData.folders.push(item.value);
			} else if (item.dataset.type === "template") {
				deleteData.templates.push(item.value);
			}
		});

		try {
			const deleteResult = await performAction("delete", deleteData);
			const result = deleteResult.data;
			const deletedTemplates = result?.templates?.deleted || [];
			const deletedFolders = result?.folders?.deleted || [];
			const totalDeleted = deletedTemplates.length + deletedFolders.length;

			if (totalDeleted > 0) {
				items.forEach((item) => {
					if ((item.dataset.type === "template" && deletedTemplates.includes(Number(item.value))) || 
						(item.dataset.type === "folder" && deletedFolders.includes(Number(item.value)))) {
						removeItemFromUi(item.value, item.dataset.type);
					}
				});

				let successMessage = `Successfully deleted ${totalDeleted} item(s).`;
				if (deletedTemplates.length > 0 && !deletedFolders.length) {
					successMessage = `Successfully deleted ${deletedTemplates.length} template(s). <a href="${wizard.site_url}/templates?folder_id=trash">View trash.</a>`;
				} else if (deletedFolders.length > 0 && !deletedTemplates.length) {
					successMessage = `Successfully deleted ${deletedFolders.length} folder(s).`;
				} else if (deletedTemplates.length > 0 && deletedFolders.length > 0) {
					successMessage = `Successfully deleted ${deletedTemplates.length} template(s) and ${deletedFolders.length} folder(s). <a href="${wizard.site_url}/templates?folder_id=trash">View template trash.</a>`;
				}
				showSuccessToast(successMessage, 10000, true);
			} else {
				throw new Error("Failed to delete items");
			}
		} catch (error) {
			showErrorToast(`Error deleting items: ${error.message}`);
		}
	}
}

function showDeleteConfirm(items, forever = false) {
	const folderCount = items.filter((item) => item.dataset.type === "folder").length;
	const templateCount = items.filter((item) => item.dataset.type === "template").length;

	let html = "";
	if (folderCount > 0) {
		html += `${folderCount} folder(s) will be <strong>permanently</strong> deleted. `;
		html += "Any templates in these folders will be moved to the folders' parent folders.<br>";
	}
	if (templateCount > 0) {
		if (forever) {
			html += `${templateCount} template(s) will be <strong>permanently</strong> deleted. `;
		} else {
			html += `${templateCount} template(s) will be moved to the trash.`;
		}
	}

	return Swal.fire({
		title: "Delete Items?",
		html: html,
		icon: "warning",
		showCancelButton: true,
		confirmButtonText: "Delete",
	});
}

function showRestoreConfirm() {
	return Swal.fire({
		title: "Undelete?",
		text: "This will restore the selected template(s).",
		icon: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		confirmButtonText: "Yes, restore them!",
	});
}

function removeItemFromUi(itemId, type) {
	const row = document.getElementById(`${type}-${itemId}`);
	if (row) {
		highlightAndRemove(row);
		setTimeout(() => {
			templateTableAPI.handleEmptyState();
		}, 500);
	}
}

async function handleMoveItems(items) {
	try {
		const result = await selectFolder("Select destination folder");
		if (!result.value) {
			console.log("Folder selection was cancelled");
			return;
		}

		const destinationFolderId = result.value;
		await moveItems(items, destinationFolderId);
		
	} catch (error) {
		console.error("Error in move operation:", error);
		showErrorToast(`Failed to move items: ${error.message}`);
	}
}
