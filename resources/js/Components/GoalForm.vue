<template>
    <div>
        <at-field
            label="Title"
        >
            <at-input v-model="form.name" placeholder="Your goal's name" />
        </at-field>

        <at-field
            label="Target amount"
        >
            <at-input  v-model="form.target" type="number" />
        </at-field>
        <at-field
            label="Amount saved"
        >
            <at-input  v-model="form.amount" type="number" />
        </at-field>
        <at-field
            label="Due date"
        >
            <n-date-picker
                v-model:value="form.due_date"
                type="date"
            />
        </at-field>
    </div>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3"
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
