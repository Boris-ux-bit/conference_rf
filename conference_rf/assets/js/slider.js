// =====================================================
// СЛАЙДЕР ДЛЯ ПОРТАЛА «Конференции.РФ»
// Автоматическое переключение каждые 3 секунды
// =====================================================

class ConferenceSlider {
    constructor(containerId, images) {
        this.container = document.getElementById(containerId);
        this.images = images;
        this.currentIndex = 0;
        this.interval = null;
        
        if (this.container) {
            this.init();
        }
    }
    
    init() {
        // Создаём HTML структуру слайдера
        this.container.innerHTML = `
            <div class="slider-container">
                <div class="slider-track">
                    ${this.images.map(img => `
                        <div class="slide">
                            <img src="${img.src}" alt="${img.alt}" loading="lazy">
                        </div>
                    `).join('')}
                </div>
                <button class="slider-btn prev-btn">❮</button>
                <button class="slider-btn next-btn">❯</button>
                <div class="slider-dots">
                    ${this.images.map((_, i) => `
                        <span class="dot ${i === 0 ? 'active' : ''}" data-index="${i}"></span>
                    `).join('')}
                </div>
            </div>
        `;
        
        this.addStyles();
        this.attachEvents();
        this.startAutoSlide();
    }
    
    addStyles() {
        if (document.getElementById('slider-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'slider-styles';
        style.textContent = `
            .slider-container {
                position: relative;
                width: 100%;
                height: 250px;
                overflow: hidden;
                border-radius: 15px;
                margin-bottom: 30px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .slider-track {
                display: flex;
                transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                height: 100%;
            }
            
            .slide {
                min-width: 100%;
                height: 100%;
            }
            
            .slide img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .slider-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(0,0,0,0.5);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 20px;
                transition: all 0.3s ease;
                z-index: 10;
            }
            
            .slider-btn:hover {
                background: rgba(0,0,0,0.8);
                transform: translateY(-50%) scale(1.1);
            }
            
            .prev-btn {
                left: 10px;
            }
            
            .next-btn {
                right: 10px;
            }
            
            .slider-dots {
                position: absolute;
                bottom: 15px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                gap: 10px;
                z-index: 10;
            }
            
            .dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: rgba(255,255,255,0.5);
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            }
            
            .dot:hover {
                transform: scale(1.3);
            }
            
            .dot.active {
                background: white;
                width: 20px;
                border-radius: 5px;
            }
            
            @media (max-width: 480px) {
                .slider-container {
                    height: 200px;
                }
                
                .slider-btn {
                    width: 30px;
                    height: 30px;
                    font-size: 16px;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    attachEvents() {
        const prevBtn = this.container.querySelector('.prev-btn');
        const nextBtn = this.container.querySelector('.next-btn');
        const dots = this.container.querySelectorAll('.dot');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.prev());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.next());
        }
        
        dots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.goTo(index);
            });
        });
        
        // Остановка автопереключения при наведении
        this.container.addEventListener('mouseenter', () => this.stopAutoSlide());
        this.container.addEventListener('mouseleave', () => this.startAutoSlide());
    }
    
    update() {
        const track = this.container.querySelector('.slider-track');
        const dots = this.container.querySelectorAll('.dot');
        
        if (track) {
            track.style.transform = `translateX(-${this.currentIndex * 100}%)`;
        }
        
        dots.forEach((dot, i) => {
            if (i === this.currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.update();
        this.resetAutoSlide();
    }
    
    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.update();
        this.resetAutoSlide();
    }
    
    goTo(index) {
        this.currentIndex = index;
        this.update();
        this.resetAutoSlide();
    }
    
    startAutoSlide() {
        if (this.interval) clearInterval(this.interval);
        this.interval = setInterval(() => {
            this.next();
        }, 3000);
    }
    
    stopAutoSlide() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
    
    resetAutoSlide() {
        this.stopAutoSlide();
        this.startAutoSlide();
    }
}

// Инициализация слайдера при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Массив с изображениями помещений для конференций
    const images = [
        {
            src: 'assets/images/room1.jpg',
            alt: 'Конференц-зал'
        },
        {
            src: 'assets/images/room2.jpg',
            alt: 'Коворкинг центр'
        },
        {
            src: 'assets/images/room3.jpg',
            alt: 'Кинозал для конференций'
        },
        {
            src: 'assets/images/room4.jpg',
            alt: 'Аудитория'
        }
    ];
    
    const sliderContainer = document.getElementById('conference-slider');
    if (sliderContainer) {
        new ConferenceSlider('conference-slider', images);
    }
});