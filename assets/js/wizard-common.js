jQuery(function ($) {
	
	// Wizard dropdown
	$(".wizard-dropdown").on('click', toggleWizardDropdown);
	$(document).on('click', hideWizardDropdown);
	$(".wizard-dropdown-panel").on('click', preventWizardDropdownClose);

	// Wizard tabs
	$(".wizard-tabs-list li").on('click', switchWizardTab);

	// Wizard Toast (with swal2)
	// To use: wizToast.fire({ icon: 'success', title: 'A message!' });
	 const wizToast = Swal.mixin({
		toast: true,
		position: "bottom-end",
		showConfirmButton: false,
		animation: false,
		timer: 3000,
		timerProgressBar: true,
		didOpen: (toast) => {
			toast.onmouseenter = Swal.stopTimer;
			toast.onmouseleave = Swal.resumeTimer;
		},
	});

	// User login popup
	// if (wizard.current_user.ID === 0) {
		
	// }


});


