import { computed, reactive, toRefs } from "vue"

interface IModalState {
  isOpen: boolean;
  data: null|Record<string, any>;
}
export const modalState = reactive<Record<string, IModalState>>({})

/**
 * useTransactionModal - get controls and state of transaction modal
 * @returns {{ toggleModal: Function, openModal: Function, closeModal: Function, isOpen: Boolean }}
 */
export const useToggleModal = (modalKey: string) => {
    if (!modalState[modalKey]) {
      modalState[modalKey] = {
        isOpen: false,
        data: null,
      }
    }
    const closeModal = () => {
        modalState[modalKey].isOpen = false
        modalState[modalKey].data = null
    }

    const openModal = (config: IModalState = { data: null, isOpen: true }) => {
        modalState[modalKey].data = config.data ?? null
        modalState[modalKey].isOpen = true
    }

    const toggleModal = (config?: IModalState) => {
        if (modalState[modalKey].isOpen)  {
            closeModal()
        } else {
            openModal(config)
        }
    }

    const { isOpen } = toRefs(modalState[modalKey])

    const data = computed(() => modalState[modalKey].data ?? null)

    const setData = (dataValue: any) => {
      modalState[modalKey].data = dataValue
    }

    return {
        toggleModal,
        openModal,
        closeModal,
        setData,
        isOpen,
        data
    }
}
