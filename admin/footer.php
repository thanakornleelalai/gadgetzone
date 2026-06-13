<?php
/**
 * admin/footer.php
 */
$__jsVer = @filemtime(__DIR__ . '/admin.js') ?: time();
?>
        </div><!-- /.admin-body -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-shell -->

<div class="toast" id="toast"></div>

<!-- Modal container -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal" id="modalBox"></div>
</div>

<script src="<?= url('admin/admin.js') ?>?v=<?= $__jsVer ?>"></script>
</body>
</html>
