const teamMembers = [
    { name: "Universidade de Copenhagen", role: "Copenhagen, Dinamarca" },
    { name: "Universidade de Roma 3", role: "Roma, Italia" },
    { name: "Universidade de Fuzhou", role: "Instituicao publica de ensino superior localizada em Fuzhou, capital da provincia de Fujian, na China." },
    { name: "GETIS", role: "Grupo de Pesquisa em Engenharia, Tecnologia, Inovacao e Sustentabilidade (GETIS) - IFSP-CAR" },
    { name: "i2", role: "Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR" },
    { name: "ENASA", role: "Grupo de pesquisa em Energia, Agua e Saneamento (ENASA) - IFSP-SP" }
];

function initCursorOrb() {
    const ball = document.querySelector('.ball');
    if (!ball) {
        return;
    }

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let ballX = mouseX;
    let ballY = mouseY;
    const easing = 0.067;

    function animate() {
        ballX += (mouseX - ballX) * easing;
        ballY += (mouseY - ballY) * easing;

        ball.style.left = `${ballX}px`;
        ball.style.top = `${ballY}px`;

        requestAnimationFrame(animate);
    }

    document.addEventListener('mousemove', (event) => {
        mouseX = event.pageX;
        mouseY = event.pageY;
    });

    animate();
}

function initExpandableText() {
    const btn = document.querySelector('.btn-ver-mais');
    const text = document.querySelector('.text-collapsed');

    if (!btn || !text) {
        return;
    }

    let closeTimeout = null;

    function collapseText() {
        text.classList.remove('text-expanded');
        btn.textContent = 'Ver Mais';

        const rect = btn.getBoundingClientRect();
        const scrollY = window.scrollY || 0;
        const absolutePosition = scrollY + rect.top;

        window.scrollTo({
            top: absolutePosition - 100,
            behavior: 'smooth'
        });
    }

    btn.addEventListener('click', () => {
        text.classList.toggle('text-expanded');
        btn.textContent = text.classList.contains('text-expanded') ? 'Ver Menos' : 'Ver Mais';

        setTimeout(() => {
            if (btn.textContent.trim() === 'Ver Mais') {
                const rect = btn.getBoundingClientRect();
                const scrollY = window.scrollY || 0;
                const absolutePosition = scrollY + rect.top;

                window.scrollTo({
                    top: absolutePosition - 100,
                    behavior: 'smooth'
                });
            }
        }, 50);
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const isExpanded = text.classList.contains('text-expanded');

            if (!entry.isIntersecting && entry.intersectionRatio < 0.4) {
                if (isExpanded && !closeTimeout) {
                    closeTimeout = setTimeout(() => {
                        if (text.classList.contains('text-expanded')) {
                            collapseText();
                        }
                        closeTimeout = null;
                    }, 3532);
                }
            } else if (closeTimeout) {
                clearTimeout(closeTimeout);
                closeTimeout = null;
            }
        });
    }, {
        threshold: [0.4]
    });

    observer.observe(text);
}

function initPublicationFilters() {
    const buttons = Array.from(document.querySelectorAll('.filtros-tags button'));
    const cards = Array.from(document.querySelectorAll('.card-publicacao-carrossel'));
    const searchField = document.getElementById('campoPesquisa');

    if (!buttons.length || !cards.length || !searchField) {
        return;
    }

    let activeTag = buttons.find((button) => button.classList.contains('active'))?.dataset.tag || 'todos';

    function filterCards() {
        const term = searchField.value.toLowerCase();

        cards.forEach((card) => {
            const tags = (card.dataset.tags || '').toLowerCase();
            const title = (card.querySelector('h2')?.textContent || '').toLowerCase();
            const matchesTag = activeTag === 'todos' || tags.includes(activeTag.toLowerCase());
            const matchesText = title.includes(term) || tags.includes(term);

            card.style.display = matchesTag && matchesText ? 'block' : 'none';
        });
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            activeTag = button.dataset.tag || 'todos';
            filterCards();
        });
    });

    searchField.addEventListener('keyup', filterCards);
}

function initSmoothScroll() {
    if (!document.body.classList.contains('smooth-scroll-page')) {
        return;
    }

    const scrollContainer = document.querySelector('.js-scroll');
    if (!scrollContainer) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let offset = window.pageYOffset;
    const easing = prefersReducedMotion ? 1 : 0.08;

    function syncBodyHeight() {
        const height = Math.max(scrollContainer.getBoundingClientRect().height, scrollContainer.scrollHeight);
        document.body.style.height = `${Math.floor(height)}px`;
    }

    function step() {
        offset += (window.pageYOffset - offset) * easing;
        scrollContainer.style.transform = `translate3d(0, ${-offset}px, 0)`;
        requestAnimationFrame(step);
    }

    syncBodyHeight();
    window.addEventListener('load', syncBodyHeight);
    window.addEventListener('resize', syncBodyHeight);
    step();
}

function initTeamCarousel() {
    const cards = Array.from(document.querySelectorAll('.card'));
    const dots = Array.from(document.querySelectorAll('.dot'));
    const memberName = document.querySelector('.member-name');
    const memberRole = document.querySelector('.member-role');
    const leftArrow = document.querySelector('.nav-arrow.left');
    const rightArrow = document.querySelector('.nav-arrow.right');

    if (!cards.length || !memberName || !memberRole) {
        return;
    }

    let currentIndex = 0;
    let isAnimating = false;
    let touchStartX = 0;
    let touchEndX = 0;
    const dotStep = dots.length ? Math.max(1, Math.ceil(cards.length / dots.length)) : 1;

    function updateCarousel(newIndex) {
        if (isAnimating) {
            return;
        }

        isAnimating = true;
        currentIndex = (newIndex + cards.length) % cards.length;

        cards.forEach((card, index) => {
            const offset = (index - currentIndex + cards.length) % cards.length;

            card.classList.remove('center', 'left-1', 'left-2', 'right-1', 'right-2', 'hidden');

            if (offset === 0) {
                card.classList.add('center');
            } else if (offset === 1) {
                card.classList.add('right-1');
            } else if (offset === 2) {
                card.classList.add('right-2');
            } else if (offset === cards.length - 1) {
                card.classList.add('left-1');
            } else if (offset === cards.length - 2) {
                card.classList.add('left-2');
            } else {
                card.classList.add('hidden');
            }
        });

        if (dots.length) {
            const activeDotIndex = Math.floor(currentIndex / dotStep) % dots.length;
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === activeDotIndex);
            });
        }

        memberName.style.opacity = '0';
        memberRole.style.opacity = '0';

        setTimeout(() => {
            memberName.textContent = teamMembers[currentIndex]?.name || '';
            memberRole.textContent = teamMembers[currentIndex]?.role || '';
            memberName.style.opacity = '1';
            memberRole.style.opacity = '1';
        }, 300);

        setTimeout(() => {
            isAnimating = false;
        }, 800);
    }

    leftArrow?.addEventListener('click', () => updateCarousel(currentIndex - 1));
    rightArrow?.addEventListener('click', () => updateCarousel(currentIndex + 1));

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            updateCarousel(index * dotStep);
        });
    });

    cards.forEach((card, index) => {
        card.addEventListener('click', () => {
            updateCarousel(index);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            updateCarousel(currentIndex - 1);
        } else if (event.key === 'ArrowRight') {
            updateCarousel(currentIndex + 1);
        }
    });

    document.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0].screenX;
    });

    document.addEventListener('touchend', (event) => {
        touchEndX = event.changedTouches[0].screenX;
        const swipeThreshold = 50;
        const difference = touchStartX - touchEndX;

        if (Math.abs(difference) > swipeThreshold) {
            updateCarousel(difference > 0 ? currentIndex + 1 : currentIndex - 1);
        }
    });

    updateCarousel(0);
}

function initPublicationCarousel() {
    const carousel = document.getElementById('carrosselPublicacoes');
    const cards = Array.from(document.querySelectorAll('.card-publicacao-carrossel'));
    const overlay = document.getElementById('overlayTelaCheia');
    const expandedCarousel = document.getElementById('carrosselExpandido');

    if (!carousel || !cards.length) {
        return;
    }

    let currentSlide = 0;
    let expandedSlide = 0;
    let autoPlay = null;

    function updateIndicators() {
        const indicators = Array.from(document.querySelectorAll('.ponto-indicador-carrossel'));
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentSlide);
        });
    }

    function updateActiveCards() {
        cards.forEach((card, index) => {
            card.classList.toggle('active', index === currentSlide);
        });
    }

    function navigate(direction) {
        const firstCard = carousel.querySelector('.card-publicacao-carrossel');
        if (!firstCard) {
            return;
        }

        const totalSlides = cards.length;
        const cardWidth = firstCard.offsetWidth + 30;
        currentSlide += direction;

        if (currentSlide < 0) {
            currentSlide = totalSlides - 1;
        } else if (currentSlide >= totalSlides) {
            currentSlide = 0;
        }

        carousel.scrollTo({
            left: currentSlide * cardWidth,
            behavior: 'smooth'
        });

        updateIndicators();
        updateActiveCards();
    }

    function goToSlide(index) {
        const firstCard = carousel.querySelector('.card-publicacao-carrossel');
        if (!firstCard) {
            return;
        }

        currentSlide = index;
        carousel.scrollTo({
            left: currentSlide * (firstCard.offsetWidth + 30),
            behavior: 'smooth'
        });

        updateIndicators();
        updateActiveCards();
    }

    function navigateExpanded(direction) {
        if (!expandedCarousel) {
            return;
        }

        const firstCard = expandedCarousel.querySelector('.card-expandido');
        if (!firstCard) {
            return;
        }

        expandedSlide += direction;

        if (expandedSlide < 0) {
            expandedSlide = cards.length - 1;
        } else if (expandedSlide >= cards.length) {
            expandedSlide = 0;
        }

        expandedCarousel.scrollTo({
            left: expandedSlide * (firstCard.offsetWidth + 40),
            behavior: 'smooth'
        });
    }

    function openExpanded() {
        if (!overlay) {
            return;
        }

        overlay.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }

    function closeExpanded() {
        if (!overlay) {
            return;
        }

        overlay.classList.remove('ativo');
        document.body.style.overflow = '';
    }

    function startAutoPlay() {
        if (autoPlay) {
            clearInterval(autoPlay);
        }

        autoPlay = setInterval(() => {
            navigate(1);
        }, 5000);
    }

    carousel.addEventListener('mouseenter', () => {
        if (autoPlay) {
            clearInterval(autoPlay);
        }
    });

    carousel.addEventListener('mouseleave', startAutoPlay);

    document.addEventListener('keydown', (event) => {
        const overlayActive = overlay?.classList.contains('ativo');

        if (event.key === 'Escape' && overlayActive) {
            closeExpanded();
        } else if (event.key === 'ArrowLeft') {
            overlayActive ? navigateExpanded(-1) : navigate(-1);
        } else if (event.key === 'ArrowRight') {
            overlayActive ? navigateExpanded(1) : navigate(1);
        }
    });

    window.navegarCarrosselPublicacoes = navigate;
    window.irParaSlidePublicacao = goToSlide;
    window.navegarCarrosselExpandido = navigateExpanded;
    window.abrirModoExpandido = openExpanded;
    window.fecharModoExpandido = closeExpanded;

    updateIndicators();
    updateActiveCards();
    startAutoPlay();
}

function initFloatingButtonObserver() {
    const button = document.querySelector('.botao-ver-mais-lateral');
    if (!button) {
        return;
    }

    function toggleButtonVisibility() {
        button.classList.toggle('ativo', window.scrollY > 300);
    }

    window.addEventListener('scroll', toggleButtonVisibility);
    toggleButtonVisibility();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            const sectionId = entry.target.id;
            button.classList.remove('secao-sobre', 'secao-implementacao', 'secao-regulamento', 'secao-parceiros', 'secao-contato');

            if (sectionId === 'sobre') {
                button.classList.add('secao-sobre');
            } else if (sectionId === 'implementacao') {
                button.classList.add('secao-implementacao');
            } else if (sectionId === 'regulamento') {
                button.classList.add('secao-regulamento');
            } else if (sectionId === 'parceiros') {
                button.classList.add('secao-parceiros');
            } else if (sectionId === 'contato') {
                button.classList.add('secao-contato');
            }
        });
    }, {
        threshold: 0.3,
        rootMargin: '-10% 0px -10% 0px'
    });

    ['sobre', 'implementacao', 'regulamento', 'parceiros', 'contato'].forEach((id) => {
        const section = document.getElementById(id);
        if (section) {
            observer.observe(section);
        }
    });
}

function initInnovationCards() {
    const cards = Array.from(document.querySelectorAll('.card-projeto-inovacao'));
    if (!cards.length) {
        return;
    }

    function toggleProjectCard(element) {
        const isExpanded = element.classList.contains('expandido');

        cards.forEach((card) => {
            card.classList.remove('expandido');
        });

        if (!isExpanded) {
            element.classList.add('expandido');
        }
    }

    window.alternarExpandirProjeto = toggleProjectCard;

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.card-projeto-inovacao')) {
            cards.forEach((card) => {
                card.classList.remove('expandido');
            });
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cards.forEach((card) => {
                card.classList.remove('expandido');
            });
        }
    });
}

function initSettingsTabs() {
    const buttons = Array.from(document.querySelectorAll('[data-tab-target]'));
    const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

    if (!buttons.length || !panels.length) {
        return;
    }

    function openTab(tabName) {
        panels.forEach((panel) => {
            panel.classList.toggle('active', panel.dataset.tabPanel === tabName);
        });

        buttons.forEach((button) => {
            button.classList.toggle('active', button.dataset.tabTarget === tabName);
        });
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            openTab(button.dataset.tabTarget);
        });
    });

    openTab(buttons.find((button) => button.classList.contains('active'))?.dataset.tabTarget || buttons[0].dataset.tabTarget);
}

document.addEventListener('DOMContentLoaded', () => {
    initCursorOrb();
    initExpandableText();
    initPublicationFilters();
    initSmoothScroll();
    initTeamCarousel();
    initPublicationCarousel();
    initFloatingButtonObserver();
    initInnovationCards();
    initSettingsTabs();
});
