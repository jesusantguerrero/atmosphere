import { reactive, toRefs } from "vue"

/**
 *
 */
export const transactionModalState = reactive({
    isOpen: false,
    transactionData: null,
    mode: 'EXPENSE',
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
        transactionModalState.mode = 'EXPENSE'
        transactionModalState.recurrence = false
    }

    const openTransactionModal = (config = {}) => {
        transactionModalState.automatic = config.automatic ?? false
        transactionModalState.transactionData = config.transactionData ?? null
        transactionModalState.recurrence = config.recurrence ?? false
        transactionModalState.mode = config.mode ?? 'EXPENSE'
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
