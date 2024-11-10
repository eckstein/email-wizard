import Swal from "sweetalert2";
import { addEventListenerIfExists } from "../../utils/functions.js";
import {
	highlight_element,
	handleFetchError,
	handleHTTPResponse,
	show_error_toast,
	show_success_toast,
	highlight_and_remove,
} from "../../utils/functions.js";

import {
	move_single_item,
	delete_item_from_server,
	remove_item_from_ui,
	show_delete_item_confirm,
} from "./common.js";

export {
	create_single_template,
	duplicate_single_template,
	get_template_row_html,
	add_template_to_table,
	restore_templates,
	delete_templates_forever
};

document.addEventListener("DOMContentLoaded", () => {
	init_template_actions();
});

function init_template_actions() {
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
				create_single_template(templateName, wizard.current_folder_id)
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
									// This resolve will never be called because we're navigating away
									resolve();
								});
							},
						}).then((result) => {
							if (result.dismiss === Swal.DismissReason.cancel) {
								// User clicked "Stay here"
								//window.location.reload();
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

async function create_single_template(templateName, folderId) {
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
		return data.data; // returns the new template ID
	} else {
		throw new Error("Failed to create template");
	}
}

function duplicate_single_template(templateId) {
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

function restore_templates(templates) {
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
				show_success_toast("Templates restored successfully");
				// remove the deleted restored templates from the UI
				templates.forEach((templateId) => {
					remove_item_from_ui(templateId, "template");
				});
			} else {
				throw new Error(data.data);
			}
		});
}

function delete_templates_forever(templates) {
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
				show_success_toast("Templates permanantly deleted successfully");
				// remove the deleted templates from the UI
				templates.forEach((templateId) => {
					remove_item_from_ui(templateId, "template");
				});
			} else {
				throw new Error(data.data);
				show_error_toast("Error deleting templates");
			}
		});
}
		
	

function get_template_row_html(templateId) {
	const urlParams = new URLSearchParams(window.location.search);
	const args = {};

	return fetch(wizard.ajaxurl, {
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
	}).then(handleHTTPResponse);
}

function add_template_to_table(html) {
	let folderTable = document.querySelector(".wizard-folders-table tbody.templates");
	folderTable.insertAdjacentHTML("beforeend", html.trim());
	const newRow = folderTable.lastElementChild;
	if (newRow && newRow.tagName === "TR") {
		init_file_explorer();
		show_success_toast("Template created successfully");
		setTimeout(() => {
			highlight_element("#" + newRow.id, 2000);
		}, 300);
	} else {
		throw new Error("Unexpected HTML structure returned");
	}
}
