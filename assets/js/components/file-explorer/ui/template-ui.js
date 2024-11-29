import { handleHTTPResponse } from "../../../utils/http-utils";
import { highlightElement } from "../../../utils/dom-utils";
import { initFileExplorer } from "../core/index.js";
import { templateTableAPI } from "../services/template-table-api.js";

export { getTemplateRowHtml, addTemplateToTable };

async function getTemplateRowHtml(templateId) {
    const urlParams = new URLSearchParams(window.location.search);
    const args = {};

    const response = await fetch(wizard.ajaxurl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            action: "generate_template_table_part",
            nonce: wizard.nonce,
            part: "template_row",
            current_folder: wizard.current_folder_id,
            user_id: wizard.current_user_id,
            item_id: templateId,
            args: JSON.stringify(args),
        }),
    });
    return handleHTTPResponse(response);
}

function addTemplateToTable(html) {
    let templatesTable = document.querySelector(".wizard-folders-table tbody.templates");
    
    if (!templatesTable) {
        const table = document.querySelector(".wizard-folders-table");
        templatesTable = document.createElement("tbody");
        templatesTable.classList.add("templates");
        table.appendChild(templatesTable);
    }

    templateTableAPI.removeEmptyState();
    templatesTable.insertAdjacentHTML("beforeend", html.trim());
    
    const newRow = templatesTable.lastElementChild;
    if (newRow && newRow.tagName === "TR") {
        initFileExplorer();
        setTimeout(() => {
            highlightElement("#" + newRow.id, 2000);
        }, 300);
    } else {
        throw new Error("Unexpected HTML structure returned");
    }
} 