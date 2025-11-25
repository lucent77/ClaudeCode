/**
 * Magic Rx Scanner - Frontend JavaScript
 */

let currentPrescriptionId = null;

// Initialize drag and drop for document
const documentDropZone = document.getElementById('documentDropZone');
const documentInput = document.getElementById('documentInput');
const documentPreview = document.getElementById('documentPreview');

// Initialize drag and drop for template
const templateDropZone = document.getElementById('templateDropZone');
const templateInput = document.getElementById('templateInput');
const templatePreview = document.getElementById('templatePreview');

// Form elements
const uploadForm = document.getElementById('uploadForm');
const loadingSection = document.getElementById('loadingSection');
const resultsSection = document.getElementById('resultsSection');

/**
 * Drag and drop handlers for document
 */
documentDropZone.addEventListener('click', () => documentInput.click());

documentDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    documentDropZone.classList.add('dragover');
});

documentDropZone.addEventListener('dragleave', () => {
    documentDropZone.classList.remove('dragover');
});

documentDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    documentDropZone.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        documentInput.files = files;
        displayDocumentPreview(files[0]);
    }
});

documentInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        displayDocumentPreview(e.target.files[0]);
    }
});

/**
 * Drag and drop handlers for template
 */
templateDropZone.addEventListener('click', () => templateInput.click());

templateDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    templateDropZone.classList.add('dragover');
});

templateDropZone.addEventListener('dragleave', () => {
    templateDropZone.classList.remove('dragover');
});

templateDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    templateDropZone.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        templateInput.files = files;
        displayTemplatePreview(files[0]);
    }
});

templateInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        displayTemplatePreview(e.target.files[0]);
    }
});

/**
 * Display document preview
 */
function displayDocumentPreview(file) {
    document.getElementById('documentFileName').textContent = file.name;
    document.getElementById('documentFileSize').textContent = formatFileSize(file.size);
    documentPreview.classList.remove('hidden');
    documentDropZone.classList.add('hidden');
}

/**
 * Display template preview
 */
function displayTemplatePreview(file) {
    document.getElementById('templateFileName').textContent = file.name;
    document.getElementById('templateFileSize').textContent = formatFileSize(file.size);
    templatePreview.classList.remove('hidden');
    templateDropZone.classList.add('hidden');
}

/**
 * Clear document
 */
function clearDocument() {
    documentInput.value = '';
    documentPreview.classList.add('hidden');
    documentDropZone.classList.remove('hidden');
}

/**
 * Clear template
 */
function clearTemplate() {
    templateInput.value = '';
    templatePreview.classList.add('hidden');
    templateDropZone.classList.remove('hidden');
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Handle form submission
 */
uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validate document file
    if (!documentInput.files || documentInput.files.length === 0) {
        showAlert('작성된 처방전 파일을 선택해주세요.', 'error');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('document', documentInput.files[0]);

    if (templateInput.files && templateInput.files.length > 0) {
        formData.append('template', templateInput.files[0]);
    }

    try {
        // Show loading
        loadingSection.classList.remove('hidden');
        resultsSection.classList.add('hidden');

        // Upload files
        const uploadResponse = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });

        const uploadData = await uploadResponse.json();

        if (!uploadData.success) {
            throw new Error(uploadData.message || '파일 업로드에 실패했습니다.');
        }

        currentPrescriptionId = uploadData.data.prescription_id;

        // Start analysis
        const analyzeResponse = await fetch('api/analyze.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                prescription_id: currentPrescriptionId
            })
        });

        const analyzeData = await analyzeResponse.json();

        if (!analyzeData.success) {
            throw new Error(analyzeData.message || '분석에 실패했습니다.');
        }

        // Display results
        displayResults(analyzeData.data);

    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        loadingSection.classList.add('hidden');
    }
});

/**
 * Display analysis results
 */
function displayResults(data) {
    resultsSection.classList.remove('hidden');

    // Set analysis mode badge
    const modeBadge = document.getElementById('analysisModeBadge');
    if (data.mode === 'handwriting_extraction') {
        modeBadge.textContent = '필기 추출 모드';
        modeBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800';

        // Show handwriting results
        document.getElementById('handwritingResults').classList.remove('hidden');
        document.getElementById('documentAIResults').classList.add('hidden');

        if (data.handwriting) {
            document.getElementById('handwritingImage').src = data.handwriting.image;
            document.getElementById('handwritingText').textContent = data.handwriting.text || '텍스트를 인식할 수 없습니다.';
            document.getElementById('handwritingConfidence').textContent = (data.handwriting.confidence || 0) + '%';
        }
    } else {
        modeBadge.textContent = 'Document AI 모드';
        modeBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800';

        // Show Document AI results
        document.getElementById('handwritingResults').classList.add('hidden');
        document.getElementById('documentAIResults').classList.remove('hidden');

        if (data.document_ai) {
            document.getElementById('fullText').textContent = data.document_ai.text || '텍스트를 인식할 수 없습니다.';

            // Display fields
            const fieldsContainer = document.getElementById('fieldsContainer');
            fieldsContainer.innerHTML = '';

            if (data.document_ai.fields && data.document_ai.fields.length > 0) {
                data.document_ai.fields.forEach(field => {
                    const fieldCard = document.createElement('div');
                    fieldCard.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4';
                    fieldCard.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-700 mb-1">${escapeHtml(field.name)}</p>
                                <p class="text-gray-800">${escapeHtml(field.value)}</p>
                            </div>
                            <span class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getConfidenceClass(field.confidence)}">
                                ${field.confidence}%
                            </span>
                        </div>
                    `;
                    fieldsContainer.appendChild(fieldCard);
                });
            } else {
                fieldsContainer.innerHTML = '<p class="text-gray-600">추출된 필드가 없습니다.</p>';
            }
        }
    }

    // Scroll to results
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Get confidence color class
 */
function getConfidenceClass(confidence) {
    if (confidence >= 80) return 'bg-green-100 text-green-800';
    if (confidence >= 60) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
}

/**
 * Download results
 */
async function downloadResults(format) {
    if (!currentPrescriptionId) {
        showAlert('다운로드할 결과가 없습니다.', 'error');
        return;
    }

    try {
        const url = `api/download.php?prescription_id=${currentPrescriptionId}&format=${format}`;
        window.open(url, '_blank');
    } catch (error) {
        console.error('Download error:', error);
        showAlert('다운로드에 실패했습니다.', 'error');
    }
}

/**
 * Reset form
 */
function resetForm() {
    uploadForm.reset();
    clearDocument();
    clearTemplate();
    resultsSection.classList.add('hidden');
    currentPrescriptionId = null;

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg ${
        type === 'error' ? 'bg-red-100 border border-red-400 text-red-800' : 'bg-blue-100 border border-blue-400 text-blue-800'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <p class="font-medium">${escapeHtml(message)}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-600 hover:text-gray-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
