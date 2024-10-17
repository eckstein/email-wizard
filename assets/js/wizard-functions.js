// Utility function for dropdown menu
function toggleWizardDropdown(event) {
  event.stopPropagation();

  var $dropdown = jQuery(this).find(".wizard-dropdown-panel");
  var spaceFromRight = jQuery(window).width() - ($dropdown.parent().offset().left + $dropdown.parent().outerWidth());
  var requiredSpace = 300;

  $dropdown.css({ left: "", right: "" });

  if (spaceFromRight < requiredSpace) {
	$dropdown.css({ left: "auto", right: 0 });
  }

  jQuery(".wizard-dropdown-panel").not($dropdown).slideUp("fast");
  $dropdown.stop(true, true).slideToggle("fast");
}

// Utility function for clicking outside the dropdown
function hideWizardDropdown() {
  jQuery(".wizard-dropdown-panel").slideUp("fast");
}

// Utility function for preventing clicks within the dropdown from closing it
function preventWizardDropdownClose(event) {
  event.stopPropagation();
}

// Utility function for tabbed interface
function switchWizardTab() {
  var tab_id = jQuery(this).attr("data-tab");

  jQuery(".wizard-tabs-list li").removeClass("active");
  jQuery(".wizard-tab-content").removeClass("active");

  jQuery(this).addClass("active");
  jQuery(".wizard-tab-content").removeClass('active');
  jQuery(".wizard-tab-content[data-content='" + tab_id + "']").addClass('active');
}




// Highlights something with a timer to unhighlight it later
function highlightElement(selector, duration = 1000) {
	const element = document.querySelector(selector);

	if (element) {
		// Add the highlight-fade class to trigger the fade animation
		element.classList.add("highlight-fade");

		// Set the background color to yellow
		element.style.backgroundColor = "#f4f0dc";

		// Remove the background color after the fade animation is complete
		setTimeout(() => {
			element.style.backgroundColor = "";
		}, duration);

		// Remove the highlight-fade class after a slight delay to allow the fade-out animation
		setTimeout(() => {
			element.classList.remove("highlight-fade");
		}, duration + 1000);
	}
}


