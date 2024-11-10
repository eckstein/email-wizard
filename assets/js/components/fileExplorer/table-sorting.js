export { sort_templates };

import { handleHTTPResponse, handleFetchError } from "../../utils/functions.js";

import { init_file_explorer } from "./fileExplorer-init";


let isSorting = false;
function sort_templates(sortBy, sortOrder) {
    if (isSorting) return;
	isSorting = true;
	const urlParams = new URLSearchParams(window.location.search);
	const currentFolder = urlParams.get("folder_id") || "root";

	// Update URL parameters
	urlParams.set("sortBy", sortBy);
	urlParams.set("sort", sortOrder);
	window.history.replaceState({}, "", `${window.location.pathname}?${urlParams.toString()}`);

	const args = {
		sortBy: sortBy,
		sort: sortOrder,
	};

	// Fetch the updated templates part
	fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "generate_template_table_part",
			nonce: wizard.nonce,
			part: "templates",
			current_folder: currentFolder,
			user_id: wizard.current_user_id,
			args: JSON.stringify(args),
		}),
	})
		.then(handleHTTPResponse)
		.then((data) => {
			if (data.success && data.data) {
				updateTemplatesTable(data.data.html);
				updateSortingIndicators(sortBy, sortOrder);
			} else {
				throw new Error(data.data || "Failed to update templates");
			}
		})
		.catch(handleFetchError)
		.finally(() => {
			isSorting = false;
		});;
}

function updateTemplatesTable(htmlData) {
	const templatesTable = document.querySelector("tbody.templates");
	if (templatesTable) {
		templatesTable.innerHTML = htmlData.trim();
	} else {
		console.error("Templates table body not found");
	}
}

function updateSortingIndicators(sortBy, sortOrder) {
	const headers = document.querySelectorAll(".wizard-table-col-header.sortable");
	headers.forEach((header) => {
		const sortingIndicator = header.querySelector(".sorting-indicator");
		if (header.getAttribute("data-sort-by") === sortBy) {
			header.setAttribute("data-sorted", "true");
			header.setAttribute("data-sort", sortOrder);
			sortingIndicator.classList.add("active");
			sortingIndicator.innerHTML =
				sortOrder === "ASC"
					? '<i class="fa-solid fa-sort-up"></i>'
					: '<i class="fa-solid fa-sort-down"></i>';
		} else {
			header.setAttribute("data-sorted", "false");
			header.removeAttribute("data-sort");
			sortingIndicator.classList.remove("active");
			sortingIndicator.innerHTML = '<i class="fa-solid fa-sort"></i>';
		}
	});
}