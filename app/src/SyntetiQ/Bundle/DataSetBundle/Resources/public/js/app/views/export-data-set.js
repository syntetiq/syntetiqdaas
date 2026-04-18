import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import Modal from 'oroui/js/modal';
import messenger from 'oroui/js/messenger';

const ExportDataSet = BaseView.extend({
    dataSet: null,

    exportTags: null,

    queueUrl: null,

    constructor: function ExportDataSet(options) {
        ExportDataSet.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        ExportDataSet.__super__.initialize.call(this, options);
        this.dataSet = options.dataSet;
        this.queueUrl = options.queueUrl || '';
        this.exportTags = Array.isArray(options.exportTags) ? options.exportTags : [];

        $(document).ready(() => {
            $('#export-data-set-button').on('click', () => {
                this.openSelectionModal();
            });
        });
    },

    openSelectionModal: function() {
        let isSubmitting = false;
        const content = this.getSelectionModalContent();
        const modal = new Modal({
            title: 'Export Data Set',
            content: content,
            cancelText: 'Cancel',
            okText: 'OK',
            okCloses: false
        });

        const applySelectionModalLayout = () => {
            const $dialog = modal.$el.find('.modal-dialog');
            const $form = modal.$el.find('.export-data-set-form');
            const $tags = modal.$el.find('.export-tag-options');

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

            isSubmitting = true;
            this.setModalButtonsState(modal, true);
            this.queueExport(this.getSelectedTags(modal), modal)
                .always(() => {
                    isSubmitting = false;
                    this.setModalButtonsState(modal, false);
                });
        });

        setTimeout(applySelectionModalLayout, 0);
    },

    getSelectionModalContent: function() {
        const helpText = this.exportTags.length
            ? 'Select tags to limit export. Leave all tags unchecked to export the full dataset.'
            : 'This dataset has no tags yet. The full dataset will be exported.';
        const tagMarkup = this.exportTags.length
            ? this.exportTags.map((tag, index) => `
                <label class="checkbox" style="display: block; margin: 0 0 8px;">
                    <input type="checkbox" class="export-tag-checkbox" data-tag-index="${index}">
                    <span style="margin-left: 8px;">${this.escapeHtml(tag)}</span>
                </label>
            `).join('')
            : '<div class="text-muted">No tags available.</div>';

        return `
            <div class="export-data-set-form" style="padding: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px;">Tags to Export</label>
                    <div style="margin-bottom: 12px; color: #6b7280;">${helpText}</div>
                    <div class="export-tag-options" style="border: 1px solid #dcdcdc; border-radius: 4px; padding: 12px; background: #fff;">
                        ${tagMarkup}
                    </div>
                </div>
            </div>
        `;
    },

    getSelectedTags: function(modal) {
        return modal.$el.find('.export-tag-checkbox:checked').get()
            .map(checkbox => {
                const index = Number($(checkbox).data('tagIndex'));

                return this.exportTags[index];
            })
            .filter(tag => typeof tag === 'string' && tag !== '');
    },

    queueExport: function(exportTags, modal) {
        if (!this.queueUrl) {
            messenger.notificationMessage('error', 'Dataset export route is not configured.');

            return $.Deferred().reject().promise();
        }

        return $.ajax({
            url: this.queueUrl,
            method: 'POST',
            data: {
                exportTags: exportTags
            }
        }).done(response => {
            modal.close();
            messenger.notificationMessage(
                'success',
                response && response.message ? response.message : 'Dataset export has been queued.'
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

        return 'Failed to queue dataset export.';
    },

    escapeHtml: function(value) {
        return $('<div>').text(value).html();
    }
});

export default ExportDataSet;
