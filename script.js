const teamMembers = [
    { name: "Universidade de Copenhagen", role: "Copenhagen, Dinamarca" },
    { name: "Universidade de Roma 3", role: "Roma, Italia" },
    { name: "Universidade de Fuzhou", role: "Instituicao publica de ensino superior localizada em Fuzhou, capital da provincia de Fujian, na China." },
    { name: "GETIS", role: "Grupo de Pesquisa em Engenharia, Tecnologia, Inovacao e Sustentabilidade (GETIS) - IFSP-CAR" },
    { name: "i2", role: "Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR" },
    { name: "ENASA", role: "Grupo de pesquisa em Energia, Agua e Saneamento (ENASA) - IFSP-SP" }
];

const reducedMotionQuery = typeof window.matchMedia === 'function'
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : { matches: false };

function prefersReducedMotion() {
    return Boolean(reducedMotionQuery.matches);
}

function getHeaderOffset() {
    const header = document.querySelector('header');
    return (header ? header.offsetHeight : 0) + 18;
}

function getHashTarget(hash) {
    if (!hash || hash === '#') {
        return null;
    }

    const normalizedHash = hash.startsWith('#') ? hash.slice(1) : hash;
    if (!normalizedHash) {
        return null;
    }

    const decodedHash = decodeURIComponent(normalizedHash);
    const escapedHash = decodedHash.replace(/"/g, '\\"');

    return document.getElementById(decodedHash) || document.querySelector(`[name="${escapedHash}"]`);
}

function scrollToHashTarget(target, behavior = 'smooth') {
    if (!target) {
        return;
    }

    const targetPosition = window.pageYOffset + target.getBoundingClientRect().top - getHeaderOffset();
    const safeBehavior = prefersReducedMotion() ? 'auto' : behavior;

    window.scrollTo({
        top: Math.max(0, targetPosition),
        behavior: safeBehavior
    });
}

function toElementArray(targets) {
    if (!targets) {
        return [];
    }

    if (typeof targets === 'string') {
        return Array.from(document.querySelectorAll(targets));
    }

    if (targets instanceof Element) {
        return [targets];
    }

    return Array.from(targets).filter((target) => target instanceof Element);
}

function markMotionTargets(targets) {
    toElementArray(targets).forEach((target) => {
        target.dataset.motionBound = 'true';
    });
}

function animateActiveTab(panel) {
    if (!panel || prefersReducedMotion() || typeof window.gsap === 'undefined') {
        return;
    }

    window.gsap.killTweensOf(panel);
    window.gsap.fromTo(panel, {
        autoAlpha: 0,
        y: 18
    }, {
        autoAlpha: 1,
        y: 0,
        duration: 0.42,
        ease: 'power2.out',
        clearProps: 'opacity,visibility,transform'
    });
}

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
            behavior: prefersReducedMotion() ? 'auto' : 'smooth'
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
                    behavior: prefersReducedMotion() ? 'auto' : 'smooth'
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
    const body = document.body;
    const scrollContainer = document.querySelector('.js-scroll');

    body.classList.remove('smooth-scroll-active');
    document.body.style.removeProperty('height');

    if (scrollContainer) {
        scrollContainer.style.removeProperty('transform');
    }
}

function initGlobalSmoothScroll() {
    const currentPath = window.location.pathname.replace(/\/+$/, '');

    document.querySelectorAll('a[href*="#"]').forEach((link) => {
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return;
        }

        let parsedUrl;
        try {
            parsedUrl = new URL(href, window.location.href);
        } catch (error) {
            return;
        }

        const targetPath = parsedUrl.pathname.replace(/\/+$/, '');
        const isSamePage = parsedUrl.origin === window.location.origin && targetPath === currentPath;

        if (!isSamePage || !parsedUrl.hash) {
            return;
        }

        link.addEventListener('click', (event) => {
            const target = getHashTarget(parsedUrl.hash);
            if (!target) {
                return;
            }

            event.preventDefault();
            scrollToHashTarget(target);

            if (window.history && window.history.pushState) {
                window.history.pushState(null, '', parsedUrl.hash);
            } else {
                window.location.hash = parsedUrl.hash;
            }
        });
    });
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
        let activePanel = null;

        panels.forEach((panel) => {
            const isActive = panel.dataset.tabPanel === tabName;
            panel.classList.toggle('active', isActive);

            if (isActive) {
                activePanel = panel;
            }
        });

        buttons.forEach((button) => {
            button.classList.toggle('active', button.dataset.tabTarget === tabName);
        });

        animateActiveTab(activePanel);
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            openTab(button.dataset.tabTarget);
        });
    });

    openTab(buttons.find((button) => button.classList.contains('active'))?.dataset.tabTarget || buttons[0].dataset.tabTarget);
}

function initGsapPageMotion() {
    if (prefersReducedMotion() || typeof window.gsap === 'undefined') {
        return;
    }

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;

    if (ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
    }

    function getScrollTriggerConfig(trigger, start = 'top 86%') {
        if (!ScrollTrigger || !trigger) {
            return null;
        }

        return {
            trigger,
            start,
            once: true
        };
    }

    function intro(timeline, targets, vars, position) {
        const elements = toElementArray(targets).filter((element) => !element.dataset.motionBound);
        if (!elements.length) {
            return;
        }

        markMotionTargets(elements);
        timeline.from(elements, {
            clearProps: 'opacity,visibility,transform',
            ...vars
        }, position);
    }

    function reveal(targets, options = {}) {
        const elements = toElementArray(targets).filter((element) => !element.dataset.motionBound);
        if (!elements.length) {
            return;
        }

        elements.forEach((element, index) => {
            markMotionTargets(element);

            const animationConfig = {
                autoAlpha: 0,
                x: options.x ?? 0,
                y: options.y ?? 44,
                scale: options.scale ?? 1,
                duration: options.duration ?? 0.82,
                ease: options.ease ?? 'power3.out',
                clearProps: 'opacity,visibility,transform'
            };

            if (options.stagger) {
                animationConfig.delay = index * options.stagger;
            }

            const triggerConfig = getScrollTriggerConfig(element, options.start);
            if (triggerConfig) {
                animationConfig.scrollTrigger = triggerConfig;
            }

            gsap.from(element, animationConfig);
        });
    }

    const introTimeline = gsap.timeline({
        defaults: {
            ease: 'power3.out'
        }
    });

    intro(introTimeline, 'header .logo', {
        autoAlpha: 0,
        y: -18,
        duration: 0.62
    }, 0);

    intro(introTimeline, 'header .site-nav-links a', {
        autoAlpha: 0,
        y: -14,
        duration: 0.42,
        stagger: 0.05
    }, 0.08);

    intro(introTimeline, 'header .header-user-badge, header .site-nav-actions .header-icon-link', {
        autoAlpha: 0,
        y: -14,
        duration: 0.42,
        stagger: 0.05
    }, 0.14);

    if (document.querySelector('.hero')) {
        intro(introTimeline, '.hero h1', {
            autoAlpha: 0,
            y: 72,
            duration: 1
        }, 0.18);

        intro(introTimeline, '.hero h2', {
            autoAlpha: 0,
            y: 34,
            duration: 0.74
        }, 0.34);

        intro(introTimeline, '.hero-buttons .btn', {
            autoAlpha: 0,
            y: 24,
            duration: 0.56,
            stagger: 0.1
        }, 0.46);
    }

    if (document.querySelector('.panel-hero-main') || document.querySelector('.panel-hero-aside')) {
        intro(introTimeline, '.panel-hero-main', {
            autoAlpha: 0,
            y: 42,
            duration: 0.78
        }, 0.24);

        intro(introTimeline, '.panel-hero-aside', {
            autoAlpha: 0,
            y: 54,
            duration: 0.7
        }, 0.38);
    }

    if (document.querySelector('.auth-grid')) {
        intro(introTimeline, '.auth-aside', {
            autoAlpha: 0,
            x: -36,
            y: 0,
            duration: 0.8
        }, 0.22);

        intro(introTimeline, '.auth-card', {
            autoAlpha: 0,
            x: 36,
            y: 0,
            duration: 0.8
        }, 0.3);

        intro(introTimeline, '.auth-highlight', {
            autoAlpha: 0,
            y: 18,
            duration: 0.48,
            stagger: 0.08
        }, 0.48);
    }

    if (document.querySelector('.public-copy-card--featured')) {
        intro(introTimeline, '.public-copy-card--featured', {
            autoAlpha: 0,
            y: 40,
            duration: 0.78
        }, 0.24);

        intro(introTimeline, '.public-story-grid .public-image-card', {
            autoAlpha: 0,
            y: 46,
            duration: 0.78
        }, 0.34);
    }

    reveal('.secao-publicacoes-recentes .cabecalho-publicacoes > *, .secao-publicacoes-recentes .barra-pesquisa, .secao-publicacoes-recentes .filtros-tags, .secao-publicacoes-recentes .wrapper-carrossel-publicacoes', {
        y: 34,
        start: 'top 90%',
        stagger: 0.08
    });

    reveal('.public-story-grid > *, .public-contact-grid > *, .public-cta-grid > *', {
        y: 42,
        start: 'top 88%',
        stagger: 0.08
    });

    reveal('.public-section-card, .public-simple-card, .public-partners-panel', {
        y: 46,
        start: 'top 88%'
    });

    reveal('.public-topic-card', {
        y: 34,
        start: 'top 90%',
        stagger: 0.05
    });

    reveal('.public-contact-item, .public-context-note', {
        y: 24,
        start: 'top 92%',
        stagger: 0.05
    });

    reveal('.member-info, .dots', {
        y: 24,
        start: 'top 92%'
    });

    reveal('.metric-card', {
        y: 32,
        start: 'top 90%',
        stagger: 0.05
    });

    reveal('.action-card', {
        y: 24,
        start: 'top 92%',
        stagger: 0.04
    });

    reveal('.dashboard-layout .panel-card, .stacked-panels .panel-card, .admin-workspace, .settings-nav', {
        y: 40,
        start: 'top 88%'
    });

    reveal('.panel-card-header, .hero-actions, .dashboard-list li, .hero-meta-list li, .settings-row', {
        y: 22,
        start: 'top 94%',
        stagger: 0.04
    });

    reveal('.monitor', {
        x: -48,
        y: 0,
        duration: 0.9,
        start: 'top 82%'
    });

    reveal('.infraestruturas-copy', {
        x: 48,
        y: 0,
        duration: 0.9,
        start: 'top 82%'
    });

    reveal('.botao-ver-mais-lateral', {
        y: 18,
        start: 'top 95%'
    });

    reveal('footer', {
        y: 20,
        duration: 0.6,
        start: 'top 98%'
    });

    if (ScrollTrigger) {
        ScrollTrigger.refresh();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initCursorOrb();
    initExpandableText();
    initProjectsShowcase();
    initSmoothScroll();
    initGlobalSmoothScroll();
    initTeamCarousel();
    initFloatingButtonObserver();
    initInnovationCards();
    initSettingsTabs();
    initGsapPageMotion();
});
