<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../lib/html_header/all.php'; ?>
    </head>
    <body>
        <?php require_once __DIR__ . '/../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">

        <pre>
        <?php print_r($_SESSION); ?>
        </pre>


        </div>
        <?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>
    </body>
</html>