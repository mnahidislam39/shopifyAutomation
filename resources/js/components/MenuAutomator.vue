<template>
  <div style="padding: 24px; background: #ffffff; border-radius: 8px; border: 1px solid #e1e3e5; max-width: 900px; margin: 0 auto;">
    <h2 style="color: #202223; margin-top: 0; margin-bottom: 20px; font-size: 20px;">
      Shopify Header & Navigation Creator (Nested Sub-menus)
    </h2>

    <form @submit.prevent="submitMenu">
      <!-- Domain & Token -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
        <div>
          <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px;">Store Domain:</label>
          <input
            v-model="form.shop_domain"
            placeholder="example.myshopify.com"
            style="width: 100%; padding: 8px 12px; border: 1px solid #c9cccf; border-radius: 4px; box-sizing: border-box;"
            required
          />
        </div>
        <div>
          <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px;">Admin Access Token:</label>
          <input
            v-model="form.access_token"
            type="password"
            placeholder="shpat_xxxxxxxxxxxxxxxx"
            style="width: 100%; padding: 8px 12px; border: 1px solid #c9cccf; border-radius: 4px; box-sizing: border-box;"
            required
          />
        </div>
      </div>

      <!-- Action Control Buttons -->
<!-- Action Control Buttons -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #f6f6f7; padding: 10px; border-radius: 6px; flex-wrap: wrap; gap: 10px;">
  <span style="font-size: 13px; font-weight: 600; color: #4a4a4a;">Templates & Import:</span>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <button
      type="button"
      @click="fetchExistingCollections"
      :disabled="fetching"
      style="padding: 6px 12px; background-color: #005bd3; color: white; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;"
    >
      {{ fetching ? 'Fetching...' : '⚡ Auto Fetch' }}
    </button>
    <button
      type="button"
      @click="loadTemplate('normal')"
      style="padding: 6px 12px; background-color: #303030; color: white; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;"
    >
      📄 Normal Menu
    </button>
    <button
      type="button"
      @click="loadTemplate('submenu')"
      style="padding: 6px 12px; background-color: #5c5f62; color: white; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;"
    >
      📂 With Sub-menu
    </button>
    <button
      type="button"
      @click="loadTemplate('nested')"
      style="padding: 6px 12px; background-color: #108043; color: white; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;"
    >
      🌲 Nested Sub-menu
    </button>
  </div>
</div>
      <!-- Menu Title -->
      <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px;">Menu Title:</label>
        <input
          v-model="form.menu_title"
          placeholder="Main Navigation"
          style="width: 100%; padding: 8px 12px; border: 1px solid #c9cccf; border-radius: 4px; box-sizing: border-box;"
          required
        />
      </div>

      <!-- JSON Structure Area -->
      <div style="margin-bottom: 20px;">
        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px;">Menu JSON Structure (Supports Multi-level Nested Sub-menus):</label>
        <textarea
          v-model="jsonInput"
          rows="16"
          style="width: 100%; padding: 12px; font-family: monospace; font-size: 13px; border: 1px solid #c9cccf; border-radius: 4px; background: #f9fafb; box-sizing: border-box;"
          required
        ></textarea>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading"
        style="width: 100%; padding: 12px; background-color: #008060; color: #ffffff; font-weight: bold; font-size: 15px; border: none; border-radius: 4px; cursor: pointer;"
      >
        {{ loading ? 'Creating Nested Navigation...' : 'Create Navigation Menu' }}
      </button>
    </form>

    <!-- Response Message -->
    <div
      v-if="result"
      style="margin-top: 20px; padding: 12px 15px; border-radius: 4px; font-size: 14px;"
      :style="{ backgroundColor: result.success ? '#e6f4ea' : '#fce8e6', color: result.success ? '#137333' : '#c5221f' }"
    >
      <strong>Status:</strong> {{ result.message }}
    </div>
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
      result: null
    };
  },
  methods: {
    loadTemplate(type) {
      if (templates[type]) {
        this.jsonInput = JSON.stringify(templates[type], null, 2);
        this.result = { success: true, message: `Loaded ${type} menu template successfully!` };
      }
    },

    async fetchExistingCollections() {
      if (!this.form.shop_domain || !this.form.access_token) {
        alert('Please enter Store Domain and Access Token first!');
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
          this.result = {
            success: true,
            message: `Successfully fetched ${collections.length} collections into sub-menu!`
          };
        }
      } catch (err) {
        this.result = {
          success: false,
          message: 'Failed to fetch collections. Check Store Domain and Access Token.'
        };
      } finally {
        this.fetching = false;
      }
    },

    async submitMenu() {
      try {
        this.loading = true;
        this.result = null;

        const response = await axios.post('/api/create-menu', {
          shop_domain: this.form.shop_domain,
          access_token: this.form.access_token,
          title: this.form.menu_title,
          items: JSON.parse(this.jsonInput)
        });

        this.result = {
          success: true,
          message: response.data.message || 'Menu created successfully!'
        };
      } catch (error) {
        this.result = {
          success: false,
          message: error.response?.data?.message || 'Failed to create menu.'
        };
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
