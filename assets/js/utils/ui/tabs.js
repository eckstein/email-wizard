export { switch_wizard_tab };

// Utility function for tabbed interface
function switch_wizard_tab(event) {
	if (event) {
		event.preventDefault();
	}
	
	const tabId = this.getAttribute("data-tab");
	
	// Store active tab in URL
	const currentUrl = new URL(window.location.href);
	currentUrl.searchParams.set('tab', tabId);
	window.history.replaceState({}, '', currentUrl);

	document.querySelectorAll(".wizard-tabs-list li").forEach((tab) => tab.classList.remove("active"));
	document.querySelectorAll(".wizard-tab-content").forEach((content) => content.classList.remove("active"));

	this.classList.add("active");
	document.querySelector(`.wizard-tab-content[data-content='${tabId}']`).classList.add("active");
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', () => {
	// Get active tab from URL
	const urlParams = new URLSearchParams(window.location.search);
	const activeTab = urlParams.get('tab');

	// Find the tab to activate
	let tabToActivate;
	if (activeTab) {
		tabToActivate = document.querySelector(`.wizard-tabs-list li[data-tab='${activeTab}']`);
	}
	
	// If no tab in URL or tab not found, use first tab
	if (!tabToActivate) {
		tabToActivate = document.querySelector('.wizard-tabs-list li');
	}

	// Activate the tab if found
	if (tabToActivate) {
		switch_wizard_tab.call(tabToActivate);
	}

	// Add click handlers
	document.querySelectorAll(".wizard-tabs-list li").forEach(tab => {
		tab.addEventListener('click', switch_wizard_tab);
	});
});