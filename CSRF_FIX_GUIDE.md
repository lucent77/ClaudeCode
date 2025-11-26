# CSRF Token 에러 해결 가이드

## 문제

"Server error (403): CSRF token validation failed" 에러가 발생하는 경우

## 해결 방법 (간단함!)

### 방법 1: CSRF 보호 비활성화 (권장 - 테스트 환경)

`includes/config.php` 파일을 열고 다음 줄을 확인:

```php
define('ENABLE_CSRF_PROTECTION', false);  // false로 설정되어 있는지 확인
```

**이미 `false`로 설정되어 있으면 CSRF 검증이 비활성화되어 있습니다.**

### 방법 2: CSRF 보호 활성화 (프로덕션 환경 권장)

CSRF 보호를 활성화하려면:

1. `includes/config.php` 파일 수정:
```php
define('ENABLE_CSRF_PROTECTION', true);  // true로 변경
```

2. 프론트엔드는 자동으로 토큰을 가져와서 사용합니다.

## 작동 원리

### CSRF 보호 비활성화 시 (기본값)
- API는 토큰 검증을 하지 않습니다
- 모든 요청이 자유롭게 처리됩니다
- 개발/테스트 환경에 적합

### CSRF 보호 활성화 시
1. 페이지 로드 시 JavaScript가 자동으로 `api/get_token.php`에서 토큰 가져오기
2. 모든 POST 요청에 토큰 자동 포함
3. 서버에서 토큰 검증
4. 유효한 토큰이 있을 때만 요청 처리

## 추가 파일

다음 파일들이 CSRF 보호를 지원합니다:

- `includes/csrf_protection.php` - CSRF 토큰 생성/검증 클래스
- `api/get_token.php` - 토큰 제공 엔드포인트
- `api/upload.php` - 토큰 검증 로직 포함
- `api/analyze.php` - 토큰 검증 로직 포함
- `assets/js/main.js` - 자동 토큰 가져오기 및 포함

## 문제 해결

### 여전히 403 에러가 발생하면?

1. **캐시 확인**: 브라우저 캐시를 삭제하고 페이지를 새로고침 (Ctrl+F5)

2. **config.php 확인**:
```bash
cat includes/config.php | grep ENABLE_CSRF_PROTECTION
```

출력이 `false`인지 확인

3. **PHP 세션 확인**:
```php
// includes/config.php에 추가
ini_set('session.save_path', '/tmp');
```

4. **로그 확인**:
```bash
tail -f logs/app.log
```

5. **Apache/Nginx 재시작**:
```bash
# Apache
sudo service apache2 restart

# Nginx
sudo service nginx restart
sudo service php-fpm restart
```

### Hostinger에서 설정

1. **파일 매니저**로 `includes/config.php` 편집
2. `ENABLE_CSRF_PROTECTION`이 `false`인지 확인
3. **File Manager** → 파일 우클릭 → **Edit**
4. 저장 후 브라우저 새로고침

## 보안 권장사항

### 개발 환경
```php
define('ENABLE_CSRF_PROTECTION', false);  // 비활성화
```

### 프로덕션 환경
```php
define('ENABLE_CSRF_PROTECTION', true);   // 활성화
```

## 테스트

CSRF 보호가 제대로 작동하는지 테스트:

```bash
# 토큰 가져오기 (CSRF 활성화 시)
curl http://your-domain.com/api/get_token.php

# 응답 예시:
{
  "success": true,
  "token": "abc123...",
  "token_name": "csrf_token",
  "expires_in": 3600
}
```

## 요약

**가장 간단한 해결책**: `includes/config.php`에서 `ENABLE_CSRF_PROTECTION`를 `false`로 설정 (이미 기본값)

이렇게 하면 CSRF 검증이 완전히 비활성화되어 403 에러가 발생하지 않습니다.
