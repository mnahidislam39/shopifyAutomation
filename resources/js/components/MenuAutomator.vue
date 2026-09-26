<template>
  <div class="max-w-4xl mx-auto my-10 bg-white p-8 border border-gray-200 rounded-xl shadow-sm relative">
    <h2 class="text-2xl font-bold text-slate-800 text-center mb-6">
      Shopify Navigation Creator (Nested Sub-menus)
    </h2>

    <form @submit.prevent="submitMenu" class="space-y-5">
      <!-- Store Domain & Admin Access Token Side by Side -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Store Domain:</label>
          <input
            v-model="form.shop_domain"
            placeholder="example.myshopify.com"
            class="w-full px-4 py-2.5 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
            required
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Admin Access Token:</label>
          <input
            v-model="form.access_token"
            type="password"
            placeholder="shpat_xxxxxxxxxxxxxxxx"
            class="w-full px-4 py-2.5 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
            required
          />
        </div>
      </div>

      <!-- Action Control Buttons -->
      <div class="flex justify-between items-center bg-gray-50 p-3.5 rounded-lg flex-wrap gap-3 border border-gray-100">
        <span class="text-xs font-semibold text-gray-600">Templates & Import:</span>
        <div class="flex gap-2 flex-wrap">
          <button
            type="button"
            @click="fetchExistingCollections"
            :disabled="fetching"
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-md text-xs font-semibold cursor-pointer transition-colors disabled:opacity-50"
          >
            {{ fetching ? 'Fetching...' : '⚡ Auto Fetch' }}
          </button>
          <button
            type="button"
            @click="loadTemplate('normal')"
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white border-none rounded-md text-xs font-semibold cursor-pointer transition-colors"
          >
            📄 Normal Menu
          </button>
          <button
            type="button"
            @click="loadTemplate('submenu')"
            class="px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white border-none rounded-md text-xs font-semibold cursor-pointer transition-colors"
          >
            📂 With Sub-menu
          </button>
          <button
            type="button"
            @click="loadTemplate('nested')"
            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white border-none rounded-md text-xs font-semibold cursor-pointer transition-colors"
          >
            🌲 Nested Sub-menu
          </button>
        </div>
      </div>

      <!-- Menu Title -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Menu Title:</label>
        <input
          v-model="form.menu_title"
          placeholder="Main Navigation"
          class="w-full px-4 py-2.5 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
          required
        />
      </div>

      <!-- JSON Structure Area -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Menu JSON Structure (Supports Multi-level Nested Sub-menus):</label>
        <textarea
          v-model="jsonInput"
          rows="14"
          class="w-full p-4 font-mono text-sm text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition-all"
          required
        ></textarea>
      </div>

      <!-- Live Timer & Progress Bar (Visible when loading) -->
      <div v-if="loading" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between shadow-inner transition-all">
        <div class="flex items-center gap-3">
          <span class="text-2xl animate-spin">⏳</span>
          <div>
            <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Creating Menu...</p>
            <p class="text-sm font-mono font-semibold text-emerald-900">Time Elapsed: {{ elapsedTime }}s</p>
          </div>
        </div>
        <div class="w-32 bg-emerald-200 rounded-full h-2.5 overflow-hidden">
          <div class="bg-emerald-600 h-2.5 rounded-full animate-pulse w-full"></div>
        </div>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg shadow-md transition-colors cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
      >
        <span v-if="loading" class="animate-spin text-lg">🕒</span>
        <span>{{ loading ? `Creating Navigation (${elapsedTime}s)...` : '⚡Start Processing' }}</span>
      </button>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

const templates = {
  normal: [
    { "title": "Home", "url": "/" },
    { "title": "Catalog", "url": "/collections/all" },
    { "title": "About Us", "url": "/pages/about-us" },
    { "title": "Contact", "url": "/pages/contact" }
  ],
  submenu: [
    { "title": "Home", "url": "/" },
    {
      "title": "Shop",
      "url": "/collections/all",
      "items": [
        { "title": "Smart Helmets 2026", "url": "/collections/smart-helmets-2026" },
        { "title": "Riding Jackets", "url": "/collections/riding-jackets" },
        { "title": "Motorcycle Gloves", "url": "/collections/motorcycle-gloves" }
      ]
    },
    { "title": "About Us", "url": "/pages/about-us" },
    { "title": "Contact", "url": "/pages/contact" }
  ],
  nested: [
    { "title": "Home", "url": "/" },
    {
      "title": "Shop",
      "url": "/collections/all",
      "items": [
        {
          "title": "Motorcycle Gear",
          "url": "/collections/all",
          "items": [
            { "title": "Smart Helmets 2026", "url": "/collections/smart-helmets-2026" },
            { "title": "Riding Jackets", "url": "/collections/riding-jackets" }
          ]
        },
        { "title": "Motorcycle Gloves", "url": "/collections/motorcycle-gloves" }
      ]
    },
    { "title": "About Us", "url": "/pages/about-us" },
    { "title": "Contact", "url": "/pages/contact" }
  ]
};

export default {
  data() {
    return {
      form: {
        shop_domain: '',
        access_token: '',
        menu_title: 'Main Navigation'
      },
      jsonInput: JSON.stringify(templates.submenu, null, 2),
      loading: false,
      fetching: false,
      elapsedTime: 0,
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
    loadTemplate(type) {
      if (templates[type]) {
        this.jsonInput = JSON.stringify(templates[type], null, 2);
        if (typeof window.showToast === 'function') {
          window.showToast('Template Loaded! ✨', `Loaded ${type} menu template successfully!`, 'success');
        }
      }
    },

    async fetchExistingCollections() {
      if (!this.form.shop_domain || !this.form.access_token) {
        if (typeof window.showToast === 'function') {
          window.showToast('Missing Info ⚠️', 'Please enter Store Domain and Access Token first!', 'error');
        }
        return;
      }

      try {
        this.fetching = true;
        const res = await axios.get('/api/get-collections', {
          params: {
            shop_domain: this.form.shop_domain,
            access_token: this.form.access_token
          }
        });

        if (res.data.success && res.data.collections) {
          const collections = res.data.collections;

          const autoMenu = [
            { "title": "Home", "url": "/" },
            {
              "title": "Shop",
              "url": "/collections/all",
              "items": collections.map(col => ({ title: col.title, url: col.url }))
            },
            { "title": "About Us", "url": "/pages/about-us" },
            { "title": "Contact", "url": "/pages/contact" }
          ];

          this.jsonInput = JSON.stringify(autoMenu, null, 2);
          if (typeof window.showToast === 'function') {
            window.showToast('Fetched! 🚀', `Successfully fetched ${collections.length} collections into sub-menu!`, 'success');
          }
        }
      } catch (err) {
        if (typeof window.showToast === 'function') {
          window.showToast('Failed ❌', 'Failed to fetch collections. Check Store Domain and Access Token.', 'error');
        }
      } finally {
        this.fetching = false;
      }
    },

    async submitMenu() {
      try {
        this.loading = true;
        this.startTimer();

        const response = await axios.post('/api/create-menu', {
          shop_domain: this.form.shop_domain,
          access_token: this.form.access_token,
          title: this.form.menu_title,
          items: JSON.parse(this.jsonInput)
        });

        if (typeof window.showToast === 'function') {
          window.showToast('Success! 🎉', `${response.data.message || 'Menu created successfully!'} (Took ${this.elapsedTime}s)`, 'success');
        }
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Failed to create menu.';
        if (typeof window.showToast === 'function') {
          window.showToast('Oops... ❌', `${errorMsg} (Took ${this.elapsedTime}s)`, 'error');
        }
      } finally {
        this.loading = false;
        this.stopTimer();
      }
    }
  }
};
</script>
