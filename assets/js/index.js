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

import { initTeams } from "./components/teams";

document.addEventListener("DOMContentLoaded", () => {
	checkUrlParamsForToasts();
	initEditableFolderTitles();
	initTeams();
});
