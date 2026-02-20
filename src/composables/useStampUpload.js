import { ref } from 'vue'

export function useStampUpload() {
    const stamp_file = ref(null)
    const stamp_file_name = ref('')

    function handleStampFile(event) {
        const file = event?.target?.files?.[0] ?? null
        if (!file || file.type !== 'application/pdf') {
            stamp_file.value = null
            stamp_file_name.value = ''
            return
        }
        stamp_file.value = file
        stamp_file_name.value = file.name
    }

    return {
        stamp_file,
        stamp_file_name,
        handleStampFile,
    }
}
