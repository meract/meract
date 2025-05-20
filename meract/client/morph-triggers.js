class MorphTriggers {
    constructor(element) {
        this.element = element;
        this.bindings = new Map(); // Хранит привязки dataKey → элементы
        this.originalHTML = element.innerHTML;
        this.placeholders = new Map(); // Хранит плейсхолдеры → {dataKey, defaultValue}
        this.defaultValues = new Map(); // Хранит dataKey → defaultValue
    }

    /**
     * Препроцессинг шаблона и создание привязок
     */
    preprocessTemplate() {
        // 1. Обработка триггеров submit
        let processedHTML = this.originalHTML.replace(
            /@morph-triggerSubmit/g, 
            'Morph._trigger_submit_(this)'
        );

        // 2. Замена MTrigger на плейсхолдеры
        processedHTML = processedHTML.replace(
            /@MTrigger\((["'])([^"']+)\1,\s*(["'])([^"']*)\3\)/g,
            (match, quote1, dataKey, quote2, defaultValue) => {
                const placeholder = this._createPlaceholder();
                this.placeholders.set(placeholder, { dataKey, defaultValue });
                this.defaultValues.set(dataKey, defaultValue);
                return `<span data-morph-placeholder="${placeholder}"></span>`;
            }
        );

        // 3. Устанавливаем обработанный HTML
        this.element.innerHTML = processedHTML;

        // 4. Создаем привязки элементов
        this.element.querySelectorAll('[data-morph-placeholder]').forEach(el => {
            const placeholder = el.getAttribute('data-morph-placeholder');
            const { dataKey } = this.placeholders.get(placeholder);
            
            el.removeAttribute('data-morph-placeholder');
            
            if (!this.bindings.has(dataKey)) {
                this.bindings.set(dataKey, []);
            }
            this.bindings.get(dataKey).push(el);
        });

        // 5. Устанавливаем значения по умолчанию
        this._applyDefaultValues();

        return this.element.innerHTML;
    }

    /**
     * Применяет значения по умолчанию ко всем привязанным элементам
     */
    _applyDefaultValues() {
        for (const [dataKey, elements] of this.bindings) {
            const defaultValue = this.defaultValues.get(dataKey);
            if (defaultValue !== undefined) {
                elements.forEach(el => {
                    el.textContent = defaultValue;
                });
            }
        }
    }

    /**
     * Обновление данных с точечным обновлением DOM
     */
    updateData(data) {
        for (const [dataKey, elements] of this.bindings) {
            const value = data.hasOwnProperty(dataKey) 
                ? data[dataKey] 
                : this.defaultValues.get(dataKey) || '';
            
            elements.forEach(el => {
                if (el.innerHTML!== value) {
                    el.innerHTML = value;
                }
            });
        }
    }

    _createPlaceholder() {
        return 'ph_' + Math.random().toString(36).substr(2, 12);
    }
}

// Глобальные методы Morph (остаются без изменений)
Morph._registerTrigger_ = function(element) {
    if (element.trigger) return element.trigger;
    
    element.trigger = new MorphTriggers(element);
    element.trigger.preprocessTemplate();
    return element.trigger;
};

Morph._updateTriggerInfo_ = function(element, data) {
    if (!element.trigger) {
        element.trigger = new MorphTriggers(element);
    }
    element.trigger.updateData(data);
};

Morph._trigger_submit_ = function(button) {
    const triggerElement = button.closest('morph-trigger');
    const formData = {};
    
    triggerElement.querySelectorAll('[name]').forEach(el => {
        formData[el.name] = el.value;
    });

    Morph.http.async.post(
        '/morph-trigger/' + triggerElement.getAttribute('action'),
        formData,
        response => {
            Morph._updateTriggerInfo_(triggerElement, JSON.parse(response.body));
        }
    );
};

Morph.registerInitHook(() => {
    document.querySelectorAll('morph-trigger').forEach(el => {
        Morph._registerTrigger_(el);
    });
});
