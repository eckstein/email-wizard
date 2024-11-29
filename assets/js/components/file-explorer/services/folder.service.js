import { handleFetchError, handleHTTPResponse } from "../../../utils/http-utils";
import { showSuccessToast, showErrorToast } from "../../../utils/notification-utils";

export {
    createFolder,
    renameFolder,
    deleteFolder,
    moveFolder,
    getUserFolders,
    getFolderRowHtml
};

async function createFolder(parentFolderId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "create_wizard_folder",
                nonce: wizard.nonce,
                parent_id: parentFolderId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to create folder");
        }

        showSuccessToast("Folder created successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function renameFolder(folderId, newName) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "rename_wizard_folder",
                nonce: wizard.nonce,
                folder_id: folderId,
                new_name: newName,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to rename folder");
        }

        showSuccessToast("Folder renamed successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function deleteFolder(folderId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "delete_wizard_folder",
                nonce: wizard.nonce,
                folder_id: folderId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to delete folder");
        }

        showSuccessToast("Folder deleted successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function moveFolder(folderId, destinationFolderId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "move_wizard_folder",
                nonce: wizard.nonce,
                folder_id: folderId,
                destination_folder_id: destinationFolderId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to move folder");
        }

        showSuccessToast("Folder moved successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function getUserFolders(exclude = []) {
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

async function getFolderRowHtml(parentFolderId, newFolderId) {
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