function themDiaChi(){
    const el = document.getElementById("dc");
    if(!el){
        alert("Không tìm thấy input địa chỉ (id='dc').");
        return;
    }

    const dc = el.value.trim();
    if(dc === ""){
        alert("Nhập địa chỉ!");
        el.focus();
        return;
    }

    const body = new URLSearchParams();
    body.append('dia_chi', dc);

    fetch("includes/them_dia_chi.php", {
        method: "POST",
        credentials: "same-origin", // gửi cookie/session
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "Accept": "text/plain, application/json"
        },
        body: body.toString()
    })
    .then(async res => {
        const text = await res.text();
        if(!res.ok){
            throw new Error(`HTTP ${res.status}: ${text}`);
        }
        return text;
    })
    .then(data => {
        const trimmed = data.trim();
        // hỗ trợ cả text "success" và JSON {"status":"success"}
        if(trimmed === "success"){
            alert("Thêm thành công!");
            location.reload();
            return;
        }
        try {
            const json = JSON.parse(trimmed);
            if(json.status && json.status === "success"){
                alert("Thêm thành công!");
                location.reload();
                return;
            }
            if(json.error) throw new Error(json.error);
        } catch(e){
            // không phải JSON hoặc JSON lỗi -> tiếp tục
        }
        alert("Lỗi: " + trimmed);
    })
    .catch(err => {
        console.error("themDiaChi error:", err);
        alert("Lỗi kết nối: " + err.message);
    });
}