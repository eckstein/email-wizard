import {
	addEventListenerIfExists,
	highlightElement,
	highlightAndRemove
} from "./utils/dom-utils";

import {
	handleFetchError,
	handleHTTPResponse,
	removeUrlParameter
} from "./utils/http-utils";

import {
	showSuccessToast,
	showErrorToast
} from "./utils/notification-utils";

import {
	toggleWizardDropdown,
	hideWizardDropdown
} from "./utils/ui/dropdown";

import { switchWizardTab } from "./utils/ui/tabs";
import { initSlideover } from "./utils/ui/slideover";
import { initEditableFolderTitles } from "./utils/tinymce";
import { checkUrlParamsForToasts } from "./utils/ui/swal2";
import { ddRepeaterRow, removeRepeaterRow } from "./utils/forms";
import { initTeams } from "./components/teams";

document.addEventListener("DOMContentLoaded", () => {
	checkUrlParamsForToasts();
	initEditableFolderTitles();
	initTeams();
});
