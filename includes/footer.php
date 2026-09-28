<?php
declare(strict_types=1);
?>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php if (! empty($includeChartJs)) { ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php } ?>
  <script src="assets/js/app.js"></script>
  <?php if (! empty($pageScript)) { ?>
  <script><?= $pageScript ?></script>
  <?php } ?>
</body>
</html>
