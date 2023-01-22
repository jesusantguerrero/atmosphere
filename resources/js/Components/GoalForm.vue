<template>
    <div>
        <AtField
            label="Title"
        >
            <AtInput v-model="form.name" placeholder="Your goal's name" />
        </AtField>

        <AtField
            label="Target amount"
        >
            <AtInput  v-model="form.target" type="number" />
        </AtField>
        <AtField
            label="Amount saved"
        >
            <AtInput  v-model="form.amount" type="number" />
        </AtField>
        <AtField
            label="Due date"
        >
            <n-date-picker
                v-model:value="form.due_date"
                type="date"
            />
        </AtField>
    </div>
</template>

<script setup>
    import { useForm } from "@inertiajs/vue3"
    import { AtField, AtInput } from "atmosphere-ui"
    import { nextTick } from '@vue/runtime-core'
    import { NDatePicker } from "naive-ui"
import { format } from "date-fns";

    defineEmits(['close']);
    const props = defineProps({
        resource: {
            type: Object,
            default: () => ({
                name: '',
            }),
        }
    });

    const form = useForm({
        ...{
            name: props.resource?.name,
            target: '',
            amount: '',
            due_date: new Date(),
        },
    })


    const submit = async () => {
        const method = props.meal ? 'put' : 'post';
        const url = props.meal ? `/goals/${props.resource.id}` : '/goals';
        form
            .transform(data => ({
                ...data,
                due_date: format(new Date(form.due_date), 'yyyy-MM-dd'),
        }))
        .submit(method, url, {
            onSuccess: async () => {
                if (method == 'post') {
                    await nextTick()
                    form.reset()
                }
            }
        })

    }

    defineExpose({
        submit,
    })
</script>
