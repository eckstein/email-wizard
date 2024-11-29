import Swal from "sweetalert2";

import { showSuccessToast, removeUrlParameter } from "../functions";

export { wizToast, checkUrlParamsForToasts };

function wizToast(args) {
	if (!args.text && !args.html) {
        throw new Error("Message text is required for wizToast");
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

function checkUrlParamsForToasts() {
	const urlParams = new URLSearchParams(window.location.search);
	const toastTriggers = ["team_switched"];

	toastTriggers.forEach((trigger) => {
		if (urlParams.has(trigger)) {
			switch (trigger) {
				case "team_switched":
					showSuccessToast(`Switched to team "${wizard.active_team_name}" successfully`);
					break;
			}
			const newUrl = removeUrlParameter(window.location.href, trigger);
			window.history.replaceState({}, "", newUrl);
		}
	});
}

document.addEventListener("DOMContentLoaded", checkUrlParamsForToasts);