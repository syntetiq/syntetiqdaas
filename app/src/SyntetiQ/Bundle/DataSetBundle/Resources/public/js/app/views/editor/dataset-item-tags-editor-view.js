import _ from 'underscore';
import TagsEditorView from 'orotag/js/app/views/editor/tags-editor-view';

const DataSetItemTagsEditorView = TagsEditorView.extend({
    /**
     * @inheritdoc
     */
    constructor: function DataSetItemTagsEditorView(options) {
        DataSetItemTagsEditorView.__super__.constructor.call(this, options);
    },

    parseRawValue: function (value) {
        if (Array.isArray(value)) {
            return value;
        }

        return this.normalizeTagsValue(value).map(function (tag) {
            return {
                id: tag.name,
                name: tag.name,
                owner: tag.owner
            };
        });
    },

    getInitialResultItem: function () {
        return this.getNormalizedModelTags().map(function (tag) {
            return {
                id: tag.name,
                label: tag.name,
                owner: tag.owner
            };
        });
    },

    isChanged: function () {
        if (!this.isSelect2Initialized) {
            return false;
        }

        const stringValue = _.toArray(this.getValue().sort().map(function (item) {
            return item.label;
        })).join('☕');
        const stringModelValue = _.toArray(this.getNormalizedModelTags().sort().map(function (item) {
            return item.name;
        })).join('☕');

        return stringValue !== stringModelValue;
    },

    getModelUpdateData: function () {
        const data = {};
        data[this.fieldName] = this.getValue().map(function (item) {
            return item.label;
        }).join(', ');

        return data;
    },

    getNormalizedModelTags: function () {
        return this.normalizeTagsValue(this.getModelValue());
    },

    normalizeTagsValue: function (value) {
        if (Array.isArray(value)) {
            return value
                .map(function (item) {
                    const name = _.isObject(item) ? item.name : item;

                    return {
                        name: String(name || '').trim(),
                        owner: _.isObject(item) ? item.owner === true : false
                    };
                })
                .filter(function (item) {
                    return item.name !== '';
                });
        }

        return String(value || '')
            .split(',')
            .map(function (tag) {
                return tag.trim();
            })
            .filter(function (tag) {
                return tag !== '';
            })
            .map(function (tag) {
                return {
                    name: tag,
                    owner: false
                };
            });
    }
});

export default DataSetItemTagsEditorView;
