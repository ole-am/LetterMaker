import { computed, ref } from 'vue'

export function useLetterForm() {
    const recipient_name = ref('')
    const recipient_info = ref('')
    const recipient_street = ref('')
    const recipient_zip = ref('')
    const recipient_city = ref('')
    const sender_name = ref('')
    const sender_info = ref('')
    const sender_street = ref('')
    const sender_zip = ref('')
    const sender_city = ref('')
    const subject = ref('')
    const dateValue = ref(new Date())
    const selectedFiles = ref([])

    const formattedDate = computed(() => {
        if (!dateValue.value) {
            return ''
        }
        const date = new Date(dateValue.value)
        if (Number.isNaN(date.getTime())) {
            return ''
        }
        const day = String(date.getDate()).padStart(2, '0')
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const year = date.getFullYear()
        return `${day}.${month}.${year}`
    })

    function checkRequired() {
        const requiredValues = [
            recipient_name.value,
            recipient_street.value,
            recipient_zip.value,
            recipient_city.value,
            sender_name.value,
            sender_street.value,
            sender_zip.value,
            sender_city.value,
        ]

        return requiredValues.every((value) => String(value ?? '').trim())
    }

    return {
        recipient_name,
        recipient_info,
        recipient_street,
        recipient_zip,
        recipient_city,
        sender_name,
        sender_info,
        sender_street,
        sender_zip,
        sender_city,
        subject,
        dateValue,
        selectedFiles,
        formattedDate,
        checkRequired,
    }
}
