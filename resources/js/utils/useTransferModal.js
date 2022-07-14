import { reactive, toRefs } from "vue"

const transferModalState = reactive({
    isOpen: false
})

export const useTransferModal = () => {


    const toggleTransferModal = () => {
        transferModalState.isOpen = !transferModalState.isOpen
    }

    const closeTransferModal = () => {
        transferModalState.isOpen = false
    }

    const openTransferModal = () => {
        transferModalState.isOpen = true
    }

    const { isOpen } = toRefs(transferModalState)
    return {
        toggleTransferModal,
        openTransferModal,
        closeTransferModal,
        isOpen,
    }
}
