<script setup lang="ts">
import { watch, computed, ref, nextTick } from "vue";
import SignaturePad from "./SignaturePad.vue";
import DialogModal from "@/Components/DialogModal.vue";
import { useForm } from "@inertiajs/vue3";
import AppButton from "@/Components/shared/AppButton.vue";
import { ElNotification } from "element-plus";

const emit = defineEmits(["update:is-open", "saved"]);
const { isOpen, signatures, entity, endpoint } = defineProps<{
  isOpen: boolean;
  endpoint: string;
  signatures: Record<string, string>[];
  entity: Record<string, any>;
  title: string;
}>();

const formData = useForm({
  text: "",
  file: "",
});

const signatureRef = ref();
const isModalOpen = computed({
  get() {
    return isOpen;
  },
  set(value: boolean) {
    emit("update:is-open", value);
  },
});

const currentSignature = computed(() => {
  return signatures?.find((signature) => signature.user_id == entity.user_id);
});

watch(
  () => isOpen,
  () => {
    if (isOpen) {
      formData.text = currentSignature.value.text;
      nextTick(() => {
        signatureRef?.value?.focus();
      });
    }
  },
  {
    immediate: true,
  }
);

const onSubmit = () => {
  if (formData.processing || !endpoint || currentSignature.value) return;

  formData.post(endpoint, {
    onSuccess() {
      formData.reset();
    },
    onError(err) {
      ElNotification({
        type: "error",
        message: err?.response
          ? err?.response.data.status.message
          : "Ha ocurrido un error",
      });
    },
  });
};
</script>

<template>
  <DialogModal
    :show="isModalOpen"
    :closeable="true"
    @close="isModalOpen = false"
    title="Sign Proposal"
  >
    <template #header>
      <span class="text-xl font-bold text-blue-500"> {{ title }}</span>
    </template>

    <template #content>
      <div class="modal-body">
        <div class="w-full px-5 py-2 border">
          <h4 class="font-bold capitalize">{{ $t("your signature") }}</h4>
          <SignaturePad
            ref="signatureRef"
            v-model="formData.text"
            :editable="true"
            :disabled="currentSignature"
          />
        </div>
      </div>
    </template>

    <template #footer>
      <div class="space-x-2 dialog-footer">
        <AppButton
          variant="primary"
          @click="onSubmit"
          :processing="formData.processing"
          :disabled="disabled"
        >
          {{ $t("confirm signature") }}
        </AppButton>
      </div>
    </template>
  </DialogModal>
</template>
