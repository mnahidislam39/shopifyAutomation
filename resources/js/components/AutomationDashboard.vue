<template>
  <div class="max-w-[950px] mx-auto my-[50px] font-sans">

    <!-- Top Navigation Tabs -->
    <div class="flex gap-3 mb-[25px] border-b-2 border-[#e1e3e5] pb-3">
      <button
        @click="activeTab = 'collections'"
        :class="[
          'px-5 py-2.5 text-[15px] font-bold cursor-pointer rounded-md border-none transition-all duration-200',
          activeTab === 'collections' ? 'bg-[#008060] text-white shadow-sm' : 'bg-[#f1f2f3] text-[#333333] hover:bg-[#e4e5e7]'
        ]"
      >
        📂 Bulk Collections
      </button>

      <button
        @click="activeTab = 'menus'"
        :class="[
          'px-5 py-2.5 text-[15px] font-bold cursor-pointer rounded-md border-none transition-all duration-200',
          activeTab === 'menus' ? 'bg-[#008060] text-white shadow-sm' : 'bg-[#f1f2f3] text-[#333333] hover:bg-[#e4e5e7]'
        ]"
      >
        🔗 Bulk Navigation Menus
      </button>
    </div>

    <!-- Active Tab Components -->
    <div v-if="activeTab === 'collections'">
      <CollectionApp />
    </div>

    <div v-if="activeTab === 'menus'">
      <MenuAutomator />
    </div>
<!-- Toast Notification Component এখানে কল করুন -->
    <Toast />
  </div>

</template>

<script>
import CollectionApp from './CollectionApp.vue';
import MenuAutomator from './MenuAutomator.vue';

export default {
  components: {
    CollectionApp,
    MenuAutomator
  },
  data() {
    return {
      activeTab: 'collections'
    };
  }
};
</script>
