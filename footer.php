<!-- footer.php -->
<footer class="footer mt-auto py-3 text-center" style="background-color: #0d125a; color: white; width: 100%;">
    <div class="container py-2">
        <p class="mb-1 small fw-bold">&copy; 2026 Davao Medical School Foundation, Inc. Admission System. All rights reserved.</p>
        <p class="mb-0 x-small opacity-75" style="font-size: 0.75rem;">Developed for Academic Quality Assurance</p>
    </div>
</footer>

<style>
    /* Ensure the footer stays at the bottom if content is short */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .main-content {
        flex: 1 0 auto;
        padding-bottom: 2rem; /* Add space for footer */
    }
    .footer {
        flex-shrink: 0;
        margin-top: auto;
    }
</style>
