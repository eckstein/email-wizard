import { handleHTTPResponse } from "../../../utils/http-utils";
import { showSuccessToast } from "../../../utils/notification-utils";

export {
    createTeam,
    switchTeam
};

async function createTeam(userId, teamName) {
    const response = await fetch(wizard.ajaxurl, {
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
    });

    const data = await handleHTTPResponse(response);
    if (data.success) {
        return data.data.team_id;
    }
    throw new Error(data.data || "Failed to create team");
}

async function switchTeam(teamId) {
    const response = await fetch(wizard.ajaxurl, {
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
    });

    const data = await handleHTTPResponse(response);
    if (data.success) {
        return teamId;
    }
    throw new Error(data.data || "Failed to switch team");
} 