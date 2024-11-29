import { highlightElement, showSuccessToast } from "../../../utils/functions.js";
import { initFileExplorer } from "../core/index.js";
import { templateTableAPI } from "../services/template-table-api.js";

export { addFolderToTable };

function addFolderToTable(htmlData) {
    const folderTable = document.querySelector(".wizard-folders-table tbody.subfolders");
    
    templateTableAPI.removeEmptyState();
    
    folderTable.insertAdjacentHTML("beforeend", htmlData.trim());
    const newRow = folderTable.lastElementChild;

    if (newRow && newRow.tagName === "TR") {
        initFileExplorer();
        showSuccessToast("Folder created successfully");
        setTimeout(() => {
            highlightElement("#" + newRow.id, 2000);
        }, 300);
    } else {
        throw new Error("Unexpected HTML structure returned");
    }
} 