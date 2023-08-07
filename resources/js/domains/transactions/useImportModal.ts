import { reactive, toRefs } from "vue"

/**
 *
 */
export const importModalState = reactive({
    isOpen: false,
    data: null,
})

/**
 * useTransactionModal - get controls and state of transaction modal
 * @returns {{ toggleModal: Function, openModal: Function, closeModal: Function, isOpen: Boolean }}
 */
export const useImportModal = () => {
    const closeModal = () => {
        importModalState.isOpen = false
        importModalState.data = null
    }

    const openModal = (config = {}) => {
        importModalState.data = config.data ?? null
        importModalState.isOpen = true
    }

    const toggleModal = (config) => {
        if (importModalState.isOpen)  {
            closeModal()
        } else {
            openModal(config)
        }
    }

    const { isOpen } = toRefs(importModalState)

    return {
        toggleModal,
        openModal,
        closeModal,
        isOpen,
    }
}
