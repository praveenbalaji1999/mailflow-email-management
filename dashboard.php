<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$navTitle = 'Dashboard';
$includeChartJs = true;

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();

$totalRecipients = (int) $pdo->query('SELECT COUNT(*) FROM recipients')->fetchColumn();
$emailsSent = (int) $pdo->query("SELECT COUNT(*) FROM email_history WHERE status = 'sent'")->fetchColumn();
$pending = (int) $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status IN ('pending','processing')")->fetchColumn();
$failed = (int) $pdo->query("SELECT COUNT(*) FROM email_history WHERE status = 'failed'")->fetchColumn();

// Activity last 7 days
$activityLabels = [];
$activityData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $activityLabels[] = date('D', strtotime($day));
    $st = $pdo->prepare("SELECT COUNT(*) FROM email_history WHERE status = 'sent' AND DATE(sent_at) = ?");
    $st->execute([$day]);
    $activityData[] = (int) $st->fetchColumn();
}

$statusCounts = [
    'sent' => $emailsSent,
    'pending' => $pending,
    'failed' => $failed,
];
$statusTotal = array_sum($statusCounts);

$recent = $pdo->query(
    'SELECT id, campaign_name, status, total_recipients, total_sent, total_failed, created_at
     FROM campaigns ORDER BY created_at DESC LIMIT 5'
)->fetchAll();
?>

        <div class="page-header">
          <div>
            <h2><?= e(greeting()) ?>, <?= e(explode(' ', $admin['name'])[0] ?: 'Admin') ?> 👋</h2>
            <p>Manage your email campaigns and recipients from one place.</p>
          </div>
          <div class="page-actions">
            <a href="compose.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
          </div>
        </div>

        <div class="row g-3 gap-section">
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-card-top">
                <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
              </div>
              <div class="stat-value"><?= number_format($totalRecipients) ?></div>
              <div class="stat-label">Total Recipients</div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-card-top">
                <div class="stat-icon success"><i class="bi bi-send-fill"></i></div>
              </div>
              <div class="stat-value"><?= number_format($emailsSent) ?></div>
              <div class="stat-label">Emails Sent</div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-card-top">
                <div class="stat-icon warning"><i class="bi bi-clock-fill"></i></div>
              </div>
              <div class="stat-value"><?= number_format($pending) ?></div>
              <div class="stat-label">Pending</div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-card-top">
                <div class="stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
              </div>
              <div class="stat-value"><?= number_format($failed) ?></div>
              <div class="stat-label">Failed</div>
            </div>
          </div>
        </div>

        <div class="row g-3 gap-section">
          <div class="col-lg-8">
            <div class="card-surface h-100">
              <div class="card-header-row">
                <h3>Email Activity</h3>
                <div class="chart-filters">
                  <button type="button" class="active">7 Days</button>
                </div>
              </div>
              <div class="chart-container">
                <canvas id="emailActivityChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card-surface h-100">
              <div class="card-header-row"><h3>Email Status</h3></div>
              <div class="chart-container doughnut position-relative">
                <canvas id="emailStatusChart"></canvas>
                <div class="doughnut-center">
                  <div class="total"><?= number_format($statusTotal) ?></div>
                  <div class="label">Total</div>
                </div>
              </div>
              <div class="chart-legend">
                <div class="legend-item"><span class="legend-dot" style="background:#16A34A"></span> Sent</div>
                <div class="legend-item"><span class="legend-dot" style="background:#F59E0B"></span> Pending</div>
                <div class="legend-item"><span class="legend-dot" style="background:#DC2626"></span> Failed</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-surface">
          <div class="card-header-row">
            <h3>Recent Campaigns</h3>
            <a href="campaigns.php" class="link-muted">View All Campaigns →</a>
          </div>
          <?php if (! $recent) { ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-envelope"></i></div>
            <h3>No campaigns yet</h3>
            <p>Create your first email campaign to start communicating with your recipients.</p>
            <a href="compose.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
          </div>
          <?php } else { ?>
          <div class="table-wrap">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Campaign</th>
                  <th>Recipients</th>
                  <th>Sent</th>
                  <th>Failed</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $c) { ?>
                <tr>
                  <td><strong><?= e($c['campaign_name']) ?></strong></td>
                  <td><?= (int) $c['total_recipients'] ?></td>
                  <td><?= (int) $c['total_sent'] ?></td>
                  <td><?= (int) $c['total_failed'] ?></td>
                  <td><?= status_badge($c['status']) ?></td>
                  <td><?= e(format_date($c['created_at'])) ?></td>
                  <td><a href="campaign-details.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <div class="card-footer-link">
            <a href="campaigns.php">View All Campaigns <i class="bi bi-arrow-right"></i></a>
          </div>
          <?php } ?>
        </div>

<?php
$pageScript = 'document.addEventListener("DOMContentLoaded",function(){
  if(typeof Chart==="undefined")return;
  const labels='.json_encode($activityLabels).';
  const data='.json_encode($activityData).';
  const ctx=document.getElementById("emailActivityChart");
  if(ctx){
    const g=ctx.getContext("2d").createLinearGradient(0,0,0,260);
    g.addColorStop(0,"rgba(37,99,235,0.22)");
    g.addColorStop(1,"rgba(37,99,235,0.01)");
    new Chart(ctx,{type:"line",data:{labels,datasets:[{label:"Emails Sent",data,borderColor:"#2563EB",backgroundColor:g,borderWidth:2.5,fill:true,tension:0.4,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{grid:{color:"#F1F5F9"},beginAtZero:true}}}});
  }
  const s=document.getElementById("emailStatusChart");
  if(s){
    new Chart(s,{type:"doughnut",data:{labels:["Sent","Pending","Failed"],datasets:[{data:['.(int) $statusCounts['sent'].','.(int) $statusCounts['pending'].','.(int) $statusCounts['failed'].'],backgroundColor:["#16A34A","#F59E0B","#DC2626"],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:"72%",plugins:{legend:{display:false}}}});
  }
});';
require_once __DIR__.'/includes/footer.php';
