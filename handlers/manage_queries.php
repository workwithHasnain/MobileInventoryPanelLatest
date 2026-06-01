<?php

/**
 * Queries Management Handler - AJAX endpoint
 * Handles listing, viewing, updating status, and deleting contact/advertising queries
 */

header('Content-Type: application/json');
require_once 'auth.php';
require_once 'database_functions.php';

requireLogin();

$response = [
    'success' => false,
    'message' => ''
];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getConnection();

    // Ensure status column exists (add if missing)
    try {
        $pdo->exec("ALTER TABLE queries ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending'");
    } catch (Exception $e) {
        // Column likely already exists, ignore
    }

    if ($action === 'list') {
        $type = $_POST['type'] ?? 'all'; // 'contact', 'ad', or 'all'

        $sql = "SELECT id, name, email, query, query_type, status, created_at FROM queries";
        $params = [];

        if ($type === 'contact') {
            $sql .= " WHERE query_type = ?";
            $params[] = 'contact';
        } elseif ($type === 'ad') {
            $sql .= " WHERE query_type = ?";
            $params[] = 'ad';
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['queries'] = $queries;
        $response['count'] = count($queries);

    } elseif ($action === 'view') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            $response['message'] = 'Invalid query ID.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, query, query_type, status, created_at FROM queries WHERE id = ?");
            $stmt->execute([$id]);
            $query = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($query) {
                $response['success'] = true;
                $response['query'] = $query;
            } else {
                $response['message'] = 'Query not found.';
            }
        }

    } elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $allowedStatuses = ['pending', 'read', 'replied', 'closed'];

        if ($id <= 0) {
            $response['message'] = 'Invalid query ID.';
        } elseif (!in_array($status, $allowedStatuses)) {
            $response['message'] = 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses);
        } else {
            $stmt = $pdo->prepare("UPDATE queries SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $response['success'] = true;
            $response['message'] = 'Status updated to "' . $status . '" successfully!';
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            $response['message'] = 'Invalid query ID.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM queries WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = 'Query deleted successfully!';
        }

    } else {
        $response['message'] = 'Invalid action.';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
