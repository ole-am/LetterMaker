import { computed, onMounted, ref } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import {
    deleteTemplateHtml,
    downloadTemplateHtml,
    fetchTemplates,
    resetTemplateHtml,
    uploadTemplateHtml,
} from '../services/templateService'

export function useTemplateAdmin() {
    const letterTemplateFileInput = ref(null)
    const envelopeTemplateFileInput = ref(null)
    const deleteDialogOpen = ref(false)
    const templateToDelete = ref(null)
    const templates = ref([])
    const deleteInProgress = ref(false)
    const uploadInProgress = ref(false)
    const resetInProgress = ref(false)
    const templateFile = ref(null)
    const uploadMessage = ref('')

    const envelopeTemplates = computed(() => templates.value.filter((template) => template.type === 'envelope'))

    function canDeleteTemplate(template) {
        return Boolean(template?.type) && templates.value.filter((entry) => entry.type === template.type).length > 1
    }

    function isHtmlFile(file) {
        const lowerName = (file.name || '').toLowerCase()
        return lowerName.endsWith('.html') || lowerName.endsWith('.htm') || file.type === 'text/html'
    }

    function resetTemplateInput(fileInputRef) {
        if (fileInputRef?.value) {
            fileInputRef.value.value = ''
        }
    }

    async function loadTemplates() {
        try {
            templates.value = await fetchTemplates()
        } catch (error) {
            console.error('Failed to load templates:', error)
            templates.value = []
        }
    }

    function openLetterTemplateFilePicker() {
        uploadMessage.value = ''
        letterTemplateFileInput.value?.click()
    }

    function openEnvelopeTemplateFilePicker() {
        uploadMessage.value = ''
        envelopeTemplateFileInput.value?.click()
    }

    function handleLetterTemplateFile(event) {
        handleTemplateFile(event, letterTemplateFileInput, true)
    }

    function handleEnvelopeTemplateFile(event) {
        handleTemplateFile(event, envelopeTemplateFileInput, false)
    }

    function handleTemplateFile(event, fileInputRef, isLetterTemplate) {
        const file = event?.target?.files?.[0] ?? null
        if (!file) {
            templateFile.value = null
            return
        }
        if (!isHtmlFile(file)) {
            templateFile.value = null
            uploadMessage.value = t('lettermaker', 'Templates must be annotated')
            return
        }
        templateFile.value = file
        uploadTemplate(fileInputRef, isLetterTemplate)
    }

    async function uploadTemplate(fileInputRef, isLetterTemplate) {
        if (!templateFile.value || uploadInProgress.value) {
            return
        }
        uploadInProgress.value = true
        uploadMessage.value = ''
        try {
            await uploadTemplateHtml(templateFile.value)
            templateFile.value = null
            resetTemplateInput(fileInputRef)
            await loadTemplates()
            if (isLetterTemplate) {
                uploadMessage.value = t('lettermaker', 'The current letter template has been changed')
            }
        } catch (error) {
            console.error('Failed to upload template HTML:', error)
            uploadMessage.value = t('lettermaker', 'Templates must be annotated')
        } finally {
            uploadInProgress.value = false
        }
    }

    function openDeleteTemplateDialog(template) {
        if (!canDeleteTemplate(template)) {
            return
        }
        templateToDelete.value = template
        deleteDialogOpen.value = true
    }

    function cancelDeleteTemplate() {
        deleteDialogOpen.value = false
        templateToDelete.value = null
    }

    async function confirmDeleteTemplate() {
        if (!templateToDelete.value || !canDeleteTemplate(templateToDelete.value) || deleteInProgress.value) {
            return
        }
        deleteInProgress.value = true
        try {
            await deleteTemplateHtml(templateToDelete.value.filename)
            await loadTemplates()
            cancelDeleteTemplate()
        } catch (error) {
            console.error('Failed to delete template:', error)
        } finally {
            deleteInProgress.value = false
        }
    }

    async function resetTemplates() {
        if (resetInProgress.value) {
            return
        }
        resetInProgress.value = true
        try {
            await resetTemplateHtml()
            await loadTemplates()
            uploadMessage.value = ''
        } catch (error) {
            console.error('Failed to reset templates:', error)
        } finally {
            resetInProgress.value = false
        }
    }

    async function downloadTemplateFile(template) {
        const filename = typeof template === 'string'
            ? template === 'letter' ? 'letter.html' : template
            : template?.filename
        if (!filename) {
            return
        }
        try {
            await downloadTemplateHtml(filename)
        } catch (error) {
            console.error('Failed to download template:', error)
        }
    }

    onMounted(loadTemplates)

    return {
        letterTemplateFileInput,
        envelopeTemplateFileInput,
        deleteDialogOpen,
        templateToDelete,
        envelopeTemplates,
        deleteInProgress,
        uploadInProgress,
        resetInProgress,
        uploadMessage,
        canDeleteTemplate,
        openLetterTemplateFilePicker,
        openEnvelopeTemplateFilePicker,
        handleLetterTemplateFile,
        handleEnvelopeTemplateFile,
        openDeleteTemplateDialog,
        cancelDeleteTemplate,
        confirmDeleteTemplate,
        resetTemplates,
        downloadTemplateFile,
    }
}
