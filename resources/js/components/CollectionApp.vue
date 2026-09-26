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

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                <span v-if="loading" class="animate-spin text-lg">🕒</span>
                <span>{{ loading ? `Processing...` : '⚡Start Processing' }}</span>
            </button>
        </form>

        <!-- Middle Overlay Popup Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-all">

                <!-- Loading / Processing State -->
                <div v-if="loading" class="text-center py-6">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-emerald-600 border-t-transparent mb-4"></div>
                    <h3 class="text-lg font-bold text-gray-800">Processing Collections...</h3>
                    <p class="text-sm text-gray-500 mt-1">Please wait while we push collections to Shopify.</p>

                    <div class="mt-6 bg-gray-100 rounded-lg p-3 flex justify-around text-sm font-medium text-gray-700">
                        <div>⏱️ Time Taken: <span class="text-emerald-600 font-bold">{{ elapsedTime }}s</span></div>
                        <div>📦 Total Items: <span class="text-emerald-600 font-bold">{{ totalItemsCount }}</span></div>
                    </div>
                </div>

                <!-- Completed State -->
                <div v-else class="py-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-slate-800">Batch Operation Completed</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm flex justify-between">
                        <span>Total Time Taken: <b class="text-emerald-600">{{ elapsedTime }} seconds</b></span>
                        <span>Total Processed: <b class="text-emerald-600">{{ results.length }} items</b></span>
                    </div>

                    <!-- Result Grid List -->
                    <div class="max-h-60 overflow-y-auto space-y-2 mb-5 pr-1">
                        <div v-for="(item, index) in results" :key="index" class="p-3 rounded-lg text-xs flex items-start justify-between border" :class="item.status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
                           <div>
                               <span class="font-bold block text-sm mb-0.5">{{ item.title }}</span>
                               <span class="text-[11px] opacity-80">{{ item.message || (item.status === 'success' ? 'Successfully created collection' : 'Failed to create') }}</span>
                           </div>
                           <span class="font-bold px-2 py-1 rounded text-[10px] uppercase" :class="item.status === 'success' ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900'">
                               {{ item.status || 'ERROR' }}
                           </span>
                        </div>
                    </div>

                    <button @click="showModal = false" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition shadow cursor-pointer">
                        Close / Done
                    </button>
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
            showModal: false,
            elapsedTime: 0,
            totalItemsCount: 0,
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
            let collectionsArray;
            try {
                collectionsArray = JSON.parse(this.jsonInput);
            } catch (e) {
                this.results = [{
                    title: 'Invalid JSON Payload',
                    status: 'failed',
                    message: 'Please check your JSON syntax. ' + e.message
                }];
                this.totalItemsCount = 0;
                this.elapsedTime = 0;
                this.loading = false;
                this.showModal = true;
                return;
            }

            try {
                this.loading = true;
                this.results = [];
                this.showModal = true;
                this.totalItemsCount = collectionsArray.length;
                this.startTimer();

                const response = await axios.post('/api/create-collections', {
                    shop_domain: this.form.shop_domain,
                    access_token: this.form.access_token,
                    collections: collectionsArray
                });

                this.stopTimer();
                this.loading = false;
                this.results = response.data.results || response.data.data || [];

            } catch (error) {
                this.stopTimer();
                this.loading = false;
                const errorMsg = error.response?.data?.message || error.message;

                this.results = [{
                    title: 'System Error',
                    status: 'failed',
                    message: errorMsg
                }];
            }
        }
    }
}
</script>
