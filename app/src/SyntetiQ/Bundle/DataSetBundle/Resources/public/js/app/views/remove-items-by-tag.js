import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import Modal from 'oroui/js/modal';
import messenger from 'oroui/js/messenger';

const RemoveItemsByTag = BaseView.extend({
    dataSet: null,

    exportTags: null,

    queueUrl: null,

    constructor: function RemoveItemsByTag(options) {
        RemoveItemsByTag.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        RemoveItemsByTag.__super__.initialize.call(this, options);
        this.dataSet = options.dataSet;
        this.queueUrl = options.queueUrl || '';
        this.exportTags = Array.isArray(options.exportTags) ? options.exportTags : [];

        $(document).ready(() => {
            $('#remove-items-by-tag-button').on('click', () => {
                this.openSelectionModal();
            });
        });
    },

    openSelectionModal: function() {
        let isSubmitting = false;
        const content = this.getSelectionModalContent();
        const modal = new Modal({
            title: 'Remove Items by Tag',
            content: content,
            cancelText: 'Cancel',
            okText: 'Remove Items',
            okButtonClass: 'btn btn-danger',
            okCloses: false
        });

        const applySelectionModalLayout = () => {
            const $dialog = modal.$el.find('.modal-dialog');
            const $form = modal.$el.find('.remove-items-by-tag-form');
            const $tags = modal.$el.find('.remove-tag-options');

            $dialog.css({
                width: '560px',
                maxWidth: '560px'
            });

            $form.css({
                maxWidth: '520px',
                margin: '0 auto'
            });

            $tags.css({
                maxHeight: '240px',
                overflowY: 'auto'
            });
        };

        modal.on('shown', applySelectionModalLayout);
        modal.open(() => {
            if (isSubmitting) {
                return;
            }

            const selectedTags = this.getSelectedTags(modal);
            if (selectedTags.length === 0) {
                if (!confirm("No tags selected. This will remove ALL items from this dataset. Are you sure?")) {
                    return;
                }
            }

            isSubmitting = true;
            this.setModalButtonsState(modal, true);
            this.queueRemove(selectedTags, modal)
                .always(() => {
                    isSubmitting = false;
                    this.setModalButtonsState(modal, false);
                });
        });

        setTimeout(applySelectionModalLayout, 0);
    },

    getSelectionModalContent: function() {
        const helpText = this.exportTags.length
            ? 'Select tags to remove specific items. <strong>Leave all tags unchecked to remove ALL items from the dataset.</strong>'
            : 'This dataset has no tags yet. <strong>ALL items will be removed.</strong>';
        const tagMarkup = this.exportTags.length
            ? this.exportTags.map((tag, index) => `
                <label class="checkbox" style="display: block; margin: 0 0 8px;">
                    <input type="checkbox" class="remove-tag-checkbox" data-tag-index="${index}">
                    <span style="margin-left: 8px;">${this.escapeHtml(tag)}</span>
                </label>
            `).join('')
            : '<div class="text-muted">No tags available.</div>';

        return `
            <div class="remove-items-by-tag-form" style="padding: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Tags to Remove</label>
                    <div class="alert alert-warning" style="margin-bottom: 12px;">${helpText}</div>
                    <div class="remove-tag-options" style="border: 1px solid #dcdcdc; border-radius: 4px; padding: 12px; background: #fff;">
                        ${tagMarkup}
                    </div>
                </div>
            </div>
        `;
    },

    getSelectedTags: function(modal) {
        return modal.$el.find('.remove-tag-checkbox:checked').get()
            .map(checkbox => {
                const index = Number($(checkbox).data('tagIndex'));

                return this.exportTags[index];
            })
            .filter(tag => typeof tag === 'string' && tag !== '');
    },

    queueRemove: function(tags, modal) {
        if (!this.queueUrl) {
            messenger.notificationMessage('error', 'Dataset remove route is not configured.');

            return $.Deferred().reject().promise();
        }

        return $.ajax({
            url: this.queueUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                tags: tags
            })
        }).done(response => {
            modal.close();
            messenger.notificationMessage(
                'success',
                response && response.message ? response.message : 'Items are set to be removed in the background.'
            );
        }).fail(xhr => {
            messenger.notificationMessage('error', this.extractErrorMessage(xhr));
        });
    },

    setModalButtonsState: function(modal, disabled) {
        if (!modal || !modal.$el || !modal.$el.length) {
            return;
        }

        modal.$el.find('.modal-footer button').prop('disabled', disabled);
    },

    extractErrorMessage: function(xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        return 'Failed to queue dataset items removal.';
    },

    escapeHtml: function(value) {
        return $('<div>').text(value).html();
    }
});

export default RemoveItemsByTag;
