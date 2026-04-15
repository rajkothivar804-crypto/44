        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

    <script>
        // Set active menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuLinks = document.querySelectorAll('.sidebar-menu a');
            
            menuLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
                    link.classList.add('active');
                }
            });

            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (window.innerWidth <= 768) {
                sidebarToggle.style.display = 'block';
            }

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebarToggle.style.display = 'none';
                    sidebar.classList.remove('active');
                } else {
                    sidebarToggle.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>
