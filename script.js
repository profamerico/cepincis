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

function initProjectsShowcase() {
    const showcase = document.querySelector('.secao-publicacoes-recentes');
    const carousel = document.getElementById('carrosselPublicacoes');
    const buttons = Array.from(showcase?.querySelectorAll('.filtros-tags button') || []);
    const cards = Array.from(carousel?.querySelectorAll('[data-project-card="true"]') || []);
    const searchField = document.getElementById('campoPesquisa');
    const prevButton = showcase?.querySelector('[data-carousel-prev]');
    const nextButton = showcase?.querySelector('[data-carousel-next]');
    const indicatorsContainer = showcase?.querySelector('[data-carousel-indicators]');
    const emptyMessage = showcase?.querySelector('[data-carousel-empty-message]');

    if (!showcase || !carousel || !buttons.length || !cards.length || !searchField || !prevButton || !nextButton || !indicatorsContainer) {
        return;
    }

    const hasRealProjects = cards.some((card) => !card.hasAttribute('data-empty-state'));
    let activeTag = buttons.find((button) => button.classList.contains('active'))?.dataset.tag || 'todos';
    let currentIndex = 0;
    let filteredCards = [];
    let autoPlayId = null;

    function normalizeValue(value) {
        return String(value || '').trim().toLowerCase();
    }

    function stopAutoPlay() {
        if (autoPlayId !== null) {
            window.clearInterval(autoPlayId);
            autoPlayId = null;
        }
    }

    function setControlState() {
        const disableNavigation = !hasRealProjects || filteredCards.length <= 1;
        prevButton.disabled = disableNavigation;
        nextButton.disabled = disableNavigation;
        indicatorsContainer.hidden = disableNavigation || filteredCards.length === 0;
    }

    function updateActiveCards() {
        cards.forEach((card) => {
            card.classList.toggle('active', filteredCards[currentIndex] === card);
        });
    }

    function renderIndicators() {
        indicatorsContainer.innerHTML = '';

        if (!hasRealProjects || filteredCards.length <= 1) {
            return;
        }

        filteredCards.forEach((card, index) => {
            const indicator = document.createElement('button');
            indicator.type = 'button';
            indicator.className = 'ponto-indicador-carrossel';
            indicator.classList.toggle('active', index === currentIndex);
            indicator.setAttribute('aria-label', `Ir para o projeto ${index + 1}`);
            indicator.addEventListener('click', () => {
                goToSlide(index);
            });
            indicatorsContainer.appendChild(indicator);
        });
    }

    function scrollToCurrent(options = {}) {
        const behavior = options.behavior || 'smooth';
        const currentCard = filteredCards[currentIndex];

        if (!currentCard) {
            carousel.scrollTo({ left: 0, behavior });
            updateActiveCards();
            renderIndicators();
            setControlState();
            return;
        }

        const left = Math.max(currentCard.offsetLeft - carousel.offsetLeft, 0);
        carousel.scrollTo({ left, behavior });
        updateActiveCards();
        renderIndicators();
        setControlState();
    }

    function startAutoPlay() {
        stopAutoPlay();

        if (!hasRealProjects || filteredCards.length <= 1) {
            return;
        }

        autoPlayId = window.setInterval(() => {
            currentIndex = (currentIndex + 1) % filteredCards.length;
            scrollToCurrent({ behavior: 'smooth' });
        }, 5500);
    }

    function goToSlide(index) {
        if (!filteredCards.length) {
            return;
        }

        currentIndex = (index + filteredCards.length) % filteredCards.length;
        scrollToCurrent({ behavior: 'smooth' });
        startAutoPlay();
    }

    function navigate(direction) {
        if (filteredCards.length <= 1) {
            return;
        }

        goToSlide(currentIndex + direction);
    }

    function applyFilters() {
        const term = normalizeValue(searchField.value);
        const currentTag = normalizeValue(activeTag);

        filteredCards = cards.filter((card) => {
            if (card.hasAttribute('data-empty-state')) {
                return !hasRealProjects;
            }

            const tagList = normalizeValue(card.dataset.tagList).split('||').filter(Boolean);
            const searchableContent = [
                card.dataset.title,
                card.dataset.category,
                card.dataset.status,
                card.dataset.tags,
            ].map(normalizeValue).join(' ');

            const matchesTag = currentTag === 'todos' || tagList.includes(currentTag);
            const matchesSearch = term === '' || searchableContent.includes(term);

            return matchesTag && matchesSearch;
        });

        cards.forEach((card) => {
            card.hidden = !filteredCards.includes(card);
        });

        const hasNoResults = hasRealProjects && filteredCards.length === 0;
        if (emptyMessage) {
            emptyMessage.hidden = !hasNoResults;
        }

        if (hasNoResults) {
            currentIndex = 0;
            stopAutoPlay();
            carousel.scrollTo({ left: 0, behavior: 'smooth' });
            updateActiveCards();
            renderIndicators();
            setControlState();
            return;
        }

        if (currentIndex >= filteredCards.length) {
            currentIndex = 0;
        }

        scrollToCurrent({ behavior: 'smooth' });
        startAutoPlay();
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            activeTag = button.dataset.tag || 'todos';
            applyFilters();
        });
    });

    searchField.addEventListener('input', applyFilters);
    prevButton.addEventListener('click', () => navigate(-1));
    nextButton.addEventListener('click', () => navigate(1));

    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);

    window.addEventListener('resize', () => {
        if (filteredCards.length) {
            scrollToCurrent({ behavior: 'auto' });
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoPlay();
            return;
        }

        startAutoPlay();
    });

    applyFilters();
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
    initProjectsShowcase();
    initSmoothScroll();
    initTeamCarousel();
    initFloatingButtonObserver();
    initInnovationCards();
    initSettingsTabs();
});
