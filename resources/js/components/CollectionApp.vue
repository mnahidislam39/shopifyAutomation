<template>
    <div class="max-w-4xl mx-auto my-10 bg-white p-8 border border-gray-200 rounded-xl shadow-sm relative">
        <h2 class="text-2xl font-bold text-slate-800 text-center mb-8">Shopify Bulk Collection Automator</h2>

        <form @submit.prevent="submitCollections" class="space-y-6">
            <!-- Store Domain & Admin Access Token Side by Side -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Store Domain:</label>
                    <input v-model="form.shop_domain" placeholder="example.myshopify.com"
                        class="w-full px-4 py-2.5 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
                        required />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Admin Access Token:</label>
                    <input v-model="form.access_token" type="password" placeholder="shpat_xxxxxxxxxxxxxxxxxxxx"
                        class="w-full px-4 py-2.5 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
                        required />
                </div>
            </div>

            <!-- Collections JSON Payload -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Collections JSON Payload:</label>
                <textarea v-model="jsonInput" rows="10"
                    class="w-full p-4 font-mono text-sm text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
                    required></textarea>
            </div>

            <!-- Live Timer & Progress Bar (Visible when loading) -->
            <div v-if="loading"
                class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between shadow-inner transition-all">
                <div class="flex items-center gap-3">
                    <span class="text-2xl animate-spin">⏳</span>
                    <div>
                        <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Processing Request...</p>
                        <p class="text-sm font-mono font-semibold text-emerald-900">Time Elapsed: {{ elapsedTime }}s</p>
                    </div>
                </div>
                <div class="w-32 bg-emerald-200 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-emerald-600 h-2.5 rounded-full animate-pulse w-full"></div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                <span v-if="loading" class="animate-spin text-lg">🕒</span>
                <span>{{ loading ? `Processing (${elapsedTime}s)...` : '⚡Start Processing' }}</span>
            </button>
        </form>

        <!-- Execution & Database Status Summary & Grid List -->
        <div v-if="results && results.length" class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Execution & Database Status:</h3>
                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full">
                    Total Created: {{ results.length }}
                </span>
            </div>

            <!-- Grid Layout to Save Vertical Space -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div v-for="(item, index) in results" :key="index" :class="[
                    'p-3 rounded-lg border text-xs transition-all flex flex-col justify-between shadow-xs',
                    item.status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'
                ]">
                    <div>
                        <span class="font-bold block truncate text-sm mb-1" :title="item.title">{{ item.title }}</span>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                            :class="item.status === 'success' ? 'bg-emerald-200 text-emerald-800' : 'bg-rose-200 text-rose-800'">
                            {{ item.status ? item.status : 'ERROR' }}
                        </span>
                    </div>
                    <span v-if="item.message" class="mt-2 text-[11px] opacity-80 truncate" :title="item.message">
                        {{ item.message }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            form: { shop_domain: '', access_token: '' },
            jsonInput: JSON.stringify([
                {
                    "type": "smart",
                    "title": "Smart Helmets 2026",
                    "handle": "smart-helmets-2026",
                    "description": "<h2>Best Safety Helmets</h2><p>Explore high quality protective gear.</p>",
                    "template_suffix": "grid-layout",
                    "image_url": "https://cdn.shopify.com/s/files/1/0589/6968/6134/files/Sdad449d0179040a6a0e886e06795be260.webp?v=1788792579",
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "helmet"
                        }
                    ]
                }
            ], null, 2),
            loading: false,
            results: [],
            elapsedTime: 0,
            timerInterval: null
        }
    },
    methods: {
        startTimer() {
            this.elapsedTime = 0;
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                this.elapsedTime++;
            }, 1000);
        },
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },
        async submitCollections() {
            try {
                this.loading = true;
                this.results = [];
                this.startTimer(); // টাইমার চালু করা হলো

                const collectionsArray = JSON.parse(this.jsonInput);

                const response = await axios.post('/api/create-collections', {
                    shop_domain: this.form.shop_domain,
                    access_token: this.form.access_token,
                    collections: collectionsArray
                });

                this.results = response.data.results || response.data.data || [];

                const hasError = this.results.some(item => item.status && item.status !== 'success');

                if (hasError) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Notice ⚠️', `Some collections failed. Completed in ${this.elapsedTime}s.`, 'error');
                    }
                } else {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Success! 🎉', `All collections created successfully in ${this.elapsedTime}s!`, 'success');
                    }
                }

            } catch (error) {
                const errorMsg = error.response?.data?.message || 'Error executing request.';

                if (typeof window.showToast === 'function') {
                    window.showToast('Oops... ❌', `${errorMsg} (Took ${this.elapsedTime}s)`, 'error');
                }

                this.results = [{
                    title: 'System Error',
                    status: 'failed',
                    message: errorMsg
                }];
            } finally {
                this.loading = false;
                this.stopTimer(); // টাইমার বন্ধ করা হলো
            }
        }
    }
}
</script>
