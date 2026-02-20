import { onMounted, ref } from 'vue'
import { fetchTemplates } from '../services/templateService'

export function useEnvelopeTemplates() {
    const envelopeTemplates = ref([])
    const selected_envelope_template = ref('')

    async function loadEnvelopeTemplates() {
        try {
            const allTemplates = await fetchTemplates()
            envelopeTemplates.value = allTemplates.filter((template) => template.type === 'envelope')
            if (envelopeTemplates.value.length > 0 && !selected_envelope_template.value) {
                selected_envelope_template.value = envelopeTemplates.value[0].id
            }
        } catch (error) {
            console.error('Failed to load envelope templates:', error)
        }
    }

    onMounted(loadEnvelopeTemplates)

    return {
        envelopeTemplates,
        selected_envelope_template,
    }
}
