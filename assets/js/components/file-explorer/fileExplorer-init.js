import { create_new_wizard_folder, open_folder_title_editor, select_folder } from "./folders.js";
import {
	duplicate_single_template,
	restore_templates,
	delete_templates_forever,
} from "./templates.js";
import { move_items, delete_items, show_delete_confirm, show_restore_confirm } from "./common.js";
import {
	addEventListenerIfExists,
	show_error_toast,
	show_success_toast,
} from "../../utils/functions.js";

import { handleNewTeam } from "../teams/teams-actions.js";
import { initSearch } from "./search.js";
import { initPaginationControls } from './pagination.js';

export function init_file_explorer() {
	setupEventListeners();
	setupBulkActions();
	initSearch();
	initPaginationControls();
}

// Initialize file explorer when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
	init_file_explorer();
	initSearch();
});

function setupEventListeners() {
	addEventListenerIfExists(".create-folder", "click", handleCreateFolder);
	document.addEventListener("click", handleGlobalClick);
	addEventListenerIfExists(".edit-folder-title", "click", handleEditFolderTitle);
}

function setupBulkActions() {
	addEventListenerIfExists(".wizard-table-bulk-check > input", "change", handleBulkCheckChange);
	addEventListenerIfExists(
		".wizard-table-bulk-check-all",
		"change",
		handleBulkCheckAllChange
	);
}



function handleCreateFolder() {
	create_new_wizard_folder(wizard.current_folder_id);
}

async function handleMoveItems(items) {
	try {
		const result = await select_folder("Select destination folder");
		if (!result.value) {
			console.log("Folder selection was cancelled");
			return;
		}

		const destinationFolderId = result.value;
		await move_items(items, destinationFolderId);
		
	} catch (error) {
		console.error("Error in move operation:", error);
		show_error_toast(`Failed to move items: ${error.message}`);
	}
}

function handleGlobalClick(event) {
	const handlers = {
		".move-folder, .move-template": handleMoveItem,
		".delete-folder, .delete-template": handleDeleteItem,
		".restore-template": handleRestoreTemplate,
		".delete-forever": handleDeleteForever,
		"#move-selected": handleMoveSelected,
		"#delete-selected": handleDeleteSelected,
		"#restore-selected": handleRestoreSelected,
		"#delete-selected-forever": handleDeleteSelectedForever,
		".duplicate-template": handleDuplicateTemplate,
		".new-team": handleNewTeam,
	};

	for (const [selector, handler] of Object.entries(handlers)) {
		if (event.target.matches(selector)) {
			handler(event);
			break;
		}
	}
}

function handleMoveItem(event) {
	const itemId = event.target.dataset.folderId || event.target.dataset.templateId;
	const itemType = event.target.dataset.folderId ? "folder" : "template";

	if (!itemId) {
		console.error(`${itemType} ID is missing.`);
		return;
	}

	handleMoveItems([{ value: itemId, dataset: { type: itemType } }]);
}

function handleDeleteItem(event) {
	const itemId = event.target.dataset.folderId || event.target.dataset.templateId;
	const itemType = event.target.dataset.folderId ? "folder" : "template";

	if (!itemId) {
		console.error(`${itemType} ID is missing.`);
		show_error_toast(`Cannot delete ${itemType}: ID is missing`);
		return;
	}

	delete_items([{ value: itemId, dataset: { type: itemType } }]);
}

function handleRestoreTemplate(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		show_error_toast("Template ID is missing");
		return;
	}
	showRestoreConfirmation([templateId]);
}

function handleDeleteForever(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		show_error_toast("Template ID is missing");
		return;
	}
	showDeleteForeverConfirmation([event.target], [templateId]);
}

function handleMoveSelected() {
	const selectedItems = getSelectedItems();
	if (selectedItems.length > 0) {
		handleMoveItems(selectedItems);
	} else {
		show_error_toast("No items selected for moving");
	}
}

function handleDeleteSelected() {
	const selectedItems = getSelectedItems();
	if (selectedItems.length > 0) {
		delete_items(selectedItems);
	} else {
		show_error_toast("No items selected for deletion");
	}
}

function handleRestoreSelected() {
	const templates = getSelectedItems();
	if (templates.length > 0) {
		const templateIds = templates.map((item) => item.value);
		showRestoreConfirmation(templateIds);
	} else {
		show_error_toast("No items selected for restoration");
	}
}

function handleDeleteSelectedForever() {
	const templates = getSelectedItems();
	if (templates.length > 0) {
		const templateIds = templates.map((item) => item.value);
		showDeleteForeverConfirmation(templates, templateIds);
	}
}

function handleDuplicateTemplate(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		return;
	}
	duplicate_single_template(templateId);
}

function handleBulkCheckChange(event) {
	updateBulkActionsState(event.target.checked);
}

function handleBulkCheckAllChange(event) {
	const checkboxes = document.querySelectorAll(".wizard-table-bulk-check input:not([hidden])");
	checkboxes.forEach((input) => {
		input.checked = event.target.checked;
		input.dispatchEvent(new Event("change", { bubbles: true }));
	});
	updateBulkActionsState(event.target.checked && checkboxes.length > 0);
}

function handleEditFolderTitle(event) {
	const folderId = event.target.dataset.editable;
	const existingName = document.querySelector(
		`tr[data-id="${folderId}"] .wizard-table-folder-name-link`
	).textContent;
	if (!folderId) {
		console.error("Folder ID is missing.");
		return;
	}
	open_folder_title_editor(folderId, existingName);
}

function getSelectedItems() {
	return Array.from(document.querySelectorAll(".wizard-table-bulk-check input:checked")).map(
		(input) => ({
			value: input.value,
			dataset: { type: input.dataset.type },
		})
	);
}

function updateBulkActionsState(isChecked) {
	const bulkActions = document.getElementById("bulk-actions");
	const bulkActionButtons = bulkActions.querySelectorAll("button");

	if (isChecked) {
		bulkActions.classList.remove("disabled");
		bulkActionButtons.forEach((button) => (button.disabled = false));
	} else {
		const checkedInputs = document.querySelectorAll(".wizard-table-bulk-check > input:checked");
		if (checkedInputs.length === 0) {
			bulkActions.classList.add("disabled");
			bulkActionButtons.forEach((button) => (button.disabled = true));
		}
	}
}

function showRestoreConfirmation(templateIds) {
	show_restore_confirm(templateIds).then((result) => {
		if (result.isConfirmed) {
			restore_templates(templateIds);
		}
	});
}

function showDeleteForeverConfirmation(templates, templateIds) {
	show_delete_confirm(templates, true).then((result) => {
		if (result.isConfirmed) {
			delete_templates_forever(templateIds);
		}
	});
}


