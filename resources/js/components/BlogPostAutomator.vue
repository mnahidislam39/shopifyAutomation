<template>
  <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">
      Shopify Bulk Blog Posts Automator
    </h2>

    <form @submit.prevent="submitBlogPosts">
      <!-- Store Domain, Access Token & Blog Name -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Store Domain:</label>
          <input
            type="text"
            v-model="form.shop_domain"
            placeholder="store.myshopify.com"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
            required
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Admin Access Token:</label>
          <input
            type="password"
            v-model="form.access_token"
            placeholder="shpat_xxxxxxxxxxxx"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
            required
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Shopify Blog Name:</label>
          <input
            type="text"
            v-model="form.blog_title"
            placeholder="News"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
            required
          />
        </div>
      </div>

      <!-- JSON Payload Input -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Blog Posts JSON Payload:</label>
        <textarea
          v-model="form.posts_json"
          rows="12"
          class="w-full px-3 py-2 font-mono text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50"
          required
        ></textarea>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-md transition duration-200 flex justify-center items-center"
      >
        <span v-if="loading">Processing Posts...</span>
        <span v-else>⚡ Auto-Create Blog & Push Posts</span>
      </button>
    </form>

    <!-- Execution Results Box -->
    <div class="mt-8">
      <h3 class="text-lg font-semibold text-gray-800 mb-2">Execution Results:</h3>
      <div
        class="bg-gray-900 text-white p-4 rounded-md font-mono text-sm max-h-80 overflow-y-auto space-y-2 border border-gray-700"
      >
        <div v-if="results.length === 0" class="text-gray-400 italic">
          No execution results yet. Submit the form to see output.
        </div>
        <div v-for="(res, index) in results" :key="index" class="leading-relaxed">
          <span v-if="res.success" class="text-green-400 font-bold">
            [SUCCESS] {{ res.title }}
          </span>
          <span v-else class="text-red-400 font-bold">
            [FAILED] {{ res.title }}
          </span>
          <p v-if="res.note" class="text-yellow-300 text-xs ml-4 mt-0.5">⚠️ Note: {{ res.note }}</p>
          <p v-if="res.message" class="text-red-300 text-xs ml-4 mt-0.5">{{ res.message }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BlogPostAutomator',
  data() {
    return {
      form: {
        shop_domain: '',
        access_token: '',
        blog_title: 'News',
        posts_json: JSON.stringify([
          {
            "title": "Top 5 Winter Care Tips for Newborn Babies",
            "handle": "top-5-winter-care-tips-for-newborns",
            "body_html": "<p>Keeping your baby warm and safe during freezing temperatures requires proper layering, indoor temperature control, and gentle skincare.</p>",
            "excerpt": "Essential winter care tips for your baby's delicate skin and warmth.",
            "author": "Nahid Admin",
            "tags": ["Winter", "Baby Care", "Newborn"],
            "is_published": true,
            "image_url": "https://cdn.pixabay.com/photo/2017/02/15/10/39/winter-2068272_1280.jpg",
            "seo_title": "Top 5 Winter Care Tips for Newborn Babies",
            "seo_description": "Learn essential winter care tips to keep your newborn baby warm and safe during cold weather."
          },
          {
            "title": "How to Choose the Right Winter Clothing for Infants",
            "handle": "choose-right-winter-clothing-infants",
            "body_html": "<p>Discover breathable cotton thermals, cozy fleece sleep sacks, and woolen caps designed to keep infants warm without overheating.</p>",
            "excerpt": "A complete guide to dressing your baby safely during cold weather.",
            "author": "Unknown Author Example",
            "tags": ["Clothing", "Winter", "Infants"],
            "is_published": true,
            "image_url": "https://cdn.pixabay.com/photo/2016/01/31/16/34/blogging-1171731_1280.jpg",
            "seo_title": "Infant Winter Clothing Guide",
            "seo_description": "A complete guide to dressing your baby safely and comfortably during cold winter months."
          }
        ], null, 2)
      },
      loading: false,
      results: []
    };
  },
  methods: {
    async submitBlogPosts() {
      this.loading = true;
      this.results = [];

      let parsedPosts;
      try {
        parsedPosts = JSON.parse(this.form.posts_json);
      } catch (e) {
        alert('Invalid JSON format in posts payload! Please fix the syntax.');
        this.loading = false;
        return;
      }

      try {
        const response = await axios.post('/api/shopify/bulk-blog-posts', {
          shop_domain: this.form.shop_domain,
          access_token: this.form.access_token,
          blog_title: this.form.blog_title,
          posts: parsedPosts
        });

        if (response.data.success) {
          this.results = response.data.results || [];
        } else {
          this.results = [{
            title: 'API Error',
            success: false,
            message: response.data.message || 'Unknown error occurred.'
          }];
        }
      } catch (error) {
        this.results = [{
          title: 'Request Exception',
          success: false,
          message: error.response?.data?.message || error.message
        }];
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
