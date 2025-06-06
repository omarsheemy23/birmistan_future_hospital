<?php include 'includes/header.php'; ?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');

    :root {
        --primary-color: #2a5298;
        --secondary-color: #1e3c72;
        --accent-color: #4facfe;
        --text-color: #333;
        --light-bg: #f8f9fa;
    }

    .hero-section {
        perspective: 100%;
        overflow: hidden;
        width: 100vw;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 600px;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        margin-top: 0;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        position: relative;
    }
    
    /* إضافة تأثير الخلفية المتحركة */
    .hero-section:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><circle cx="50" cy="50" r="1" fill="%23ffffff10"/></svg>');
        background-size: 50px 50px;
        opacity: 0.3;
        animation: backgroundMove 60s linear infinite;
    }
    
    @keyframes backgroundMove {
        0% { background-position: 0 0; }
        100% { background-position: 1000px 1000px; }
    }

    /* تخصيص العنوان الرئيسي */
    .title-container {
        background: transparent;
        margin-bottom: 0;
    }

    /* تأثيرات الأزرار */
    .pulse-btn {
        background: #ffffff;
        color: #2a5298;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
    }
    
    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
        }
    }
    
    .glow-btn {
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .glow-btn:after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        transform: rotate(30deg);
        transition: all 0.3s ease;
    }
    
    .glow-btn:hover:after {
        transform: rotate(30deg) translate(-10%, -10%);
    }
    
    .glow-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
    }

    /* تحسين تصميم البطاقات */
    .card {
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        background: linear-gradient(145deg, #ffffff, #f5f5f5);
        position: relative;
        z-index: 1;
    }
    
    .card:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        opacity: 0;
        z-index: -1;
        transition: opacity 0.5s ease;
    }

    .card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    
    .card:hover:before {
        opacity: 0.05;
    }

    .icon-circle {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        z-index: 1;
    }
    
    .icon-circle:after {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
        z-index: -1;
        opacity: 0;
        transition: all 0.5s ease;
    }

    .icon-circle i {
        color: white;
        transition: all 0.5s ease;
    }

    .card:hover .icon-circle {
        transform: rotate(360deg) scale(1.1);
    }
    
    .card:hover .icon-circle:after {
        opacity: 1;
        top: -10px;
        left: -10px;
        right: -10px;
        bottom: -10px;
        filter: blur(10px);
    }

    .services-section, .departments-section {
        padding: 80px 0;
    }

    .section-title {
        font-family: 'Almarai', sans-serif;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 50px;
        position: relative;
        padding-bottom: 15px;
        text-align: center;
        font-size: 2.5rem;
        letter-spacing: -1px;
    }

    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        border-radius: 2px;
    }

    .why-choose-us {
        background: linear-gradient(135deg, var(--light-bg) 0%, #e9ecef 100%);
        padding: 80px 0;
        border-radius: 30px;
        margin: 60px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    
    .why-choose-us:before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(79, 172, 254, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
        z-index: 0;
    }

    .cta-section {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 80px 0;
        margin-bottom: 0;
        position: relative;
        overflow: hidden;
    }
    
    .cta-section:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><polygon points="0,100 100,0 100,100" fill="%23ffffff05"/></svg>');
        background-size: 100px 100px;
        opacity: 0.3;
    }

    .btn-light {
        background: #ffffff;
        color: #2a5298;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .btn-light:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .btn-light:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 10px;
        height: 10px;
        background: rgba(255, 255, 255, 0.3);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
        z-index: -1;
    }
    
    .btn-light:hover:after {
        animation: ripple 1s ease-out;
    }
    
    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        100% {
            transform: scale(20, 20);
            opacity: 0;
        }
    }
    /* تأثير النيون للعنوان الرئيسي */
    .neon-text {
        color: #fff;
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.8),
                    0 0 20px rgba(255, 255, 255, 0.8),
                    0 0 30px rgba(255, 255, 255, 0.8),
                    0 0 40px #2a5298,
                    0 0 70px #2a5298,
                    0 0 80px #2a5298,
                    0 0 100px #2a5298,
                    0 0 150px #2a5298;
        animation: neon 1.5s ease-in-out infinite alternate;
    }
    
    @keyframes neon {
        from {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8),
                        0 0 20px rgba(255, 255, 255, 0.8),
                        0 0 30px rgba(255, 255, 255, 0.8),
                        0 0 40px #2a5298,
                        0 0 70px #2a5298,
                        0 0 80px #2a5298,
                        0 0 100px #2a5298;
        }
        to {
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.8),
                        0 0 10px rgba(255, 255, 255, 0.8),
                        0 0 15px rgba(255, 255, 255, 0.8),
                        0 0 20px #2a5298,
                        0 0 35px #2a5298,
                        0 0 40px #2a5298,
                        0 0 50px #2a5298;
        }
    }
</style>

<!-- Hero Section -->
<div class="hero-section text-white py-5">
    <div class="text-center w-100">
        <h1 class="display-4 fw-bold mb-4 neon-text">مستشفى بارمستان المستقبل</h1>
        <div class="d-flex align-items-center justify-content-center flex-column flex-md-row mt-4">
            <div>
                <p class="lead text-white mb-4">نقدم خدمات رعاية صحية عالمية المستوى مع تكنولوجيا متطورة ورعاية متميزة</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="appointments.php" class="btn btn-light btn-lg pulse-btn">حجز موعد</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg glow-btn">تسجيل حساب جديد</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<section class="services-section mb-5">
    <h2 class="text-center section-title">خدماتنا</h2>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-user-md fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">أطباء متخصصون</h3>
                        <p class="text-muted">فريق من الأطباء ذوي الخبرة يقدم رعاية متخصصة في مختلف المجالات الطبية</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-calendar-check fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">حجز المواعيد</h3>
                        <p class="text-muted">احجز موعدك بسهولة من خلال نظام الحجز الإلكتروني المتطور</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-video fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">استشارات عن بعد</h3>
                        <p class="text-muted">تواصل مع الأطباء عن بعد من خلال منصة الاستشارات المرئية الآمنة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Departments Section -->
<section class="departments-section mb-5">
    <h2 class="text-center section-title">أقسامنا الطبية</h2>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-heartbeat fa-3x text-primary"></i>
                        </div>
                        <h4>قسم القلب</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-brain fa-3x text-primary"></i>
                        </div>
                        <h4>قسم الأعصاب</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-baby fa-3x text-primary"></i>
                        </div>
                        <h4>قسم الأطفال</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle mb-4">
                            <i class="fas fa-tooth fa-3x text-primary"></i>
                        </div>
                        <h4>قسم الأسنان</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us bg-light py-5 mb-5">
    <h2 class="text-center section-title">لماذا تختارنا</h2>
    <div class="container">
        <div class="row mx-0">
            <div class="col-md-4 mb-4">
                <div class="d-flex align-items-center feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-clock fa-2x text-primary"></i>
                    </div>
                    <div class="feature-content">
                        <h4>خدمة على مدار الساعة</h4>
                        <p>مساعدة طبية ورعاية طوارئ على مدار الساعة طوال أيام الأسبوع.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="d-flex align-items-center feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-microscope fa-2x text-primary"></i>
                    </div>
                    <div class="feature-content">
                        <h4>معدات حديثة</h4>
                        <p>مرافق ومعدات طبية متطورة على أحدث مستوى تكنولوجي.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="d-flex align-items-center feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-user-md fa-2x text-primary"></i>
                    </div>
                    <div class="feature-content">
                        <h4>طاقم طبي متميز</h4>
                        <p>متخصصون طبيون مؤهلون وذوو خبرة عالية في مختلف المجالات.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section bg-primary text-white py-5">
    <div class="text-center">
        <h2 class="mb-3">هل أنت مستعد للبدء؟</h2>
        <p class="lead mb-4">احجز موعدك اليوم واستمتع برعاية صحية عالمية المستوى</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="appointments.php" class="btn btn-light btn-lg">احجز موعداً</a>
            <a href="register.php" class="btn btn-outline-light btn-lg">سجل حساباً جديداً</a>
        </div>
    </div>
</section>

<footer class="text-center text-lg-start text-white mt-5" style="background-color: #343a40; position: relative;">
    <div class="container p-4 pb-0">
        <div class="row">
            <div class="col-md-6 col-lg-4 col-xl-3 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold">مستشفى المستقبل</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto" style="width: 60px; background-color: #7c4dff; height: 2px"/>
                <p>نقدم رعاية صحية متكاملة بأحدث التقنيات وأفضل الكفاءات الطبية لضمان صحة أفضل لجميع أفراد المجتمع.</p>
            </div>
        </div>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>