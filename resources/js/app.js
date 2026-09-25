import { createApp } from 'vue';
import AutomationDashboard from './components/AutomationDashboard.vue';
import '../css/app.css';
const app = createApp({});
import Toast from './components/Toast.vue'; // Toast component import korun

// AutomationDashboard কম্পোনেন্টটি রেজিস্টার করা হলো
app.component('automation-dashboard', AutomationDashboard);
app.component('Toast', Toast);

app.mount('#app');

