import { createApp } from 'vue';
import AutomationDashboard from './components/AutomationDashboard.vue';

const app = createApp({});

// AutomationDashboard কম্পোনেন্টটি রেজিস্টার করা হলো
app.component('automation-dashboard', AutomationDashboard);

app.mount('#app');
