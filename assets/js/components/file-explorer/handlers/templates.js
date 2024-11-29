import { showErrorToast } from "../../../utils/notification-utils";
import { addEventListenerIfExists } from "../../../utils/dom-utils";
import { showRestoreConfirm, showDeleteConfirm, removeItemFromUi } from "../core/common.js";
import { 
    restoreTemplate,
    deleteTemplateForever,
    duplicateTemplate
} from "../services/template.service.js";
import { showCreateTemplateDialog } from "../ui/template-dialogs.js";

export {
    handleTemplateRestore,
    handleTemplateDeleteForever,
    handleRestoreTemplate,
    handleDeleteForever,
    handleDuplicateTemplate,
    initTemplateActions
};

function initTemplateActions() {
    addEventListenerIfExists(".new-template", "click", (event) => {
        event.preventDefault();
        showCreateTemplateDialog();
    });
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

function handleDuplicateTemplate(event) {
    const templateId = event.target.dataset.templateId;
    if (!templateId) {
        console.error("Template ID is missing.");
        return;
    }
    duplicateTemplate(templateId);
}

async function handleTemplateRestore(templateIds) {
    try {
        const result = await showRestoreConfirm(templateIds);
        if (result.isConfirmed) {
            await restoreTemplate(templateIds);
            templateIds.forEach(templateId => {
                removeItemFromUi(templateId, 'template');
            });
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
            await deleteTemplateForever(templateIds);
            templateIds.forEach(templateId => {
                removeItemFromUi(templateId, 'template');
            });
        }
    } catch (error) {
        showErrorToast(`Error deleting templates: ${error.message}`);
    }
} 