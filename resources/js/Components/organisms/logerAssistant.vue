<script setup lang="ts">
    import { watch, ref } from 'vue'
    import { useSpeechRecognition } from '@vueuse/core'
    import { useSpeechSynthesis } from '@vueuse/core'
import LogerButtonTab from '../atoms/LogerButtonTab.vue';

    const {
    isListening,
    result,
    start,
    stop,
    } = useSpeechRecognition()

const theMessage = ref("")
const {
  isSupported,
  isPlaying,
  status,
  voiceInfo: speakerVoiceInfo,
  utterance,
  error,
  stop: stopSpeaker,
  toggle,
  speak,
} = useSpeechSynthesis(theMessage)

const toggleListen = () => {
    if (isListening.value) {
        stop()
        return
    }
    start();
}

function play() {
  if (status.value === 'pause') {
    console.log('resume')
    window.speechSynthesis.resume()
  }
  else {
    speak()
    stop()
  }
}

watch(result,  () => {
    if(result.value.includes('balance')) {
        theMessage.value = `This is your balance ${pageProps.balance}`;
        play()
    }
})

</script>

<template>
    <LogerButtonTab @click="toggleListen()">
        {{ isListening ? 'stop' : 'Press and talk' }}
     </LogerButtonTab>
</template>



