"""
AI 트렌드 2025 PPT 생성 스크립트 - 모던 프로페셔널 디자인
"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE
from pptx.oxml.ns import nsmap


# 모던 컬러 팔레트
class Colors:
    # 메인 컬러
    PRIMARY = RGBColor(15, 23, 42)       # Slate 900 - 다크 배경
    SECONDARY = RGBColor(30, 41, 59)     # Slate 800
    ACCENT = RGBColor(99, 102, 241)      # Indigo 500 - 포인트
    ACCENT_LIGHT = RGBColor(129, 140, 248)  # Indigo 400

    # 텍스트 컬러
    TEXT_WHITE = RGBColor(255, 255, 255)
    TEXT_LIGHT = RGBColor(226, 232, 240)  # Slate 200
    TEXT_MUTED = RGBColor(148, 163, 184)  # Slate 400
    TEXT_DARK = RGBColor(30, 41, 59)      # Slate 800

    # 섹션별 액센트 컬러 (그라데이션 느낌)
    GRADIENT_1 = RGBColor(139, 92, 246)   # Violet
    GRADIENT_2 = RGBColor(236, 72, 153)   # Pink
    GRADIENT_3 = RGBColor(14, 165, 233)   # Sky
    GRADIENT_4 = RGBColor(20, 184, 166)   # Teal
    GRADIENT_5 = RGBColor(245, 158, 11)   # Amber
    GRADIENT_6 = RGBColor(34, 197, 94)    # Green
    GRADIENT_7 = RGBColor(99, 102, 241)   # Indigo


def add_background(slide, prs, color):
    """슬라이드 배경 추가"""
    bg = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height
    )
    bg.fill.solid()
    bg.fill.fore_color.rgb = color
    bg.line.fill.background()
    # 맨 뒤로 보내기
    spTree = slide.shapes._spTree
    sp = bg._element
    spTree.remove(sp)
    spTree.insert(2, sp)


def add_accent_bar(slide, prs, color, position='left'):
    """액센트 바 추가"""
    if position == 'left':
        bar = slide.shapes.add_shape(
            MSO_SHAPE.RECTANGLE, 0, 0, Inches(0.15), prs.slide_height
        )
    elif position == 'top':
        bar = slide.shapes.add_shape(
            MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(0.08)
        )
    elif position == 'bottom':
        bar = slide.shapes.add_shape(
            MSO_SHAPE.RECTANGLE, 0, prs.slide_height - Inches(0.08),
            prs.slide_width, Inches(0.08)
        )
    bar.fill.solid()
    bar.fill.fore_color.rgb = color
    bar.line.fill.background()


def add_decorative_circle(slide, x, y, size, color, opacity=0.1):
    """장식용 원 추가"""
    circle = slide.shapes.add_shape(
        MSO_SHAPE.OVAL, x, y, size, size
    )
    circle.fill.solid()
    circle.fill.fore_color.rgb = color
    circle.line.fill.background()
    # 투명도 설정
    fill = circle.fill._xPr
    srgbClr = fill.find('.//' + '{http://schemas.openxmlformats.org/drawingml/2006/main}srgbClr')
    if srgbClr is not None:
        from lxml import etree
        alpha = etree.SubElement(srgbClr, '{http://schemas.openxmlformats.org/drawingml/2006/main}alpha')
        alpha.set('val', str(int(opacity * 100000)))


def add_title_slide(prs):
    """모던 타이틀 슬라이드"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)

    # 장식 요소
    add_accent_bar(slide, prs, Colors.ACCENT, 'top')
    add_accent_bar(slide, prs, Colors.ACCENT, 'bottom')

    # 장식용 원들
    circle1 = slide.shapes.add_shape(MSO_SHAPE.OVAL, Inches(7.5), Inches(-1), Inches(4), Inches(4))
    circle1.fill.solid()
    circle1.fill.fore_color.rgb = Colors.ACCENT
    circle1.line.fill.background()

    circle2 = slide.shapes.add_shape(MSO_SHAPE.OVAL, Inches(-1.5), Inches(5), Inches(3), Inches(3))
    circle2.fill.solid()
    circle2.fill.fore_color.rgb = Colors.SECONDARY
    circle2.line.fill.background()

    # 메인 타이틀
    title_box = slide.shapes.add_textbox(Inches(0.8), Inches(2.3), Inches(8), Inches(1.2))
    tf = title_box.text_frame
    tf.word_wrap = False
    p = tf.paragraphs[0]
    p.text = "AI TRENDS"
    p.font.size = Pt(72)
    p.font.bold = True
    p.font.color.rgb = Colors.TEXT_WHITE
    p.alignment = PP_ALIGN.LEFT

    # 연도
    year_box = slide.shapes.add_textbox(Inches(0.8), Inches(3.5), Inches(8), Inches(0.8))
    tf = year_box.text_frame
    p = tf.paragraphs[0]
    p.text = "2025"
    p.font.size = Pt(56)
    p.font.bold = True
    p.font.color.rgb = Colors.ACCENT
    p.alignment = PP_ALIGN.LEFT

    # 서브타이틀
    sub_box = slide.shapes.add_textbox(Inches(0.8), Inches(4.5), Inches(6), Inches(0.6))
    tf = sub_box.text_frame
    p = tf.paragraphs[0]
    p.text = "인공지능이 바꾸는 비즈니스의 미래"
    p.font.size = Pt(22)
    p.font.color.rgb = Colors.TEXT_MUTED
    p.alignment = PP_ALIGN.LEFT

    # 하단 라인
    line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(4.35), Inches(2), Inches(0.04))
    line.fill.solid()
    line.fill.fore_color.rgb = Colors.ACCENT
    line.line.fill.background()


def add_toc_slide(prs):
    """목차 슬라이드 - 그리드 스타일"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)
    add_accent_bar(slide, prs, Colors.ACCENT, 'left')

    # 헤더
    header_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.4), Inches(3), Inches(0.5))
    tf = header_box.text_frame
    p = tf.paragraphs[0]
    p.text = "CONTENTS"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = Colors.ACCENT

    topics = [
        ("01", "생성형 AI의 진화", Colors.GRADIENT_1),
        ("02", "멀티모달 AI", Colors.GRADIENT_2),
        ("03", "AI 에이전트", Colors.GRADIENT_3),
        ("04", "엣지 AI", Colors.GRADIENT_4),
        ("05", "AI 규제와 윤리", Colors.GRADIENT_5),
        ("06", "산업별 AI 적용", Colors.GRADIENT_6),
        ("07", "미래 전망", Colors.GRADIENT_7),
    ]

    start_y = Inches(1.2)
    for i, (num, title, color) in enumerate(topics):
        y = start_y + Inches(i * 0.85)

        # 번호 박스
        num_box = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.6), y, Inches(0.7), Inches(0.55))
        num_box.fill.solid()
        num_box.fill.fore_color.rgb = color
        num_box.line.fill.background()

        # 번호 텍스트
        num_text = slide.shapes.add_textbox(Inches(0.6), y + Inches(0.08), Inches(0.7), Inches(0.4))
        tf = num_text.text_frame
        p = tf.paragraphs[0]
        p.text = num
        p.font.size = Pt(18)
        p.font.bold = True
        p.font.color.rgb = Colors.TEXT_WHITE
        p.alignment = PP_ALIGN.CENTER

        # 제목
        title_box = slide.shapes.add_textbox(Inches(1.5), y + Inches(0.1), Inches(7), Inches(0.5))
        tf = title_box.text_frame
        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(22)
        p.font.color.rgb = Colors.TEXT_WHITE
        p.alignment = PP_ALIGN.LEFT


def add_section_slide(prs, number, title, accent_color):
    """섹션 구분 슬라이드 - 미니멀 스타일"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)

    # 큰 숫자 배경
    big_num = slide.shapes.add_textbox(Inches(5.5), Inches(0.5), Inches(5), Inches(6))
    tf = big_num.text_frame
    p = tf.paragraphs[0]
    p.text = f"{number:02d}"
    p.font.size = Pt(280)
    p.font.bold = True
    p.font.color.rgb = Colors.SECONDARY
    p.alignment = PP_ALIGN.RIGHT

    # 액센트 라인
    line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(3.2), Inches(0.8), Inches(0.06))
    line.fill.solid()
    line.fill.fore_color.rgb = accent_color
    line.line.fill.background()

    # 섹션 번호
    num_box = slide.shapes.add_textbox(Inches(0.8), Inches(2.5), Inches(2), Inches(0.6))
    tf = num_box.text_frame
    p = tf.paragraphs[0]
    p.text = f"SECTION {number:02d}"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = accent_color

    # 섹션 제목
    title_box = slide.shapes.add_textbox(Inches(0.8), Inches(3.5), Inches(7), Inches(1.2))
    tf = title_box.text_frame
    p = tf.paragraphs[0]
    p.text = title
    p.font.size = Pt(44)
    p.font.bold = True
    p.font.color.rgb = Colors.TEXT_WHITE


def add_content_slide(prs, title, subtitle, items, accent_color):
    """콘텐츠 슬라이드 - 카드 스타일"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)
    add_accent_bar(slide, prs, accent_color, 'left')

    # 헤더 영역
    header_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.35), Inches(8), Inches(0.4))
    tf = header_box.text_frame
    p = tf.paragraphs[0]
    p.text = subtitle
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = accent_color

    # 메인 타이틀
    title_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.7), Inches(8), Inches(0.7))
    tf = title_box.text_frame
    p = tf.paragraphs[0]
    p.text = title
    p.font.size = Pt(32)
    p.font.bold = True
    p.font.color.rgb = Colors.TEXT_WHITE

    # 구분선
    line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.6), Inches(1.45), Inches(8.8), Inches(0.015))
    line.fill.solid()
    line.fill.fore_color.rgb = Colors.SECONDARY
    line.line.fill.background()

    # 콘텐츠 카드들
    start_y = Inches(1.7)
    for i, item in enumerate(items):
        y = start_y + Inches(i * 1.05)

        # 카드 배경
        card = slide.shapes.add_shape(
            MSO_SHAPE.ROUNDED_RECTANGLE,
            Inches(0.6), y, Inches(8.8), Inches(0.9)
        )
        card.fill.solid()
        card.fill.fore_color.rgb = Colors.SECONDARY
        card.line.fill.background()

        # 번호 인디케이터
        indicator = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            Inches(0.85), y + Inches(0.28), Inches(0.35), Inches(0.35)
        )
        indicator.fill.solid()
        indicator.fill.fore_color.rgb = accent_color
        indicator.line.fill.background()

        # 번호
        num_text = slide.shapes.add_textbox(Inches(0.85), y + Inches(0.32), Inches(0.35), Inches(0.3))
        tf = num_text.text_frame
        p = tf.paragraphs[0]
        p.text = str(i + 1)
        p.font.size = Pt(14)
        p.font.bold = True
        p.font.color.rgb = Colors.TEXT_WHITE
        p.alignment = PP_ALIGN.CENTER

        # 텍스트
        text_box = slide.shapes.add_textbox(Inches(1.4), y + Inches(0.25), Inches(7.8), Inches(0.5))
        tf = text_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = item
        p.font.size = Pt(18)
        p.font.color.rgb = Colors.TEXT_LIGHT


def add_two_column_slide(prs, title, subtitle, left_items, right_items, accent_color):
    """2단 레이아웃 슬라이드"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)
    add_accent_bar(slide, prs, accent_color, 'left')

    # 헤더
    header_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.35), Inches(8), Inches(0.4))
    tf = header_box.text_frame
    p = tf.paragraphs[0]
    p.text = subtitle
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = accent_color

    # 메인 타이틀
    title_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.7), Inches(8), Inches(0.7))
    tf = title_box.text_frame
    p = tf.paragraphs[0]
    p.text = title
    p.font.size = Pt(32)
    p.font.bold = True
    p.font.color.rgb = Colors.TEXT_WHITE

    # 왼쪽 컬럼
    for i, (label, desc) in enumerate(left_items):
        y = Inches(1.8) + Inches(i * 1.4)

        # 라벨
        label_box = slide.shapes.add_textbox(Inches(0.6), y, Inches(4), Inches(0.4))
        tf = label_box.text_frame
        p = tf.paragraphs[0]
        p.text = label
        p.font.size = Pt(18)
        p.font.bold = True
        p.font.color.rgb = accent_color

        # 설명
        desc_box = slide.shapes.add_textbox(Inches(0.6), y + Inches(0.4), Inches(4), Inches(0.8))
        tf = desc_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = desc
        p.font.size = Pt(15)
        p.font.color.rgb = Colors.TEXT_LIGHT

    # 오른쪽 컬럼
    for i, (label, desc) in enumerate(right_items):
        y = Inches(1.8) + Inches(i * 1.4)

        # 라벨
        label_box = slide.shapes.add_textbox(Inches(5.2), y, Inches(4), Inches(0.4))
        tf = label_box.text_frame
        p = tf.paragraphs[0]
        p.text = label
        p.font.size = Pt(18)
        p.font.bold = True
        p.font.color.rgb = accent_color

        # 설명
        desc_box = slide.shapes.add_textbox(Inches(5.2), y + Inches(0.4), Inches(4.2), Inches(0.8))
        tf = desc_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = desc
        p.font.size = Pt(15)
        p.font.color.rgb = Colors.TEXT_LIGHT


def add_closing_slide(prs):
    """마무리 슬라이드"""
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    add_background(slide, prs, Colors.PRIMARY)

    # 장식 요소
    add_accent_bar(slide, prs, Colors.ACCENT, 'top')
    add_accent_bar(slide, prs, Colors.ACCENT, 'bottom')

    # 장식 원
    circle = slide.shapes.add_shape(MSO_SHAPE.OVAL, Inches(6.5), Inches(4.5), Inches(4), Inches(4))
    circle.fill.solid()
    circle.fill.fore_color.rgb = Colors.SECONDARY
    circle.line.fill.background()

    # Thank You
    thanks_box = slide.shapes.add_textbox(Inches(0.8), Inches(2.5), Inches(8), Inches(1))
    tf = thanks_box.text_frame
    p = tf.paragraphs[0]
    p.text = "THANK YOU"
    p.font.size = Pt(64)
    p.font.bold = True
    p.font.color.rgb = Colors.TEXT_WHITE

    # 서브 텍스트
    sub_box = slide.shapes.add_textbox(Inches(0.8), Inches(3.6), Inches(6), Inches(0.5))
    tf = sub_box.text_frame
    p = tf.paragraphs[0]
    p.text = "AI Trends 2025"
    p.font.size = Pt(20)
    p.font.color.rgb = Colors.ACCENT

    # 라인
    line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(4.2), Inches(1.5), Inches(0.04))
    line.fill.solid()
    line.fill.fore_color.rgb = Colors.ACCENT
    line.line.fill.background()

    # 추가 정보
    info_box = slide.shapes.add_textbox(Inches(0.8), Inches(4.5), Inches(6), Inches(0.8))
    tf = info_box.text_frame
    p = tf.paragraphs[0]
    p.text = "The Future is Intelligent"
    p.font.size = Pt(16)
    p.font.color.rgb = Colors.TEXT_MUTED


def create_ai_trends_ppt():
    """AI 트렌드 2025 PPT 생성 - 프로페셔널 버전"""
    prs = Presentation()
    prs.slide_width = Inches(10)
    prs.slide_height = Inches(7.5)

    # 1. 타이틀 슬라이드
    add_title_slide(prs)

    # 2. 목차
    add_toc_slide(prs)

    # 3. 생성형 AI 섹션
    add_section_slide(prs, 1, "생성형 AI의 진화", Colors.GRADIENT_1)
    add_content_slide(prs,
        "Generative AI",
        "SECTION 01 · 생성형 AI",
        [
            "GPT-4, Claude, Gemini 등 대형 언어모델(LLM)의 지속적 발전",
            "텍스트, 이미지, 비디오, 음악 등 다양한 콘텐츠 자동 생성",
            "코드 생성 및 소프트웨어 개발 프로세스 자동화",
            "RAG 기술을 통한 정확도와 신뢰성 향상",
            "기업 맞춤형 Private LLM 솔루션 확대"
        ],
        Colors.GRADIENT_1
    )

    # 4. 멀티모달 AI 섹션
    add_section_slide(prs, 2, "멀티모달 AI", Colors.GRADIENT_2)
    add_content_slide(prs,
        "Multimodal AI",
        "SECTION 02 · 멀티모달 AI",
        [
            "텍스트 + 이미지 + 음성 + 비디오 통합 처리 능력",
            "자연스러운 인간-AI 상호작용 인터페이스 구현",
            "실시간 다국어 번역 및 통역 서비스",
            "의료 영상 분석과 진단 보조 시스템",
            "자율주행 및 로보틱스 분야의 인지 능력 향상"
        ],
        Colors.GRADIENT_2
    )

    # 5. AI 에이전트 섹션
    add_section_slide(prs, 3, "AI 에이전트", Colors.GRADIENT_3)
    add_content_slide(prs,
        "Agentic AI",
        "SECTION 03 · AI 에이전트",
        [
            "자율적으로 목표를 설정하고 작업을 계획·실행하는 AI",
            "복잡한 비즈니스 프로세스의 End-to-End 자동화",
            "웹 브라우징, 파일 관리, 코딩 등 다양한 태스크 수행",
            "인간과 AI의 협업 기반 하이브리드 워크플로우",
            "2025년 가장 주목받는 AI 기술 트렌드"
        ],
        Colors.GRADIENT_3
    )

    # 6. 엣지 AI 섹션
    add_section_slide(prs, 4, "엣지 AI", Colors.GRADIENT_4)
    add_content_slide(prs,
        "Edge AI & On-Device AI",
        "SECTION 04 · 엣지 AI",
        [
            "스마트폰, IoT 디바이스에서 직접 AI 모델 실행",
            "클라우드 의존도 감소로 프라이버시 및 보안 강화",
            "초저지연 실시간 처리로 사용자 경험 향상",
            "Apple Intelligence, Google Gemini Nano 등 상용화",
            "소형 언어모델(SLM)의 효율성 및 성능 발전"
        ],
        Colors.GRADIENT_4
    )

    # 7. AI 규제와 윤리 섹션
    add_section_slide(prs, 5, "AI 규제와 윤리", Colors.GRADIENT_5)
    add_content_slide(prs,
        "AI Governance & Ethics",
        "SECTION 05 · AI 규제와 윤리",
        [
            "EU AI Act 본격 시행 - 리스크 기반 규제 프레임워크",
            "AI 시스템의 투명성 및 설명가능성(XAI) 요구 증가",
            "딥페이크, 허위정보 대응을 위한 기술적·제도적 장치",
            "AI 저작권 및 지적재산권 관련 법적 논의 활발",
            "Responsible AI 프레임워크 도입 확산"
        ],
        Colors.GRADIENT_5
    )

    # 8. 산업별 AI 적용 섹션
    add_section_slide(prs, 6, "산업별 AI 적용", Colors.GRADIENT_6)
    add_two_column_slide(prs,
        "Industry Applications",
        "SECTION 06 · 산업별 AI 적용",
        [
            ("Healthcare", "신약 개발 가속화, AI 진단 보조,\n개인 맞춤형 정밀 치료"),
            ("Finance", "실시간 사기 탐지, 리스크 관리,\nAI 기반 로보어드바이저"),
            ("Manufacturing", "예측 정비, 품질 검사 자동화,\n스마트 팩토리 구현"),
        ],
        [
            ("Education", "적응형 학습 시스템, AI 튜터,\n자동 평가 및 피드백"),
            ("Retail", "초개인화 추천, 수요 예측,\nAI 고객 서비스 챗봇"),
            ("Logistics", "경로 최적화, 자율 배송,\n스마트 창고 관리"),
        ],
        Colors.GRADIENT_6
    )

    # 9. 미래 전망 섹션
    add_section_slide(prs, 7, "미래 전망", Colors.GRADIENT_7)
    add_content_slide(prs,
        "Future Outlook",
        "SECTION 07 · 미래 전망",
        [
            "AGI(범용 인공지능)를 향한 지속적인 연구 개발",
            "AI와 인간의 공존을 위한 협력 모델 정립",
            "AI로 인한 새로운 직업 창출과 기존 직무의 재정의",
            "AI 민주화 - 누구나 AI를 쉽게 활용하는 시대",
            "Green AI - 에너지 효율적인 지속가능한 AI 개발"
        ],
        Colors.GRADIENT_7
    )

    # 10. 마무리 슬라이드
    add_closing_slide(prs)

    # 파일 저장
    output_path = "AI_Trends_2025.pptx"
    prs.save(output_path)
    print(f"✅ PPT 파일이 생성되었습니다: {output_path}")
    return output_path


if __name__ == "__main__":
    create_ai_trends_ppt()
