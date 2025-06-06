<!-- تمت إزالة تأثير العنوان المتوهج -->
    
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>مستشفى بارمستان المستقبل</h5>
                <p>نقدم خدمات رعاية صحية عالية الجودة لمجتمعنا.</p>
            </div>
            <div class="col-md-4">
                <h5>روابط سريعة</h5>
                <ul class="list-unstyled">
                    <li><a href="/birmistan_future_hospital/about.php" class="text-white">من نحن</a></li>
                    <li><a href="/birmistan_future_hospital/services.php" class="text-white">خدماتنا</a></li>
                    <li><a href="/birmistan_future_hospital/contact.php" class="text-white">اتصل بنا</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>معلومات الاتصال</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-phone"></i> +1234567890</li>
                    <li><i class="fas fa-envelope"></i> info@hospital.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> شارع المستشفى 123، المدينة</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; <?php echo date('Y'); ?> مستشفى بارمستان المستقبل. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- تمت إزالة تأثير النص المتوهج -->
<!-- Custom JavaScript -->
<script>
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
</body>
</html>