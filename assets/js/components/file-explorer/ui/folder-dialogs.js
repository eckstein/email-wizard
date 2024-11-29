import Swal from "sweetalert2";
import NiceSelect from "nice-select2";
import { getUserFolders } from "../services/folder.service.js";
import { highlightElement } from "../../../utils/functions.js";

export {
    showCreateFolderDialog,
    openFolderTitleEditor,
    selectFolder
};

function showCreateFolderDialog() {
    return Swal.fire({
        title: "Create New Folder",
        html: '<input id="swal-input1" class="swal2-input" placeholder="Enter folder name">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Create",
        cancelButtonText: "Cancel",
        allowEnterKey: true,
        allowEscapeKey: true,
        preConfirm: () => {
            const folderName = Swal.getPopup().querySelector("#swal-input1").value;
            if (!folderName) {
                Swal.showValidationMessage("Please enter a folder name");
            }
            return { folderName: folderName };
        },
    });
}

function openFolderTitleEditor(folderId, existingName) {
    return Swal.fire({
        title: "Rename folder",
        html: `<input id="folder-title" class="swal2-input" placeholder="Enter new folder title" value="${existingName}">`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Rename",
        preConfirm: () => {
            const folderTitle = Swal.getPopup().querySelector("#folder-title").value;
            if (!folderTitle) {
                Swal.showValidationMessage("Please enter a folder title");
            }
            return { folderTitle: folderTitle };
        },
    });
}

async function selectFolder(title = "Select folder") {
    return new Promise(async (resolve, reject) => {
        try {
            const currentFolderId = wizard.current_folder_id;
            
            const foldersResponse = await getUserFolders([currentFolderId]);
            let availableFolders = foldersResponse.data || [];

            if (currentFolderId && currentFolderId !== "root") {
                availableFolders.unshift({
                    id: "root",
                    name: "Root",
                });
            }

            if (availableFolders.length > 0) {
                let folderOptions = availableFolders.map((folder) => 
                    `<option value="${folder.id}">${folder.name}</option>`
                );

                Swal.fire({
                    title: title,
                    html: `<select id="folder-select" class="swal2-input">${folderOptions.join("")}</select>`,
                    confirmButtonText: "Select",
                    showCancelButton: true,
                    customClass: {
                        container: "swal-with-folder-select",
                    },
                    preConfirm: () => {
                        return document.getElementById("folder-select").value;
                    },
                    didOpen: () => {
                        new NiceSelect(document.getElementById("folder-select"), {
                            searchable: true,
                        });
                    },
                })
                .then(resolve)
                .catch(reject);
            } else {
                reject(new Error("No available folders to select from"));
            }
        } catch (error) {
            reject(error);
        }
    });
} 