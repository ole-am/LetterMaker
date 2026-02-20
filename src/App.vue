<template>
    <NcContent app-name="LetterMaker">
        <NcAppContent>
            <LetterView v-if="activeItem === 'letter'" />
        </NcAppContent>
    </NcContent>
</template>

<script>
import { ref } from 'vue'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import LetterView from './views/LetterView.vue'

export default {
    name: 'App',
    components: {
        NcContent,
        NcAppContent,
        NcDateTimePicker,
        LetterView,
    },
    setup() {
        const activeItem = ref('letter')
        return {
            activeItem,
        }
    },
}
</script>
