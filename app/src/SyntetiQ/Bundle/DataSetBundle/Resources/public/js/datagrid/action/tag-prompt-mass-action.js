import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import MassAction from 'oro/datagrid/action/mass-action';

const TagPromptMassAction = MassAction.extend({
    tagValue: '',

    /**
     * @inheritdoc
     */
    constructor: function TagPromptMassAction(options) {
        TagPromptMassAction.__super__.constructor.call(this, options);
    },

    getActionParameters: function () {
        return _.extend({}, TagPromptMassAction.__super__.getActionParameters.call(this), {
            tagValue: this.tagValue
        });
    },

    getConfirmDialog: function (callback) {
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
                    const $input = this.confirmModal.$el.find('[data-role="tag-input"]');
                    const $error = this.confirmModal.$el.find('[data-role="tag-input-error"]');

                    $input.on('input', () => {
                        $error.hide().empty();
                        $input.removeClass('error');
                    });
                    $input.trigger('focus');
                    if (this.tagValue) {
                        $input.val(this.tagValue);
                        const input = $input.get(0);
                        if (input && typeof input.setSelectionRange === 'function') {
                            input.setSelectionRange(this.tagValue.length, this.tagValue.length);
                        }
                    }
                })
                .on('ok', () => {
                    const $input = this.confirmModal.$el.find('[data-role="tag-input"]');
                    const $error = this.confirmModal.$el.find('[data-role="tag-input-error"]');
                    const tagValue = $.trim($input.val() || '');

                if (!tagValue) {
                    $error.text(__(this.messages.confirm_validation_error)).show();
                    $input.addClass('error');
                    $input.trigger('focus');
                    return;
                }

                this.tagValue = tagValue;
                $error.hide().empty();
                $input.removeClass('error');
                callback();
                this.confirmModal.close();
            })
            .on('hidden', function () {
                delete this.confirmModal;
            }.bind(this));

        return this.confirmModal;
    },

    getConfirmDialogOptions: function () {
        return _.extend({}, TagPromptMassAction.__super__.getConfirmDialogOptions.call(this), {
            content: this.getConfirmContentMessage()
        });
    },

    getConfirmContentMessage: function () {
        const content = __(this.messages.confirm_content);
        const label = __(this.messages.confirm_input_label);
        const placeholder = __(this.messages.confirm_input_placeholder);

            return '' +
                '<div class="sq-mass-action-tag-form" style="max-width:520px;">' +
                    '<p style="margin:0 0 18px;">' + _.escape(content) + '</p>' +
                    '<div class="control-group" style="margin:0;">' +
                        '<div class="controls" style="display:flex;align-items:center;margin-left:0;">' +
                            '<label class="control-label" ' +
                                'style="width:110px;flex:0 0 110px;margin:0;padding-top:0;text-align:left;" ' +
                                'for="sq-mass-action-tag-input">' +
                                _.escape(label) +
                            '</label>' +
                            '<input id="sq-mass-action-tag-input" ' +
                                'data-role="tag-input" ' +
                                'type="text" ' +
                                'class="input input-large" ' +
                                'style="width:260px;margin:0;" ' +
                                'placeholder="' + _.escape(placeholder) + '" ' +
                                'autocomplete="off">' +
                        '</div>' +
                    '</div>' +
                    '<div data-role="tag-input-error" class="validation-failed" ' +
                        'style="display:none;margin-top:12px;"></div>' +
                '</div>';
        }
    });

export default TagPromptMassAction;
