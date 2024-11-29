import { addEventListenerIfExists } from "../../utils/functions.js";
import { handleNewTeam, handleTeamSwitch } from "./handlers/team-actions.js";

export function initTeams() {
    // Initialize team switching
    document.querySelectorAll(".switch-team-trigger").forEach((trigger) => {
        trigger.addEventListener("click", handleTeamSwitch);
    });

    // Initialize new team creation
    addEventListenerIfExists(".new-team", "click", handleNewTeam);
} 