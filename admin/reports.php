<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

try {
	$pdo = get_pdo();

	$from = $_GET['from'] ?? date('Y-m-01');
	$to = $_GET['to'] ?? date('Y-m-d');

	// Revenue by day for chart
	$stmt = $pdo->prepare("
		SELECT DATE(created_at) as d, COALESCE(SUM(total),0) as revenue
		FROM orders
		WHERE DATE(created_at) BETWEEN ? AND ?
		GROUP BY DATE(created_at)
		ORDER BY d ASC
	");
	$stmt->execute([$from, $to]);
	$rev = $stmt->fetchAll();
	$labels = array_map(fn($r) => $r['d'], $rev);
	$values = array_map(fn($r) => (float)$r['revenue'], $rev);

	// Feedback list and average rating
	$fb = $pdo->prepare("SELECT f.id, f.rating, f.comment, f.created_at, u.name as user_name
		FROM feedback f JOIN users u ON u.id = f.user_id
		ORDER BY f.created_at DESC");
	$fb->execute();
	$feedback = $fb->fetchAll();
	$avgRating = null;
	if ($feedback) {
		$avgRating = array_sum(array_map(fn($f) => (int)$f['rating'], $feedback)) / count($feedback);
	}
} catch (PDOException $e) {
	$labels = [];
	$values = [];
	$feedback = [];
	$avgRating = null;
	$from = date('Y-m-01');
	$to = date('Y-m-d');
	$error_message = "Unable to load reports data.";
}

include __DIR__ . '/../inc/header.php';
?>
<h2 class="mb-3">Reports</h2>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php endif; ?>
<form method="get" class="row g-2 mb-3">
	<div class="col-md-3">
		<label class="form-label">From</label>
		<input type="date" class="form-control" name="from" value="<?php echo e($from); ?>">
	</div>
	<div class="col-md-3">
		<label class="form-label">To</label>
		<input type="date" class="form-control" name="to" value="<?php echo e($to); ?>">
	</div>
	<div class="col-md-3 d-flex align-items-end">
		<button class="btn btn-primary">Filter</button>
	</div>
</form>

<div class="card mb-4 shadow-sm">
	<div class="card-body">
		<h5 class="card-title">Revenue by Day</h5>
		<canvas id="revenueChart" height="120"></canvas>
	</div>
</div>

<div class="card shadow-sm">
	<div class="card-body">
		<div class="d-flex justify-content-between align-items-center">
			<h5 class="card-title mb-0">Feedback</h5>
			<?php if ($avgRating !== null): ?>
				<div class="small text-muted">Average rating: <?php echo number_format($avgRating, 2); ?>/5</div>
			<?php endif; ?>
		</div>
		<div class="table-responsive mt-3">
			<table class="table">
				<thead>
					<tr>
						<th>ID</th>
						<th>User</th>
						<th>Rating</th>
						<th>Comment</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($feedback as $f): ?>
						<tr>
							<td><?php echo e((int)$f['id']); ?></td>
							<td><?php echo e($f['user_name']); ?></td>
							<td><?php echo e((int)$f['rating']); ?></td>
							<td><?php echo e($f['comment']); ?></td>
							<td><?php echo e($f['created_at']); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
const labels = <?php echo json_encode($labels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
const values = <?php echo json_encode($values, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
document.addEventListener('DOMContentLoaded', () => {
	const ctx = document.getElementById('revenueChart');
	new Chart(ctx, {
		type: 'bar',
		data: {
			labels,
			datasets: [{
				label: 'Revenue ($)',
				data: values,
				backgroundColor: 'rgba(13,110,253,0.5)',
				borderColor: 'rgba(13,110,253,1)',
				borderWidth: 1
			}]
		},
		options: {
			scales: {
				y: { beginAtZero: true }
			}
		}
	});
});
</script>
<?php include __DIR__ . '/../inc/footer.php'; ?>

