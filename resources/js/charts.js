/**
 * Report charts.
 *
 * Every chart is declared in Blade as <div data-chart="..."> carrying its own JSON, so the
 * report views stay readable and no chart configuration is duplicated in a template. Apex is
 * imported only on a page that actually holds a chart.
 */
const BRAND = {
    purple: '#3c3489',
    purpleLight: '#7f77dd',
    lavender: '#eeedfe',
    dark: '#1d1d1d',
};

const PALETTE = [BRAND.purple, BRAND.purpleLight, '#a49df0', '#cfcbf7', '#6f66c9'];

/**
 * Shared look, so ten charts across two reports cannot drift apart.
 */
function baseOptions(rtl) {
    return {
        chart: {
            fontFamily: 'inherit',
            foreColor: BRAND.dark,
            toolbar: { show: false },
            animations: { enabled: ! window.matchMedia('(prefers-reduced-motion: reduce)').matches },
        },
        colors: PALETTE,
        grid: { borderColor: 'rgba(60, 52, 137, 0.10)', strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', markers: { radius: 3 } },
        tooltip: { theme: 'light' },
        noData: { text: '' },
    };
}

function merge(base, extra) {
    const out = { ...base, ...extra };
    for (const key of ['chart', 'grid', 'legend', 'tooltip', 'dataLabels']) {
        if (extra[key]) {
            out[key] = { ...base[key], ...extra[key] };
        }
    }
    return out;
}

/**
 * @param {{type: string, series: array, categories?: array, horizontal?: boolean, currency?: string, height?: number}} spec
 */
function optionsFor(spec, rtl) {
    const base = baseOptions(rtl);
    const height = spec.height ?? 300;
    const suffix = spec.currency ? ' ' + spec.currency : '';

    if (spec.type === 'donut') {
        return merge(base, {
            chart: { type: 'donut', height },
            series: spec.series,
            labels: spec.categories ?? [],
            plotOptions: { pie: { donut: { size: '68%' } } },
        });
    }

    return merge(base, {
        chart: { type: spec.type, height, stacked: spec.stacked ?? false },
        series: spec.series,
        stroke: spec.type === 'line' || spec.type === 'area'
            ? { curve: 'smooth', width: 3 }
            : { width: 0 },
        fill: spec.type === 'area'
            ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
            : { opacity: 1 },
        plotOptions: {
            bar: {
                horizontal: spec.horizontal ?? false,
                borderRadius: 4,
                borderRadiusApplication: 'end',
                columnWidth: '55%',
            },
        },
        xaxis: {
            categories: spec.categories ?? [],
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            // Ticket counts are whole people; money keeps its unit.
            labels: {
                formatter: (value) => spec.currency
                    ? Math.round(value).toLocaleString() + suffix
                    : Math.round(value).toLocaleString(),
            },
        },
    });
}

export default async function initCharts() {
    const nodes = document.querySelectorAll('[data-chart]');

    if (nodes.length === 0) {
        return;
    }

    const { default: ApexCharts } = await import('apexcharts');
    const rtl = document.documentElement.dir === 'rtl';

    nodes.forEach((node) => {
        let spec;

        try {
            spec = JSON.parse(node.dataset.chart);
        } catch (error) {
            return;
        }

        new ApexCharts(node, optionsFor(spec, rtl)).render();
    });
}
