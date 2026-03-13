/**
 * OJT Journal Report Generator - Frontend JavaScript
 * Handles form submission, image uploads, and AI-powered enhancements
 */

// DOM Elements
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

// Edit Entry Modal Elements
const editEntryModal = document.getElementById('editEntryModal');
const editEntryForm = document.getElementById('editEntryForm');
const editEntryId = document.getElementById('editEntryId');
const editEntryTitle = document.getElementById('editEntryTitle');
const editEntryDate = document.getElementById('editEntryDate');
const editEntryDescription = document.getElementById('editEntryDescription');
const closeEditEntryBtn = document.getElementById('closeEditEntryBtn');
const cancelEditEntryBtn = document.getElementById('cancelEditEntryBtn');
const editStatusMessage = document.getElementById('editStatusMessage');

// Image Analysis Modal Elements
const imageAnalysisModal = document.getElementById('imageAnalysisModal');
const analysisImage = document.getElementById('analysisImage');
const analysisDescription = document.getElementById('analysisDescription');
const closeImageAnalysisBtn = document.getElementById('closeImageAnalysisBtn');
const saveAnalysisBtn = document.getElementById('saveAnalysisBtn');
const regenerateAnalysisBtn = document.getElementById('regenerateAnalysisBtn');
const analysisStatusMessage = document.getElementById('analysisStatusMessage');

// AI Customize Modal Elements
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

// State
let selectedFiles = [];
let narrativeCache = null;
let currentEditEntry = null;
let currentImageId = null;
let currentEntryId = null;

// Set default date to today
entryDate.valueAsDate = new Date();

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
}

// Unregister any service workers to prevent cache issues with POST requests
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

// Clear cache to prevent stale resources
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

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Clean up service workers and cache first to prevent POST caching issues
    await unregisterServiceWorkers();
    await clearCache();
    
    initializeTheme();
    initializeEventListeners();
    loadWeeklyReport();
    initializeDownloadReport(); // Initialize download report functions
});

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Form submission
    ojtForm.addEventListener('submit', handleSubmit);

    // Upload area click
    uploadArea.addEventListener('click', (e) => {
        if (e.target !== submitBtn && e.target !== clearBtn && !e.target.closest('.remove-btn')) {
            imageInput.click();
        }
    });

    // File input change
    imageInput.addEventListener('change', handleFileSelect);

    // Drag and drop
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleDrop);

    // Clear button
    clearBtn.addEventListener('click', clearForm);

    // Refresh button
    refreshBtn.addEventListener('click', loadWeeklyReport);

    // Narrative button
    narrativeBtn.addEventListener('click', handleGenerateNarrative);

    // Close narrative button
    closeNarrativeBtn.addEventListener('click', () => {
        narrativeContainer.classList.remove('show');
    });

    // Refresh button
    refreshBtn.addEventListener('click', loadWeeklyReport);

    // Theme toggle
    themeToggle.addEventListener('click', toggleTheme);

    // Enhance Description button
    const enhanceBtn = document.getElementById('enhanceBtn');
    if (enhanceBtn) {
        enhanceBtn.addEventListener('click', handleEnhanceDescription);
    }

    // Edit Entry Modal
    closeEditEntryBtn.addEventListener('click', closeEditEntryModal);
    cancelEditEntryBtn.addEventListener('click', closeEditEntryModal);
    editEntryForm.addEventListener('submit', handleEditEntrySubmit);

    // Close edit modal on overlay click
    if (editEntryModal) {
        editEntryModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('download-report-overlay')) {
                closeEditEntryModal();
            }
        });
    }

    // Image Analysis Modal
    closeImageAnalysisBtn.addEventListener('click', closeImageAnalysisModal);
    cancelEditEntryBtn.addEventListener('click', closeEditEntryModal);
    editEntryForm.addEventListener('submit', handleEditEntrySubmit);

    // Image analysis buttons
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
    if (uploadActions) {
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

    // Close AI customize modal on overlay click
    if (aiCustomizeModal) {
        aiCustomizeModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('download-report-overlay')) {
                closeAICustomizeModal();
            }
        });
    }
}

/**
 * Close Edit Entry Modal
 */
function closeEditEntryModal() {
    if (editEntryModal) {
        editEntryModal.classList.remove('show');
        editEntryForm.reset();
        editStatusMessage.textContent = '';
        currentEditEntry = null;
    }
}

/**
 * Close Image Analysis Modal
 */
function closeImageAnalysisModal() {
    if (imageAnalysisModal) {
        imageAnalysisModal.classList.remove('show');
        analysisStatusMessage.textContent = '';
        currentImageId = null;
        currentEntryId = null;
    }
}

/**
 * Open Image Analysis Modal
 */
function openImageAnalysisModal(imageId, entryId, imageUrl, description) {
    currentImageId = imageId;
    currentEntryId = entryId;
    analysisImage.src = imageUrl;
    analysisDescription.value = description || 'No AI description available. Click "Regenerate" to analyze this image.';
    analysisStatusMessage.textContent = '';
    
    if (imageAnalysisModal) {
        imageAnalysisModal.classList.add('show');
    }
}

/**
 * Handle Save Analysis
 */
async function handleSaveAnalysis() {
    if (!currentImageId) return;
    
    const newDescription = analysisDescription.value.trim();
    
    if (!newDescription) {
        analysisStatusMessage.innerHTML = '<span class="error">Description cannot be empty</span>';
        return;
    }
    
    saveAnalysisBtn.disabled = true;
    saveAnalysisBtn.innerHTML = 'Saving...';
    
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
            analysisStatusMessage.innerHTML = '<span class="success">Description saved successfully!</span>';
            setTimeout(() => {
                closeImageAnalysisModal();
                loadWeeklyReport(); // Refresh to show updated description
            }, 1500);
        } else {
            analysisStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to save description'}</span>`;
        }
    } catch (error) {
        console.error('Save analysis error:', error);
        analysisStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
    } finally {
        saveAnalysisBtn.disabled = false;
        saveAnalysisBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Save Changes
        `;
    }
}

/**
 * Handle Analyze Images for Entry (Auto-generate title and description)
 */
async function handleAnalyzeImagesForEntry() {
    if (selectedFiles.length === 0) {
        showToast('Please upload images first', 'warning');
        return;
    }

    // Fix: Query the button locally instead of using undefined scoped variable
    const analyzeImagesBtn = document.getElementById('analyzeImagesBtn');
    if (!analyzeImagesBtn) {
        showToast('Analyze button not found', 'error');
        return;
    }

    analyzeImagesBtn.disabled = true;
    analyzeImagesBtn.innerHTML = 'Analyzing images...';

    try {
        // Create FormData with all images
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
            // Auto-fill title
            if (result.title) {
                entryTitle.value = result.title;
            }

            // Auto-fill description
            if (result.description) {
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
async function handleRegenerateAnalysis() {
    if (!currentImageId || !analysisImage.src) return;
    
    regenerateAnalysisBtn.disabled = true;
    regenerateAnalysisBtn.innerHTML = 'Analyzing...';
    analysisDescription.value = 'AI is analyzing the image...';
    
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
            analysisDescription.value = result.description;
            analysisStatusMessage.innerHTML = '<span class="success">Analysis regenerated successfully!</span>';
        } else {
            analysisStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to regenerate analysis'}</span>`;
        }
    } catch (error) {
        console.error('Regenerate analysis error:', error);
        analysisStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
    } finally {
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

/**
 * AI Customize Functions
 */

/**
 * Close AI Customize Modal
 */
function closeAICustomizeModal() {
    if (aiCustomizeModal) {
        aiCustomizeModal.classList.remove('show');
        customizeStatusMessage.textContent = '';
        customizeEntryId.value = '';
        enhancedPreview.value = '';
        applyCustomizationBtn.disabled = true;
    }
}

/**
 * Open AI Customize Modal
 */
function openAICustomizeModal(entryId, title, description) {
    customizeEntryId.value = entryId;
    currentDescription.value = description;
    customPrompt.value = '';
    enhancedPreview.value = '';
    customizeStatusMessage.textContent = '';
    applyCustomizationBtn.disabled = true;

    if (aiCustomizeModal) {
        aiCustomizeModal.classList.add('show');
    }
}

/**
 * Handle Generate Customization
 */
async function handleGenerateCustomization() {
    const entryId = customizeEntryId.value;
    const prompt = customPrompt.value.trim();

    if (!entryId) {
        customizeStatusMessage.innerHTML = '<span class="error">No entry selected</span>';
        return;
    }

    if (!prompt) {
        customizeStatusMessage.innerHTML = '<span class="error">Please enter your customization prompt</span>';
        customPrompt.focus();
        return;
    }

    generateCustomizationBtn.disabled = true;
    generateCustomizationBtn.innerHTML = 'Generating...';
    customizeStatusMessage.textContent = '';

    try {
        const response = await fetch('process.php?action=customizeEntryWithAI', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                entry_id: entryId,
                current_description: currentDescription.value,
                custom_prompt: prompt
            })
        });

        const result = await response.json();

        if (result.success) {
            enhancedPreview.value = result.enhanced_description;
            applyCustomizationBtn.disabled = false;
            customizeStatusMessage.innerHTML = '<span class="success">AI enhancement generated! Review and apply if satisfied.</span>';
        } else {
            customizeStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to generate enhancement'}</span>`;
        }
    } catch (error) {
        console.error('Generate customization error:', error);
        customizeStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
    } finally {
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

/**
 * Handle Apply Customization
 */
async function handleApplyCustomization() {
    const entryId = customizeEntryId.value;
    const enhancedDescription = enhancedPreview.value;

    if (!entryId || !enhancedDescription) {
        customizeStatusMessage.innerHTML = '<span class="error">No enhancement to apply</span>';
        return;
    }

    applyCustomizationBtn.disabled = true;
    applyCustomizationBtn.innerHTML = 'Applying...';

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
            customizeStatusMessage.innerHTML = '<span class="success">Changes applied successfully!</span>';
            setTimeout(() => {
                closeAICustomizeModal();
                loadWeeklyReport(); // Refresh to show updated entry
            }, 1500);
        } else {
            customizeStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to apply changes'}</span>`;
        }
    } catch (error) {
        console.error('Apply customization error:', error);
        customizeStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
    } finally {
        applyCustomizationBtn.disabled = false;
        applyCustomizationBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Apply Changes
        `;
    }
}

/**
 * Open Edit Entry Modal with entry data
 */
function openEditEntryModal(entry) {
    currentEditEntry = entry;
    editEntryId.value = entry.id;
    editEntryTitle.value = entry.title;
    editEntryDate.value = entry.entry_date;
    editEntryDescription.value = entry.ai_enhanced_description || entry.user_description || '';
    editStatusMessage.textContent = '';
    
    if (editEntryModal) {
        editEntryModal.classList.add('show');
    }
}

/**
 * Handle Edit Entry Form Submit
 */
async function handleEditEntrySubmit(e) {
    e.preventDefault();
    
    const id = editEntryId.value;
    const title = editEntryTitle.value.trim();
    const description = editEntryDescription.value.trim();
    const entryDate = editEntryDate.value;
    
    // Validate
    if (!title || title.length < 3) {
        editStatusMessage.innerHTML = '<span class="error">Title must be at least 3 characters</span>';
        return;
    }
    
    if (!entryDate) {
        editStatusMessage.innerHTML = '<span class="error">Please select a date</span>';
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
                entry_date: entryDate
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            editStatusMessage.innerHTML = '<span class="success">Entry updated successfully!</span>';
            setTimeout(() => {
                closeEditEntryModal();
                loadWeeklyReport();
            }, 1500);
        } else {
            editStatusMessage.innerHTML = `<span class="error">${result.error || 'Failed to update entry'}</span>`;
        }
    } catch (error) {
        console.error('Edit entry error:', error);
        editStatusMessage.innerHTML = `<span class="error">Error: ${error.message}</span>`;
    }
}

/**
 * Handle form submission
 */
async function handleSubmit(e) {
    e.preventDefault();

    const title = entryTitle.value.trim();
    const description = entryDescription.value.trim();
    const date = entryDate.value;

    // Comprehensive client-side validation
    if (!title) {
        showStatus('Please enter a title for this entry', 'error');
        entryTitle.focus();
        return;
    }

    if (title.length < 3) {
        showStatus('Title must be at least 3 characters long', 'error');
        entryTitle.focus();
        return;
    }

    if (title.length > 200) {
        showStatus('Title must not exceed 200 characters', 'error');
        entryTitle.focus();
        return;
    }

    if (description.length > 5000) {
        showStatus('Description must not exceed 5000 characters', 'error');
        entryDescription.focus();
        return;
    }

    if (!date) {
        showStatus('Please select a date', 'error');
        entryDate.focus();
        return;
    }

    // Validate date is not in the future
    const today = new Date().toISOString().split('T')[0];
    if (date > today) {
        showStatus('Entry date cannot be in the future', 'error');
        entryDate.focus();
        return;
    }

    // Validate date is not too old (max 1 year)
    const oneYearAgo = new Date();
    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
    const oneYearAgoStr = oneYearAgo.toISOString().split('T')[0];
    if (date < oneYearAgoStr) {
        showStatus('Entry date is too old. Maximum 1 year back', 'error');
        entryDate.focus();
        return;
    }

    if (selectedFiles.length === 0) {
        showStatus('Please upload at least one image', 'error');
        return;
    }

    if (selectedFiles.length > 10) {
        showStatus('Maximum 10 images allowed per entry', 'error');
        return;
    }

    // Validate files before upload
    const validFiles = [];
    const invalidFiles = [];
    const maxFileSize = 5 * 1024 * 1024; // 5MB
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
        showStatus(`Invalid files:<br>${errorList}`, 'error');
        return;
    }

    if (validFiles.length === 0) {
        showStatus('No valid files to upload', 'error');
        return;
    }

    setLoading(true);
    hideStatus();

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
            // Disable cache for POST requests
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
            console.error('JSON parse error:', parseError);
            throw new Error('Server returned invalid JSON. Check console for details.');
        }

        if (result.success) {
            // Check if any images failed
            const imageResults = result.images || [];
            const failedImages = imageResults.filter(img => img.error);
            
            let successMessage = 'OJT entry created successfully! AI is enhancing your description.';
            if (failedImages.length > 0) {
                successMessage += ` (${failedImages.length} image(s) failed to upload)`;
            }
            
            showStatus(successMessage, 'success');
            clearForm();
            narrativeCache = null;
            loadWeeklyReport();
        } else {
            showStatus(result.error || 'Failed to create entry', 'error');
        }
    } catch (error) {
        console.error('Submit error:', error);
        showStatus('Error: ' + error.message, 'error');
    } finally {
        setLoading(false);
    }
}

/**
 * Handle drag over event
 */
function handleDragOver(e) {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
}

/**
 * Handle drag leave event
 */
function handleDragLeave(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
}

/**
 * Handle drop event
 */
function handleDrop(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    processFiles(files);
}

/**
 * Handle file input selection
 */
function handleFileSelect(e) {
    const files = e.target.files;
    processFiles(files);
}

/**
 * Process selected files
 */
function processFiles(files) {
    const validFiles = Array.from(files).filter(file => {
        if (!file.type.startsWith('image/')) {
            showStatus(`${file.name} is not an image file`, 'error');
            return false;
        }
        if (file.size > 5 * 1024 * 1024) {
            showStatus(`${file.name} exceeds 5MB limit`, 'error');
            return false;
        }
        return true;
    });

    selectedFiles = [...selectedFiles, ...validFiles];
    updatePreview();
}

/**
 * Update preview container
 */
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
                <button class="remove-btn" data-index="${index}">&times;</button>
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

/**
 * Remove a file from selection
 */
function removeFile(index) {
    selectedFiles.splice(index, 1);
    updatePreview();
}

/**
 * Clear form
 */
function clearForm() {
    ojtForm.reset();
    entryDate.valueAsDate = new Date();
    selectedFiles = [];
    imageInput.value = '';
    updatePreview();
    hideStatus();
}

/**
 * Load weekly report
 */
async function loadWeeklyReport() {
    reportGrid.innerHTML = '';
    emptyState.classList.remove('show');
    narrativeContainer.classList.remove('show');

    try {
        const response = await fetch('process.php?action=getWeekly');
        const responseText = await response.text();

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response from server');
        }

        if (result.success) {
            displayWeeklyReport(result.week);
        } else {
            showStatus(result.error || 'Failed to load weekly report', 'error');
        }
    } catch (error) {
        console.error('Load error:', error);
        showStatus('Error: ' + error.message, 'error');
    }
}

/**
 * Display weekly report entries
 */
function displayWeeklyReport(week) {
    weekRange.textContent = `${week.start} - ${week.end}`;
    entryCount.textContent = `${week.entries.length} entr${week.entries.length !== 1 ? 'ies' : 'y'}`;

    // Update stats dashboard
    updateStats(week.entries);

    reportGrid.innerHTML = '';

    if (week.entries.length === 0) {
        emptyState.classList.add('show');
        return;
    }

    week.entries.forEach(entry => {
        const card = createEntryCard(entry);
        reportGrid.appendChild(card);
    });
}

/**
 * Update stats dashboard
 */
function updateStats(entries) {
    const totalEntries = entries.length;
    const totalImages = entries.reduce((sum, entry) => sum + (entry.images ? entry.images.length : 0), 0);
    
    // Calculate unique days
    const uniqueDates = new Set(entries.map(e => e.entry_date));
    const totalDays = uniqueDates.size;
    
    // Animate numbers
    animateValue('totalEntries', totalEntries);
    animateValue('totalImages', totalImages);
    animateValue('totalDays', totalDays);
}

/**
 * Animate value counter
 */
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

/**
 * Create an entry card
 */
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
        galleryHtml = `<div class="ojt-entry-gallery">
            ${entry.images.map(img => `
                <img src="${img.image_path}" alt="Entry image" data-full="${img.image_path}" title="Click to enlarge">
            `).join('')}
        </div>`;
    }

    // Check if description was AI-enhanced
    const isEnhanced = entry.ai_enhanced_description !== entry.user_description &&
                       entry.ai_enhanced_description !== 'No description available';

    const description = entry.ai_enhanced_description || entry.user_description || 'No description';
    const enhancedClass = isEnhanced ? 'enhanced' : '';
    const currentDescription = entry.ai_enhanced_description || entry.user_description || '';

    card.innerHTML = `
        <div class="ojt-entry-header">
            <h3 class="ojt-entry-title">${escapeHtml(entry.title)}</h3>
            <span class="ojt-entry-date">${formattedDate}</span>
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
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    ${entry.images ? entry.images.length : 0} image${entry.images && entry.images.length !== 1 ? 's' : ''}
                </span>
                <div class="entry-actions">
                    <button class="entry-edit" data-id="${entry.id}" title="Edit entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="entry-ai-customize" data-id="${entry.id}" data-title="${escapeHtml(entry.title)}" data-description="${escapeHtml(entry.ai_enhanced_description || entry.user_description || '')}" title="AI Customize">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/>
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
                        </svg>
                    </button>
                    <button class="entry-delete" data-id="${entry.id}" title="Delete entry">
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

    // Add edit listener - opens full edit modal
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

    // Add image click listeners for analysis modal
    card.querySelectorAll('.ojt-entry-gallery img').forEach((img, index) => {
        img.addEventListener('click', () => {
            const imageData = entry.images[index];
            openImageAnalysisModal(
                imageData.id,
                entry.id,
                imageData.image_path,
                imageData.ai_description || 'No AI description available. Click "Regenerate" to analyze this image.'
            );
        });
    });

    return card;
}

/**
 * Edit description
 */
function editDescription(id) {
    const descParagraph = document.getElementById(`desc-${id}`);
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const editActions = descParagraph.parentElement.nextElementSibling;
    
    descParagraph.style.display = 'none';
    editTextarea.style.display = 'block';
    editActions.style.display = 'block';
    editTextarea.focus();
}

/**
 * Save description
 */
async function saveDescription(id) {
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const newDescription = editTextarea.value.trim();
    
    if (!newDescription) {
        showStatus('Description cannot be empty', 'error');
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
            showStatus('Description updated successfully!', 'success');
            narrativeCache = null;
        } else {
            showStatus(result.error || 'Failed to update description', 'error');
        }
    } catch (error) {
        showStatus('Network error: ' + error.message, 'error');
    }
}

/**
 * Cancel edit
 */
function cancelEdit(id) {
    const descParagraph = document.getElementById(`desc-${id}`);
    const editTextarea = document.getElementById(`edit-desc-${id}`);
    const editActions = descParagraph.parentElement.nextElementSibling;
    
    descParagraph.style.display = 'block';
    editTextarea.style.display = 'none';
    editActions.style.display = 'none';
}

/**
 * Show image modal
 */
function showImageModal(imageSrc) {
    let modal = document.querySelector('.image-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = `
            <button class="close-btn">&times;</button>
            <img src="" alt="Full size image">
        `;
        document.body.appendChild(modal);

        modal.querySelector('.close-btn').addEventListener('click', () => {
            modal.classList.remove('show');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    }

    modal.querySelector('img').src = imageSrc;
    modal.classList.add('show');
}

/**
 * Delete an entry
 */
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
                entryCount.textContent = `${entries.length} entr${entries.length !== 1 ? 'ies' : 'y'}`;
                
                if (entries.length === 0) {
                    emptyState.classList.add('show');
                }
            }, 200);
        } else {
            showStatus(result.error || 'Delete failed', 'error');
        }
    } catch (error) {
        showStatus('Network error: ' + error.message, 'error');
    }
}

/**
 * Generate narrative report
 */
async function handleGenerateNarrative() {
    narrativeContainer.classList.add('show');
    narrativeContent.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div class="btn-loader" style="display: inline-block; margin-bottom: 1rem;"></div>
            <p>Generating your weekly narrative report...</p>
            <p style="font-size: 0.85rem; opacity: 0.8;">AI is analyzing your OJT entries</p>
        </div>
    `;

    const currentEntryCount = reportGrid.querySelectorAll('.ojt-entry-card').length;
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
            narrativeContent.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: rgba(255,255,255,0.8);">${result.error || 'Failed to generate narrative'}</p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">Add some OJT entries first, then try again.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Narrative error:', error);
        narrativeContent.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p>Error: ${error.message}</p>
            </div>
        `;
    }
}

/**
 * Display narrative report
 */
function displayNarrative(narrative) {
    const paragraphs = narrative.split('\n\n').filter(p => p.trim());
    narrativeContent.innerHTML = paragraphs
        .map(p => `<p>${escapeHtml(p)}</p>`)
        .join('');
}

/**
 * Handle print
 */
function handlePrint() {
    document.getElementById('printWeekRange').textContent = weekRange.textContent;
    window.print();
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon based on type
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Show status message (legacy - now uses toast)
 */
function showStatus(message, type) {
    // Use toast instead of inline message
    showToast(message, type);
    
    // Also update legacy status message if exists
    if (statusMessage) {
        statusMessage.textContent = message;
        statusMessage.className = `status-message show ${type}`;
        if (type === 'success') {
            setTimeout(hideStatus, 5000);
        }
    }
}

/**
 * Hide status message
 */
function hideStatus() {
    statusMessage.className = 'status-message';
}

/**
 * Set loading state
 */
function setLoading(loading) {
    submitBtn.disabled = loading;
    const btnLoader = submitBtn.querySelector('.btn-loader');

    if (loading) {
        // Preserve icon, update text
        const icon = submitBtn.querySelector('svg');
        const textNode = submitBtn.childNodes[submitBtn.childNodes.length - 2];
        if (textNode) textNode.nodeValue = ' Creating Entry... ';
        btnLoader.classList.remove('hidden');
    } else {
        const textNode = submitBtn.childNodes[submitBtn.childNodes.length - 2];
        if (textNode) textNode.nodeValue = ' Create Entry ';
        btnLoader.classList.add('hidden');
    }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Handle Enhance Description with AI
 */
async function handleEnhanceDescription() {
    const enhanceBtn = document.getElementById('enhanceBtn');
    const descriptionTextarea = document.getElementById('entryDescription');
    const originalText = descriptionTextarea.value.trim();
    
    if (!originalText) {
        showToast('Please enter a description first', 'warning');
        descriptionTextarea.focus();
        return;
    }
    
    // Set loading state
    enhanceBtn.disabled = true;
    enhanceBtn.classList.add('loading');
    // Preserve icon, only change text
    const icon = enhanceBtn.querySelector('svg');
    enhanceBtn.innerHTML = '';
    enhanceBtn.appendChild(icon.cloneNode(true));
    enhanceBtn.appendChild(document.createTextNode(' Enhancing...'));
    
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
            descriptionTextarea.value = result.enhanced_description;
            showToast('Description enhanced successfully!', 'success');
        } else {
            showToast(result.error || 'Failed to enhance description', 'error');
        }
    } catch (error) {
        console.error('Enhance error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        // Reset button state
        enhanceBtn.disabled = false;
        enhanceBtn.classList.remove('loading');
        // Restore icon and text
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
        enhanceBtn.appendChild(document.createTextNode(' Enhance with AI'));
    }
}

/**
 * Handle Generate Download Report (non-AI, just entries)
 */
async function handleGenerateDownloadReport() {
    downloadReportModal.classList.add('show');
    downloadReportContent.innerHTML = `
        <div style="text-align: center; padding: 3rem;">
            <div class="btn-loader" style="display: inline-block; margin-bottom: 1rem;"></div>
            <h3>Generating your OJT Report...</h3>
            <p>Loading your OJT entries and photos</p>
        </div>
    `;

    try {
        const response = await fetch('process.php?action=generateDownloadReport');
        const result = await response.json();

        if (result.success) {
            downloadReportCache = result.report;
            displayDownloadReport(result.report);
        } else {
            downloadReportContent.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: var(--error-color);">${result.error || 'Failed to generate report'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Download Report error:', error);
        downloadReportContent.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p style="color: var(--error-color);">Error: ${error.message}</p>
            </div>
        `;
    }
}

/**
 * Display Download Report (non-AI, just entries following ISPSC format)
 */
function displayDownloadReport(report) {
    const { entries, start_date, end_date, total_days, student_name } = report;

    downloadReportContent.innerHTML = `
        <div class="download-report">
            <!-- Cover Page -->
            <div class="report-page report-cover-page">
                <div class="report-cover-content">
                    <h1 class="report-cover-college">ILOCOS SUR POLYTECHNIC STATE COLLEGE</h1>
                    <h2 class="report-cover-campus">Candon Campus</h2>
                    <div class="report-cover-spacer-large"></div>
                    <h1 class="report-cover-title">OJT REPORT</h1>
                    <p class="report-cover-company">(Name of Company/office assigned)</p>
                    <div class="report-cover-spacer-large"></div>
                    <p class="report-cover-name">${student_name}</p>
                    <p class="report-cover-program">Bachelor of Science in Information Technology</p>
                    <p class="report-cover-sy">S.Y. 2025 - 2026</p>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="report-page">
                <h2 class="report-toc-title">Table of Content</h2>
                <div class="report-toc">
                    <div class="report-toc-chapter">
                        <h3>Chapter I Company Profile</h3>
                        <ul>
                            <li>Introduction ............................................................................................ 1</li>
                            <li>Duration and time ................................................................................... 2</li>
                            <li>Purpose/Role to the company .................................................................. 3</li>
                        </ul>
                    </div>
                    <div class="report-toc-chapter">
                        <h3>Chapter II Immersion Documentation</h3>
                        <ul>
                            <li>Background of the action plan ............................................................... 4</li>
                            <li>Program of Activities – per day .............................................................. 5</li>
                            <li>Evaluation of Result (4th year only) ....................................................... 8</li>
                        </ul>
                    </div>
                    <div class="report-toc-chapter">
                        <h3>Chapter III Conclusion and Recommendation</h3>
                        <ul>
                            <li>Conclusion .............................................................................................. 9</li>
                            <li>Recommendation .................................................................................... 10</li>
                        </ul>
                    </div>
                    <div class="report-toc-chapter">
                        <h3>Appendix</h3>
                        <ul>
                            <li>Endorsement Letter ................................................................................ 11</li>
                            <li>Screen of the Project .............................................................................. 12</li>
                            <li>Certificate ................................................................................................ 13</li>
                            <li>Daily Time and Record DTR (4th year only) ........................................... 14</li>
                            <li>Photo Documentation .............................................................................. 15</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Chapter I -->
            <div class="report-page">
                <h2 class="report-chapter-title">Chapter I: Company Profile</h2>
                
                <h3 class="report-section-title">Introduction</h3>
                <div class="report-placeholder">
                    <p><em>[Write the introduction of the company here. Include company name, location, nature of business, and background.]</em></p>
                </div>

                <h3 class="report-section-title">Duration and Time</h3>
                <div class="report-placeholder">
                    <p><em>Start Date: ${start_date}</em></p>
                    <p><em>End Date: ${end_date}</em></p>
                    <p><em>Daily Hours: [Specify your daily OJT hours, e.g., 8:00 AM - 5:00 PM]</em></p>
                </div>

                <h3 class="report-section-title">Purpose/Role to the Company</h3>
                <div class="report-placeholder">
                    <p><em>[Describe your specific role and what you aimed to achieve during the OJT]</em></p>
                </div>
            </div>

            <!-- Chapter II -->
            <div class="report-page">
                <h2 class="report-chapter-title">Chapter II: Immersion Documentation</h2>
                
                <h3 class="report-section-title">Background of the Action Plan</h3>
                <div class="report-placeholder">
                    <p><em>[Describe the plan you created before starting the immersion]</em></p>
                </div>

                <h3 class="report-section-title">Program of Activities – Per Day</h3>
                <table class="report-activities-table">
                    <thead>
                        <tr>
                            <th>Day/Date</th>
                            <th>Activity</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${entries.map((entry, index) => `
                            <tr>
                                <td>Day ${index + 1}<br>${new Date(entry.entry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                                <td>${escapeHtml(entry.title)}</td>
                                <td>${escapeHtml(entry.user_description || 'No description')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <h3 class="report-section-title">Evaluation of Result</h3>
                <div class="report-placeholder">
                    <p><em>(Include this section ONLY if you are a 4th Year Student)</em></p>
                    <p><em>[Evaluate the outcomes of your immersion]</em></p>
                </div>
            </div>

            <!-- Chapter III -->
            <div class="report-page">
                <h2 class="report-chapter-title">Chapter III: Conclusion and Recommendation</h2>
                
                <h3 class="report-section-title">Conclusion</h3>
                <div class="report-placeholder">
                    <p><em>[Summarize your overall experience and learnings]</em></p>
                </div>

                <h3 class="report-section-title">Recommendation</h3>
                <div class="report-placeholder">
                    <p><em>[Provide suggestions for future OJT students, the company, or the school]</em></p>
                </div>
            </div>

            <!-- Appendix -->
            <div class="report-page">
                <h2 class="report-chapter-title">Appendix</h2>
                
                <h3 class="report-section-title">Endorsement Letter</h3>
                <div class="report-placeholder">
                    <p><em>[Insert scanned copy or text of the endorsement letter]</em></p>
                </div>

                <h3 class="report-section-title">Screen of the Project</h3>
                <div class="report-placeholder">
                    <p><em>[Insert screenshots of projects worked on]</em></p>
                </div>

                <h3 class="report-section-title">Certificate</h3>
                <div class="report-placeholder">
                    <p><em>[Insert Completion Certificate]</em></p>
                </div>

                <h3 class="report-section-title">Daily Time and Record (DTR)</h3>
                <div class="report-placeholder">
                    <p><em>(Include this section ONLY if you are a 4th Year Student)</em></p>
                    <p><em>[Insert signed DTR forms]</em></p>
                </div>

                <h3 class="report-section-title">Photo Documentation</h3>
                <div class="report-photo-appendix">
                    ${entries.filter(e => e.images && e.images.length > 0).map(entry => `
                        <div class="report-photo-item">
                            <img src="${entry.images[0]}" alt="${escapeHtml(entry.title)}" />
                            <p class="report-photo-caption">Figure: ${escapeHtml(entry.title)}</p>
                            <p class="report-photo-date">${new Date(entry.entry_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

/**
 * Print Download Report
 */
function handlePrintDownloadReport() {
    const printContent = document.getElementById('printReportContent');

    if (!downloadReportCache) {
        showStatus('No report data available', 'error');
        return;
    }

    const { entries, start_date, end_date, student_name } = downloadReportCache;

    printContent.innerHTML = `
            <!-- Cover Page -->
            <div class="print-page print-cover-page">
                <div class="print-cover-content">
                    <h1 class="print-cover-college">ILOCOS SUR POLYTECHNIC STATE COLLEGE</h1>
                    <h2 class="print-cover-campus">Candon Campus</h2>
                    <div class="print-cover-spacer-large"></div>
                    <h1 class="print-cover-title">OJT REPORT</h1>
                    <p class="print-cover-company">(Name of Company/office assigned)</p>
                    <div class="print-cover-spacer-large"></div>
                    <p class="print-cover-name">${student_name}</p>
                    <p class="print-cover-program">Bachelor of Science in Information Technology</p>
                    <p class="print-cover-sy">S.Y. 2025 - 2026</p>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="print-page">
                <h2 class="print-toc-title">Table of Content</h2>
                <div class="print-toc">
                    <div class="print-toc-chapter">
                        <h3>Chapter I Company Profile</h3>
                        <ul>
                            <li>Introduction</li>
                            <li>Duration and time</li>
                            <li>Purpose/Role to the company</li>
                        </ul>
                    </div>
                    <div class="print-toc-chapter">
                        <h3>Chapter II Immersion Documentation</h3>
                        <ul>
                            <li>Background of the action plan</li>
                            <li>Program of Activities – per day</li>
                            <li>Evaluation of Result (4th year only)</li>
                        </ul>
                    </div>
                    <div class="print-toc-chapter">
                        <h3>Chapter III Conclusion and Recommendation</h3>
                        <ul>
                            <li>Conclusion</li>
                            <li>Recommendation</li>
                        </ul>
                    </div>
                    <div class="print-toc-chapter">
                        <h3>Appendix</h3>
                        <ul>
                            <li>Endorsement Letter</li>
                            <li>Screen of the Project</li>
                            <li>Certificate</li>
                            <li>Daily Time and Record DTR (4th year only)</li>
                            <li>Photo Documentation</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Chapter I -->
            <div class="print-page">
                <h2 class="print-chapter-title">Chapter I: Company Profile</h2>
                
                <h3 class="print-section-title">Introduction</h3>
                <div class="print-placeholder">
                    <p><em>[Write the introduction of the company here]</em></p>
                </div>

                <h3 class="print-section-title">Duration and Time</h3>
                <div class="print-placeholder">
                    <p><em>Start Date: ${start_date}</em></p>
                    <p><em>End Date: ${end_date}</em></p>
                    <p><em>Daily Hours: [Specify your daily OJT hours]</em></p>
                </div>

                <h3 class="print-section-title">Purpose/Role to the Company</h3>
                <div class="print-placeholder">
                    <p><em>[Describe your specific role and objectives]</em></p>
                </div>
            </div>

            <!-- Chapter II -->
            <div class="print-page">
                <h2 class="print-chapter-title">Chapter II: Immersion Documentation</h2>
                
                <h3 class="print-section-title">Background of the Action Plan</h3>
                <div class="print-placeholder">
                    <p><em>[Describe the plan you created before starting the immersion]</em></p>
                </div>

                <h3 class="print-section-title">Program of Activities – Per Day</h3>
                <table class="print-activities-table">
                    <thead>
                        <tr>
                            <th>Day/Date</th>
                            <th>Activity</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${entries.map((entry, index) => `
                            <tr>
                                <td>Day ${index + 1}<br>${new Date(entry.entry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                                <td>${escapeHtml(entry.title)}</td>
                                <td>${escapeHtml(entry.user_description || 'No description')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <h3 class="print-section-title">Evaluation of Result</h3>
                <div class="print-placeholder">
                    <p><em>(4th Year Only)</em></p>
                </div>
            </div>

            <!-- Chapter III -->
            <div class="print-page">
                <h2 class="print-chapter-title">Chapter III: Conclusion and Recommendation</h2>
                
                <h3 class="print-section-title">Conclusion</h3>
                <div class="print-placeholder">
                    <p><em>[Summarize your overall experience and learnings]</em></p>
                </div>

                <h3 class="print-section-title">Recommendation</h3>
                <div class="print-placeholder">
                    <p><em>[Provide suggestions for future OJT students, company, school]</em></p>
                </div>
            </div>

            <!-- Appendix -->
            <div class="print-page">
                <h2 class="print-chapter-title">Appendix</h2>
                
                <h3 class="print-section-title">Endorsement Letter</h3>
                <div class="print-placeholder">
                    <p><em>[Insert scanned copy]</em></p>
                </div>

                <h3 class="print-section-title">Screen of the Project</h3>
                <div class="print-placeholder">
                    <p><em>[Insert screenshots]</em></p>
                </div>

                <h3 class="print-section-title">Certificate</h3>
                <div class="print-placeholder">
                    <p><em>[Insert Completion Certificate]</em></p>
                </div>

                <h3 class="print-section-title">Daily Time and Record (DTR)</h3>
                <div class="print-placeholder">
                    <p><em>(4th Year Only)</em></p>
                </div>

                <h3 class="print-section-title">Photo Documentation</h3>
                <div class="print-photo-appendix">
                    ${(() => {
                        const photoEntries = entries.filter(e => e.images && e.images.length > 0);
                        return photoEntries.map((entry, idx) => {
                            if (idx % 2 === 0) {
                                const nextEntry = photoEntries[idx + 1];
                                return `
                                    <div class="print-photo-row">
                                        <div class="print-photo-item">
                                            <img src="${entry.images[0]}" alt="${escapeHtml(entry.title)}" />
                                            <p class="print-photo-caption">Figure: ${escapeHtml(entry.title)}</p>
                                            <p class="print-photo-date">${new Date(entry.entry_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p>
                                        </div>
                                        ${nextEntry ? `
                                        <div class="print-photo-item">
                                            <img src="${nextEntry.images[0]}" alt="${escapeHtml(nextEntry.title)}" />
                                            <p class="print-photo-caption">Figure: ${escapeHtml(nextEntry.title)}</p>
                                            <p class="print-photo-date">${new Date(nextEntry.entry_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p>
                                        </div>
                                        ` : '<div class="print-photo-item"></div>'}
                                    </div>
                                `;
                            }
                            return '';
                        }).join('');
                    })()}
                </div>
            </div>
        `;

    // Give browser time to render content before printing
    setTimeout(() => {
        window.print();
    }, 100);
}

/**
 * Download as Word document
 */
async function handleDownloadWord() {
    if (!downloadReportCache) {
        showStatus('No report data available', 'error');
        return;
    }

    const { entries, start_date, end_date, student_name } = downloadReportCache;

    // Convert images to base64
    const imageCache = {};
    const imagePromises = [];
    
    entries.forEach(entry => {
        if (entry.images && entry.images.length > 0) {
            entry.images.forEach(imgSrc => {
                if (!imageCache[imgSrc]) {
                    imagePromises.push(
                        fetch(imgSrc)
                            .then(res => res.blob())
                            .then(blob => {
                                return new Promise((resolve) => {
                                    const reader = new FileReader();
                                    reader.onloadend = () => {
                                        imageCache[imgSrc] = reader.result;
                                        resolve(reader.result);
                                    };
                                    reader.readAsDataURL(blob);
                                });
                            })
                            .catch(err => {
                                console.error('Error loading image:', imgSrc, err);
                                imageCache[imgSrc] = '';
                            })
                    );
                }
            });
        }
    });

    // Wait for all images to load
    await Promise.all(imagePromises);

    // Create Word document HTML
    const wordHtml = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset='utf-8'>
            <style>
                @page { size: letter; margin: 1in 1in 1in 1.5in; }
                body { font-family: Arial; font-size: 12pt; line-height: 1.5; }
                .cover-page { text-align: center; page-break-after: always; }
                .cover-college { font-size: 16pt; font-weight: bold; }
                .cover-campus { font-size: 14pt; font-weight: bold; margin-bottom: 50px; }
                .cover-title { font-size: 18pt; font-weight: bold; }
                .cover-name { font-size: 12pt; text-transform: uppercase; font-weight: bold; }
                h2 { font-size: 14pt; font-weight: bold; text-align: center; margin-top: 30px; }
                h3 { font-size: 12pt; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11pt; }
                th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; }
                th { background: #f0f0f0; font-weight: bold; }
                .photo-grid { display: table; width: 100%; margin-top: 20px; }
                .photo-row { display: table-row; }
                .photo-item { display: table-cell; width: 50%; padding: 10px; text-align: center; page-break-inside: avoid; }
                .photo-item img { width: 80%; max-width: 200px; max-height: 150px; object-fit: contain; border: 1px solid #999; }
                .photo-caption { text-align: center; font-weight: bold; font-size: 10pt; margin: 8px 0 4px 0; }
                .photo-date { text-align: center; font-size: 9pt; font-style: italic; color: #666; }
                p { margin: 8px 0; }
            </style>
        </head>
        <body>
            <div class="cover-page">
                <h1 class="cover-college">ILOCOS SUR POLYTECHNIC STATE COLLEGE</h1>
                <h2 class="cover-campus">Candon Campus</h2>
                <div style="height: 50px;"></div>
                <h1 class="cover-title">OJT REPORT</h1>
                <p>(Name of Company/office assigned)</p>
                <div style="height: 50px;"></div>
                <p class="cover-name">${student_name}</p>
                <p>Bachelor of Science in Information Technology</p>
                <p>S.Y. 2025 - 2026</p>
            </div>

            <h2>Chapter I: Company Profile</h2>
            <h3>Introduction</h3>
            <p><em>[Write the introduction of the company here]</em></p>
            <h3>Duration and Time</h3>
            <p>Start Date: ${start_date}</p>
            <p>End Date: ${end_date}</p>
            <p><em>Daily Hours: [Specify your daily OJT hours]</em></p>
            <h3>Purpose/Role to the Company</h3>
            <p><em>[Describe your specific role and objectives]</em></p>

            <h2>Chapter II: Immersion Documentation</h2>
            <h3>Background of the Action Plan</h3>
            <p><em>[Describe the plan you created before starting the immersion]</em></p>
            <h3>Program of Activities – Per Day</h3>
            <table>
                <thead>
                    <tr><th>Day/Date</th><th>Activity</th><th>Remarks</th></tr>
                </thead>
                <tbody>
                    ${entries.map((entry, i) => `
                        <tr>
                            <td style="width: 15%;">Day ${i + 1}<br>${new Date(entry.entry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                            <td style="width: 35%;">${escapeHtml(entry.title)}</td>
                            <td style="width: 50%;">${escapeHtml(entry.user_description || 'No description')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <h2>Chapter III: Conclusion and Recommendation</h2>
            <h3>Conclusion</h3>
            <p><em>[Summarize your overall experience and learnings]</em></p>
            <h3>Recommendation</h3>
            <p><em>[Provide suggestions for future OJT students, company, school]</em></p>

            <h2>Appendix: Photo Documentation</h2>
            <div class="photo-grid">
                ${entries.filter(e => e.images && e.images.length > 0).map((entry, idx) => {
                    if (idx % 2 === 0) {
                        const nextEntry = entries.filter(e => e.images && e.images.length > 0)[idx + 1];
                        return `
                            <div class="photo-row">
                                <div class="photo-item">
                                    <img src="${imageCache[entry.images[0]] || ''}" alt="${escapeHtml(entry.title)}" />
                                    <p class="photo-caption">Figure: ${escapeHtml(entry.title)}</p>
                                    <p class="photo-date">${new Date(entry.entry_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p>
                                </div>
                                ${nextEntry ? `
                                <div class="photo-item">
                                    <img src="${imageCache[nextEntry.images[0]] || ''}" alt="${escapeHtml(nextEntry.title)}" />
                                    <p class="photo-caption">Figure: ${escapeHtml(nextEntry.title)}</p>
                                    <p class="photo-date">${new Date(nextEntry.entry_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</p>
                                </div>
                                ` : '<div class="photo-item"></div>'}
                            </div>
                        `;
                    }
                    return '';
                }).join('')}
            </div>
        </body>
        </html>
    `;

    const blob = new Blob(['\ufeff', wordHtml], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `OJT_Report_${student_name.replace(/\s+/g, '_')}.doc`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * Download as PDF using browser print
 */
function handleDownloadPdf() {
    if (!downloadReportCache) {
        showStatus('No report data available', 'error');
        return;
    }

    // Trigger print and user can select "Save as PDF"
    handlePrintDownloadReport();
}
