<template>
  <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 relative">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Shopify Bulk Page Automator</h2>

    <!-- Store Domain & Access Token -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Store Domain:</label>
        <input
          type="text"
          v-model="shopDomain"
          placeholder="your-store.myshopify.com"
          class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Access Token:</label>
        <input
          type="password"
          v-model="accessToken"
          placeholder="shpat_xxxxxxxxxxxx"
          class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
        />
      </div>
    </div>

    <!-- Pages JSON Payload -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Pages JSON Payload:</label>
      <textarea
        v-model="pagesJson"
        rows="10"
        class="w-full font-mono text-sm px-3 py-2 border rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-gray-50"
      ></textarea>
    </div>

    <!-- Submit Button -->
    <button
      @click="startProcessing"
      :disabled="loading"
      class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 cursor-pointer"
    >
      <span v-if="loading">Processing...</span>
      <span v-else>⚡ Start Processing Pages</span>
    </button>

    <!-- Response / Results Area -->
    <div v-if="results.length > 0" class="mt-6">
      <h3 class="font-bold text-gray-700 mb-2">Execution Results:</h3>
      <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-xs max-h-60 overflow-y-auto">
        <div v-for="(res, index) in results" :key="index" class="mb-1">
          <span :class="res.success ? 'text-green-400' : 'text-red-400'">
            [{{ res.success ? 'SUCCESS' : 'FAILED' }}] {{ res.title }}
          </span>
          <p v-if="!res.success" class="text-red-300 ml-4">{{ res.message }}</p>
        </div>
      </div>
    </div>

    <!-- Middle Overlay Popup Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-all">

        <!-- Loading / Processing State -->
        <div v-if="loading" class="text-center py-6">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-emerald-600 border-t-transparent mb-4"></div>
          <h3 class="text-lg font-bold text-gray-800">Processing Pages...</h3>
          <p class="text-sm text-gray-500 mt-1">Please wait while we push pages to Shopify.</p>

          <div class="mt-6 bg-gray-100 rounded-lg p-3 flex justify-around text-sm font-medium text-gray-700">
            <div>⏱️ Time Taken: <span class="text-emerald-600 font-bold">{{ elapsedTime }}s</span></div>
            <div>📦 Total Items: <span class="text-emerald-600 font-bold">{{ totalPagesCount }}</span></div>
          </div>
        </div>

        <!-- Completed State -->
        <div v-else class="py-2">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Batch Operation Completed</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
          </div>

          <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm flex justify-between">
            <span>Total Time: <b class="text-emerald-600">{{ elapsedTime }} seconds</b></span>
            <span>Processed: <b class="text-emerald-600">{{ results.length }} items</b></span>
          </div>

          <!-- Result Items List -->
          <div class="max-h-60 overflow-y-auto space-y-2 mb-5 pr-1">
            <div v-for="(res, index) in results" :key="index" class="p-2.5 rounded-lg text-xs flex items-start justify-between border" :class="res.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
               <div>
                   <span class="font-bold block">{{ res.title }}</span>
                   <span class="text-[11px] opacity-80">{{ res.success ? 'Successfully published' : res.message }}</span>
               </div>
               <span class="font-bold px-1.5 py-0.5 rounded text-[10px]" :class="res.success ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900'">
                   {{ res.success ? 'SUCCESS' : 'FAILED' }}
               </span>
            </div>
          </div>

          <button @click="showModal = false" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition shadow">
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
      shopDomain: 'example-store.myshopify.com',
      accessToken: '',
      pagesJson: JSON.stringify([
        {
          "title": "About Our Winter Collection",
          "handle": "about-winter-collection",
          "body_html": "<h1>Welcome to Winter Baby Care</h1><p>We provide the softest and warmest winter wear.</p>",
          "is_published": true,
          "seo_title": "About Our Winter Baby Collection",
          "seo_description": "Discover cozy winter essentials for babies."
        }
      ], null, 2),
      loading: false,
      results: [],
      showModal: false,
      elapsedTime: 0,
      totalPagesCount: 0,
      timerInterval: null
    };
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
    async startProcessing() {
      if (!this.shopDomain || !this.accessToken) {
        this.results = [{
          title: 'Validation Error',
          success: false,
          message: 'Please enter store domain and access token.'
        }];
        this.totalPagesCount = 0;
        this.elapsedTime = 0;
        this.loading = false;
        this.showModal = true;
        return;
      }

      let parsedPages;
      try {
        parsedPages = JSON.parse(this.pagesJson);
      } catch (e) {
        this.results = [{
          title: 'Invalid JSON Payload',
          success: false,
          message: 'Please check your JSON syntax. ' + e.message
        }];
        this.totalPagesCount = 0;
        this.elapsedTime = 0;
        this.loading = false;
        this.showModal = true;
        return;
      }

      this.loading = true;
      this.results = [];
      this.showModal = true;
      this.totalPagesCount = parsedPages.length;

      this.startTimer();

      try {
        const response = await axios.post('/api/shopify/pages/bulk-create', {
          shop_domain: this.shopDomain,
          access_token: this.accessToken,
          pages: parsedPages
        });

        this.stopTimer();
        this.loading = false;

        if (response.data.success) {
          this.results = response.data.results || [];
        } else {
          this.results = [{
            title: 'API Error',
            success: false,
            message: response.data.message || 'Something went wrong!'
          }];
        }
      } catch (error) {
        this.stopTimer();
        this.loading = false;
        this.results = [{
          title: 'Request Exception',
          success: false,
          message: error.response?.data?.message || error.message
        }];
      }
    }
  }
};
</script>
