<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지 - 경품 추첨</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;900&display=swap');

        body {
            font-family: 'Noto Sans KR', sans-serif;
        }

        .tab-active {
            border-bottom: 3px solid #8B5CF6;
            color: #8B5CF6;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 헤더 -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">
                    🎁 경품 추첨 관리자
                </h1>
                <a href="/" class="text-purple-600 hover:text-purple-700 font-medium">
                    ← 메인 페이지로
                </a>
            </div>
        </div>
    </header>

    <!-- 탭 메뉴 -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="container mx-auto px-4">
            <nav class="flex space-x-8">
                <button
                    onclick="switchTab('prizes')"
                    class="tab-button py-4 px-2 font-medium text-gray-600 hover:text-purple-600 transition-colors tab-active"
                    data-tab="prizes"
                >
                    상품 관리
                </button>
                <button
                    onclick="switchTab('winners')"
                    class="tab-button py-4 px-2 font-medium text-gray-600 hover:text-purple-600 transition-colors"
                    data-tab="winners"
                >
                    당첨자 목록
                </button>
                <button
                    onclick="switchTab('statistics')"
                    class="tab-button py-4 px-2 font-medium text-gray-600 hover:text-purple-600 transition-colors"
                    data-tab="statistics"
                >
                    통계
                </button>
            </nav>
        </div>
    </div>

    <!-- 메인 컨텐츠 -->
    <main class="container mx-auto px-4 py-8">
        <!-- 상품 관리 탭 -->
        <div id="prizesTab" class="tab-content">
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">등록된 상품</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상품명</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">확률 (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">색상</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">재고</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                            </tr>
                        </thead>
                        <tbody id="prizesTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
                                    데이터를 불러오는 중...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="prizesTotalProbability" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>총 확률 합계:</strong> <span id="totalProbText">계산 중...</span>
                    </p>
                    <p class="text-xs text-blue-600 mt-1">
                        ⚠️ 확률 합계는 100%여야 정상적으로 작동합니다.
                    </p>
                </div>
            </div>
        </div>

        <!-- 당첨자 목록 탭 -->
        <div id="winnersTab" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">당첨자 목록</h2>
                    <button
                        onclick="exportWinners()"
                        class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                    >
                        📥 CSV 다운로드
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">번호</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">이메일</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">당첨 상품</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">추첨 일시</th>
                            </tr>
                        </thead>
                        <tbody id="winnersTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
                                    데이터를 불러오는 중...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="winnersPagination" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        총 <span id="totalWinners">0</span>명의 당첨자
                    </div>
                    <div class="flex space-x-2">
                        <button
                            onclick="loadWinners(Math.max(0, currentOffset - 50))"
                            id="prevButton"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            이전
                        </button>
                        <button
                            onclick="loadWinners(currentOffset + 50)"
                            id="nextButton"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            다음
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 통계 탭 -->
        <div id="statisticsTab" class="tab-content hidden">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">총 추첨 참여자</h3>
                    <p class="text-4xl font-bold text-purple-600" id="statTotalWinners">-</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">등록된 상품</h3>
                    <p class="text-4xl font-bold text-blue-600" id="statTotalPrizes">-</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">오늘 참여자</h3>
                    <p class="text-4xl font-bold text-green-600" id="statTodayWinners">-</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">상품별 당첨 통계</h3>
                <div id="prizeStatistics" class="space-y-4">
                    <p class="text-center text-gray-500 py-8">데이터를 불러오는 중...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="/assets/js/raffle.js"></script>
    <script>
        let currentOffset = 0;
        let allWinners = [];

        // 탭 전환
        function switchTab(tabName) {
            // 모든 탭 컨텐츠 숨기기
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });

            // 모든 탭 버튼 비활성화
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('tab-active');
            });

            // 선택된 탭 표시
            document.getElementById(tabName + 'Tab').classList.remove('hidden');
            document.getElementById(tabName + 'Tab').classList.add('fade-in');
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('tab-active');

            // 탭별 데이터 로드
            if (tabName === 'prizes') {
                loadPrizes();
            } else if (tabName === 'winners') {
                loadWinners(0);
            } else if (tabName === 'statistics') {
                loadStatistics();
            }
        }

        // 상품 목록 로드
        async function loadPrizes() {
            try {
                const data = await RaffleAPI.getPrizes();
                const tbody = document.getElementById('prizesTableBody');

                if (data.prizes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">등록된 상품이 없습니다.</td></tr>';
                    return;
                }

                tbody.innerHTML = data.prizes.map(prize => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${prize.id}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">${prize.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-600">${prize.probability}%</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded border border-gray-300" style="background-color: ${prize.color}"></div>
                                <span class="text-sm text-gray-600">${prize.color}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${prize.stock === -1 ? '무제한' : prize.stock}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${prize.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${prize.is_active ? '활성' : '비활성'}
                            </span>
                        </td>
                    </tr>
                `).join('');

                // 확률 합계 표시
                const totalProb = data.total_probability;
                const totalProbText = document.getElementById('totalProbText');
                totalProbText.textContent = `${totalProb}%`;

                if (totalProb !== 100) {
                    totalProbText.classList.add('text-red-600', 'font-bold');
                } else {
                    totalProbText.classList.remove('text-red-600', 'font-bold');
                    totalProbText.classList.add('text-green-600', 'font-bold');
                }

            } catch (error) {
                console.error('Error loading prizes:', error);
                alert('상품 목록을 불러오는데 실패했습니다.');
            }
        }

        // 당첨자 목록 로드
        async function loadWinners(offset = 0) {
            try {
                const data = await RaffleAPI.getWinners(50, offset);
                allWinners = data.winners;
                currentOffset = offset;

                const tbody = document.getElementById('winnersTableBody');

                if (data.winners.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">당첨자가 없습니다.</td></tr>';
                    return;
                }

                tbody.innerHTML = data.winners.map((winner, index) => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${offset + index + 1}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">${winner.email}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded" style="background-color: ${winner.prize_color || '#6B7280'}"></div>
                                <span class="text-gray-900">${winner.prize_name}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            ${new Date(winner.drawn_at).toLocaleString('ko-KR')}
                        </td>
                    </tr>
                `).join('');

                // 페이지네이션 업데이트
                document.getElementById('totalWinners').textContent = data.total;
                document.getElementById('prevButton').disabled = offset === 0;
                document.getElementById('nextButton').disabled = offset + 50 >= data.total;

            } catch (error) {
                console.error('Error loading winners:', error);
                alert('당첨자 목록을 불러오는데 실패했습니다.');
            }
        }

        // 통계 로드
        async function loadStatistics() {
            try {
                const [prizesData, winnersData] = await Promise.all([
                    RaffleAPI.getPrizes(),
                    RaffleAPI.getWinners(1000, 0)
                ]);

                // 기본 통계
                document.getElementById('statTotalWinners').textContent = winnersData.total;
                document.getElementById('statTotalPrizes').textContent = prizesData.prizes.length;

                // 오늘 참여자 계산
                const today = new Date().toDateString();
                const todayWinners = winnersData.winners.filter(w => {
                    return new Date(w.drawn_at).toDateString() === today;
                }).length;
                document.getElementById('statTodayWinners').textContent = todayWinners;

                // 상품별 당첨 통계
                const prizeStats = {};
                winnersData.winners.forEach(winner => {
                    if (!prizeStats[winner.prize_name]) {
                        prizeStats[winner.prize_name] = 0;
                    }
                    prizeStats[winner.prize_name]++;
                });

                const statsContainer = document.getElementById('prizeStatistics');
                const sortedStats = Object.entries(prizeStats).sort((a, b) => b[1] - a[1]);

                if (sortedStats.length === 0) {
                    statsContainer.innerHTML = '<p class="text-center text-gray-500">아직 당첨자가 없습니다.</p>';
                } else {
                    statsContainer.innerHTML = sortedStats.map(([prizeName, count]) => {
                        const percentage = ((count / winnersData.total) * 100).toFixed(2);
                        return `
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-gray-800">${prizeName}</span>
                                    <span class="text-purple-600 font-bold">${count}명</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: ${percentage}%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${percentage}%</p>
                            </div>
                        `;
                    }).join('');
                }

            } catch (error) {
                console.error('Error loading statistics:', error);
                alert('통계를 불러오는데 실패했습니다.');
            }
        }

        // CSV 내보내기
        async function exportWinners() {
            try {
                const data = await RaffleAPI.getWinners(10000, 0);

                // CSV 헤더
                let csv = '\uFEFF'; // UTF-8 BOM
                csv += '번호,이메일,당첨상품,추첨일시\n';

                // CSV 데이터
                data.winners.forEach((winner, index) => {
                    csv += `${index + 1},"${winner.email}","${winner.prize_name}","${new Date(winner.drawn_at).toLocaleString('ko-KR')}"\n`;
                });

                // 다운로드
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `당첨자목록_${new Date().toISOString().split('T')[0]}.csv`;
                link.click();

            } catch (error) {
                console.error('Error exporting winners:', error);
                alert('CSV 내보내기에 실패했습니다.');
            }
        }

        // 페이지 로드 시 상품 목록 로드
        document.addEventListener('DOMContentLoaded', () => {
            loadPrizes();
        });
    </script>
</body>
</html>
