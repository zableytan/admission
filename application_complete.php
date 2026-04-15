<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --success-color: #198754;
            --primary-color: #dc3545;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .success-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            padding: 60px 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 30px;
            display: inline-block;
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes bounceIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            color: #1a1a1a;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .lead-text {
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 40px;
            text-align: left;
            border-left: 5px solid var(--success-color);
        }

        .info-box h5 {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 10px;
            color: #2d3436;
        }

        .info-box p {
            margin-bottom: 0;
            font-size: 0.95rem;
            color: #636e72;
        }

        .btn-home {
            background-color: #1a1a1a;
            border: none;
            border-radius: 12px;
            padding: 16px 30px;
            font-weight: 700;
            color: white;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-home:hover {
            background-color: #333;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #ffd700;
            top: -20px;
            opacity: 0;
            animation: confetti 3s ease-in-out infinite;
        }

        @keyframes confetti {
            0% {
                top: -20px;
                opacity: 1;
                transform: rotate(0deg);
            }

            100% {
                top: 100%;
                opacity: 0;
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <!-- Contact Button & Modal -->
    <button type="button" class="btn btn-primary rounded-circle shadow" data-bs-toggle="modal"
        data-bs-target="#contactModal"
        style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1050; background-color: #196199; border: none; display: flex; align-items: center; justify-content: center;">
        <i class="bi bi-chat-dots-fill fs-3"></i>
    </button>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #196199;">
                    <h5 class="modal-title fw-bold" id="contactModalLabel"><i
                            class="bi bi-envelope-fill me-2"></i>Contact Admissions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4 small">If there are any concerns or need of improvement for this tool,
                        please email us at the appropriate department below.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <strong>Medicine</strong>
                            <a href="mailto:admission.med@dmsf.edu.ph"
                                class="text-decoration-none rounded px-2 py-1 bg-light small"><i
                                    class="bi bi-envelope me-1"></i> admission.med@dmsf.edu.ph</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <strong>Nursing</strong>
                            <a href="mailto:admission.nursing@dmsf.edu.ph"
                                class="text-decoration-none rounded px-2 py-1 bg-light small"><i
                                    class="bi bi-envelope me-1"></i> admission.nursing@dmsf.edu.ph</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <strong>Dentistry</strong>
                            <a href="mailto:admission.dentistry@dmsf.edu.ph"
                                class="text-decoration-none rounded px-2 py-1 bg-light small"><i
                                    class="bi bi-envelope me-1"></i> admission.dentistry@dmsf.edu.ph</a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <strong>Midwifery</strong>
                            <a href="mailto:admission.midwifery@dmsf.edu.ph"
                                class="text-decoration-none rounded px-2 py-1 bg-light small"><i
                                    class="bi bi-envelope me-1"></i> admission.midwifery@dmsf.edu.ph</a>
                        </li>
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                            <strong>Biology</strong>
                            <a href="mailto:admission.biology@dmsf.edu.ph"
                                class="text-decoration-none rounded px-2 py-1 bg-light small"><i
                                    class="bi bi-envelope me-1"></i> admission.biology@dmsf.edu.ph</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="success-card">
        <!-- Simple Confetti Effect -->
        <div class="confetti-piece" style="left: 10%; animation-delay: 0s; background: #dc3545;"></div>
        <div class="confetti-piece" style="left: 20%; animation-delay: 0.5s; background: #198754;"></div>
        <div class="confetti-piece" style="left: 30%; animation-delay: 1.2s; background: #0d6efd;"></div>
        <div class="confetti-piece" style="left: 45%; animation-delay: 0.2s; background: #ffc107;"></div>
        <div class="confetti-piece" style="left: 60%; animation-delay: 0.8s; background: #6610f2;"></div>
        <div class="confetti-piece" style="left: 75%; animation-delay: 1.5s; background: #fd7e14;"></div>
        <div class="confetti-piece" style="left: 90%; animation-delay: 0.3s; background: #20c997;"></div>

        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>

        <h2>Application Submitted!</h2>
        <p class="lead-text">Congratulations! Your application has been successfully submitted to the Davao Medical
            School Foundation Admission Office.</p>

        <div class="info-box">
            <h5><i class="bi bi-info-circle me-2"></i> What's Next?</h5>
            <p>Your documents are under review. You will receive an email once your application status is updated, usually within 24 hours.<br><br>
                If it takes longer, you may contact your college's Admissions Office using the email found in the "Contact" button at the lower right.</p>
        </div>

        <a href="index.php" class="btn btn-home">
            <i class="bi bi-house-door"></i> Back to Home
        </a>

        <div class="mt-5 pt-3 border-top">
            <p class="text-muted small mb-0">&copy; 2026 Davao Medical School Foundation, Inc.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Clear all admission drafts on completion
        document.addEventListener('DOMContentLoaded', function () {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('admission_draft_')) {
                    localStorage.removeItem(key);
                }
            });
            console.log('All admission drafts cleared.');
        });
    </script>
</body>

</html>