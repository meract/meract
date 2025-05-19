class MorphInstance {
	constructor() {
		this.basePath = './';
		this.loaded = new Set();
		this.currentPage = null;
		this.morphs = {};
		this.initialized = false;
		this.lastHandledHash = '';
		this.ignoreNextHashChange = false;
	}

	async onScriptStart(callback){
		document.currentScript.addEventListener("load", callback);
	}

	// Инициализация морфов
	async init() {
		if (this.initialized) return;
		this.initialized = true;

		// Инициализация всех элементов morph
		document.querySelectorAll('morph').forEach(el => {
			const name = el.getAttribute('name');
			if (!name) {
				console.warn('Morph element missing name attribute', el);
				return;
			}

			el.virutal = function() {
				return this.cloneNode(true);
			};

			el.renderVirtual = function(dom) {
				this.innerHTML = dom.innerHTML;
			};

			this.morphs[name] = el;
			this.initMorphElement(el);

			// Загрузка компонента при необходимости
			const backload = this.getBackloadUrl(el);
			const backloadType = el.getAttribute('backloadType');

			if (backload && backloadType === 'once') {
				this.loadComponent(el, backload);
			}
		});

		// Настройка обработчиков навигации
		this._setupNavigationHandlers();

		// Обработка начального хэша
		this._handleHashChange();

		if (!this.currentPage && Object.keys(this.morphs).length > 0) {
			const firstPage = Object.values(this.morphs)[0];
			await this.activatePage(firstPage);
		}



		this.initMorphForms();
		const script = document.createElement('script');
		script.src = `/morph-scripts?path=${encodeURIComponent(window.location.pathname)}`;
		document.head.appendChild(script);
	}

	// Настройка обработчиков событий навигации
	_setupNavigationHandlers() {
		// Обработка кнопок назад/вперед
		window.addEventListener('popstate', (event) => {
			if (this.ignoreNextHashChange) {
				this.ignoreNextHashChange = false;
				return;
			}
			this._handleHashChange(true);
		});

		// Обработка ручного изменения URL
		window.addEventListener('hashchange', () => {
			if (this.ignoreNextHashChange) {
				this.ignoreNextHashChange = false;
				return;
			}
			this._handleHashChange();
		});

		// Обработка кликов по ссылкам с хэшем
		document.addEventListener('click', (event) => {
			const link = event.target.closest('a[href^="#"]');
			if (link && link.getAttribute('href') !== '#') {
				event.preventDefault();
				const morphName = link.getAttribute('href').substring(1);
				if (this.morphs[morphName]) {
					this._navigateTo(morphName);
				}
			}
		});
	}

	_initHooks = [];
	registerInitHook(hook) {
		this._initHooks.push(hook);
	}

	// Обработка изменения хэша
	_handleHashChange(isHistoryNavigation = false) {
		let currentHash = window.location.hash.substring(1);

		let hashArr = currentHash.split('?');
		currentHash = hashArr[0];
		let params;
		if (hashArr.length === 2){
			params = JSON.parse('{"' + decodeURI(hashArr[1]).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
		}

		// Пропуск если хэш не изменился
		if (currentHash === this.lastHandledHash) return;

		this.lastHandledHash = currentHash;

		if (currentHash && this.morphs[currentHash]) {
			if (hashArr.length !== 2){
				this._navigateTo(currentHash, null, isHistoryNavigation);
			} else {
				this._navigateTo(currentHash, params, isHistoryNavigation);
			}
		} else if (Object.keys(this.morphs).length > 0) {
			// Активация первой страницы если нет валидного хэша
			const firstPage = Object.values(this.morphs)[0];
			if (!currentHash) {
				this.activatePage(firstPage);
				this._addToHistory(firstPage.getAttribute('name'));
			}
		}
	}

	// Переход к указанному морфу
	goTo(name, data = null) {
		if (!this.initialized) this.init();

		const targetPage = this.morphs[name];
		if (!targetPage) {
			console.error(`Page "${name}" not found`);
			return false;
		}

		this._navigateTo(name, data);
		return true;
	}

	// Внутренний метод навигации
	async _navigateTo(name, data = null, isHistoryNavigation = false) {
		const targetPage = this.morphs[name];
		if (!targetPage) {
			console.error(`Page "${name}" not found`);
			return false;
		}

		await this.activatePage(targetPage, data);

		if (!isHistoryNavigation) {
			this._addToHistory(name, data);
		}
	}

	// Добавление в историю
	_addToHistory(name, data = null) {
		const state = { morphName: name, data };

		// Добавление в историю только если хэш изменился
		if (window.location.hash.substring(1) !== name) {
			this.ignoreNextHashChange = true;
			if (this.morphs[name].getAttribute('showParams') === "true") {
				let resultArr = [];
				Object.keys(data).forEach(e => {
					resultArr.push(`${encodeURI(e)}=${encodeURI(data[e])}`);
				});
				let searchParams = resultArr.join('&');
				window.history.pushState(state, '', `#${name}?${searchParams}`);
			} else {
				window.history.pushState(state, '', `#${name}`);
			}
		}

		this.lastHandledHash = name;
	}

	// Активация страницы
	async activatePage(pageElement, data = null, reload = false) {
		// Пропуск если уже активна
		if (this.currentPage === pageElement && !reload) return;

		// Деактивация текущей страницы
		if (this.currentPage) {
			this.currentPage.removeAttribute('active');
		}

		// Активация новой страницы
		pageElement.setAttribute('active', '');
		this.currentPage = pageElement;

		// Загрузка компонента если требуется
		const backloadUrl = this.getBackloadUrl(pageElement);
		if (backloadUrl) {
			const backloadType = pageElement.getAttribute('backloadType');

			if (backloadType === 'every' || (backloadType === 'goto' && !reload)) {
				try {
					await this.loadComponent(pageElement, backloadUrl, data);

					// Удаление атрибутов для типа "goto"
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

	// Загрузка компонента
	async loadComponent(morphElement, url, data = null) {
		try {
			const fetchOptions = {
				method: data ? 'POST' : 'GET',
				headers: {},
			};

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

			const wasActive = morphElement.hasAttribute('active');

			morphElement.innerHTML = html;

			if (wasActive) {
				morphElement.setAttribute('active', '');
			}


			morphElement.querySelectorAll('morph').forEach(el => {
				const name = el.getAttribute('name');
				if (!name) {
					console.warn('Morph element missing name attribute', el);
					return;
				}

				el.virutal = function() {
					return this.cloneNode(true);
				};

				el.renderVirtual = function(dom) {
					this.innerHTML = dom.innerHTML;
				};

				this.morphs[name] = el;
				this.initMorphElement(el);

				// Загрузка компонента при необходимости
				const backload = this.getBackloadUrl(el);
				const backloadType = el.getAttribute('backloadType');

				if (backload && backloadType === 'once') {
					this.loadComponent(el, backload);
				}
			});


			this.initMorphElement(morphElement);
			// Инициализируем морф формы
			this.initMorphForms();

			// Инициализируем скрипты
			const scripts = morphElement.querySelectorAll('script');
			scripts.forEach(script => {
				const newScript = document.createElement('script');
				if (script.src) {
					newScript.src = script.src; // Подгружаем внешние скрипты
				} else {
					newScript.textContent = script.textContent; // Выполняем inline-скрипты
				}
				document.body.appendChild(newScript).remove(); // Добавляем и сразу удаляем
			});

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

	// Получение URL для загрузки
	getBackloadUrl(morphElement) {
		const customUrl = morphElement.getAttribute('customBackload');
		if (customUrl) return customUrl;

		const componentName = morphElement.getAttribute('backload');
		if (componentName) return `/morph-component/${componentName}`;

		return null;
	}

	// Инициализация элемента morph
	initMorphElement(el) {
		const theme = el.getAttribute('theme');
		if (theme) {
			this.loadTheme(theme);
		}

		const colorscheme = el.getAttribute('colorscheme');
		if (colorscheme) {
			this.loadColorscheme(colorscheme);
		}

		if (this._initHooks.length > 0) {
			this._initHooks.forEach(hook => {
				hook(el);
			});
		}
	}

	// Загрузка CSS
	loadCSS(file) {
		if (this.loaded.has(file)) return;

		const link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = `/${file}`;
		document.head.appendChild(link);

		this.loaded.add(file);
	}

	// Загрузка темы
	loadTheme(name) {
		this.loadCSS(`morph-themes/${name}.css`);
	}

	// Загрузка цветовой схемы
	loadColorscheme(name) {
		this.loadCSS(`morph-colorschemes/${name}.css`);
	}

	ajaxForm(formElement) {
		formElement.addEventListener('submit', async (event) => {
			event.preventDefault();

			try {
				// Получаем action формы (название морфа)
				let action = formElement.getAttribute('action') || '';

				// Если action пустой или ".", значит перезагружаем текущую страницу
				const isReload = action === '' || action === '.';

				// Если action не указан, используем текущий морф
				if (isReload && this.currentPage) {
					action = this.currentPage.getAttribute('name');
				}

				// Собираем данные формы
				const formData = new FormData(formElement);
				const parameters = {};

				for (const [key, value] of formData.entries()) {
					parameters[key] = value;
				}

				// Выполняем переход или перезагрузку
				if (isReload) {
					await this.reload(parameters);
				} else {
					await this.goTo(action, parameters);
				}

			} catch (error) {
				console.error('Form submission error:', error);
				// Можно добавить обработку ошибок, например показать сообщение в форме
			}
		});
	}

	// Метод для инициализации всех форм с атрибутом type="morph"
	initMorphForms() {
		// Инициализация форм при загрузке
		document.querySelectorAll('form[type="morph"]').forEach(form => {
			this.ajaxForm(form);
		});

		// Наблюдатель за изменениями DOM для обработки динамически загруженных форм
		const observer = new MutationObserver(mutations => {
			mutations.forEach(mutation => {
				mutation.addedNodes.forEach(node => {
					if (node.nodeType === Node.ELEMENT_NODE) {
						const forms = node.querySelectorAll ? 
							node.querySelectorAll('form[type="morph"]') : [];

						forms.forEach(form => {
							this.ajaxForm(form);
						});
					}
				});
			});
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}
	// Рендер морфа
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

	// Перезагрузка текущей страницы
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
}

// Глобальный экземпляр Morph
const Morph = new MorphInstance();

// Глобальная функция для доступа к морфам
function morph(name) {
	return Morph.morphs[name];
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => Morph.init());
