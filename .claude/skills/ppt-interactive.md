# PPT Interactive Elements Skill

PPT에 인터랙티브하고 동적인 요소를 추가하는 고급 skill입니다.

## 인터랙티브 요소

### 1. 하이퍼링크 네비게이션

목차에서 각 섹션으로 바로 이동하는 클릭 가능한 링크를 추가합니다.

```python
from pptx.util import Inches, Pt
from pptx.enum.action import PP_ACTION
from pptx.oxml.ns import qn
from lxml import etree

def add_hyperlink_to_slide(shape, target_slide_index):
    """도형에 슬라이드 이동 하이퍼링크 추가"""
    click = shape._element.find(qn('a:hlinkClick'))
    if click is None:
        # 클릭 액션 추가
        spPr = shape._element
        cNvPr = spPr.find('.//' + qn('p:cNvPr'))
        if cNvPr is not None:
            hlinkClick = etree.SubElement(
                cNvPr,
                qn('a:hlinkClick'),
                {qn('r:id'): '', 'action': f'ppaction://hlinksldjump?num={target_slide_index + 1}'}
            )

def add_clickable_toc(slide, prs, topics):
    """클릭 가능한 목차 생성"""
    for i, (title, target_idx) in enumerate(topics):
        y = Inches(1.5) + Inches(i * 0.7)

        # 클릭 영역 (투명 사각형)
        click_area = slide.shapes.add_shape(
            MSO_SHAPE.RECTANGLE,
            Inches(0.5), y, Inches(9), Inches(0.6)
        )
        click_area.fill.background()
        click_area.line.fill.background()

        # 하이퍼링크 추가
        add_hyperlink_to_slide(click_area, target_idx)

        # 호버 효과를 위한 배경
        hover_bg = slide.shapes.add_shape(
            MSO_SHAPE.ROUNDED_RECTANGLE,
            Inches(0.5), y, Inches(9), Inches(0.6)
        )
        hover_bg.fill.solid()
        hover_bg.fill.fore_color.rgb = RGBColor(30, 41, 59)
        hover_bg.line.fill.background()
```

### 2. 홈 버튼 (슬라이드 네비게이션)

모든 슬라이드에 홈/목차로 돌아가는 버튼 추가

```python
def add_home_button(slide, prs, home_slide_index=1):
    """홈 버튼 추가 (우측 하단)"""
    button_size = Inches(0.4)
    x = prs.slide_width - Inches(0.6)
    y = prs.slide_height - Inches(0.6)

    # 버튼 배경
    button = slide.shapes.add_shape(
        MSO_SHAPE.OVAL,
        x, y, button_size, button_size
    )
    button.fill.solid()
    button.fill.fore_color.rgb = RGBColor(99, 102, 241)
    button.line.fill.background()

    # 홈 아이콘 (작은 사각형으로 표현)
    icon = slide.shapes.add_shape(
        MSO_SHAPE.PENTAGON,
        x + Inches(0.1), y + Inches(0.08),
        Inches(0.2), Inches(0.2)
    )
    icon.fill.solid()
    icon.fill.fore_color.rgb = RGBColor(255, 255, 255)
    icon.line.fill.background()
    icon.rotation = 180

    # 하이퍼링크 추가
    add_hyperlink_to_slide(button, home_slide_index)
```

### 3. 진행 표시기 (Progress Bar)

슬라이드 하단에 진행률을 표시하는 바

```python
def add_progress_bar(slide, prs, current, total, accent_color):
    """진행 표시 바 추가"""
    bar_height = Inches(0.08)
    y = prs.slide_height - bar_height

    # 배경 바 (전체)
    bg_bar = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        0, y, prs.slide_width, bar_height
    )
    bg_bar.fill.solid()
    bg_bar.fill.fore_color.rgb = RGBColor(30, 41, 59)
    bg_bar.line.fill.background()

    # 진행 바 (현재)
    progress_width = prs.slide_width * (current / total)
    progress_bar = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        0, y, progress_width, bar_height
    )
    progress_bar.fill.solid()
    progress_bar.fill.fore_color.rgb = accent_color
    progress_bar.line.fill.background()
```

### 4. 애니메이션 마커

슬라이드에 순차적 등장 효과를 위한 번호 마커

```python
def add_animation_markers(slide, items, start_x, start_y, accent_color):
    """순차 등장 효과를 위한 번호 마커"""
    for i, item in enumerate(items):
        y = start_y + Inches(i * 1.0)

        # 번호 원
        marker = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            start_x, y, Inches(0.5), Inches(0.5)
        )
        marker.fill.solid()
        marker.fill.fore_color.rgb = accent_color
        marker.line.width = Pt(2)
        marker.line.color.rgb = RGBColor(255, 255, 255)

        # 번호 텍스트
        num_box = slide.shapes.add_textbox(
            start_x, y + Inches(0.08),
            Inches(0.5), Inches(0.35)
        )
        tf = num_box.text_frame
        p = tf.paragraphs[0]
        p.text = str(i + 1)
        p.font.size = Pt(18)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER
```

## 시각적 효과

### 5. 그라데이션 배경

```python
from pptx.oxml.ns import qn
from lxml import etree

def add_gradient_background(slide, prs, color1, color2, angle=90):
    """그라데이션 배경 추가"""
    bg = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height
    )
    bg.line.fill.background()

    # 그라데이션 설정
    fill = bg.fill
    fill.gradient()
    fill.gradient_angle = angle
    fill.gradient_stops[0].color.rgb = color1
    fill.gradient_stops[1].color.rgb = color2

    # 맨 뒤로 보내기
    spTree = slide.shapes._spTree
    sp = bg._element
    spTree.remove(sp)
    spTree.insert(2, sp)
```

### 6. 글로우 효과 (Glow Effect)

```python
def add_glow_effect(shape, color, radius=10):
    """도형에 글로우 효과 추가"""
    spPr = shape._element.spPr

    # effectLst 요소 찾기 또는 생성
    effectLst = spPr.find(qn('a:effectLst'))
    if effectLst is None:
        effectLst = etree.SubElement(spPr, qn('a:effectLst'))

    # glow 효과 추가
    glow = etree.SubElement(effectLst, qn('a:glow'))
    glow.set('rad', str(radius * 12700))  # EMU 단위

    srgbClr = etree.SubElement(glow, qn('a:srgbClr'))
    srgbClr.set('val', f'{color.red:02X}{color.green:02X}{color.blue:02X}')

    alpha = etree.SubElement(srgbClr, qn('a:alpha'))
    alpha.set('val', '60000')  # 60% 투명도
```

### 7. 그림자 효과 (Drop Shadow)

```python
def add_shadow_effect(shape, blur=50000, dist=38100, direction=2700000):
    """도형에 그림자 효과 추가"""
    spPr = shape._element.spPr

    effectLst = spPr.find(qn('a:effectLst'))
    if effectLst is None:
        effectLst = etree.SubElement(spPr, qn('a:effectLst'))

    # outerShdw (외부 그림자)
    outerShdw = etree.SubElement(effectLst, qn('a:outerShdw'))
    outerShdw.set('blurRad', str(blur))
    outerShdw.set('dist', str(dist))
    outerShdw.set('dir', str(direction))
    outerShdw.set('algn', 'tl')

    srgbClr = etree.SubElement(outerShdw, qn('a:srgbClr'))
    srgbClr.set('val', '000000')
    alpha = etree.SubElement(srgbClr, qn('a:alpha'))
    alpha.set('val', '40000')
```

### 8. 투명도 설정

```python
def set_shape_transparency(shape, transparency_percent):
    """도형 투명도 설정 (0-100)"""
    fill = shape.fill._xPr
    srgbClr = fill.find('.//' + qn('a:srgbClr'))
    if srgbClr is not None:
        alpha = etree.SubElement(srgbClr, qn('a:alpha'))
        alpha.set('val', str(int((100 - transparency_percent) * 1000)))
```

## 인터랙티브 컴포넌트

### 9. 탭 네비게이션

```python
def add_tab_navigation(slide, tabs, active_index, y_position, accent_color):
    """탭 스타일 네비게이션"""
    tab_width = Inches(2)
    start_x = Inches(0.5)

    for i, tab_name in enumerate(tabs):
        x = start_x + (tab_width * i)

        # 탭 배경
        tab_bg = slide.shapes.add_shape(
            MSO_SHAPE.ROUNDED_RECTANGLE,
            x, y_position, tab_width - Inches(0.1), Inches(0.5)
        )

        if i == active_index:
            tab_bg.fill.solid()
            tab_bg.fill.fore_color.rgb = accent_color
        else:
            tab_bg.fill.solid()
            tab_bg.fill.fore_color.rgb = RGBColor(30, 41, 59)
        tab_bg.line.fill.background()

        # 탭 텍스트
        tab_text = slide.shapes.add_textbox(
            x, y_position + Inches(0.1),
            tab_width - Inches(0.1), Inches(0.3)
        )
        tf = tab_text.text_frame
        p = tf.paragraphs[0]
        p.text = tab_name
        p.font.size = Pt(14)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER
```

### 10. 토글 버튼

```python
def add_toggle_button(slide, x, y, is_on=True, accent_color=RGBColor(99, 102, 241)):
    """토글 버튼 스타일"""
    # 트랙 배경
    track = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE,
        x, y, Inches(0.8), Inches(0.35)
    )
    track.fill.solid()
    if is_on:
        track.fill.fore_color.rgb = accent_color
    else:
        track.fill.fore_color.rgb = RGBColor(100, 116, 139)
    track.line.fill.background()

    # 토글 원
    knob_x = x + Inches(0.45) if is_on else x + Inches(0.05)
    knob = slide.shapes.add_shape(
        MSO_SHAPE.OVAL,
        knob_x, y + Inches(0.05),
        Inches(0.25), Inches(0.25)
    )
    knob.fill.solid()
    knob.fill.fore_color.rgb = RGBColor(255, 255, 255)
    knob.line.fill.background()
```

## 사용 예시

```python
# 인터랙티브 목차 생성
topics = [
    ("생성형 AI", 2),
    ("멀티모달 AI", 4),
    ("AI 에이전트", 6),
]
add_clickable_toc(toc_slide, prs, topics)

# 모든 슬라이드에 홈 버튼과 진행 바 추가
for i, slide in enumerate(prs.slides):
    if i > 0:  # 타이틀 제외
        add_home_button(slide, prs, home_slide_index=1)
        add_progress_bar(slide, prs, i, len(prs.slides), Colors.ACCENT)
```

## 주의사항

1. 하이퍼링크는 프레젠테이션 모드에서만 동작
2. 그라데이션, 그림자 효과는 XML 직접 조작 필요
3. 애니메이션은 PowerPoint에서 추가 설정 필요
4. 복잡한 효과는 파일 크기 증가 가능
