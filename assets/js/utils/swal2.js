import Swal from "sweetalert2";

export { wiz_toast };

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
