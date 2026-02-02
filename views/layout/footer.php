<?php if (isset($currentUser) && $currentUser): ?>
    </main>
    <footer class="mt-auto border-t border-slate-200 bg-white py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-slate-500">
            &copy;
            <?php echo date('Y'); ?> TrainPro. Todos los derechos reservados.
        </div>
    </footer>
<?php endif; ?>
<script>
    lucide.createIcons();
</script>
</body>

</html>