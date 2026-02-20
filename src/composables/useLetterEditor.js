import { onBeforeUnmount, onMounted, ref } from 'vue'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const TOOLBAR_OPTIONS = [
    ['bold', 'italic', 'underline', 'strike'],
    [{ header: 1 }, { header: 2 }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ indent: '-1' }, { indent: '+1' }],
    [{ align: [] }],
    ['link'],
    ['clean'],
]

export function useLetterEditor() {
    const editorRoot = ref(null)
    const editor = ref(null)
    const lettertext_html = ref('')
    const lettertext = ref('')

    function syncEditorContent() {
        if (!editor.value) {
            return false
        }
        lettertext_html.value = editor.value.root.innerHTML
        lettertext.value = editor.value.getText()
        return true
    }

    function handleEditorInput() {
        syncEditorContent()
    }

    onMounted(() => {
        editor.value = new Quill(editorRoot.value, {
            theme: 'snow',
            modules: {
                toolbar: TOOLBAR_OPTIONS,
            },
        })
        editor.value.on('text-change', handleEditorInput)
    })

    onBeforeUnmount(() => {
        if (editor.value) {
            editor.value.off('text-change', handleEditorInput)
        }
        editor.value = null
    })

    return {
        editorRoot,
        lettertext_html,
        lettertext,
        syncEditorContent,
    }
}
