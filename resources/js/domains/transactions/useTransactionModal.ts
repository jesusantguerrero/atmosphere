import { reactive, toRefs } from "vue"

/**
 *
 */
export const transactionModalState = reactive({
    isOpen: false,
    transactionData: null,
    mode: 'WITHDRAW',
    recurrence: false,
    automatic: false,
})

/**
 * useTransactionModal - get controls and state of transaction modal
 * @returns {{ toggleTransactionModal: Function, openTransactionModal: Function, closeTransactionModal: Function, isOpen: Boolean }}
 */
export const useTransactionModal = () => {
    const closeTransactionModal = () => {
        transactionModalState.isOpen = false
        transactionModalState.automatic = false
        transactionModalState.transactionData = null
        transactionModalState.mode = 'WITHDRAW'
        transactionModalState.recurrence = false
    }

    const openTransactionModal = (config = {}) => {
        transactionModalState.automatic = config.automatic ?? false
        transactionModalState.transactionData = config.transactionData ?? null
        transactionModalState.recurrence = config.recurrence ?? false
        transactionModalState.mode = config.mode ?? 'WITHDRAW'
        transactionModalState.isOpen = true
    }

    const toggleTransactionModal = (config) => {
        if (transactionModalState.isOpen)  {
            closeTransactionModal()
        } else {
            openTransactionModal(config)
        }
    }

    const { isOpen } = toRefs(transactionModalState)

    return {
        toggleTransactionModal,
        openTransactionModal,
        closeTransactionModal,
        isOpen,
    }
}
