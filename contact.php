<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 class="mb-4">Contact Us</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">معلومات الاتصال</h5>
                    <p><strong>العنوان:</strong> ١٢٣ شارع المستشفيات، الحي الطبي، القاهرة</p>
                    <p><strong>الهاتف:</strong> +20 2 2345 6789</p>
                    <p><strong>البريد الإلكتروني:</strong> info@birmistanhospital.com.eg</p>
                    <p><strong>الطوارئ:</strong> +20 2 2345 6700</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Send us a Message</h5>
                    <form action="process_contact.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Your Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>