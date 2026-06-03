export class WindowManager {
	constructor(workspaceSelector = '#logger-workspace') {
		this.workspace = document.querySelector(workspaceSelector);
		this.windows = new Map();
		this.activeWindow = null;
		this.zIndexCounter = 1;

		this.dragState = null;
		this.resizeState = null;
		this.snapThreshold = 12; // px distance to snap

		this.setupEventListeners();
	}

	setupEventListeners() {
		document.addEventListener('mousemove', (e) => this.handleDrag(e));
		document.addEventListener('mouseup', (e) => this.handleDragEnd(e));
		document.addEventListener('mousemove', (e) => this.handleResize(e));
		document.addEventListener('mouseup', (e) => this.handleResizeEnd(e));
	}

	// Create window
	createWindow(config) {
		const id = config.id || 'window_' + Date.now();
		const windowEl = document.createElement('div');
		windowEl.className = 'window';
		windowEl.id = id;
		windowEl.style.left = (config.x || 50) + 'px';
		windowEl.style.top = (config.y || 50) + 'px';
		windowEl.style.width = (config.width || 400) + 'px';
		windowEl.style.height = (config.height || 300) + 'px';
		windowEl.style.display = 'flex'; // Ensure display is set

		// Store min size configuration
		windowEl.dataset.minWidth = config.minWidth || 300;
		windowEl.dataset.minHeight = config.minHeight || 200;

		// Header
		const header = document.createElement('div');
		header.className = 'window-header';

		const title = document.createElement('div');
		title.className = 'window-title';
		title.textContent = config.title || lang_window_default_title;

		const controls = document.createElement('div');
		controls.className = 'window-controls';

		// Close Button
		const closeBtn = document.createElement('button');
		closeBtn.className = 'window-btn close';
		closeBtn.innerHTML = '×';
		closeBtn.onclick = (e) => {
			e.stopPropagation();
			this.closeWindow(id);
		};

		// Append only close control
		controls.appendChild(closeBtn);

		header.appendChild(title);
		header.appendChild(controls);

		// Body
		const body = document.createElement('div');
		body.className = 'window-body';
		body.innerHTML = config.content || '';

		// Resize handles: 4 corners + 4 edges
		const handles = ['tl', 'tr', 'bl', 'br', 't', 'b', 'l', 'r'];
		handles.forEach(handle => {
			const el = document.createElement('div');
			el.className = `window-resize window-resize-${handle}`;
			windowEl.appendChild(el);
			el.addEventListener('mousedown', (e) => this.handleResizeStart(e, id, handle));
		});

		windowEl.appendChild(header);
		windowEl.appendChild(body);

		this.workspace.appendChild(windowEl);

		// Event Listeners
		header.addEventListener('mousedown', (e) => this.handleDragStart(e, id));
		windowEl.addEventListener('click', () => this.focusWindow(id));

		this.windows.set(id, {
			element: windowEl,
			config: config,
			title: config.title || lang_window_default_title
		});

		this.focusWindow(id);
		return windowEl;
	}

	// Drag Start
	handleDragStart(e, windowId) {
		const windowData = this.windows.get(windowId);
		if (!windowData) return;
		
		this.focusWindow(windowId);
		const rect = windowData.element.getBoundingClientRect();

		this.dragState = {
			windowId: windowId,
			startX: e.clientX,
			startY: e.clientY,
			offsetX: e.clientX - rect.left,
			offsetY: e.clientY - rect.top
		};
	}

	// Drag
	handleDrag(e) {
		if (!this.dragState) return;

		const windowData = this.windows.get(this.dragState.windowId);
		if (!windowData) return;

		let newX = e.clientX - this.dragState.offsetX;
		let newY = e.clientY - this.dragState.offsetY;

		// Apply snapping against other windows and workspace edges
		const rect = windowData.element.getBoundingClientRect();
		const snapped = this.applySnap(this.dragState.windowId, newX, newY, rect.width, rect.height);
		newX = snapped.x;
		newY = snapped.y;

		windowData.element.style.left = newX + 'px';
		windowData.element.style.top = newY + 'px';
	}

	// Drag End
	handleDragEnd() {
		this.dragState = null;
	}

	// Resize Start
	handleResizeStart(e, windowId, corner = 'br') {
		const windowData = this.windows.get(windowId);
		if (!windowData) return;
		
		this.focusWindow(windowId);
		const rect = windowData.element.getBoundingClientRect();

		this.resizeState = {
			windowId: windowId,
			corner: corner,
			startX: e.clientX,
			startY: e.clientY,
			startWidth: rect.width,
			startHeight: rect.height,
			startLeft: rect.left,
			startTop: rect.top
		};

		e.preventDefault();
	}

	// Resize
	handleResize(e) {
		if (!this.resizeState) return;

		const windowData = this.windows.get(this.resizeState.windowId);
		if (!windowData) return;

		const wsRect = this.workspace.getBoundingClientRect();
		const corner = this.resizeState.corner;

		let newWidth = this.resizeState.startWidth;
		let newHeight = this.resizeState.startHeight;
		let newLeft = parseInt(windowData.element.style.left) || this.resizeState.startLeft;
		let newTop = parseInt(windowData.element.style.top) || this.resizeState.startTop;

		// Get minimum sizes from window element's data attributes
		const minWidth = parseInt(windowData.element.dataset.minWidth) || 300;
		const minHeight = parseInt(windowData.element.dataset.minHeight) || 200;

		// Calculate deltas
		const deltaX = e.clientX - this.resizeState.startX;
		const deltaY = e.clientY - this.resizeState.startY;

		// Handle resize direction based on handle
		if (corner === 'br' || corner === 'bl' || corner === 'b') {
			newHeight = Math.max(minHeight, this.resizeState.startHeight + deltaY);
		}
		if (corner === 'tl' || corner === 'tr' || corner === 't') {
			const heightDelta = this.resizeState.startHeight - deltaY;
			if (heightDelta >= minHeight) {
				newHeight = heightDelta;
				newTop = this.resizeState.startTop + deltaY;
			}
		}

		if (corner === 'br' || corner === 'tr' || corner === 'r') {
			newWidth = Math.max(minWidth, this.resizeState.startWidth + deltaX);
		}
		if (corner === 'tl' || corner === 'bl' || corner === 'l') {
			const widthDelta = this.resizeState.startWidth - deltaX;
			if (widthDelta >= minWidth) {
				newWidth = widthDelta;
				newLeft = this.resizeState.startLeft + deltaX;
			}
		}

		// Apply snapping during resize
		const snapped = this.applySnapResize(this.resizeState.windowId, newLeft, newTop, newWidth, newHeight, corner);
		newLeft = snapped.x;
		newTop = snapped.y;
		newWidth = snapped.width;
		newHeight = snapped.height;

		// Apply viewport constraints
		// Ensure window stays within workspace bounds
		if (newLeft < 0) {
			newWidth = newLeft + newWidth; // preserve right edge
			newLeft = 0;
		}
		if (newTop < 0) {
			newHeight = newTop + newHeight; // preserve bottom edge
			newTop = 0;
		}
		if (newLeft + newWidth > wsRect.width) {
			newWidth = wsRect.width - newLeft;
		}
		if (newTop + newHeight > wsRect.height) {
			newHeight = wsRect.height - newTop;
		}

		// Ensure minimum dimensions are met after constraints
		newWidth = Math.max(minWidth, newWidth);
		newHeight = Math.max(minHeight, newHeight);

		windowData.element.style.width = newWidth + 'px';
		windowData.element.style.height = newHeight + 'px';
		windowData.element.style.left = newLeft + 'px';
		windowData.element.style.top = newTop + 'px';
	}

	// Resize End
	handleResizeEnd() {
		this.resizeState = null;
	}

	// Focus window (bring to front)
	focusWindow(windowId) {
		// Remove active class from all
		this.windows.forEach(data => data.element.classList.remove('active'));

		// Add to clicked window
		const windowData = this.windows.get(windowId);
		if (windowData) {
			windowData.element.style.zIndex = ++this.zIndexCounter;
			windowData.element.classList.add('active');
			this.activeWindow = windowId;
		}
	}



	// Close window
	closeWindow(windowId) {
		const windowData = this.windows.get(windowId);
		if (windowData) {
			windowData.element.style.display = 'none';
			windowData.isHidden = true;

			if (this.activeWindow === windowId) {
				this.activeWindow = null;
			}
		}
	}

	// Toggle window visibility (for Control Panel)
	toggleWindowVisibility(windowId) {
		const windowData = this.windows.get(windowId);
		if (windowData) {
			if (windowData.isHidden) {
				windowData.element.style.display = 'flex';
				windowData.isHidden = false;
				this.focusWindow(windowId);
			} else {
				windowData.element.style.display = 'none';
				windowData.isHidden = true;
			}
		}
	}

	// Get all windows (for Control Panel component list)
	getAllWindows() {
		const windows = [];
		this.windows.forEach((data, id) => {
			windows.push({
				id: id,
				title: data.title || lang_window_default_title,
				isHidden: data.isHidden || false
			});
		});
		return windows;
	}

	// Calculate snapping position relative to other windows and workspace
	applySnap(windowId, x, y, width, height) {
		const wsRect = this.workspace.getBoundingClientRect();
		let snappedX = x;
		let snappedY = y;

		// Snap to workspace edges
		if (Math.abs(snappedX - 0) <= this.snapThreshold) snappedX = 0;
		if (Math.abs(snappedY - 0) <= this.snapThreshold) snappedY = 0;
		if (Math.abs((wsRect.width) - (snappedX + width)) <= this.snapThreshold) {
			snappedX = wsRect.width - width;
		}
		if (Math.abs((wsRect.height) - (snappedY + height)) <= this.snapThreshold) {
			snappedY = wsRect.height - height;
		}

		// Snap to other windows
		this.windows.forEach((data, id) => {
			if (id === windowId) return;
			const r = data.element.getBoundingClientRect();
			const left = snappedX;
			const top = snappedY;
			const right = snappedX + width;
			const bottom = snappedY + height;

			// Horizontal snapping
			// snap left to other right
			if (Math.abs(left - r.right) <= this.snapThreshold && this.verticalOverlap(top, bottom, r.top, r.bottom)) {
				snappedX = r.right;
			}
			// snap right to other left
			if (Math.abs(right - r.left) <= this.snapThreshold && this.verticalOverlap(top, bottom, r.top, r.bottom)) {
				snappedX = r.left - width;
			}

			// Vertical snapping
			// snap top to other bottom
			if (Math.abs(top - r.bottom) <= this.snapThreshold && this.horizontalOverlap(left, right, r.left, r.right)) {
				snappedY = r.bottom;
			}
			// snap bottom to other top
			if (Math.abs(bottom - r.top) <= this.snapThreshold && this.horizontalOverlap(left, right, r.left, r.right)) {
				snappedY = r.top - height;
			}
		});

		// Keep inside workspace bounds
		snappedX = Math.max(0, Math.min(snappedX, wsRect.width - width));
		snappedY = Math.max(0, Math.min(snappedY, wsRect.height - height));

		return { x: snappedX, y: snappedY };
	}

	// Calculate snapping during resize operations
	applySnapResize(windowId, x, y, width, height, corner) {
		const wsRect = this.workspace.getBoundingClientRect();
		let snappedX = x;
		let snappedY = y;
		let snappedWidth = width;
		let snappedHeight = height;

		const left = snappedX;
		const top = snappedY;
		const right = snappedX + snappedWidth;
		const bottom = snappedY + snappedHeight;

		// Snap to workspace edges
		// Left edge
		if (Math.abs(left - 0) <= this.snapThreshold && (corner === 'tl' || corner === 'bl' || corner === 'l')) {
			const diff = snappedX - 0;
			snappedX = 0;
			snappedWidth += diff;
		}
		// Top edge
		if (Math.abs(top - 0) <= this.snapThreshold && (corner === 'tl' || corner === 'tr' || corner === 't')) {
			const diff = snappedY - 0;
			snappedY = 0;
			snappedHeight += diff;
		}
		// Right edge
		if (Math.abs(wsRect.width - right) <= this.snapThreshold && (corner === 'tr' || corner === 'br' || corner === 'r')) {
			snappedWidth = wsRect.width - snappedX;
		}
		// Bottom edge
		if (Math.abs(wsRect.height - bottom) <= this.snapThreshold && (corner === 'bl' || corner === 'br' || corner === 'b')) {
			snappedHeight = wsRect.height - snappedY;
		}

		// Snap to other windows
		this.windows.forEach((data, id) => {
			if (id === windowId) return;
			const r = data.element.getBoundingClientRect();

			const currentLeft = snappedX;
			const currentTop = snappedY;
			const currentRight = snappedX + snappedWidth;
			const currentBottom = snappedY + snappedHeight;

			// Horizontal snapping
			// Left edge to other right edge
			if ((corner === 'tl' || corner === 'bl' || corner === 'l') && Math.abs(currentLeft - r.right) <= this.snapThreshold &&
				this.verticalOverlap(currentTop, currentBottom, r.top, r.bottom)) {
				const diff = snappedX - r.right;
				snappedX = r.right;
				snappedWidth += diff;
			}
			// Right edge to other left edge
			if ((corner === 'tr' || corner === 'br' || corner === 'r') && Math.abs(currentRight - r.left) <= this.snapThreshold &&
				this.verticalOverlap(currentTop, currentBottom, r.top, r.bottom)) {
				snappedWidth = r.left - snappedX;
			}

			// Vertical snapping
			// Top edge to other bottom edge
			if ((corner === 'tl' || corner === 'tr' || corner === 't') && Math.abs(currentTop - r.bottom) <= this.snapThreshold &&
				this.horizontalOverlap(currentLeft, currentRight, r.left, r.right)) {
				const diff = snappedY - r.bottom;
				snappedY = r.bottom;
				snappedHeight += diff;
			}
			// Bottom edge to other top edge
			if ((corner === 'bl' || corner === 'br' || corner === 'b') && Math.abs(currentBottom - r.top) <= this.snapThreshold &&
				this.horizontalOverlap(currentLeft, currentRight, r.left, r.right)) {
				snappedHeight = r.top - snappedY;
			}
		});

		return { x: snappedX, y: snappedY, width: snappedWidth, height: snappedHeight };
	}

	// Helper: check overlap on Y-axis
	verticalOverlap(topA, bottomA, topB, bottomB) {
		return !(bottomA < topB || topA > bottomB);
	}

	// Helper: check overlap on X-axis
	horizontalOverlap(leftA, rightA, leftB, rightB) {
		return !(rightA < leftB || leftA > rightB);
	}

	// Save current layout to get positions and sizes
	saveLayout() {
		const layout = {};
		this.windows.forEach((data, id) => {
			const el = data.element;
			layout[id] = {
				x: parseInt(el.style.left) || 0,
				y: parseInt(el.style.top) || 0,
				width: parseInt(el.style.width) || 400,
				height: parseInt(el.style.height) || 300,
				isHidden: data.isHidden || false,
				zIndex: parseInt(el.style.zIndex) || 0
			};
		});
		return layout;
	}

	// Load layout and apply positions and sizes
	loadLayout(layout) {
		if (!layout || typeof layout !== 'object') {
			console.warn('WindowManager: Invalid layout data');
			return;
		}

		this.windows.forEach((data, id) => {
			if (layout[id]) {
				const config = layout[id];
				const el = data.element;

				el.style.left = (config.x || 0) + 'px';
				el.style.top = (config.y || 0) + 'px';
				el.style.width = (config.width || 400) + 'px';
				el.style.height = (config.height || 300) + 'px';
				el.style.zIndex = config.zIndex || 1;

				if (config.isHidden) {
					el.style.display = 'none';
					data.isHidden = true;
				} else {
					el.style.display = 'flex';
					data.isHidden = false;
				}
			}
		});
	}

	// Reset all windows to their original configuration
	resetLayout() {
		this.windows.forEach((data, id) => {
			const config = data.config;
			const el = data.element;

			el.style.left = (config.x || 50) + 'px';
			el.style.top = (config.y || 50) + 'px';
			el.style.width = (config.width || 400) + 'px';
			el.style.height = (config.height || 300) + 'px';
			el.style.display = 'flex';
			data.isHidden = false;
		});
	}

	// Show Bootstrap Toast
	showToast(title, text, type = 'bg-success text-white', delay = 3000) {
		/*
		Examples:
		showToast('Saved', 'Your data was saved!', 'bg-success text-white', 3000);
		showToast('Error', 'Failed to connect to server.', 'bg-danger text-white', 5000);
		showToast('Warning', 'Please check your input.', 'bg-warning text-dark', 4000);
		showToast('Info', 'System will restart soon.', 'bg-info text-dark', 4000);
		*/

		const container = document.getElementById('toast-container');

		// Create toast element
		const toastEl = document.createElement('div');
		toastEl.className = `toast align-items-center ${type}`;
		toastEl.setAttribute('role', 'alert');
		toastEl.setAttribute('aria-live', 'assertive');
		toastEl.setAttribute('aria-atomic', 'true');
		toastEl.setAttribute('data-bs-delay', delay);

		// Toast inner HTML
		toastEl.innerHTML = `
      <div class="d-flex">
      <div class="toast-body">
        <strong>${title}</strong><br>${text}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

		// Append and show
		container.appendChild(toastEl);
		const bsToast = new bootstrap.Toast(toastEl);
		bsToast.show();

		// Remove from DOM when hidden
		toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
	}
}
