<template>
    <div class="container">
        <div class="section">
            <NcSettingsSection
                class="admin-settings-section"
                name="LetterMaker"
                :description="t('lettermaker', 'Set custom templates for the document generation')" />




            <h4>{{ t('lettermaker', 'Letters') }}</h4>
            <input
                ref="letterTemplateFileInput"
                type="file"
                accept=".html,text/html"
                class="hidden-file-input"
                @change="handleLetterTemplateFile">
            <NcButton :disabled="uploadInProgress" @click="openLetterTemplateFilePicker">{{ t('lettermaker', 'Replace template') }}</NcButton>

            <div class="heading-row">
                <h4>{{ t('lettermaker', 'Envelopes') }}</h4>
                <p
                    class="heading-row__hint"
                    v-html='t("lettermaker", "To use the Stamp feature, please select the format \"<strong>Seiko SLP-Stamp 1 42x36</strong>\" when purchasing.")'>
                </p>
            </div>

            <ul class="template-list">
                <li
                    v-for="template in envelopeTemplates"
                    :key="template.id"
                    class="template-list__item">
                    <div class="template-list__meta">
                        <strong>{{ template.name }}</strong>
                        <span>{{ template.filename }}</span>
                    </div>
                    <div class="template-list__actions">
                        <NcButton
                            :aria-label="t('lettermaker', 'Download template')"
                            @click="downloadTemplateFile(template)">
                            <template #icon>
                                <svg
                                    class="template-delete-icon"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path
                                        fill="currentColor"
                                        d="M5 20h14v-2H5v2zM11 4h2v8h3l-4 4-4-4h3V4z" />
                                </svg>
                            </template>
                        </NcButton>
                        <NcButton
                            :aria-label="t('lettermaker', 'Delete template')"
                            :disabled="!canDeleteTemplate(template)"
                            @click="openDeleteTemplateDialog(template)">
                            <template #icon>
                                <svg
                                    class="template-delete-icon"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path
                                        fill="currentColor"
                                        d="M9 3h6l1 1h5v2H3V4h5l1-1zm-3 5h12v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8zm3 2v9h2v-9H9zm4 0v9h2v-9h-2z" />
                                </svg>
                            </template>
                        </NcButton>
                    </div>
                </li>
            </ul>
            <input
                ref="envelopeTemplateFileInput"
                type="file"
                accept=".html,text/html"
                class="hidden-file-input"
                @change="handleEnvelopeTemplateFile">
            <NcButton :disabled="uploadInProgress" @click="openEnvelopeTemplateFilePicker">
                {{ t('lettermaker', 'Upload envelope template') }}
            </NcButton>
            <p v-if="uploadMessage" class="upload-message">{{ uploadMessage }}</p>

            <NcDialog
                :open="deleteDialogOpen"
                :name="t('lettermaker', 'Delete template')"
                @close="cancelDeleteTemplate">
                <div class="delete-dialog-content">
                    {{ t('lettermaker', 'Do you really want to delete this template?') }}
                    <strong v-if="templateToDelete">
                        {{ templateToDelete.name }} ({{ templateToDelete.filename }})
                    </strong>
                </div>
                <template #actions>
                    <NcButton @click="cancelDeleteTemplate">
                        {{ t('lettermaker', 'Cancel') }}
                    </NcButton>
                    <NcButton type="primary" :disabled="deleteInProgress" @click="confirmDeleteTemplate">
                        {{ t('lettermaker', 'Confirm') }}
                    </NcButton>
                </template>
            </NcDialog>
            <h4>{{ t('lettermaker', 'Annotations') }}</h4>
                        <NcNoteCard class="annotations-note-card" type="info">
                <p><strong>{{ t('lettermaker', 'To create custom templates, the following attributes need to be defined within the <style>-Tag') }}</strong></p>
                <p>
                    <code>
                        &lt;style&gt;
                            <ul>
                                <li>/* @template-type: envelope */</li>
                                <li>/* @template-id: my-custom-envelope */</li>
                                <li>/* @template-name: Custom Envelope */</li>
                                <li>/* @template-height: 114mm */</li>
                                <li>/* @template-width: 162mm */</li>
                                <li>/* @stamp-position-x: 105 */</li>
                                <li>/* @stamp-position-y: 15 */</li>
                                <li>/* @stamp-height: 35 */</li>
                                <li>/* @stamp-width: 42 */</li>
                                <li>/* @stamp-rotation: -90 */</li>
                            </ul>
                        &lt;/style&gt;
                    </code>
                </p>
                <p><strong>{{ t('lettermaker', 'Templates should be named after the template-id+.html') }}</strong></p>

                <p><strong>{{ t('lettermaker', 'Furthermore, within the template file these placeholders should be implemented') }}</strong></p>
                <p>
                    <code>
                            <ul>
                                <li>{sender_name}</li> 
                                <li>{sender_street}</li>
                                <li>{sender_zip}</li>
                                <li>{sender_city}</li>
                                <li>{recipient_name}</li>
                                <li>{recipient_info}</li>
                                <li>{recipient_street}</li>
                                <li>{recipient_zip}</li>
                                <li>{recipient_city}</li>
                                <li>{date}</li>
                                <li>{subject}</li>
                                <li>{lettertext_html}</li>
                            </ul>
                    </code>
                </p>

<NcButton @click="downloadTemplateFile('letter')">{{ t('lettermaker', 'Download sample template') }}</NcButton>
            </NcNoteCard>
            <h4>{{ t('lettermaker', 'Others') }}</h4>
            <NcButton :disabled="resetInProgress" @click="resetTemplates">{{ t('lettermaker', 'Reset templates') }}</NcButton>
            <br>
            <p>{{ t('lettermaker', 'The standard templates are based on DIN standards but differ in certain aspects. They are provided for guidance purposes only and may vary depending on individual print settings. Therefore, no liability or warranty is assumed.') }}</p>
        </div>
    </div>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { useTemplateAdmin } from '../composables/useTemplateAdmin'

export default {
    name: 'AdminView',
    components: {
        NcSettingsSection,
        NcNoteCard,
        NcButton,
        NcDialog,
    },
    setup() {
        const admin = useTemplateAdmin()
        return {
            t,
            ...admin,
        }
    },
}
</script>
