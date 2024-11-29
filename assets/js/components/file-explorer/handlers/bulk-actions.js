import { showErrorToast } from "../../../utils/notification-utils";
import { addEventListenerIfExists } from "../../../utils/dom-utils";
import { moveItems, deleteItems, handleMoveItems } from "../core/common.js";
import { handleTemplateRestore, handleTemplateDeleteForever } from "./templates.js";
import { getSelectedItems, updateBulkActionsState } from "../ui/selection.js";

export {
    setupBulkActions,
    handleBulkCheckChange,
    handleBulkCheckAllChange,
    handleMoveSelected,
    handleDeleteSelected,
    handleRestoreSelected,
    handleDeleteSelectedForever
};

function setupBulkActions() {
    addEventListenerIfExists(".wizard-table-bulk-check > input", "change", handleBulkCheckChange);
    addEventListenerIfExists(
        ".wizard-table-bulk-check-all",
        "change",
        handleBulkCheckAllChange
    );
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