# PPT Generator Skill

PPT(PowerPoint) 프레젠테이션을 모던하고 전문적인 디자인으로 생성하는 skill입니다.

## 사용 시점

사용자가 다음과 같은 요청을 할 때 이 skill을 사용합니다:
- PPT 만들어줘
- 프레젠테이션 생성해줘
- 발표 자료 만들어줘
- 슬라이드 제작해줘

## 필수 라이브러리

```bash
pip install python-pptx
```

## 디자인 시스템

### 컬러 팔레트 (다크 테마)

```python
class Colors:
    # 메인 컬러
    PRIMARY = RGBColor(15, 23, 42)       # Slate 900 - 다크 배경
    SECONDARY = RGBColor(30, 41, 59)     # Slate 800 - 카드 배경
    ACCENT = RGBColor(99, 102, 241)      # Indigo 500 - 포인트

    # 텍스트 컬러
    TEXT_WHITE = RGBColor(255, 255, 255)
    TEXT_LIGHT = RGBColor(226, 232, 240)  # Slate 200
    TEXT_MUTED = RGBColor(148, 163, 184)  # Slate 400

    # 섹션별 액센트 컬러
    VIOLET = RGBColor(139, 92, 246)
    PINK = RGBColor(236, 72, 153)
    SKY = RGBColor(14, 165, 233)
    TEAL = RGBColor(20, 184, 166)
    AMBER = RGBColor(245, 158, 11)
    GREEN = RGBColor(34, 197, 94)
    INDIGO = RGBColor(99, 102, 241)
```

### 슬라이드 크기

```python
prs.slide_width = Inches(10)
prs.slide_height = Inches(7.5)
```

### 슬라이드 유형별 디자인

#### 1. 타이틀 슬라이드
- 다크 배경 (PRIMARY)
- 상단/하단 액센트 바 (ACCENT)
- 대형 영문 타이틀 (72pt, Bold, White)
- 연도 또는 강조 텍스트 (56pt, Bold, ACCENT)
- 서브타이틀 (22pt, TEXT_MUTED)
- 장식용 원형 도형

#### 2. 목차 슬라이드 (CONTENTS)
- 왼쪽 액센트 바 (ACCENT)
- 헤더 "CONTENTS" (14pt, Bold, ACCENT)
- 번호 박스 (ROUNDED_RECTANGLE, 섹션별 컬러)
- 제목 (22pt, White)

#### 3. 섹션 구분 슬라이드
- 배경에 대형 숫자 (280pt, SECONDARY)
- "SECTION XX" 라벨 (14pt, Bold, 섹션 컬러)
- 액센트 라인 (0.8인치 너비)
- 섹션 제목 (44pt, Bold, White)

#### 4. 콘텐츠 슬라이드 (카드 스타일)
- 왼쪽 액센트 바 (섹션별 컬러)
- 서브타이틀 (12pt, Bold, 섹션 컬러)
- 메인 타이틀 (32pt, Bold, White)
- 구분선 (SECONDARY)
- 카드형 콘텐츠:
  - 카드 배경 (ROUNDED_RECTANGLE, SECONDARY)
  - 번호 인디케이터 (OVAL, 섹션 컬러)
  - 텍스트 (18pt, TEXT_LIGHT)

#### 5. 2단 레이아웃 슬라이드
- 왼쪽/오른쪽 컬럼 균등 배치
- 라벨 (18pt, Bold, 섹션 컬러)
- 설명 (15pt, TEXT_LIGHT)

#### 6. 마무리 슬라이드
- "THANK YOU" (64pt, Bold, White)
- 서브텍스트 (20pt, ACCENT)
- 슬로건 (16pt, TEXT_MUTED)

## 레이아웃 가이드라인

### 여백
- 좌측 여백: 0.6 ~ 0.8 인치
- 상단 여백: 0.35 인치 (헤더)
- 카드 간격: 1.05 인치

### 액센트 바
- 왼쪽 바: 너비 0.15인치, 전체 높이
- 상단/하단 바: 높이 0.08인치, 전체 너비

### 도형
- 카드: ROUNDED_RECTANGLE
- 번호 인디케이터: OVAL (0.35 x 0.35 인치)
- 구분선: RECTANGLE (높이 0.015 인치)

## 코드 템플릿

PPT 생성 시 `create_ai_trends_ppt.py` 파일을 참고하여 동일한 디자인 시스템을 적용합니다.

### 기본 구조

```python
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

# Colors 클래스 정의
# add_background() 함수
# add_accent_bar() 함수
# add_title_slide() 함수
# add_toc_slide() 함수
# add_section_slide() 함수
# add_content_slide() 함수
# add_two_column_slide() 함수
# add_closing_slide() 함수
```

## 주의사항

1. 모든 슬라이드는 빈 레이아웃 사용 (`slide_layouts[6]`)
2. 배경 도형은 항상 맨 뒤로 보내기
3. 텍스트 정렬: 왼쪽 정렬 기본, 번호/제목은 중앙 정렬
4. 한글/영문 혼용 시 영문은 대문자로 통일
5. 섹션별로 일관된 액센트 컬러 사용
