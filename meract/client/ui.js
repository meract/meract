Morph.ui = {
	component : class MorphUiComponent{
		constructor(html = '', obj = {}, onmount = undefined) {
			this._code = html;
			this._object = obj;
			this._onmount = onmount;
		}

		mount(element) {
			element.innerHTML = this._code;
			let reactive = Morph.ui.reactive(this._object, element);
			if (this._onmount !== undefined) {this._onmount(reactive, element);}
			return reactive;
		}

	},


	toggleClass(element, className) {
		return (force) => {
			if (force === undefined) {
				element.classList.toggle(className);
			} else {
				element.classList[force ? 'add' : 'remove'](className);
			}
		};
	},


	inputMask(element, mask, options = {}) {
		const placeholder = options.placeholder || '_';
		let lastValue = '';

		element.addEventListener('input', (e) => {
			let newValue = '';
			let maskPos = 0;
			let valuePos = 0;

			while (maskPos < mask.length && valuePos < e.target.value.length) {
				const maskChar = mask[maskPos];
				const valueChar = e.target.value[valuePos];

				if (maskChar === '#') {
					if (options.allowedChars?.test(valueChar)) {
						newValue += valueChar;
						valuePos++;
					} else {
						valuePos++;
					}
				} else {
					newValue += maskChar;
					if (valueChar === maskChar) valuePos++;
				}
				maskPos++;
			}

			element.value = newValue;
			lastValue = newValue;
		});

		element.addEventListener('keydown', (e) => {
			if (e.key.length === 1 && !(options.allowedChars || /\d/).test(e.key)) {
				e.preventDefault();
			}
		});
	},


	lazyLoad: function(selector = '[data-src]') {
		document.querySelectorAll(selector).forEach(el => {
			this.onview(el, () => {
				if (el.dataset.src) {
					el.src = el.dataset.src;
					el.removeAttribute('data-src');
				}
			}, { threshold: 0.01 });
		});
	},

	onview : function(element, callback, {
		repeat = false,
		onHide = null,
		threshold = 0.01
	} = {}) {

		if (!element || !(element instanceof HTMLElement)) {
			throw new Error('Element must be a valid DOM node');
		}

		let wasVisible = false;

		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					wasVisible = true;
					callback(entry);
				} else if (wasVisible && onHide) {
					onHide(entry);
					if (!repeat) {
						observer.disconnect();
					}
				}
			});
		}, {
			threshold: Math.max(0.01, Math.min(threshold, 1))
		});

		observer.observe(element);

		return () => observer.disconnect();
	},
	reactive(object, element) {
		// 1. Сохраняем оригинальный HTML
		const originalHTML = element.innerHTML;

		// 2. Заменяем шаблоны на data-атрибуты
		const markers = [];
		let updatedHTML = originalHTML.replace(
			/\{\<(\w+)\>\}/g, 
			(match, prop) => {
				const markerId = `data-r-${prop}-${Math.random().toString(36).substr(2, 8)}`;
				markers.push({ markerId, prop });
				return `<span ${markerId}></span>`;
			}
		);

		// 3. Вставляем обновленный HTML
		element.innerHTML = updatedHTML;

		// 4. Находим все маркеры в DOM
		const markerElements = {};
		markers.forEach(({ markerId, prop }) => {
			const el = element.querySelector(`[${markerId}]`);
			if (el) {
				if (!markerElements[prop]) markerElements[prop] = [];
				markerElements[prop].push(el);
				el.removeAttribute(markerId); // Чистим временный атрибут
			}
		});

		// 5. Функция обновления DOM
		function updateDOM(prop, value) {
			if (markerElements[prop]) {
				markerElements[prop].forEach(el => {
					el.textContent = value;
				});
			}
		}

		// 6. Создаем Proxy с реактивными обновлениями
		const proxy = new Proxy(object, {
			set(target, prop, value) {
				if (target[prop] !== value) {
					target[prop] = value;
					updateDOM(prop, value);
				}
				return true;
			}
		});

		// 7. Первичная инициализация значений
		Object.keys(markerElements).forEach(prop => {
			updateDOM(prop, object[prop]);
		});

		return proxy;
	}
};

Morph.ui.animation = {
	// ======================
	// 1. Easing-функции
	// ======================
	easings: {
		linear: t => t,
		easeIn: t => t * t,
		easeOut: t => t * (2 - t),
		easeInOut: t => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t,
		bounce: t => {
			if (t < (1 / 2.75)) return 7.5625 * t * t;
			if (t < (2 / 2.75)) return 7.5625 * (t -= (1.5 / 2.75)) * t + 0.75;
			if (t < (2.5 / 2.75)) return 7.5625 * (t -= (2.25 / 2.75)) * t + 0.9375;
			return 7.5625 * (t -= (2.625 / 2.75)) * t + 0.984375;
		}
	},

	// ======================
	// 2. Готовые методы
	// ======================
	fade: function(element, direction = 'in', duration = 400, easing = 'easeInOut', { onStart, onComplete } = {}) {
		let startTime = null;
		let requestId;
		const from = direction === 'in' ? 0 : 1;
		const to = direction === 'in' ? 1 : 0;

		const animate = (timestamp) => {
			if (!startTime) {
				startTime = timestamp;
				if (onStart) onStart();
			}

			const progress = Math.min((timestamp - startTime) / duration, 1);
			element.style.opacity = from + (to - from) * this.easings[easing](progress);

			if (progress < 1) {
				requestId = requestAnimationFrame(animate);
			} else if (onComplete) {
				onComplete();
			}
		};

		requestId = requestAnimationFrame(animate);

		return {
			cancel: () => cancelAnimationFrame(requestId)
		};
	},

	slide: function(element, direction = 'left', distance = 100, duration = 500, easing = 'easeOut', { onStart, onComplete } = {}) {
		let startTime = null;
		let requestId;
		const axis = direction === 'left' || direction === 'right' ? 'X' : 'Y';
		const sign = direction === 'left' || direction === 'up' ? -1 : 1;

		const animate = (timestamp) => {
			if (!startTime) {
				startTime = timestamp;
				if (onStart) onStart();
			}

			const progress = Math.min((timestamp - startTime) / duration, 1);
			const value = sign * distance * this.easings[easing](progress);
			element.style.transform = `translate${axis}(${value}px)`;

			if (progress < 1) {
				requestId = requestAnimationFrame(animate);
			} else if (onComplete) {
				onComplete();
			}
		};

		requestId = requestAnimationFrame(animate);

		return {
			cancel: () => cancelAnimationFrame(requestId)
		};
	},

	rotate: function(element, angle = 360, duration = 1000, easing = 'linear', { onStart, onComplete } = {}) {
		let startTime = null;
		let requestId;

		const animate = (timestamp) => {
			if (!startTime) {
				startTime = timestamp;
				if (onStart) onStart();
			}

			const progress = Math.min((timestamp - startTime) / duration, 1);
			element.style.transform = `rotate(${angle * progress}deg)`;

			if (progress < 1) {
				requestId = requestAnimationFrame(animate);
			} else if (onComplete) {
				onComplete();
			}
		};

		requestId = requestAnimationFrame(animate);

		return {
			cancel: () => cancelAnimationFrame(requestId)
		};
	},

	scale: function(element, from = 0, to = 1, duration = 600, easing = 'easeInOut', { onStart, onComplete } = {}) {
		let startTime = null;
		let requestId;

		const animate = (timestamp) => {
			if (!startTime) {
				startTime = timestamp;
				if (onStart) onStart();
			}

			const progress = Math.min((timestamp - startTime) / duration, 1);
			element.style.transform = `scale(${from + (to - from) * this.easings[easing](progress)})`;

			if (progress < 1) {
				requestId = requestAnimationFrame(animate);
			} else if (onComplete) {
				onComplete();
			}
		};

		requestId = requestAnimationFrame(animate);

		return {
			cancel: () => cancelAnimationFrame(requestId)
		};
	}
};
