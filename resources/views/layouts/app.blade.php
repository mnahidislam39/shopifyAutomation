<!-- Main Content-এর পরে কিন্তু </body> ট্যাগের আগে -->

    <!-- Toast HTML UI -->
    <div id="customToast" class="fixed top-5 right-5 z-50 transform translate-x-full transition-all duration-300 ease-in-out flex items-center gap-3 bg-white border border-gray-100 shadow-xl rounded-xl px-5 py-4 max-w-sm">
        <div id="toastIcon" class="text-2xl">✨</div>
        <div>
            <h4 id="toastTitle" class="font-semibold text-gray-800 text-sm"></h4>
            <p id="toastMessage" class="text-gray-500 text-xs mt-0.5"></p>
        </div>
    </div>

    <!-- Centralized JS Function (Global) -->
    <script>
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('customToast');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');

            toastTitle.innerText = title;
            toastMessage.innerText = message;

            if (type === 'success') {
                toastIcon.innerText = '🎉';
                toast.classList.remove('border-red-500', 'border-gray-100');
                toast.classList.add('border-emerald-500');
            } else {
                toastIcon.innerText = '❌';
                toast.classList.remove('border-emerald-500', 'border-gray-100');
                toast.classList.add('border-red-500');
            }

            // Show Toast
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');

            // Hide after 3.5 seconds
            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
            }, 3500);
        }
    </script>

    @stack('scripts') <!-- পেজ অনুযায়ী আলাদা স্ক্রিপ্ট লোড করার জন্য -->
</body>
