import { reactive, toRefs } from "vue"

/**
 *
 */
export const transferModalState = reactive({
    isOpen: false,
    transactionData: null,
    recurrence: false,
    automatic: false,
})

/**
 * useTransactionModal - get controls and state of transaction modal
 * @returns {{ toggleTransferModal: Function, openTransferModal: Function, closeTransferModal: Function, isOpen: Boolean }}
 */
export const useTransactionModal = () => {
    const closeTransferModal = () => {
        transferModalState.isOpen = false
        transferModalState.automatic = false
        transferModalState.transactionData = null
        transferModalState.recurrence = false
    }

    const openTransferModal = (config = {}) => {
        transferModalState.automatic = config.automatic ?? false
        transferModalState.transactionData = config.transactionData ?? null
        transferModalState.recurrence = config.recurrence ?? false
        transferModalState.isOpen = true
    }

    const toggleTransferModal = (config) => {
        if (transferModalState.isOpen)  {
            closeTransferModal()
        } else {
            openTransferModal(config)
        }
    }

    const { isOpen } = toRefs(transferModalState)

    return {
        toggleTransferModal,
        openTransferModal,
        closeTransferModal,
        isOpen,
    }
}
