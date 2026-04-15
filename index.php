<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMSF - Admission Portal</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #196199;
            /* Updated to DMSF Blue */
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

        .admission-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            padding: 60px 40px;
            max-width: 480px;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }

        .admission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-color);
        }

        .admission-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.12);
        }

        .logo-img {
            width: 120px !important;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 8px 15px rgba(0, 0, 0, 0.1));
        }

        h2 {
            color: #1a1a1a;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .sub-text {
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            margin-bottom: 10px;
            padding-left: 4px;
        }

        .form-select {
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid #f1f3f5;
            background-color: #f8f9fa;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 30px;
            transition: all 0.2s;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(25, 97, 153, 0.1);
        }

        .btn-apply {
            background-color: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-apply:hover {
            background-color: #124d7a;
            box-shadow: 0 8px 20px rgba(25, 97, 153, 0.3);
            transform: scale(1.02);
            color: white;
        }

        .footer-text {
            margin-top: 40px;
            font-size: 0.8rem;
            color: #adb5bd;
            font-weight: 500;
        }

        .college-selection-grid {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            border: 2px solid #f1f3f5;
        }

        .custom-check {
            padding: 10px 12px 10px 35px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .custom-check:hover {
            background: #e9ecef;
        }

        .form-check-input:checked+.form-check-label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .all-colleges-check {
            margin-top: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        .admin-link {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #adb5bd;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.2s;
        }

        .medicine-type-container {
            margin-top: 10px;
            margin-left: 35px;
            padding: 10px 15px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            display: none;
        }

        .medicine-type-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
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

    <div class="admission-card text-center">
        <a href="admin_login.php" class="admin-link" title="Admin Portal">
            <i class="bi bi-shield-lock"></i>
        </a>

        <div class="logo-container">
            <img src="DMSF_Logo.png" alt="DMSF Logo" class="logo-img">
        </div>

        <h2>Welcome</h2>
        <p class="sub-text">Embark on your medical journey with Davao Medical School Foundation.</p>

        <form action="apply.php" method="GET" autocomplete="off">
            <div class="text-start mb-4">
                <label class="form-label">Select Your Desired College(s)</label>
                <div class="college-selection-grid">
                    <!-- Applicant Type Selection (Filipino or Foreign) -->
                    <div class="col-12 mb-3 mt-2 applicant-type-container">
                        <span class="fw-bold d-block mb-2">Applicant Type</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="applicant_type" id="typeFilipino"
                                value="Filipino" checked>
                            <label class="form-check-label" for="typeFilipino">Filipino</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="applicant_type" id="typeForeign"
                                value="Foreign">
                            <label class="form-check-label" for="typeForeign">Foreign</label>
                        </div>
                    </div>

                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Medicine" id="medCollege">
                        <label class="form-check-label" for="medCollege">Doctor of Medicine</label>
                    </div>

                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Nursing" id="nursing">
                        <label class="form-check-label" for="nursing">BS in Nursing</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Dentistry" id="dentistry">
                        <label class="form-check-label" for="dentistry">Doctor of Dental Medicine</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Midwifery" id="midwifery">
                        <label class="form-check-label" for="midwifery">BS in Midwifery</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Biology" id="biology">
                        <label class="form-check-label" for="biology">BS in Biology</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Master in Community Health" id="mch">
                        <label class="form-check-label" for="mch">Master in Community Health</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Master in Health Professions Education" id="mhpe">
                        <label class="form-check-label" for="mhpe">Master in Health Professions Education</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Master in Participatory Development" id="mpd">
                        <label class="form-check-label" for="mpd">Master in Participatory Development</label>
                    </div>
                    <div class="form-check custom-check">
                        <input class="form-check-input college-checkbox" type="checkbox" name="college[]"
                            value="Accelerated Pathway for Medicine" id="apm">
                        <label class="form-check-label" for="apm">Accelerated Pathway for Medicine</label>
                    </div>
                    <div class="form-check custom-check all-colleges-check">
                        <input class="form-check-input" type="checkbox" id="selectAllColleges" value="All Colleges">
                        <label class="form-check-label fw-bold" for="selectAllColleges"
                            style="color: var(--primary-color);">Select All Colleges (Universal Application)</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-apply w-100 shadow-sm mt-2" id="submitBtn" disabled>
                Begin Application <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="footer-text">
            &copy; 2026 Davao Medical School Foundation, Inc.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAllColleges');
            const checkboxes = document.querySelectorAll('.college-checkbox');
            const submitBtn = document.getElementById('submitBtn');

            function checkSelection() {
                let anyChecked = Array.from(checkboxes).some(cb => cb.checked);

                // Handle All Colleges behavior
                if (selectAll.checked) {
                    anyChecked = true;
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                        cb.disabled = true;
                    });
                } else {
                    checkboxes.forEach(cb => {
                        cb.disabled = false;
                    });
                }

                submitBtn.disabled = !anyChecked;
            }

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                checkSelection();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                    checkSelection();
                });
            });
        });
    </script>


</body>

</html>