function addToCart(id) {
    fetch("includes/them_gio_hang.php?id=" + id)
        .then(res => res.text())
        .then(data => {
            if (data === "success") {
                alert("Đã thêm vào giỏ hàng!");
                window.location.href = "giohang.php";
            }
        });
}



let deleteId = null;

// Tăng giảm số lượng
function updateCart(id, action) {
    fetch("includes/update_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id + "&action=" + action
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // ✅ nếu bị xóa luôn (quantity = 0)
            if (data.deleted) {
                document.getElementById("row-" + id).remove();

                document.getElementById("total").innerText = "Tổng: " + data.total + "đ";

                // 🔥 kiểm tra nếu hết sản phẩm
                if (document.querySelectorAll("tbody tr").length === 0) {
                    document.getElementById("cart-container").innerHTML = `
            <p class="text-center text-gray-500 text-lg mt-10">
                🛒 Không có sản phẩm trong giỏ hàng
            </p>
        `;
                }

                return;
            }

            // ✅ update bình thường
            document.getElementById("qty-" + id).innerText = data.quantity;
            document.getElementById("money-" + id).innerText = data.money + "đ";
            document.getElementById("total").innerText = "Tổng: " + data.total + "đ";
        });
}


function confirmDelete(id) {
    deleteId = id;
    document.getElementById("popupDelete").classList.remove("hidden");
}

function closePopup() {
    document.getElementById("popupDelete").classList.add("hidden");
}

function deleteItem() {
    fetch("includes/delete_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + deleteId
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("row-" + deleteId).remove();
                document.getElementById("total").innerText = "Tổng: " + data.total + "đ";

                closePopup();

                // 🔥 kiểm tra nếu hết sản phẩm
                if (document.querySelectorAll("tbody tr").length === 0) {
                    document.getElementById("cart-container").innerHTML = `
            <p class="text-center text-gray-500 text-lg mt-10">
                🛒 Không có sản phẩm trong giỏ hàng
            </p>
        `;
                }
            }
        });
}