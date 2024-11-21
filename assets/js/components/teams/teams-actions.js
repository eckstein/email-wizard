import { show_success_toast, handleFetchError, handleHTTPResponse } from "../../utils/functions.js";
import Swal from "sweetalert2";

export {
	init_team_handler,
	handleNewTeam,
	show_create_team_dialog,
	createTeamRequest,
	switch_team,
};

init_team_handler();

function init_team_handler() {
	document.querySelectorAll(".switch-team-trigger").forEach((trigger) => {
		trigger.addEventListener("click", (event) => {
			const teamId = event.target.dataset.teamId;
			if (teamId) {
				switch_team(teamId);
			}
		});
	});
}
function handleNewTeam() {
	show_create_team_dialog()
		.then((result) => {
			if (!result.isConfirmed) {
				throw new Error("User cancelled");
			}
			return createTeamRequest(wizard.current_user_id, result.value.teamName);
		})
		.then((newTeamId) => {
			if (!newTeamId) {
				throw new Error("No team ID returned");
			}
			show_success_toast("Team created successfully");
			// Add logic here to refresh the teams list
			window.location.reload();
		})
		.catch((error) => {
			if (error.message !== "User cancelled") {
				handleFetchError(error);
			}
		});
}
function show_create_team_dialog() {
	return Swal.fire({
		title: "Create New Team",
		html: '<input id="swal-input1" class="swal2-input" placeholder="Enter team name">',
		focusConfirm: false,
		showCancelButton: true,
		confirmButtonText: "Create",
		cancelButtonText: "Cancel",
		allowEnterKey: true,
		allowEscapeKey: true,
		preConfirm: () => {
			const teamName = Swal.getPopup().querySelector("#swal-input1").value;
			if (!teamName) {
				Swal.showValidationMessage("Please enter a team name");
			}
			return { teamName: teamName };
		},
	});
}

function createTeamRequest(userId, teamName) {
	return fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "add_wizard_team",
			nonce: wizard.nonce,
			user_id: userId,
			team_name: teamName,
		}),
	})
		.then(handleHTTPResponse)
		.then((data) => {
			if (data.success) {
				return data.data.team_id;
			}
			throw new Error(data.data || "Failed to create team");
		});
}

function switch_team(teamId) {
	fetch(wizard.ajaxurl, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: new URLSearchParams({
			action: "switch_wizard_team",
			nonce: wizard.nonce,
			team_id: teamId,
			user_id: wizard.current_user_id,
		}),
	})
		.then(handleHTTPResponse)
		.then((data) => {
			if (data.success) {
				window.location.href =
					window.location.href.split("?")[0] + "?team_switched=" + teamId;
			} else {
				throw new Error(data.data || "Failed to switch team");
			}
		})
		.catch((error) => {
			handleFetchError(error);
		});
}
