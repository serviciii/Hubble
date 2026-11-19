<?php
$isGuest      = $isGuest ?? false;
$extraScripts = $extraScripts ?? [];
?>
<?php if (!$isGuest): ?>
    </main>
  </div>
<?php endif; ?>

  <script src="<?= base_url('assets/js/script.js') ?>"></script>
  <?php foreach ($extraScripts as $scriptPath): ?>
    <script src="<?= base_url($scriptPath) ?>"></script>
  <?php endforeach; ?>
</body>

</html>
