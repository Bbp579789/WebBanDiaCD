<!-- Banner -->
<div class="relative max-w-4xl mx-auto overflow-hidden mt-3 rounded-xl">

    <!-- Slides -->
    <div id="slider" class="flex transition-transform duration-700">

        <!-- Banner 1 -->
        <a href="chitietsanpham.php?id=1" class="w-full flex-shrink-0">
            <img src="uploads/image/banner/mck.jpg" class="w-full h-auto cursor-pointer">
        </a>

        <!-- Banner 2 -->
        <a href="chitietsanpham.php?id=2" class="w-full flex-shrink-0">
            <img src="uploads/image/banner/wxrdie99.jpg" class="w-full h-auto cursor-pointer">
        </a>

        <!-- Banner 3 -->
        <a href="chitietsanpham.php?id=3" class="w-full flex-shrink-0">
            <img src="uploads/image/banner/tlinh99.jpg" class="w-full h-auto cursor-pointer">
        </a>

        <!-- Banner 4 -->
        <a href="chitietsanpham.php?id=4" class="w-full flex-shrink-0">
            <img src="uploads/image/banner/lowg99.jpg" class="w-full h-auto cursor-pointer">
        </a>

    </div>

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-30 pointer-events-none"></div>

    <!-- Nút trái -->
    <button onclick="prevSlide()"
        class="absolute left-3 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white px-3 py-2 rounded-full hover:bg-opacity-70">
        ❮
    </button>

    <!-- Nút phải -->
    <button onclick="nextSlide()"
        class="absolute right-3 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white px-3 py-2 rounded-full hover:bg-opacity-70">
        ❯
    </button>
</div>

<!-- tự động chuyển ảnh banner -->
<script>
    let index = 0;
    const slider = document.getElementById('slider');
    const total = slider.children.length;

    function showSlide(i) {
        index = (i + total) % total;
        slider.style.transform = `translateX(-${index * 100}%)`;
    }

    function nextSlide() {
        showSlide(index + 1);
    }

    function prevSlide() {
        showSlide(index - 1);
    }

    // Tự động chạy mỗi 3 giây
    setInterval(nextSlide, 3000);
</script>