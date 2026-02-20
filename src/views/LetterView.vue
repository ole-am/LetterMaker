<template>
    <div class="letter-page">
        <div class="container">
            <div class="section">
                <h2 class="letter_page_heading">{{ t('lettermaker', 'LetterMaker') }}</h2>

                <div class="address_form">
                    <div class="recipient_address">
                        <h4>{{ t('lettermaker', 'Recipient') }}</h4>
                        <NcTextField class="required-field" :label="t('lettermaker', 'Name')"
                            :show-trailing-button="false" :required="true" v-model="recipient_name" />

                        <NcTextField :label="t('lettermaker', 'Additional infos')" :show-trailing-button="false"
                            v-model="recipient_info" />
                        <NcTextField class="required-field" :label="t('lettermaker', 'Street')"
                            :show-trailing-button="false" :required="true" v-model="recipient_street" />

                        <div class="grid grid-2">
                            <NcTextField class="required-field" :label="t('lettermaker', 'ZIP Code')"
                                :show-trailing-button="false" :required="true" v-model="recipient_zip" />
                            <NcTextField class="required-field" :label="t('lettermaker', 'City')"
                                :show-trailing-button="false" :required="true" v-model="recipient_city" />
                        </div>

                    </div>
                    <div class="address_form_spacer" aria-hidden="true"></div>
                    <div class="sender_address">
                        <h4 style="text-align: end;">{{ t('lettermaker', 'Sender') }}</h4>
                        <NcTextField class="required-field" :label="t('lettermaker', 'Name')"
                            :show-trailing-button="false" :required="true" v-model="sender_name" />

                        <NcTextField :label="t('lettermaker', 'Additional infos')" :show-trailing-button="false"
                            v-model="sender_info" />
                        <NcTextField class="required-field" :label="t('lettermaker', 'Street')"
                            :show-trailing-button="false" :required="true" v-model="sender_street" />

                        <div class="grid grid-2">
                            <NcTextField class="required-field" :label="t('lettermaker', 'ZIP Code')"
                                :show-trailing-button="false" :required="true" v-model="sender_zip" />
                            <NcTextField class="required-field" :label="t('lettermaker', 'City')"
                                :show-trailing-button="false" :required="true" v-model="sender_city" />
                        </div>
                    </div>
                </div>
                <br>
                <div class="subject_date_row">
                    <div class="subject_left_column">
                        <NcTextField type="text" :placeholder="t('lettermaker', 'Subject')" v-model="subject" />
                    </div>
                    <NcDateTimePicker v-model="dateValue" :label="t('lettermaker', 'Date') + ': ' + formattedDate"
                        :show-trailing-button="false" />
                </div>
                <br>
                <div ref="editorRoot" class="quill-editor"></div>
                <br>

                <br>
                <NcFilePicker ref="picker" directory @pick="selectedFiles = $event" />
                <div class="letter_actions_row">
                    <NcButton class="letter_actions_print" type="primary" :value="'letter'" @click="printLetter">
                        {{ t('lettermaker', 'Print Letter') }}
                    </NcButton>
                    <div class="letter_actions_right">
                        <div class="letter_actions_stamp">
                            <NcButton @click="$refs.stampUpload.click()">
                                {{ stamp_file_name ? '✅ ' + t('lettermaker', 'Stamp uploaded') : t('lettermaker',
                                    'Upload stamp') }}
                            </NcButton>
                            <input ref="stampUpload" type="file" accept="application/pdf" style="display: none"
                                @change="handleStampFile" />
                        </div>
                        <div class="letter_actions_envelope">
                            <select v-model="selected_envelope_template" class="envelope_type_select">
                                <option v-for="template in envelopeTemplates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                            <NcButton type="primary" @click="printEnvelope">
                                {{ t('lettermaker', 'Print Envelope') }}
                            </NcButton>
                        </div>
                    </div>
                </div>
                <NcDialog :open="modal" :name="t('lettermaker', 'Information')" @close="closeMissingFieldsModal">
                    <div class="error_model_content">
                        {{ t('lettermaker', 'Please fill in missing fields') }}
                    </div>
                    <template #actions>
                        <NcButton type="primary" @click="closeMissingFieldsModal">Ok</NcButton>
                    </template>
                </NcDialog>
            </div>
        </div>
    </div>
</template>

<script>
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import NcButton from '@nextcloud/vue/components/NcButton'
import { translate as t } from '@nextcloud/l10n'
import { useEnvelopeTemplates } from '../composables/useEnvelopeTemplates'
import { useLetterEditor } from '../composables/useLetterEditor'
import { useLetterForm } from '../composables/useLetterForm'
import { useDocumentPrint } from '../composables/useDocumentPrint'
import { useStampUpload } from '../composables/useStampUpload'

export default {
    name: 'LetterView',
    components: {
        NcDialog,
        NcTextField,
        NcDateTimePicker,
        NcButton,
    },
    setup() {
        const form = useLetterForm()
        const editor = useLetterEditor()
        const templates = useEnvelopeTemplates()
        const stamp = useStampUpload()
        const documentPrint = useDocumentPrint({
            form,
            editor,
            templates,
            stamp,
        })

        return {
            t,
            ...form,
            ...editor,
            ...templates,
            ...stamp,
            ...documentPrint,
        }
    },
}
</script>
