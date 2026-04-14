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

function initPageLoader() {
    const loader = document.querySelector('[data-page-loader]');
    if (!loader || !document.body) {
        return;
    }

    const loaderLabel = loader.querySelector('[data-page-loader-label]');
    const pageLabels = {
        'index.php': 'Home',
        'about.php': 'Sobre',
        'contact.php': 'Contato',
        'implement.php': 'Areas Tematicas',
        'login.php': 'Login',
        'register.php': 'Registro',
        'dashboard.php': 'Dashboard',
        'profile.php': 'Perfil',
        'projects.php': 'Projetos',
        'settings.php': 'Configuracoes',
        'admin.php': 'Admin',
        'logout.php': 'Saindo'
    };

    const defaultLabel = document.body.dataset.loadingPage || 'CEPIN-CIS';

    function setLoaderLabel(nextLabel) {
        if (!loaderLabel) {
            return;
        }

        loaderLabel.textContent = nextLabel || defaultLabel;
    }

    function releaseLoader() {
        document.body.classList.remove('page-loader-leaving');

        window.setTimeout(() => {
            document.body.classList.add('page-loader-ready');
            setLoaderLabel(defaultLabel);
        }, prefersReducedMotion() ? 0 : 140);
    }

    function armLoader(nextLabel) {
        setLoaderLabel(nextLabel);
        document.body.classList.remove('page-loader-ready');
        document.body.classList.add('page-loader-leaving');
    }

    function getLoaderLabelFromHref(href) {
        try {
            const url = new URL(href, window.location.href);
            const scriptName = (url.pathname.split('/').pop() || 'index.php').toLowerCase();
            return pageLabels[scriptName] || defaultLabel;
        } catch (error) {
            return defaultLabel;
        }
    }

    function isInternalPageNavigation(link) {
        if (!(link instanceof HTMLAnchorElement)) {
            return false;
        }

        const rawHref = link.getAttribute('href') || '';
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || link.hasAttribute('download')) {
            return false;
        }

        if (link.target && link.target !== '_self') {
            return false;
        }

        try {
            const targetUrl = new URL(link.href, window.location.href);
            const currentUrl = new URL(window.location.href);

            if (targetUrl.origin !== currentUrl.origin) {
                return false;
            }

            if (targetUrl.pathname === currentUrl.pathname && targetUrl.search === currentUrl.search) {
                return false;
            }

            return true;
        } catch (error) {
            return false;
        }
    }

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');
        if (!isInternalPageNavigation(link)) {
            return;
        }

        event.preventDefault();
        armLoader(getLoaderLabelFromHref(link.href));

        window.setTimeout(() => {
            window.location.href = link.href;
        }, prefersReducedMotion() ? 0 : 110);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.target && form.target !== '_self') {
            return;
        }

        armLoader(form.dataset.loadingLabel || defaultLabel);
    });

    window.addEventListener('load', releaseLoader, { once: true });
    window.addEventListener('pageshow', releaseLoader);

    if (document.readyState === 'complete') {
        releaseLoader();
    }
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

function splitTextIntoWords(target) {
    if (!(target instanceof Element)) {
        return [];
    }

    if (target.dataset.splitReady === 'true') {
        return Array.from(target.querySelectorAll('.motion-word'));
    }

    const text = target.textContent.replace(/\s+/g, ' ').trim();
    if (!text) {
        return [];
    }

    target.dataset.splitReady = 'true';
    target.textContent = '';

    text.split(' ').forEach((word, index, words) => {
        const wrapper = document.createElement('span');
        wrapper.className = 'motion-word-wrap';

        const span = document.createElement('span');
        span.className = 'motion-word';
        span.textContent = word;

        wrapper.appendChild(span);
        target.appendChild(wrapper);

        if (index < words.length - 1) {
            target.appendChild(document.createTextNode(' '));
        }
    });

    return Array.from(target.querySelectorAll('.motion-word'));
}

function collectSplitWords(targets) {
    return toElementArray(targets).reduce((words, target) => {
        return words.concat(splitTextIntoWords(target));
    }, []);
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

function initMobileNavigation() {
    const toggle = document.querySelector('[data-mobile-nav-toggle]');
    const drawer = document.querySelector('[data-mobile-nav-drawer]');
    const overlay = document.querySelector('[data-mobile-nav-overlay]');
    const closeButton = document.querySelector('[data-mobile-nav-close]');
    const compactViewport = window.matchMedia('(max-width: 1100px)');

    if (!toggle || !drawer || !overlay) {
        return;
    }

    const drawerLinks = Array.from(drawer.querySelectorAll('a'));

    function setOpen(isOpen) {
        document.body.classList.toggle('mobile-nav-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        overlay.hidden = !isOpen;
    }

    function closeMenu() {
        setOpen(false);
    }

    toggle.addEventListener('click', () => {
        setOpen(!document.body.classList.contains('mobile-nav-open'));
    });

    closeButton?.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    drawerLinks.forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    function handleViewportChange() {
        if (!compactViewport.matches) {
            closeMenu();
        }
    }

    if (typeof compactViewport.addEventListener === 'function') {
        compactViewport.addEventListener('change', handleViewportChange);
    } else if (typeof compactViewport.addListener === 'function') {
        compactViewport.addListener(handleViewportChange);
    }
}

function initMobileCollapseCards() {
    const groups = Array.from(document.querySelectorAll('[data-mobile-collapse]'));
    const compactViewport = window.matchMedia('(max-width: 1100px)');

    if (!groups.length) {
        return;
    }

    groups.forEach((group, index) => {
        const button = group.querySelector('[data-mobile-collapse-toggle]');
        const content = group.querySelector('[data-mobile-collapse-content]');
        const inner = group.querySelector('[data-mobile-collapse-inner]');

        if (!button || !content || !inner) {
            return;
        }

        if (!content.id) {
            content.id = `mobileCollapse${index + 1}`;
        }

        button.setAttribute('aria-controls', content.id);
        button.setAttribute('aria-expanded', 'false');

        function syncHeight() {
            const isOpen = group.classList.contains('is-open');

            if (!compactViewport.matches) {
                content.style.maxHeight = 'none';
                return;
            }

            content.style.maxHeight = isOpen ? `${inner.scrollHeight}px` : '0px';
        }

        button.addEventListener('click', () => {
            if (!compactViewport.matches) {
                return;
            }

            const willOpen = !group.classList.contains('is-open');
            group.classList.toggle('is-open', willOpen);
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            syncHeight();
        });

        const handleViewportChange = () => {
            if (!compactViewport.matches) {
                group.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            }

            syncHeight();
        };

        window.addEventListener('resize', syncHeight);

        if (typeof compactViewport.addEventListener === 'function') {
            compactViewport.addEventListener('change', handleViewportChange);
        } else if (typeof compactViewport.addListener === 'function') {
            compactViewport.addListener(handleViewportChange);
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(syncHeight).catch(() => {});
        }

        syncHeight();
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

    document.body.classList.add('motion-enhanced');

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    const compactViewport = typeof window.matchMedia === 'function'
        ? window.matchMedia('(max-width: 1100px)').matches
        : false;

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

    function introWords(timeline, targets, vars, position) {
        const elements = toElementArray(targets).filter((element) => !element.dataset.motionBound);
        if (!elements.length) {
            return;
        }

        const words = collectSplitWords(elements);
        if (!words.length) {
            return;
        }

        markMotionTargets(elements);
        timeline.from(words, {
            autoAlpha: 0,
            yPercent: vars.yPercent ?? 120,
            rotate: vars.rotate ?? 4,
            duration: vars.duration ?? 0.9,
            ease: vars.ease ?? 'expo.out',
            stagger: vars.stagger ?? 0.045,
            clearProps: 'opacity,visibility,transform'
        }, position);
    }

    function revealBatch(targets, options = {}) {
        const elements = toElementArray(targets).filter((element) => !element.dataset.motionBound && !element.hidden);
        if (!elements.length) {
            return;
        }

        markMotionTargets(elements);

        const fromVars = {
            autoAlpha: 0,
            x: options.x ?? 0,
            y: options.y ?? 56,
            scale: options.scale ?? 0.96,
            rotateX: options.rotateX ?? 8,
            transformOrigin: options.transformOrigin ?? 'top center',
            filter: options.filter ?? 'blur(10px)'
        };

        const toVars = {
            autoAlpha: 1,
            x: 0,
            y: 0,
            scale: 1,
            rotateX: 0,
            filter: 'blur(0px)',
            duration: options.duration ?? 0.95,
            ease: options.ease ?? 'expo.out',
            stagger: options.stagger ?? 0.08,
            clearProps: 'opacity,visibility,transform,filter'
        };

        if (!ScrollTrigger) {
            gsap.fromTo(elements, fromVars, toVars);
            return;
        }

        ScrollTrigger.batch(elements, {
            start: options.start ?? 'top 88%',
            once: true,
            onEnter: (batch) => {
                gsap.fromTo(batch, fromVars, {
                    ...toVars,
                    stagger: options.stagger ?? 0.08
                });
            }
        });
    }

    function revealPanels(targets, options = {}) {
        const elements = toElementArray(targets).filter((element) => !element.dataset.motionBound && !element.hidden);
        if (!elements.length) {
            return;
        }

        elements.forEach((element) => {
            markMotionTargets(element);

            const animationConfig = {
                autoAlpha: 0,
                y: options.y ?? 64,
                scale: options.scale ?? 0.97,
                clipPath: options.clipPath ?? 'inset(0 0 16% 0 round 32px)',
                filter: options.filter ?? 'blur(12px)',
                duration: options.duration ?? 1.05,
                ease: options.ease ?? 'expo.out',
                clearProps: 'opacity,visibility,transform,filter,clipPath'
            };

            const triggerConfig = getScrollTriggerConfig(element, options.start ?? 'top 84%');
            if (triggerConfig) {
                animationConfig.scrollTrigger = triggerConfig;
            }

            gsap.from(element, animationConfig);
        });
    }

    function addParallax(targets, options = {}) {
        if (!ScrollTrigger) {
            return;
        }

        toElementArray(targets).forEach((element) => {
            gsap.to(element, {
                yPercent: options.yPercent ?? -10,
                scale: options.scale ?? 1.03,
                ease: 'none',
                scrollTrigger: {
                    trigger: options.trigger || element,
                    start: options.start ?? 'top bottom',
                    end: options.end ?? 'bottom top',
                    scrub: options.scrub ?? 1.2
                }
            });
        });
    }

    const introTimeline = gsap.timeline({
        defaults: {
            ease: 'expo.out'
        }
    });

    intro(introTimeline, 'header .logo', {
        autoAlpha: 0,
        y: -22,
        filter: 'blur(10px)',
        duration: 0.85
    }, 0);

    intro(introTimeline, 'header .site-nav-links a', {
        autoAlpha: 0,
        y: -18,
        filter: 'blur(8px)',
        duration: 0.6,
        stagger: 0.06
    }, 0.12);

    intro(introTimeline, 'header .header-user-badge, header .site-nav-actions .header-icon-link', {
        autoAlpha: 0,
        y: -18,
        filter: 'blur(8px)',
        duration: 0.58,
        stagger: 0.05
    }, 0.2);

    if (document.querySelector('.hero')) {
        introWords(introTimeline, '.hero h1', {
            yPercent: compactViewport ? 108 : 132,
            rotate: 2,
            duration: compactViewport ? 0.92 : 1.1,
            stagger: compactViewport ? 0.038 : 0.05
        }, 0.24);

        introWords(introTimeline, '.hero h2', {
            yPercent: compactViewport ? 94 : 118,
            rotate: 2,
            duration: compactViewport ? 0.78 : 0.86,
            stagger: compactViewport ? 0.022 : 0.028
        }, 0.48);

        intro(introTimeline, '.hero-buttons .btn', {
            autoAlpha: 0,
            y: compactViewport ? 22 : 30,
            scale: compactViewport ? 0.97 : 0.94,
            duration: compactViewport ? 0.62 : 0.72,
            stagger: compactViewport ? 0.08 : 0.12
        }, 0.7);
    }

    if (document.querySelector('.panel-hero-main') || document.querySelector('.panel-hero-aside')) {
        intro(introTimeline, '.panel-hero-main', {
            autoAlpha: 0,
            y: 54,
            clipPath: 'inset(0 0 18% 0 round 28px)',
            filter: 'blur(10px)',
            duration: 0.98
        }, 0.32);

        introWords(introTimeline, '.panel-hero-main h1', {
            yPercent: 118,
            rotate: 2,
            duration: 0.9,
            stagger: 0.034
        }, 0.44);

        intro(introTimeline, '.panel-hero-aside', {
            autoAlpha: 0,
            y: 64,
            scale: 0.95,
            filter: 'blur(12px)',
            duration: 0.9
        }, 0.48);

        introWords(introTimeline, '.panel-hero-aside h2', {
            yPercent: 118,
            rotate: 2,
            duration: 0.82,
            stagger: 0.03
        }, 0.62);
    }

    if (document.querySelector('.auth-grid')) {
        intro(introTimeline, '.auth-aside', {
            autoAlpha: 0,
            x: -54,
            y: 0,
            filter: 'blur(10px)',
            duration: 1
        }, 0.3);

        introWords(introTimeline, '.auth-aside h1, .auth-card h2', {
            yPercent: 116,
            rotate: 2,
            duration: 0.88,
            stagger: 0.03
        }, 0.42);

        intro(introTimeline, '.auth-card', {
            autoAlpha: 0,
            x: 54,
            y: 0,
            filter: 'blur(10px)',
            duration: 1
        }, 0.42);

        intro(introTimeline, '.auth-highlight', {
            autoAlpha: 0,
            y: 24,
            scale: 0.96,
            duration: 0.64,
            stagger: 0.08
        }, 0.64);
    }

    if (document.querySelector('.public-copy-card--featured')) {
        intro(introTimeline, '.public-copy-card--featured', {
            autoAlpha: 0,
            y: 56,
            clipPath: 'inset(0 0 18% 0 round 28px)',
            filter: 'blur(12px)',
            duration: 0.98
        }, 0.3);

        introWords(introTimeline, '.public-copy-card--featured h1', {
            yPercent: 118,
            rotate: 2,
            duration: 0.92,
            stagger: 0.032
        }, 0.42);

        intro(introTimeline, '.public-story-grid .public-image-card', {
            autoAlpha: 0,
            y: 68,
            scale: 0.94,
            filter: 'blur(12px)',
            duration: 0.98
        }, 0.48);
    }

    revealPanels('.secao-publicacoes-recentes', {
        y: 72,
        start: 'top 86%'
    });

    revealBatch('.secao-publicacoes-recentes .cabecalho-publicacoes > *, .secao-publicacoes-recentes .barra-pesquisa, .secao-publicacoes-recentes .filtros-tags, .secao-publicacoes-recentes .wrapper-carrossel-publicacoes, .navegacao-carrossel-publicacoes, .indicadores-paginacao-carrossel', {
        y: 44,
        start: 'top 88%',
        stagger: 0.09
    });

    revealBatch('.card-publicacao-carrossel', {
        y: 38,
        scale: 0.93,
        rotateX: 10,
        start: 'top 90%',
        stagger: 0.1
    });

    revealPanels('.public-story-grid > *, .public-contact-grid > *, .public-cta-grid > *, .public-section-card, .public-simple-card, .public-partners-panel, .dashboard-layout .panel-card, .stacked-panels .panel-card, .admin-workspace, .settings-nav, .panel-card.tab-content.active', {
        y: 62,
        start: 'top 86%'
    });

    revealBatch('.public-topic-card, .metric-card, .action-card, .public-contact-item, .public-context-note, .auth-highlight, .dashboard-list li, .hero-meta-list li, .settings-row', {
        y: 30,
        scale: 0.95,
        rotateX: 6,
        start: 'top 92%',
        stagger: 0.06
    });

    revealBatch('.member-info, .dots, .botao-ver-mais-lateral, footer', {
        y: 24,
        scale: 0.98,
        start: 'top 95%',
        stagger: 0.05
    });

    revealPanels('.public-image-card, .public-map-card, .public-partners-panel .carousel-container, .monitor, .infraestruturas-copy', {
        y: 56,
        start: 'top 84%'
    });

    addParallax('.public-image-card img, .public-map-frame', {
        yPercent: -8,
        scale: 1.04,
        scrub: 1.3
    });

    addParallax('.monitor', {
        yPercent: -10,
        scale: 1.05,
        scrub: 1.5
    });

    if (ScrollTrigger && document.querySelector('.hero') && !compactViewport) {
        gsap.to('.hero', {
            backgroundPosition: '50% 62%',
            ease: 'none',
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1.2
            }
        });

        gsap.to('.hero h1', {
            yPercent: -18,
            ease: 'none',
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1.1
            }
        });

        gsap.to('.hero h2, .hero-buttons', {
            yPercent: -10,
            ease: 'none',
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1
            }
        });

        if (document.querySelector('.ball')) {
            gsap.to('.ball', {
                scale: 1.28,
                opacity: 0.2,
                ease: 'none',
                scrollTrigger: {
                    trigger: '.hero',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.1
                }
            });
        }
    }

    if (ScrollTrigger) {
        ScrollTrigger.refresh();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initPageLoader();
    initCursorOrb();
    initExpandableText();
    initProjectsShowcase();
    initSmoothScroll();
    initGlobalSmoothScroll();
    initMobileNavigation();
    initMobileCollapseCards();
    initTeamCarousel();
    initFloatingButtonObserver();
    initInnovationCards();
    initSettingsTabs();
    initGsapPageMotion();
});
