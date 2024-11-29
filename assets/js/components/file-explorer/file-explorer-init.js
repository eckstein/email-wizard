import { createNewWizardFolder, openFolderTitleEditor, selectFolder } from "./folders.js";
import {
	duplicateSingleTemplate,
	restoreTemplates,
	deleteTemplatesForever,
} from "./templates.js";
import { moveItems, deleteItems, showDeleteConfirm, showRestoreConfirm } from "./common.js";
import {
	addEventListenerIfExists,
	showErrorToast,
	showSuccessToast,
} from "../../utils/functions.js";

import { handleNewTeam } from "../teams/teams-actions.js";
import { initSearch } from "./search.js";
import { initPaginationControls } from './pagination.js';

export { initFileExplorer };

function initFileExplorer() {
	setupEventListeners();
	setupBulkActions();
	initSearch();
	initPaginationControls();
}

document.addEventListener("DOMContentLoaded", () => {
	initFileExplorer();
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
	createNewWizardFolder(wizard.current_folder_id);
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
		showErrorToast(`Cannot delete ${itemType}: ID is missing`);
		return;
	}

	deleteItems([{ value: itemId, dataset: { type: itemType } }]);
}

function handleRestoreTemplate(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		showErrorToast("Template ID is missing");
		return;
	}
	handleTemplateRestore([templateId]);
}

function handleDeleteForever(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		showErrorToast("Template ID is missing");
		return;
	}
	handleTemplateDeleteForever([{ value: templateId, dataset: { type: 'template' } }]);
}

function handleMoveSelected() {
	const selectedItems = getSelectedItems();
	if (selectedItems.length > 0) {
		handleMoveItems(selectedItems);
	} else {
		showErrorToast("No items selected for moving");
	}
}

function handleDeleteSelected() {
	const selectedItems = getSelectedItems();
	if (selectedItems.length > 0) {
		deleteItems(selectedItems);
	} else {
		showErrorToast("No items selected for deletion");
	}
}

function handleRestoreSelected() {
	const templates = getSelectedItems();
	if (templates.length > 0) {
		const templateIds = templates.map((item) => item.value);
		handleTemplateRestore(templateIds);
	} else {
		showErrorToast("No items selected for restoration");
	}
}

function handleDeleteSelectedForever() {
	const templates = getSelectedItems();
	if (templates.length > 0) {
		handleTemplateDeleteForever(templates);
	}
}

function handleDuplicateTemplate(event) {
	const templateId = event.target.dataset.templateId;
	if (!templateId) {
		console.error("Template ID is missing.");
		return;
	}
	duplicateSingleTemplate(templateId);
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
	openFolderTitleEditor(folderId, existingName);
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

async function handleTemplateRestore(templateIds) {
	try {
		const result = await showRestoreConfirm(templateIds);
		if (result.isConfirmed) {
			await restoreTemplates(templateIds);
		}
	} catch (error) {
		showErrorToast(`Error restoring templates: ${error.message}`);
	}
}

async function handleTemplateDeleteForever(templates) {
	try {
		const templateIds = templates.map(t => t.value);
		const result = await showDeleteConfirm(templates, true);
		if (result.isConfirmed) {
			await deleteTemplatesForever(templateIds);
		}
	} catch (error) {
		showErrorToast(`Error deleting templates: ${error.message}`);
	}
}


