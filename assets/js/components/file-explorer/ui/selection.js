export { getSelectedItems, updateBulkActionsState };

function getSelectedItems() {
    return Array.from(document.querySelectorAll(".wizard-table-bulk-check input:checked")).map(
        (input) => ({
            value: input.value,
            dataset: { type: input.dataset.type },
        })
    );
}

function updateBulkActionsState(isChecked) {
    const bulkActions = document.getElementById("bulk-actions");
    const bulkActionButtons = bulkActions.querySelectorAll("button");

    if (isChecked) {
        bulkActions.classList.remove("disabled");
        bulkActionButtons.forEach((button) => (button.disabled = false));
    } else {
        const checkedInputs = document.querySelectorAll(".wizard-table-bulk-check > input:checked");
        if (checkedInputs.length === 0) {
            bulkActions.classList.add("disabled");
            bulkActionButtons.forEach((button) => (button.disabled = true));
        }
    }
} 