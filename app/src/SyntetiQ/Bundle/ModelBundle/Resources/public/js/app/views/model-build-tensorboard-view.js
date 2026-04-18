import BaseView from 'oroui/js/app/views/base/view';
import Flotr from 'flotr2/flotr2.amd';

const ModelBuildTensorBoardView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'runName',
        'tagsUrl',
        'scalarsUrl',
        'isLive',
        'refreshInterval'
    ]),

    constructor: function ModelBuildTensorBoardView(options) {
        ModelBuildTensorBoardView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        ModelBuildTensorBoardView.__super__.initialize.call(this, options);

        this.graphs = [];
        this.refreshTimer = null;
        this.isLoading = false;
        this.preferredSections = ['metrics', 'train', 'val', 'lr'];
        this.isLive = Boolean(this.isLive);
        this.refreshInterval = Number.parseInt(this.refreshInterval, 10) || 15000;

        this.renderLoading();
        this.loadMetrics();
    },

    renderLoading: function() {
        this.$el.html(
            this.renderHeader() +
            '<div class="model-build-tensorboard__status">' +
                'Loading scalar metrics for this build...' +
            '</div>'
        );
    },

    renderError: function(message) {
        this.$el.html(
            this.renderHeader() +
            '<div class="model-build-tensorboard__empty">' + this.escapeHtml(message) + '</div>'
        );
    },

    loadMetrics: function() {
        if (this.isLoading || this.disposed) {
            return;
        }

        this.isLoading = true;

        fetch(this.withCacheBust(this.tagsUrl), {
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Could not load TensorBoard scalar tags.');
                }

                return response.json();
            })
            .then(tagsByRun => {
                const runTags = tagsByRun[this.runName] || {};
                const tags = Object.keys(runTags);

                if (!tags.length) {
                    this.renderError('No scalar metrics were found for this build yet.');
                    return [];
                }

                return Promise.all(tags.map(tag => this.loadSeries(tag)));
            })
            .then(results => {
                if (Array.isArray(results)) {
                    this.renderMetrics(results);
                }
            })
            .catch(() => {
                this.renderError(
                    'The build metrics panel could not load TensorBoard scalar data. ' +
                    'Use the full TensorBoard link to verify the run.'
                );
            })
            .finally(() => {
                this.isLoading = false;
                this.scheduleNextRefresh();
            });
    },

    loadSeries: function(tag) {
        const url = this.withCacheBust(
            this.scalarsUrl + '?run=' + encodeURIComponent(this.runName) + '&tag=' + encodeURIComponent(tag)
        );

        return fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Could not load scalar series.');
                }

                return response.json();
            })
            .then(events => {
                return {
                    tag: tag,
                    events: Array.isArray(events) ? events : []
                };
            })
            .catch(() => {
                return {
                    tag: tag,
                    events: []
                };
            });
    },

    renderMetrics: function(results) {
        const grouped = {};

        results.forEach(item => {
            if (!item.events.length) {
                return;
            }

            const group = this.getSectionName(item.tag);

            if (!grouped[group]) {
                grouped[group] = [];
            }

            grouped[group].push(item);
        });

        const sectionNames = Object.keys(grouped).sort((left, right) => {
            const leftPriority = this.preferredSections.indexOf(left);
            const rightPriority = this.preferredSections.indexOf(right);

            if (leftPriority !== -1 || rightPriority !== -1) {
                return (leftPriority === -1 ? 999 : leftPriority) - (rightPriority === -1 ? 999 : rightPriority);
            }

            return left.localeCompare(right);
        });

        if (!sectionNames.length) {
            this.renderError('No scalar metrics were found for this build yet.');
            return;
        }

        const html = sectionNames.map(name => {
            const cards = grouped[name]
                .sort((left, right) => left.tag.localeCompare(right.tag))
                .map(item => this.renderCard(item))
                .join('');

            return '' +
                '<section>' +
                    '<h4 class="model-build-tensorboard__section-title">' + this.escapeHtml(name) + '</h4>' +
                    '<div class="model-build-tensorboard__cards">' + cards + '</div>' +
                '</section>';
        }).join('');

        this.$el.html(
            this.renderHeader() +
            '<div class="model-build-tensorboard__sections">' + html + '</div>'
        );

        this.drawGraphs(results);
    },

    renderCard: function(item) {
        const values = item.events.map(event => Number(event[2]));
        const latest = values[values.length - 1];
        const min = Math.min.apply(null, values);
        const max = Math.max.apply(null, values);
        const lastStep = Number(item.events[item.events.length - 1][1]);
        const label = item.tag.indexOf('/') === -1 ? item.tag : item.tag.split('/').slice(1).join('/');
        const chartId = this.getChartId(item.tag);

        return '' +
            '<article class="model-build-tensorboard__card">' +
                '<h4 class="model-build-tensorboard__card-title">' + this.escapeHtml(label) + '</h4>' +
                '<div class="model-build-tensorboard__card-tag">' + this.escapeHtml(item.tag) + '</div>' +
                this.renderStatsBlock(latest, min, max, lastStep) +
                '<div id="' + this.escapeHtml(chartId) + '" class="model-build-tensorboard__chart"></div>' +
            '</article>';
    },

    renderHeader: function() {
        return '' +
            '<div class="model-build-tensorboard__header">' +
                '<div>' +
                    '<h3 class="model-build-tensorboard__title">Build Metrics</h3>' +
                    '<div class="model-build-tensorboard__meta">TensorBoard run: <strong>' +
                        this.escapeHtml(this.runName) +
                    '</strong>' +
                    (this.isLive ? ' | Auto-refreshing while training is running.' : '') +
                    '</div>' +
                '</div>' +
            '</div>';
    },

    scheduleNextRefresh: function() {
        if (!this.isLive || this.disposed) {
            return;
        }

        window.clearTimeout(this.refreshTimer);
        this.refreshTimer = window.setTimeout(() => {
            this.loadMetrics();
        }, this.refreshInterval);
    },

    renderStatsBlock: function(latest, min, max, lastStep) {
        const items = [
            {label: 'Last', value: this.formatNumber(latest)},
            {label: 'Min', value: this.formatNumber(min)},
            {label: 'Max', value: this.formatNumber(max)},
            {label: 'Step', value: lastStep}
        ];

        return '' +
            '<div class="model-build-tensorboard__stats">' +
                items.map(item => {
                    return '' +
                        '<div>' +
                            '<span class="model-build-tensorboard__stat-label">' +
                                this.escapeHtml(item.label) +
                            '</span>' +
                            '<span class="model-build-tensorboard__stat-value">' +
                                this.escapeHtml(item.value) +
                            '</span>' +
                        '</div>';
                }).join('') +
            '</div>';
    },

    drawGraphs: function(results) {
        this.graphs = [];

        results.forEach(item => {
            if (!item.events.length) {
                return;
            }

            const chartId = this.getChartId(item.tag);
            const element = document.getElementById(chartId);

            if (!element) {
                return;
            }

            const data = item.events.map(event => {
                return [Number(event[1]), Number(event[2])];
            });

            const graph = Flotr.draw(element, [{
                data: data,
                lines: {
                    show: true,
                    fill: false,
                    lineWidth: 2
                },
                points: {
                    show: data.length <= 40,
                    radius: 3,
                    fill: true
                },
                shadowSize: 0,
                color: '#ff7a00'
            }], {
                HtmlText: false,
                fontColor: '#475569',
                grid: {
                    color: '#cbd5e1',
                    backgroundColor: '#ffffff',
                    outlineWidth: 1,
                    outline: 's',
                    verticalLines: true,
                    minorVerticalLines: true,
                    horizontalLines: true
                },
                xaxis: {
                    title: 'Step',
                    color: '#64748b',
                    noTicks: 6
                },
                yaxis: {
                    title: 'Value',
                    color: '#64748b',
                    autoscaleMargin: 0.15
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: '#94a3b8',
                    trackDecimals: 4,
                    sensibility: 8,
                    trackFormatter: function(track) {
                        return 'step: ' + track.x + '<br>value: ' + track.y;
                    }
                },
                selection: {
                    mode: null
                },
                legend: {
                    show: false
                }
            });

            this.graphs.push(graph);
        });
    },

    getSectionName: function(tag) {
        if (!tag || tag.indexOf('/') === -1) {
            return 'other';
        }

        return tag.split('/')[0];
    },

    getChartId: function(tag) {
        return 'tb-chart-' + this.cid + '-' + tag.replace(/[^a-zA-Z0-9_-]+/g, '-');
    },

    formatNumber: function(value) {
        if (!Number.isFinite(value)) {
            return 'N/A';
        }

        if (Math.abs(value) >= 1000 || Math.abs(value) === 0) {
            return value.toFixed(2);
        }

        if (Math.abs(value) >= 1) {
            return value.toFixed(4).replace(/\.?0+$/, '');
        }

        return value.toPrecision(4);
    },

    escapeHtml: function(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    withCacheBust: function(url) {
        const separator = url.indexOf('?') === -1 ? '?' : '&';

        return url + separator + '_tbts=' + Date.now();
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        window.clearTimeout(this.refreshTimer);
        this.graphs = [];

        ModelBuildTensorBoardView.__super__.dispose.call(this);
    }
});

export default ModelBuildTensorBoardView;
