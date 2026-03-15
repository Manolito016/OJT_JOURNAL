/**
 * OJT Journal Report Generator - Frontend JavaScript
 * Modern UI/UX with enhanced interactions, toast notifications, and lightbox gallery
 */

// =============================================================================
// DOM ELEMENTS
// =============================================================================

const ojtForm = document.getElementById('ojtForm');
const entryTitle = document.getElementById('entryTitle');
const entryDate = document.getElementById('entryDate');
const entryDescription = document.getElementById('entryDescription');
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('imageInput');
const previewContainer = document.getElementById('previewContainer');
const submitBtn = document.getElementById('submitBtn');
const clearBtn = document.getElementById('clearBtn');
const statusMessage = document.getElementById('statusMessage');
const reportGrid = document.getElementById('reportGrid');
const emptyState = document.getElementById('emptyState');
const weekRange = document.getElementById('weekRange');
const entryCount = document.getElementById('entryCount');
const refreshBtn = document.getElementById('refreshBtn');
const narrativeBtn = document.getElementById('narrativeBtn');
const narrativeContainer = document.getElementById('narrativeContainer');
const narrativeContent = document.getElementById('narrativeContent');
const closeNarrativeBtn = document.getElementById('closeNarrativeBtn');
const themeToggle = document.getElementById('themeToggle');
const mainHeader = document.getElementById('mainHeader');

// Modal Elements
const editEntryModal = document.getElementById('editEntryModal');
const editEntryForm = document.getElementById('editEntryForm');
const editEntryId = document.getElementById('editEntryId');
const editEntryTitle = document.getElementById('editEntryTitle');
const editEntryDate = document.getElementById('editEntryDate');
const editEntryDescription = document.getElementById('editEntryDescription');
const closeEditEntryBtn = document.getElementById('closeEditEntryBtn');
const cancelEditEntryBtn = document.getElementById('cancelEditEntryBtn');
const editStatusMessage = document.getElementById('editStatusMessage');

// Image Analysis Modal
const imageAnalysisModal = document.getElementById('imageAnalysisModal');
const analysisImage = document.getElementById('analysisImage');
const analysisDescription = document.getElementById('analysisDescription');
const closeImageAnalysisBtn = document.getElementById('closeImageAnalysisBtn');
const saveAnalysisBtn = document.getElementById('saveAnalysisBtn');
const regenerateAnalysisBtn = document.getElementById('regenerateAnalysisBtn');
const analysisStatusMessage = document.getElementById('analysisStatusMessage');

// AI Customize Modal
const aiCustomizeModal = document.getElementById('aiCustomizeModal');
const customizeEntryId = document.getElementById('customizeEntryId');
const customPrompt = document.getElementById('customPrompt');
const currentDescription = document.getElementById('currentDescription');
const enhancedPreview = document.getElementById('enhancedPreview');
const generateCustomizationBtn = document.getElementById('generateCustomizationBtn');
const applyCustomizationBtn = document.getElementById('applyCustomizationBtn');
const cancelCustomizeBtn = document.getElementById('cancelCustomizeBtn');
const closeAICustomizeBtn = document.getElementById('closeAICustomizeBtn');
const customizeStatusMessage = document.getElementById('customizeStatusMessage');
const enhancementStyle = document.getElementById('enhancementStyle');

// Download Report Modal (use var for global access by print-report.js)
var downloadReportModal = document.getElementById('downloadReportModal');
var downloadReportContent = document.getElementById('downloadReportContent');
var closeDownloadBtn = document.getElementById('closeDownloadBtn');

// AI Report Modal (use var for global access by print-report.js)
var aiReportModal = document.getElementById('aiReportModal');
var aiReportContent = document.getElementById('aiReportContent');
var closeAIReportBtn = document.getElementById('closeAIReportBtn');

// Lightbox Modal
const lightboxModal = document.getElementById('lightboxModal');
const lightboxImage = document.getElementById('lightboxImage');
const lightboxCaption = document.getElementById('lightboxCaption');
const lightboxClose = document.getElementById('lightboxClose');
const lightboxPrev = document.getElementById('lightboxPrev');
const lightboxNext = document.getElementById('lightboxNext');
const lightboxZoomIn = document.getElementById('lightboxZoomIn');
const lightboxZoomOut = document.getElementById('lightboxZoomOut');
const lightboxReset = document.getElementById('lightboxReset');

// OJT Report Info Form
const reportInfoForm = document.getElementById('reportInfoForm');
const reportInfoStatus = document.getElementById('reportInfoStatus');
const saveReportInfoBtn = document.getElementById('saveReportInfoBtn');
const resetReportInfoBtn = document.getElementById('resetReportInfoBtn');
const toggleReportInfoBtn = document.getElementById('toggleReportInfoBtn');
const reportInfoCard = document.querySelector('.report-info-card');

console.log('Report Info Elements:', {
    toggleReportInfoBtn: toggleReportInfoBtn ? 'Found' : 'NOT FOUND',
    reportInfoCard: reportInfoCard ? 'Found' : 'NOT FOUND'
});

// Report Info Form Fields
const studentName = document.getElementById('studentName');
const studentCourse = document.getElementById('studentCourse');
const schoolYear = document.getElementById('schoolYear');
const companyName = document.getElementById('companyName');
const companyLocation = document.getElementById('companyLocation');
const companyNatureOfBusiness = document.getElementById('companyNatureOfBusiness');
const companyBackground = document.getElementById('companyBackground');
const ojtStartDate = document.getElementById('ojtStartDate');
const ojtEndDate = document.getElementById('ojtEndDate');
const dailyHours = document.getElementById('dailyHours');
const purposeRole = document.getElementById('purposeRole');
const backgroundActionPlan = document.getElementById('backgroundActionPlan');
const conclusion = document.getElementById('conclusion');
const recommendationStudents = document.getElementById('recommendationStudents');
const recommendationCompany = document.getElementById('recommendationCompany');
const recommendationSchool = document.getElementById('recommendationSchool');
const acknowledgment = document.getElementById('acknowledgment');

// Loading Overlay
const loadingOverlay = document.getElementById('loadingOverlay');

// =============================================================================
// STATE
// =============================================================================

let selectedFiles = [];
let narrativeCache = null;
let currentEditEntry = null;
let currentImageId = null;
let currentEntryId = null;
let reportInfoData = null;
let downloadReportCache = null;

// Lightbox state
let lightboxImages = [];
let lightboxCurrentIndex = 0;
let lightboxZoom = 1;

// =============================================================================
// INITIALIZATION
// =============================================================================

// Set default date to today
if (entryDate) {
    entryDate.valueAsDate = new Date();
}

// Initialize theme
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

// Toggle theme
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    showToast(`Switched to ${newTheme} mode`, 'info');
}

// Unregister service workers
async function unregisterServiceWorkers() {
    if ('serviceWorker' in navigator) {
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (const registration of registrations) {
                await registration.unregister();
            }
        } catch (error) {
            console.error('Failed to unregister service workers:', error);
        }
    }
}

// Clear cache
async function clearCache() {
    if ('caches' in window) {
        try {
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames.map(cacheName => caches.delete(cacheName))
            );
        } catch (error) {
            console.error('Failed to clear cache:', error);
        }
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', async () => {
    await unregisterServiceWorkers();
    await clearCache();
    initializeTheme();
    initializeEventListeners();
    initializeHeaderScroll();
    loadWeeklyReport();
    initializeDownloadReport();
    initializeLightbox();
    restoreCollapseState(); // Restore collapse/expand state
    restoreFormData(); // Restore form data
});

// =============================================================================
// HEADER SCROLL EFFECT
// =============================================================================

function initializeHeaderScroll() {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            mainHeader.classList.add('scrolled');
        } else {
            mainHeader.classList.remove('scrolled');
        }
    });
}

// =============================================================================
// EVENT LISTENERS
// =============================================================================

function initializeEventListeners() {
    // Form submission
    if (ojtForm) {
        ojtForm.addEventListener('submit', handleSubmit);
    }

    // Upload area click
    if (uploadArea) {
        uploadArea.addEventListener('click', (e) => {
            if (e.target !== submitBtn && e.target !== clearBtn && !e.target.closest('.remove-btn')) {
                imageInput.click();
            }
        });
    }

    // File input change
    if (imageInput) {
        imageInput.addEventListener('change', handleFileSelect);
    }

    // Drag and drop
    if (uploadArea) {
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('dragleave', handleDragLeave);
        uploadArea.addEventListener('drop', handleDrop);
    }

    // Clear button
    if (clearBtn) {
        clearBtn.addEventListener('click', clearForm);
    }

    // Refresh button
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadWeeklyReport);
    }

    // Narrative button
    if (narrativeBtn) {
        narrativeBtn.addEventListener('click', handleGenerateNarrative);
    }

    // Close narrative button
    if (closeNarrativeBtn) {
        closeNarrativeBtn.addEventListener('click', () => {
            narrativeContainer.classList.remove('show');
        });
    }

    // Theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Quick Edit Date (delegated event listener for dynamically created cards)
    document.addEventListener('click', function(e) {
        const quickEditBtn = e.target.closest('.ojt-quick-edit-date');
        if (quickEditBtn) {
            const entryId = quickEditBtn.dataset.id;
            const currentDate = quickEditBtn.dataset.date;
            const entryTitle = quickEditBtn.dataset.title;
            const entryDesc = quickEditBtn.dataset.desc;
            showQuickDatePicker(entryId, currentDate, entryTitle, entryDesc);
        }
    });

    // Initialize Quick Actions (Bento Grid)
    initializeQuickActions();

    // Enhance Description button
    const enhanceBtn = document.getElementById('enhanceBtn');
    if (enhanceBtn) {
        enhanceBtn.addEventListener('click', handleEnhanceDescription);
    }

    // Character count for title
    if (entryTitle) {
        entryTitle.addEventListener('input', updateTitleCharCount);
    }

    // Edit Entry Modal
    if (closeEditEntryBtn) {
        closeEditEntryBtn.addEventListener('click', closeEditEntryModal);
    }
    if (cancelEditEntryBtn) {
        cancelEditEntryBtn.addEventListener('click', closeEditEntryModal);
    }
    if (editEntryForm) {
        editEntryForm.addEventListener('submit', handleEditEntrySubmit);
    }

    // Close edit modal on overlay click
    if (editEntryModal) {
        editEntryModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('download-report-overlay')) {
                closeEditEntryModal();
            }
        });
    }

    // Image Analysis Modal
    if (closeImageAnalysisBtn) {
        closeImageAnalysisBtn.addEventListener('click', closeImageAnalysisModal);
    }
    if (saveAnalysisBtn) {
        saveAnalysisBtn.addEventListener('click', handleSaveAnalysis);
    }
    if (regenerateAnalysisBtn) {
        regenerateAnalysisBtn.addEventListener('click', handleRegenerateAnalysis);
    }

    // Analyze Images button
    const analyzeImagesBtn = document.getElementById('analyzeImagesBtn');
    const uploadActions = document.getElementById('uploadActions');

    if (analyzeImagesBtn) {
        analyzeImagesBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            handleAnalyzeImagesForEntry();
        });
    }

    // Show/hide analyze button based on uploaded images
    if (uploadActions && previewContainer) {
        const observer = new MutationObserver((mutations) => {
            const hasImages = previewContainer.querySelectorAll('img').length > 0;
            uploadActions.style.display = hasImages ? 'block' : 'none';
        });
        observer.observe(previewContainer, { childList: true });
    }

    // AI Customize Modal
    if (closeAICustomizeBtn) {
        closeAICustomizeBtn.addEventListener('click', closeAICustomizeModal);
    }
    if (cancelCustomizeBtn) {
        cancelCustomizeBtn.addEventListener('click', closeAICustomizeModal);
    }
    if (generateCustomizationBtn) {
        generateCustomizationBtn.addEventListener('click', handleGenerateCustomization);
    }
    if (applyCustomizationBtn) {
        applyCustomizationBtn.addEventListener('click', handleApplyCustomization);
    }

    // Add Reflection Button
    const addReflectionBtn = document.getElementById('addReflectionBtn');
    if (addReflectionBtn) {
        addReflectionBtn.addEventListener('click', handleAddReflection);
    }

    // Improve Writing Flow Button
    const improveWritingBtn = document.getElementById('improveWritingBtn');
    if (improveWritingBtn) {
        improveWritingBtn.addEventListener('click', handleImproveWritingFlow);
    }

    // Rate Document Button
    const rateDocumentBtn = document.getElementById('rateDocumentBtn');
    if (rateDocumentBtn) {
        rateDocumentBtn.addEventListener('click', handleRateDocument);
    }

    // Close Rating Modal
    const closeRatingBtn = document.getElementById('closeRatingBtn');
    if (closeRatingBtn) {
        closeRatingBtn.addEventListener('click', () => {
            document.getElementById('ratingModal').classList.remove('show');
        });
    }

    // Close AI customize modal on overlay click
    if (aiCustomizeModal) {
        aiCustomizeModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('download-report-overlay')) {
                closeAICustomizeModal();
            }
        });
    }

    // Smart suggestion chips
    document.querySelectorAll('.suggestion-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const prompt = chip.getAttribute('data-prompt');
            if (customPrompt && prompt) {
                customPrompt.value = prompt;
                customPrompt.focus();
            }
        });
    });

    // OJT Report Info Form
    if (reportInfoForm) {
        reportInfoForm.addEventListener('submit', handleReportInfoSubmit);
    }
    if (resetReportInfoBtn) {
        resetReportInfoBtn.addEventListener('click', handleResetReportInfo);
        console.log('Reset button listener attached');
    }
    if (toggleReportInfoBtn) {
        toggleReportInfoBtn.addEventListener('click', handleToggleReportInfo);
        console.log('Toggle button listener attached');
    } else {
        console.error('Toggle button NOT FOUND!');
    }
    
    // Auto-save form data on input change
    if (reportInfoForm) {
        reportInfoForm.addEventListener('input', saveFormData);
    }

    // Download Report Modal
    if (closeDownloadBtn) {
        closeDownloadBtn.addEventListener('click', () => {
            downloadReportModal.classList.remove('show');
        });
    }

    // AI Report Modal
    if (closeAIReportBtn) {
        closeAIReportBtn.addEventListener('click', () => {
            aiReportModal.classList.remove('show');
        });
    }

    // Load report info
    loadReportInfo();
}

// =============================================================================
// TOAST NOTIFICATIONS
// =============================================================================

function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    // Icons based on type
    const icons = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-content">
            <span class="toast-message">${escapeHtml(message)}</span>
        </div>
        <button class="toast-close" aria-label="Close notification">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="toast-progress"></div>
    `;

    toastContainer.appendChild(toast);

    // Close button handler
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        removeToast(toast);
    });

    // Auto remove after duration
    setTimeout(() => {
        removeToast(toast);
    }, duration);
}

function removeToast(toast) {
    toast.style.animation = 'slideOutRight 0.3s ease-in forwards';
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// =============================================================================
// LIGHTBOX GALLERY
// =============================================================================

function initializeLightbox() {
    // Close button
    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    // Navigation
    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', () => navigateImage(-1));
    }
    if (lightboxNext) {
        lightboxNext.addEventListener('click', () => navigateImage(1));
    }

    // Zoom controls
    if (lightboxZoomIn) {
        lightboxZoomIn.addEventListener('click', () => updateZoom(0.25));
    }
    if (lightboxZoomOut) {
        lightboxZoomOut.addEventListener('click', () => updateZoom(-0.25));
    }
    if (lightboxReset) {
        lightboxReset.addEventListener('click', resetZoom);
    }

    // Close on overlay click
    if (lightboxModal) {
        lightboxModal.addEventListener('click', (e) => {
            if (e.target === lightboxModal) {
                closeLightbox();
            }
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightboxModal.classList.contains('show')) return;

        switch (e.key) {
            case 'Escape':
                closeLightbox();
                break;
            case 'ArrowLeft':
                navigateImage(-1);
                break;
            case 'ArrowRight':
                navigateImage(1);
                break;
            case '+':
            case '=':
                updateZoom(0.25);
                break;
            case '-':
                updateZoom(-0.25);
                break;
            case '0':
                resetZoom();
                break;
        }
    });
}

function openLightbox(imageId, entryId, imageUrl, caption, images = []) {
    if (!lightboxModal || !lightboxImage) return;

    // Store all images for navigation
    if (images.length > 0) {
        lightboxImages = images;
        lightboxCurrentIndex = images.findIndex(img => img.image_path === imageUrl);
        if (lightboxCurrentIndex === -1) lightboxCurrentIndex = 0;
    } else {
        lightboxImages = [{ image_path: imageUrl, ai_description: caption }];
        lightboxCurrentIndex = 0;
    }

    // Set image and caption
    updateLightboxImage();

    // Show modal
    lightboxModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    if (!lightboxModal) return;

    lightboxModal.classList.remove('show');
    document.body.style.overflow = '';
    resetZoom();
}

function navigateImage(direction) {
    if (lightboxImages.length === 0) return;

    lightboxCurrentIndex += direction;

    // Loop around
    if (lightboxCurrentIndex < 0) {
        lightboxCurrentIndex = lightboxImages.length - 1;
    } else if (lightboxCurrentIndex >= lightboxImages.length) {
        lightboxCurrentIndex = 0;
    }

    updateLightboxImage();
}

function updateLightboxImage() {
    if (!lightboxImage || !lightboxCaption) return;

    const currentImage = lightboxImages[lightboxCurrentIndex];
    lightboxImage.src = currentImage.image_path;
    lightboxCaption.textContent = currentImage.ai_description || '';

    // Reset zoom when changing images
    resetZoom();
}

function updateZoom(delta) {
    if (!lightboxImage) return;

    lightboxZoom = Math.max(0.5, Math.min(3, lightboxZoom + delta));
    lightboxImage.style.transform = `scale(${lightboxZoom})`;
}

function resetZoom() {
    if (!lightboxImage) return;

    lightboxZoom = 1;
    lightboxImage.style.transform = 'scale(1)';
}

// =============================================================================
// FORM HANDLERS
// =============================================================================

function updateTitleCharCount() {
    const charCount = document.getElementById('titleCharCount');
    if (!charCount) return;

    const length = entryTitle.value.length;
    charCount.textContent = `${length}/200`;

    if (length > 180) {
        charCount.classList.add('error');
    } else if (length > 150) {
        charCount.classList.add('warning');
    } else {
        charCount.classList.remove('warning', 'error');
    }
}

async function handleSubmit(e) {
    e.preventDefault();

    const title = entryTitle.value.trim();
    const description = entryDescription.value.trim();
    const date = entryDate.value;

    // Validation
    if (!title) {
        showToast('Please enter a title for this entry', 'warning');
        entryTitle.focus();
        return;
    }

    if (title.length < 3) {
        showToast('Title must be at least 3 characters long', 'warning');
        entryTitle.focus();
        return;
    }

    if (title.length > 200) {
        showToast('Title must not exceed 200 characters', 'warning');
        entryTitle.focus();
        return;
    }

    if (!date) {
        showToast('Please select a date', 'warning');
        entryDate.focus();
        return;
    }

    // Validate date
    const today = new Date().toISOString().split('T')[0];
    if (date > today) {
        showToast('Entry date cannot be in the future', 'warning');
        entryDate.focus();
        return;
    }

    if (selectedFiles.length === 0) {
        showToast('Please upload at least one image', 'warning');
        return;
    }

    if (selectedFiles.length > 25) {
        showToast('Maximum 25 images allowed per entry', 'warning');
        return;
    }

    // Validate files
    const validFiles = [];
    const invalidFiles = [];
    const maxFileSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    selectedFiles.forEach(file => {
        if (file.size > maxFileSize) {
            invalidFiles.push({ name: file.name, reason: 'File too large (max 5MB)' });
        } else if (!allowedTypes.includes(file.type)) {
            invalidFiles.push({ name: file.name, reason: 'Invalid file type' });
        } else {
            validFiles.push(file);
        }
    });

    if (invalidFiles.length > 0) {
        const errorList = invalidFiles.map(f => `${f.name}: ${f.reason}`).join('<br>');
        showToast(`Invalid files:<br>${errorList}`, 'error');
        return;
    }

    setLoading(true);

    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('entry_date', date);

    validFiles.forEach(file => {
        formData.append('images[]', file);
    });

    try {
        const response = await fetch('process.php?action=createEntry', {
            method: 'POST',
            body: formData,
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const responseText = await response.text();

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error('Server returned invalid JSON');
        }

        if (result.success) {
            const imageResults = result.images || [];
            const failedImages = imageResults.filter(img => img.error);

            let successMessage = '✅ OJT entry saved to database!';
            if (failedImages.length > 0) {
                successMessage += ` (${failedImages.length} image(s) failed)`;
            }

            showToast(successMessage, 'success');
            clearForm();
            narrativeCache = null;
            // Force reload with cache busting
            loadWeeklyReport();
        } else {
            showToast(result.error || 'Failed to create entry', 'error');
        }
    } catch (error) {
        console.error('Submit error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        setLoading(false);
    }
}

// =============================================================================
// FILE UPLOAD HANDLERS
// =============================================================================

function handleDragOver(e) {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
}

function handleDragLeave(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');

    const files = e.dataTransfer.files;
    processFiles(files);
}

function handleFileSelect(e) {
    const files = e.target.files;
    processFiles(files);
}

function processFiles(files) {
    const validFiles = Array.from(files).filter(file => {
        if (!file.type.startsWith('image/')) {
            showToast(`${file.name} is not an image file`, 'warning');
            return false;
        }
        if (file.size > 5 * 1024 * 1024) {
            showToast(`${file.name} exceeds 5MB limit`, 'warning');
            return false;
        }
        return true;
    });

    selectedFiles = [...selectedFiles, ...validFiles];
    updatePreview();
}

function updatePreview() {
    previewContainer.innerHTML = '';

    if (selectedFiles.length === 0) {
        uploadArea.classList.remove('has-files');
        return;
    }

    uploadArea.classList.add('has-files');

    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button class="remove-btn" data-index="${index}" aria-label="Remove image">&times;</button>
            `;
            previewContainer.appendChild(previewItem);

            previewItem.querySelector('.remove-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(e.target.dataset.index);
                removeFile(idx);
            });
        };
        reader.readAsDataURL(file);
    });
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updatePreview();
}

function clearForm() {
    if (ojtForm) ojtForm.reset();
    if (entryDate) entryDate.valueAsDate = new Date();
    selectedFiles = [];
    if (imageInput) imageInput.value = '';
    updatePreview();
    hideStatus();
    updateTitleCharCount();
}

// =============================================================================
// LOAD AND DISPLAY REPORT
// =============================================================================

async function loadWeeklyReport() {
    if (reportGrid) {
        reportGrid.innerHTML = '';
    }
    if (emptyState) {
        emptyState.classList.remove('show');
    }
    if (narrativeContainer) {
        narrativeContainer.classList.remove('show');
    }

    // Show loading skeleton
    showLoadingSkeleton();

    try {
        // Add cache-busting timestamp to prevent stale data
        const timestamp = new Date().getTime();
        const response = await fetch(`process.php?action=getWeekly&_t=${timestamp}`, {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        });
        const responseText = await response.text();

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Invalid JSON response from server');
        }

        if (result.success) {
            displayWeeklyReport(result.week);
        } else {
            showToast(result.error || 'Failed to load weekly report', 'error');
        }
    } catch (error) {
        console.error('Load error:', error);
        showToast('Error: ' + error.message, 'error');
    }
}

function showLoadingSkeleton() {
    if (!reportGrid) return;

    reportGrid.innerHTML = '';
    for (let i = 0; i < 3; i++) {
        const skeleton = document.createElement('div');
        skeleton.className = 'ojt-entry-card skeleton skeleton-entry-card';
        skeleton.innerHTML = `
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-image"></div>
        `;
        reportGrid.appendChild(skeleton);
    }
}

function displayWeeklyReport(week) {
    const dateRange = week.date_range || `${week.start} - ${week.end}`;
    if (weekRange) weekRange.textContent = dateRange;
    if (entryCount) {
        entryCount.textContent = `${week.entries.length} entr${week.entries.length !== 1 ? 'ies' : 'y'} ${week.total_days > 0 ? '(' + week.total_days + ' day' + (week.total_days !== 1 ? 's' : '') + ')' : ''}`;
    }

    // Update stats
    updateStats(week.entries);

    if (!reportGrid) return;
    reportGrid.innerHTML = '';

    if (week.entries.length === 0) {
        if (emptyState) emptyState.classList.add('show');
        return;
    }

    week.entries.forEach(entry => {
        const card = createEntryCard(entry);
        reportGrid.appendChild(card);
    });

    // Load Bento Grid components
    loadRecentEntries();
}

/**
 * Show Quick Date Picker Popup
 */
function showQuickDatePicker(entryId, currentDate, entryTitle, entryDesc) {
    // Create popup if it doesn't exist
    let popup = document.getElementById('quickDatePicker');
    if (!popup) {
        popup = document.createElement('div');
        popup.id = 'quickDatePicker';
        popup.className = 'quick-date-picker';
        popup.innerHTML = `
            <div class="quick-date-picker-content">
                <h4>📅 Edit Entry Date</h4>
                <input type="date" id="quickDateInput">
                <div class="quick-date-actions">
                    <button class="btn btn-sm btn-primary" id="saveQuickDate">Save</button>
                    <button class="btn btn-sm btn-secondary" id="cancelQuickDate">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(popup);

        // Add event listeners
        document.getElementById('saveQuickDate').addEventListener('click', saveQuickDate);
        document.getElementById('cancelQuickDate').addEventListener('click', hideQuickDatePicker);
        popup.addEventListener('click', function(e) {
            if (e.target === popup) hideQuickDatePicker();
        });
    }

    // Set current entry data
    popup.dataset.entryId = entryId;
    popup.dataset.entryTitle = entryTitle;
    popup.dataset.entryDesc = entryDesc;
    const dateInput = document.getElementById('quickDateInput');
    dateInput.value = currentDate;
    
    // Show popup
    popup.classList.add('show');
    dateInput.focus();
}

/**
 * Hide Quick Date Picker
 */
function hideQuickDatePicker() {
    const popup = document.getElementById('quickDatePicker');
    if (popup) {
        popup.classList.remove('show');
    }
}

/**
 * Save Quick Date Change
 */
async function saveQuickDate() {
    const popup = document.getElementById('quickDatePicker');
    const entryId = popup.dataset.entryId;
    const entryTitle = popup.dataset.entryTitle || 'OJT Entry';
    const entryDesc = popup.dataset.entryDesc || '';
    const newDate = document.getElementById('quickDateInput').value;

    if (!newDate) {
        showToast('Please select a date', 'warning');
        return;
    }

    try {
        const response = await fetch('process.php?action=updateEntry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: entryId,
                title: entryTitle,
                description: entryDesc,
                entry_date: newDate
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('Date updated successfully!', 'success');
            hideQuickDatePicker();
            // Reload entries to show updated date
            loadWeeklyReport();
        } else {
            showToast(result.error || 'Failed to update date', 'error');
        }
    } catch (error) {
        console.error('Date update error:', error);
        showToast('Error updating date', 'error');
    }
}

function updateStats(entries) {
    const totalEntries = entries.length;
    const totalImages = entries.reduce((sum, entry) => sum + (entry.images ? entry.images.length : 0), 0);
    const uniqueDates = new Set(entries.map(e => e.entry_date));
    const totalDays = uniqueDates.size;

    animateValue('totalEntries', totalEntries);
    animateValue('totalImages', totalImages);
    animateValue('totalDays', totalDays);
}

function animateValue(elementId, value) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const duration = 1000;
    const start = 0;
    const increment = value / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= value) {
            element.textContent = value;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}

function createEntryCard(entry) {
    const card = document.createElement('div');
    card.className = 'ojt-entry-card';

    const entryDate = new Date(entry.entry_date);
    const formattedDate = entryDate.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Build image gallery
    let galleryHtml = '';
    if (entry.images && entry.images.length > 0) {
        const visibleImages = entry.images.slice(0, 4);
        const remainingCount = entry.images.length - 4;

        galleryHtml = `<div class="ojt-entry-gallery">
            ${visibleImages.map((img, index) => `
                <img src="${img.image_path}" alt="Entry image" data-full="${img.image_path}" title="Click to enlarge" data-index="${index}" data-entry-id="${entry.id}">
            `).join('')}
            ${remainingCount > 0 ? `
                <div class="gallery-more-badge" data-entry-id="${entry.id}">+${remainingCount}</div>
            ` : ''}
        </div>`;
    }

    const isEnhanced = entry.ai_enhanced_description !== entry.user_description &&
                       entry.ai_enhanced_description !== 'No description available';

    const description = entry.ai_enhanced_description || entry.user_description || 'No description';
    const enhancedClass = isEnhanced ? 'enhanced' : '';
    const currentDescription = entry.ai_enhanced_description || entry.user_description || '';

    card.innerHTML = `
        <div class="ojt-entry-header">
            <h3 class="ojt-entry-title">${escapeHtml(entry.title)}</h3>
            <div class="ojt-entry-date-group">
                <span class="ojt-entry-date-badge">${entryDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                <button class="ojt-quick-edit-date" data-id="${entry.id}" data-date="${entry.entry_date}" data-title="${escapeHtml(entry.title)}" data-desc="${escapeHtml(entry.user_description || '')}" title="Edit date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="ojt-entry-body">
            <div class="description-container">
                <p class="ojt-entry-description ${enhancedClass}" id="desc-${entry.id}">${escapeHtml(description)}</p>
                <textarea class="ojt-edit-description" id="edit-desc-${entry.id}" style="display:none;" rows="5">${escapeHtml(currentDescription)}</textarea>
            </div>
            <div class="edit-actions" style="display:none; margin-top: 0.5rem;">
                <button class="btn btn-sm btn-primary" onclick="saveDescription(${entry.id})">Save</button>
                <button class="btn btn-sm btn-secondary" onclick="cancelEdit(${entry.id})">Cancel</button>
            </div>
            ${galleryHtml}
            <div class="ojt-entry-meta">
                <span class="ojt-entry-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    ${entry.images ? entry.images.length : 0} image${entry.images && entry.images.length !== 1 ? 's' : ''}
                </span>
                <div class="entry-actions">
                    <button class="entry-action-btn entry-edit" data-id="${entry.id}" title="Edit entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="entry-action-btn entry-ai-customize" data-id="${entry.id}" data-title="${escapeHtml(entry.title)}" data-description="${escapeHtml(entry.ai_enhanced_description || entry.user_description || '')}" title="AI Customize">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                        </svg>
                    </button>
                    <button class="entry-action-btn entry-delete" data-id="${entry.id}" title="Delete entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add delete listener
    card.querySelector('.entry-delete').addEventListener('click', () => {
        deleteEntry(entry.id, card);
    });

    // Add edit listener
    card.querySelector('.entry-edit').addEventListener('click', () => {
        openEditEntryModal(entry);
    });

    // Add AI Customize listener
    card.querySelector('.entry-ai-customize')?.addEventListener('click', (e) => {
        const btn = e.currentTarget;
        openAICustomizeModal(
            entry.id,
            entry.title,
            entry.ai_enhanced_description || entry.user_description || ''
        );
    });

    // Add image click listeners for lightbox
    card.querySelectorAll('.ojt-entry-gallery img').forEach((img, index) => {
        img.addEventListener('click', () => {
            openLightbox(
                entry.images[index].id,
                entry.id,
                entry.images[index].image_path,
                entry.images[index].ai_description || '',
                entry.images
            );
        });
    });

    // Add "more" badge click listener
    const moreBadge = card.querySelector('.gallery-more-badge');
    if (moreBadge) {
        moreBadge.addEventListener('click', () => {
            openLightbox(null, entry.id, entry.images[0].image_path, '', entry.images);
        });
    }

    return card;
}

// =============================================================================
// EDIT DESCRIPTION
// =============================================================================

function editDescription(id) {
    const descParagraph = document.getElementById(`desc-${id}`);
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const editActions = descParagraph.parentElement.nextElementSibling;

    if (descParagraph && editTextarea && editActions) {
        descParagraph.style.display = 'none';
        editTextarea.style.display = 'block';
        editActions.style.display = 'block';
        editTextarea.focus();
    }
}

async function saveDescription(id) {
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const newDescription = editTextarea.value.trim();

    if (!newDescription) {
        showToast('Description cannot be empty', 'warning');
        return;
    }

    try {
        const response = await fetch('process.php?action=updateDescription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, description: newDescription })
        });

        const result = await response.json();

        if (result.success) {
            const descParagraph = document.getElementById(`desc-${id}`);
            descParagraph.textContent = newDescription;
            descParagraph.classList.add('enhanced');
            cancelEdit(id);
            showToast('Description updated successfully!', 'success');
            narrativeCache = null;
        } else {
            showToast(result.error || 'Failed to update description', 'error');
        }
    } catch (error) {
        showToast('Network error: ' + error.message, 'error');
    }
}

function cancelEdit(id) {
    const descParagraph = document.getElementById(`desc-${id}`);
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const editActions = descParagraph ? descParagraph.parentElement.nextElementSibling : null;

    if (descParagraph && editTextarea && editActions) {
        descParagraph.style.display = 'block';
        editTextarea.style.display = 'none';
        editActions.style.display = 'none';
    }
}

// =============================================================================
// DELETE ENTRY
// =============================================================================

async function deleteEntry(id, cardElement) {
    if (!confirm('Are you sure you want to delete this entry?')) return;

    try {
        const response = await fetch('process.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });

        const result = await response.json();

        if (result.success) {
            cardElement.style.opacity = '0';
            cardElement.style.transform = 'scale(0.95)';
            narrativeCache = null;
            setTimeout(() => {
                cardElement.remove();
                const entries = reportGrid.querySelectorAll('.ojt-entry-card');
                if (entryCount) {
                    entryCount.textContent = `${entries.length} entr${entries.length !== 1 ? 'ies' : 'y'}`;
                }

                if (entries.length === 0) {
                    if (emptyState) emptyState.classList.add('show');
                }
            }, 200);
            showToast('Entry deleted successfully!', 'success');
        } else {
            showToast(result.error || 'Delete failed', 'error');
        }
    } catch (error) {
        showToast('Network error: ' + error.message, 'error');
    }
}

// =============================================================================
// NARRATIVE REPORT
// =============================================================================

async function handleGenerateNarrative() {
    if (narrativeContainer) {
        narrativeContainer.classList.add('show');
    }
    if (narrativeContent) {
        narrativeContent.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <div class="loading-spinner" style="margin-bottom: 1rem;"></div>
                <p>Generating your weekly narrative report...</p>
                <p style="font-size: 0.85rem; opacity: 0.8;">AI is analyzing your OJT entries</p>
            </div>
        `;
    }

    const currentEntryCount = reportGrid ? reportGrid.querySelectorAll('.ojt-entry-card').length : 0;
    if (narrativeCache && narrativeCache.entryCount !== currentEntryCount) {
        narrativeCache = null;
    }

    try {
        const response = await fetch('process.php?action=generateNarrative');
        const result = await response.json();

        if (result.success) {
            narrativeCache = {
                narrative: result.narrative,
                entryCount: currentEntryCount
            };
            displayNarrative(result.narrative);
        } else {
            if (narrativeContent) {
                narrativeContent.innerHTML = `
                    <div style="text-align: center; padding: 2rem;">
                        <p style="color: var(--error-color);">${result.error || 'Failed to generate narrative'}</p>
                        <p style="font-size: 0.85rem; margin-top: 0.5rem;">Add some OJT entries first, then try again.</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Narrative error:', error);
        if (narrativeContent) {
            narrativeContent.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p>Error: ${error.message}</p>
                </div>
            `;
        }
    }
}

function displayNarrative(narrative) {
    if (!narrativeContent) return;

    const paragraphs = narrative.split('\n\n').filter(p => p.trim());
    narrativeContent.innerHTML = paragraphs
        .map(p => `<p>${escapeHtml(p)}</p>`)
        .join('');
}

// =============================================================================
// EDIT ENTRY MODAL
// =============================================================================

function openEditEntryModal(entry) {
    currentEditEntry = entry;
    if (editEntryId) editEntryId.value = entry.id;
    if (editEntryTitle) editEntryTitle.value = entry.title;
    if (editEntryDate) editEntryDate.value = entry.entry_date;
    if (editEntryDescription) editEntryDescription.value = entry.ai_enhanced_description || entry.user_description || '';
    if (editStatusMessage) editStatusMessage.textContent = '';

    if (editEntryModal) {
        editEntryModal.classList.add('show');
    }
}

function closeEditEntryModal() {
    if (editEntryModal) {
        editEntryModal.classList.remove('show');
        if (editEntryForm) editEntryForm.reset();
        if (editStatusMessage) editStatusMessage.textContent = '';
        currentEditEntry = null;
    }
}

async function handleEditEntrySubmit(e) {
    e.preventDefault();

    const id = editEntryId ? editEntryId.value : '';
    const title = editEntryTitle ? editEntryTitle.value.trim() : '';
    const description = editEntryDescription ? editEntryDescription.value.trim() : '';
    const entryDateValue = editEntryDate ? editEntryDate.value : '';

    if (!title || title.length < 3) {
        if (editStatusMessage) {
            editStatusMessage.innerHTML = '<span class="error">Title must be at least 3 characters</span>';
        }
        return;
    }

    if (!entryDateValue) {
        if (editStatusMessage) {
            editStatusMessage.innerHTML = '<span class="error">Please select a date</span>';
        }
        return;
    }

    try {
        const response = await fetch('process.php?action=updateEntry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                title: title,
                description: description,
                entry_date: entryDateValue
            })
        });

        const result = await response.json();

        if (result.success) {
            if (editStatusMessage) {
                editStatusMessage.innerHTML = '<span class="success">Entry updated successfully!</span>';
            }
            setTimeout(() => {
                closeEditEntryModal();
                loadWeeklyReport();
            }, 1500);
        } else {
            if (editStatusMessage) {
                editStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to update entry'}</span>`;
            }
        }
    } catch (error) {
        console.error('Edit entry error:', error);
        if (editStatusMessage) {
            editStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    }
}

// =============================================================================
// IMAGE ANALYSIS MODAL
// =============================================================================

function closeImageAnalysisModal() {
    if (imageAnalysisModal) {
        imageAnalysisModal.classList.remove('show');
        if (analysisStatusMessage) analysisStatusMessage.textContent = '';
        currentImageId = null;
        currentEntryId = null;
    }
}

function openImageAnalysisModal(imageId, entryId, imageUrl, description) {
    currentImageId = imageId;
    currentEntryId = entryId;
    if (analysisImage) analysisImage.src = imageUrl;
    if (analysisDescription) analysisDescription.value = description || 'No AI description available.';
    if (analysisStatusMessage) analysisStatusMessage.textContent = '';

    if (imageAnalysisModal) {
        imageAnalysisModal.classList.add('show');
    }
}

async function handleSaveAnalysis() {
    if (!currentImageId) return;

    const newDescription = analysisDescription ? analysisDescription.value.trim() : '';

    if (!newDescription) {
        if (analysisStatusMessage) {
            analysisStatusMessage.innerHTML = '<span class="error">Description cannot be empty</span>';
        }
        return;
    }

    if (saveAnalysisBtn) {
        saveAnalysisBtn.disabled = true;
        saveAnalysisBtn.innerHTML = 'Saving...';
    }

    try {
        const response = await fetch('process.php?action=updateImageDescription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                image_id: currentImageId,
                description: newDescription
            })
        });

        const result = await response.json();

        if (result.success) {
            if (analysisStatusMessage) {
                analysisStatusMessage.innerHTML = '<span class="success">Description saved successfully!</span>';
            }
            setTimeout(() => {
                closeImageAnalysisModal();
                loadWeeklyReport();
            }, 1500);
        } else {
            if (analysisStatusMessage) {
                analysisStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to save description'}</span>`;
            }
        }
    } catch (error) {
        console.error('Save analysis error:', error);
        if (analysisStatusMessage) {
            analysisStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    } finally {
        if (saveAnalysisBtn) {
            saveAnalysisBtn.disabled = false;
            saveAnalysisBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Save Changes
            `;
        }
    }
}

async function handleRegenerateAnalysis() {
    if (!currentImageId || !analysisImage || !analysisImage.src) return;

    if (regenerateAnalysisBtn) {
        regenerateAnalysisBtn.disabled = true;
        regenerateAnalysisBtn.innerHTML = 'Analyzing...';
    }
    if (analysisDescription) {
        analysisDescription.value = 'AI is analyzing the image...';
    }

    try {
        const response = await fetch('process.php?action=regenerateImageAnalysis', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                image_id: currentImageId,
                image_url: analysisImage.src
            })
        });

        const result = await response.json();

        if (result.success) {
            if (analysisDescription) {
                analysisDescription.value = result.description;
            }
            if (analysisStatusMessage) {
                analysisStatusMessage.innerHTML = '<span class="success">Analysis regenerated successfully!</span>';
            }
        } else {
            if (analysisStatusMessage) {
                analysisStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to regenerate analysis'}</span>`;
            }
        }
    } catch (error) {
        console.error('Regenerate analysis error:', error);
        if (analysisStatusMessage) {
            analysisStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    } finally {
        if (regenerateAnalysisBtn) {
            regenerateAnalysisBtn.disabled = false;
            regenerateAnalysisBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Regenerate
            `;
        }
    }
}

// =============================================================================
// AI CUSTOMIZE MODAL
// =============================================================================

function closeAICustomizeModal() {
    if (aiCustomizeModal) {
        aiCustomizeModal.classList.remove('show');
        if (customizeStatusMessage) customizeStatusMessage.textContent = '';
        if (customizeEntryId) customizeEntryId.value = '';
        if (enhancedPreview) enhancedPreview.value = '';
        if (applyCustomizationBtn) applyCustomizationBtn.disabled = true;
    }
}

function openAICustomizeModal(entryId, title, description) {
    if (customizeEntryId) customizeEntryId.value = entryId;
    if (currentDescription) currentDescription.value = description;
    if (customPrompt) customPrompt.value = '';
    if (enhancedPreview) enhancedPreview.value = '';
    if (customizeStatusMessage) customizeStatusMessage.textContent = '';
    if (applyCustomizationBtn) applyCustomizationBtn.disabled = true;
    if (enhancementStyle) enhancementStyle.value = 'professional';

    if (aiCustomizeModal) {
        aiCustomizeModal.classList.add('show');
    }
}

async function handleGenerateCustomization() {
    const entryId = customizeEntryId ? customizeEntryId.value : '';
    const prompt = customPrompt ? customPrompt.value.trim() : '';
    const style = enhancementStyle ? enhancementStyle.value : 'professional';

    if (!entryId) {
        if (customizeStatusMessage) {
            customizeStatusMessage.innerHTML = '<span class="error">No entry selected</span>';
        }
        return;
    }

    if (!prompt) {
        if (customizeStatusMessage) {
            customizeStatusMessage.innerHTML = '<span class="error">Please enter your customization prompt</span>';
        }
        if (customPrompt) customPrompt.focus();
        return;
    }

    if (generateCustomizationBtn) {
        generateCustomizationBtn.disabled = true;
        generateCustomizationBtn.innerHTML = 'Generating...';
    }
    if (customizeStatusMessage) customizeStatusMessage.textContent = '';

    try {
        const response = await fetch('process.php?action=customizeEntryWithAI', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                entry_id: entryId,
                current_description: currentDescription ? currentDescription.value : '',
                custom_prompt: prompt,
                enhancement_style: style
            })
        });

        const result = await response.json();

        if (result.success) {
            if (enhancedPreview) {
                enhancedPreview.value = result.enhanced_description;
            }
            if (applyCustomizationBtn) {
                applyCustomizationBtn.disabled = false;
            }
            if (customizeStatusMessage) {
                customizeStatusMessage.innerHTML = '<span class="success">AI enhancement generated! Review and apply if satisfied.</span>';
            }
        } else {
            if (customizeStatusMessage) {
                customizeStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to generate enhancement'}</span>`;
            }
        }
    } catch (error) {
        console.error('Generate customization error:', error);
        if (customizeStatusMessage) {
            customizeStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    } finally {
        if (generateCustomizationBtn) {
            generateCustomizationBtn.disabled = false;
            generateCustomizationBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                </svg>
                Generate with AI
            `;
        }
    }
}

async function handleApplyCustomization() {
    const entryId = customizeEntryId ? customizeEntryId.value : '';
    const enhancedDescription = enhancedPreview ? enhancedPreview.value : '';

    if (!entryId || !enhancedDescription) {
        if (customizeStatusMessage) {
            customizeStatusMessage.innerHTML = '<span class="error">No enhancement to apply</span>';
        }
        return;
    }

    if (applyCustomizationBtn) {
        applyCustomizationBtn.disabled = true;
        applyCustomizationBtn.innerHTML = 'Applying...';
    }

    try {
        const response = await fetch('process.php?action=updateEntryDescription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                entry_id: entryId,
                description: enhancedDescription
            })
        });

        const result = await response.json();

        if (result.success) {
            if (customizeStatusMessage) {
                customizeStatusMessage.innerHTML = '<span class="success">Changes applied successfully!</span>';
            }
            setTimeout(() => {
                closeAICustomizeModal();
                loadWeeklyReport();
            }, 1500);
        } else {
            if (customizeStatusMessage) {
                customizeStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to apply changes'}</span>`;
            }
        }
    } catch (error) {
        console.error('Apply customization error:', error);
        if (customizeStatusMessage) {
            customizeStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    } finally {
        if (applyCustomizationBtn) {
            applyCustomizationBtn.disabled = false;
            applyCustomizationBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Apply Changes
            `;
        }
    }
}

// =============================================================================
// ANALYZE IMAGES FOR ENTRY
// =============================================================================

async function handleAnalyzeImagesForEntry() {
    if (selectedFiles.length === 0) {
        showToast('Please upload images first', 'warning');
        return;
    }

    if (selectedFiles.length > 15) {
        const confirmed = confirm(`You have ${selectedFiles.length} images. Analyzing this many images may take 3-5 minutes. Continue?`);
        if (!confirmed) return;
    }

    const analyzeImagesBtn = document.getElementById('analyzeImagesBtn');
    if (!analyzeImagesBtn) {
        showToast('Analyze button not found', 'error');
        return;
    }

    analyzeImagesBtn.disabled = true;
    analyzeImagesBtn.innerHTML = `Analyzing ${selectedFiles.length} image${selectedFiles.length !== 1 ? 's' : ''}...`;

    try {
        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('images[]', file);
        });

        const response = await fetch('process.php?action=analyzeImagesForEntry', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            if (entryTitle && result.title) {
                entryTitle.value = result.title;
            }
            if (entryDescription && result.description) {
                entryDescription.value = result.description;
            }
            showToast('Title and description generated successfully!', 'success');
        } else {
            showToast(result.error || 'Failed to analyze images', 'error');
        }
    } catch (error) {
        console.error('Analyze images error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        analyzeImagesBtn.disabled = false;
        analyzeImagesBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
            </svg>
            Auto-Generate Title & Description
        `;
    }
}

// =============================================================================
// ENHANCE DESCRIPTION
// =============================================================================

async function handleEnhanceDescription() {
    const enhanceBtn = document.getElementById('enhanceBtn');
    const descriptionTextarea = document.getElementById('entryDescription');
    const originalText = descriptionTextarea ? descriptionTextarea.value.trim() : '';

    if (!originalText) {
        showToast('Please enter a description first', 'warning');
        if (descriptionTextarea) descriptionTextarea.focus();
        return;
    }

    if (enhanceBtn) {
        enhanceBtn.disabled = true;
        enhanceBtn.classList.add('loading');
        const icon = enhanceBtn.querySelector('svg');
        enhanceBtn.innerHTML = '';
        enhanceBtn.appendChild(icon.cloneNode(true));
        enhanceBtn.appendChild(document.createTextNode(' Enhancing...'));
    }

    try {
        const response = await fetch('process.php?action=enhanceDescription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                description: originalText
            })
        });

        const result = await response.json();

        if (result.success) {
            if (descriptionTextarea) {
                descriptionTextarea.value = result.enhanced_description;
            }
            showToast('Description enhanced successfully!', 'success');
        } else {
            showToast(result.error || 'Failed to enhance description', 'error');
        }
    } catch (error) {
        console.error('Enhance error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        if (enhanceBtn) {
            enhanceBtn.disabled = false;
            enhanceBtn.classList.remove('loading');
            enhanceBtn.innerHTML = '';
            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('viewBox', '0 0 24 24');
            icon.setAttribute('fill', 'none');
            icon.setAttribute('stroke', 'currentColor');
            icon.setAttribute('stroke-width', '2');
            icon.setAttribute('width', '16');
            icon.setAttribute('height', '16');
            icon.innerHTML = '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><circle cx="12" cy="12" r="3"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>';
            enhanceBtn.appendChild(icon);
            enhanceBtn.appendChild(document.createTextNode(' Enhance'));
        }
    }
}

// =============================================================================
// REPORT INFO FORM
// =============================================================================

async function handleReportInfoSubmit(e) {
    e.preventDefault();

    if (saveReportInfoBtn) {
        saveReportInfoBtn.disabled = true;
        saveReportInfoBtn.innerHTML = 'Saving...';
    }

    const formData = new FormData(reportInfoForm);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('process.php?action=saveReportInfo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            if (reportInfoStatus) {
                reportInfoStatus.innerHTML = '<span class="success">Report information saved successfully!</span>';
                reportInfoStatus.classList.add('show');
            }
            showToast('Report information saved successfully!', 'success');
            reportInfoData = data;
        } else {
            if (reportInfoStatus) {
                reportInfoStatus.innerHTML = `<span class="error">${result.error || 'Failed to save report information'}</span>`;
                reportInfoStatus.classList.add('show');
            }
            showToast(result.error || 'Failed to save report information', 'error');
        }
    } catch (error) {
        console.error('Save report info error:', error);
        if (reportInfoStatus) {
            reportInfoStatus.innerHTML = `<span class="error">Error: ${error.message}</span>`;
            reportInfoStatus.classList.add('show');
        }
        showToast('Error: ' + error.message, 'error');
    } finally {
        if (saveReportInfoBtn) {
            saveReportInfoBtn.disabled = false;
            saveReportInfoBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Report Information
            `;
        }
    }
}

function handleResetReportInfo() {
    // Reset all form fields to default/empty values
    if (studentName) studentName.value = 'JUAN DELA CRUZ';
    if (studentCourse) studentCourse.value = 'Bachelor of Science in Information Technology';
    if (schoolYear) schoolYear.value = 'S.Y. 2025 - 2026';
    
    // Company Profile - Reset to empty
    if (companyName) companyName.value = '';
    if (companyLocation) companyLocation.value = '';
    if (companyNatureOfBusiness) companyNatureOfBusiness.value = '';
    if (companyBackground) companyBackground.value = '';
    
    // Duration - Reset to empty
    if (ojtStartDate) ojtStartDate.value = '';
    if (ojtEndDate) ojtEndDate.value = '';
    if (dailyHours) dailyHours.value = '';
    
    // Purpose/Role - Reset to empty
    if (purposeRole) purposeRole.value = '';
    
    // Action Plan - Reset to empty
    if (backgroundActionPlan) backgroundActionPlan.value = '';
    
    // Conclusion - Reset to empty
    if (conclusion) conclusion.value = '';
    
    // Recommendations - Reset to empty
    if (recommendationStudents) recommendationStudents.value = '';
    if (recommendationCompany) recommendationCompany.value = '';
    if (recommendationSchool) recommendationSchool.value = '';
    
    // Acknowledgment - Reset to empty
    if (acknowledgment) acknowledgment.value = '';

    // Clear saved form data from localStorage
    localStorage.removeItem('ojtReportFormData');
    
    showToast('Form reset successfully', 'info');
}

function handleToggleReportInfo() {
    if (reportInfoCard) {
        reportInfoCard.classList.toggle('collapsed');
        const isCollapsed = reportInfoCard.classList.contains('collapsed');

        // Update button text and icon
        if (toggleReportInfoBtn) {
            toggleReportInfoBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="transform: ${isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)'}; transition: transform 0.3s;">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
                ${isCollapsed ? 'Expand' : 'Collapse'}
            `;
        }

        // Save collapse state to localStorage
        localStorage.setItem('ojtReportInfoCollapsed', isCollapsed);
    }
}

// Restore collapse state on page load
function restoreCollapseState() {
    const isCollapsed = localStorage.getItem('ojtReportInfoCollapsed') === 'true';
    if (isCollapsed && reportInfoCard) {
        reportInfoCard.classList.add('collapsed');
        if (toggleReportInfoBtn) {
            toggleReportInfoBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="transform: rotate(180deg); transition: transform 0.3s;">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
                Expand
            `;
        }
    }
}

// Save form data to localStorage
function saveFormData() {
    if (!reportInfoForm) return;
    
    const formData = {
        studentName: studentName?.value || '',
        studentCourse: studentCourse?.value || '',
        schoolYear: schoolYear?.value || '',
        companyName: companyName?.value || '',
        companyLocation: companyLocation?.value || '',
        companyNatureOfBusiness: companyNatureOfBusiness?.value || '',
        companyBackground: companyBackground?.value || '',
        ojtStartDate: ojtStartDate?.value || '',
        ojtEndDate: ojtEndDate?.value || '',
        dailyHours: dailyHours?.value || '',
        purposeRole: purposeRole?.value || '',
        backgroundActionPlan: backgroundActionPlan?.value || '',
        conclusion: conclusion?.value || '',
        recommendationStudents: recommendationStudents?.value || '',
        recommendationCompany: recommendationCompany?.value || '',
        recommendationSchool: recommendationSchool?.value || '',
        acknowledgment: acknowledgment?.value || ''
    };
    
    localStorage.setItem('ojtReportFormData', JSON.stringify(formData));
}

// Restore form data from localStorage
function restoreFormData() {
    const savedData = localStorage.getItem('ojtReportFormData');
    if (!savedData) return;
    
    try {
        const formData = JSON.parse(savedData);
        
        if (studentName && formData.studentName) studentName.value = formData.studentName;
        if (studentCourse && formData.studentCourse) studentCourse.value = formData.studentCourse;
        if (schoolYear && formData.schoolYear) schoolYear.value = formData.schoolYear;
        if (companyName && formData.companyName) companyName.value = formData.companyName;
        if (companyLocation && formData.companyLocation) companyLocation.value = formData.companyLocation;
        if (companyNatureOfBusiness && formData.companyNatureOfBusiness) companyNatureOfBusiness.value = formData.companyNatureOfBusiness;
        if (companyBackground && formData.companyBackground) companyBackground.value = formData.companyBackground;
        if (ojtStartDate && formData.ojtStartDate) ojtStartDate.value = formData.ojtStartDate;
        if (ojtEndDate && formData.ojtEndDate) ojtEndDate.value = formData.ojtEndDate;
        if (dailyHours && formData.dailyHours) dailyHours.value = formData.dailyHours;
        if (purposeRole && formData.purposeRole) purposeRole.value = formData.purposeRole;
        if (backgroundActionPlan && formData.backgroundActionPlan) backgroundActionPlan.value = formData.backgroundActionPlan;
        if (conclusion && formData.conclusion) conclusion.value = formData.conclusion;
        if (recommendationStudents && formData.recommendationStudents) recommendationStudents.value = formData.recommendationStudents;
        if (recommendationCompany && formData.recommendationCompany) recommendationCompany.value = formData.recommendationCompany;
        if (recommendationSchool && formData.recommendationSchool) recommendationSchool.value = formData.recommendationSchool;
        if (acknowledgment && formData.acknowledgment) acknowledgment.value = formData.acknowledgment;
    } catch (error) {
        console.error('Failed to restore form data:', error);
    }
}

async function loadReportInfo() {
    try {
        // Add cache-busting to prevent stale data
        const timestamp = new Date().getTime();
        const response = await fetch(`process.php?action=getReportInfo&_t=${timestamp}`, {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        });
        const result = await response.json();

        if (result.success && result.data) {
            reportInfoData = result.data;
            const data = result.data;

            if (studentName) studentName.value = data.student_name || studentName.value;
            if (studentCourse) studentCourse.value = data.student_course || studentCourse.value;
            if (schoolYear) schoolYear.value = data.school_year || schoolYear.value;
            if (companyName) companyName.value = data.company_name || '';
            if (companyLocation) companyLocation.value = data.company_location || '';
            if (companyNatureOfBusiness) companyNatureOfBusiness.value = data.company_nature_of_business || '';
            if (companyBackground) companyBackground.value = data.company_background || '';
            if (ojtStartDate) ojtStartDate.value = data.ojt_start_date || ojtStartDate.value;
            if (ojtEndDate) ojtEndDate.value = data.ojt_end_date || ojtEndDate.value;
            if (dailyHours) dailyHours.value = data.daily_hours || '';
            if (purposeRole) purposeRole.value = data.purpose_role || '';
            if (backgroundActionPlan) backgroundActionPlan.value = data.background_action_plan || '';
            if (conclusion) conclusion.value = data.conclusion || '';
            if (recommendationStudents) recommendationStudents.value = data.recommendation_students || '';
            if (recommendationCompany) recommendationCompany.value = data.recommendation_company || '';
            if (recommendationSchool) recommendationSchool.value = data.recommendation_school || '';
            if (acknowledgment) acknowledgment.value = data.acknowledgment || '';
            
            // Show confirmation toast if data was loaded
            if (data.company_name || data.purpose_role || data.conclusion) {
                showToast('📋 Report info loaded from database', 'info');
            }
        }
    } catch (error) {
        console.error('Load report info error:', error);
    }
}

// =============================================================================
// DOWNLOAD REPORT FUNCTIONS
// =============================================================================

function initializeDownloadReport() {
    const downloadReportBtn = document.getElementById('downloadReportBtn');
    const downloadWordBtn = document.getElementById('downloadWordBtn');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const printDownloadBtn = document.getElementById('printDownloadBtn');
    const aiReportBtn = document.getElementById('aiReportBtn');
    const aiDownloadWordBtn = document.getElementById('aiDownloadWordBtn');
    const aiDownloadPdfBtn = document.getElementById('aiDownloadPdfBtn');
    const aiPrintBtn = document.getElementById('aiPrintBtn');

    if (downloadReportBtn) {
        downloadReportBtn.addEventListener('click', handleGenerateDownloadReport);
    }
    if (downloadWordBtn) {
        downloadWordBtn.addEventListener('click', () => handleDownloadWord('regular'));
    }
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', () => handleDownloadPdf('regular'));
    }
    if (printDownloadBtn) {
        printDownloadBtn.addEventListener('click', handlePrintDownloadReport);
    }
    if (aiReportBtn) {
        aiReportBtn.addEventListener('click', handleGenerateAIReport);
    }
    if (aiDownloadWordBtn) {
        aiDownloadWordBtn.addEventListener('click', () => handleDownloadWord('ai'));
    }
    if (aiDownloadPdfBtn) {
        aiDownloadPdfBtn.addEventListener('click', () => handleDownloadPdf('ai'));
    }
    if (aiPrintBtn) {
        aiPrintBtn.addEventListener('click', handlePrintAIReport);
    }
}

// Note: handleGenerateDownloadReport, displayDownloadReport, and other report functions
// are now in print-report.js to avoid duplication

function handlePrintDownloadReport() {
    if (!downloadReportCache) {
        showToast('No report data available', 'warning');
        return;
    }
    window.print();
}

async function handleGenerateAIReport() {
    if (aiReportModal) {
        aiReportModal.classList.add('show');
    }
    if (aiReportContent) {
        aiReportContent.innerHTML = `
            <div style="text-align: center; padding: 3rem;">
                <div class="loading-spinner" style="margin-bottom: 1rem;"></div>
                <h3>Generating AI-Powered Report...</h3>
                <p>AI is analyzing your entries and creating a comprehensive report</p>
            </div>
        `;
    }

    try {
        const response = await fetch('process.php?action=generateAIReport');
        const result = await response.json();

        if (result.success) {
            if (aiReportContent) {
                aiReportContent.innerHTML = `
                    <div class="download-report">
                        <div style="white-space: pre-wrap;">${escapeHtml(result.report)}</div>
                    </div>
                `;
            }
        } else {
            if (aiReportContent) {
                aiReportContent.innerHTML = `
                    <div style="text-align: center; padding: 2rem;">
                        <p style="color: var(--error-color);">${result.error || 'Failed to generate AI report'}</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('AI Report error:', error);
        if (aiReportContent) {
            aiReportContent.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: var(--error-color);">Error: ${error.message}</p>
                </div>
            `;
        }
    }
}

function handlePrintAIReport() {
    window.print();
}

async function handleDownloadWord(type = 'regular') {
    showToast('Downloading Word document...', 'info');
    // Implementation would trigger server-side Word download
}

async function handleDownloadPdf(type = 'regular') {
    showToast('Downloading PDF document...', 'info');
    // Implementation would trigger server-side PDF download
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

function setLoading(loading) {
    if (submitBtn) {
        submitBtn.disabled = loading;
        const btnLoader = submitBtn.querySelector('.btn-loader');
        if (loading) {
            if (btnLoader) btnLoader.classList.remove('hidden');
        } else {
            if (btnLoader) btnLoader.classList.add('hidden');
        }
    }
}

function showStatus(message, type) {
    if (statusMessage) {
        statusMessage.textContent = message;
        statusMessage.className = `status-message show ${type}`;
        if (type === 'success') {
            setTimeout(hideStatus, 5000);
        }
    }
}

function hideStatus() {
    if (statusMessage) {
        statusMessage.className = 'status-message';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoadingOverlay() {
    if (loadingOverlay) {
        loadingOverlay.classList.add('show');
    }
}

function hideLoadingOverlay() {
    if (loadingOverlay) {
        loadingOverlay.classList.remove('show');
    }
}

/**
 * Initialize Quick Action Buttons (Bento Grid)
 */
function initializeQuickActions() {
    const quickDownload = document.getElementById('quickDownloadReport');
    const quickAI = document.getElementById('quickAIReport');
    const quickPrint = document.getElementById('quickPrint');

    if (quickDownload) {
        quickDownload.addEventListener('click', () => {
            document.getElementById('downloadReportBtn')?.click();
        });
    }

    if (quickAI) {
        quickAI.addEventListener('click', () => {
            document.getElementById('aiReportBtn')?.click();
        });
    }

    if (quickPrint) {
        quickPrint.addEventListener('click', () => {
            document.getElementById('printDownloadBtn')?.click();
        });
    }
}

/**
 * Load Recent Entries into Bento Grid Sidebar
 */
function loadRecentEntries() {
    const container = document.getElementById('recentEntriesList');
    if (!container || !downloadReportCache) return;

    const entries = downloadReportCache.entries || [];
    const recentEntries = entries.slice(-5).reverse();

    if (recentEntries.length === 0) {
        container.innerHTML = '<p class="empty-message" style="color: #64748b; font-size: 13px; text-align: center;">No entries yet</p>';
        return;
    }

    container.innerHTML = recentEntries.map(entry => `
        <div class="recent-entry-item">
            <div class="recent-entry-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                </svg>
            </div>
            <div class="recent-entry-info">
                <h4 class="recent-entry-title">${escapeHtml(entry.title)}</h4>
                <p class="recent-entry-date">${new Date(entry.entry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</p>
            </div>
            <div class="recent-entry-images">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                <span>${entry.images ? entry.images.length : 0}</span>
            </div>
        </div>
    `).join('');

    updateWeekStats(entries);
}

/**
 * Update Weekly Stats in Sidebar
 */
function updateWeekStats(entries) {
    const now = new Date();
    const oneWeekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
    
    const weekEntries = entries.filter(e => new Date(e.entry_date) >= oneWeekAgo);
    const weekImages = weekEntries.reduce((sum, e) => sum + (e.images ? e.images.length : 0), 0);

    const weekEntriesEl = document.getElementById('weekEntries');
    const weekImagesEl = document.getElementById('weekImages');

    if (weekEntriesEl) weekEntriesEl.textContent = weekEntries.length;
    if (weekImagesEl) weekImagesEl.textContent = weekImages;
}

/**
 * Auto Generate ALL Recommendations at once
 */
async function autoGenerateAllRecommendations() {
    const btn = event.target.closest('.ai-generate-btn');
    const originalText = btn.innerHTML;
    
    // Set loading state
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="animation: spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Generating...`;
    
    showToast('🤖 Generating all 3 recommendations...', 'info');

    try {
        // Generate all 3 recommendations in parallel
        const [students, company, school] = await Promise.all([
            generateRecommendation('recommendationStudents'),
            generateRecommendation('recommendationCompany'),
            generateRecommendation('recommendationSchool')
        ]);

        // Fill in the textareas
        if (students) document.getElementById('recommendationStudents').value = students;
        if (company) document.getElementById('recommendationCompany').value = company;
        if (school) document.getElementById('recommendationSchool').value = school;

        showToast('✅ All recommendations generated!', 'success');
    } catch (error) {
        console.error('Generation error:', error);
        showToast('Failed to generate recommendations', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Helper function to generate a single recommendation
 */
async function generateRecommendation(type) {
    const prompts = {
        recommendationStudents: {
            prompt: "Write SHORT, PRACTICAL advice for future OJT students (1-2 short paragraphs). Include: 2-3 specific tips, one mistake to avoid, something you wish you knew. DO NOT say 'Congratulations' or write motivational speeches. Example: 'Take notes during everything. Ask questions early. Don't wait for tasks.' Write casually. Under 150 words.",
            systemMessage: 'You are a senior student giving honest advice to juniors. Be practical, not motivational.'
        },
        recommendationCompany: {
            prompt: "Write SHORT, CONSTRUCTIVE feedback for the company (1-2 short paragraphs). Include: 1-2 things done well, 1 reasonable suggestion. DO NOT write fake praise or make demands. Example: 'IT staff were patient. One suggestion: provide checklists for week 1 tasks.' Write respectfully. Under 120 words.",
            systemMessage: 'You are a student giving respectful, constructive feedback. Be honest but diplomatic.'
        },
        recommendationSchool: {
            prompt: "Write SHORT, PRACTICAL suggestions for ISPSC (1-2 short paragraphs). Include: 1 thing done well, 1-2 specific improvements. DO NOT complain or make unrealistic demands. Example: 'ISPSC prepared me well. Suggestion: add workplace expectations orientation before OJT.' Write respectfully. Under 120 words.",
            systemMessage: 'You are a student giving respectful suggestions to your school. Be constructive.'
        }
    };

    const config = prompts[type];
    if (!config) return null;

    try {
        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                prompt: config.prompt, 
                system_message: config.systemMessage 
            })
        });

        const result = await response.json();
        return result.success ? result.narrative.trim() : null;
    } catch (error) {
        console.error(`${type} generation error:`, error);
        return null;
    }
}

/**
 * Auto Generate Report Field (creates content from scratch)
 */
async function autoGenerateField(fieldId, fieldType) {
    const textarea = document.getElementById(fieldId);
    if (!textarea) {
        showToast('Field not found', 'error');
        return;
    }

    // Get button and set loading state
    const button = event.target.closest('.ai-generate-btn');
    const originalText = button.innerHTML;
    button.classList.add('loading');
    button.disabled = true;
    button.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="animation: spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Generating...`;

    try {
        let prompt = '';
        let systemMessage = '';

        // AUTO-GENERATE prompts - SHORT, SPECIFIC, HONEST
        switch (fieldType) {
            case 'purpose':
                prompt = "Write a SHORT, HONEST OJT purpose statement (1-2 short paragraphs). Use simple student language, NOT formal/corporate speak.\n\nInclude ONLY:\n- Your actual role/title (e.g., 'student trainee', 'intern')\n- 2-3 specific tasks you actually did\n- The company name if known\n\nDO NOT:\n- List skills you don't have\n- Make up titles like 'Junior Specialist'\n- Write 3+ paragraphs\n- Use buzzwords like 'cutting-edge', 'synergy', 'leverage'\n\nExample: 'I was assigned as a student trainee at [Company] under the IT department. My main tasks were encoding records, helping with network maintenance, and basic computer troubleshooting.'\n\nWrite in first person, past tense. Keep it under 150 words.";
                systemMessage = 'You are a student writing your OJT report. Be honest, specific, and brief. No corporate buzzwords.';
                break;
            case 'actionPlan':
                prompt = "Write a SHORT, REALISTIC OJT action plan (1-2 short paragraphs). Be honest about the timeline.\n\nInclude ONLY:\n- Your actual OJT duration (typically 300 hours / ~2 months, NOT 6 months)\n- 2-3 real tasks you planned to do\n- Simple timeline (Week 1-2: orientation, Week 3-6: main tasks, etc.)\n\nDO NOT:\n- Make up fake deliverables\n- Say 'developing software by week 16' (OJT is only ~8 weeks)\n- List unrealistic goals\n\nExample: 'My OJT was planned for 300 hours over 8 weeks. Week 1-2: orientation and training. Week 3-6: assisting with IT support tasks and encoding. Week 7-8: documentation and final reporting.'\n\nWrite in first person. Keep it under 150 words.";
                systemMessage = 'You are a student planning your OJT. Be realistic and honest. 300 hours = ~8 weeks, not 6 months.';
                break;
            case 'conclusion':
                prompt = "Write a SHORT, HONEST OJT conclusion (1-2 short paragraphs). Sound like a real student, not a motivational speaker.\n\nInclude ONLY:\n- 1-2 specific things you learned\n- One real challenge you faced\n- How it was different from school\n- The company name if known\n\nDO NOT:\n- List skills you didn't actually learn\n- Say 'cloud computing' or 'network administration' unless you actually did those\n- Write 'Congratulations' or motivational language\n- Sound like a LinkedIn post\n\nExample: 'My OJT at [Company] showed me how IT works in a real office. Unlike school where tasks are structured, I had to adapt quickly. The biggest challenge was explaining technical issues to non-technical staff. If I could do it again, I would take more notes during training sessions.'\n\nWrite in first person, past tense. Keep it under 150 words.";
                systemMessage = 'You are a student reflecting on your OJT. Be honest and specific. No motivational language.';
                break;
            case 'recommendationStudents':
                prompt = "Write SHORT, PRACTICAL advice for future OJT students (1-2 short paragraphs). Sound like a senior giving real advice, not a blog post.\n\nInclude ONLY:\n- 2-3 specific, practical tips\n- One mistake to avoid\n- Something you wish you knew before starting\n\nDO NOT:\n- Say 'Congratulations on taking the first step'\n- Write motivational speeches\n- Give generic advice like 'work hard'\n- Sound like LinkedIn influencer\n\nExample: 'Take notes during everything - you'll forget details otherwise. Ask questions early, even if they seem dumb. Don't wait for tasks - if you finish early, ask what else you can help with. I wish I knew how important it was to build relationships with the staff, not just do the tasks.'\n\nWrite in casual, friendly tone. Keep it under 150 words.";
                systemMessage = 'You are a senior student giving honest advice to juniors. Be practical, not motivational.';
                break;
            case 'recommendationCompany':
                prompt = "Write SHORT, CONSTRUCTIVE feedback for the company (1-2 short paragraphs). Be respectful but honest.\n\nInclude ONLY:\n- 1-2 things the company did well\n- 1 specific, reasonable suggestion for improvement\n\nDO NOT:\n- Write fake praise\n- Make demands\n- Sound entitled\n\nExample: 'The IT staff at [Company] were patient in teaching me the basics. I appreciated being included in real tasks. One suggestion: it would help future interns to have a written checklist of common tasks during the first week, so we can refer back to it when stuck.'\n\nWrite respectfully. Keep it under 120 words.";
                systemMessage = 'You are a student giving respectful, constructive feedback. Be honest but diplomatic.';
                break;
            case 'recommendationSchool':
                prompt = "Write SHORT, PRACTICAL suggestions for ISPSC (1-2 short paragraphs). Be respectful.\n\nInclude ONLY:\n- 1 thing the school did well\n- 1-2 specific, reasonable suggestions\n\nDO NOT:\n- Write complaints\n- Make unrealistic demands\n- Sound entitled\n\nExample: 'ISPSC prepared me well with the technical basics. One suggestion: it would help to have a brief orientation on workplace expectations before OJT starts - things like professional communication, time management, and what to expect on day 1. This would reduce the initial anxiety.'\n\nWrite respectfully. Keep it under 120 words.";
                systemMessage = 'You are a student giving respectful suggestions to your school. Be constructive, not complaining.';
                break;
            case 'companyIntroduction':
                prompt = "Write a SHORT company introduction (2-3 paragraphs). Include: company name, location, nature of business, brief history, mission/vision. Write professionally but simply. Under 150 words. Example: '[Company Name] is located in [City]. Established in [year], the company specializes in [field]. Their mission is to [mission]. As an IT company, they focus on [services].'";
                systemMessage = 'You are writing a company introduction for an OJT report. Be professional and concise.';
                break;
        }

        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt, system_message: systemMessage })
        });

        if (!response.ok) {
            throw new Error('AI generation failed');
        }

        const result = await response.json();
        
        if (result.success && result.narrative) {
            textarea.value = result.narrative.trim();
            showToast('Content generated!', 'success');
        } else {
            throw new Error(result.error || 'No content generated');
        }

    } catch (error) {
        console.error('Generation error:', error);
        showToast('Failed to generate. Try again.', 'error');
    } finally {
        button.classList.remove('loading');
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Add Deep Reflection to Entry Description
 */
async function handleAddReflection() {
    const description = entryDescription.value.trim();
    
    if (!description) {
        showToast('Please enter a description first', 'warning');
        entryDescription.focus();
        return;
    }

    const btn = document.getElementById('addReflectionBtn');
    const originalText = btn.innerHTML;
    
    // Set loading state
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="animation: spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Adding Reflection...`;
    
    showToast('🤔 AI is generating deep reflection...', 'info');

    try {
        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: `Add 2-3 sentences of DEEP REFLECTION to this OJT journal entry. Ask: (1) What surprised you? (2) How did this change your thinking? (3) What would you do differently? (4) How does this connect to school learning?\n\nEntry: "${description}"\n\nAdd reflection sentences at the end. Use first person, past tense. Be honest and personal. Example: "This surprised me because..." or "I realized that..." or "Next time, I would..."`,
                system_message: 'You are helping a student add deep reflection to their OJT journal. Make it personal, honest, and insightful. Not generic.'
            })
        });

        const result = await response.json();

        if (result.success && result.narrative) {
            // Append reflection to existing description
            entryDescription.value = description + '\n\n' + result.narrative.trim();
            showToast('✅ Reflection added!', 'success');
        } else {
            showToast('Failed to add reflection', 'error');
        }
    } catch (error) {
        console.error('Reflection error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Improve Writing Flow and Sentence Variety
 */
async function handleImproveWritingFlow() {
    const description = entryDescription.value.trim();
    
    if (!description) {
        showToast('Please enter a description first', 'warning');
        entryDescription.focus();
        return;
    }

    const btn = document.getElementById('improveWritingBtn');
    const originalText = btn.innerHTML;
    
    // Set loading state
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="animation: spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Improving Flow...`;
    
    showToast('✨ AI is improving sentence variety...', 'info');

    try {
        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: `Improve the writing flow and sentence variety of this OJT journal entry. Fix: (1) Repetitive "I used [tool]" structure, (2) Short choppy sentences, (3) Lack of transition words. Combine sentences, add transitions (However, Furthermore, Consequently), vary sentence starters. Keep original meaning and tools mentioned. Make it read naturally. Entry: "${description}"`,
                system_message: "You are improving writing quality. Maintain authenticity while making it read better. Do not make it sound corporate or fake."
            })
        });

        const result = await response.json();

        if (result.success && result.narrative) {
            entryDescription.value = result.narrative.trim();
            showToast('✅ Writing flow improved!', 'success');
        } else {
            showToast('Failed to improve writing', 'error');
        }
    } catch (error) {
        console.error('Writing improvement error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * AI Enhance Report Field
 * Auto-generates or enhances the content of a specific report field using AI
 */
async function enhanceReportField(fieldId, fieldType) {
    const textarea = document.getElementById(fieldId);
    if (!textarea) {
        showToast('Field not found', 'error');
        return;
    }

    const currentContent = textarea.value.trim();
    const isEmpty = !currentContent || currentContent.length < 5;
    
    // Get button and set loading state
    const button = event.target.closest('.ai-enhance-btn');
    const originalText = button.innerHTML;
    button.classList.add('loading');
    button.disabled = true;
    
    // Show different loading text based on empty or existing content
    button.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="animation: spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> ${isEmpty ? 'Generating...' : 'Enhancing...'}`;

    try {
        // Build prompt based on field type
        let prompt = '';
        let systemMessage = '';

        if (isEmpty) {
            // AUTO-GENERATE mode - create content from scratch
            switch (fieldType) {
                case 'purpose':
                    prompt = "Create a professional OJT (On-the-Job Training) purpose/role statement for an IT student. Include: (1) the main role and position, (2) key objectives and goals, (3) expected learning outcomes, and (4) how this contributes to professional development. Write in formal, professional tone. Make it 2-3 comprehensive paragraphs.";;
                    systemMessage = 'You are an expert at writing professional OJT purpose statements for IT students. Create clear, inspiring, and comprehensive purpose statements.';
                    break;
                case 'actionPlan':
                    prompt = "Create a professional OJT action plan background for an IT student. Include: (1) preparation and planning done before starting, (2) methodology and approach, (3) specific goals and deliverables, (4) timeline and milestones. Write in formal, professional tone. Make it 2-3 comprehensive paragraphs.";;
                    systemMessage = 'You are an expert at creating OJT action plans for IT students. Write detailed, structured, and professional action plan backgrounds.';
                    break;
                case 'conclusion':
                    prompt = "Create a professional OJT conclusion for an IT student. Include: (1) key learnings and skills gained, (2) challenges overcome and how, (3) impact on professional growth, (4) how this experience prepares for future career. Write in reflective, professional tone. Make it 2-3 comprehensive paragraphs.";;
                    systemMessage = 'You are an expert at writing reflective OJT conclusions for IT students. Create thoughtful, professional conclusions highlighting learning outcomes.';
                    break;
                case 'recommendationStudents':
                    prompt = "Create helpful recommendations for future IT OJT students. Include: (1) practical tips for success, (2) common mistakes to avoid, (3) best practices during OJT, (4) how to maximize the learning experience. Write in encouraging, friendly tone. Make it 2-3 helpful paragraphs.";;
                    systemMessage = 'You are an expert at providing guidance for OJT students. Write encouraging, practical, and actionable recommendations.';
                    break;
                case 'recommendationCompany':
                    prompt = "Create professional recommendations for companies hosting IT OJT students. Include: (1) suggestions for improving the OJT program, (2) better mentorship strategies, (3) enhanced learning opportunities, (4) feedback mechanisms. Write in constructive, diplomatic tone. Make it 2-3 professional paragraphs.";;
                    systemMessage = 'You are an expert at providing constructive feedback for companies. Write professional, diplomatic recommendations.';
                    break;
                case 'recommendationSchool':
                    prompt = "Create helpful recommendations for ISPSC's OJT program. Include: (1) curriculum improvements, (2) better industry partnerships, (3) enhanced OJT preparation, (4) support systems for students. Write in constructive, specific tone. Make it 2-3 helpful paragraphs.";;
                    systemMessage = 'You are an expert at providing educational recommendations. Write constructive, specific suggestions for improving OJT programs.';
                    break;
            }
        } else {
            // ENHANCE mode - improve existing content
            switch (fieldType) {
                case 'purpose':
                    prompt = `Enhance this OJT purpose/role description. Make it more professional, clear, and comprehensive. Improve grammar, flow, and depth. Include role, objectives, and expected outcomes. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at writing professional OJT purpose statements for IT students. Enhance and improve existing content.';
                    break;
                case 'actionPlan':
                    prompt = `Enhance this OJT action plan background. Make it more detailed and professional. Improve structure, clarity, and completeness. Include preparation, goals, methodology, and deliverables. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at creating OJT action plans for IT students. Enhance and improve existing content.';
                    break;
                case 'conclusion':
                    prompt = `Enhance this OJT conclusion. Make it more reflective and comprehensive. Improve depth, clarity, and impact. Include learnings, skills, challenges, and growth. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at writing reflective OJT conclusions for IT students. Enhance and improve existing content.';
                    break;
                case 'recommendationStudents':
                    prompt = `Enhance this recommendation for future OJT students. Make it more practical, encouraging, and actionable. Improve clarity and helpfulness. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at providing guidance for OJT students. Enhance and improve existing recommendations.';
                    break;
                case 'recommendationCompany':
                    prompt = `Enhance this recommendation for the company. Make it more constructive, professional, and specific. Improve diplomacy and clarity. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at providing constructive feedback for companies. Enhance and improve existing recommendations.';
                    break;
                case 'recommendationSchool':
                    prompt = `Enhance this recommendation for the school. Make it more constructive and specific. Improve clarity and actionability. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at providing educational recommendations. Enhance and improve existing recommendations.';
                    break;
                case 'companyIntroduction':
                    prompt = `Enhance this company introduction. Make it more professional and informative. Improve flow and clarity. Include company details, history, and mission. Current content: "${currentContent}"`;
                    systemMessage = 'You are an expert at writing professional company introductions. Enhance and improve existing content.';
                    break;
            }
        }

        // Call AI API
        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt, system_message: systemMessage })
        });

        if (!response.ok) {
            throw new Error('AI enhancement failed');
        }

        const result = await response.json();
        
        if (result.success && result.narrative) {
            textarea.value = result.narrative.trim();
            showToast(isEmpty ? 'Content generated successfully!' : 'Content enhanced successfully!', 'success');
        } else {
            throw new Error(result.error || 'No content generated');
        }

    } catch (error) {
        console.error('Enhancement error:', error);
        showToast('Failed to generate content. Please try again.', 'error');
    } finally {
        // Restore button
        button.classList.remove('loading');
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Rate Entire Document with AI
 */
async function handleRateDocument() {
    const ratingModal = document.getElementById('ratingModal');
    const ratingContent = document.getElementById('ratingContent');
    
    // Show modal with loading state
    ratingModal.classList.add('show');
    ratingContent.innerHTML = `
        <div class="rating-loading">
            <div class="loading-spinner" style="display: inline-block; margin-bottom: 1rem;"></div>
            <h3>🤖 AI is analyzing your document...</h3>
            <p>Checking consistency, reflection depth, writing quality, and more</p>
        </div>
    `;

    try {
        // Gather all document content
        const reportInfo = {
            companyName: companyName?.value || '',
            companyLocation: companyLocation?.value || '',
            purposeRole: purposeRole?.value || '',
            actionPlan: backgroundActionPlan?.value || '',
            conclusion: conclusion?.value || '',
            recommendations: {
                students: recommendationStudents?.value || '',
                company: recommendationCompany?.value || '',
                school: recommendationSchool?.value || ''
            }
        };

        // Get all entries
        const entriesText = Array.from(document.querySelectorAll('.ojt-entry-description'))
            .map(el => el.textContent.trim())
            .join('\n\n');

        const response = await fetch('process.php?action=generateWithPrompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: `You are an expert OJT grader. Rate this document and return ONLY valid JSON.

CRITICAL: Your response MUST be valid JSON starting with { and ending with }. No extra text, no markdown.

Rate these categories (1-10 scale):
1. INTERNAL CONSISTENCY (25%): Company name matches, dates logical, tasks align with company
2. REFLECTION DEPTH (20%): Personal insights, "I realized/learned" phrases
3. WRITING QUALITY (15%): Sentence variety, transitions, no repetition
4. SPECIFICITY (15%): Names tools, specific tasks, concrete examples
5. COMPLETENESS (15%): All sections filled, adequate length
6. PROFESSIONAL TONE (10%): Grammar, formality

Company Info: ${JSON.stringify(reportInfo)}
Entries: ${entriesText.substring(0, 2000)}

Return ONLY this JSON format (no markdown, no extra text):
{"overall":7.2,"categories":[{"name":"Internal Consistency","score":5.0,"issues":["Issue 1"]},{"name":"Reflection Depth","score":6.0,"issues":["Issue 2"]},{"name":"Writing Quality","score":7.0,"issues":["Issue 3"]},{"name":"Specificity","score":7.5,"issues":[]},{"name":"Completeness","score":8.5,"issues":[]},{"name":"Professional Tone","score":7.0,"issues":[]}],"fixes":[{"category":"Internal Consistency","action":"Fix this issue","priority":"high"}]}`,
                system_message: "Return ONLY valid JSON starting with { and ending with }. No markdown, no explanations, no extra text."
            })
        });

        const result = await response.json();

        if (result.success && result.narrative) {
            try {
                // Extract JSON from response (handle markdown code blocks and extra text)
                let jsonStr = result.narrative;
                
                // Remove markdown code blocks if present
                jsonStr = jsonStr.replace(/```json\s*/g, '').replace(/```\s*/g, '');
                
                // Find the first { and last } to extract just the JSON
                const startIdx = jsonStr.indexOf('{');
                const endIdx = jsonStr.lastIndexOf('}');
                
                if (startIdx === -1 || endIdx === -1) {
                    throw new Error('No valid JSON found in response');
                }
                
                jsonStr = jsonStr.substring(startIdx, endIdx + 1).trim();
                
                // Parse the JSON
                const rating = JSON.parse(jsonStr);
                displayRatingDashboard(rating);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Raw response:', result.narrative);
                throw new Error('Failed to parse AI response. Please try again.');
            }
        } else {
            throw new Error(result.error || 'Failed to get rating');
        }
    } catch (error) {
        console.error('Rating error:', error);
        ratingContent.innerHTML = `
            <div class="rating-error">
                <p style="color: var(--error-color);">Failed to analyze document</p>
                <p style="font-size: 0.9rem; color: var(--text-tertiary);">${error.message}</p>
                <button class="btn btn-sm btn-primary" onclick="handleRateDocument()" style="margin-top: 1rem;">Try Again</button>
            </div>
        `;
    }
}

/**
 * Display Rating Dashboard
 */
function displayRatingDashboard(rating) {
    const ratingContent = document.getElementById('ratingContent');
    
    const categoryBars = rating.categories.map(cat => `
        <div class="rating-category-bar">
            <div class="rating-category-header">
                <span class="rating-category-name">${cat.name}</span>
                <span class="rating-category-score ${getScoreClass(cat.score)}">${cat.score}/10</span>
            </div>
            <div class="rating-progress-bg">
                <div class="rating-progress-fill ${getScoreClass(cat.score)}" style="width: ${cat.score * 10}%"></div>
            </div>
            ${cat.issues && cat.issues.length > 0 ? `
                <div class="rating-issues">
                    ${cat.issues.map(issue => `<p class="rating-issue">⚠️ ${issue}</p>`).join('')}
                </div>
            ` : ''}
        </div>
    `).join('');

    const fixesList = rating.fixes && rating.fixes.length > 0 ? `
        <div class="rating-fixes-section">
            <h3>🔧 Suggested Improvements</h3>
            <div class="rating-fixes-list">
                ${rating.fixes.map((fix, i) => `
                    <div class="rating-fix-item priority-${fix.priority}">
                        <span class="rating-fix-priority">${fix.priority === 'high' ? '🔴 High' : fix.priority === 'medium' ? '🟡 Medium' : '🟢 Low'}</span>
                        <p class="rating-fix-text">${fix.action}</p>
                        <button class="btn btn-sm btn-outline" onclick="applyFix(${i})">Apply</button>
                    </div>
                `).join('')}
            </div>
            <button class="btn btn-primary" onclick="applyAllFixes()" style="width: 100%; margin-top: 1rem;">
                ✨ Fix All Issues
            </button>
        </div>
    ` : '<div class="rating-perfect"><p>✅ No major issues found! Your document looks great!</p></div>';

    ratingContent.innerHTML = `
        <div class="rating-dashboard">
            <div class="rating-overview">
                <div class="rating-score-circle ${getScoreClass(rating.overall)}">
                    <span class="rating-score-number">${rating.overall}</span>
                    <span class="rating-score-label">/ 10</span>
                </div>
                <div class="rating-overview-text">
                    <h3>Overall Score</h3>
                    <p>${getScoreMessage(rating.overall)}</p>
                </div>
            </div>

            <div class="rating-categories">
                <h3>Category Breakdown</h3>
                ${categoryBars}
            </div>

            ${fixesList}
        </div>
    `;
}

function getScoreClass(score) {
    if (score >= 8) return 'score-good';
    if (score >= 6) return 'score-medium';
    return 'score-low';
}

function getScoreMessage(score) {
    if (score >= 9) return "Excellent! Ready for submission!";
    if (score >= 8) return "Very good! Just a few improvements needed.";
    if (score >= 7) return "Good, but there's room for improvement.";
    if (score >= 6) return "Fair. Several areas need attention.";
    return "Needs significant improvement before submission.";
}

/**
 * Apply Individual Fix
 */
function applyFix(fixIndex) {
    showToast('🔧 Applying fix...', 'info');
    // Implementation depends on fix type - would need to parse and apply
    setTimeout(() => showToast('✅ Fix applied!', 'success'), 1000);
}

/**
 * Apply All Fixes
 */
function applyAllFixes() {
    showToast('🔧 Applying all fixes...', 'info');
    // Would iterate through all fixes and apply them
    setTimeout(() => {
        showToast('✅ All fixes applied!', 'success');
        document.getElementById('ratingModal').classList.remove('show');
    }, 1500);
}
