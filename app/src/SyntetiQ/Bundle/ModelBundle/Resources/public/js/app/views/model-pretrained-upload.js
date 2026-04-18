import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import Modal from 'oroui/js/modal';
import messenger from 'oroui/js/messenger';

const routing = require('routing');

const ModelPretrainedUploadView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['modelId', 'engines']),

    modelId: null,

    engines: null,

    constructor: function ModelPretrainedUploadView(options) {
        ModelPretrainedUploadView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        ModelPretrainedUploadView.__super__.initialize.call(this, options);

        this.modelId = options.modelId;
        this.engines = options.engines || {};

        $(document).ready(() => {
            $('#upload-pretrained-model-button').on('click', () => {
                this.openUploadModal();
            });
        });
    },

    openUploadModal: function() {
        const engineKeys = Object.keys(this.engines);
        const firstEngineKey = engineKeys[0] || '';
        const engineOptions = engineKeys.map(engineKey => {
            const selected = engineKey === firstEngineKey ? 'selected' : '';

            return `<option value="${this.escapeHtml(engineKey)}" ${selected}>${this.escapeHtml(engineKey)}</option>`;
        }).join('');

        const content = `
            <style>
                .model-pretrained-upload-form {
                    padding: 24px 28px 12px;
                }

                .model-pretrained-upload-form__row {
                    display: grid;
                    grid-template-columns: 130px minmax(0, 1fr);
                    align-items: center;
                    column-gap: 20px;
                    row-gap: 8px;
                    margin-bottom: 18px;
                }

                .model-pretrained-upload-form__label {
                    margin: 0;
                    font-weight: 600;
                }

                .model-pretrained-upload-form__control {
                    min-width: 0;
                    max-width: 640px;
                }

                .model-pretrained-upload-form__control .form-control {
                    width: 100%;
                }

                .model-pretrained-upload-form__control input[type="file"] {
                    padding-right: 0;
                }

                .model-pretrained-upload-form__help {
                    margin: 6px 0 0;
                }

                .model-pretrained-upload-form__error {
                    margin-left: 150px;
                    max-width: 640px;
                }

                @media (max-width: 767px) {
                    .model-pretrained-upload-form {
                        padding: 20px 20px 10px;
                    }

                    .model-pretrained-upload-form__row {
                        grid-template-columns: minmax(0, 1fr);
                        row-gap: 6px;
                    }

                    .model-pretrained-upload-form__error {
                        margin-left: 0;
                    }
                }
            </style>
            <form class="model-pretrained-upload-form">
                <div class="model-pretrained-upload-form__row">
                    <label class="model-pretrained-upload-form__label" for="model-pretrained-upload-name">Name</label>
                    <div class="model-pretrained-upload-form__control">
                        <input id="model-pretrained-upload-name" name="name" type="text" class="form-control" placeholder="Optional display name">
                    </div>
                </div>
                <div class="model-pretrained-upload-form__row">
                    <label class="model-pretrained-upload-form__label" for="model-pretrained-upload-engine">Engine</label>
                    <div class="model-pretrained-upload-form__control">
                        <select id="model-pretrained-upload-engine" name="engine" class="form-control">${engineOptions}</select>
                    </div>
                </div>
                <div class="model-pretrained-upload-form__row">
                    <label class="model-pretrained-upload-form__label" for="model-pretrained-upload-engine-model">Model Type</label>
                    <div class="model-pretrained-upload-form__control">
                        <select id="model-pretrained-upload-engine-model" name="engineModel" class="form-control">${this.renderModelOptions(firstEngineKey)}</select>
                    </div>
                </div>
                <div class="model-pretrained-upload-form__row">
                    <label class="model-pretrained-upload-form__label" for="model-pretrained-upload-file">Checkpoint File</label>
                    <div class="model-pretrained-upload-form__control">
                        <input id="model-pretrained-upload-file" name="file" type="file" class="form-control" accept=".pt,.pth">
                        <div class="help-block model-pretrained-upload-form__help">YOLO uses <code>.pt</code>, SSD uses <code>.pth</code>.</div>
                    </div>
                </div>
                <div class="upload-error text-danger model-pretrained-upload-form__error" style="display: none;"></div>
            </form>
        `;

        const modal = new Modal({
            title: 'Upload Pretrained Model',
            content: content,
            okText: 'Upload',
            cancelText: 'Cancel'
        });

        modal.open();

        const $form = modal.$el.find('.model-pretrained-upload-form');
        const $engine = $form.find('[name="engine"]');
        const $engineModel = $form.find('[name="engineModel"]');
        const $file = $form.find('[name="file"]');
        const $error = $form.find('.upload-error');

        $engine.on('change', () => {
            $engineModel.html(this.renderModelOptions($engine.val()));
        });

        modal.on('ok', () => {
            const file = $file[0].files[0];
            if (!file) {
                $error.text('Choose a checkpoint file to upload.').show();

                return false;
            }

            const formData = new FormData();
            formData.append('name', ($form.find('[name="name"]').val() || '').toString().trim());
            formData.append('engine', $engine.val());
            formData.append('engineModel', $engineModel.val());
            formData.append('file', file);

            modal.$el.find('button.ok').prop('disabled', true).text('Uploading...');
            $error.hide().text('');

            $.ajax({
                url: routing.generate('syntetiq_model_model_upload_pretrained', {id: this.modelId}),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(() => {
                messenger.notificationMessage('success', 'Pretrained model uploaded.');
                modal.close();
                window.location.reload();
            }).fail(xhr => {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Unable to upload pretrained model.';

                $error.text(message).show();
                modal.$el.find('button.ok').prop('disabled', false).text('Upload');
            });

            return false;
        });
    },

    renderModelOptions: function(engineKey) {
        const engine = this.engines[engineKey] || {};
        const models = engine.models || [];

        return models.map(modelName =>
            `<option value="${this.escapeHtml(modelName)}">${this.escapeHtml(modelName)}</option>`
        ).join('');
    },

    escapeHtml: function(value) {
        return $('<div>').text(value || '').html();
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        ModelPretrainedUploadView.__super__.dispose.call(this);
    }
});

export default ModelPretrainedUploadView;
