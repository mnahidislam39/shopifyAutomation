<template>
  <div style="max-width: 950px; margin: 30px auto; font-family: system-ui, sans-serif; padding: 0 15px;">

    <!-- Top Navigation Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #e1e3e5; padding-bottom: 10px;">
      <button
        @click="activeTab = 'collections'"
        :style="tabButtonStyle(activeTab === 'collections')"
      >
        📂 Bulk Collections
      </button>

      <button
        @click="activeTab = 'menus'"
        :style="tabButtonStyle(activeTab === 'menus')"
      >
        🔗 Header & Navigation Menus
      </button>
    </div>

    <!-- Active Tab Components -->
    <div v-if="activeTab === 'collections'">
      <CollectionApp />
    </div>

    <div v-if="activeTab === 'menus'">
      <MenuAutomator />
    </div>

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
  },
  methods: {
    tabButtonStyle(isActive) {
      return {
        padding: '10px 20px',
        fontSize: '15px',
        fontWeight: 'bold',
        cursor: 'pointer',
        borderRadius: '6px',
        border: 'none',
        backgroundColor: isActive ? '#008060' : '#f1f2f3',
        color: isActive ? '#ffffff' : '#333333',
        transition: 'all 0.2s ease'
      };
    }
  }
};
</script>
