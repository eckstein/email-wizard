import Swal from "sweetalert2";

export { showCreateTeamDialog };

function showCreateTeamDialog() {
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