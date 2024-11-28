export function initSearch() {
	const searchForm = document.querySelector(".wizard-search-form");
	if (!searchForm) return;

	// Handle form submission
	searchForm.addEventListener("submit", (e) => {
		e.preventDefault();

		const searchInput = document.querySelector(".wizard-search-input");
		const searchTerm = searchInput.value.trim();

		// Get current URL parameters
		const urlParams = new URLSearchParams(window.location.search);

		// Remove page parameter when searching
		urlParams.delete("page");

		// Update or remove search parameter
		if (searchTerm) {
			urlParams.set("s", searchTerm);
		} else {
			urlParams.delete("s");
		}

		// Construct new URL
		const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
		window.location.href = newUrl;
	});
}
