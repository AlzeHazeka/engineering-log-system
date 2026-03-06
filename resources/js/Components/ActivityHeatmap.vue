<script setup>
import { computed } from "vue";

const props = defineProps({
    data: Array,
});

const days = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"];

/*
Map date -> total
*/

const map = computed(() => {
    const m = {};

    props.data.forEach((d) => {
        m[d.date] = d.total;
    });

    return m;
});

const calendar = computed(() => {
    const arr = [];

    if (!props.data.length) return arr;

    const first = new Date(props.data[0].date);

    let startDay = first.getDay();

    // convert sunday start -> monday start
    startDay = startDay === 0 ? 6 : startDay - 1;

    for (let i = 0; i < startDay; i++) {
        arr.push(null);
    }

    props.data.forEach((d) => {
        arr.push(d);
    });

    return arr;
});

function colorLevel(total) {
    if (!total) return "bg-gray-100";

    if (total === 1) return "bg-orange-200";

    if (total <= 3) return "bg-orange-400";

    if (total <= 6) return "bg-orange-600";

    return "bg-red-600";
}

function shortDate(date) {
    return new Date(date).getDate();
}
</script>

<template>
    <div class="bg-white rounded-xl shadow border p-6">
        <h3 class="font-semibold mb-4">Log Activity (Last 30 Days)</h3>

        <!-- day header -->

        <div class="grid grid-cols-7 text-xs text-gray-400 mb-2">
            <div v-for="d in days" :key="d">
                {{ d }}
            </div>
        </div>

        <!-- calendar -->

        <div class="grid grid-cols-7 gap-2">
            <div v-for="(day, i) in calendar" :key="i">
                <div
                    v-if="day"
                    class="w-8 h-8 rounded flex items-center justify-center text-xs"
                    :class="colorLevel(day.total)"
                    :title="`${day.date} — ${day.total} logs`"
                >
                    {{ shortDate(day.date) }}
                </div>
            </div>
        </div>

        <!-- legend -->

        <div class="flex items-center gap-2 text-xs text-gray-500 mt-4">
            <span>Less</span>

            <div class="w-4 h-4 bg-gray-100 rounded"></div>
            <div class="w-4 h-4 bg-orange-200 rounded"></div>
            <div class="w-4 h-4 bg-orange-400 rounded"></div>
            <div class="w-4 h-4 bg-orange-600 rounded"></div>
            <div class="w-4 h-4 bg-red-600 rounded"></div>

            <span>More</span>
        </div>
    </div>
</template>
