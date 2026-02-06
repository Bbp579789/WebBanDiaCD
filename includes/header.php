<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="text-black pt-3 pb-3 rounded-md">

<div class="flex items-center justify-between mb-3 px-4">

    <!-- LOGO -->
    <a href="index.php" class="flex items-center">
        <img src="uploads/image/others/music.jpg"
             alt="Logo"
             class="h-12 rounded-md">
    </a>

    <!-- SEARCH -->
    <div class="flex-1 flex justify-center">
        <input type="search"
               placeholder="Tìm kiếm sản phẩm..."
               class="border-2 border-green-900 p-2 rounded w-1/2">
    </div>

    <!-- RIGHT ACTION -->
    <div class="flex gap-3 relative">

        <!-- GIỎ HÀNG -->
        <?php if (isset($_SESSION["user_id"])): ?>
          <a href="giohang.php"
             class="border border-green-600 px-4 py-2 rounded-md
              hover:bg-green-600 hover:text-white transition">
              🛒 Giỏ hàng
           </a>
        <?php else: ?>
           <button onclick="requireLogin('giohang.php')"
            class="border border-green-600 px-4 py-2 rounded-md
                   hover:bg-green-600 hover:text-white transition">
           🛒 Giỏ hàng
           </button>
        <?php endif; ?>


        <!-- LOGIN / USER -->
        <?php if (isset($_SESSION["user_id"])): ?>

            <button id="userBtn"
                    class="border border-green-600 px-4 py-2 rounded-md
                           hover:bg-green-600 hover:text-white transition">
                👤 <?= htmlspecialchars($_SESSION["name"]) ?>
            </button>

            <div id="userMenu"
                 class="hidden absolute right-0 top-12 bg-white
                        border rounded-md shadow-md w-40 z-50">
                <a href="hoso.php"
                   class="block px-4 py-2 hover:bg-gray-100">
                    Hồ sơ
                </a>
                <a href="includes/logout.php"
                   class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                    Đăng xuất
                </a>
            </div>

        <?php else: ?>

            <button onclick="openLogin()"
                    class="border border-green-600 px-4 py-2 rounded-md
                           hover:bg-green-600 hover:text-white transition">
              👤 Đăng nhập
            </button>

        <?php endif; ?>

    </div>
</div>

<!-- NAV -->
<?php
require_once "config/database.php";
$result = mysqli_query($conn, "SELECT * FROM the_loai");
?>
<nav class="bg-black h-20 rounded-md flex items-center justify-center gap-6 text-white font-medium">

    <a href="danhsachsanpham.php"
       class="px-4 py-2 rounded-md transition hover:bg-green-600">
        Tất cả sản phẩm
    </a>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <a href="theloai.php?id=<?= $row["id"] ?>"
           class="px-4 py-2 rounded-md transition hover:bg-green-600">
            <?= htmlspecialchars($row["ten_the_loai"]) ?>
        </a>
    <?php endwhile; ?>

</nav>

<?php if (!empty($_SESSION["register_success"])): ?>
<div id="registerToast"
     class="fixed top-5 right-5 bg-green-600 text-white px-4 py-3 rounded shadow-lg z-50">
    <?= $_SESSION["register_success"] ?>
</div>

<script>
setTimeout(() => {
    const toast = document.getElementById("registerToast");
    if (toast) toast.remove();
}, 3000);
</script>

<?php unset($_SESSION["register_success"]); ?>
<?php endif; ?>

</header>

<!-- LOGIN MODAL -->
<div id="loginModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden
            flex items-center justify-center z-50">

<div class="bg-green-100 p-8 rounded-xl shadow-lg w-96 relative">

    <button onclick="closeLogin()"
            class="absolute top-3 right-3 text-gray-600 hover:text-black">
      ✕
    </button>

    <h2 class="text-2xl font-semibold mb-6 text-center">Đăng nhập</h2>

    <form id="loginForm" class="space-y-4">

        <div>
            <label class="block text-gray-700">Tên đăng nhập</label>
            <input id="login_username"
                   class="w-full border p-2 rounded mt-1 focus:outline-none">
        </div>

        <div>
            <label class="block text-gray-700">Mật khẩu</label>
            <input type="password" id="login_password"
                   class="w-full border p-2 rounded mt-1 focus:outline-none">
        </div>

        <p id="loginError"
           class="text-red-600 text-sm hidden"></p>

        <a href="quenmatkhau.php"
         class="text-green-600 hover:underline flex justify-end text-sm">
        Quên mật khẩu
      </a>

        <button type="submit"
                class="w-full bg-green-600 text-white py-2 rounded
                       hover:bg-green-700 transition">
            Đăng nhập
        </button>
    </form>

    <div class="text-sm text-gray-500 mt-4 text-center">
        Bạn chưa có tài khoản?
        <a href="dangky.php"
           class="text-green-600 font-medium hover:underline">
            Đăng ký ngay
        </a>
    </div>
</div>
</div>

<!-- DROPDOWN -->
<script>
document.getElementById("userBtn")?.addEventListener("click", e => {
    e.stopPropagation();
    document.getElementById("userMenu").classList.toggle("hidden");
});
document.addEventListener("click", () => {
    document.getElementById("userMenu")?.classList.add("hidden");
});
</script>

<!-- LOGIN MODAL -->
<script>
function openLogin() {
    document.getElementById("loginModal").classList.remove("hidden");
}
function closeLogin() {
    document.getElementById("loginModal").classList.add("hidden");
}
document.getElementById("loginModal")?.addEventListener("click", e => {
    if (e.target.id === "loginModal") closeLogin();
});
</script>

<!-- REDIRECT -->
<script>
let redirectAfterLogin = null;

function requireLogin(url) {
    redirectAfterLogin = url;
    openLogin();
}
</script>


<!-- AJAX LOGIN -->
<script>
document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const username = login_username.value.trim();
    const password = login_password.value.trim();
    const errorBox = loginError;

    errorBox.classList.add("hidden");
    errorBox.innerText = "";

    fetch("includes/ajax_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ username, password })
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch {
            throw new Error("JSON parse error");
        }
    })
    .then(data => {
        if (data.status === "success") {
            if (typeof redirectAfterLogin !== "undefined" && redirectAfterLogin) {
                window.location.href = redirectAfterLogin;
            } else {
                location.reload();
            }
        } else {
            errorBox.classList.remove("hidden");
            errorBox.innerText = getLoginError(data.type);
        }
    })
    .catch(() => {
        errorBox.classList.remove("hidden");
        errorBox.innerText = "Lỗi hệ thống (AJAX)";
    });
});

function getLoginError(type) {
    switch (type) {
        case "empty":
            return "Vui lòng nhập đầy đủ thông tin";
        case "username":
            return "Tên đăng nhập không tồn tại";
        case "password":
            return "Mật khẩu không chính xác";
        case "blocked":
            return "Tài khoản đã bị khóa";
        default:
            return "Đăng nhập thất bại";
    }
}
</script>


