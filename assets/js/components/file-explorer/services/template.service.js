import { handleFetchError } from "../../../utils/http-utils";
import { showSuccessToast } from "../../../utils/notification-utils";

export {
    createTemplate,
    duplicateTemplate,
    deleteTemplate,
    moveTemplate,
    restoreTemplate,
    deleteTemplateForever
};

async function createTemplate(data) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "create_wizard_template",
                nonce: wizard.nonce,
                ...data
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        if (!responseData.success) {
            throw new Error(responseData.data || "Failed to create template");
        }

        showSuccessToast("Template created successfully");
        return responseData.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function duplicateTemplate(templateId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "duplicate_wizard_template",
                nonce: wizard.nonce,
                template_id: templateId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to duplicate template");
        }

        showSuccessToast("Template duplicated successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function deleteTemplate(templateId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "delete_wizard_template",
                nonce: wizard.nonce,
                template_id: templateId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to delete template");
        }

        showSuccessToast("Template moved to trash");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function moveTemplate(templateId, destinationFolderId) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "move_wizard_template",
                nonce: wizard.nonce,
                template_id: templateId,
                destination_folder_id: destinationFolderId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to move template");
        }

        showSuccessToast("Template moved successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function restoreTemplate(templateIds) {
    if (!Array.isArray(templateIds)) {
        templateIds = [templateIds];
    }

    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "restore_trashed_templates",
                nonce: wizard.nonce,
                template_ids: JSON.stringify(templateIds),
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to restore template");
        }

        showSuccessToast("Template(s) restored successfully");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

async function deleteTemplateForever(templateIds) {
    if (!Array.isArray(templateIds)) {
        templateIds = [templateIds];
    }

    try {
        const response = await fetch(wizard.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "delete_templates_forever",
                nonce: wizard.nonce,
                template_ids: JSON.stringify(templateIds),
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || "Failed to permanently delete template");
        }

        showSuccessToast("Template(s) permanently deleted");
        return data.data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
} 