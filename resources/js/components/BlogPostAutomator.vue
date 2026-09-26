<template>
  <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-8 relative">
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

    <!-- Middle Overlay Popup Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-all">

        <!-- Loading / Processing State -->
        <div v-if="loading" class="text-center py-6">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-green-600 border-t-transparent mb-4"></div>
          <h3 class="text-lg font-bold text-gray-800">Publishing Blog Posts...</h3>
          <p class="text-sm text-gray-500 mt-1">Please wait while we push data to Shopify.</p>

          <div class="mt-6 bg-gray-100 rounded-lg p-3 flex justify-around text-sm font-medium text-gray-700">
            <div>⏱️ Time Taken: <span class="text-green-600 font-bold">{{ elapsedTime }}s</span></div>
            <div>📦 Total Posts: <span class="text-green-600 font-bold">{{ totalPostsCount }}</span></div>
          </div>
        </div>

        <!-- Completed State -->
        <div v-else class="py-2">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Batch Operation Completed</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
          </div>

          <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm flex justify-between">
            <span>Total Time: <b class="text-green-600">{{ elapsedTime }} seconds</b></span>
            <span>Processed: <b class="text-green-600">{{ results.length }} items</b></span>
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

          <button @click="showModal = false" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition shadow">
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
      results: [],
      showModal: false,
      elapsedTime: 0,
      totalPostsCount: 0,
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
    async submitBlogPosts() {
      let parsedPosts;

      // JSON ফরম্যাট ভুল থাকলে অ্যালার্ট না দেখিয়ে পপআপে ফেইল্ড দেখাবে
      try {
        parsedPosts = JSON.parse(this.form.posts_json);
      } catch (e) {
        this.results = [{
          title: 'Invalid JSON Payload',
          success: false,
          message: 'Please check your JSON syntax. ' + e.message
        }];
        this.totalPostsCount = 0;
        this.elapsedTime = 0;
        this.loading = false;
        this.showModal = true;
        return;
      }

      this.loading = true;
      this.results = [];
      this.showModal = true;
      this.totalPostsCount = parsedPosts.length;

      this.startTimer();

      try {
        const response = await axios.post('/api/shopify/bulk-blog-posts', {
          shop_domain: this.form.shop_domain,
          access_token: this.form.access_token,
          blog_title: this.form.blog_title,
          posts: parsedPosts
        });

        this.stopTimer();
        this.loading = false;

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
