import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import Modal from 'oroui/js/modal';
import messenger from 'oroui/js/messenger';

const routing = require('routing');
const Resumable = require('../../lib/resumable');

const UploadArchive = BaseView.extend({
    dataSet: null,

    constructor: function UploadArchive(options) {
        UploadArchive.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        UploadArchive.__super__.initialize.call(this, options);
        this.dataSet = options.dataSet;

        $(document).ready(() => {
            $('#upload-archive-button').on('click', () => {
                this.openSelectionModal();
            });
        });
    },

    openSelectionModal: function() {
        const content = `
            <div class="archive-upload-form" style="padding: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px;">ZIP archive</label>
                    <input
                        type="file"
                        class="archive-file-input"
                        accept=".zip,application/zip"
                        style="display: block; width: 100%;"
                    >
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label style="display: block; margin-bottom: 8px;">Tag (optional)</label>
                    <input type="text" class="form-control archive-tag-input" maxlength="255">
                </div>
            </div>
        `;

        const modal = new Modal({
            title: 'Import Data Set',
            content: content,
            cancelText: 'Cancel',
            okText: 'OK',
            okCloses: false
        });

        const applySelectionModalLayout = () => {
            const $dialog = modal.$el.find('.modal-dialog');
            const $form = modal.$el.find('.archive-upload-form');
            const $fileWidget = modal.$el.find('.archive-file-input').closest('.uploader');
            const $tagInput = modal.$el.find('.archive-tag-input');

            $dialog.css({
                width: '560px',
                maxWidth: '560px'
            });

            $form.css({
                maxWidth: '520px',
                margin: '0 auto'
            });

            $tagInput.addClass('full').css('width', '100%');

            if ($fileWidget.length) {
                $fileWidget.css({
                    width: '100%',
                    maxWidth: '100%'
                });

                $fileWidget.find('.filename').css({
                    flex: '1 1 auto',
                    minWidth: '0'
                });
            }
        };

        modal.on('shown', applySelectionModalLayout);
        modal.$el.on('input-widget:init input-widget:refresh', '.archive-file-input', applySelectionModalLayout);

        modal.open(() => {
            const fileInput = modal.$el.find('.archive-file-input').get(0);
            const file = fileInput && fileInput.files ? fileInput.files[0] : null;
            const tag = $.trim(modal.$el.find('.archive-tag-input').val() || '');

            if (!file) {
                messenger.notificationMessage('error', 'Choose a ZIP archive first.');
                return;
            }

            if (!/\.zip$/i.test(file.name)) {
                messenger.notificationMessage('error', 'Only ZIP archives are supported.');
                return;
            }

            if (file.size <= 0) {
                messenger.notificationMessage('error', 'The selected archive is empty.');
                return;
            }

            modal.close();
            this.uploadArchive(file, tag);
        });

        setTimeout(applySelectionModalLayout, 0);
    },

    uploadArchive: function(file, tag) {
        const chunkSize = 20 * 1024 * 1024;
        const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
        const radius = 40;
        const circumference = 2 * Math.PI * radius;
        const chunkUrl = routing.generate('syntetiq_data_set_import_archive_chunk', {id: this.dataSet});
        const completeUrl = routing.generate('syntetiq_data_set_import_archive_complete', {id: this.dataSet});
        const uploadSessionId = this.generateUploadSessionId();
        let isCancelled = false;
        let isFinalizing = false;

        const content = `
            <div style="text-align: center; padding: 20px;">
                <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                    <svg width="100" height="100" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                        <circle cx="50" cy="50" r="${radius}" fill="none" stroke="#e6e6e6" stroke-width="8" />
                        <circle
                                class="progress-ring-circle"
                                cx="50"
                                cy="50"
                                r="${radius}"
                                fill="none"
                                stroke="#28a745"
                                stroke-width="8"
                                stroke-dasharray="${circumference}"
                                stroke-dashoffset="${circumference}"
                                style="transition: stroke-dashoffset 0.35s;"
                        />
                    </svg>
                    <div
                            class="progress-text"
                            style="position: absolute; top: 50%; left: 50%;
                                transform: translate(-50%, -50%); font-weight: bold; font-size: 14px;"
                    >
                        0%
                    </div>
                </div>
                <div class="output-status" style="margin-top: 15px; font-weight: 500;">Preparing upload...</div>
                <div
                        class="upload-error"
                        style="display: none; margin-top: 15px; color: #c0392b; font-weight: 500;"
                ></div>
            </div>
        `;

        const progressModal = new Modal({
            title: 'Uploading Archive...',
            content: content,
            cancelText: 'Stop Upload',
            allowClose: false
        });

        progressModal.open();

        const $progressCircle = progressModal.$el.find('.progress-ring-circle');
        const $progressText = progressModal.$el.find('.progress-text');
        const $statusText = progressModal.$el.find('.output-status');
        const $errorText = progressModal.$el.find('.upload-error');

        const setProgress = percent => {
            const offset = circumference - (percent / 100) * circumference;
            $progressCircle.css('stroke-dashoffset', offset);
            $progressText.text(percent + '%');
        };

        const setCompletedState = message => {
            $statusText.text(message);
            setProgress(100);
            $errorText.hide().text('');

            setTimeout(() => {
                progressModal.close();
                window.location.reload();
            }, 300);
        };

        const extractErrorMessage = (message, fallbackMessage) => {
            if (message && typeof message === 'object' && message.message) {
                return message.message;
            }

            if (typeof message === 'string' && message !== '') {
                try {
                    const parsed = JSON.parse(message);
                    if (parsed && parsed.message) {
                        return parsed.message;
                    }
                } catch (error) {
                    return message;
                }

                return message;
            }

            return fallbackMessage;
        };

        const handleFailure = message => {
            if (isCancelled) {
                return;
            }

            $statusText.text(message);
            $errorText.text(message).show();
            messenger.notificationMessage('error', message);
        };

        const resumable = new Resumable({
            target: chunkUrl,
            testTarget: chunkUrl,
            chunkSize: chunkSize,
            forceChunkSize: true,
            simultaneousUploads: 1,
            fileParameterName: 'chunk',
            query: () => ({
                tag: tag
            }),
            generateUniqueIdentifier: resumableFile => {
                const fileName = resumableFile && resumableFile.name ? resumableFile.name : 'archive.zip';

                return uploadSessionId + '-' + fileName.replace(/[^0-9a-zA-Z_-]/g, '');
            },
            maxFiles: 1,
            fileType: ['zip'],
            maxFilesErrorCallback: () => {
                handleFailure('Only one archive can be uploaded at a time.');
            },
            minFileSizeErrorCallback: () => {
                handleFailure('The selected archive is empty.');
            },
            fileTypeErrorCallback: fileToReject => {
                const fileName = fileToReject && fileToReject.name ? fileToReject.name : 'The selected file';
                handleFailure(fileName + ' is not a ZIP archive.');
            },
            testChunks: true,
            maxChunkRetries: 3,
            chunkRetryInterval: 2000
        });

        if (!resumable.support) {
            handleFailure('Resumable upload is not supported in this browser.');
            return;
        }

        progressModal.on('cancel', () => {
            isCancelled = true;
            resumable.cancel();
            messenger.notificationMessage('warning', 'Upload cancelled.');
        });

        resumable.on('fileAdded', () => {
            if (isCancelled) {
                return;
            }

            $errorText.hide().text('');
            $statusText.text('Starting upload...');
            resumable.upload();
        });

        resumable.on('fileProgress', resumableFile => {
            const percent = Math.min(100, Math.round(resumableFile.progress() * 100));
            setProgress(percent);
            $errorText.hide().text('');
            $statusText.text('Uploading ' + resumableFile.fileName + '...');
        });

        resumable.on('fileRetry', () => {
            $errorText.hide().text('');
            $statusText.text('Retrying failed chunk...');
        });

        resumable.on('fileError', (resumableFile, message) => {
            handleFailure(extractErrorMessage(message, 'Archive upload failed.'));
        });

        resumable.on('fileSuccess', resumableFile => {
            if (isCancelled || isFinalizing) {
                return;
            }

            isFinalizing = true;
            $errorText.hide().text('');
            $statusText.text('Combining archive on server...');

            $.ajax({
                url: completeUrl,
                method: 'POST',
                data: {
                    uploadId: resumableFile.uniqueIdentifier,
                    totalChunks: totalChunks,
                    fileName: file.name,
                    tag: tag
                },
                success: response => {
                    setCompletedState(response.message || 'Import started successfully');
                    messenger.notificationMessage(
                        'success',
                        response.message || 'Import started successfully'
                    );
                },
                error: xhr => {
                    isFinalizing = false;
                    const message = xhr && xhr.responseJSON
                        ? extractErrorMessage(xhr.responseJSON, 'Unable to finalize archive upload.')
                        : 'Unable to finalize archive upload.';
                    handleFailure(message);
                }
            });
        });

        resumable.addFile(file);

        if (!resumable.files || !resumable.files.length) {
            handleFailure('Unable to queue the archive for upload.');
        }
    },

    generateUploadSessionId: function() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'upload-' + Date.now() + '-' + Math.random().toString(36).slice(2, 12);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        UploadArchive.__super__.dispose.call(this);
    }
});

export default UploadArchive;
