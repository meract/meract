class MorphInstance {
    constructor() {
        this.basePath = './';
        this.loaded = new Set();
        this.currentPage = null;
        this.morphs = {};
    }

    async init() {
        //this.loadCSS('morph.css');

        // Обработка всех morph-элементов
        document.querySelectorAll('morph').forEach(el => {
            const name = el.getAttribute('name');
            const backload = el.getAttribute('backload');

            this.morphs[name] = el;

            if (backload) {
                this.loadComponent(el, backload);
            } else {
                this.initMorph(el);
            }
        });
    }

    async loadComponent(morphElement, componentName) {
        try {
            const response = await fetch(`/morph-component/${componentName}`);
            if (!response.ok) throw new Error('Component not found');
            
            const html = await response.text();
            morphElement.innerHTML = html;
            this.initMorph(morphElement);
        } catch (error) {
            console.error('Morph backload failed:', error);
            morphElement.innerHTML = '<div class="error">Component load error</div>';
        }
    }

    initMorph(el) {
        const theme = el.getAttribute('theme');
        const colorscheme = el.getAttribute('colorscheme');

        if (theme) this.loadTheme(theme);
        if (colorscheme) this.loadColorscheme(colorscheme);

        if (!this.currentPage) {
            this.currentPage = el;
            el.setAttribute('active', '');
        } else {
            el.removeAttribute('active');
        }
    }

    goTo(name) {
        const nextPage = this.morphs[name];
        if (nextPage) {
            this.currentPage?.removeAttribute('active');
            nextPage.setAttribute('active', '');
            this.currentPage = nextPage;
        }
    }

    loadCSS(file) {
        if (this.loaded.has(file)) return;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `${this.basePath}${file}`;
        document.head.appendChild(link);
        this.loaded.add(file);
    }

    loadTheme(name) {
        this.loadCSS(`themes/${name}.css`);
    }

    loadColorscheme(name) {
        this.loadCSS(`colorschemes/${name}.css`);
    }
}

const Morph = new MorphInstance();
document.addEventListener('DOMContentLoaded', () => Morph.init());