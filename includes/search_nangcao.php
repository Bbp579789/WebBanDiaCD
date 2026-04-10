<!-- includes/sidebar_filter.php -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky mt-5">
    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
        <span class="w-2 h-6 bg-green-600 rounded-full"></span>
        Bộ lọc tìm kiếm
    </h3>
    
    <form id="filterForm" action="timkiem.php" method="GET" class="space-y-5">
        <!-- Tìm tên -->
        <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 tracking-widest">Từ khóa</label>
            <input type="text" name="search" id="search_input" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                   placeholder="Tên đĩa, nghệ sĩ..." 
                   class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-green-500 outline-none text-sm">
        </div>

        <!-- Chọn thể loại -->
        <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 tracking-widest">Thể loại</label>
            <select name="category" id="category_select" class="w-full border-gray-200 border p-2.5 rounded-xl text-sm outline-none">
                <option value="0">Tất cả thể loại</option>
                <?php
                $res_cat = mysqli_query($conn, "SELECT * FROM the_loai WHERE trang_thai = 1");
                while($c = mysqli_fetch_assoc($res_cat)):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $c['id']) ? 'selected' : '';
                ?>
                <option value="<?= $c['id'] ?>" <?= $selected ?>>
                    <?= htmlspecialchars($c['ten_the_loai']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Khoảng giá -->
        <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 tracking-widest">Khoảng giá (VNĐ)</label>
            <div class="space-y-2">
                <input type="text" id="min_price_format" 
                       value="<?= isset($_GET['min_price']) && $_GET['min_price'] != '' ? number_format($_GET['min_price']) : '' ?>" 
                       placeholder="Từ" 
                       class="w-full border border-gray-200 p-2 rounded-xl text-sm price-format">
                <input type="hidden" name="min_price" id="min_price_raw" value="<?= $_GET['min_price'] ?? '' ?>">

                <input type="text" id="max_price_format" 
                       value="<?= isset($_GET['max_price']) && $_GET['max_price'] != '' ? number_format($_GET['max_price']) : '' ?>" 
                       placeholder="Đến" 
                       class="w-full border border-gray-200 p-2 rounded-xl text-sm price-format">
                <input type="hidden" name="max_price" id="max_price_raw" value="<?= $_GET['max_price'] ?? '' ?>">
            </div>
        </div>

        <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-100">
            ÁP DỤNG
        </button>
        
        <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="block text-center text-[10px] text-gray-400 uppercase font-bold hover:text-green-600">Làm mới</a>
    </form>
</div>

<script>
// 1. Xử lý định dạng tiền tệ khi nhập
document.querySelectorAll('.price-format').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, "");
        const rawInputId = this.id.replace('_format', '_raw');
        document.getElementById(rawInputId).value = value;

        if (value !== "") {
            this.value = new Intl.NumberFormat('en-US').format(value);
        } else {
            this.value = "";
        }
    });
});

// 2. Chặn Submit nếu không có dữ liệu nào được nhập/chọn
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const search = document.getElementById('search_input').value.trim();
    const category = document.getElementById('category_select').value;
    const minPrice = document.getElementById('min_price_raw').value;
    const maxPrice = document.getElementById('max_price_raw').value;

    // Kiểm tra xem tất cả các trường có trống hoặc ở giá trị mặc định không
    if (search === "" && category === "0" && minPrice === "" && maxPrice === "") {
        e.preventDefault(); // Dừng việc gửi form
        alert("Vui lòng nhập từ khóa hoặc chọn bộ lọc trước khi áp dụng!");
    }
});
</script>