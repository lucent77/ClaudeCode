        <?php if (isset($showNav) && $showNav): ?>
        </div>
    </div>
    <?php endif; ?>

    <script src="/assets/js/app.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>
