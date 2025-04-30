

class MorphTriggers {
    constructor() {
        this.placeholders = new Map();
        this.originalTemplate = '';
        this.processedTemplate = '';
    }

    generatePlaceholder() {
        return 'ph_' + Math.random().toString(36).substr(2, 9);
    }

    preprocessTemplate(template) {
        this.originalTemplate = template;
        this.processedTemplate = template;

        // Заменяем @morph-triggerSubmit
        this.processedTemplate = this.processedTemplate.replaceAll('@morph-triggerSubmit', 'Morph._trigger_submit_(this)');

        // Заменяем @MTrigger на плейсхолдеры и сохраняем информацию о них
        this.processedTemplate = this.processedTemplate.replace(/@MTrigger\((["'])([^"']+)\1,\s*(["'])([^"']*)\3\)/g,
            (match, quote1, dataKey, quote2, defaultValue) => {
                const placeholder = this.generatePlaceholder();
                this.placeholders.set(placeholder, { dataKey, defaultValue });
                return placeholder;
            });

        // Сохраняем копию с плейсхолдерами для последующих замен
        this.templateWithPlaceholders = this.processedTemplate;

        // Создаём версию с значениями по умолчанию
        let defaultTemplate = this.processedTemplate;
        for (const [placeholder, { defaultValue }] of this.placeholders) {
            defaultTemplate = defaultTemplate.replace(new RegExp(placeholder, 'g'), defaultValue);
        }

        return defaultTemplate;
    }

    updateData(data) {
        let result = this.templateWithPlaceholders;

        for (const [placeholder, { dataKey, defaultValue }] of this.placeholders) {
            const value = data.hasOwnProperty(dataKey) ? data[dataKey] : defaultValue;
            result = result.replace(new RegExp(placeholder, 'g'), value);
        }

        return result;
    }
}

Morph._registerTrigger_ = function (elem) {
    if ((typeof elem.trigger) === 'object') {return;}
    elem.trigger = new MorphTriggers;
    elem.innerHTML = elem.trigger.preprocessTemplate(elem.innerHTML);
    return elem.trigger;
};

Morph._updateTriggerInfo_ = function(elem, data) {
    elem.innerHTML = elem.trigger.updateData(data);
}

Morph._trigger_submit_ = function(el) {
    trig = el.closest('morph-trigger');
    const namedElems = trig.querySelectorAll('*[name]');
    let data = {};
    namedElems.forEach(el => {
        data[el.name] = el.value;
    })


    Morph.http.async.post('/morph-trigger/' + trig.getAttribute('action'), data, function (response) {
        Morph._updateTriggerInfo_(trig, JSON.parse(response.body));
    });
}

Morph.registerInitHook(function () {
    document.querySelectorAll('morph-trigger').forEach(el => {
        console.log(el);
        Morph._registerTrigger_(el);
    })
})