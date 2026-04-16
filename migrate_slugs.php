<?php
require_once 'database_functions.php';

$pdo = getConnection();

// Add slug column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE brands ADD COLUMN IF NOT EXISTS slug VARCHAR(255) UNIQUE");
} catch (Exception $e) {
    // Column might already exist or other error
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_slugs'])) {
    $pdo->beginTransaction();
    try {
        foreach ($_POST['slugs'] as $id => $slug) {
            $stmt = $pdo->prepare("UPDATE brands SET slug = ? WHERE id = ?");
            $stmt->execute([trim($slug), $id]);
        }
        $pdo->commit();
        $message = "<div class='alert alert-success'>Successfully updated all slugs!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->query("SELECT id, name, slug FROM brands ORDER BY name ASC");
$brands = $stmt->fetchAll();

// generateSlug imported from database_functions.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brand Slug Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .brand-row { border-bottom: 1px solid #eee; padding: 10px 0; }
        .brand-name { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Brand Slug Migration</h2>
    <?php echo $message; ?>
    <form method="POST">
        <div class="row brand-row mb-2">
            <div class="col-md-4"><strong>Brand Name</strong></div>
            <div class="col-md-8"><strong>Slug</strong></div>
        </div>
        <?php foreach ($brands as $brand): 
            $suggested = $brand['slug'] ?: generateSlug($brand['name']);
        ?>
        <div class="row brand-row align-items-center">
            <div class="col-md-4 brand-name"><?php echo htmlspecialchars($brand['name']); ?></div>
            <div class="col-md-8">
                <input type="text" name="slugs[<?php echo $brand['id']; ?>]" value="<?php echo htmlspecialchars($suggested); ?>" class="form-control">
            </div>
        </div>
        <?php endforeach; ?>
        <div class="mt-4">
            <button type="submit" name="update_slugs" class="btn btn-primary btn-lg w-100">Update All Slugs</button>
        </div>
    </form>
</div>
</body>
</html>
