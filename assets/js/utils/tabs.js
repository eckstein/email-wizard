export { switch_wizard_tab };

// Utility function for tabbed interface
function switch_wizard_tab() {
	const tabId = this.getAttribute("data-tab");

	document.querySelectorAll(".wizard-tabs-list li").forEach((tab) => tab.classList.remove("active"));
	document.querySelectorAll(".wizard-tab-content").forEach((content) => content.classList.remove("active"));

	this.classList.add("active");
	document.querySelector(`.wizard-tab-content[data-content='${tabId}']`).classList.add("active");
}
// Initialize event listeners
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(".wizard-tabs-list li").forEach(tab => {
        tab.addEventListener('click', switch_wizard_tab);
    });
});