<?php if (isset($_SESSION['success'])): ?>
    <div id="toast" class="fixed top-24 right-5 bg-green-500 text-white px-4 py-2 rounded shadow z-[9999]">
        <?= $_SESSION['success']; ?>
    </div>

    <script>
        setTimeout(() => {
            document.getElementById("toast").remove();
        }, 2000);
    </script>

    <?php unset($_SESSION['success']); ?>
<?php endif; ?>