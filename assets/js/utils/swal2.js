import Swal from "sweetalert2";

import { show_success_toast, remove_url_parameter } from "./functions.js";

export { wiz_toast, checkUrlParamsForToasts };

function wiz_toast(args) {
	if (!args.text && !args.html) {
        throw new Error("Message text is required for wiz_toast");
    }

	args.toast = true;
	args.customClass = {
		popup: "wiz-toast",
	};
	args.position = args.position || "bottom-end";
	args.showConfirmButton = false;
	args.animation = false;
	args.timer = args.timer || 3000;
	args.timerProgressBar = true;
	args.icon = args.icon || "success";
	args.didOpen = (toast) => {
		toast.onmouseenter = Swal.stopTimer;
		toast.onmouseleave = Swal.resumeTimer;
	};

    return Swal.fire(args);
}

// Detect URLs that have specific query params to auto-show toasts on load
export function checkUrlParamsForToasts() {
	const urlParams = new URLSearchParams(window.location.search);
	const toast_triggers = ["team_switched"];

	toast_triggers.forEach((trigger) => {
		if (urlParams.has(trigger)) {
			switch (trigger) {
				case "team_switched":
					show_success_toast(`Switched to team "${wizard.active_team_name}" successfully`);
					break;
				// Add more cases as needed for future triggers
			}
			// Remove the URL parameter from the URL
			const newUrl = remove_url_parameter(window.location.href, trigger);
			window.history.replaceState({}, "", newUrl);
		}
	});
}

// Call this when page loads
document.addEventListener("DOMContentLoaded", checkUrlParamsForToasts);