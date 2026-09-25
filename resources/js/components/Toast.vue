<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs transition-all">
    <div :class="[
      'px-6 py-5 rounded-2xl shadow-2xl border text-white transform scale-100 transition-all duration-300 flex items-center gap-4 max-w-md w-full mx-4',
      type === 'success' ? 'bg-emerald-600 border-emerald-500' : 'bg-rose-600 border-rose-500'
    ]">
      <div class="text-3xl">
        {{ type === 'success' ? '🎉' : '❌' }}
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-base">{{ title }}</h4>
        <p class="text-xs mt-1 opacity-90 leading-relaxed">{{ message }}</p>
      </div>
      <button @click="visible = false" class="text-xl font-bold opacity-70 hover:opacity-100 cursor-pointer p-1">
        &times;
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      visible: false,
      title: '',
      message: '',
      type: 'success',
      timer: null
    };
  },
  mounted() {
    window.showToast = (title, message, type = 'success') => {
      this.title = title;
      this.message = message;
      this.type = type;
      this.visible = true;

      if (this.timer) clearTimeout(this.timer);
      this.timer = setTimeout(() => {
        this.visible = false;
      }, 8000);
    };
  }
};
</script>
