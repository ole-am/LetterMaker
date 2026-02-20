import { createApp } from 'vue'
import App from './App.vue'
import AdminView from './views/AdminView.vue'
import './styles/global.css'
import './styles/letterpage.css'
import './styles/adminpage.css'
import './styles/quill.css'

const appElement = document.getElementById('lettermaker-app')
if (appElement) {
    const app = createApp(App)
    app.mount('#lettermaker-app')
}

const adminAppElement = document.getElementById('lettermaker-admin-app')
if (adminAppElement) {
    const adminApp = createApp(AdminView)
    adminApp.mount('#lettermaker-admin-app')
}
