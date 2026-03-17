// Thông báo khi thêm giỏ hàng thành công
    function themGio(id) {

        fetch("includes/themgiohang.php?id=" + id)
            .then(res => res.text())
            .then(data => {

                if (data == "success") {
                    showToast("✔ Đã thêm vào giỏ hàng");
                }

            });

    }

    function showToast(text) {

        let toast = document.getElementById("toast");

        toast.innerText = text;
        toast.classList.remove("hidden");

        setTimeout(() => {
            toast.classList.add("hidden");
        }, 2000);

    }
