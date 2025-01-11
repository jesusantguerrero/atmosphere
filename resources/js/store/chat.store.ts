import { defineStore } from 'pinia'
import { computed, reactive, ref, toRefs } from 'vue';


export interface ChatMessage {
    author: string;
    body: string;
    isError?: boolean;
    writing: boolean;
    created_at: Date;
  }
export const useChatStore = defineStore('chat', () => {

    const messages = ref<ChatMessage[]>([]);
    const state = reactive({
        isFocus: false,
        reversedMessages: computed(() => {
            return [...messages.value].reverse();
        }),
        processing: false,
        threadId: '',
        fileId: '',
    });


    const addMessage = (params: ChatMessage) => {
        messages.value.push(params)
    }

    const updateLastMessage = (params: Partial<ChatMessage>) => {
        Object.entries(params).forEach(([key, value]) => {
            messages.value.at(-1)[key] = value;
        });
    }

    const isAssistantWriting = () => {
        return messages.value.at(-1).writing
    }

    const getLastMessageBody = () => {
        return messages.value.at(-1).body
    }

    return {
        messages,
        ...toRefs(state),
        addMessage,
        updateLastMessage,
        isAssistantWriting,
        getLastMessageBody
    };
})
