import { handleCreateFolder, handleEditFolderTitle } from "../handlers/folders.js";
import {
	handleRestoreTemplate,
	handleDeleteForever,
	handleDuplicateTemplate,
	initTemplateActions
} from "../handlers/templates.js";
import {
	setupBulkActions,
	handleMoveSelected,
	handleDeleteSelected,
	handleRestoreSelected,
	handleDeleteSelectedForever
} from "../handlers/bulk-actions.js";
import { addEventListenerIfExists, showErrorToast } from "../../../utils/functions.js";
import { handleNewTeam } from "../../teams/teams-actions.js";
import { initSearch } from "../utils/search.js";
import { initPaginationControls } from '../utils/pagination.js';
import { moveItems, deleteItems, handleMoveItems } from "./common.js";

export { initFileExplorer };

function initFileExplorer() {
	setupEventListeners();
	setupBulkActions();
	initTemplateActions();
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


