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

			el.virutal = function () {
				return this.cloneNode(true);
			}

			el.renderVirtual = function(dom) {
				this.innerHTML = dom.innerHTML;
			}

			this.morphs[name] = el;
			this.initMorphElement(el);

			// Handle backload if needed
			const backload = this.getBackloadUrl(el);
			const backloadType = el.getAttribute('backloadType');
			
			if (backload && backloadType === 'once') {
				this.loadComponent(el, backload);
			}
		});

		// Activate first page if none is active
		if (!this.currentPage && Object.keys(this.morphs).length > 0) {
			const firstPage = Object.values(this.morphs)[0];
			await this.activatePage(firstPage);
		}
	}

	goTo(name, data = null) {
		if (!this.initialized) this.init();

		const targetPage = this.morphs[name];
		if (!targetPage) {
			console.error(`Page "${name}" not found`);
			return false;
		}

		this.activatePage(targetPage, data);
		return true;
	}

	async render(name, data = null) {
		if (!this.initialized) this.init();

		const targetPage = this.morphs[name];
		if (!targetPage) {
			console.error(`Page "${name}" not found`);
			return false;
		}

		const backloadUrl = this.getBackloadUrl(targetPage);
		const backloadType = targetPage.getAttribute('backloadType');
		
		if (backloadUrl && backloadType === 'wait') {
			await this.loadComponent(targetPage, backloadUrl, data);
			return true;
		}
		
		console.warn(`Morph "${name}" is not of type "wait" or has no backload URL`);
		return false;
	}

	async reload(data = null) {
		if (!this.currentPage) return;
		
		const backloadUrl = this.getBackloadUrl(this.currentPage);
		const backloadType = this.currentPage.getAttribute('backloadType');
		
		if (backloadUrl && (backloadType === 'every' || backloadType === 'wait')) {
			await this.loadComponent(this.currentPage, backloadUrl, data);
		} else if (backloadUrl && backloadType === 'goto') {
			await this.activatePage(this.currentPage, data, true);
		}
	}
	
	getBackloadUrl(morphElement) {
		// Priority: backloadUrl > backload
		const customUrl = morphElement.getAttribute('customBackload');
		if (customUrl) return customUrl;
		
		const componentName = morphElement.getAttribute('backload');
		if (componentName) return `/morph-component/${componentName}`;
		
		return null;
	}

	async activatePage(pageElement, data = null, reload = false) {
		// Skip if already active
		if (this.currentPage === pageElement && !reload) return;

		// Deactivate current page
		if (this.currentPage) {
			this.currentPage.removeAttribute('active');
		}

		// Activate new page
		pageElement.setAttribute('active', '');
		this.currentPage = pageElement;

		// Handle backload if needed
		const backloadUrl = this.getBackloadUrl(pageElement);
		if (backloadUrl) {
			const backloadType = pageElement.getAttribute('backloadType');
			
			if (backloadType === 'every' || (backloadType === 'goto' && !reload)) {
				try {
					await this.loadComponent(pageElement, backloadUrl, data);

					// Remove backload attributes if type is "goto"
					if (backloadType === 'goto') {
						pageElement.removeAttribute('backload');
						pageElement.removeAttribute('customBackload');
					}
				} catch (error) {
					console.error(`Failed to backload component for ${pageElement.getAttribute('name')}:`, error);
				}
			}
		}
	}

	async loadComponent(morphElement, url, data = null) {
		try {
			const fetchOptions = {
				method: data ? 'POST' : 'GET',
				headers: {},
			};

			// Если есть данные, формируем тело запроса в формате urlencoded
			if (data) {
				const formData = new URLSearchParams();
				for (const key in data) {
					formData.append(key, data[key]);
				}
				fetchOptions.body = formData;
				fetchOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded';
			}

			const response = await fetch(url, fetchOptions);
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
			console.error(`Failed to load component from "${url}":`, error);
			morphElement.innerHTML = `
			<div class="morph-error">
				Failed to load component from: ${url}
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
function morph(name){
	return Morph.morphs[name];
}
document.addEventListener('DOMContentLoaded', () => Morph.init());
