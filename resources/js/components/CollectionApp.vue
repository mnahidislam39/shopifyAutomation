<template>
    <div
        style="max-width: 700px; margin: 40px auto; font-family: system-ui, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h2 style="color: #2c3e50; text-align: center; margin-bottom: 20px;">Shopify Bulk Collection Automator</h2>

        <form @submit.prevent="submitCollections">
            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold;">Store Domain:</label>
                <input v-model="form.shop_domain" placeholder="example.myshopify.com"
                    style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold;">Admin Access Token:</label>
                <input v-model="form.access_token" type="password" placeholder="shpat_xxxxxxxxxxxxxxxxxxxx"
                    style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold;">Collections JSON Payload:</label>
                <textarea v-model="jsonInput" rows="10"
                    style="width: 100%; padding: 10px; margin-top: 5px; font-family: monospace; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required></textarea>
            </div>

            <button type="submit" :disabled="loading"
                style="width: 100%; padding: 12px; background-color: #008060; color: white; font-size: 16px; font-weight: bold; border: none; border-radius: 4px; cursor: pointer;">
                {{ loading ? 'Processing & Saving...' : 'Run Bulk Creation' }}
            </button>
        </form>

        <!-- v-if="results && results.length" নিরাপদ কন্ডিশন -->
        <div v-if="results && results.length" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <h3>Execution & Database Status:</h3>
            <ul style="list-style-type: none; padding: 0;">
                <li v-for="(item, index) in results" :key="index"
                    :style="{ padding: '10px', margin: '8px 0', borderRadius: '4px', backgroundColor: item.status === 'success' ? '#e6f4ea' : '#fce8e6' }">
                    <strong>{{ item.title }}</strong>: {{ item.status ? item.status.toUpperCase() : 'ERROR' }}
                    <span v-if="item.message"> - {{ item.message }}</span>
                </li>
            </ul>
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
                // ১. কাস্টম টেমপ্লেটসহ স্মার্ট কালেকশন (উদাহরণ: 'grid-layout')
                {
                    "type": "smart",
                    "title": "Smart Helmets 2026",
                    "handle": "smart-helmets-2026",
                    "description": "<h2>Best Safety Helmets</h2><p>Explore high quality protective gear.</p>",
                    "template_suffix": "grid-layout", // <-- কাস্টম টেমপ্লেট বসবে (collection.grid-layout.json)
                    "image_url": "https://cdn.shopify.com/s/files/1/0589/6968/6134/files/Sdad449d0179040a6a0e886e06795be260.webp?v=1788792579",
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "helmet"
                        }
                    ]
                },

                // ২. ডিফল্ট টেমপ্লেট (template_suffix ফাঁকা রাখলে অটোমেটিক Default Collection বসবে)
                {
                    "type": "smart",
                    "title": "Riding Jackets",
                    "handle": "riding-jackets",
                    "description": "<p>Durable and all-weather motorcycle riding jackets.</p>",
                    "template_suffix": "", // <-- ফাঁকা রাখায় 'Default collection' টেমপ্লেট সেট হবে
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "jacket"
                        }
                    ]
                },

                // ৩. কাস্টম টেমপ্লেটসহ স্মার্ট কালেকশন (উদাহরণ: 'sidebar-filter')
                {
                    "type": "smart",
                    "title": "Motorcycle Gloves",
                    "handle": "motorcycle-gloves",
                    "description": "<p>Premium leather and protective racing gloves.</p>",
                    "template_suffix": "sidebar-filter", // <-- নির্দিষ্ট কাস্টম টেমপ্লেট বসবে
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "gloves"
                        }
                    ]
                },

                // ৪. template_suffix না দিলে (অটোমেটিক Default বসবে)
                {
                    "type": "smart",
                    "title": "Riding Boots",
                    "handle": "riding-boots",
                    "description": "<p>Heavy duty boots for maximum safety.</p>",
                    // "template_suffix" ফিল্ডটি বাদ রাখলেও ডিফল্টভাবে 'Default collection' নিবে
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "boots"
                        }
                    ]
                },

                // ৫. কাস্টম টেমপ্লেট (উদাহরণ: 'full-width')
                {
                    "type": "smart",
                    "title": "Armor & Protectors",
                    "handle": "armor-protectors",
                    "description": "<p>Back, chest, and knee guards for bikers.</p>",
                    "template_suffix": "full-width", // <-- কাস্টম ফুল-উইডথ টেমপ্লেট
                    "rules": [
                        {
                            "column": "type",
                            "relation": "equals",
                            "condition": "Protection"
                        }
                    ]
                },

                // ৬. ডিফল্ট টেমপ্লেট
                {
                    "type": "smart",
                    "title": "Biker Accessories",
                    "handle": "biker-accessories",
                    "description": "<p>Keychains, tank pads, and intercom devices.</p>",
                    "template_suffix": "", // <-- 'Default collection' বসবে
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "accessory"
                        }
                    ]
                },

                // ৭. কাস্টম টেমপ্লেট (উদাহরণ: 'banner-header')
                {
                    "type": "smart",
                    "title": "New Arrivals 2026",
                    "handle": "new-arrivals-2026",
                    "description": "<p>Latest gear added recently to our inventory.</p>",
                    "template_suffix": "banner-header", // <-- কাস্টম ব্যানার টেমপ্লেট
                    "rules": [
                        {
                            "column": "variant_price",
                            "relation": "greater_than",
                            "condition": "0"
                        }
                    ]
                },

                // ৮. ডিফল্ট টেমপ্লেট
                {
                    "type": "smart",
                    "title": "Clearance Sale Items",
                    "handle": "clearance-sale-items",
                    "description": "<p>Discounted products with special pricing.</p>",
                    "template_suffix": "", // <-- 'Default collection' বসবে
                    "rules": [
                        {
                            "column": "tag",
                            "relation": "equals",
                            "condition": "sale"
                        }
                    ]
                },

                // ==========================================
                // CUSTOM / MANUAL COLLECTIONS (মোট ২টি)
                // ==========================================

                // ৯. কাস্টম টেমপ্লেটসহ ম্যানুয়াল কালেকশন (উদাহরণ: 'promotional-layout')
                {
                    "type": "custom",
                    "title": "Manual Sale Offer",
                    "handle": "manual-sale-offer",
                    "description": "<p>Hand-picked promotional products for special offers.</p>",
                    "template_suffix": "promotional-layout" // <-- কাস্টম প্রমোশনাল টেমপ্লেট
                },

                // ১০. ডিফল্ট টেমপ্লেটসহ ম্যানুয়াল কালেকশন
                {
                    "type": "custom",
                    "title": "Featured Best Sellers",
                    "handle": "featured-best-sellers",
                    "description": "<p>Manually curated top trending items.</p>",
                    "template_suffix": "" // <-- 'Default collection' বসবে
                }
            ], null, 2),
            loading: false,
            results: []
        }
    },
methods: {
  async submitCollections() {
    try {
      this.loading = true;
      this.results = [];
      const collectionsArray = JSON.parse(this.jsonInput);

      const response = await axios.post('/api/create-collections', {
        shop_domain: this.form.shop_domain,
        access_token: this.form.access_token,
        collections: collectionsArray
      });

      // Backend response safe fallback
      this.results = response.data.results || response.data.data || [];

    } catch (error) {
      const errorMsg = error.response?.data?.message || 'Error executing request.';
      alert(errorMsg);
      this.results = [{
        title: 'System Error',
        status: 'failed',
        message: errorMsg
      }];
    } finally {
      this.loading = false;
    }
  }
}
}
</script>
