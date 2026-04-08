<?php
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
function navLink(string $href, string $label, string $currentPath): string {
    $isActive = ($href === '/')
        ? ($currentPath === '/')
        : str_starts_with($currentPath, $href);
    if ($isActive) {
        return "<li class='nav-item'><a class='nav-link active' aria-current='page' href='{$href}'>{$label}</a></li>";
    }
    return "<li class='nav-item'><a class='nav-link' href='{$href}'>{$label}</a></li>";
}
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/"><i class="bi bi-music-note-beamed brand-icon"></i> BandBinder</a>
        <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0 me-2 me-lg-0 order-lg-last">
            <button id="themeToggleBtn" type="button" class="btn btn-outline-secondary btn-sm" title="Toggle dark mode" onclick="toggleTheme()">
                <i id="themeToggleIcon" class="bi bi-moon-fill"></i>
            </button>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php echo navLink('/', 'Home', $currentPath); ?>
                <?php
                if (in_array('charts.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/charts/mine/', 'My Charts', $currentPath);
                }
                if (in_array('charts.viewAll', $_SESSION['user']['permissions'])) {
                    echo navLink('/charts/all/', 'All Charts', $currentPath);
                }
                if (in_array('setlists.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/setlists/', 'Setlists', $currentPath);
                }
                if (in_array('artists.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/artists/', 'Artists', $currentPath);
                }
                if (in_array('arrangers.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/arrangers/', 'Arrangers', $currentPath);
                }
                if (in_array('instruments.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/instruments/', 'Instruments', $currentPath);
                }
                if (in_array('users.view', $_SESSION['user']['permissions'])) {
                    echo navLink('/users/', 'Users', $currentPath);
                }
                ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
(function(){
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var icon = document.getElementById('themeToggleIcon');
    if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
})();

function toggleTheme() {
    var html   = document.documentElement;
    var isDark = html.getAttribute('data-bs-theme') === 'dark';
    var next   = isDark ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    localStorage.setItem('bb-theme', next);
    document.getElementById('themeToggleIcon').className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
}
</script>