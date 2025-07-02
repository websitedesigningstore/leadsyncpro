                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($GLOBALS['page_scripts'])): ?>
        <?php foreach ($GLOBALS['page_scripts'] as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($GLOBALS['inline_js'])): ?>
        <script>
            <?php echo $GLOBALS['inline_js']; ?>
        </script>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo getSetting('company_name', 'LeadSync Pro'); ?>. 
                        All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">
                        Version <?php echo APP_VERSION; ?> | 
                        <a href="<?php echo APP_URL; ?>/docs/" class="text-decoration-none">Documentation</a> |
                        <a href="<?php echo APP_URL; ?>/support/" class="text-decoration-none">Support</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>