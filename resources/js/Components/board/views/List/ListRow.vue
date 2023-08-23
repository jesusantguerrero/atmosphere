<script setup lang="ts">
import { matrixColors } from '@/utils/constants';
import CellSummaryDate from '../../cellTypes/CellSummaryDate.vue';
import CellSummaryProgress from '../../cellTypes/CellSummaryProgress.vue';
import ItemGroupCell from "../../ItemGroupCell.vue";
import { PlanItem , LogerField, FieldValue } from "@/domains/housing/models";
import { ConcreteComponent } from 'vue';


const props = defineProps<{
    item: PlanItem;
    visibleFields: LogerField[],
    rowIndex: number;
    slim: boolean;
    isSummary: boolean;
}>()

function getBg(field: LogerField, item: PlanItem, fieldName: string) {
    if(!item || !field || props.isSummary) return
    const fieldValue = item.fields && item.fields.find(field => field.field_name == fieldName);
    const value = fieldValue ? fieldValue.value : item[fieldName];

    if (value && field.rules) {
        const bgRule = field.rules.find(rule => rule.name == "bg");
        if (bgRule) {
            const ruleOptions = bgRule.options || (bgRule.reference && field[bgRule.reference]);
            // @ts-ignore
            const bg = ruleOptions && ruleOptions.find(rule => {
                const name = rule.name || rule.value;
                return value.toLowerCase() == name.toLowerCase();
            });
            // @ts-ignore
            return bg ? matrixColors[bg.result || bg.color] : "";
        }
    }
    return "bg-gray-200";
}

const components: Record<string, ConcreteComponent> = {
    progress: CellSummaryProgress,
    summaryDate: CellSummaryDate
}

const getRenderComponent = (item: PlanItem, field: LogerField) => {
    console.log(item, field.name)
    return item?.type ? components[item[field.name]] : ItemGroupCell
}
</script>

<template>
<div
    class="grid text-left item-group-row h-11"
    :class="{'item-group ic-scroller ic-scroller-slim': slim }"
    @scroll="$emit('scroll', $event)"
>
    <component
        v-for="(field, index) in visibleFields"
        :is="getRenderComponent(item, field)"
        :key="`${field.name}-${index}`"
        class="text-center border border-white custom-field"
        :class="[getBg(field, item, field.name)]"
        :field-name="field.name"
        :field="field"
        :index="rowIndex"
        :item="item"
        @saved="$emit('saved', item, field.name, $event)"
    />
</div>
</template>

