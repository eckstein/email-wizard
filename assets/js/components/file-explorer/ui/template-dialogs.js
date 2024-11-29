import Swal from "sweetalert2";
import { showErrorToast } from "../../../utils/notification-utils";
import { createTemplate } from "../services/template.service.js";
import { getTemplateRowHtml, addTemplateToTable } from "./template-ui.js";
import { templateTableAPI } from "../services/template-table-api.js";

export { showCreateTemplateDialog };

function showCreateTemplateDialog() {
    return Swal.fire({
        title: "Enter Template Name",
        input: "text",
        inputPlaceholder: "Template Name",
        showCancelButton: true,
        confirmButtonText: "Create",
        showLoaderOnConfirm: true,
        preConfirm: (templateName) => {
            if (!templateName) {
                Swal.showValidationMessage("Please enter a template name.");
            }
            return templateName;
        },
        allowOutsideClick: () => !Swal.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            const templateName = result.value;
            createTemplate({ template_name: templateName, folder_id: wizard.current_folder_id })
                .then((templateId) => {
                    const permalink = `${wizard.site_url}?p=${templateId}`;
                    return Swal.fire({
                        title: "Template Created",
                        text: `What's your next move?`,
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: "Go to Template",
                        cancelButtonText: "Stay here",
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading(),
                        preConfirm: () => {
                            return new Promise((resolve) => {
                                window.location.href = permalink;
                                resolve();
                            });
                        },
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            templateTableAPI.removeEmptyState();
                            
                            getTemplateRowHtml(templateId)
                                .then(data => {
                                    if (data.success && data.data) {
                                        addTemplateToTable(data.data.html);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error getting template row:', error);
                                    showErrorToast('Failed to update template list');
                                });
                        }
                    });
                })
                .catch((error) => {
                    console.error("Error creating template:", error);
                    Swal.fire("Error", "Failed to create template", "error");
                });
        }
    });
} 