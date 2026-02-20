import { createApp } from 'vue'
import AdminView from './views/AdminView.vue'
import './styles/global.css'

const adminApp = createApp(AdminView)
adminApp.mount('#lettermaker-admin-app')
