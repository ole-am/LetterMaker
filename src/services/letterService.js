import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function downloadGeneratedLetterPdf({ fields, stampFile, filename }) {
    const payload = new FormData()

    Object.entries(fields).forEach(([key, value]) => {
        payload.append(key, value ?? '')
    })

    if (stampFile) {
        payload.append('stamp_pdf', stampFile, stampFile.name)
    }

    const response = await axios.post(
        generateUrl('/apps/lettermaker/api/generate'),
        payload,
        { responseType: 'blob' },
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
}
