<?php
/**
 * Clients Management Component
 * Included in admin_dashboard_hub.php
 */

// Ensure connection exists
if (!isset($basi)) {
    $basi = connectMaBasi();
}

// Initialize variables
$edit_client = null;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete client
    if (isset($_POST['delete_client'])) {
        $client_id = intval($_POST['client_id']);
        $query = "DELETE FROM users WHERE ID = $client_id";
        mysqli_query($basi, $query);
        header("Location: admin_dashboard_hub.php?tab=clients&action=deleted");
        exit();
    }

    // Edit client
    if (isset($_POST['edit_client'])) {
        $client_id = intval($_POST['client_id']);
        $query = "SELECT * FROM users WHERE ID = $client_id";
        $result = mysqli_query($basi, $query);
        $edit_client = mysqli_fetch_assoc($result);
    }

    // Update client
    if (isset($_POST['update_client'])) {
        $client_id = intval($_POST['client_id']);
        $name = mysqli_real_escape_string($basi, $_POST['name']);
        $prenom = mysqli_real_escape_string($basi, $_POST['prenom']);
        $email = mysqli_real_escape_string($basi, $_POST['email']);

        $query = "UPDATE users SET NOM = '$name', PRENOM = '$prenom', EMAIL = '$email' WHERE ID = $client_id";
        mysqli_query($basi, $query);
        header("Location: admin_dashboard_hub.php?tab=clients&action=updated");
        exit();
    }
}

// Get all clients (non-admin users)
$query_clients = "SELECT ID, NOM, PRENOM, EMAIL FROM users WHERE role != 'admin'";
$result_clients = mysqli_query($basi, $query_clients);
?>

<!-- Success Messages -->
<?php if (isset($_GET['action'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php 
        if ($_GET['action'] === 'updated') echo 'Client updated successfully!';
        elseif ($_GET['action'] === 'deleted') echo 'Client deleted successfully!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Edit Client Form -->
<?php if ($edit_client): ?>
    <div class="card mb-4" style="border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="card-header" style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Client</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="client_id" value="<?php echo $edit_client['ID']; ?>">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_client['NOM']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="prenom" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_client['PRENOM']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_client['EMAIL']); ?>" required>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="update_client" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Client
                    </button>
                    <a href="admin_dashboard_hub.php?tab=clients" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Clients Table -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_count = 0; while ($client = mysqli_fetch_assoc($result_clients)): $row_count++; ?>
                <tr>
                    <td><small>#<?php echo $client['ID']; ?></small></td>
                    <td><strong><?php echo htmlspecialchars($client['NOM']); ?></strong></td>
                    <td><?php echo htmlspecialchars($client['PRENOM']); ?></td>
                    <td><?php echo htmlspecialchars($client['EMAIL']); ?></td>
                    <td>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="client_id" value="<?php echo $client['ID']; ?>">
                            <button type="submit" name="edit_client" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="submit" name="delete_client" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete this client? This action cannot be undone.')" title="Delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php if ($row_count === 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No clients found in the system.
        </div>
    <?php endif; ?>
</div>
