

// Function to save template form data
export async function saveTemplateFormData(formData) {
	try {
		const response = await fetch(wizard.ajaxurl, {
			method: "POST",
			body: new URLSearchParams({
				action: "your_action_name",
				security: wizard.nonce,
				...Object.fromEntries(formData),
			}),
		});

		if (!response.ok) {
			throw new Error("Network response was not ok");
		}

		const data = await response.json();
		// Handle the response
		console.log("Success:", data);
		return data; // Return the data in case the caller needs it
	} catch (error) {
		console.error("Error:", error);
		throw error; // Re-throw the error so the caller can handle it if needed
	}
}

// Event listener for form submission
document.addEventListener("DOMContentLoaded", () => {
	const form = document.getElementById("template-data-form");
	if (form) {
		form.addEventListener("submit", function (e) {
			e.preventDefault();
			const formData = new FormData(this);
			saveTemplateFormData(formData);
		});
	}
});
