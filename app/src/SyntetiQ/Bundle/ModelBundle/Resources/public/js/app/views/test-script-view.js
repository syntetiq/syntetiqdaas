import BaseView from 'oroui/js/app/views/base/view';

const TestScriptView = BaseView.extend({
    count: 0,

    counterValueEl: null,

    /**
     * @inheritdoc
     */
    constructor: function TestScriptView(options) {
        TestScriptView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        TestScriptView.__super__.initialize.call(this, options);

        console.log('ModelBundle test script view initialized');
        this.renderWidget();
    },

    renderWidget: function() {
        const container = this.el.querySelector('.test-script-widget');
        if (!container) {
            return;
        }

        container.innerHTML = `
            <div style="padding:20px;border:2px solid #1f4d86;border-radius:8px;background:#f6f9fc;max-width:420px;">
                <h4 style="margin:0 0 10px;">Counter Sandbox</h4>
                <p style="margin:0 0 16px;">Use this page for quick JS checks.</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <button type="button" class="btn btn-primary test-script-decrement">-</button>
                    <strong class="test-script-count">0</strong>
                    <button type="button" class="btn btn-primary test-script-increment">+</button>
                </div>
            </div>
        `;

        this.counterValueEl = container.querySelector('.test-script-count');
        container.querySelector('.test-script-decrement')?.addEventListener('click', () => {
            this.updateCount(this.count - 1);
        });
        container.querySelector('.test-script-increment')?.addEventListener('click', () => {
            this.updateCount(this.count + 1);
        });
    },

    updateCount: function(nextCount) {
        this.count = nextCount;

        if (this.counterValueEl) {
            this.counterValueEl.textContent = String(this.count);
        }
    }
});

export default TestScriptView;
