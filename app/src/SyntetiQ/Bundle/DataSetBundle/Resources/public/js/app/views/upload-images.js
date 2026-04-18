import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import Modal from 'oroui/js/modal';
import messenger from 'oroui/js/messenger';

const routing = require('routing');


const UploadImage = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['emptyFileSelector', 'fileSelector', 'isExternalFile']),

    dataSet: null,

    /**
     * @inheritdoc
     */
    constructor: function UploadImage(options) {
        UploadImage.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function (options) {
        UploadImage.__super__.initialize.call(this, options);
        this.dataSet = options.dataSet;
        var self = this;

        $(document).ready(function () {
            $('#upload-images-button').on('click', function () {
                const content = `
                    <div class="upload-images-form" style="padding: 20px;">
                        <p style="margin: 0 0 16px;">Do you want to upload a whole folder or specific files?</p>
                        <div class="form-group" style="margin-top: 16px;">
                            <label style="display: block; margin-bottom: 8px;">Tag (optional)</label>
                            <input type="text" class="form-control upload-images-tag-input" maxlength="255">
                        </div>
                        <div class="upload-images-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <button class="btn btn-primary upload-folder-btn">Upload Folder</button>
                            <button class="btn btn-primary upload-files-btn">Upload Files</button>
                        </div>
                    </div>
                `;

                const modal = new Modal({
                    title: 'Upload Images',
                    content: content,
                    cancelText: 'Cancel',
                    buttons: {} // Clear default buttons if any
                });

                const applySelectionModalLayout = () => {
                    const $dialog = modal.$el.find('.modal-dialog');
                    const $form = modal.$el.find('.upload-images-form');
                    const $tagInput = modal.$el.find('.upload-images-tag-input');
                    const $actions = modal.$el.find('.upload-images-actions');

                    $dialog.css({
                        width: '560px',
                        maxWidth: '560px'
                    });

                    $form.css({
                        maxWidth: '520px',
                        margin: '0 auto'
                    });

                    $tagInput.addClass('full').css('width', '100%');

                    $actions.find('.btn').css({
                        minWidth: '120px'
                    });
                };

                modal.on('shown', applySelectionModalLayout);
                modal.open();
                setTimeout(applySelectionModalLayout, 0);

                // Bind events to the buttons inside the modal
                // We use delegation on modal.$el to be safe
                modal.$el.on('click', '.upload-folder-btn', function () {
                    const tag = $.trim(modal.$el.find('.upload-images-tag-input').val() || '');
                    self._initiateUpload(true, tag);
                    modal.close();
                });

                modal.$el.on('click', '.upload-files-btn', function () {
                    const tag = $.trim(modal.$el.find('.upload-images-tag-input').val() || '');
                    self._initiateUpload(false, tag);
                    modal.close();
                });
            });
        });
    },

    _initiateUpload: function (isFolder, tag) {
        const self = this;
        const normalizedTag = $.trim(tag || '');
        // Create input dynamically
        let inputStr = '<input type="file" multiple accept="image/*" style="display:none"';
        if (isFolder) {
            inputStr += ' webkitdirectory directory';
        }
        inputStr += '>';

        const input = $(inputStr).appendTo('body');

        input.on('change', function (e) {
            const files = Array.from(e.target.files);
            input.remove(); // Clean up input

            if (!files.length) return;

            const totalFiles = files.length;
            let processedFiles = 0;
            let activeXhrs = [];
            let isCancelled = false;

            // Concurrency Control
            const MAX_CONCURRENT_UPLOADS = 2;
            let activeUploads = 0;
            let uploadQueue = [];

            // Create Progress Modal with Circular Indicator
            const radius = 40;
            const circumference = 2 * Math.PI * radius;

            const content = `
                <div style="text-align: center; padding: 20px;">
                    <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                        <svg width="100" height="100" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                            <circle cx="50" cy="50" r="${radius}" fill="none" stroke="#e6e6e6" stroke-width="8" />
                            <circle class="progress-ring-circle" cx="50" cy="50" r="${radius}" fill="none" stroke="#28a745" stroke-width="8"
                                    stroke-dasharray="${circumference}" stroke-dashoffset="${circumference}" 
                                    style="transition: stroke-dashoffset 0.35s;" />
                        </svg>
                        <div class="progress-text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 14px;">
                            0%
                        </div>
                    </div>
                    <div class="output-status" style="margin-top: 15px; font-weight: 500;">Preparing to upload ${totalFiles} files...</div>
                </div>
            `;

            const progressModal = new Modal({
                title: 'Uploading Images...',
                content: content,
                cancelText: 'Stop Upload',
                allowClose: false
            });
            progressModal.open();

            // Handle Cancellation
            progressModal.on('cancel', function () {
                isCancelled = true;
                activeXhrs.forEach(xhr => xhr.abort());
                messenger.notificationMessage('warning', 'Upload cancelled.');
                setTimeout(function () {
                    location.reload();
                }, 1000);
            });

            const $progressCircle = progressModal.$el.find('.progress-ring-circle');
            const $progressTextPercentage = progressModal.$el.find('.progress-text');
            const $statusText = progressModal.$el.find('.output-status');

            function setProgress(percent) {
                const offset = circumference - (percent / 100) * circumference;
                $progressCircle.css('stroke-dashoffset', offset);
                $progressTextPercentage.text(percent + '%');
            }

            function checkCompletion() {
                if (isCancelled) return;
                if (processedFiles >= totalFiles) {
                    $statusText.text('Upload Complete!');
                    setProgress(100);

                    progressModal.$el.find('.btn-close').hide();
                    const $cancelBtn = progressModal.$el.find('button.cancel');
                    if ($cancelBtn.length) {
                        $cancelBtn.prop('disabled', true).text('Completed');
                    } else {
                        progressModal.$el.find('.modal-footer button').prop('disabled', true).text('Completed');
                    }

                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                }
            }

            const BATCH_SIZE = 5;

            function processQueue() {
                if (isCancelled) return;

                while (activeUploads < MAX_CONCURRENT_UPLOADS && uploadQueue.length > 0) {
                    const batch = uploadQueue.shift();
                    activeUploads++;
                    uploadBatch(batch);
                }
            }

            function uploadBatch(batch) {
                if (isCancelled) return;

                const formData = new FormData();
                batch.forEach(file => {
                    const relativePath = file.webkitRelativePath || file.name;
                    formData.append('files[]', file, relativePath);
                });
                if (normalizedTag !== '') {
                    formData.append('tag', normalizedTag);
                }

                const xhr = $.ajax({
                    url: routing.generate('syntetiq_data_set_import_images', { id: self.dataSet }),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: res => {
                        if (isCancelled) return;
                        processedFiles += batch.length;
                        const percent = Math.min(100, Math.round((processedFiles / totalFiles) * 100));
                        setProgress(percent);
                        $statusText.text('Uploaded ' + processedFiles + ' of ' + totalFiles + ' files');
                        checkCompletion();
                    },
                    error: (xhr, status, error) => {
                        if (status === 'abort') return;
                        console.error('Batch error', error);
                        processedFiles += batch.length;
                        const percent = Math.min(100, Math.round((processedFiles / totalFiles) * 100));
                        setProgress(percent);
                        checkCompletion();
                    },
                    complete: () => {
                        activeUploads--;
                        processQueue();
                    }
                });
                activeXhrs.push(xhr);
            }

            for (let i = 0; i < files.length; i += BATCH_SIZE) {
                const batch = files.slice(i, i + BATCH_SIZE);
                uploadQueue.push(batch);
            }

            // Kick off uploads
            processQueue();
        });

        input.trigger('click');
    },


    dispose: function () {
        if (this.disposed) {
            return;
        }

        UploadImage.__super__.dispose.call(this);
    }
});

export default UploadImage;
