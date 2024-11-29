import { showSuccessToast, handleFetchError } from "../../../utils/functions.js";
import { createTeam, switchTeam } from "../services/team.service.js";
import { showCreateTeamDialog } from "../ui/team-dialogs.js";

export {
    handleNewTeam,
    handleTeamSwitch
};

function handleTeamSwitch(event) {
    const teamId = event.target.dataset.teamId;
    if (!teamId) {
        console.error("Team ID is missing");
        return;
    }

    switchTeam(teamId)
        .then(() => {
            window.location.href = window.location.href.split("?")[0] + "?team_switched=" + teamId;
        })
        .catch(handleFetchError);
}

function handleNewTeam() {
    showCreateTeamDialog()
        .then((result) => {
            if (!result.isConfirmed) {
                throw new Error("User cancelled");
            }
            return createTeam(wizard.current_user_id, result.value.teamName);
        })
        .then((newTeamId) => {
            if (!newTeamId) {
                throw new Error("No team ID returned");
            }
            showSuccessToast("Team created successfully");
            window.location.reload();
        })
        .catch((error) => {
            if (error.message !== "User cancelled") {
                handleFetchError(error);
            }
        });
} 