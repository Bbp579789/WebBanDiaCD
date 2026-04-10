 document.addEventListener('DOMContentLoaded', function () {
      const userInfo = document.getElementById('userInfo');
      const dropdown = document.querySelector('.option-account-admin');

      // Toggle dropdown khi click vào user-info
      userInfo.addEventListener('click', function (e) {
        e.stopPropagation();
        userInfo.classList.toggle('active');
      });

      // Đóng dropdown khi click bất kỳ đâu trên document
      document.addEventListener('click', function (e) {
        if (userInfo.classList.contains('active') && !userInfo.contains(e.target)) {
          userInfo.classList.remove('active');
        }
      });
      
      // Ngăn chặn đóng dropdown khi click vào chính dropdown
      dropdown.addEventListener('click', function (e) {
        e.stopPropagation();
      });
    });