<link rel="preload" href="{{ asset('fonts/vazirmatn.woff2') }}" as="font" type="font/woff2" crossorigin>

<style>
    .fi-panel-studio .fi-wi-chart .fi-wi-chart-text-color {
        color: #574c53;
    }

    .dark .fi-panel-studio .fi-wi-chart .fi-wi-chart-text-color {
        color: #d0c7ce;
    }
</style>

<script>
    (() => {
        const family = 'Vazirmatn, sans-serif';
        const font = '500 13px Vazirmatn';
        const sample = 'آذر ۱۲۳۴۵۶۷۸۹۰';
        const labelFont = ({ chart }) => chart.options.font;
        const labelColor = ({ chart }) => chart.options.color;

        window.filamentChartJsPlugins ??= [];
        window.filamentChartJsPlugins.push({
            id: 'porint-dashboard-typography',
            beforeInit(chart) {
                const options = chart.config.options;
                const needsFont = document.fonts && !document.fonts.check(font, sample);

                options.font = { ...options.font, family: needsFont ? 'sans-serif' : family, size: 13, weight: 500 };

                for (const scale of Object.values(options.scales ?? {})) {
                    scale.ticks = { ...scale.ticks, font: labelFont, color: labelColor, padding: 7 };
                }

                const legend = options.plugins?.legend;
                if (legend && legend.display !== false) {
                    legend.labels = { ...legend.labels, font: labelFont, color: labelColor, padding: 16 };
                }

                const tooltip = options.plugins?.tooltip;
                if (tooltip) {
                    tooltip.bodyFont = labelFont;
                    tooltip.titleFont = ({ chart }) => ({ ...chart.options.font, size: 14, weight: 650 });
                }

                if (needsFont) {
                    // A different family invalidates widths measured before the webfont loaded.
                    document.fonts.load(font, sample).then((faces) => {
                        if (!chart.canvas || !faces.length) return;

                        options.font.family = family;
                        chart.update('resize');
                    }).catch(() => {});
                }
            },
        });
    })();
</script>
