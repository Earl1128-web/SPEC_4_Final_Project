<?php
/**
 * includes/footer.php
 * Closes the app layout opened by header.php and renders the footer.
 */
?>
<?php if (isLoggedIn()): ?>
        </main>
        <footer class="app-footer site-footer">
            <p>&copy; <?= date('Y') ?> Barangay Pahina Central &ndash; Online Reservation System. All rights reserved.</p>
        </footer>
    </div><!-- /.app-main-viewport -->
</div><!-- /.app-layout -->
<?php else: ?>
    </main>
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Barangay Pahina Central &ndash; Online Reservation System. All rights reserved.</p>
    </footer>
<?php endif; ?>

<script src="<?= h(appUrl('/js/script.js?v=' . filemtime(__DIR__ . '/../js/script.js'))) ?>"></script>
</body>
</html>
