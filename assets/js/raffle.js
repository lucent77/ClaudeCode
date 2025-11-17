/**
 * 룰렛 추첨 애니메이션 클래스
 */
class RaffleWheel {
    constructor(containerId, prizes) {
        this.container = document.getElementById(containerId);
        this.prizes = prizes;
        this.isSpinning = false;
        this.rotation = 0;
        this.radius = 250;
        this.centerX = 300;
        this.centerY = 300;

        this.init();
    }

    /**
     * SVG 룰렛 초기화
     */
    init() {
        if (!this.container) return;

        const svg = this.createSVG();
        this.container.innerHTML = '';
        this.container.appendChild(svg);
    }

    /**
     * SVG 엘리먼트 생성
     */
    createSVG() {
        const svgNS = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNS, "svg");
        svg.setAttribute("viewBox", "0 0 600 600");
        svg.setAttribute("class", "raffle-wheel");
        svg.setAttribute("id", "raffleWheelSVG");

        // 그룹 생성 (회전용)
        const wheelGroup = document.createElementNS(svgNS, "g");
        wheelGroup.setAttribute("id", "wheelGroup");
        wheelGroup.setAttribute("transform", `rotate(0, ${this.centerX}, ${this.centerY})`);

        // 각 상품 섹션 그리기
        const totalPrizes = this.prizes.length;
        const anglePerPrize = 360 / totalPrizes;

        this.prizes.forEach((prize, index) => {
            const startAngle = index * anglePerPrize;
            const endAngle = (index + 1) * anglePerPrize;

            // 섹션 그리기
            const section = this.createWheelSection(
                startAngle,
                endAngle,
                prize.color,
                prize.name,
                index
            );

            wheelGroup.appendChild(section);
        });

        // 중앙 원 (장식용)
        const centerCircle = document.createElementNS(svgNS, "circle");
        centerCircle.setAttribute("cx", this.centerX);
        centerCircle.setAttribute("cy", this.centerY);
        centerCircle.setAttribute("r", "30");
        centerCircle.setAttribute("fill", "#1F2937");
        centerCircle.setAttribute("stroke", "#F59E0B");
        centerCircle.setAttribute("stroke-width", "4");

        // 포인터 (위쪽 화살표)
        const pointer = this.createPointer();

        svg.appendChild(wheelGroup);
        svg.appendChild(centerCircle);
        svg.appendChild(pointer);

        return svg;
    }

    /**
     * 룰렛 섹션 생성
     */
    createWheelSection(startAngle, endAngle, color, text, index) {
        const svgNS = "http://www.w3.org/2000/svg";
        const group = document.createElementNS(svgNS, "g");
        group.setAttribute("class", "wheel-section");
        group.setAttribute("data-index", index);

        // 각도를 라디안으로 변환
        const startRad = (startAngle - 90) * Math.PI / 180;
        const endRad = (endAngle - 90) * Math.PI / 180;

        // 좌표 계산
        const x1 = this.centerX + this.radius * Math.cos(startRad);
        const y1 = this.centerY + this.radius * Math.sin(startRad);
        const x2 = this.centerX + this.radius * Math.cos(endRad);
        const y2 = this.centerY + this.radius * Math.sin(endRad);

        // 큰 호 플래그
        const largeArcFlag = (endAngle - startAngle) > 180 ? 1 : 0;

        // 패스 생성
        const path = document.createElementNS(svgNS, "path");
        const pathData = [
            `M ${this.centerX} ${this.centerY}`,
            `L ${x1} ${y1}`,
            `A ${this.radius} ${this.radius} 0 ${largeArcFlag} 1 ${x2} ${y2}`,
            `Z`
        ].join(' ');

        path.setAttribute("d", pathData);
        path.setAttribute("fill", color);
        path.setAttribute("stroke", "#FFFFFF");
        path.setAttribute("stroke-width", "2");

        // 텍스트 추가
        const midAngle = (startAngle + endAngle) / 2 - 90;
        const midRad = midAngle * Math.PI / 180;
        const textRadius = this.radius * 0.7;
        const textX = this.centerX + textRadius * Math.cos(midRad);
        const textY = this.centerY + textRadius * Math.sin(midRad);

        const textElement = document.createElementNS(svgNS, "text");
        textElement.setAttribute("x", textX);
        textElement.setAttribute("y", textY);
        textElement.setAttribute("text-anchor", "middle");
        textElement.setAttribute("dominant-baseline", "middle");
        textElement.setAttribute("fill", "#FFFFFF");
        textElement.setAttribute("font-size", "14");
        textElement.setAttribute("font-weight", "bold");
        textElement.setAttribute("transform", `rotate(${midAngle + 90}, ${textX}, ${textY})`);
        textElement.setAttribute("class", "pointer-events-none select-none");
        textElement.textContent = this.truncateText(text, 15);

        group.appendChild(path);
        group.appendChild(textElement);

        return group;
    }

    /**
     * 포인터 생성 (상단 화살표)
     */
    createPointer() {
        const svgNS = "http://www.w3.org/2000/svg";
        const pointer = document.createElementNS(svgNS, "polygon");
        const points = `${this.centerX},40 ${this.centerX - 20},80 ${this.centerX + 20},80`;

        pointer.setAttribute("points", points);
        pointer.setAttribute("fill", "#EF4444");
        pointer.setAttribute("stroke", "#FFFFFF");
        pointer.setAttribute("stroke-width", "3");
        pointer.setAttribute("filter", "drop-shadow(0 4px 6px rgba(0,0,0,0.3))");

        return pointer;
    }

    /**
     * 텍스트 자르기
     */
    truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * 룰렛 회전 애니메이션
     */
    spin(winningIndex, onComplete) {
        if (this.isSpinning) return;

        this.isSpinning = true;
        const wheelGroup = document.getElementById('wheelGroup');

        // 회전 각도 계산
        const totalPrizes = this.prizes.length;
        const anglePerPrize = 360 / totalPrizes;

        // 당첨 위치를 상단(0도)에 맞추기 위한 각도 계산
        const targetAngle = -(winningIndex * anglePerPrize + anglePerPrize / 2);

        // 5바퀴 회전 + 목표 각도
        const finalRotation = 360 * 5 + targetAngle;

        // 애니메이션 시작
        const duration = 5000; // 5초
        const startTime = Date.now();
        const startRotation = this.rotation;

        const animate = () => {
            const currentTime = Date.now();
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Ease-out cubic 함수
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);

            // 현재 회전 각도 계산
            const currentRotation = startRotation + (finalRotation - startRotation) * easeOutCubic;
            this.rotation = currentRotation;

            // 룰렛 회전
            wheelGroup.setAttribute(
                "transform",
                `rotate(${currentRotation}, ${this.centerX}, ${this.centerY})`
            );

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                this.isSpinning = false;
                if (onComplete) {
                    setTimeout(() => onComplete(), 500);
                }
            }
        };

        animate();
    }

    /**
     * 룰렛 리셋
     */
    reset() {
        this.rotation = 0;
        const wheelGroup = document.getElementById('wheelGroup');
        if (wheelGroup) {
            wheelGroup.setAttribute(
                "transform",
                `rotate(0, ${this.centerX}, ${this.centerY})`
            );
        }
        this.isSpinning = false;
    }
}

/**
 * API 호출 헬퍼 함수
 */
const RaffleAPI = {
    async getPrizes() {
        const response = await fetch('/api/get_prizes.php');
        const data = await response.json();
        if (!data.success) throw new Error(data.error);
        return data;
    },

    async draw(email) {
        const response = await fetch('/api/draw.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.error);
        return data;
    },

    async getWinners(limit = 100, offset = 0) {
        const response = await fetch(`/api/get_winners.php?limit=${limit}&offset=${offset}`);
        const data = await response.json();
        if (!data.success) throw new Error(data.error);
        return data;
    }
};
