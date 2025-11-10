<?php
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">데이터 업로드</h2>
    <p class="text-gray-600 mt-1">CSV 파일을 업로드하여 공구 및 생산 데이터를 일괄 입력</p>
</div>

<!-- Upload Forms -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Tool CSV Upload -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
            </svg>
            공구 데이터 (Tool.csv)
        </h3>

        <div class="mb-4 p-4 bg-blue-50 rounded-lg text-sm text-gray-700">
            <p class="font-semibold mb-2">필수 CSV 컬럼:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <li>Tool Code - 공구 코드</li>
                <li>Tool Name - 공구 이름</li>
                <li>Category Name - 카테고리</li>
                <li>Tool Size - 크기</li>
                <li>Supplier Name - 공급업체</li>
                <li>Supplier Model Number - 모델 번호</li>
                <li>Current Stock - 현재 재고</li>
                <li>Minimum Stock - 최소 재고</li>
                <li>Lifespan Type - 수명 타입 (time/cycles/distance)</li>
                <li>Lifespan Limit - 수명 한계</li>
                <li>Description - 설명</li>
            </ul>
        </div>

        <form id="toolUploadForm" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">CSV 파일 선택</label>
                <input
                    type="file"
                    name="tool_csv"
                    accept=".csv"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <button
                type="submit"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
            >
                공구 데이터 업로드
            </button>
        </form>

        <div id="toolUploadResult" class="mt-4"></div>
    </div>

    <!-- Production CSV Upload -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            생산 데이터 (Swiss.csv)
        </h3>

        <div class="mb-4 p-4 bg-green-50 rounded-lg text-sm text-gray-700">
            <p class="font-semibold mb-2">필수 CSV 컬럼:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <li>DATE - 작업 일자</li>
                <li>MODEL NAME - 모델명</li>
                <li>SKU - SKU</li>
                <li>LOT NO - LOT 번호</li>
                <li>WORKER - 작업자</li>
                <li>CNC - 장비 코드 (MP1, MP2, etc.)</li>
                <li>CNC Run Time - CNC 가동 시간</li>
                <li>PLAN - 계획 수량</li>
                <li>UNIT TOTAL - 총 생산 수량</li>
                <li>% - 달성률</li>
            </ul>
        </div>

        <form id="productionUploadForm" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">CSV 파일 선택</label>
                <input
                    type="file"
                    name="production_csv"
                    accept=".csv"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                >
            </div>

            <button
                type="submit"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold"
            >
                생산 데이터 업로드
            </button>
        </form>

        <div id="productionUploadResult" class="mt-4"></div>
    </div>
</div>

<script>
// Tool CSV Upload
document.getElementById('toolUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const resultDiv = document.getElementById('toolUploadResult');
    const submitButton = this.querySelector('button[type="submit"]');

    submitButton.disabled = true;
    submitButton.textContent = '업로드 중...';
    resultDiv.innerHTML = '<div class="p-4 bg-blue-50 rounded-lg text-blue-700">업로드 진행 중...</div>';

    try {
        const response = await fetch('api/upload_tools_csv.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <p class="font-semibold text-green-800">${data.message}</p>
                    ${data.data ? `<p class="text-sm text-green-700 mt-1">추가: ${data.data.inserted}개, 업데이트: ${data.data.updated}개</p>` : ''}
                </div>
            `;
            this.reset();
            setTimeout(() => {
                location.href = 'tools.php';
            }, 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="font-semibold text-red-800">오류: ${data.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                <p class="font-semibold text-red-800">업로드 중 오류가 발생했습니다.</p>
            </div>
        `;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = '공구 데이터 업로드';
    }
});

// Production CSV Upload
document.getElementById('productionUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const resultDiv = document.getElementById('productionUploadResult');
    const submitButton = this.querySelector('button[type="submit"]');

    submitButton.disabled = true;
    submitButton.textContent = '업로드 중...';
    resultDiv.innerHTML = '<div class="p-4 bg-green-50 rounded-lg text-green-700">업로드 진행 중...</div>';

    try {
        const response = await fetch('api/upload_production_csv.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <p class="font-semibold text-green-800">${data.message}</p>
                    ${data.data ? `<p class="text-sm text-green-700 mt-1">처리된 행: ${data.data.processed}개</p>` : ''}
                </div>
            `;
            this.reset();
            setTimeout(() => {
                location.href = 'production.php';
            }, 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="font-semibold text-red-800">오류: ${data.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                <p class="font-semibold text-red-800">업로드 중 오류가 발생했습니다.</p>
            </div>
        `;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = '생산 데이터 업로드';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
