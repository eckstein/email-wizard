// template-table-api.js

export const templateTableAPI = {
	

	async fetchTablePart(part, itemId = null, additionalArgs = {}) {
		try {
			const args = {
				page: additionalArgs.page,
				per_page: additionalArgs.per_page,
				orderby: additionalArgs.orderby,
				order: additionalArgs.order,
				search_term: additionalArgs.search_term,
				folder_ids: additionalArgs.folder_ids,
				show_row_breadcrumb: additionalArgs.show_row_breadcrumb,
			};

			// Clean undefined values
			Object.keys(args).forEach(key => args[key] === undefined && delete args[key]);

			const response = await fetch(wizard.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'generate_template_table_part',
					nonce: wizard.nonce,
					user_id: wizard.current_user_id,
					current_folder: wizard.current_folder_id,
					part: part,
					...(itemId && { item_id: itemId }),
					args: JSON.stringify(args)
				})
			});

			const data = await response.json();
			
			if (!data.success) {
				throw new Error(data.data || 'Failed to fetch table part');
			}

			return {
				success: true,
				data: data.data
			};
		} catch (error) {
			console.error('Error fetching table part:', error);
			return {
				success: false,
				error: error.message
			};
		}
	},

	async handleEmptyState() {
		const subfoldersBody = document.querySelector('.wizard-folders-table tbody.subfolders');
		const templatesBody = document.querySelector('.wizard-folders-table tbody.templates');
		const noItemsMessage = '<tr class="no-results-message"><td colspan="5">No items here</td></tr>';

		// Remove existing no-results messages
		document.querySelectorAll('.no-results-message').forEach(el => el.closest('tr')?.remove());

		// Check if both bodies are empty
		const isSubfoldersEmpty = !subfoldersBody?.querySelector('tr:not(.no-results-message)');
		const isTemplatesEmpty = !templatesBody?.querySelector('tr:not(.no-results-message)');

		if (isSubfoldersEmpty && isTemplatesEmpty) {
			// Add message to templates body if it exists, otherwise to subfolders
			const targetBody = templatesBody || subfoldersBody;
			if (targetBody) {
				targetBody.innerHTML = noItemsMessage;
			}
		}
	},

	// Method to remove the no-items message if it exists
	removeEmptyState() {
		document.querySelectorAll('.no-results-message').forEach(el => el.closest('tr')?.remove());
	},

	initializeComponents() {
		// Re-initialize any necessary components or event listeners
		if (typeof init_file_explorer === 'function') {
			init_file_explorer();
		}
	},

	// Utility methods
	highlightElement(selector, duration = 2000) {
		setTimeout(() => {
			const element = document.querySelector(selector);
			if (element) {
				element.classList.add("highlighted");
				setTimeout(() => {
					element.classList.remove("highlighted");
				}, duration);
			}
		}, 300);
	},

	showToast(message, type = "success") {
		if (typeof show_success_toast === "function" && type === "success") {
			show_success_toast(message);
		}
		if (typeof show_error_toast === "function" && type === "error") {
				show_error_toast(message);
		}
	}
};
