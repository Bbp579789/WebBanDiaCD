<?php if (isset($_SESSION['success'])): ?>
    <div class="fixed top-5 right-5 bg-green-500 text-white px-4 py-2 rounded shadow z-50">
        <?= $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="fixed top-5 right-5 bg-red-500 text-white px-4 py-2 rounded shadow z-50">
        <?= $_SESSION['error']; ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<script>
    setTimeout(() => {
        document.querySelectorAll('.fixed').forEach(el => el.remove());
    }, 2000);
</script>