import { showErrorToast } from "../../../utils/notification-utils";
import { handleFetchError } from "../../../utils/http-utils";
import { highlightElement } from "../../../utils/dom-utils";
import { createFolder, getFolderRowHtml, renameFolder } from "../services/folder.service.js";
import { showCreateFolderDialog, openFolderTitleEditor } from "../ui/folder-dialogs.js";
import { addFolderToTable } from "../ui/folder-ui.js";

export {
    handleCreateFolder,
    handleEditFolderTitle,
    createNewWizardFolder
};

function createNewWizardFolder(parentFolderId = "root") {
    showCreateFolderDialog()
        .then((result) => {
            if (!result.isConfirmed) {
                throw new Error("User cancelled");
            }
            const folderName = result.value.folderName;
            return createFolder(parentFolderId, folderName);
        })
        .then((newFolderId) => {
            if (newFolderId) {
                return getFolderRowHtml(parentFolderId, newFolderId);
            }
            throw new Error("No folder ID returned");
        })
        .then((data) => {
            if (data.success && data.data) {
                addFolderToTable(data.data.html);
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

function handleCreateFolder() {
    createNewWizardFolder(wizard.current_folder_id);
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
    openFolderTitleEditor(folderId, existingName)
        .then((result) => {
            if (result.isConfirmed) {
                const newTitle = result.value.folderTitle;
                return renameFolder(folderId, newTitle).then(() => ({ newTitle }));
            }
        })
        .then((data) => {
            if (data) {
                const folderTitleElement = document.querySelector(`tr[data-id="${folderId}"] .wizard-table-folder-name-link`);
                if (folderTitleElement) {
                    folderTitleElement.textContent = data.newTitle;
                    highlightElement(`tr[data-id="${folderId}"] .wizard-table-template-name`, 2000);
                }
            }
        })
        .catch((error) => {
            if (error.message !== "User cancelled") {
                handleFetchError(error);
            }
        });
} 