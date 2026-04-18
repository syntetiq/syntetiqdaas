import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import MassAction from 'oro/datagrid/action/mass-action';

const RenameLabelPromptMassAction = MassAction.extend({
    oldLabelValue: '',
    newLabelValue: '',

    constructor: function RenameLabelPromptMassAction(options) {
        RenameLabelPromptMassAction.__super__.constructor.call(this, options);
    },

    getActionParameters: function() {
        return _.extend({}, RenameLabelPromptMassAction.__super__.getActionParameters.call(this), {
            oldLabelValue: this.oldLabelValue,
            newLabelValue: this.newLabelValue
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
                const $oldInput = this.confirmModal.$el.find('[data-role="old-label-input"]');
                const $newInput = this.confirmModal.$el.find('[data-role="new-label-input"]');
                const $error = this.confirmModal.$el.find('[data-role="rename-label-error"]');

                $oldInput.add($newInput).on('input', () => {
                    $error.hide().empty();
                    $oldInput.removeClass('error');
                    $newInput.removeClass('error');
                });

                $oldInput.trigger('focus');

                if (this.oldLabelValue) {
                    $oldInput.val(this.oldLabelValue);
                }

                if (this.newLabelValue) {
                    $newInput.val(this.newLabelValue);
                }
            })
            .on('ok', () => {
                const $oldInput = this.confirmModal.$el.find('[data-role="old-label-input"]');
                const $newInput = this.confirmModal.$el.find('[data-role="new-label-input"]');
                const $error = this.confirmModal.$el.find('[data-role="rename-label-error"]');
                const oldLabelValue = $.trim($oldInput.val() || '');
                const newLabelValue = $.trim($newInput.val() || '');

                if (!oldLabelValue) {
                    $error.text(__(this.messages.confirm_old_validation_error)).show();
                    $oldInput.addClass('error').trigger('focus');
                    $newInput.removeClass('error');
                    return;
                }

                if (!newLabelValue) {
                    $error.text(__(this.messages.confirm_new_validation_error)).show();
                    $newInput.addClass('error').trigger('focus');
                    $oldInput.removeClass('error');
                    return;
                }

                if (oldLabelValue === newLabelValue) {
                    $error.text(__(this.messages.confirm_same_value_error)).show();
                    $newInput.addClass('error').trigger('focus');
                    $oldInput.removeClass('error');
                    return;
                }

                this.oldLabelValue = oldLabelValue;
                this.newLabelValue = newLabelValue;
                $error.hide().empty();
                $oldInput.removeClass('error');
                $newInput.removeClass('error');
                callback();
                this.confirmModal.close();
            })
            .on('hidden', function() {
                delete this.confirmModal;
            }.bind(this));

        return this.confirmModal;
    },

    getConfirmDialogOptions: function() {
        return _.extend({}, RenameLabelPromptMassAction.__super__.getConfirmDialogOptions.call(this), {
            content: this.getConfirmContentMessage()
        });
    },

    getConfirmContentMessage: function() {
        const content = __(this.messages.confirm_content);
        const oldLabel = __(this.messages.confirm_old_input_label);
        const oldPlaceholder = __(this.messages.confirm_old_input_placeholder);
        const newLabel = __(this.messages.confirm_new_input_label);
        const newPlaceholder = __(this.messages.confirm_new_input_placeholder);

        return '' +
            '<div class="sq-mass-action-rename-label-form" style="max-width:520px;">' +
                '<p style="margin:0 0 18px;">' + _.escape(content) + '</p>' +
                '<div style="display:flex;flex-direction:column;gap:12px;">' +
                '<div class="control-group" style="margin:0;">' +
                    '<div class="controls" style="display:flex;align-items:center;margin-left:0;">' +
                        '<label class="control-label" ' +
                            'style="width:110px;flex:0 0 110px;margin:0;padding-top:0;text-align:left;" ' +
                            'for="sq-mass-action-old-label-input">' +
                            _.escape(oldLabel) +
                        '</label>' +
                        '<input id="sq-mass-action-old-label-input" ' +
                            'data-role="old-label-input" ' +
                            'type="text" ' +
                            'class="input input-large" ' +
                            'style="width:260px;margin:0;" ' +
                            'placeholder="' + _.escape(oldPlaceholder) + '" ' +
                            'autocomplete="off">' +
                    '</div>' +
                '</div>' +
                '<div class="control-group" style="margin:0;">' +
                    '<div class="controls" style="display:flex;align-items:center;margin-left:0;">' +
                        '<label class="control-label" ' +
                            'style="width:110px;flex:0 0 110px;margin:0;padding-top:0;text-align:left;" ' +
                            'for="sq-mass-action-new-label-input">' +
                            _.escape(newLabel) +
                        '</label>' +
                        '<input id="sq-mass-action-new-label-input" ' +
                            'data-role="new-label-input" ' +
                            'type="text" ' +
                            'class="input input-large" ' +
                            'style="width:260px;margin:0;" ' +
                            'placeholder="' + _.escape(newPlaceholder) + '" ' +
                            'autocomplete="off">' +
                    '</div>' +
                '</div>' +
                '</div>' +
                '<div data-role="rename-label-error" class="validation-failed" ' +
                    'style="display:none;margin-top:12px;"></div>' +
            '</div>';
    }
});

export default RenameLabelPromptMassAction;
