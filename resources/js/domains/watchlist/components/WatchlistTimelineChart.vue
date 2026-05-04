<script setup lang="ts">
import { computed } from "vue";

import LogerChart from "@/Components/organisms/LogerChart.vue";
import { formatMonth, MonthTypeFormat } from "@/utils";

interface MonthPoint {
    month: string;
    total: number;
}

const props = defineProps<{
    series: MonthPoint[];
    target?: number | null;
}>();

const labels = computed(() => props.series.map((p) => formatMonth(p.month, MonthTypeFormat.short)));
const totals = computed(() => props.series.map((p) => p.total));
const targetValue = computed(() => Number(props.target ?? 0));
const hasTarget = computed(() => targetValue.value > 0);

// Color each bar based on whether it crosses the target
const barColors = computed(() => {
    if (!hasTarget.value) {
        return totals.value.map(() => "#7B77D1");
    }
    return totals.value.map((total) => {
        const ratio = total / targetValue.value;
        if (ratio > 1) return "#EF4444"; // red-500
        if (ratio > 0.7) return "#F59E0B"; // amber-500
        return "#22C55E"; // green-500
    });
});

const chartSeries = computed(() => {
    const datasets: any[] = [
        {
            name: "Spending",
            data: totals.value,
            type: "bar",
            backgroundColor: barColors.value,
            borderColor: barColors.value,
            borderRadius: 4,
            order: 2,
        },
    ];

    if (hasTarget.value) {
        datasets.push({
            name: "Target",
            data: totals.value.map(() => targetValue.value),
            type: "line",
            borderColor: "#1F2937",
            backgroundColor: "rgba(31, 41, 55, 0.05)",
            borderWidth: 2,
            borderDash: [6, 4],
            pointRadius: 0,
            pointHoverRadius: 0,
            fill: false,
            order: 1,
        });
    }

    return datasets;
});

const chartOptions = computed(() => ({
    colors: ["#7B77D1", "#1F2937"],
    plugins: {
        legend: {
            display: hasTarget.value,
            position: "top" as const,
            labels: {
                boxWidth: 12,
                font: { size: 11 },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <section class="bg-base-lvl-3 rounded-md px-5 py-4 border border-base">
        <header class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-bold text-body">Last 12 months</h3>
                <p class="text-xs text-body-1">Monthly spending. Bars colored by status vs target.</p>
            </div>
        </header>
        <LogerChart
            class="bg-white"
            style="height: 280px; width: 100%;"
            type="bar"
            :labels="labels"
            :series="chartSeries"
            :options="chartOptions"
        />
    </section>
</template>
