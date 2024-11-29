import {
	toggleWizardDropdown,
	hideWizardDropdown,
	ddRepeaterRow,
	removeRepeaterRow,
	addEventListenerIfExists,
	highlightElement,
	wizToast,
	checkUrlParamsForToasts,
	switchWizardTab,
	initEditableFolderTitles,
	handleFetchError,
	handleHTTPResponse,
	showSuccessToast,
	showErrorToast,
	highlightAndRemove,
	initSlideover,
	removeUrlParameter,
} from "./utils";

document.addEventListener("DOMContentLoaded", () => {
	checkUrlParamsForToasts();
	initEditableFolderTitles();
});
