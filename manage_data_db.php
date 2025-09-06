<?php
session_start();
require_once 'auth.php';
require_once 'includes/database_functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_brand':
            $name = trim($_POST['brand_name'] ?? '');
            $description = trim($_POST['brand_description'] ?? '');
            
            if (empty($name)) {
                $error = 'Brand name is required.';
            } else {
                $result = addBrandDB($name, $description);
                if ($result) {
                    $message = 'Brand added successfully!';
                } else {
                    $error = 'Brand already exists or failed to add.';
                }
            }
            break;
            
        case 'edit_brand':
            $id = $_POST['brand_id'] ?? '';
            $name = trim($_POST['brand_name'] ?? '');
            $description = trim($_POST['brand_description'] ?? '');
            
            if (empty($name)) {
                $error = 'Brand name is required.';
            } else {
                $result = updateBrandDB($id, $name, $description);
                if ($result) {
                    $message = 'Brand updated successfully!';
                } else {
                    $error = 'Failed to update brand or brand with same name already exists.';
                }
            }
            break;
            
        case 'delete_brand':
            $id = $_POST['brand_id'] ?? '';
            $result = deleteBrandDB($id);
            if ($result) {
                $message = 'Brand deleted successfully!';
            } else {
                $error = 'Failed to delete brand.';
            }
            break;
            
        case 'add_chipset':
            $name = trim($_POST['chipset_name'] ?? '');
            $description = trim($_POST['chipset_description'] ?? '');
            
            if (empty($name)) {
                $error = 'Chipset name is required.';
            } else {
                $result = addChipsetDB($name, $description);
                if ($result) {
                    $message = 'Chipset added successfully!';
                } else {
                    $error = 'Chipset already exists or failed to add.';
                }
            }
            break;
            
        case 'edit_chipset':
            $id = $_POST['chipset_id'] ?? '';
            $name = trim($_POST['chipset_name'] ?? '');
            $description = trim($_POST['chipset_description'] ?? '');
            
            if (empty($name)) {
                $error = 'Chipset name is required.';
            } else {
                $result = updateChipsetDB($id, $name, $description);
                if ($result) {
                    $message = 'Chipset updated successfully!';
                } else {
                    $error = 'Failed to update chipset or chipset with same name already exists.';
                }
            }
            break;
            
        case 'delete_chipset':
            $id = $_POST['chipset_id'] ?? '';
            $result = deleteChipsetDB($id);
            if ($result) {
                $message = 'Chipset deleted successfully!';
            } else {
                $error = 'Failed to delete chipset.';
            }
            break;
    }
}

// Get all brands and chipsets
$brands = getAllBrandsDB();
$chipsets = getAllChipsetsDB();

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-database"></i> Data Management (PostgreSQL)</h2>
            <p class="text-muted">Manage brands and chipsets using PostgreSQL database</p>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="dataManagementTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands" type="button" role="tab">
                <i class="fas fa-tags"></i> Brands Management
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="chipsets-tab" data-bs-toggle="tab" data-bs-target="#chipsets" type="button" role="tab">
                <i class="fas fa-microchip"></i> Chipsets Management
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="dataManagementTabContent">
        <!-- Brands Tab -->
        <div class="tab-pane fade show active" id="brands" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tags"></i> Brands Management
                        <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                            <i class="fas fa-plus"></i> Add Brand
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands as $brand): ?>
                                <tr>
                                    <td><?php echo $brand['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($brand['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($brand['description'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($brand['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning me-1" onclick="editBrand(<?php echo $brand['id']; ?>, '<?php echo addslashes($brand['name']); ?>', '<?php echo addslashes($brand['description'] ?? ''); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteBrand(<?php echo $brand['id']; ?>, '<?php echo addslashes($brand['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chipsets Tab -->
        <div class="tab-pane fade" id="chipsets" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-microchip"></i> Chipsets Management
                        <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addChipsetModal">
                            <i class="fas fa-plus"></i> Add Chipset
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chipsets as $chipset): ?>
                                <tr>
                                    <td><?php echo $chipset['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($chipset['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($chipset['description'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($chipset['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning me-1" onclick="editChipset(<?php echo $chipset['id']; ?>, '<?php echo addslashes($chipset['name']); ?>', '<?php echo addslashes($chipset['description'] ?? ''); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteChipset(<?php echo $chipset['id']; ?>, '<?php echo addslashes($chipset['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_brand">
                    <div class="mb-3">
                        <label for="brand_name" class="form-label">Brand Name *</label>
                        <input type="text" class="form-control" id="brand_name" name="brand_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="brand_description" class="form-label">Description</label>
                        <textarea class="form-control" id="brand_description" name="brand_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBrandForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_brand">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <div class="mb-3">
                        <label for="edit_brand_name" class="form-label">Brand Name *</label>
                        <input type="text" class="form-control" id="edit_brand_name" name="brand_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_brand_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_brand_description" name="brand_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Chipset Modal -->
<div class="modal fade" id="addChipsetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Chipset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_chipset">
                    <div class="mb-3">
                        <label for="chipset_name" class="form-label">Chipset Name *</label>
                        <input type="text" class="form-control" id="chipset_name" name="chipset_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="chipset_description" class="form-label">Description</label>
                        <textarea class="form-control" id="chipset_description" name="chipset_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Chipset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Chipset Modal -->
<div class="modal fade" id="editChipsetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Chipset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editChipsetForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_chipset">
                    <input type="hidden" name="chipset_id" id="edit_chipset_id">
                    <div class="mb-3">
                        <label for="edit_chipset_name" class="form-label">Chipset Name *</label>
                        <input type="text" class="form-control" id="edit_chipset_name" name="chipset_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_chipset_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_chipset_description" name="chipset_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Chipset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBrand(id, name, description) {
    document.getElementById('edit_brand_id').value = id;
    document.getElementById('edit_brand_name').value = name;
    document.getElementById('edit_brand_description').value = description;
    new bootstrap.Modal(document.getElementById('editBrandModal')).show();
}

function deleteBrand(id, name) {
    if (confirm('Are you sure you want to delete the brand "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_brand"><input type="hidden" name="brand_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function editChipset(id, name, description) {
    document.getElementById('edit_chipset_id').value = id;
    document.getElementById('edit_chipset_name').value = name;
    document.getElementById('edit_chipset_description').value = description;
    new bootstrap.Modal(document.getElementById('editChipsetModal')).show();
}

function deleteChipset(id, name) {
    if (confirm('Are you sure you want to delete the chipset "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_chipset"><input type="hidden" name="chipset_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php require_once 'includes/footer.php'; ?>