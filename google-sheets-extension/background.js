/**
 * background.js - Chrome 확장 프로그램 백그라운드 서비스 워커
 *
 * Manifest V3에서는 Service Worker로 동작합니다.
 * 주요 역할:
 * - 확장 프로그램 설치/업데이트 시 초기화
 * - 팝업과 다른 부분 간의 메시지 중계
 * - 장기 실행 작업 처리
 */

// ===== 확장 프로그램 설치/업데이트 이벤트 =====
chrome.runtime.onInstalled.addListener((details) => {
  console.log('[Background] 확장 프로그램 이벤트:', details.reason);

  if (details.reason === 'install') {
    // 첫 설치 시
    console.log('[Background] 확장 프로그램이 설치되었습니다!');

    // 기본 설정 저장
    chrome.storage.local.set({
      sheetName: 'Sheet1',
      installed: true,
      installDate: new Date().toISOString()
    });

  } else if (details.reason === 'update') {
    // 업데이트 시
    console.log('[Background] 확장 프로그램이 업데이트되었습니다!');
    console.log('[Background] 이전 버전:', details.previousVersion);
  }
});

// ===== 메시지 리스너 =====
// 팝업이나 content script에서 메시지를 받아 처리합니다
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  console.log('[Background] 메시지 수신:', request);

  // 메시지 타입에 따른 처리
  switch (request.type) {
    case 'GET_AUTH_TOKEN':
      // 인증 토큰 요청
      handleGetAuthToken(sendResponse);
      return true;  // 비동기 응답을 위해 true 반환

    case 'REVOKE_TOKEN':
      // 토큰 폐기 요청
      handleRevokeToken(request.token, sendResponse);
      return true;

    case 'LOG':
      // 로그 메시지 (디버깅용)
      console.log('[Background Log]', request.message);
      sendResponse({ success: true });
      break;

    default:
      console.log('[Background] 알 수 없는 메시지 타입:', request.type);
      sendResponse({ error: 'Unknown message type' });
  }
});

/**
 * 인증 토큰을 가져옵니다
 * @param {Function} sendResponse - 응답 콜백
 */
function handleGetAuthToken(sendResponse) {
  chrome.identity.getAuthToken({ interactive: true }, (token) => {
    if (chrome.runtime.lastError) {
      console.error('[Background] 토큰 획득 실패:', chrome.runtime.lastError);
      sendResponse({
        success: false,
        error: chrome.runtime.lastError.message
      });
      return;
    }

    console.log('[Background] 토큰 획득 성공');
    sendResponse({
      success: true,
      token: token
    });
  });
}

/**
 * 인증 토큰을 폐기합니다
 * @param {string} token - 폐기할 토큰
 * @param {Function} sendResponse - 응답 콜백
 */
function handleRevokeToken(token, sendResponse) {
  if (!token) {
    sendResponse({ success: false, error: 'No token provided' });
    return;
  }

  // 캐시된 토큰 제거
  chrome.identity.removeCachedAuthToken({ token: token }, () => {
    console.log('[Background] 토큰 캐시에서 제거됨');

    // Google의 토큰 폐기 엔드포인트 호출 (선택적)
    fetch(`https://accounts.google.com/o/oauth2/revoke?token=${token}`)
      .then(() => {
        console.log('[Background] 토큰 완전 폐기 완료');
        sendResponse({ success: true });
      })
      .catch((error) => {
        console.error('[Background] 토큰 폐기 오류:', error);
        // 캐시에서는 제거되었으므로 성공으로 처리
        sendResponse({ success: true });
      });
  });
}

// ===== 알람 (스케줄링) 예제 =====
// 정기적인 작업이 필요한 경우 사용할 수 있습니다

/*
// 알람 생성 (예: 1시간마다)
chrome.alarms.create('periodicSync', {
  periodInMinutes: 60
});

// 알람 리스너
chrome.alarms.onAlarm.addListener((alarm) => {
  if (alarm.name === 'periodicSync') {
    console.log('[Background] 주기적 동기화 실행');
    // 동기화 로직 실행
  }
});
*/

// ===== 네트워크 상태 모니터링 예제 =====
// 오프라인/온라인 상태에 따른 처리가 필요한 경우

/*
self.addEventListener('online', () => {
  console.log('[Background] 네트워크 연결됨');
});

self.addEventListener('offline', () => {
  console.log('[Background] 네트워크 연결 끊김');
});
*/

console.log('[Background] Service Worker 시작됨');
