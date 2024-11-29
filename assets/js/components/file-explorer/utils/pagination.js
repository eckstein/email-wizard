export function initPaginationControls() {
    const perPageSelect = document.querySelector('.wizard-per-page-select');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', handlePerPageChange);
    }
}

function handlePerPageChange(event) {
    const perPage = event.target.value;
    const currentUrl = new URL(window.location.href);
    
    // Update URL parameters
    currentUrl.searchParams.set('per_page', perPage);
    currentUrl.searchParams.delete('paged'); // Reset to first page when changing per_page
    
    // Navigate to new URL
    window.location.href = currentUrl.toString();
} 