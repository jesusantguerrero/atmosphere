/**
 * useTransactionModal - get transaction modal functions of a child
 * @param {import("vue").Ref} modalParent - ComponentRef owner of the transaction modal
 * @returns {{ openModalFor: Function, handleEdit: Function }}
 */

import { inject } from "vue"

export const useTransactionModal = (modalParentRef) =>{
    const openModalFor = inject('openTransactionModal', (...args) => {
        modalParentRef?.value?.openTransactionModal.apply(null, args)
    })

    const handleEdit = inject('openTransactionModalForEdit', (...args) => {
        modalParentRef?.value?.openTransactionModalForEdit.apply(null, args)
    })

    return {
        handleEdit,
        openModalFor
    }
}
