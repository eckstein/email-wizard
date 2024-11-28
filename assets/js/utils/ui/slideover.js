export { init_slideover };

function init_slideover() {
	const slideOverTrigger = document.querySelectorAll(".wizard-slideover-trigger");
	const slideoverPanel = document.querySelector(".wizard-slideover");

	// Add click handlers for triggers
	slideOverTrigger.forEach((trigger) => {
		trigger.addEventListener("click", (e) => {
			e.stopPropagation();
			document.querySelector("body").classList.toggle("overflow-hidden");
			slideoverPanel.classList.toggle("show");
		});
	});

	// Single document click handler outside the loop
	document.addEventListener("click", (event) => {
		if (
			!event.target.closest(".wizard-slideover") &&
			!event.target.closest(".wizard-slideover-trigger")
		) {
			document.querySelector("body").classList.remove("overflow-hidden");
			slideoverPanel.classList.remove("show");
		}
	});

	// Add click handler for close button
	const closeButton = document.querySelector(".slideover-close");
	closeButton.addEventListener("click", () => {
		document.querySelector("body").classList.remove("overflow-hidden");
		slideoverPanel.classList.remove("show");
	});
}

document.addEventListener("DOMContentLoaded", init_slideover);
