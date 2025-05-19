Morph.utils = Morph.utils || {};

Morph.utils.debounce = (callback, wait) => {
	let timeoutId = null;
	return (...args) => {
		window.clearTimeout(timeoutId);
		timeoutId = window.setTimeout(() => {
			callback(...args);
		}, wait);
	};
}

Morph.utils.uuid = function() {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
		const r = Math.random() * 16 | 0;
		return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
	});
}

Morph.utils.emitEvent = function(element, name, detail = {}) {
	const event = new CustomEvent(name, { detail });
	element.dispatchEvent(event);
};

Morph.utils.validate = {
	// Проверка email
	email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),

	// Проверка номера телефона (простой вариант)
	phone: (value) => /^\+?[\d\s\-\(\)]{7,}$/.test(value),

	// Проверка на пустоту (с тримингом)
	required: (value) => String(value).trim() !== '',
}

Morph.utils.validate.password = (password, rules = {}) => {
	const {
		minLength = 0,          // Минимальная длина (по умолчанию не проверяется)
		maxLength = Infinity,   // Максимальная длина (по умолчанию не проверяется)
		digit = false,   // Требуется ли цифра
		specialChar = false, // Требуется ли спецсимвол
		uppercase = false,  // Требуется ли заглавная буква
		lowercase = false   // Требуется ли строчная буква
	} = rules;

	// Проверка длины
	if (password.length < minLength) return false;
	if (password.length > maxLength) return false;

	// Проверка цифр
	if (digit && !/\d/.test(password)) return false;

	// Проверка спецсимволов
	if (specialChar && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) return false;

		// Проверка регистра
		if (uppercase && !/[A-Z]/.test(password)) return false;
		if (lowercase && !/[a-z]/.test(password)) return false;

		return true; // Все проверки пройдены
	};


Function.prototype.Mdebounce = function(delay) {
	return Morph.utils.debounce(this, delay);
}
String.prototype.Mquery = function() {return document.querySelector(this);}
String.prototype.MqueryAll = function() {return document.querySelectorAll(this);}
String.prototype.Mvalidate = function(type, rules = {}) {
	if (type === "password") {
		return Morph.utils.validate[type](this, rules);
	} else {
		return Morph.utils.validate[type](this);
	}
}
