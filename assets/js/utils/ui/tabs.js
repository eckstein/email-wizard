export { switch_wizard_tab, initWizardTabs };

/**
 * Custom event dispatched before tab switch
 * @event wizard-tab-before-switch
 * @type {CustomEvent}
 * @property {Object} detail
 * @property {string} detail.fromTab - ID of the current tab
 * @property {string} detail.toTab - ID of the tab being switched to
 * @property {Function} detail.preventDefault - Call to prevent tab switch
 */

/**
 * Custom event dispatched after tab switch
 * @event wizard-tab-switched
 * @type {CustomEvent}
 * @property {Object} detail
 * @property {string} detail.tab - ID of the newly active tab
 */

/**
 * Switch to a specific tab
 * @param {Event} [event] - Click event if triggered by click
 * @param {Object} [options] - Switch options
 * @param {boolean} [options.updateUrl=true] - Whether to update URL
 * @param {boolean} [options.triggerEvents=true] - Whether to trigger events
 * @returns {Promise<void>}
 */
async function switch_wizard_tab(event, options = {}) {
	const defaults = {
		updateUrl: true,
		triggerEvents: true
	};
	const settings = { ...defaults, ...options };

	if (event) {
		event.preventDefault();
	}

	const tabElement = this instanceof Element ? this : document.querySelector(`.wizard-tabs-list li[data-tab='${this}']`);
	if (!tabElement) return;

	const tabId = tabElement.getAttribute("data-tab");
	const currentTab = document.querySelector(".wizard-tabs-list li.active");
	const currentTabId = currentTab?.getAttribute("data-tab");

	// Dispatch before-switch event
	if (settings.triggerEvents) {
		const beforeEvent = new CustomEvent("wizard-tab-before-switch", {
			detail: {
				fromTab: currentTabId,
				toTab: tabId,
				preventDefault: () => (settings.updateUrl = false)
			},
			cancelable: true
		});
		
		const allowed = tabElement.dispatchEvent(beforeEvent);
		if (!allowed) return;
	}

	// Update URL if needed
	if (settings.updateUrl) {
		const currentUrl = new URL(window.location.href);
		currentUrl.searchParams.set('tab', tabId);
		window.history.replaceState({}, '', currentUrl);
	}

	// Remove active states
	document.querySelectorAll(".wizard-tabs-list li").forEach((tab) => tab.classList.remove("active"));
	document.querySelectorAll(".wizard-tab-content").forEach((content) => content.classList.remove("active"));

	// Add active states
	tabElement.classList.add("active");
	const contentElement = document.querySelector(`.wizard-tab-content[data-content='${tabId}']`);
	
	if (contentElement) {
		// Show loading state if content needs to be loaded
		if (contentElement.dataset.dynamicLoad && !contentElement.dataset.loaded) {
			contentElement.classList.add("loading");
			
			try {
				const response = await fetch(`/api/wiz-ajax/`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'get_tab_content',
						tab: tabId,
						nonce: window.wizard?.nonce
					})
				});

				const data = await response.json();
				if (data.success) {
					contentElement.innerHTML = data.data.content;
					contentElement.dataset.loaded = 'true';
					
					// Initialize any JS in the new content
					if (window.wizard?.initTabContent) {
						window.wizard.initTabContent(contentElement);
					}
				}
			} catch (error) {
				console.error('Failed to load tab content:', error);
			} finally {
				contentElement.classList.remove("loading");
			}
		}

		contentElement.classList.add("active");
	}

	// Dispatch switched event
	if (settings.triggerEvents) {
		const switchedEvent = new CustomEvent("wizard-tab-switched", {
			detail: { tab: tabId }
		});
		tabElement.dispatchEvent(switchedEvent);
	}
}

/**
 * Initialize the tabbed interface
 * @param {string} [containerId] - Optional container ID to initialize specific instance
 */
function initWizardTabs(containerId) {
	const selector = containerId ? `#${containerId}` : '.wizard-tabs';
	const containers = document.querySelectorAll(selector);

	containers.forEach(container => {
		// Get active tab from URL
		const urlParams = new URLSearchParams(window.location.search);
		const activeTab = urlParams.get('tab');

		// Find the tab to activate
		let tabToActivate;
		if (activeTab) {
			tabToActivate = container.querySelector(`li[data-tab='${activeTab}']`);
		}
		
		// If no tab in URL or tab not found, use first tab
		if (!tabToActivate) {
			tabToActivate = container.querySelector('.wizard-tabs-list li');
		}

		// Activate the tab if found
		if (tabToActivate) {
			switch_wizard_tab.call(tabToActivate, null, { triggerEvents: false });
		}

		// Add click handlers
		container.querySelectorAll(".wizard-tabs-list li").forEach(tab => {
			tab.addEventListener('click', switch_wizard_tab);
		});
	});
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => initWizardTabs());
} else {
	initWizardTabs();
}