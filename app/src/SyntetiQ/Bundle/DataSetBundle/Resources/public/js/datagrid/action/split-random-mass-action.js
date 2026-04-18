import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import MassAction from 'oro/datagrid/action/mass-action';

const FIELD_NAMES = ['trainPercentage', 'valPercentage', 'testPercentage'];

const SplitRandomMassAction = MassAction.extend({
    trainPercentage: 82,

    valPercentage: 15,

    testPercentage: 3,

    constructor: function SplitRandomMassAction(options) {
        SplitRandomMassAction.__super__.constructor.call(this, options);
    },

    getActionParameters: function() {
        return _.extend({}, SplitRandomMassAction.__super__.getActionParameters.call(this), {
            trainPercentage: this.trainPercentage,
            valPercentage: this.valPercentage,
            testPercentage: this.testPercentage
        });
    },

    getConfirmDialog: function(callback) {
        if (this.confirmModal) {
            this.confirmModal.dispose();
            delete this.confirmModal;
        }

        this.confirmModal = new this.confirmModalConstructor(
            _.extend({}, this.getConfirmDialogOptions(), {
                okCloses: false
            })
        );

        this.confirmModal
            .on('shown', () => {
                const $inputs = this.confirmModal.$el.find('[data-role="split-percentage-input"]');

                $inputs.on('input', () => {
                    this.clearValidationError();
                    this.updateTotalSummary();
                });
                this.setFieldValue('trainPercentage', this.trainPercentage);
                this.setFieldValue('valPercentage', this.valPercentage);
                this.setFieldValue('testPercentage', this.testPercentage);
                this.updateTotalSummary();
                this.confirmModal.$el.find('[data-name="trainPercentage"]').trigger('focus');
            })
            .on('ok', () => {
                const formData = this.collectFormData();
                const validationError = this.getValidationError(formData);

                if (validationError) {
                    this.showValidationError(validationError);
                    return;
                }

                this.trainPercentage = formData.trainPercentage;
                this.valPercentage = formData.valPercentage;
                this.testPercentage = formData.testPercentage;
                this.clearValidationError();
                callback();
                this.confirmModal.close();
            })
            .on('hidden', function() {
                delete this.confirmModal;
            }.bind(this));

        return this.confirmModal;
    },

    getConfirmDialogOptions: function() {
        return _.extend({}, SplitRandomMassAction.__super__.getConfirmDialogOptions.call(this), {
            content: this.getConfirmContentMessage()
        });
    },

    getConfirmContentMessage: function() {
        return '' +
            '<div class="sq-mass-action-split-form" style="max-width:520px;">' +
                '<p style="margin:0 0 18px;">' + _.escape(__(this.messages.confirm_content)) + '</p>' +
                '<div style="display:flex;flex-direction:column;gap:12px;">' +
                this.getFieldMarkup('trainPercentage', this.messages.confirm_train_label) +
                this.getFieldMarkup('valPercentage', this.messages.confirm_validation_label) +
                this.getFieldMarkup('testPercentage', this.messages.confirm_test_label) +
                '</div>' +
                '<div class="control-group" style="margin:18px 0 0;">' +
                    '<div class="controls" style="display:flex;align-items:center;margin-left:0;padding-top:12px;border-top:1px solid #e5e5e5;">' +
                        '<strong style="width:110px;flex:0 0 110px;">' + _.escape(__(this.messages.confirm_total_label)) + ':</strong> ' +
                        '<span data-role="split-total-value" style="font-size:18px;font-weight:600;">0%</span>' +
                    '</div>' +
                '</div>' +
                '<div data-role="split-input-error" class="validation-failed" ' +
                    'style="display:none;margin-top:12px;"></div>' +
            '</div>';
    },

    getFieldMarkup: function(fieldName, labelKey) {
        return '' +
            '<div class="control-group" style="margin:0;">' +
                '<div class="controls" style="display:flex;align-items:center;margin-left:0;">' +
                '<label class="control-label" ' +
                    'style="width:110px;flex:0 0 110px;margin:0;padding-top:0;text-align:left;" ' +
                    'for="sq-mass-action-split-' + _.escape(fieldName) + '">' +
                    _.escape(__(labelKey)) +
                '</label>' +
                '<div style="display:flex;align-items:center;gap:10px;">' +
                    '<input id="sq-mass-action-split-' + _.escape(fieldName) + '" ' +
                        'data-role="split-percentage-input" ' +
                        'data-name="' + _.escape(fieldName) + '" ' +
                        'type="number" ' +
                        'class="input input-small" ' +
                        'style="width:120px;margin:0;text-align:right;" ' +
                        'min="0" max="100" step="1" autocomplete="off">' +
                    '<span style="display:inline-block;min-width:16px;font-weight:600;">' +
                        _.escape(__(this.messages.confirm_input_suffix)) +
                    '</span>' +
                '</div>' +
                '</div>' +
            '</div>';
    },

    setFieldValue: function(fieldName, value) {
        this.confirmModal.$el.find('[data-name="' + fieldName + '"]').val(value);
    },

    collectFormData: function() {
        const formData = {};

        FIELD_NAMES.forEach(fieldName => {
            const value = $.trim(this.confirmModal.$el.find('[data-name="' + fieldName + '"]').val() || '');
            formData[fieldName] = value === '' ? null : Number.parseInt(value, 10);
        });

        return formData;
    },

    getValidationError: function(formData) {
        const values = FIELD_NAMES.map(fieldName => formData[fieldName]);

        if (_.some(values, value => !Number.isInteger(value) || value < 0 || value > 100)) {
            return __(this.messages.confirm_validation_error);
        }

        const total = _.reduce(values, (sum, value) => sum + value, 0);
        if (total !== 100) {
            return __(this.messages.confirm_validation_error);
        }

        return null;
    },

    updateTotalSummary: function() {
        if (!this.confirmModal) {
            return;
        }

        const formData = this.collectFormData();
        const total = _.reduce(FIELD_NAMES, (sum, fieldName) => {
            const value = formData[fieldName];

            return sum + (Number.isInteger(value) ? value : 0);
        }, 0);

        this.confirmModal.$el.find('[data-role="split-total-value"]').text(total + '%');
    },

    showValidationError: function(message) {
        this.confirmModal.$el
            .find('[data-role="split-input-error"]')
            .text(message)
            .show();
    },

    clearValidationError: function() {
        this.confirmModal.$el
            .find('[data-role="split-input-error"]')
            .hide()
            .empty();
    }
});

export default SplitRandomMassAction;
