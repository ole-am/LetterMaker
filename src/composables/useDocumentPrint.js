import { ref } from 'vue'
import { downloadGeneratedLetterPdf } from '../services/letterService'

export function useDocumentPrint({
    form,
    editor,
    templates,
    stamp,
}) {
    const modal = ref(false)

    function closeMissingFieldsModal() {
        modal.value = false
    }

    function openMissingFieldsModal() {
        modal.value = true
    }

    async function downloadPdf(templateName) {
        const fields = {
            recipient_name: form.recipient_name.value,
            recipient_info: form.recipient_info.value,
            recipient_street: form.recipient_street.value,
            recipient_zip: form.recipient_zip.value,
            recipient_city: form.recipient_city.value,
            sender_name: form.sender_name.value,
            sender_info: form.sender_info.value,
            sender_street: form.sender_street.value,
            sender_zip: form.sender_zip.value,
            sender_city: form.sender_city.value,
            date: form.formattedDate.value,
            subject: form.subject.value,
            lettertext: editor.lettertext.value,
            lettertext_html: editor.lettertext_html.value,
            template_name: templateName,
        }

        await downloadGeneratedLetterPdf({
            fields,
            stampFile: stamp.stamp_file.value,
            filename: `Letter_${form.recipient_name.value}_${form.formattedDate.value}.pdf`,
        })
    }

    async function printLetter() {
        if (!editor.syncEditorContent()) {
            return
        }
        if (!form.checkRequired()) {
            openMissingFieldsModal()
            return
        }
        await downloadPdf('letter')
    }

    async function printEnvelope() {
        if (!form.checkRequired()) {
            openMissingFieldsModal()
            return
        }
        // const templateName = templates.selected_envelope_template.value ?? 'din-long-envelope'
        const templateName = templates.selected_envelope_template.value
        await downloadPdf(templateName)
    }
    
    return {
        modal,
        closeMissingFieldsModal,
        printLetter,
        printEnvelope,
    }
}
