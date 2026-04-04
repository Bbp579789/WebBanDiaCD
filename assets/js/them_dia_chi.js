function themDiaChi(){
    let dc = document.getElementById("dc").value;

    if(dc.trim() === ""){
        alert("Nhập địa chỉ!");
        return;
    }

    fetch("./them_dia_chi.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "dia_chi=" + encodeURIComponent(dc)
    })
    .then(res => {
        console.log("STATUS:", res.status);
        return res.text();
    })
    .then(data => {
        console.log("DATA:", data);

        if(data.trim() === "success"){
            alert("Thêm thành công!");
            location.reload();
        } else {
            alert("Lỗi: " + data);
        }
    })
    .catch(err => {
        console.log("ERROR:", err);
    });
}