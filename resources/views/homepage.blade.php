<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BFAR Regional Fisheries Laboratory XII Laboratory Information Management System">
    <title>BFAR RFL XII | Laboratory Information Management System</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/bfarlogo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ocean: #0065a8;
            --deep: #003b67;
            --aqua: #00a7b5;
            --gold: #f5c343;
            --ink: #17324a;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            color: var(--ink);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .topbar {
            background: var(--deep);
            color: #d9ecfa;
            font-size: .82rem;
        }

        .navbar {
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 2px 18px rgba(8, 62, 99, .1);
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .brand-name {
            color: var(--deep);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .03em;
            line-height: 1.25;
        }

        .brand-name small {
            color: #5d7182;
            font-size: .65rem;
            font-weight: 600;
        }

        .nav-link {
            color: var(--ink) !important;
            font-weight: 600;
            font-size: .9rem;
        }

        .nav-link:hover {
            color: var(--ocean) !important;
        }

        .hero {
            min-height: 630px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(90deg, rgba(0, 42, 72, .93) 0%, rgba(0, 76, 115, .82) 48%, rgba(0, 101, 168, .26) 100%), url('{{ asset('assets/images/Banner-1_V2.jpg') }}') center/cover;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -120px;
            bottom: -210px;
            width: 580px;
            height: 580px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .22);
            box-shadow: 0 0 0 55px rgba(255, 255, 255, .05), 0 0 0 110px rgba(255, 255, 255, .04);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            color: #fff;
        }

        .eyebrow {
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            padding: .48rem .85rem;
            border-radius: 30px;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .26);
            letter-spacing: .08em;
            font-weight: 700;
            font-size: .73rem;
        }

        .hero h1 {
            max-width: 720px;
            font-size: clamp(2.4rem, 5vw, 4.7rem);
            font-weight: 800;
            line-height: 1.06;
            letter-spacing: -.045em;
        }

        .hero h1 span {
            color: #6ce0e5;
        }

        .hero p {
            max-width: 610px;
            color: #e0eff7;
            font-size: 1.08rem;
            line-height: 1.75;
        }

        .btn-primary-custom {
            background: var(--gold);
            border-color: var(--gold);
            color: #17324a;
            font-weight: 800;
            padding: .85rem 1.3rem;
        }

        .btn-primary-custom:hover {
            background: #ffd663;
            border-color: #ffd663;
            color: #17324a;
        }

        .btn-outline-light {
            font-weight: 700;
            padding: .85rem 1.3rem;
        }

        .hero-card {
            max-width: 320px;
            margin-left: auto;
            padding: 1.4rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, .94);
            color: var(--ink);
            box-shadow: 0 16px 44px rgba(0, 31, 54, .25);
        }

        .hero-card .icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            background: var(--ocean);
            font-size: 1.3rem;
        }

        .service-strip {
            position: relative;
            margin-top: -44px;
            z-index: 3;
        }

        .service-strip .strip {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(9, 53, 81, .12);
        }

        .strip-item {
            padding: 1.2rem 1.5rem;
            border-right: 1px solid #e6edf1;
        }

        .strip-item:last-child {
            border: 0;
        }

        .strip-icon {
            color: var(--aqua);
            font-size: 1.5rem;
        }

        .section-label {
            color: var(--ocean);
            letter-spacing: .12em;
            font-size: .76rem;
            font-weight: 800;
        }

        .section-title {
            color: var(--deep);
            font-weight: 800;
            letter-spacing: -.025em;
        }

        .service-card {
            height: 100%;
            padding: 2rem;
            border: 1px solid #e2ebef;
            border-radius: 16px;
            transition: .2s ease;
            background: #fff;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(7, 72, 109, .12);
            border-color: #c6e5e7;
        }

        .service-card .card-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin-bottom: 1.25rem;
            border-radius: 14px;
            background: #e6f6f7;
            color: var(--ocean);
            font-size: 1.5rem;
        }

        .service-card h3 {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--deep);
        }

        .about {
            background: #eff8fa;
        }

        .about-image {
            min-height: 380px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(0, 59, 103, .35), rgba(0, 167, 181, .18)), url('{{ asset('assets/images/bg.jpg') }}') center/cover;
        }

        .quick-link {
            text-decoration: none;
            color: var(--ink);
            display: flex;
            gap: 1rem;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #d6e5e9;
            font-weight: 700;
        }

        .quick-link:last-child {
            border-bottom: 0;
        }

        .quick-link i {
            color: var(--ocean);
            font-size: 1.25rem;
        }

        .cta {
            background: linear-gradient(115deg, var(--deep), var(--ocean));
            color: #fff;
            border-radius: 20px;
        }

        footer {
            background: #052d4b;
            color: #c9dce8;
        }

        footer a {
            color: #c9dce8;
            text-decoration: none;
        }

        footer a:hover {
            color: #fff;
        }

        @media (max-width: 991px) {
            .hero {
                min-height: 590px;
            }

            .hero-card {
                margin: 2rem 0 0;
            }

            .strip-item {
                border-right: 0;
                border-bottom: 1px solid #e6edf1;
            }

            .strip-item:last-child {
                border-bottom: 0;
            }
        }

        @media (max-width: 575px) {
            .hero {
                min-height: 650px;
            }

            .topbar .contact-info {
                display: none;
            }

            .brand-name {
                font-size: .67rem;
            }
        }
    </style>
</head>

<body>
    <div class="topbar py-2">
        <div class="container d-flex justify-content-between align-items-center"><span><i
                    class="bi bi-building me-1"></i> Department of Agriculture — Republic of the Philippines</span><span
                class="contact-info"><i class="bi bi-geo-alt me-1"></i> J. Catolico St., Lagao, General Santos
                City</span></div>
    </div>
    <nav class="navbar navbar-expand-lg sticky-top py-2">
        <div class="container"><a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}"><img
                    class="brand-logo" src="{{ asset('assets/images/bfarlogo.png') }}" alt="BFAR Region XII seal"><span
                    class="brand-name">BUREAU OF FISHERIES<br>AND AQUATIC RESOURCES<br><small>REGIONAL FISHERIES
                        LABORATORY XII</small></span></a><button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Open navigation"><i
                    class="bi bi-list fs-3"></i></button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-primary px-3 rounded-pill fw-semibold"
                            href="{{ route('login') }}"><i class="bi bi-person-lock me-1"></i> Staff Portal</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
        <section class="hero" id="home">
            <div class="container hero-content">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="eyebrow mb-4"><i class="bi bi-patch-check-fill"></i> REGIONAL FISHERIES LABORATORY
                            XII</div>
                        <h1>Science that protects our <span>fisheries</span> and communities.</h1>
                        <p class="mt-4 mb-4">Welcome to the BFAR Laboratory Information Management System. Access
                            reliable laboratory services and support the safety, quality, and sustainability of aquatic
                            resources.</p>
                        <div class="d-flex flex-wrap gap-3"><a href="{{ route('kiosk') }}"
                                class="btn btn-primary-custom rounded-pill"><i
                                    class="bi bi-grid-1x2-fill me-2"></i>Access Client Kiosk</a><a href="#services"
                                class="btn btn-outline-light rounded-pill">Explore Services <i
                                    class="bi bi-arrow-right ms-1"></i></a></div>
                    </div>
                    <div class="col-lg-4">
                        <div class="hero-card">
                            <div class="icon mb-3"><i class="bi bi-clipboard2-pulse"></i></div>
                            <h2 class="h5 fw-bold">Laboratory Services</h2>
                            <p class="small text-secondary mb-3">Start your visit, share feedback, or access the system
                                through our public service channels.</p><a class="fw-bold text-decoration-none"
                                style="color:var(--ocean)" href="{{ route('kiosk') }}">Open the kiosk <i
                                    class="bi bi-arrow-up-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="service-strip">
            <div class="container">
                <div class="strip row g-0 text-center text-md-start">
                    <div class="col-md-4 strip-item d-flex align-items-center gap-3"><i
                            class="bi bi-shield-check strip-icon"></i>
                        <div><strong>Quality-focused</strong><br><small class="text-secondary">Reliable laboratory
                                processes</small></div>
                    </div>
                    <div class="col-md-4 strip-item d-flex align-items-center gap-3"><i
                            class="bi bi-droplet-half strip-icon"></i>
                        <div><strong>Fisheries protection</strong><br><small class="text-secondary">Supporting safe
                                aquatic products</small></div>
                    </div>
                    <div class="col-md-4 strip-item d-flex align-items-center gap-3"><i
                            class="bi bi-people strip-icon"></i>
                        <div><strong>Public service</strong><br><small class="text-secondary">Accessible client
                                support</small></div>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-5" id="services">
            <div class="container py-lg-4">
                <div class="text-center mx-auto mb-5" style="max-width:670px">
                    <div class="section-label mb-2">OUR SERVICES</div>
                    <h2 class="section-title">Supporting safe and sustainable fisheries</h2>
                    <p class="text-secondary mb-0">Our laboratory services help clients, communities, and industry make
                        informed decisions about aquatic resources and products.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="service-card">
                            <div class="card-icon"><i class="bi bi-droplet"></i></div>
                            <h3>Water Quality Testing</h3>
                            <p class="small text-secondary mb-0">Assessment services that support safe water and healthy
                                aquatic environments.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="service-card">
                            <div class="card-icon"><i class="bi bi-virus"></i></div>
                            <h3>Microbiological Analysis</h3>
                            <p class="small text-secondary mb-0">Laboratory analysis for water, fish, and fishery
                                product safety.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="service-card">
                            <div class="card-icon"><i class="bi bi-flask"></i></div>
                            <h3>Chemical Analysis</h3>
                            <p class="small text-secondary mb-0">Testing support for product quality and regulatory
                                requirements.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="service-card">
                            <div class="card-icon"><i class="bi bi-search-heart"></i></div>
                            <h3>Fish Health Services</h3>
                            <p class="small text-secondary mb-0">Diagnostic support for fish health and aquatic animal
                                management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="about py-5" id="about">
            <div class="container py-lg-4">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="about-image"></div>
                    </div>
                    <div class="col-lg-6">
                        <div class="section-label mb-2">ABOUT BFAR RFL XII</div>
                        <h2 class="section-title mb-3">Committed to better fisheries through laboratory excellence.
                        </h2>
                        <p class="text-secondary">The Regional Fisheries Laboratory XII serves clients in the region
                            through laboratory testing, documentation, and quality-focused public service.</p>
                        <div class="mt-4"><a class="quick-link" href="{{ route('kiosk') }}"><i
                                    class="bi bi-display"></i><span>Visit the self-service laboratory kiosk</span><i
                                    class="bi bi-arrow-right ms-auto"></i></a><a class="quick-link"
                                href="{{ route('arta.create') }}"><i class="bi bi-chat-square-heart"></i><span>Share
                                    your Citizen's Charter feedback</span><i class="bi bi-arrow-right ms-auto"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-5">
            <div class="container">
                <div class="cta p-4 p-md-5 d-md-flex align-items-center justify-content-between gap-4">
                    <div>
                        <div class="section-label text-info mb-2">CLIENT ACCESS</div>
                        <h2 class="h2 fw-bold mb-2">Ready to transact with the laboratory?</h2>
                        <p class="mb-0 text-white-50">Use the client kiosk to begin your visit or provide service
                            feedback.</p>
                    </div><a href="{{ route('kiosk') }}" class="btn btn-primary-custom rounded-pill flex-shrink-0"><i
                            class="bi bi-arrow-right-circle me-2"></i>Go to Client Kiosk</a>
                </div>
            </div>
        </section>
    </main>
    <footer class="pt-5 pb-3">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-3 mb-3"><img class="brand-logo"
                            src="{{ asset('assets/images/bfarlogo.png') }}" alt="BFAR Region XII seal"><strong
                            class="text-white">BFAR REGIONAL FISHERIES<br>LABORATORY XII</strong></div>
                    <p class="small mb-0">Bureau of Fisheries and Aquatic Resources<br>J. Catolico St., Lagao, General
                        Santos City</p>
                </div>
                <div class="col-6 col-lg-3">
                    <h3 class="h6 text-white mb-3">Quick Links</h3>
                    <ul class="list-unstyled small lh-lg">
                        <li><a href="#services">Laboratory Services</a></li>
                        <li><a href="{{ route('kiosk') }}">Client Kiosk</a></li>
                        <li><a href="{{ route('arta.create') }}">Client Feedback</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h3 class="h6 text-white mb-3">System</h3>
                    <ul class="list-unstyled small lh-lg">
                        <li><a href="{{ route('login') }}">Staff Portal</a></li>
                        <li><a href="#home">Back to top</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary mt-4">
            <div class="d-flex flex-wrap justify-content-between small"><span>© {{ date('Y') }} BFAR Regional
                    Fisheries Laboratory XII.</span><span>Laboratory Information Management System</span></div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
