import Swal from "sweetalert2";
import {
	highlight_and_remove,
	show_success_toast,
	show_error_toast,
	handleFetchError,
} from "../../utils/functions";

export { move_items, delete_items, show_delete_confirm, show_restore_confirm, remove_item_from_ui };

async function perform_action(action_type, items) {
	if (!["move", "delete"].includes(action_type)) {
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
				action_type: action_type,
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

async function move_items(items, newParentId) {
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
		const response = await perform_action("move", moveData);
		console.log("Full response:", response);

		if (!response.success) {
			throw new Error("Server returned an unsuccessful response");
		}

		const result = response.data;
		console.log("Result data:", result);

		const movedTemplates = result?.templates?.moved || [];
		const movedFolders = result?.folders?.moved || [];

		console.log("Moved templates:", movedTemplates);
		console.log("Moved folders:", movedFolders);

		const totalMoved = movedTemplates.length + movedFolders.length;

		if (totalMoved > 0) {
			items.forEach((item) => {
				console.log("Checking item:", item);
				if (
					(item.dataset.type === "template" &&
						movedTemplates.includes(Number(item.value))) ||
					(item.dataset.type === "folder" && movedFolders.includes(Number(item.value)))
				) {
					remove_item_from_ui(item.value, item.dataset.type);
				} else {
					console.log("Item not found in moved items:", item);
				}
			});
			const folderUrl = `${wizard.site_url}/templates/?folder_id=${newParentId}`;
			show_success_toast(
				`${totalMoved} items moved successfully. <a href='${folderUrl}'>Go to folder</a>`,
				10000,
				true
			);
			return true;
		} else {
			throw new Error("No items were moved");
		}
	} catch (error) {
		console.error("Error in move_items:", error);
		show_error_toast(`Error moving items: ${error.message}`);
		return false;
	}
}

async function delete_items(items) {
	if (!items.length) {
		console.error("No items to delete.");
		return;
	}

	const result = await show_delete_confirm(items);
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
			const deleteResult = await perform_action("delete", deleteData);

			const result = deleteResult.data;

			const deletedTemplates = result?.templates?.deleted || [];
			const deletedFolders = result?.folders?.deleted || [];

			const totalDeleted = deletedTemplates.length + deletedFolders.length;
			if (totalDeleted > 0) {
				items.forEach((item) => {
					if (
						(item.dataset.type === "template" &&
							deletedTemplates.includes(Number(item.value))) ||
						(item.dataset.type === "folder" &&
							deletedFolders.includes(Number(item.value)))
					) {
						remove_item_from_ui(item.value, item.dataset.type);
					} else {
						console.log("Item not found in deleted items:", item);
					}
				});
				let successMessage = `Successfully deleted ${totalDeleted} item(s).`;
				if (deletedTemplates.length > 0 && !deletedFolders.length) {
					successMessage = `Successfully deleted ${deletedTemplates.length} template(s). <a href="${wizard.site_url}/templates?folder_id=trash">View trash.</a>`;
				} else if (deletedFolders.length > 0 && !deletedTemplates.length) {
					successMessage = `Successfully deleted ${deletedFolders.length} folder(s).`;
				} else if (deletedTemplates.length > 0 && deletedFolders.length > 0) {
					successMessage = `Successfully deleted ${deletedTemplates.length} template(s) and ${deletedFolders.length} folder(s).  <a href="${wizard.site_url}/templates?folder_id=trash">View template trash.</a>`;
				}
				show_success_toast(successMessage, 10000, true);
			} else {
				throw new Error("Failed to delete items");
			}
		} catch (error) {
			show_error_toast(`Error deleting items: ${error.message}`);
		}
	}
}

function show_delete_confirm(items, forever = false) {
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

function show_restore_confirm(templateIds) {
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

function remove_item_from_ui(itemId, itemType) {
	const itemElement = document.querySelector(`#${itemType}-${itemId}`);
	if (itemElement) {
		highlight_and_remove(itemElement);
	}
}
