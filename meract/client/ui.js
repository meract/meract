Morph.ui = {
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

	reactive : function (object, element) {
		// Сохраняем оригинальный HTML для повторного рендеринга
		const originalHTML = element.innerHTML;

		// Создаем Proxy для отслеживания изменений
		const proxy = new Proxy(object, {
			set(target, key, value) {
				target[key] = value;
				updateDOM(); // Обновляем DOM при изменениях
				return true;
			}
		});

		// Функция для обновления DOM
		function updateDOM() {
			let html = originalHTML;

			// Заменяем все вхождения {<property>} на значения из объекта
			html = html.replace(/\{\<(\w+)\>\}/g, (match, property) => {
				return proxy[property] !== undefined ? proxy[property] : match;
			});

			element.innerHTML = html;
		}

		// Первоначальное обновление DOM
		updateDOM();

		return proxy;
	}
};
