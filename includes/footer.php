</main>
<footer>
    <p>&copy; <?php echo date('Y'); ?> Artisan Pastry Shop. Crafted with love and butter.</p>
    <?php if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])): ?>
        <p style="margin-top: 0.5rem; font-size: 0.85rem;">
            <a href="admin_login.php" class="admin-link">Admin Login</a>
        </p>
    <?php endif; ?>
</footer>

<script src="js/main.js"></script>
</body>

</html>