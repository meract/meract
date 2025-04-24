class MorphInstance {
	constructor() {
		this.basePath = './';
		this.loaded = new Set();
		this.currentPage = null;
		this.morphs = {};
		this.initialized = false;
	}

	async init() {
		if (this.initialized) return;
		this.initialized = true;

		// Initialize all morph elements
		document.querySelectorAll('morph').forEach(el => {
			const name = el.getAttribute('name');
			if (!name) {
				console.warn('Morph element missing name attribute', el);
				return;
			}

			this.morphs[name] = el;
			this.initMorphElement(el);

			// Handle backload if needed
			const backload = el.getAttribute('backload');
			if (backload && el.getAttribute('backloadType') === 'once') {
				this.loadComponent(el, backload);
			}
		});

		// Activate first page if none is active
		if (!this.currentPage && Object.keys(this.morphs).length > 0) {
			const firstPage = Object.values(this.morphs)[0];
			await this.activatePage(firstPage);
		}
	}

	async goTo(name) {
		if (!this.initialized) await this.init();

		const targetPage = this.morphs[name];
		if (!targetPage) {
			console.error(`Page "${name}" not found`);
			return false;
		}

		await this.activatePage(targetPage);
		return true;
	}

	async activatePage(pageElement) {
		// Skip if already active
		if (this.currentPage === pageElement) return;

		// Deactivate current page
		if (this.currentPage) {
			this.currentPage.removeAttribute('active');
		}

		// Activate new page
		pageElement.setAttribute('active', '');
		this.currentPage = pageElement;

		// Handle backload if needed
		const backload = pageElement.getAttribute('backload');
		if (backload) {
			const backloadType = pageElement.getAttribute('backloadType');
			if (backloadType !== "once") {
				try {
					await this.loadComponent(pageElement, backload);

					// Remove backload attribute if type is "goto"
					if (backloadType === 'goto') {
						pageElement.removeAttribute('backload');
					}
				} catch (error) {
					console.error(`Failed to backload component for ${pageElement.getAttribute('name')}:`, error);
				}
			}
		}
	}

	async loadComponent(morphElement, componentName) {
		try {
			const response = await fetch(`/morph-component/${componentName}`);
			if (!response.ok) throw new Error(`HTTP ${response.status}`);

			const html = await response.text();

			// Store current active state
			const wasActive = morphElement.hasAttribute('active');

			// Update content
			morphElement.innerHTML = html;

			// Reapply active state if needed
			if (wasActive) {
				morphElement.setAttribute('active', '');
			}

			// Initialize new content
			this.initMorphElement(morphElement);

			return true;
		} catch (error) {
			console.error(`Failed to load component "${componentName}":`, error);
			morphElement.innerHTML = `
		<div class="morph-error">
		  Failed to load component: ${componentName}
		  <small>${error.message}</small>
		</div>
	  `;
			return false;
		}
	}

	initMorphElement(el) {
		// Load theme if specified
		const theme = el.getAttribute('theme');
		if (theme) {
			this.loadTheme(theme);
		}

		// Load colorscheme if specified
		const colorscheme = el.getAttribute('colorscheme');
		if (colorscheme) {
			this.loadColorscheme(colorscheme);
		}

		// Initialize any custom logic for the element
		// (This is separated for easier extension)
	}

	loadCSS(file) {
		if (this.loaded.has(file)) return;

		const link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = `/${file}`;
		document.head.appendChild(link);

		this.loaded.add(file);
	}

	loadTheme(name) {
		this.loadCSS(`morph-themes/${name}.css`);
	}

	loadColorscheme(name) {
		this.loadCSS(`morph-colorschemes/${name}.css`);
	}
}

const Morph = new MorphInstance();
document.addEventListener('DOMContentLoaded', () => Morph.init());
