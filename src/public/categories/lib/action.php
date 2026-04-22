<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {
    case 'getCategories':
        if (!in_array('categories.view', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        try {
            $categories = Category::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'idCategory'      => $category->getIdCategory(),
                'categoryName'   => $category->getCategoryName(),
                'categoryColour' => $category->getCategoryColour(),
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    case 'createCategory':
        if (!in_array('categories.create', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $categoryName = trim($_POST['categoryName'] ?? '');
        if ($categoryName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Category name is required']);
            exit;
        }
        $categoryColour = trim($_POST['categoryColour'] ?? '') ?: null;
        try {
            $category = Category::CreateCategory($categoryName, $categoryColour);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['id' => $category->getIdCategory(), 'name' => $category->getCategoryName()]);
        break;

    case 'updateCategory':
        if (!in_array('categories.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idCategory   = trim($_POST['idCategory'] ?? '');
        $categoryName = trim($_POST['categoryName'] ?? '');
        if ($idCategory === '' || $categoryName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'ID and name are required']);
            exit;
        }
        $categoryColour = trim($_POST['categoryColour'] ?? '') ?: null;
        try {
            $category = new Category($idCategory);
            $category->UpdateCategory($categoryName, $categoryColour);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'deleteCategory':
        if (!in_array('categories.delete', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idCategory = trim($_POST['idCategory'] ?? '');
        if ($idCategory === '') {
            http_response_code(400);
            echo json_encode(['message' => 'ID is required']);
            exit;
        }
        try {
            $category = new Category($idCategory);
            $category->DeleteCategory();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}