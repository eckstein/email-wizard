import { init_file_explorer } from "./fileExplorer-init.js";

export { init_template_search };
let searchCleanup;
function init_template_search() {
	// If there's an existing search setup, clean it up first
	if (searchCleanup) {
		searchCleanup();
	}

	const searchInput = document.querySelector(".template-search");
	if (!searchInput) return;

	let currentController = null;
	let debounceTimer = null;

	// Flag to ensure we only add the event listener once
	let listenerAdded = false;

	async function handleSearch(term) {
		if (currentController) {
			currentController.abort();
		}
		currentController = new AbortController();

		if (!term) {
			await clearSearch();
			return;
		}

		removeSubfolderRows();
		updateSearchSummary("searching");

		try {
			const result = await searchTemplates(term, currentController.signal);
			if (result.success) {
				updateSearchResults(result, term);
			} else {
				handleNoResults(term);
			}
		} catch (error) {
			if (error.name === "AbortError") {
				//console.log("Search aborted as new search started");
				return;
			}
			console.error("Search error:", error);
			updateSearchSummary("error");
		}
	}

	function debouncedHandleSearch(e) {
		const term = e.target.value.trim();
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
		debounceTimer = setTimeout(() => handleSearch(term), 300);
	}

	function addSearchListener() {
		if (!listenerAdded) {
			searchInput.addEventListener("input", debouncedHandleSearch);
			listenerAdded = true;
		}
	}

	function removeSearchListener() {
		searchInput.removeEventListener("input", debouncedHandleSearch);
		listenerAdded = false;
	}

	// Add the listener when initializing
	searchInput.addEventListener("input", debouncedHandleSearch);

	async function searchTemplates(term, signal) {
		const currentFolderId = wizard.current_folder_id;
		const folderIds = [currentFolderId, ...wizard.recursive_subfolder_ids];
		return fetchSearchResults(term, currentFolderId, folderIds, signal);
	}

	function fetchSearchResults(term, currentFolderId, folderIds, signal) {
		const searchData = new FormData();
		searchData.append("action", "generate_template_table_part");
		searchData.append("nonce", wizard.nonce);
		searchData.append("part", "templates");
		searchData.append("current_folder", currentFolderId);
		searchData.append("user_id", wizard.current_user_id);
		searchData.append("search_term", term);
		searchData.append("folder_ids", JSON.stringify(folderIds));
		searchData.append("args", JSON.stringify({ show_row_breadcrumb: true }));

		return fetchWithTimeout(wizard.ajaxurl, { method: "POST", body: searchData, signal });
	}

	async function clearSearch() {
		updateSearchSummary("resetting");

		const data = new FormData();
		data.append("action", "generate_template_table_part");
		data.append("nonce", wizard.nonce);
		data.append("part", "body");
		data.append("current_folder", wizard.current_folder_id);
		data.append("user_id", wizard.current_user_id);

		try {
			const result = await fetchWithTimeout(wizard.ajaxurl, { method: "POST", body: data });
			if (result.success) {
				updateTableContent(result.data.html);
				updateSearchSummary("");
			} else {
				throw new Error(result.data);
			}
		} catch (error) {
			if (error.name === "AbortError") {
				// This is an expected error when the user starts a new search before the previous one finishes
				// We can either ignore it or log it if we want to track these occurrences
				//console.log("Search aborted as new search started");
				return; // Exit the function early
			}

			// For any other type of error, log it and update the UI
			console.error("Error resetting table:", error);
			updateSearchSummary("error");
		}
	}

	function handleNoResults(term) {
		updateTableContent("");
		updateSearchSummary(`no-results:${term}`);
		const noResultsMessage = createNoResultsMessage(term);
		document.querySelector(".templates").appendChild(noResultsMessage);
	}

	function updateSearchResults(result, term) {
		updateTableContent(result.data.html);
		updateSearchSummary(`results:${term}`);
	}

	function updateSearchSummary(status) {
		const searchStatusEl = document.querySelector("#template-search-active");
		const messages = {
			searching: '<i class="fa-solid fa-spin fa-spinner"></i>&nbsp;&nbsp;Searching...',
			resetting: '<i class="fa-solid fa-spin fa-spinner"></i>&nbsp;&nbsp;Resetting...',
			error: "An error occurred",
			"": "",
		};
		searchStatusEl.innerHTML = status.startsWith("results:")
			? `Search results for: <strong>${status.split(":")[1]}</strong>`
			: status.startsWith("no-results:")
			? `No results found for: ${status.split(":")[1]}`
			: messages[status] || status;
	}

	// Utility functions
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	}

	function removeSubfolderRows() {
		document.querySelector("tbody.subfolders")?.remove();
	}

	function updateTableContent(html) {
		const templatesContainer = document.querySelector(".templates");
		if (templatesContainer) {
			templatesContainer.innerHTML = html;
		} else {
			console.error("Could not find .templates container");
		}
	}

	function createNoResultsMessage(term) {
		const noResultsMessage = document.createElement("tr");
		noResultsMessage.className = "no-results-message";
		noResultsMessage.innerHTML = `<td colspan="3" class="no-search-results">No templates found matching "${term}". Please try a different search term.</td>`;
		return noResultsMessage;
	}


	async function fetchWithTimeout(resource, options = {}) {
		const { timeout = 8000 } = options;

		const timeoutId = setTimeout(() => {
			if (currentController) {
				currentController.abort();
			}
		}, timeout);

		try {
			const response = await fetch(resource, {
				...options,
				signal: currentController.signal,
			});
			clearTimeout(timeoutId);
			return await response.json();
		} catch (error) {
			clearTimeout(timeoutId);
			throw error;
		}
	}

	// Store the cleanup function
	searchCleanup = function cleanup() {
		searchInput.removeEventListener("input", debouncedHandleSearch);
		if (currentController) {
			currentController.abort();
		}
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
	};

	// Return the cleanup function in case it's needed externally
	return searchCleanup;
}
