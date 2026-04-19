<script setup lang="ts">
import { computed,  onMounted, reactive} from 'vue';
import {  useForm } from '@inertiajs/vue3';
import { NSelect } from "naive-ui";
import { AtField  } from 'atmosphere-ui';
import Modal from '@/Components/atoms/Modal.vue'
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { IAccount } from '../models';

const props = defineProps<{
    show: boolean;
    maxWidth?: string;
    closeable: boolean;
    title: string;
    account: Partial<IAccount>;
}>()

const emit = defineEmits(['save', 'close'])

const form = useForm({
    bank_code: null,
    integration_id: null
});


const state = reactive({
    integrations: [],
    banks: [] as { id: string; name: string }[],
})


function getIntegrations() {
    return axios({
        url: "/api/integrations"
    }).then(({ data }) => {
        state.integrations = data;
    })
    .catch(() => {});
}

function getBanks() {
    return axios({
        url: "/api/banks"
    }).then(({ data }) => {
        state.banks = data;
    })
    .catch(() => {});
}

onMounted(async () => {
    await Promise.all([getIntegrations(), getBanks()]);
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

const bankOptions = computed(() => {
    return state.banks.map((bank) => ({
        label: bank.name,
        value: bank.id,
    }));
})


const submit = () => {
    form.post(`/finance/accounts/${props.account.id}/link-bank`, {
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
                        <NSelect
                            filterable
                            clearable
                            placeholder="Bank"
                            :options="bankOptions"
                            v-model:value="form.bank_code"
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


