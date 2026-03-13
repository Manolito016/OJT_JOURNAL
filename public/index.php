<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Journal Report Generator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="print-styles.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📔</text></svg>">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <div>
                    <h1>OJT Journal Report Generator</h1>
                    <p class="subtitle">Document your On-the-Job Training journey</p>
                </div>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode">
                    <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Stats Dashboard -->
        <section class="stats-section">
            <div class="stats-container" id="statsContainer">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="stat-value" id="totalEntries">0</div>
                    <div class="stat-label">Total Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <div class="stat-value" id="totalImages">0</div>
                    <div class="stat-label">Total Images</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-value" id="totalDays">0</div>
                    <div class="stat-label">Days Tracked</div>
                </div>
            </div>
        </section>

        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- OJT Entry Form -->
        <section class="entry-section">
            <div class="entry-card">
                <h2>New OJT Entry</h2>
                
                <form id="ojtForm" class="ojt-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entryTitle">Entry Title *</label>
                            <input type="text" id="entryTitle" name="title" placeholder="e.g., Website Development - Day 1" required>
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
                                Enhance with AI
                            </button>
                        </div>
                        <span class="form-hint">AI will enhance this description with analysis from your images</span>
                    </div>

                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="imageInput" accept="image/*" multiple hidden>
                        <div class="upload-placeholder">
                            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p>Drag & drop images here or <span class="browse-link">browse</span></p>
                            <p class="upload-hint">Supports: JPEG, PNG, GIF, WebP (Max 5MB each)</p>
                        </div>
                        <div class="preview-container" id="previewContainer"></div>
                        <div class="upload-actions" id="uploadActions" style="display: none; margin-top: 1rem;">
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
                    </div>

                    <div class="status-message" id="statusMessage"></div>
                </form>
            </div>
        </section>

        <!-- Weekly Report Section -->
        <section class="report-section">
            <div class="report-header">
                <h2>All OJT Entries</h2>
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
                    <button class="btn btn-sm btn-outline" id="closeNarrativeBtn">&times;</button>
                </div>
                <div class="narrative-content" id="narrativeContent">
                    <!-- AI-generated narrative will appear here -->
                </div>
            </div>

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
                            <button class="btn btn-secondary" id="closeDownloadBtn">&times; Close</button>
                        </div>
                    </div>
                    <div class="download-report-content" id="downloadReportContent">
                        <!-- Report content will be loaded here -->
                    </div>
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
                            <button class="btn btn-secondary" id="closeAIReportBtn">&times; Close</button>
                        </div>
                    </div>
                    <div class="download-report-content" id="aiReportContent">
                        <!-- AI-generated report content will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Edit Entry Modal -->
            <div class="download-report-modal" id="editEntryModal">
                <div class="download-report-overlay"></div>
                <div class="download-report-container edit-entry-container">
                    <div class="download-report-header">
                        <h2>✏️ Edit OJT Entry</h2>
                        <button class="btn btn-secondary" id="closeEditEntryBtn">&times; Close</button>
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
                        <button class="btn btn-secondary" id="closeImageAnalysisBtn">&times; Close</button>
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

            <!-- AI Customize Modal -->
            <div class="download-report-modal" id="aiCustomizeModal">
                <div class="download-report-overlay"></div>
                <div class="download-report-container">
                    <div class="download-report-header">
                        <h2>✨ AI Customize Entry</h2>
                        <button class="btn btn-secondary" id="closeAICustomizeBtn">&times; Close</button>
                    </div>
                    <div class="download-report-content">
                        <form id="aiCustomizeForm">
                            <input type="hidden" id="customizeEntryId">
                            <div class="form-group">
                                <label for="currentDescription">Current Description</label>
                                <textarea id="currentDescription" rows="4" readonly></textarea>
                            </div>
                            <div class="form-group">
                                <label for="customPrompt">✨ What would you like AI to do?</label>
                                <textarea id="customPrompt" rows="3" placeholder="e.g., Make it more professional and emphasize the technical skills I used..."></textarea>
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

            <div class="report-grid" id="reportGrid">
                <!-- Entries will be loaded here -->
            </div>

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
        <div id="printReportContent">
            <!-- Content will be injected here -->
        </div>
    </div>

    <script src="script.js"></script>
    <script src="print-report.js"></script>
</body>
</html>
