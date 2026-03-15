<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>OJT Journal Report Generator</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="print-styles.css?v=<?php echo time(); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📔</text></svg>">
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <header class="header" id="mainHeader">
            <div class="header-content">
                <div class="header-brand">
                    <div class="header-logo">📔</div>
                    <div class="header-title-group">
                        <h1>OJT Journal Report Generator</h1>
                        <p class="header-subtitle">Document your On-the-Job Training journey</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-sm btn-outline" id="rateDocumentBtn" title="AI will rate your document and suggest improvements">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        Rate My Report
                    </button>
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode" aria-label="Toggle theme">
                    <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- Bento Grid Layout -->
        <div class="bento-layout">
            <!-- Left Sidebar - Stats (Sticky) -->
            <aside class="bento-sidebar bento-sidebar-left">
                <div class="sticky-stats-wrapper">
                    <h3 class="sidebar-title">📊 Statistics</h3>
                    <div class="stats-vertical">
                        <div class="stat-card-vertical">
                            <div class="stat-icon primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="totalEntries">0</div>
                            <div class="stat-label">Entries</div>
                        </div>
                        <div class="stat-card-vertical">
                            <div class="stat-icon success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="totalImages">0</div>
                            <div class="stat-label">Images</div>
                        </div>
                        <div class="stat-card-vertical">
                            <div class="stat-icon warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="totalDays">0</div>
                            <div class="stat-label">Days</div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Center Content -->
            <main class="bento-main">

                <!-- ============================================================
                     SECTION: OJT REPORT INFORMATION (Collapsible)
                     Contains: Student Info, Company Profile, Duration, Purpose, Action Plan
                     ============================================================ -->
                <section class="entry-form-section">
                    <div class="report-info-card" id="reportInfoCard">
                        <div class="report-info-header">
                            <h2>📋 OJT Report Information</h2>
                            <button type="button" class="report-info-toggle-btn" id="toggleReportInfoBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                                Collapse
                            </button>
                        </div>

                        <form id="reportInfoForm" class="report-info-form">

                            <!-- Student Information -->
                            <div class="info-section">
                                <h3>👨‍🎓 Student Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="studentName">Name *</label>
                                        <input type="text" id="studentName" name="student_name" placeholder="(Replace with your name)" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentCourse">Course *</label>
                                        <input type="text" id="studentCourse" name="student_course" value="Bachelor of Science in Information Technology" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="schoolYear">School Year *</label>
                                    <input type="text" id="schoolYear" name="school_year" value="S.Y. 2025 - 2026" required>
                                </div>
                            </div>

                            <!-- Company Profile (Chapter I) -->
                            <div class="info-section">
                                <h3>🏢 Company Profile (Chapter I)</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="companyName">Company/Office Assigned *</label>
                                        <input type="text" id="companyName" name="company_name" placeholder="e.g., BayanAIhan" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyLocation">Location</label>
                                        <input type="text" id="companyLocation" name="company_location" placeholder="e.g., Candon City, Ilocos Sur">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="companyNatureOfBusiness">Nature of Business</label>
                                    <input type="text" id="companyNatureOfBusiness" name="company_nature_of_business" placeholder="e.g., AI Training and Education">
                                </div>
                                <div class="form-group">
                                    <label for="companyBackground">Introduction
                                        <div class="ai-buttons-group" style="margin-left: 8px;">
                                            <button type="button" class="ai-generate-btn" onclick="autoGenerateField('companyBackground', 'companyIntroduction')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                                </svg>
                                                Auto Generate
                                            </button>
                                            <button type="button" class="ai-enhance-btn" onclick="enhanceReportField('companyBackground', 'companyIntroduction')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                                                </svg>
                                                Enhance
                                            </button>
                                        </div>
                                    </label>
                                    <textarea id="companyBackground" name="company_background" rows="5" placeholder="Brief introduction of the company — history, mission, vision, and core values..."></textarea>
                                </div>
                            </div>

                            <!-- Duration and Time -->
                            <div class="info-section">
                                <h3>📅 Duration and Time</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="ojtStartDate">Start Date</label>
                                        <input type="text" id="ojtStartDate" name="ojt_start_date" value="February 18, 2026">
                                    </div>
                                    <div class="form-group">
                                        <label for="ojtEndDate">End Date</label>
                                        <input type="text" id="ojtEndDate" name="ojt_end_date" value="March 6, 2026">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="dailyHours">Daily Hours</label>
                                    <input type="text" id="dailyHours" name="daily_hours" placeholder="e.g., 8:00 AM - 5:00 PM">
                                </div>
                            </div>

                            <!-- Purpose/Role -->
                            <div class="info-section">
                                <h3>🎯 Purpose/Role to the Company
                                    <div class="ai-buttons-group">
                                        <button type="button" class="ai-generate-btn" onclick="autoGenerateField('purposeRole', 'purpose')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                            </svg>
                                            Auto Generate
                                        </button>
                                        <button type="button" class="ai-enhance-btn" onclick="enhanceReportField('purposeRole', 'purpose')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                                            </svg>
                                            Enhance
                                        </button>
                                    </div>
                                </h3>
                                <div class="form-group">
                                    <label for="purposeRole">Your Role and Objectives</label>
                                    <textarea id="purposeRole" name="purpose_role" rows="3" placeholder="Describe your specific role and what you aimed to achieve..."></textarea>
                                </div>
                            </div>

                            <!-- Background of Action Plan -->
                            <div class="info-section">
                                <h3>📝 Background of the Action Plan
                                    <div class="ai-buttons-group">
                                        <button type="button" class="ai-generate-btn" onclick="autoGenerateField('backgroundActionPlan', 'actionPlan')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                            </svg>
                                            Auto Generate
                                        </button>
                                        <button type="button" class="ai-enhance-btn" onclick="enhanceReportField('backgroundActionPlan', 'actionPlan')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                                            </svg>
                                            Enhance
                                        </button>
                                    </div>
                                </h3>
                                <div class="form-group">
                                    <label for="backgroundActionPlan">Action Plan Description</label>
                                    <textarea id="backgroundActionPlan" name="background_action_plan" rows="4" placeholder="Describe the plan you created before starting the immersion..."></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="saveReportInfoBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    Save Report Information
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetReportInfoBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <polyline points="1 4 1 10 7 10"/>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    Reset
                                </button>
                            </div>

                            <div class="status-message" id="reportInfoStatus"></div>
                        </form>
                    </div>
                </section>

                <!-- ============================================================
                     SECTION: NEW OJT ENTRY FORM
                     ============================================================ -->
                <section class="entry-section">
                    <div class="entry-card">
                        <h2>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            New OJT Entry
                        </h2>

                        <form id="ojtForm" class="ojt-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="entryTitle">Entry Title *</label>
                                    <input type="text" id="entryTitle" name="title" placeholder="e.g., Website Development - Day 1" required>
                                    <span class="char-count" id="titleCharCount">0/200</span>
                                </div>
                                <div class="form-group">
                                    <label for="entryDate">Date *</label>
                                    <input type="date" id="entryDate" name="entry_date" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="entryDescription">Your Description</label>
                                <div class="description-input-wrapper">
                                    <textarea id="entryDescription" name="description" rows="3" placeholder="Briefly describe what you did today, tasks completed, challenges faced, and skills learned..."></textarea>
                                    <button type="button" class="btn btn-sm btn-outline" id="enhanceBtn" title="AI will refine and enhance your description">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                                        </svg>
                                        Enhance
                                    </button>
                                </div>
                                <span class="form-hint">AI will enhance this description with analysis from your images</span>
                            </div>

                            <div class="upload-area" id="uploadArea">
                                <input type="file" id="imageInput" accept="image/*" multiple hidden>
                                <div class="upload-placeholder">
                                    <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <p>Drag & drop images here or <span class="browse-link">browse</span></p>
                                    <p class="upload-hint">Supports: JPEG, PNG, GIF, WebP (Max 25 images, 5MB each)</p>
                                </div>
                                <div class="preview-container" id="previewContainer"></div>
                                <div class="upload-actions" id="uploadActions" style="display: none;">
                                    <button type="button" class="btn btn-primary" id="analyzeImagesBtn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                                        </svg>
                                        Auto-Generate Title & Description
                                    </button>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Create Entry
                                    <span class="btn-loader hidden"></span>
                                </button>
                                <button type="button" class="btn btn-secondary" id="clearBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    Clear Form
                                </button>
                                <button type="button" class="btn btn-outline" id="addReflectionBtn" title="AI will add deep reflection questions to your entry">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    Add Reflection
                                </button>
                                <button type="button" class="btn btn-outline" id="improveWritingBtn" title="AI will improve sentence variety and flow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                                        <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                                        <path d="M2 2l7.586 7.586"/>
                                        <circle cx="11" cy="11" r="2"/>
                                    </svg>
                                    Improve Flow
                                </button>
                            </div>

                            <div class="status-message" id="statusMessage"></div>
                        </form>
                    </div>
                </section>

                <!-- ============================================================
                     SECTION: ALL OJT ENTRIES
                     ============================================================ -->
                <section class="report-section">
                    <div class="report-header">
                        <h2>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            All OJT Entries
                        </h2>
                        <div class="report-actions">
                            <button class="btn btn-outline" id="narrativeBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                                Generate Narrative
                            </button>
                            <button class="btn btn-primary" id="downloadReportBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Download Report
                            </button>
                            <button class="btn btn-outline" id="aiReportBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                                </svg>
                                AI Report
                            </button>
                            <button class="btn btn-outline" id="refreshBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <polyline points="23 4 23 10 17 10"/>
                                    <polyline points="1 20 1 14 7 14"/>
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                </svg>
                                Refresh
                            </button>
                        </div>
                    </div>

                    <div class="week-info" id="weekInfo">
                        <span class="week-range" id="weekRange">Loading...</span>
                        <span class="entry-count" id="entryCount">0 entries</span>
                    </div>

                    <!-- Narrative Report Section -->
                    <div class="narrative-container" id="narrativeContainer">
                        <div class="narrative-header">
                            <h3>OJT Narrative Report</h3>
                            <button class="btn btn-sm btn-outline" id="closeNarrativeBtn" style="color: white; border-color: rgba(255,255,255,0.5);">
                                &times; Close
                            </button>
                        </div>
                        <div class="narrative-content" id="narrativeContent"></div>
                    </div>

                    <!-- Entries Grid -->
                    <div class="report-grid" id="reportGrid"></div>

                    <div class="empty-state" id="emptyState">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <h3>No OJT entries yet</h3>
                        <p>Fill out the form above to document your training activities</p>
                    </div>
                </section>

                <!-- ============================================================
                     SECTION: CHAPTER III — CONCLUSION & RECOMMENDATION (NEW)
                     Moved OUT of the collapsible card, lives below entries
                     ============================================================ -->
                <section class="conclusion-recommendation-section" id="conclusionRecommendationSection">
                    <div class="cr-card">
                        <div class="cr-card-header">
                            <div class="cr-card-title-group">
                                <span class="cr-chapter-badge">Chapter III</span>
                                <h2>Conclusion and Recommendation</h2>
                            </div>
                            <button type="button" class="report-info-toggle-btn" id="toggleCRBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                                Collapse
                            </button>
                        </div>

                        <div class="cr-card-body" id="crCardBody">

                            <!-- Conclusion -->
                            <div class="cr-group conclusion-group">
                                <div class="info-section">
                                    <h3>✅ Conclusion
                                        <div class="ai-buttons-group">
                                            <button type="button" class="ai-generate-btn" onclick="autoGenerateField('conclusion', 'conclusion')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                                </svg>
                                                Auto Generate
                                            </button>
                                            <button type="button" class="ai-enhance-btn" onclick="enhanceReportField('conclusion', 'conclusion')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                                                </svg>
                                                Enhance
                                            </button>
                                        </div>
                                    </h3>
                                    <div class="form-group">
                                        <label for="conclusion">Overall Experience and Learnings</label>
                                        <textarea id="conclusion" name="conclusion" rows="5" placeholder="Summarize your overall experience and learnings from the OJT program..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendation -->
                            <div class="cr-group recommendation-group">
                                <div class="info-section">
                                    <h3>💡 Recommendation
                                        <button type="button" class="ai-generate-btn" onclick="autoGenerateAllRecommendations()" style="margin-left: 8px;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                            </svg>
                                            Auto Generate All
                                        </button>
                                    </h3>
                                    <p class="cr-section-hint">Provide suggestions for future OJT students, the company, and the school.</p>

                                    <div class="cr-recommendation-grid">
                                        <div class="cr-recommendation-item">
                                            <div class="form-group">
                                                <label for="recommendationStudents">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="9" cy="7" r="4"/>
                                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                                    </svg>
                                                    For Future OJT Students
                                                </label>
                                                <textarea id="recommendationStudents" name="recommendation_students" rows="4" placeholder="Suggestions and advice for students who will undergo OJT..."></textarea>
                                            </div>
                                        </div>
                                        <div class="cr-recommendation-item">
                                            <div class="form-group">
                                                <label for="recommendationCompany">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                                    </svg>
                                                    For the Company
                                                </label>
                                                <textarea id="recommendationCompany" name="recommendation_company" rows="4" placeholder="Constructive feedback and suggestions for the company..."></textarea>
                                            </div>
                                        </div>
                                        <div class="cr-recommendation-item">
                                            <div class="form-group">
                                                <label for="recommendationSchool">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                        <polyline points="9 22 9 12 15 12 15 22"/>
                                                    </svg>
                                                    For the School (ISPSC)
                                                </label>
                                                <textarea id="recommendationSchool" name="recommendation_school" rows="4" placeholder="Recommendations for improving the OJT program..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="cr-form-actions">
                                <button type="button" class="btn btn-primary" id="saveCRBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    Save Conclusion & Recommendation
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetCRBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <polyline points="1 4 1 10 7 10"/>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    Reset
                                </button>
                            </div>
                            <div class="status-message" id="crStatus"></div>

                        </div><!-- /cr-card-body -->
                    </div><!-- /cr-card -->
                </section>

            </main><!-- /bento-main -->

            <!-- Right Sidebar - Quick Actions + Recent -->
            <aside class="bento-sidebar bento-sidebar-right">
                <!-- Quick Actions -->
                <div class="quick-actions-card">
                    <h3 class="sidebar-title">⚡ Quick Actions</h3>
                    <div class="quick-actions-vertical">
                        <button class="quick-action-btn" id="quickDownloadReport">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span>Download</span>
                        </button>
                        <button class="quick-action-btn ai" id="quickAIReport">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                            </svg>
                            <span>✨ AI Report</span>
                        </button>
                        <button class="quick-action-btn" id="quickPrint">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                <polyline points="6 9 6 2 18 2 18 9"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect x="6" y="14" width="12" height="8"/>
                            </svg>
                            <span>Print</span>
                        </button>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="mini-stats-card">
                    <h3 class="sidebar-title">📊 This Week</h3>
                    <div class="mini-stats-vertical">
                        <div class="mini-stat-item">
                            <span class="mini-stat-value" id="weekEntries">0</span>
                            <span class="mini-stat-label">Entries</span>
                        </div>
                        <div class="mini-stat-item">
                            <span class="mini-stat-value" id="weekImages">0</span>
                            <span class="mini-stat-label">Images</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Entries -->
                <div class="recent-entries-card">
                    <h3 class="sidebar-title">📜 Recent</h3>
                    <div class="recent-entries-vertical" id="recentEntriesList"></div>
                </div>
            </aside>
        </div><!-- /bento-layout -->
    </div><!-- /container -->

    <!-- ============================================================
         MODALS
         ============================================================ -->

    <!-- Download Report Modal -->
    <div class="download-report-modal" id="downloadReportModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container">
            <div class="download-report-header">
                <h2>OJT Report Preview</h2>
                <div class="download-report-actions">
                    <button class="btn btn-outline" id="downloadWordBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Download Word
                    </button>
                    <button class="btn btn-outline" id="downloadPdfBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Download PDF
                    </button>
                    <button class="btn btn-primary" id="printDownloadBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Print
                    </button>
                    <button class="modal-close" id="closeDownloadBtn" aria-label="Close modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="download-report-content" id="downloadReportContent"></div>
        </div>
    </div>

    <!-- AI Report Modal -->
    <div class="download-report-modal" id="aiReportModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container">
            <div class="download-report-header">
                <h2>AI-Generated OJT Report</h2>
                <div class="download-report-actions">
                    <button class="btn btn-outline" id="aiDownloadWordBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Download Word
                    </button>
                    <button class="btn btn-outline" id="aiDownloadPdfBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Download PDF
                    </button>
                    <button class="btn btn-primary" id="aiPrintBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Print
                    </button>
                    <button class="modal-close" id="closeAIReportBtn" aria-label="Close modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="download-report-content" id="aiReportContent"></div>
        </div>
    </div>

    <!-- Edit Entry Modal -->
    <div class="download-report-modal" id="editEntryModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container edit-entry-container">
            <div class="download-report-header">
                <h2>✏️ Edit OJT Entry</h2>
                <button class="modal-close" id="closeEditEntryBtn" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="download-report-content">
                <form id="editEntryForm" class="edit-entry-form">
                    <input type="hidden" id="editEntryId">
                    <div class="form-group">
                        <label for="editEntryTitle">Entry Title *</label>
                        <input type="text" id="editEntryTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="editEntryDate">Date *</label>
                        <input type="date" id="editEntryDate" name="entry_date" required>
                    </div>
                    <div class="form-group">
                        <label for="editEntryDescription">Description</label>
                        <textarea id="editEntryDescription" name="description" rows="5"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" id="cancelEditEntryBtn">Cancel</button>
                    </div>
                    <div class="status-message" id="editStatusMessage"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Analysis Modal -->
    <div class="download-report-modal" id="imageAnalysisModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container image-analysis-container">
            <div class="download-report-header">
                <h2>🖼️ Image Analysis</h2>
                <button class="modal-close" id="closeImageAnalysisBtn" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="download-report-content image-analysis-content">
                <div class="image-analysis-image">
                    <img id="analysisImage" src="" alt="Image analysis preview">
                </div>
                <div class="image-analysis-details">
                    <div class="form-group">
                        <label>AI Description</label>
                        <textarea id="analysisDescription" rows="8"></textarea>
                    </div>
                    <div class="image-analysis-actions">
                        <button class="btn btn-primary" id="saveAnalysisBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Save Changes
                        </button>
                        <button class="btn btn-outline" id="regenerateAnalysisBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <polyline points="23 4 23 10 17 10"/>
                                <polyline points="1 20 1 14 7 14"/>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                            </svg>
                            Regenerate
                        </button>
                    </div>
                    <div class="status-message" id="analysisStatusMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Rating Modal -->
    <div class="download-report-modal" id="ratingModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container rating-container">
            <div class="download-report-header">
                <h2>📊 AI Document Rating</h2>
                <button class="modal-close" id="closeRatingBtn" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="rating-content" id="ratingContent">
                <!-- Rating dashboard will be injected here -->
            </div>
        </div>
    </div>

    <!-- AI Customize Modal -->
    <div class="download-report-modal" id="aiCustomizeModal">
        <div class="download-report-overlay"></div>
        <div class="download-report-container">
            <div class="download-report-header">
                <h2>✨ AI Customize Entry</h2>
                <button class="modal-close" id="closeAICustomizeBtn" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="download-report-content">
                <form id="aiCustomizeForm">
                    <input type="hidden" id="customizeEntryId">
                    <div class="form-group">
                        <label for="currentDescription">Current Description</label>
                        <textarea id="currentDescription" rows="4" readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label for="enhancementStyle">🎨 Enhancement Style</label>
                        <select id="enhancementStyle">
                            <option value="professional">💼 Professional - Formal tone with focus on technical skills</option>
                            <option value="detailed">📝 Detailed - Comprehensive with specific tasks and solutions</option>
                            <option value="concise">⚡ Concise - Clear and direct key points</option>
                            <option value="academic">🎓 Academic - Reflective with learning outcomes</option>
                        </select>
                        <span class="form-hint">Choose how you want AI to enhance your entry</span>
                    </div>
                    <div class="form-group">
                        <label for="customPrompt">✨ What would you like AI to do?</label>
                        <textarea id="customPrompt" rows="3" placeholder="e.g., Make it more professional and emphasize the technical skills I used..."></textarea>
                        <div class="smart-suggestions">
                            <span class="suggestions-label">Quick suggestions:</span>
                            <button type="button" class="suggestion-chip" data-prompt="Make it more professional and formal for my OJT report">💼 More Professional</button>
                            <button type="button" class="suggestion-chip" data-prompt="Add more technical details about the tools and technologies I used">🔧 More Technical</button>
                            <button type="button" class="suggestion-chip" data-prompt="Emphasize the skills I learned and how I grew">📈 Highlight Learning</button>
                            <button type="button" class="suggestion-chip" data-prompt="Make it concise and focus on key accomplishments">⚡ More Concise</button>
                            <button type="button" class="suggestion-chip" data-prompt="Improve grammar and flow while keeping the same meaning">✨ Polish Writing</button>
                        </div>
                        <span class="form-hint">Describe how you want AI to improve or modify your entry</span>
                    </div>
                    <div class="form-group">
                        <label for="enhancedPreview">AI-Enhanced Preview</label>
                        <textarea id="enhancedPreview" rows="6" readonly placeholder="AI enhancement will appear here after you click Generate..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="generateCustomizationBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                            </svg>
                            Generate with AI
                        </button>
                        <button type="button" class="btn btn-success" id="applyCustomizationBtn" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Apply Changes
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelCustomizeBtn">Cancel</button>
                    </div>
                    <div class="status-message" id="customizeStatusMessage"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Lightbox Gallery Modal -->
    <div class="lightbox-modal" id="lightboxModal">
        <div class="lightbox-content">
            <button class="lightbox-close" id="lightboxClose" aria-label="Close lightbox">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="lightbox-image-wrapper">
                <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <img class="lightbox-image" id="lightboxImage" src="" alt="Full size image">
                <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
            <div class="lightbox-caption" id="lightboxCaption"></div>
            <div class="lightbox-controls">
                <button class="lightbox-zoom-btn" id="lightboxZoomIn" aria-label="Zoom in">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="11" y1="8" x2="11" y2="14"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </button>
                <button class="lightbox-zoom-btn" id="lightboxZoomOut" aria-label="Zoom out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </button>
                <button class="lightbox-zoom-btn" id="lightboxReset" aria-label="Reset zoom">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Print Area for Download Report -->
    <div id="printReportArea" class="print-report-area">
        <div class="print-header-section">
            <div class="ispcc-header">
                <strong>ILOCOS SUR POLYTECHNIC STATE COLLEGE</strong><br>
                Candon Campus
            </div>
            <h1 class="ispcc-title">OJT REPORT</h1>
            <p class="ispcc-program">Bachelor of Science in Information Technology</p>
        </div>
        <div id="printReportContent"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script src="print-report.js?v=<?php echo time(); ?>"></script>

    <!-- Chapter III: Conclusion & Recommendation inline controller -->
    <script>
    (function () {
        // ── Toggle collapse for Chapter III card ──────────────────────────────
        const crCard   = document.getElementById('crCardBody');
        const crBtn    = document.getElementById('toggleCRBtn');

        if (crBtn && crCard) {
            crBtn.addEventListener('click', function () {
                const collapsed = crCard.style.display === 'none';
                crCard.style.display = collapsed ? '' : 'none';
                crBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"
                         style="transform:${collapsed ? 'rotate(0deg)' : 'rotate(180deg)'}; transition:transform .3s">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                    ${collapsed ? 'Collapse' : 'Expand'}
                `;
            });
        }

        // ── Save Conclusion & Recommendation ─────────────────────────────────
        const saveCRBtn  = document.getElementById('saveCRBtn');
        const crStatus   = document.getElementById('crStatus');

        if (saveCRBtn) {
            saveCRBtn.addEventListener('click', async function () {
                saveCRBtn.disabled = true;
                saveCRBtn.textContent = 'Saving…';

                const payload = {
                    conclusion:               (document.getElementById('conclusion')            || {}).value || '',
                    recommendation_students:  (document.getElementById('recommendationStudents') || {}).value || '',
                    recommendation_company:   (document.getElementById('recommendationCompany')  || {}).value || '',
                    recommendation_school:    (document.getElementById('recommendationSchool')   || {}).value || '',
                };

                try {
                    const res    = await fetch('process.php?action=saveConclusionRecommendation', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify(payload),
                    });
                    const result = await res.json();

                    if (result.success) {
                        if (typeof showToast === 'function') showToast('Saved successfully!', 'success');
                        if (crStatus) {
                            crStatus.className  = 'status-message show success';
                            crStatus.textContent = 'Conclusion & Recommendation saved!';
                            setTimeout(() => crStatus.classList.remove('show'), 4000);
                        }
                    } else {
                        if (typeof showToast === 'function') showToast(result.error || 'Save failed', 'error');
                        if (crStatus) {
                            crStatus.className  = 'status-message show error';
                            crStatus.textContent = result.error || 'Failed to save.';
                        }
                    }
                } catch (err) {
                    if (typeof showToast === 'function') showToast('Network error: ' + err.message, 'error');
                } finally {
                    saveCRBtn.disabled = false;
                    saveCRBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Save Conclusion &amp; Recommendation`;
                }
            });
        }

        // ── Reset Conclusion & Recommendation ────────────────────────────────
        const resetCRBtn = document.getElementById('resetCRBtn');
        if (resetCRBtn) {
            resetCRBtn.addEventListener('click', function () {
                ['conclusion', 'recommendationStudents', 'recommendationCompany', 'recommendationSchool']
                    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                if (typeof showToast === 'function') showToast('Fields cleared', 'info');
            });
        }

        // ── Load saved values on page load ───────────────────────────────────
        // Values are loaded by script.js → loadReportInfo(), which already
        // populates conclusion / recommendationStudents / etc. from the DB.
        // No extra fetch needed here.
    })();
    </script>

</body>
</html>