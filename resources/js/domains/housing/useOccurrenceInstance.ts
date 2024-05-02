import { useForm } from "@inertiajs/vue3";
import { IOccurrenceCheck } from "./models";
import { computed } from "vue";


export enum OccurrenceAction {
    Add = "post",
    Delete = "delete"
}
export const useOccurrenceInstance = () => {
    const occurrenceInstanceForm = useForm({
        occurrenceId: null
    })
    
    
    const applyChange = (occurrenceCheck: IOccurrenceCheck, action: OccurrenceAction) => {
        occurrenceInstanceForm.occurrenceId = occurrenceCheck.id;
        occurrenceInstanceForm.submit(action, `/housing/occurrences/${occurrenceCheck.id}/instances`, {
            onSuccess: () => {
                occurrenceInstanceForm.occurrenceId = null;
            }
        })
    }

    const remove = (occurrenceCheck: IOccurrenceCheck) => {
        occurrenceInstanceForm.delete(`/housing/occurrences/${occurrenceCheck.id}`, {
            onSuccess: () => {
                occurrenceInstanceForm.occurrenceId = null;
            }
        })
    }

    const isProcessing = computed(() => {
        return occurrenceInstanceForm.processing;
    })

    const isLoading = (id?: number) => {
        return id ? occurrenceInstanceForm.occurrenceId == id && occurrenceInstanceForm.processing: occurrenceInstanceForm.processing;
    }

    return {
        occurrenceInstanceForm,
        applyChange,
        remove,
        isProcessing,
        isLoading
    }
}