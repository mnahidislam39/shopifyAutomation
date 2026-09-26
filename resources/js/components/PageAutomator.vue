<template>
  <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
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
      results: []
    };
  },
  methods: {
    async startProcessing() {
      if (!this.shopDomain || !this.accessToken) {
        alert('Please enter store domain and access token.');
        return;
      }

      let parsedPages;
      try {
        parsedPages = JSON.parse(this.pagesJson);
      } catch (e) {
        alert('Invalid JSON format. Please correct it.');
        return;
      }

      this.loading = true;
      this.results = [];

      try {
        const response = await axios.post('/api/shopify/pages/bulk-create', {
  shop_domain: this.shopDomain,
  access_token: this.accessToken,
  pages: parsedPages
});

        if (response.data.success) {
          this.results = response.data.results;
        } else {
          alert('Something went wrong!');
        }
      } catch (error) {
        alert(error.response?.data?.message || error.message);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
