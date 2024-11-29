import Swal from "sweetalert2";
import { addEventListenerIfExists } from "../../utils/functions.js";
import {
	highlightElement,
	handleHTTPResponse,
	showErrorToast,
	showSuccessToast,
} from "../../utils/functions.js";

import {
	removeItemFromUi
} from "./common.js";

import { templateTableAPI } from "./template-table-api.js";

import { initFileExplorer } from "./file-explorer-init.js";

export {
	createSingleTemplate,
	duplicateSingleTemplate,
	getTemplateRowHtml,
	addTemplateToTable,
	restoreTemplates,
	deleteTemplatesForever
};

document.addEventListener("DOMContentLoaded", () => {
	initTemplateActions();
});

function initTemplateActions() {
	addEventListenerIfExists(".new-template", "click", (event) => {
		event.preventDefault();
		Swal.fire({
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
				createSingleTemplate(templateName, wizard.current_folder_id)
					.then((template_id) => {
						const permalink = `${wizard.site_url}?p=${template_id}`;
						Swal.fire({
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
								
								getTemplateRowHtml(template_id)
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
	});
}

async function createSingleTemplate(templateName, folderId) {
	if (!templateName || !folderId) {
		console.error("Template name or folder ID is missing.");
		return;
	}
	const response = await fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "create_new_template",
			nonce: wizard.nonce,
			template_name: templateName,
			folder_id: folderId,
		}),
	});
	const data = await response.json();
	if (data.success && data.data) {
		return data.data;
	} else {
		throw new Error("Failed to create template");
	}
}

function duplicateSingleTemplate(templateId) {
	fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "duplicate_wizard_template",
			nonce: wizard.nonce,
			template_id: templateId,
		}),
	})
		.then((response) => {
			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}
			return response.json();
		})
		.then((data) => {
			if (data.success) {
				Swal.fire({
					title: "Template Duplicated",
					text: "The template was successfully duplicated.",
					icon: "success",
				}).then(() => {
					location.reload();
				});
			} else {
				throw new Error(data.data);
			}
		})
		.catch((error) => {
			console.error("Error duplicating template:", error);
			Swal.fire("Error!", "An error occurred while duplicating the template.", "error");
		});
}

function restoreTemplates(templates) {
	if (!templates || !templates.length) {
		return;
	}
	return fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "restore_trashed_templates",
			nonce: wizard.nonce,
			template_ids: JSON.stringify(templates),
		}),
	})
		.then((response) => {
			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}
			return response.json();
		})
		.then((data) => {
			if (data.success) {
				showSuccessToast("Templates restored successfully");
				templates.forEach((templateId) => {
					removeItemFromUi(templateId, "template");
				});
			} else {
				throw new Error(data.data);
			}
		});
}

function deleteTemplatesForever(templates) {
	if (!templates || !templates.length) {
		return;
	}
	return fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "delete_templates_forever",
			nonce: wizard.nonce,
			template_ids: JSON.stringify(templates),
		}),
	})
		.then((response) => {
			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}
			return response.json();
		})
		.then((data) => {
			if (data.success) {
				showSuccessToast("Templates permanently deleted successfully");
				templates.forEach((templateId) => {
					removeItemFromUi(templateId, "template");
				});
			} else {
				throw new Error(data.data);
				showErrorToast("Error deleting templates");
			}
		});
}

async function getTemplateRowHtml(templateId) {
	const urlParams = new URLSearchParams(window.location.search);
	const args = {};

	const response = await fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "generate_template_table_part",
			nonce: wizard.nonce,
			part: "template_row",
			current_folder: wizard.current_folder_id,
			user_id: wizard.current_user_id,
			item_id: templateId,
			args: JSON.stringify(args),
		}),
	});
	return handleHTTPResponse(response);
}

function addTemplateToTable(html) {
	let templatesTable = document.querySelector(".wizard-folders-table tbody.templates");
	
	if (!templatesTable) {
		const table = document.querySelector(".wizard-folders-table");
		templatesTable = document.createElement('tbody');
		templatesTable.className = 'templates';
		table.appendChild(templatesTable);
	}

	templateTableAPI.removeEmptyState();
	
	templatesTable.insertAdjacentHTML("beforeend", html.trim());
	const newRow = templatesTable.lastElementChild;
	
	if (newRow && newRow.tagName === "TR") {
		initFileExplorer();
		showSuccessToast("Template created successfully");
		setTimeout(() => {
			highlightElement("#" + newRow.id, 2000);
		}, 300);
	} else {
		throw new Error("Unexpected HTML structure returned");
	}
}
