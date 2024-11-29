import tinymce from "tinymce/tinymce";
import "tinymce/icons/default";
import "tinymce/models/dom";

tinymce.baseURL = "/tinymce";

import { handleEditFolderTitle } from "../components/file-explorer/handlers/folders.js";
import { addEventListenerIfExists } from "./functions.js";

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

export function initEditableFolderTitles() {
	initTinyMCE({
		selector: ".editable.folder-title",
		setup: function (editor) {
			editor.on("change", function () {
				var editableTitle = editor.getElement();
				var folderId = editableTitle.getAttribute("data-folder-id");
				handleEditFolderTitle({ target: { dataset: { editable: folderId } } });
			});
		},
		suppressPassiveWarnings: true,
	});
}

addEventListenerIfExists("document", "DOMContentLoaded", () => {
	initEditableFolderTitles();
});
