<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Rx Scanner - AI 처방전 스캐너</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .drop-zone {
            transition: all 0.3s ease;
        }
        .drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .spinner {
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-left-color: #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .result-card {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-prescription-bottle-alt text-4xl text-blue-600"></i>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Magic Rx Scanner</h1>
                        <p class="text-sm text-gray-600">AI 기반 치과 처방전 분석 시스템</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 mr-2 bg-green-400 rounded-full"></span>
                        시스템 정상
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Instructions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                사용 방법
            </h2>
            <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">1</span>
                    <div>
                        <p class="font-semibold">작성된 처방전 업로드</p>
                        <p class="text-gray-600">환자가 작성한 처방전 이미지를 선택하세요</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">2</span>
                    <div>
                        <p class="font-semibold">빈 템플릿 업로드 (선택사항)</p>
                        <p class="text-gray-600">필기만 추출하려면 빈 템플릿을 추가하세요</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">3</span>
                    <div>
                        <p class="font-semibold">AI 분석 시작</p>
                        <p class="text-gray-600">Google AI가 자동으로 텍스트를 추출합니다</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">4</span>
                    <div>
                        <p class="font-semibold">결과 확인 및 다운로드</p>
                        <p class="text-gray-600">추출된 데이터를 확인하고 CSV/JSON으로 내보내기</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-upload text-blue-600 mr-2"></i>
                파일 업로드
            </h2>

            <form id="uploadForm">
                <!-- Document Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        작성된 처방전 <span class="text-red-500">*</span>
                    </label>
                    <div id="documentDropZone" class="drop-zone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400">
                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 mb-2">파일을 드래그 앤 드롭하거나 클릭하여 선택하세요</p>
                        <p class="text-sm text-gray-500">JPG, PNG (최대 10MB)</p>
                        <input type="file" id="documentInput" name="document" accept="image/jpeg,image/png,image/jpg" class="hidden" required>
                    </div>
                    <div id="documentPreview" class="mt-3 hidden">
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-file-image text-blue-600 text-2xl"></i>
                                <div>
                                    <p id="documentFileName" class="text-sm font-medium text-gray-800"></p>
                                    <p id="documentFileSize" class="text-xs text-gray-500"></p>
                                </div>
                            </div>
                            <button type="button" onclick="clearDocument()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Template Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        빈 템플릿 (선택사항)
                    </label>
                    <div id="templateDropZone" class="drop-zone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-green-400">
                        <i class="fas fa-file-alt text-5xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 mb-2">필기 추출을 위한 빈 템플릿 선택 (선택사항)</p>
                        <p class="text-sm text-gray-500">JPG, PNG (최대 10MB)</p>
                        <input type="file" id="templateInput" name="template" accept="image/jpeg,image/png,image/jpg" class="hidden">
                    </div>
                    <div id="templatePreview" class="mt-3 hidden">
                        <div class="flex items-center justify-between bg-green-50 p-3 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-file-image text-green-600 text-2xl"></i>
                                <div>
                                    <p id="templateFileName" class="text-sm font-medium text-gray-800"></p>
                                    <p id="templateFileSize" class="text-xs text-gray-500"></p>
                                </div>
                            </div>
                            <button type="button" onclick="clearTemplate()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="analyzeBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                    <i class="fas fa-magic"></i>
                    <span>AI 분석 시작</span>
                </button>
            </form>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingSection" class="hidden bg-white rounded-lg shadow-md p-8 mb-8">
            <div class="flex flex-col items-center justify-center">
                <div class="spinner mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">분석 중...</h3>
                <p class="text-gray-600 text-center">AI가 처방전을 분석하고 있습니다. 잠시만 기다려주세요.</p>
                <div class="mt-4 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full animate-pulse" style="width: 70%"></div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="hidden">
            <div class="bg-white rounded-lg shadow-md p-6 result-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        분석 결과
                    </h2>
                    <div class="flex space-x-2">
                        <button onclick="downloadResults('json')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm flex items-center space-x-2">
                            <i class="fas fa-download"></i>
                            <span>JSON</span>
                        </button>
                        <button onclick="downloadResults('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm flex items-center space-x-2">
                            <i class="fas fa-file-csv"></i>
                            <span>CSV</span>
                        </button>
                    </div>
                </div>

                <!-- Analysis Mode Badge -->
                <div class="mb-4">
                    <span id="analysisModeBadge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"></span>
                </div>

                <!-- Handwriting Results -->
                <div id="handwritingResults" class="hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">추출된 필기 이미지</h3>
                        <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                            <img id="handwritingImage" class="max-w-full h-auto mx-auto" alt="Extracted Handwriting">
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">인식된 텍스트</h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p id="handwritingText" class="text-gray-800 whitespace-pre-wrap"></p>
                            <div class="mt-3 flex items-center text-sm text-gray-600">
                                <i class="fas fa-chart-line mr-2"></i>
                                <span>신뢰도: <strong id="handwritingConfidence">0%</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document AI Results -->
                <div id="documentAIResults" class="hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">전체 텍스트</h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p id="fullText" class="text-gray-800 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">필드 데이터</h3>
                        <div id="fieldsContainer" class="space-y-3"></div>
                    </div>
                </div>

                <!-- Reset Button -->
                <button onclick="resetForm()" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center space-x-2 mt-6">
                    <i class="fas fa-redo"></i>
                    <span>새로운 분석 시작</span>
                </button>
            </div>
        </div>
    </main>

    <footer class="bg-white shadow-md mt-12">
        <div class="container mx-auto px-4 py-6 text-center text-gray-600 text-sm">
            <p>&copy; 2025 Magic Rx Scanner. Powered by Google Cloud AI.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
