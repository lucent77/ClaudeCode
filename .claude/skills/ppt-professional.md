# PPT Professional Design Patterns Skill

전문가급 PPT를 위한 고급 디자인 패턴과 데이터 시각화 컴포넌트입니다.

## 데이터 시각화

### 1. 차트 (Charts)

```python
from pptx.chart.data import CategoryChartData
from pptx.enum.chart import XL_CHART_TYPE, XL_LEGEND_POSITION
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor

def add_bar_chart(slide, data, x, y, width, height, accent_color):
    """막대 차트 추가"""
    chart_data = CategoryChartData()
    chart_data.categories = data['categories']

    for series_name, values in data['series'].items():
        chart_data.add_series(series_name, values)

    chart = slide.shapes.add_chart(
        XL_CHART_TYPE.COLUMN_CLUSTERED,
        x, y, width, height,
        chart_data
    ).chart

    # 스타일링
    plot = chart.plots[0]
    plot.has_data_labels = True

    # 시리즈 색상 설정
    for i, series in enumerate(chart.series):
        series.format.fill.solid()
        series.format.fill.fore_color.rgb = accent_color

    return chart

def add_line_chart(slide, data, x, y, width, height, colors):
    """라인 차트 추가"""
    chart_data = CategoryChartData()
    chart_data.categories = data['categories']

    for series_name, values in data['series'].items():
        chart_data.add_series(series_name, values)

    chart = slide.shapes.add_chart(
        XL_CHART_TYPE.LINE_MARKERS,
        x, y, width, height,
        chart_data
    ).chart

    # 라인 스타일링
    for i, series in enumerate(chart.series):
        series.format.line.color.rgb = colors[i % len(colors)]
        series.format.line.width = Pt(3)

    return chart

def add_pie_chart(slide, data, x, y, size, colors):
    """파이 차트 추가"""
    chart_data = CategoryChartData()
    chart_data.categories = data['categories']
    chart_data.add_series('', data['values'])

    chart = slide.shapes.add_chart(
        XL_CHART_TYPE.PIE,
        x, y, size, size,
        chart_data
    ).chart

    # 데이터 라벨 표시
    chart.plots[0].has_data_labels = True
    data_labels = chart.plots[0].data_labels
    data_labels.show_percentage = True
    data_labels.show_category_name = True

    return chart

def add_donut_chart(slide, data, x, y, size, colors):
    """도넛 차트 추가"""
    chart_data = CategoryChartData()
    chart_data.categories = data['categories']
    chart_data.add_series('', data['values'])

    chart = slide.shapes.add_chart(
        XL_CHART_TYPE.DOUGHNUT,
        x, y, size, size,
        chart_data
    ).chart

    return chart
```

### 2. 통계 카드 (Stat Cards)

```python
def add_stat_card(slide, value, label, x, y, accent_color, trend=None):
    """통계 수치 카드"""
    card_width = Inches(2.2)
    card_height = Inches(1.8)

    # 카드 배경
    card = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE,
        x, y, card_width, card_height
    )
    card.fill.solid()
    card.fill.fore_color.rgb = RGBColor(30, 41, 59)
    card.line.fill.background()

    # 상단 액센트 라인
    accent_line = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        x, y, card_width, Inches(0.06)
    )
    accent_line.fill.solid()
    accent_line.fill.fore_color.rgb = accent_color
    accent_line.line.fill.background()

    # 수치
    value_box = slide.shapes.add_textbox(
        x + Inches(0.15), y + Inches(0.3),
        card_width - Inches(0.3), Inches(0.8)
    )
    tf = value_box.text_frame
    p = tf.paragraphs[0]
    p.text = str(value)
    p.font.size = Pt(36)
    p.font.bold = True
    p.font.color.rgb = RGBColor(255, 255, 255)

    # 라벨
    label_box = slide.shapes.add_textbox(
        x + Inches(0.15), y + Inches(1.1),
        card_width - Inches(0.3), Inches(0.4)
    )
    tf = label_box.text_frame
    p = tf.paragraphs[0]
    p.text = label
    p.font.size = Pt(12)
    p.font.color.rgb = RGBColor(148, 163, 184)

    # 트렌드 표시 (선택적)
    if trend:
        trend_box = slide.shapes.add_textbox(
            x + Inches(0.15), y + Inches(1.4),
            card_width - Inches(0.3), Inches(0.3)
        )
        tf = trend_box.text_frame
        p = tf.paragraphs[0]
        if trend > 0:
            p.text = f"▲ +{trend}%"
            p.font.color.rgb = RGBColor(34, 197, 94)
        else:
            p.text = f"▼ {trend}%"
            p.font.color.rgb = RGBColor(239, 68, 68)
        p.font.size = Pt(11)
        p.font.bold = True

def add_stat_row(slide, stats, y, accent_colors):
    """통계 카드 행 추가"""
    start_x = Inches(0.6)
    spacing = Inches(2.4)

    for i, (value, label, trend) in enumerate(stats):
        x = start_x + (spacing * i)
        color = accent_colors[i % len(accent_colors)]
        add_stat_card(slide, value, label, x, y, color, trend)
```

### 3. 테이블 (Tables)

```python
def add_styled_table(slide, data, x, y, col_widths, accent_color):
    """스타일이 적용된 테이블"""
    rows = len(data)
    cols = len(data[0]) if data else 0

    table = slide.shapes.add_table(rows, cols, x, y,
        sum(col_widths), Inches(0.5 * rows)).table

    # 열 너비 설정
    for i, width in enumerate(col_widths):
        table.columns[i].width = width

    # 데이터 및 스타일 적용
    for row_idx, row_data in enumerate(data):
        for col_idx, cell_value in enumerate(row_data):
            cell = table.cell(row_idx, col_idx)
            cell.text = str(cell_value)

            # 헤더 행 스타일
            if row_idx == 0:
                cell.fill.solid()
                cell.fill.fore_color.rgb = accent_color
                for paragraph in cell.text_frame.paragraphs:
                    paragraph.font.bold = True
                    paragraph.font.color.rgb = RGBColor(255, 255, 255)
                    paragraph.font.size = Pt(12)
            else:
                # 교대 행 색상
                if row_idx % 2 == 0:
                    cell.fill.solid()
                    cell.fill.fore_color.rgb = RGBColor(30, 41, 59)
                else:
                    cell.fill.solid()
                    cell.fill.fore_color.rgb = RGBColor(15, 23, 42)

                for paragraph in cell.text_frame.paragraphs:
                    paragraph.font.color.rgb = RGBColor(226, 232, 240)
                    paragraph.font.size = Pt(11)

    return table
```

## 인포그래픽 컴포넌트

### 4. 타임라인 (Timeline)

```python
def add_timeline(slide, events, y, accent_colors):
    """가로 타임라인"""
    line_y = y + Inches(0.8)
    start_x = Inches(0.8)
    end_x = Inches(9.2)
    spacing = (end_x - start_x) / (len(events) - 1) if len(events) > 1 else 0

    # 메인 라인
    main_line = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        start_x, line_y, end_x - start_x, Inches(0.04)
    )
    main_line.fill.solid()
    main_line.fill.fore_color.rgb = RGBColor(71, 85, 105)
    main_line.line.fill.background()

    for i, (year, title, desc) in enumerate(events):
        x = start_x + (spacing * i)
        color = accent_colors[i % len(accent_colors)]

        # 노드 (원)
        node = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            x - Inches(0.15), line_y - Inches(0.13),
            Inches(0.3), Inches(0.3)
        )
        node.fill.solid()
        node.fill.fore_color.rgb = color
        node.line.width = Pt(3)
        node.line.color.rgb = RGBColor(255, 255, 255)

        # 연도
        year_box = slide.shapes.add_textbox(
            x - Inches(0.4), line_y - Inches(0.6),
            Inches(0.8), Inches(0.3)
        )
        tf = year_box.text_frame
        p = tf.paragraphs[0]
        p.text = str(year)
        p.font.size = Pt(14)
        p.font.bold = True
        p.font.color.rgb = color
        p.alignment = PP_ALIGN.CENTER

        # 제목
        title_box = slide.shapes.add_textbox(
            x - Inches(0.6), line_y + Inches(0.3),
            Inches(1.2), Inches(0.4)
        )
        tf = title_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER
```

### 5. 프로세스 다이어그램 (Process Flow)

```python
def add_process_flow(slide, steps, y, accent_color):
    """프로세스 플로우 다이어그램"""
    start_x = Inches(0.6)
    step_width = Inches(1.8)
    arrow_width = Inches(0.4)
    spacing = step_width + arrow_width

    for i, (step_num, title, desc) in enumerate(steps):
        x = start_x + (spacing * i)

        # 스텝 박스
        box = slide.shapes.add_shape(
            MSO_SHAPE.ROUNDED_RECTANGLE,
            x, y, step_width, Inches(1.4)
        )
        box.fill.solid()
        box.fill.fore_color.rgb = RGBColor(30, 41, 59)
        box.line.color.rgb = accent_color
        box.line.width = Pt(2)

        # 스텝 번호
        num_circle = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            x + step_width/2 - Inches(0.25), y - Inches(0.25),
            Inches(0.5), Inches(0.5)
        )
        num_circle.fill.solid()
        num_circle.fill.fore_color.rgb = accent_color
        num_circle.line.fill.background()

        num_text = slide.shapes.add_textbox(
            x + step_width/2 - Inches(0.25), y - Inches(0.18),
            Inches(0.5), Inches(0.4)
        )
        tf = num_text.text_frame
        p = tf.paragraphs[0]
        p.text = str(step_num)
        p.font.size = Pt(16)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER

        # 제목
        title_box = slide.shapes.add_textbox(
            x + Inches(0.1), y + Inches(0.4),
            step_width - Inches(0.2), Inches(0.4)
        )
        tf = title_box.text_frame
        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER

        # 설명
        desc_box = slide.shapes.add_textbox(
            x + Inches(0.1), y + Inches(0.8),
            step_width - Inches(0.2), Inches(0.5)
        )
        tf = desc_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = desc
        p.font.size = Pt(10)
        p.font.color.rgb = RGBColor(148, 163, 184)
        p.alignment = PP_ALIGN.CENTER

        # 화살표 (마지막 제외)
        if i < len(steps) - 1:
            arrow = slide.shapes.add_shape(
                MSO_SHAPE.CHEVRON,
                x + step_width + Inches(0.05), y + Inches(0.5),
                Inches(0.3), Inches(0.4)
            )
            arrow.fill.solid()
            arrow.fill.fore_color.rgb = accent_color
            arrow.line.fill.background()
```

### 6. 비교 슬라이드 (Comparison)

```python
def add_comparison_slide(slide, prs, left_data, right_data, accent_colors):
    """VS 비교 레이아웃"""
    center_x = prs.slide_width / 2

    # VS 원
    vs_circle = slide.shapes.add_shape(
        MSO_SHAPE.OVAL,
        center_x - Inches(0.4), Inches(3.2),
        Inches(0.8), Inches(0.8)
    )
    vs_circle.fill.solid()
    vs_circle.fill.fore_color.rgb = RGBColor(71, 85, 105)
    vs_circle.line.fill.background()

    vs_text = slide.shapes.add_textbox(
        center_x - Inches(0.4), Inches(3.35),
        Inches(0.8), Inches(0.5)
    )
    tf = vs_text.text_frame
    p = tf.paragraphs[0]
    p.text = "VS"
    p.font.size = Pt(20)
    p.font.bold = True
    p.font.color.rgb = RGBColor(255, 255, 255)
    p.alignment = PP_ALIGN.CENTER

    # 왼쪽 패널
    add_comparison_panel(slide, left_data, Inches(0.4), accent_colors[0])

    # 오른쪽 패널
    add_comparison_panel(slide, right_data, Inches(5.1), accent_colors[1])

def add_comparison_panel(slide, data, x, accent_color):
    """비교 패널 컴포넌트"""
    panel_width = Inches(4.5)

    # 패널 배경
    panel = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE,
        x, Inches(1.5), panel_width, Inches(5)
    )
    panel.fill.solid()
    panel.fill.fore_color.rgb = RGBColor(30, 41, 59)
    panel.line.fill.background()

    # 헤더
    header = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        x, Inches(1.5), panel_width, Inches(0.8)
    )
    header.fill.solid()
    header.fill.fore_color.rgb = accent_color
    header.line.fill.background()

    # 제목
    title_box = slide.shapes.add_textbox(
        x + Inches(0.2), Inches(1.65),
        panel_width - Inches(0.4), Inches(0.5)
    )
    tf = title_box.text_frame
    p = tf.paragraphs[0]
    p.text = data['title']
    p.font.size = Pt(20)
    p.font.bold = True
    p.font.color.rgb = RGBColor(255, 255, 255)
    p.alignment = PP_ALIGN.CENTER

    # 항목들
    for i, item in enumerate(data['items']):
        y = Inches(2.5) + Inches(i * 0.7)

        # 체크 아이콘
        check = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            x + Inches(0.2), y,
            Inches(0.25), Inches(0.25)
        )
        check.fill.solid()
        check.fill.fore_color.rgb = accent_color
        check.line.fill.background()

        # 텍스트
        item_box = slide.shapes.add_textbox(
            x + Inches(0.55), y,
            panel_width - Inches(0.75), Inches(0.5)
        )
        tf = item_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = item
        p.font.size = Pt(13)
        p.font.color.rgb = RGBColor(226, 232, 240)
```

### 7. 인용문 슬라이드 (Quote)

```python
def add_quote_slide(slide, prs, quote, author, accent_color):
    """인용문 슬라이드"""
    # 큰 따옴표
    quote_mark = slide.shapes.add_textbox(
        Inches(0.5), Inches(1.5),
        Inches(1), Inches(1.5)
    )
    tf = quote_mark.text_frame
    p = tf.paragraphs[0]
    p.text = """
    p.font.size = Pt(120)
    p.font.color.rgb = accent_color

    # 인용문
    quote_box = slide.shapes.add_textbox(
        Inches(1.2), Inches(2.5),
        Inches(7.5), Inches(2)
    )
    tf = quote_box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = quote
    p.font.size = Pt(28)
    p.font.italic = True
    p.font.color.rgb = RGBColor(255, 255, 255)

    # 저자
    author_box = slide.shapes.add_textbox(
        Inches(1.2), Inches(4.8),
        Inches(7.5), Inches(0.5)
    )
    tf = author_box.text_frame
    p = tf.paragraphs[0]
    p.text = f"— {author}"
    p.font.size = Pt(18)
    p.font.color.rgb = accent_color
```

### 8. 키 메시지 강조 (Key Message)

```python
def add_key_message(slide, prs, message, sub_message, accent_color):
    """키 메시지 강조 슬라이드"""
    # 중앙 강조 박스
    box_width = Inches(8)
    box_height = Inches(2.5)
    x = (prs.slide_width - box_width) / 2
    y = Inches(2.5)

    # 외곽선 박스
    outer_box = slide.shapes.add_shape(
        MSO_SHAPE.ROUNDED_RECTANGLE,
        x - Inches(0.1), y - Inches(0.1),
        box_width + Inches(0.2), box_height + Inches(0.2)
    )
    outer_box.fill.background()
    outer_box.line.color.rgb = accent_color
    outer_box.line.width = Pt(3)

    # 메인 메시지
    msg_box = slide.shapes.add_textbox(
        x, y + Inches(0.5),
        box_width, Inches(1)
    )
    tf = msg_box.text_frame
    p = tf.paragraphs[0]
    p.text = message
    p.font.size = Pt(36)
    p.font.bold = True
    p.font.color.rgb = RGBColor(255, 255, 255)
    p.alignment = PP_ALIGN.CENTER

    # 서브 메시지
    sub_box = slide.shapes.add_textbox(
        x, y + Inches(1.5),
        box_width, Inches(0.6)
    )
    tf = sub_box.text_frame
    p = tf.paragraphs[0]
    p.text = sub_message
    p.font.size = Pt(18)
    p.font.color.rgb = RGBColor(148, 163, 184)
    p.alignment = PP_ALIGN.CENTER
```

### 9. 아이콘 그리드 (Icon Grid)

```python
def add_icon_grid(slide, items, start_x, start_y, cols, accent_colors):
    """아이콘 + 텍스트 그리드"""
    item_width = Inches(2.2)
    item_height = Inches(1.8)
    spacing_x = Inches(0.3)
    spacing_y = Inches(0.3)

    for i, (icon_shape, title, desc) in enumerate(items):
        row = i // cols
        col = i % cols

        x = start_x + col * (item_width + spacing_x)
        y = start_y + row * (item_height + spacing_y)
        color = accent_colors[i % len(accent_colors)]

        # 아이콘 영역
        icon_bg = slide.shapes.add_shape(
            MSO_SHAPE.OVAL,
            x + item_width/2 - Inches(0.4), y,
            Inches(0.8), Inches(0.8)
        )
        icon_bg.fill.solid()
        icon_bg.fill.fore_color.rgb = color
        icon_bg.line.fill.background()

        # 아이콘 (도형으로 표현)
        icon = slide.shapes.add_shape(
            icon_shape,
            x + item_width/2 - Inches(0.2), y + Inches(0.2),
            Inches(0.4), Inches(0.4)
        )
        icon.fill.solid()
        icon.fill.fore_color.rgb = RGBColor(255, 255, 255)
        icon.line.fill.background()

        # 제목
        title_box = slide.shapes.add_textbox(
            x, y + Inches(0.95),
            item_width, Inches(0.4)
        )
        tf = title_box.text_frame
        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(14)
        p.font.bold = True
        p.font.color.rgb = RGBColor(255, 255, 255)
        p.alignment = PP_ALIGN.CENTER

        # 설명
        desc_box = slide.shapes.add_textbox(
            x, y + Inches(1.3),
            item_width, Inches(0.5)
        )
        tf = desc_box.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = desc
        p.font.size = Pt(10)
        p.font.color.rgb = RGBColor(148, 163, 184)
        p.alignment = PP_ALIGN.CENTER
```

## 고급 레이아웃

### 10. 매거진 스타일

```python
def add_magazine_layout(slide, prs, image_placeholder, headline, body_text, accent_color):
    """매거진 스타일 레이아웃"""
    # 이미지 영역 (왼쪽 절반)
    image_bg = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE,
        0, 0, prs.slide_width / 2, prs.slide_height
    )
    image_bg.fill.solid()
    image_bg.fill.fore_color.rgb = RGBColor(30, 41, 59)
    image_bg.line.fill.background()

    # 텍스트 영역 (오른쪽)
    text_x = prs.slide_width / 2 + Inches(0.5)
    text_width = prs.slide_width / 2 - Inches(1)

    # 헤드라인
    headline_box = slide.shapes.add_textbox(
        text_x, Inches(2),
        text_width, Inches(1.5)
    )
    tf = headline_box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = headline
    p.font.size = Pt(32)
    p.font.bold = True
    p.font.color.rgb = RGBColor(255, 255, 255)

    # 본문
    body_box = slide.shapes.add_textbox(
        text_x, Inches(3.8),
        text_width, Inches(2.5)
    )
    tf = body_box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = body_text
    p.font.size = Pt(14)
    p.font.color.rgb = RGBColor(148, 163, 184)
    p.line_spacing = 1.5
```

## 사용 가이드

### 슬라이드 유형별 추천 컴포넌트

| 슬라이드 유형 | 추천 컴포넌트 |
|-------------|-------------|
| 데이터 발표 | stat_card, bar_chart, line_chart |
| 비교 분석 | comparison_slide, styled_table |
| 프로세스 설명 | process_flow, timeline |
| 강조 메시지 | key_message, quote_slide |
| 개요/소개 | icon_grid, magazine_layout |

### 색상 조합 추천

```python
# 데이터 차트용
CHART_COLORS = [
    RGBColor(99, 102, 241),   # Indigo
    RGBColor(236, 72, 153),   # Pink
    RGBColor(14, 165, 233),   # Sky
    RGBColor(34, 197, 94),    # Green
    RGBColor(245, 158, 11),   # Amber
]

# 비교용
COMPARISON_COLORS = [
    RGBColor(99, 102, 241),   # 옵션 A
    RGBColor(236, 72, 153),   # 옵션 B
]

# 타임라인/프로세스용
SEQUENCE_COLORS = [
    RGBColor(139, 92, 246),
    RGBColor(99, 102, 241),
    RGBColor(14, 165, 233),
    RGBColor(20, 184, 166),
    RGBColor(34, 197, 94),
]
```
