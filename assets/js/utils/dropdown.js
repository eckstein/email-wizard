export { initializeWizardDropdowns };

function initializeWizardDropdowns() {
	document.addEventListener("DOMContentLoaded", () => {
		const dropdowns = document.querySelectorAll(".wizard-dropdown");
		dropdowns.forEach((dropdown) => {
			dropdown.addEventListener("click", toggleWizardDropdown);
		});

		document.addEventListener("click", hideAllDropdowns);

		// Initialize all dropdowns to be closed
		const dropdownPanels = document.querySelectorAll(".wizard-dropdown-panel");
		dropdownPanels.forEach((panel) => {
			panel.style.display = "none";
			panel.setAttribute("data-open", "false");
		});
	});
}

function toggleWizardDropdown(event) {
	// Check if the click is on an actionable menu item
	if (event.target.closest(".dropdown-action")) {
		// Don't prevent default or stop propagation for actionable items
		return;
	}

	event.stopPropagation();

	if (this.classList.contains("disabled")) {
		return;
	}

	const dropdown = this.querySelector(".wizard-dropdown-panel");
	const isOpen = dropdown.getAttribute("data-open") === "true";

	// Close all other dropdowns
	closeAllDropdownsExcept(dropdown);

	// Toggle current dropdown
	if (isOpen) {
		closeDropdown(dropdown);
	} else {
		openDropdown(dropdown);
	}

	// Adjust dropdown position if necessary
	adjustDropdownPosition(dropdown);
}

function openDropdown(dropdown) {
	dropdown.style.display = "block";
	dropdown.setAttribute("data-open", "true");
}

function closeDropdown(dropdown) {
	dropdown.style.display = "none";
	dropdown.setAttribute("data-open", "false");
}

function closeAllDropdownsExcept(exceptDropdown) {
	document.querySelectorAll(".wizard-dropdown-panel").forEach((panel) => {
		if (panel !== exceptDropdown) {
			closeDropdown(panel);
		}
	});
}

function adjustDropdownPosition(dropdown) {
	const spaceFromRight =
		window.innerWidth -
		(dropdown.parentElement.offsetLeft + dropdown.parentElement.offsetWidth);
	const requiredSpace = 300;

	dropdown.style.left = "";
	dropdown.style.right = "";

	if (spaceFromRight < requiredSpace) {
		dropdown.style.left = "auto";
		dropdown.style.right = "0";
	}
}

function hideAllDropdowns(event) {
	if (!event.target.closest(".wizard-dropdown") || event.target.closest(".dropdown-action")) {
		document.querySelectorAll(".wizard-dropdown-panel").forEach((panel) => {
			closeDropdown(panel);
		});
	}
}

// You can add this function if you need to handle menu item clicks
function handleMenuItemClick(event) {
	console.log("Menu item clicked:", event.target.textContent);
	// Add your menu item click handling logic here
}

// Initialize the dropdowns
initializeWizardDropdowns();
