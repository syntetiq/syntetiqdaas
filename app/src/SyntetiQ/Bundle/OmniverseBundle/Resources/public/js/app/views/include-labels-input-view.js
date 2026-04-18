import BaseView from 'oroui/js/app/views/base/view';

const IncludeLabelsInputView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['fieldSelector']),

    labels: null,

    textarea: null,

    wrapper: null,

    list: null,

    input: null,

    /**
     * @inheritdoc
     */
    constructor: function IncludeLabelsInputView(options) {
        IncludeLabelsInputView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        IncludeLabelsInputView.__super__.initialize.call(this, options);

        this.labels = [];
        this.textarea = this.fieldSelector ? this.el.querySelector(this.fieldSelector) : this.el.querySelector('textarea');

        if (!this.textarea || this.textarea.dataset.enhanced === 'true') {
            return;
        }

        this.textarea.dataset.enhanced = 'true';
        this.textarea.style.display = 'none';

        this.buildUi();
        this.labels = this.parseLabels(this.textarea.value);
        this.syncTextarea();
        this.renderLabels();
    },

    buildUi: function() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'omniverse-labels-input';

        this.list = document.createElement('div');
        this.list.className = 'omniverse-labels-input__list';

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'omniverse-labels-input__field';
        this.input.placeholder = this.textarea.getAttribute('placeholder') || '';
        this.input.setAttribute('aria-label', this.textarea.getAttribute('aria-label') || this.textarea.getAttribute('name'));

        this.wrapper.addEventListener('click', this.onWrapperClick.bind(this));
        this.input.addEventListener('keydown', this.onInputKeydown.bind(this));
        this.input.addEventListener('blur', this.onInputBlur.bind(this));
        this.input.addEventListener('paste', this.onInputPaste.bind(this));

        this.wrapper.appendChild(this.list);
        this.wrapper.appendChild(this.input);
        this.textarea.insertAdjacentElement('afterend', this.wrapper);
    },

    parseLabels: function(value) {
        if (!value) {
            return [];
        }

        const parts = value.split(/[\n,]+/);
        const next = [];
        const seen = Object.create(null);

        for (let i = 0; i < parts.length; i++) {
            const label = parts[i].trim();
            if (!label || seen[label]) {
                continue;
            }

            seen[label] = true;
            next.push(label);
        }

        return next;
    },

    syncTextarea: function() {
        this.textarea.value = this.labels.join('\n');
    },

    addLabels: function(value) {
        const next = this.parseLabels(value);
        if (!next.length) {
            return;
        }

        const existing = Object.create(null);
        for (let i = 0; i < this.labels.length; i++) {
            existing[this.labels[i]] = true;
        }

        for (let i = 0; i < next.length; i++) {
            if (!existing[next[i]]) {
                this.labels.push(next[i]);
                existing[next[i]] = true;
            }
        }

        this.syncTextarea();
        this.renderLabels();
    },

    removeLabel: function(index) {
        this.labels.splice(index, 1);
        this.syncTextarea();
        this.renderLabels();
    },

    renderLabels: function() {
        if (!this.list) {
            return;
        }

        this.list.innerHTML = '';

        for (let i = 0; i < this.labels.length; i++) {
            const tag = document.createElement('span');
            tag.className = 'omniverse-labels-input__tag';
            tag.appendChild(document.createTextNode(this.labels[i]));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'omniverse-labels-input__remove';
            remove.setAttribute('aria-label', 'Remove ' + this.labels[i]);
            remove.textContent = '×';
            remove.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                this.removeLabel(i);
                this.input.focus();
            });

            tag.appendChild(remove);
            this.list.appendChild(tag);
        }
    },

    onWrapperClick: function() {
        if (this.input) {
            this.input.focus();
        }
    },

    onInputKeydown: function(event) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            this.addLabels(this.input.value);
            this.input.value = '';
            return;
        }

        if (event.key === 'Backspace' && !this.input.value && this.labels.length) {
            event.preventDefault();
            this.removeLabel(this.labels.length - 1);
        }
    },

    onInputBlur: function() {
        if (this.input.value.trim()) {
            this.addLabels(this.input.value);
            this.input.value = '';
        }
    },

    onInputPaste: function(event) {
        const text = event.clipboardData ? event.clipboardData.getData('text') : '';
        if (!text || !/[\n,]/.test(text)) {
            return;
        }

        event.preventDefault();
        this.addLabels(text);
        this.input.value = '';
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        if (this.wrapper) {
            this.wrapper.remove();
            this.wrapper = null;
        }

        if (this.textarea) {
            this.textarea.style.display = '';
            delete this.textarea.dataset.enhanced;
            this.textarea = null;
        }

        this.list = null;
        this.input = null;
        this.labels = null;

        IncludeLabelsInputView.__super__.dispose.call(this);
    }
});

export default IncludeLabelsInputView;
