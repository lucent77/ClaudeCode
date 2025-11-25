# Google Cloud Credentials

이 디렉토리에는 Google Cloud Service Account JSON 키 파일을 저장합니다.

## 설정 방법

1. Google Cloud Console에서 Service Account JSON 키 다운로드
2. 파일명을 `google-service-account.json`으로 변경
3. 이 디렉토리에 파일 저장
4. 파일 권한 설정:
   ```bash
   chmod 600 credentials/google-service-account.json
   ```

## 보안 주의사항

⚠️ **절대로 이 파일을 Git에 커밋하지 마세요!**
⚠️ **이 파일을 공개적으로 공유하지 마세요!**

`.gitignore` 파일에 다음 내용이 포함되어 있는지 확인하세요:
```
credentials/*.json
```

## 파일 구조 예시

```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "...",
  "private_key": "...",
  "client_email": "...",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "...",
  "client_x509_cert_url": "..."
}
```
