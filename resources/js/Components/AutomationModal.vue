<template>
   <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-10 pt-5 pb-4">
            <h3 class="text-lg font-bold text-pink-500">
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
                        v-model:value="form.service"
                        :default-expand-all="true"
                        :options="serviceOptions"
                    />
                </AtField>
                <AtField label="Integration">
                    <n-select
                        filterable
                        clearable
                        :options="integrationOptions"
                        v-model:value="form.integration"
                    />
                </AtField>
                <AtField label="Recipe">
                    <n-select
                        filterable
                        clearable
                        :options="recipeOptions"
                        v-model:value="form.recipe"
                    />
                </AtField>
                <AtField label="Condition type" v-if="hasInput('condition')">
                    <n-select
                        filterable
                        clearable
                        :options="emailConditions"
                        v-model:value="form.condition"
                    />
                </AtField>
                <AtField label="Condition text" v-if="hasInput('value')">
                    <at-input
                        v-model="form.value"
                    />
                </AtField>
            </form>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-gray-100">
            <at-button type="secondary" @click="close"> Cancel </at-button>
            <at-button class="text-white bg-pink-400" @click="submit"> Save </at-button>
        </div>
   </modal>
</template>
<script setup>
import Modal from '@/Jetstream/Modal'
import { useForm } from '@inertiajs/inertia-vue3';
import { AtField, AtButton, AtInput } from 'atmosphere-ui';
import { NSelect } from "naive-ui";
import { computed,  } from 'vue';

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
})

const emit = defineEmits(['save'])

const form = useForm({
    service: null,
    recipe: null,
    integration: null,
    condition: null,
    value: ''
});

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
    const  recipe = props.recipes.find(recipe => recipe.id == formData.recipe);
    formData.automation_recipe_id = formData.recipe;
    formData.name = recipe.name;
    formData.description = recipe.description;
    formData.sentence = recipe.sentence;

    formData.config = {};
    if (form.integration) {
        formData.integration_id = formData.integration;
    }

    if (formData.condition) {
        formData.config["condition"] = formData.Condition.id;
    }

    const inputs = getInputs();
    if (inputs) {
        inputs.map((inputName) => {
            if(!formData.config[inputName]) {
                formData.config[inputName] = formData[inputName];
            }
        })
    }

    formData.config = JSON.stringify(formData.config);
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
