<meta charset="utf-8">
<script>
(function(){
    var t = localStorage.getItem('bb-theme');
    if (t === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    /* ── Global polish ───────────────────────────────────── */
    body { font-size: 0.9rem; }

    /* Navbar */
    .navbar { box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .navbar-brand { font-weight: 700; letter-spacing: -0.5px; font-size: 1.25rem; }
    .navbar-brand .brand-icon { color: #0d6efd; }

    /* Cards */
    .card { box-shadow: 0 1px 4px rgba(0,0,0,.06); border-radius: 10px; }
    .card-header { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: .04em; }

    /* Tables — allow horizontal scroll on small screens */
    .table th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; }
    .table-responsive { -webkit-overflow-scrolling: touch; }

    /* Stat tiles */
    .stat-tile { border-radius: 12px; padding: 1.25rem 1.5rem; }
    .stat-tile .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .stat-tile .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .06em; opacity: .7; margin-top: 4px; }
    .stat-tile .stat-icon  { font-size: 2.2rem; opacity: .18; }

    /* Headings */
    h1 { font-weight: 700; }
    h5.section-heading { font-weight: 600; font-size: 0.95rem; text-transform: uppercase;
        letter-spacing: .05em; border-bottom: 2px solid var(--bs-primary); padding-bottom: 6px;
        margin-bottom: 1rem; display: inline-block; }

    /* ── Mobile ──────────────────────────────────────────── */

    /* Tighten page padding on phones */
    @media (max-width: 575.98px) {
        .container-fluid { padding-left: .75rem; padding-right: .75rem; }

        /* Smaller stat tiles on phones */
        .stat-tile { padding: 1rem 1.1rem; }
        .stat-tile .stat-value { font-size: 1.5rem; }
        .stat-tile .stat-icon  { font-size: 1.6rem; }

        /* Shrink h1 on phones */
        h1 { font-size: 1.4rem; }

        /* DataTables: hide less-important columns via DT responsive */
        /* Action buttons: keep them compact */
        .btn-sm { padding: .2rem .45rem; font-size: .78rem; }

        /* Wrap button groups in modals */
        .modal-footer { flex-wrap: wrap; gap: .5rem; }

        /* Full-width modals on phones */
        .modal-dialog { margin: .5rem; }
    }

    /* Wrap long DataTable toolbars */
    div.dt-container { overflow-x: auto; }
    div.dt-layout-row { flex-wrap: wrap; gap: .25rem; }
</style>