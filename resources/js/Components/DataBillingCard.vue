<script lang="ts" setup>
import { ElNotification } from "element-plus";
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "./atoms/LogerButton.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  isCurrent: {
    type: Boolean,
    default: false,
  },
  subscribeLink: {
    type: String,
    required: true,
  },
  contactLink: {
    type: String,
  },
  subscribeLabel: {
    type: String,
    default: "subscribe",
  },
  disabled: {
    type: Boolean,
  },
});
const emit = defineEmits(["selected"]);

const buttonsContainer = ref();
onMounted(() => {
  paypal
    ?.Buttons({
      vault: true,
      intent: "subscription",
      createSubscription(data: any, actions: any) {
        return actions.subscription.create({
          plan_id: props.plan.paypal_plan_id,
          vault: true,
        });
      },

      onApprove(data: any, actions: any) {
        data.plan_id = props.plan.paypal_plan_id;
        createSubscription(data);
      },
    })
    .render(buttonsContainer.value);
});

const { t } = useI18n();
function createSubscription(data: any) {
  axios({
    method: "POST",
    url: `/v2/subscriptions/${data.subscriptionID}/save`,
    data,
  }).then(() => {
    ElNotification({
      type: "success",
      message: t(`Team has been subscribed to plan ${props.plan?.display_name}`),
    });
    router.reload();
  });
}
</script>

<template>
  <div
    class="w-4/12 px-5 py-10 mx-6 mb-5 bg-white rounded-md shadow-md"
    :class="{ 'border-primary border-2': isCurrent }"
  >
    <div class="prose prose-xl">
      <h3 class="text-center">
        {{ plan.display_name ?? plan.name }}
        <div v-if="isCurrent" class="px-1 py-1 text-xs rounded-md text-primary">
          Current Plan
        </div>
      </h3>
    </div>

    <div class="px-5 py-2 my-2 rounded-md">
      <h2 class="text-5xl text-center">
        <span class="font-extrabold">
          {{ plan.quantity }}
        </span>
        <small class="text-md"> USD </small>
      </h2>

      <div class="mt-5" v-if="plan?.features">
        <div class="flex flex-col prose prose-md">
          <span
            v-for="feature in plan.features"
            class="font-bold capitalize text-secondary"
          >
            {{ feature }}
          </span>
        </div>
      </div>
    </div>

    <div class="text-center" v-if="!isCurrent">
      <a
        v-if="contactLink"
        class="inline-block px-5 py-2 text-blue-500 bg-white border-2 rounded-md border-primary"
        :href="contactLink"
      >
        Contact Sales
      </a>

      <div ref="buttonsContainer" v-if="plan.paypal_plan_id" vault="true" />
      <AppButton
        v-else
        class="justify-center w-full text-white bg-primary"
        @click="$emit('selected', plan)"
        :disabled="disabled"
      >
        {{ subscribeLabel }}
      </AppButton>
    </div>
  </div>
</template>
