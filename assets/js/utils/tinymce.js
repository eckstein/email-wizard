import tinymce from "tinymce/tinymce";
import "tinymce/icons/default";
import "tinymce/models/dom";

// Set the base URL for TinyMCE resources
tinymce.baseURL = "/tinymce";

import { rename_single_folder } from "../components/file-explorer/folders";
import { addEventListenerIfExists } from "./functions";

// Function to suppress passive event listener warnings
function suppressPassiveWarnings() {
	const originalAddEventListener = EventTarget.prototype.addEventListener;
	EventTarget.prototype.addEventListener = function (type, listener, options) {
		if (type === "touchstart" || type === "touchmove" || type === "wheel") {
			originalAddEventListener.call(this, type, listener, { passive: true, ...options });
		} else {
			originalAddEventListener.call(this, type, listener, options);
		}
	};
}

// Customizable TinyMCE initialization function
function initTinyMCE(options = {}) {
	const defaultOptions = {
		inline: true,
		menubar: false,
		toolbar: false,
		skins: false,
		theme: false,
		ui_mode: "split",
		suppressPassiveWarnings: false,
		setup: function (editor) {
			// Default setup function
		},
	};

	const mergedOptions = { ...defaultOptions, ...options };

	if (mergedOptions.suppressPassiveWarnings) {
		suppressPassiveWarnings();
	}

	return tinymce.init(mergedOptions);
}

// Specific initialization for editable folder titles
export function init_editable_folder_titles() {
	initTinyMCE({
		selector: ".editable.folder-title",
		setup: function (editor) {
			editor.on("change", function () {
				var editableTitle = editor.getElement();
				var folderId = editableTitle.getAttribute("data-folder-id");
				var folderTitle = editor.getContent();
				rename_single_folder(folderId, folderTitle);
			});
		},
		suppressPassiveWarnings: true, // Set to true if you want to suppress warnings for this instance
	});
}

// Init folders on document ready
addEventListenerIfExists("document", "DOMContentLoaded", () => {
	init_editable_folder_titles();
});
