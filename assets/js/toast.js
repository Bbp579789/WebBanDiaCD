function themGio(id) {
    fetch("includes/them_gio_hang.php?id=" + id)
        .then(res => res.text())
        .then(data => {
            console.log(data);

            if (data.trim() === "success") {
                showToast("✔ Đã thêm vào giỏ hàng");
            } else {
                showToast("❌ Thêm thất bại");
            }
        });
}

function muaNgay(id) {
    fetch("includes/them_gio_hang.php?id=" + id)
        .then(res => res.text())
        .then(data => {
            if (data.trim() === "success") {
                window.location.href = "giohang.php"; // 👉 chuyển trang
            } else {
                showToast("❌ Có lỗi xảy ra");
            }
        })
        .catch(() => {
            showToast("❌ Lỗi kết nối");
        });
}

function showToast(text) {
    let toast = document.getElementById("toast");

    toast.innerText = text;
    toast.style.right = "20px";

    setTimeout(() => {
        toast.style.right = "-300px";
    }, 2000);
}