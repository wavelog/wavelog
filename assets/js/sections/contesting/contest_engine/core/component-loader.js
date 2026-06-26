export class ComponentManager {
	constructor(windowManager, layoutConfig = null) {
		this.windowManager = windowManager;
		this.components = new Map();
		this.layoutConfig = layoutConfig || {};
		this.initializeComponents();
	}

	/**
	 * Convert percentage-based layout to pixels
	 * @param {Object} layout - Layout with percentage values (0-100)
	 * @returns {Object} Layout with pixel values
	 */
	convertLayoutToPixels(layout) {
		if (!layout || Object.keys(layout).length === 0) {
			return {};
		}

		const workspace = this.windowManager.workspace;
		const rect = workspace.getBoundingClientRect();

		return {
			x: layout.x !== undefined ? Math.round((layout.x / 100) * rect.width) : undefined,
			y: layout.y !== undefined ? Math.round((layout.y / 100) * rect.height) : undefined,
			width: layout.width !== undefined ? Math.round((layout.width / 100) * rect.width) : undefined,
			height: layout.height !== undefined ? Math.round((layout.height / 100) * rect.height) : undefined,
		};
	}

	// Load all components on page
	initializeComponents() {
		// Load existing components from DOM
		this.loadExistingComponents();
	}

	// Load existing components from DOM (legacy support)
	loadExistingComponents() {
		const windowComponents = document.querySelectorAll('.window-component');

		windowComponents.forEach(componentEl => {
			let componentId = componentEl.getAttribute('data-component');
			if (!componentId) return;

			// Get HTML parts
			const header = componentEl.querySelector('.window-header');
			const body = componentEl.querySelector('.window-body');
			const title = componentEl.querySelector('.window-title')?.textContent || componentId;

			// Parse component config from data attribute
			let config = {};
			const configStr = componentEl.getAttribute('data-config');
			if (configStr) {
				try {
					config = JSON.parse(configStr);
				} catch (e) {
					console.warn(`ComponentManager: Could not parse config for ${componentId}`, e);
				}
			}

			// Get window size from config or use defaults
			const defaultSize = config.default_size || { width: 500, height: 400 };
			const minSize = config.min_size || { width: 300, height: 200 };

			// Get layout from configuration and convert percentages to pixels
			const layoutPercentage = this.layoutConfig[componentId] || {};
			const layout = this.convertLayoutToPixels(layoutPercentage);

			if (header && body) {
				// Create a window
				const windowEl = this.windowManager.createWindow({
					id: componentId,
					title: title,
					x: layout.x ?? (50 + this.components.size * 30),
					y: layout.y ?? (50 + this.components.size * 30),
					width: layout.width ?? defaultSize.width ?? 500,
					height: layout.height ?? defaultSize.height ?? 400,
					minWidth: minSize.width ?? 300,
					minHeight: minSize.height ?? 200,
					content: body.innerHTML
				});

				// Event listener for Close button
				const closeBtn = windowEl.querySelector('[data-action="close"]');
				if (closeBtn) {
					closeBtn.onclick = (e) => {
						e.stopPropagation();
						this.windowManager.closeWindow(componentId);
					};
				}

				this.components.set(componentId, {
					element: componentEl,
					windowElement: windowEl
				});
			}

			// Hide original element (only needed for loading)
			componentEl.remove(); // Remove completely instead of just hiding
		});
	}
}