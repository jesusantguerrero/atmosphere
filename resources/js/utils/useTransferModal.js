import { reactive, toRefs } from "vue"

export const transferModalState = reactive({
    isOpen: false,
    transactionData: null,
    recurrence: false,
    automatic: false,
})

export const useTransferModal = () => {
    const closeTransferModal = () => {
        transferModalState.isOpen = false
        transferModalState.automatic = false
        transferModalState.transactionData = null
        transferModalState.recurrence = false
    }

    const openTransferModal = (config = {}) => {
        transferModalState.automatic = config.automatic
        transferModalState.transactionData = config.transactionData
        transferModalState.recurrence = config.recurrence
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
