/**
 * popup.js - Google Sheets 확장 프로그램 팝업 로직
 *
 * 이 파일은 팝업 UI의 모든 동작을 처리합니다:
 * - Google 로그인/로그아웃
 * - 데이터 입력 폼 처리
 * - Google Sheets API 호출
 */

// ===== 전역 변수 =====
let accessToken = null;  // Google API 액세스 토큰 저장

// ===== DOM 요소 참조 =====
const elements = {
  // 상태 표시
  status: document.getElementById('status'),

  // 로그인 관련
  loginSection: document.getElementById('loginSection'),
  loginBtn: document.getElementById('loginBtn'),

  // 메인 폼
  mainForm: document.getElementById('mainForm'),
  spreadsheetId: document.getElementById('spreadsheetId'),
  sheetName: document.getElementById('sheetName'),
  dataInput: document.getElementById('dataInput'),

  // 버튼들
  appendBtn: document.getElementById('appendBtn'),
  readBtn: document.getElementById('readBtn'),
  logoutBtn: document.getElementById('logoutBtn'),

  // 로그
  logContent: document.getElementById('logContent')
};

// ===== 초기화 =====
document.addEventListener('DOMContentLoaded', () => {
  log('확장 프로그램 초기화...');

  // 저장된 설정 불러오기
  loadSavedSettings();

  // 로그인 상태 확인
  checkAuthStatus();

  // 이벤트 리스너 등록
  setupEventListeners();
});

/**
 * 이벤트 리스너 설정
 * 각 버튼에 클릭 이벤트를 연결합니다
 */
function setupEventListeners() {
  // 로그인 버튼
  elements.loginBtn.addEventListener('click', handleLogin);

  // 데이터 추가 버튼
  elements.appendBtn.addEventListener('click', handleAppendData);

  // 데이터 읽기 버튼
  elements.readBtn.addEventListener('click', handleReadData);

  // 로그아웃 버튼
  elements.logoutBtn.addEventListener('click', handleLogout);

  // 스프레드시트 ID 변경 시 저장
  elements.spreadsheetId.addEventListener('change', saveSettings);
  elements.sheetName.addEventListener('change', saveSettings);
}

// ===== 인증 관련 함수들 =====

/**
 * 현재 로그인 상태를 확인합니다
 * Chrome의 identity API를 사용하여 토큰 존재 여부 확인
 */
function checkAuthStatus() {
  log('로그인 상태 확인 중...');

  // Chrome identity API로 캐시된 토큰 확인
  chrome.identity.getAuthToken({ interactive: false }, (token) => {
    if (chrome.runtime.lastError) {
      log('로그인 필요: ' + chrome.runtime.lastError.message);
      showLoginSection();
      return;
    }

    if (token) {
      accessToken = token;
      log('기존 로그인 세션 발견!');
      showMainForm();
    } else {
      log('로그인이 필요합니다');
      showLoginSection();
    }
  });
}

/**
 * Google 로그인 처리
 * OAuth 2.0 인증 플로우를 시작합니다
 */
function handleLogin() {
  log('Google 로그인 시작...');
  showStatus('로그인 중...', 'info');

  // interactive: true로 설정하면 로그인 팝업이 표시됩니다
  chrome.identity.getAuthToken({ interactive: true }, (token) => {
    if (chrome.runtime.lastError) {
      log('로그인 실패: ' + chrome.runtime.lastError.message);
      showStatus('로그인 실패: ' + chrome.runtime.lastError.message, 'error');
      return;
    }

    if (token) {
      accessToken = token;
      log('로그인 성공!');
      showStatus('로그인 성공!', 'success');
      showMainForm();

      // 2초 후 상태 메시지 숨기기
      setTimeout(() => hideStatus(), 2000);
    }
  });
}

/**
 * 로그아웃 처리
 * 토큰을 무효화하고 로그인 화면으로 돌아갑니다
 */
function handleLogout() {
  log('로그아웃 중...');

  if (accessToken) {
    // 토큰 무효화
    chrome.identity.removeCachedAuthToken({ token: accessToken }, () => {
      accessToken = null;
      log('로그아웃 완료');
      showStatus('로그아웃되었습니다', 'info');
      showLoginSection();
    });
  } else {
    showLoginSection();
  }
}

// ===== Google Sheets API 함수들 =====

/**
 * 데이터 추가 처리
 * 입력된 데이터를 파싱하여 스프레드시트에 추가합니다
 */
async function handleAppendData() {
  // 입력값 검증
  const spreadsheetId = elements.spreadsheetId.value.trim();
  const sheetName = elements.sheetName.value.trim();
  const dataText = elements.dataInput.value.trim();

  if (!spreadsheetId) {
    showStatus('스프레드시트 ID를 입력해주세요', 'error');
    return;
  }

  if (!dataText) {
    showStatus('입력할 데이터를 입력해주세요', 'error');
    return;
  }

  // 데이터 파싱 (각 줄을 행으로, 쉼표로 열 구분)
  const rows = dataText.split('\n').map(line =>
    line.split(',').map(cell => cell.trim())
  );

  log(`파싱된 데이터: ${rows.length}행`);

  // 버튼 비활성화
  elements.appendBtn.disabled = true;
  elements.appendBtn.innerHTML = '<span class="loading"></span>전송 중...';

  try {
    // Google Sheets API 호출
    const result = await appendToSheet(spreadsheetId, sheetName, rows);

    log('데이터 추가 성공!');
    showStatus(`${rows.length}개 행이 추가되었습니다!`, 'success');

    // 입력창 초기화
    elements.dataInput.value = '';

  } catch (error) {
    log('에러: ' + error.message);
    showStatus('데이터 추가 실패: ' + error.message, 'error');
  } finally {
    // 버튼 복원
    elements.appendBtn.disabled = false;
    elements.appendBtn.textContent = '데이터 추가하기';
  }
}

/**
 * Google Sheets API를 호출하여 데이터를 추가합니다
 *
 * @param {string} spreadsheetId - 스프레드시트 ID
 * @param {string} sheetName - 시트 이름
 * @param {Array} values - 추가할 데이터 (2차원 배열)
 */
async function appendToSheet(spreadsheetId, sheetName, values) {
  // 토큰 확인
  if (!accessToken) {
    throw new Error('로그인이 필요합니다');
  }

  // API 엔드포인트 구성
  const range = `${sheetName}!A:Z`;  // A열부터 Z열까지
  const url = `https://sheets.googleapis.com/v4/spreadsheets/${spreadsheetId}/values/${encodeURIComponent(range)}:append?valueInputOption=USER_ENTERED`;

  log('API 호출: ' + url);

  // API 요청
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${accessToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      values: values
    })
  });

  // 응답 처리
  if (!response.ok) {
    const errorData = await response.json();

    // 토큰 만료 시 재인증 시도
    if (response.status === 401) {
      log('토큰 만료 - 재인증 필요');
      chrome.identity.removeCachedAuthToken({ token: accessToken }, () => {
        accessToken = null;
        showLoginSection();
      });
      throw new Error('인증이 만료되었습니다. 다시 로그인해주세요.');
    }

    throw new Error(errorData.error?.message || '알 수 없는 오류');
  }

  return await response.json();
}

/**
 * 스프레드시트에서 데이터를 읽어옵니다
 */
async function handleReadData() {
  const spreadsheetId = elements.spreadsheetId.value.trim();
  const sheetName = elements.sheetName.value.trim();

  if (!spreadsheetId) {
    showStatus('스프레드시트 ID를 입력해주세요', 'error');
    return;
  }

  // 버튼 비활성화
  elements.readBtn.disabled = true;
  elements.readBtn.innerHTML = '<span class="loading"></span>읽는 중...';

  try {
    const data = await readFromSheet(spreadsheetId, sheetName);

    if (data.values && data.values.length > 0) {
      log(`읽은 데이터: ${data.values.length}행`);

      // 데이터를 로그에 표시
      const preview = data.values.slice(0, 5).map(row => row.join(', ')).join('\n');
      log('데이터 미리보기:\n' + preview);

      showStatus(`${data.values.length}개 행을 읽었습니다 (로그 확인)`, 'success');
    } else {
      log('데이터가 비어있습니다');
      showStatus('시트에 데이터가 없습니다', 'info');
    }

  } catch (error) {
    log('에러: ' + error.message);
    showStatus('데이터 읽기 실패: ' + error.message, 'error');
  } finally {
    // 버튼 복원
    elements.readBtn.disabled = false;
    elements.readBtn.textContent = '현재 데이터 읽기';
  }
}

/**
 * Google Sheets API를 호출하여 데이터를 읽습니다
 */
async function readFromSheet(spreadsheetId, sheetName) {
  if (!accessToken) {
    throw new Error('로그인이 필요합니다');
  }

  const range = `${sheetName}!A:Z`;
  const url = `https://sheets.googleapis.com/v4/spreadsheets/${spreadsheetId}/values/${encodeURIComponent(range)}`;

  log('API 호출: GET ' + url);

  const response = await fetch(url, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${accessToken}`
    }
  });

  if (!response.ok) {
    const errorData = await response.json();

    if (response.status === 401) {
      chrome.identity.removeCachedAuthToken({ token: accessToken }, () => {
        accessToken = null;
        showLoginSection();
      });
      throw new Error('인증이 만료되었습니다. 다시 로그인해주세요.');
    }

    throw new Error(errorData.error?.message || '알 수 없는 오류');
  }

  return await response.json();
}

// ===== UI 헬퍼 함수들 =====

/**
 * 로그인 섹션 표시
 */
function showLoginSection() {
  elements.loginSection.style.display = 'block';
  elements.mainForm.style.display = 'none';
}

/**
 * 메인 폼 표시
 */
function showMainForm() {
  elements.loginSection.style.display = 'none';
  elements.mainForm.style.display = 'block';
}

/**
 * 상태 메시지 표시
 */
function showStatus(message, type) {
  elements.status.textContent = message;
  elements.status.className = `status ${type}`;
}

/**
 * 상태 메시지 숨기기
 */
function hideStatus() {
  elements.status.className = 'status hidden';
}

/**
 * 로그 메시지 추가
 */
function log(message) {
  const timestamp = new Date().toLocaleTimeString('ko-KR');
  const logLine = `[${timestamp}] ${message}`;

  // 콘솔에도 출력
  console.log(logLine);

  // UI 로그에 추가
  elements.logContent.textContent = logLine + '\n' + elements.logContent.textContent;

  // 최대 줄 수 제한 (50줄)
  const lines = elements.logContent.textContent.split('\n');
  if (lines.length > 50) {
    elements.logContent.textContent = lines.slice(0, 50).join('\n');
  }
}

// ===== 설정 저장/불러오기 =====

/**
 * 설정을 Chrome Storage에 저장합니다
 */
function saveSettings() {
  const settings = {
    spreadsheetId: elements.spreadsheetId.value,
    sheetName: elements.sheetName.value
  };

  chrome.storage.local.set(settings, () => {
    log('설정 저장됨');
  });
}

/**
 * 저장된 설정을 불러옵니다
 */
function loadSavedSettings() {
  chrome.storage.local.get(['spreadsheetId', 'sheetName'], (result) => {
    if (result.spreadsheetId) {
      elements.spreadsheetId.value = result.spreadsheetId;
    }
    if (result.sheetName) {
      elements.sheetName.value = result.sheetName;
    }
    log('저장된 설정 불러옴');
  });
}
