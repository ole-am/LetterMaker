import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const TEMPLATE_BASE_URL = '/apps/lettermaker/api/templates'

function normalizeTemplate(template) {
    return {
        ...template,
        filename: template.filename ?? `${template.id}.html`,
        name: template.name ?? template.id,
    }
}

export async function fetchTemplates() {
    const response = await axios.get(generateUrl(TEMPLATE_BASE_URL))
    const templates = response.data?.templates ?? []
    return templates.map(normalizeTemplate)
}

export async function uploadTemplateHtml(file) {
    const payload = new FormData()
    payload.append('template_html', file, file.name)
    await axios.post(generateUrl(`${TEMPLATE_BASE_URL}/upload`), payload)
}

export async function deleteTemplateHtml(filename) {
    const payload = new FormData()
    payload.append('filename', filename)
    await axios.post(generateUrl(`${TEMPLATE_BASE_URL}/delete`), payload)
}

export async function resetTemplateHtml() {
    await axios.post(generateUrl(`${TEMPLATE_BASE_URL}/reset`), new FormData())
}

export async function downloadTemplateHtml(filename) {
    const response = await axios.get(generateUrl(`${TEMPLATE_BASE_URL}/download`), {
        params: { filename },
        responseType: 'blob',
    })
    const blob = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = blob
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(blob)
}
