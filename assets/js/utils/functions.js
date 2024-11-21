import Swal from "sweetalert2";
import { wiz_toast } from "./swal2";

export {
	addEventListenerIfExists,
	highlight_element,
	handleHTTPResponse,
	handleFetchError,
	show_success_toast,
	show_error_toast,
	highlight_and_remove,
	remove_url_parameter,
};

function addEventListenerIfExists(selector, eventType, callback, passive = false) {
	
	if (selector === "document") {
		document.addEventListener(eventType, callback, { passive: passive });
	} else {
		const elements = document.querySelectorAll(selector);
		elements.forEach((el) => el.addEventListener(eventType, callback));
	}
}

function highlight_element(selector, duration = 1000) {
	const element = document.querySelector(selector);

	if (element) {
		element.classList.add("highlight-fade");
		element.style.backgroundColor = "#f4f0dc";

		setTimeout(() => {
			element.style.backgroundColor = "";
		}, duration);

		setTimeout(() => {
			element.classList.remove("highlight-fade");
		}, duration + 1000);
	}
}

function highlight_and_remove(element) {
	element.style.transition = "background-color 0.5s, opacity 0.5s";
	element.style.backgroundColor = "#ffebee";
	
	
	setTimeout(() => {
		element.style.opacity = "0";
		setTimeout(() => {
			element.remove();
		}, 500);
	}, 1000);
}
function handleHTTPResponse(response) {
	if (!response.ok) {
		throw new Error(`HTTP error! status: ${response.status}`);
	}
	return response.json();
}

function handleFetchError(error) {
	console.error("Error:", error);
	Swal.fire("Error!", error.message, "error");
}

function show_success_toast(text = "Success!", timer = 10000, asHtml = false) {
	const args = {
		timer: timer,
		icon: "success",
	};
	if (asHtml) {
		args.html = text;
	} else {
		args.text = text;
	}
	wiz_toast(args);
}

function show_error_toast(text = "Error!", timer = 10000, asHtml = false) {
	const args = {
		timer: timer,
		icon: "error",
	};
	if (asHtml) {
		args.html = text;
	} else {
		args.text = text;
	}
	wiz_toast(args);
}

function remove_url_parameter(url, parameter) {
	//prefer to use l.search if you have a location/link object
	var urlparts = url.split("?");
	if (urlparts.length >= 2) {
		var prefix = encodeURIComponent(parameter) + "=";
		var pars = urlparts[1].split(/[&;]/g);

		//reverse iteration as may be destructive
		for (var i = pars.length; i-- > 0; ) {
			//idiom for string.startsWith
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}

		return urlparts[0] + (pars.length > 0 ? "?" + pars.join("&") : "");
	}
	return url;
}
