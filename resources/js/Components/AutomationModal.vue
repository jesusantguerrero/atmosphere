<template>
   <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="bg-base-lvl-3">
            <div class="px-5 pt-5 pb-4">
                <h3 class="text-lg font-bold text-primary">
                    <slot name="title">
                        {{ title }}
                    </slot>
                </h3>
            </div>

            <div class="px-5 py-3">
                <form @submit.prevent="submit">
                    <AtField label="Services">
                        <n-select
                            filterable
                            clearable
                            :default-expand-all="true"
                            :options="serviceOptions"
                            @update:value="setService"
                        />
                    </AtField>
                    <AtField label="Integration" v-if="isExternalService">
                        <n-select
                            filterable
                            clearable
                            :options="integrationOptions"
                            v-model:value="form.integration"
                        />
                    </AtField>
                    <div class="flex">
                        <Button @click.prevent="selectType('manual')">Manual</Button>
                        <Button @click.prevent="selectType('recipe')">Recipes</Button>
                    </div>
                    <AtField label="Recipe" v-if="automationType=='recipe'">
                        <n-select
                            filterable
                            clearable
                            :options="recipeOptions"
                            v-model:value="form.recipe"
                        />
                    </AtField>
                    <div v-if="automationType=='manual'|| form.recipe">
                        <div v-for="(task, index) in form.tasks" :key="task">
                            <AtField label="Automation Task" >
                                <n-select
                                    filterable
                                    clearable
                                    :options="taskOptions"
                                    v-model:value="tasks[index].automation_task_id"
                                    @update:value="setTask(index, $event)"
                                />
                            </AtField>
                            <div v-if="task.config" class="ml-5">
                                <AtField :label="field.label||field.title||fieldName" v-for="(field, fieldName) in task.config" :key="fieldName">
                                    <n-select
                                        v-if="field.type=='select'"
                                        filterable
                                        clearable
                                        :options="makeOptions(field.options)"
                                        v-model:value="task.values[fieldName]"
                                    />
                                    <AtInput
                                        v-else
                                        type="text"
                                        v-model.trim="task.values[fieldName]"
                                    >
                                        <template name="suffix">
                                            <div>
                                                <button>Value</button>
                                                <button>Formula</button>
                                                <button>Field</button>
                                            </div>
                                        </template>
                                    </AtInput>
                                </AtField>
                            </div>
                        </div>
                        <Button @click.prevent="addComponent"> Add component </Button>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-gray-100">
            <at-button type="secondary" @click="close"> Cancel </at-button>
            <at-button class="text-white bg-primary" @click="submit"> Save </at-button>
        </div>
   </modal>
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
import { AtField, AtButton, AtInput } from 'atmosphere-ui';
import { NSelect } from "naive-ui";
import { computed, nextTick, ref } from 'vue';
import { AtButton as Button } from 'atmosphere-ui';
import Modal from '@/Components/atoms/Modal.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    maxWidth: {
        type: String,
        default: 'lg'
    },
    closeable: {
        type: Boolean,
        default: true
    },
    title: {
        type: String,
        default: 'Modal'
    },
    services: {
        type: Array,
        default() {
            return [];
        }
    },
    integrations: {
        type: Array,
        default() {
            return [];
        }
    },
    recipes: {
        type: Array,
        default() {
            return [];
        }
    },
    tasks: {
        type: Array,
        default() {
            return [];
        }
    },
})

const emit = defineEmits(['save'])

const form = useForm({
    service: null,
    recipe: null,
    integration: null,
    tasks: [],
});

const setService = (value, option) => {
    form.service = props.services.find(service => service.id == option.value);
}

const isExternalService = computed(() => {
    return form.service?.type == 'external'
});

const automationType = ref('manual');
const selectType = (type) => {
    automationType.value = type;
    if (type == 'manual' && form.tasks.length == 0) {
        addComponent();
    }
}

const makeOptions = (options) => {
    return options.map(option => {
        return {
            label: option,
            value: option,
        }
    })
}

const addComponent = () => {
    form.tasks.push({
        automation_task_id: null,
        name: '',
        label: '',
        config: {},
        values: {}
    });
}

const setTask = (index, taskId) => {
    nextTick(() => {
        const newTask = form.tasks[index];
        const taskDefinition = props.tasks.find(task => {
            return task.id === taskId
        });
        const config = JSON.parse(taskDefinition?.config || '{}') ?? {};
        form.tasks[index] = {
            ...newTask,
            automation_task_id: taskId,
            name: taskDefinition.name,
            label: taskDefinition.label,
            config,
            values: Object.keys(config).reduce((values, fieldName) => {
                values[fieldName] = '';
                return values;
            }, {})
        };
    })
}

const serviceOptions = props.services.map(service => {
    return {
        label: service.name,
        value: service.id,
    }
});

const integrationOptions = computed(() => {
    return props.integrations.map(integration => {
        return {
            label: `${integration.name} ${integration.hash}`,
            value: integration.id,
        }
    });
})

const recipeOptions = computed(() => {
    return props.recipes.map(recipe => {
        return {
            label: recipe.name,
            value: recipe.id,
        }
    });
})

const taskOptions = computed(() => {
    return props.tasks.map(task => {
        return {
            label: task.label,
            value: task.id,
        }
    });
})

const selectedRecipe = computed(() => {
    return props.recipes.find(recipe => {
        return recipe.id === form.recipe;
    });
})

const emailConditions = [
    {
        id: "from:",
        label: "From"
    },
    {
        label: "To",
        id: "to:"
    },
    {
        label: "subject:",
        id: "Subject"
    },
    {
        label: "Includes",
        id: "has"
    },
    {
        id: "",
        label: "Custom"
    }
]

const getInputs = () => {
    if (selectedRecipe.value) {
        const mapper = JSON.parse(selectedRecipe.value.mapper);
        return mapper && mapper.input
    }
    return [];
}

const hasInput = (inputName) => {
    const inputs = getInputs();
    if (inputs) {
        return inputs.includes(inputName);
    }
}

const prepareForm = () => {
    const formData = { ...form.data() };
    formData.name = formData.name || formData.tasks.map(task => task.name).join(' ');
    formData.description = formData.description || formData.tasks.map(task => task.label).join(' ');
    formData.sentence = formData.sentence || formData.tasks.map(task => task.label).join(' ');

    formData.config = {};
    if (form.integration) {
        formData.integration_id = formData.integration;
    }

    return formData;
}

const submit = () => {
    const formData = prepareForm();
    const method = formData.id ? "PUT" : "POST";
    const param = formData.id ? `/${formData.id}` : "";

    axios({
        url: `/api/automation${param}`,
        method,
        data: formData
    }).then(() => {
        emit("saved");
    });
}
</script>
