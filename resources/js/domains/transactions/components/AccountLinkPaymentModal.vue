<script setup lang="ts">
import { computed,  onMounted, reactive} from 'vue';
import {  useForm } from '@inertiajs/vue3';
import { NSelect } from "naive-ui";
import { AtField  } from 'atmosphere-ui';
import Modal from '@/Components/atoms/Modal.vue'
import LogerButton from '@/Components/atoms/LogerButton.vue';
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
import { IAccount } from '../models';

const props = defineProps<{
    show: boolean;
    maxWidth: string;
    closeable: boolean;
    title: string;
    account: IAccount;
}>()

const emit = defineEmits(['save', 'close'])

const form = useForm({
    service_id: null,
    integration_id: null
});


const state = reactive({
    integrations: [],

})


function getIntegrations() {
    return axios({
        url: "/api/integrations"
    }).then(({ data }) => {
        state.integrations = data;
    })
    .catch((err) => console.log(err));
}

onMounted(async () => {
    await getIntegrations();
    if (state.integrations.length == 1) {
        form.integration_id = state.integrations.at(0).id
    }
})

const integrationOptions = computed(() => {
    return state.integrations.map((integration: Record<string, any>) => {
        return {
            label: `${integration.name} ${integration.hash}`,
            value: integration.id,
        }
    });
})


const submit = () => {
    form.post(`/finance/accounts/${props.account.id}/automation-services/${form.service_id}/link/`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess() {
            emit("save");
        }
    });
}

const close = () => emit('close');
</script>

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
                    <AtField label="Integration">
                        <NSelect
                            filterable
                            clearable
                            :disabled="state.integrations.length == 1"
                            :options="integrationOptions"
                            v-model:value="form.integration_id"
                        />
                    </AtField>
                    <AtField label="Bank" >
                        <LogerApiSimpleSelect
                          v-model="form.service_id"
                          v-model:label="form.service_label"
                          custom-label="name"
                          track-id="id"
                          placeholder="Bank"
                          endpoint="/api/automation-services"
                        />
                      </AtField>
                </form>
            </div>
        </div>

        <div class="flex justify-end px-6 py-4 space-x-3 text-right bg-gray-100">
            <LogerButton variant="neutral" @click="close"> Cancel </LogerButton>
            <LogerButton class="text-white bg-primary" @click="submit" :processing="form.processing">
                Save
            </LogerButton>
        </div>
   </modal>
</template>


