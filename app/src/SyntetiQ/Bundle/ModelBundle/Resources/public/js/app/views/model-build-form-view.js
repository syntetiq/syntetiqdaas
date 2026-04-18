import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';

const ModelBuildFormView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['emptyFileSelector', 'fileSelector', 'isExternalFile']),

    /**
     * @inheritdoc
     */
    constructor: function ModelBuildFormView(options) {
        ModelBuildFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function (options) {
        ModelBuildFormView.__super__.initialize.call(this, options);

        var hiddenObjectInfo = JSON.parse($('.hidden-engines-info').val());
        var engineSelector = $('select[name="syntetiq_model_type_model_build[engine]"]');
        var engineModelSelector = $('select[name="syntetiq_model_type_model_build[engineModel]"]');
        var pretrainedModelSelector = $('select[name="syntetiq_model_type_model_build[pretrainedModel]"]');
        var pretrainedModelOptions = pretrainedModelSelector.find('option').map(function () {
            return $(this).clone();
        }).get();

        var refreshPretrainedModelWidget = function () {
            if (typeof pretrainedModelSelector.inputWidget === 'function') {
                pretrainedModelSelector.inputWidget('refresh');
            }

            pretrainedModelSelector.trigger('change');
        };

        var filterPretrainedModelOptions = function () {
            if (!pretrainedModelSelector.length) {
                return;
            }

            var selectedEngine = engineSelector.val();
            var selectedEngineModel = engineModelSelector.val();
            var currentValue = pretrainedModelSelector.val();
            var matchedValue = '';

            pretrainedModelSelector.empty();

            $.each(pretrainedModelOptions, function (_, originalOption) {
                var option = $(originalOption).clone();
                var optionValue = option.val();

                if (!optionValue) {
                    pretrainedModelSelector.append(option);
                    return;
                }

                var isCompatible = option.data('engine') === selectedEngine &&
                    option.data('engineModel') === selectedEngineModel;

                if (!isCompatible) {
                    return;
                }

                if (optionValue === currentValue) {
                    matchedValue = optionValue;
                }

                pretrainedModelSelector.append(option);
            });

            pretrainedModelSelector.val(matchedValue);
            refreshPretrainedModelWidget();
        };

        engineSelector.on('change', function () {
            var selectedEngine = $(this).val();
            var currentEngineModel = engineModelSelector.val();
            var firstEl = null;
            var currentValueExists = false;

            engineModelSelector.empty();

            for (var key in hiddenObjectInfo) {
                if (key === selectedEngine) {
                    for (var i in hiddenObjectInfo[key].models) {
                        var modelName = hiddenObjectInfo[key].models[i];

                        if (!firstEl) {
                            firstEl = modelName;
                        }

                        if (modelName === currentEngineModel) {
                            currentValueExists = true;
                        }

                        engineModelSelector.append($('<option>', {
                            value: modelName,
                            text: modelName
                        }));
                    }
                }
            }

            engineModelSelector.val(currentValueExists ? currentEngineModel : firstEl);
            engineModelSelector.trigger('change');
        });

        engineModelSelector.on('change', function () {
            filterPretrainedModelOptions();
        });
        filterPretrainedModelOptions();

    },

    dispose: function () {
        if (this.disposed) {
            return;
        }

        ModelBuildFormView.__super__.dispose.call(this);
    }
});

export default ModelBuildFormView;
