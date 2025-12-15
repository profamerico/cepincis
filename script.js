const ball = document.querySelector('.ball');

let mouseX = 0;
let mouseY = 0;

let ballX = 0;
let ballY = 0;

let speed = 0.067;

// Update ball position
function animate() {
	//Determine distance between ball and mouse
	let distX = mouseX - ballX;
	let distY = mouseY - ballY;

	// Find position of ball and some distance * speed
	ballX = ballX + (distX * speed);
	ballY = ballY + (distY * speed);

	ball.style.left = ballX + "px";
	ball.style.top = ballY + "px";

	requestAnimationFrame(animate);
}
animate();

// Move ball with cursor
document.addEventListener("mousemove", function (event) {
	mouseX = event.pageX;
	mouseY = event.pageY;
});

const btn = document.querySelector('.btn-ver-mais');
const text = document.querySelector('.text-collapsed');

btn.addEventListener('click', () => {
	text.classList.toggle('text-expanded');

	if (text.classList.contains('text-expanded')) {
		btn.textContent = 'Ver Menos';
	} else {
		btn.textContent = 'Ver Mais';
	}
});

// AUTO-FECHAMENTO DA ÁREA EXPANDIDA AO ROLAR COM DELAY
let fecharTextoTimeout = null; // guarda o timeout para cancelar se voltar

if (text) {
	const observerFecharTexto = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			const estaExpandido = text.classList.contains('text-expanded');

			// Quando sai da tela (menos de 40% visível)
			if (!entry.isIntersecting && entry.intersectionRatio < 0.4) {
				if (estaExpandido && !fecharTextoTimeout) {
					// agenda o fechamento após X ms fora da tela
					fecharTextoTimeout = setTimeout(() => {
						if (text.classList.contains('text-expanded')) {
							text.classList.remove('text-expanded');
							if (btn) btn.textContent = 'Ver Mais';

							// volta a rolagem para o botão assim que virar "Ver Mais"
							const rect = btn.getBoundingClientRect();
							const scrollY = window.scrollY || 0;
							const posicaoAbsoluta = scrollY + rect.top;
							window.scrollTo({
								top: posicaoAbsoluta - 100, // ajuste se quiser
								behavior: "smooth"
							});
						}
						fecharTextoTimeout = null;
					}, 3532); // 3000 ms = 3 segundos (ajuste como quiser)
				}
			} else {
				// Se voltou a aparecer antes do tempo acabar, cancela o fechamento
				if (fecharTextoTimeout) {
					clearTimeout(fecharTextoTimeout);
					fecharTextoTimeout = null;
				}
			}
		});
	}, {
		threshold: [0.4]  // 40% visível
	});

	observerFecharTexto.observe(text);
}

// ===== VOLTAR PARA O BOTÃO ASSIM QUE VIRAR "VER MAIS" (CLIQUE MANUAL) =====
if (btn) {
	btn.addEventListener("click", () => {
		// pequeno delay para garantir que o layout já foi atualizado
		setTimeout(() => {
			if (btn.textContent.trim() === "Ver Mais") {
				const rect = btn.getBoundingClientRect();
				const scrollY = window.scrollY || 0;
				const posicaoAbsoluta = scrollY + rect.top;
				window.scrollTo({
					top: posicaoAbsoluta - 100, // ajuste se quiser
					behavior: "smooth"
				});
			}
		}, 50);
	});
}

const body = document.body,
	jsScroll = document.getElementsByClassName('js-scroll')[0],
	height = jsScroll.getBoundingClientRect().height - 1,
	scrollSpeed = 0.05

var offset = 0

body.style.height = Math.floor(height) + "px"

function smoothScroll() {
	offset += (window.pageYOffset - offset) * speed

	var scroll = "translateY(-" + offset + "px) translateZ(0)"
	jsScroll.style.transform = scroll

	raf = requestAnimationFrame(smoothScroll)
}
smoothScroll()



const teamMembers = [
	{ name: "Universidade de Copenhagen", role: "Copenhagen, Dinamarca" },
	{ name: "Universidade de Roma 3", role: "Roma, Itália" },
	{ name: "Universidade de Fuzhou", role: "A universidade de Fuhzou, é uma prestigiada instituição pública de ensino superior localizada em Fuzhou, capital da província de Fujian, na China." },
	{ name: "GETIS", role: "Grupo de Pesquisa em Engenharia, Tecnologia, Inovaçao e Sustentabilidade (GETIS) - IFSP-CAR" },
	{ name: "i2", role: "Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR" },
	{ name: "ENASA", role: "Grupo de pesquisa em Energia, Água e Saneamento (ENASA) - IFSP-SP" }
];

const cards = document.querySelectorAll(".card");
const dots = document.querySelectorAll(".dot");
const memberName = document.querySelector(".member-name");
const memberRole = document.querySelector(".member-role");
const leftArrow = document.querySelector(".nav-arrow.left");
const rightArrow = document.querySelector(".nav-arrow.right");
let currentIndex = 0;
let isAnimating = false;

// compute dot step (how many cards correspond to one dot/page)
// fallback to 1 if dots not present or division invalid
const dotStep = (dots && dots.length) ? Math.max(1, Math.ceil(cards.length / dots.length)) : 1;

function updateCarousel(newIndex) {
	if (isAnimating) return;
	isAnimating = true;

	currentIndex = (newIndex + cards.length) % cards.length;

	cards.forEach((card, i) => {
		const offset = (i - currentIndex + cards.length) % cards.length;

		card.classList.remove(
			"center",
			"left-1",
			"left-2",
			"right-1",
			"right-2",
			"hidden"
		);

		if (offset === 0) {
			card.classList.add("center");
		} else if (offset === 1) {
			card.classList.add("right-1");
		} else if (offset === 2) {
			card.classList.add("right-2");
		} else if (offset === cards.length - 1) {
			card.classList.add("left-1");
		} else if (offset === cards.length - 2) {
			card.classList.add("left-2");
		} else {
			card.classList.add("hidden");
		}
	});

	// mark the correct dot as active using dotStep
	if (dots && dots.length) {
		const activeDotIndex = Math.floor(currentIndex / dotStep) % dots.length;
		dots.forEach((dot, i) => {
			dot.classList.toggle("active", i === activeDotIndex);
		});
	}

	memberName.style.opacity = "0";
	memberRole.style.opacity = "0";

	setTimeout(() => {
		memberName.textContent = teamMembers[currentIndex].name;
		memberRole.textContent = teamMembers[currentIndex].role;
		memberName.style.opacity = "1";
		memberRole.style.opacity = "1";
	}, 300);

	setTimeout(() => {
		isAnimating = false;
	}, 800);
}

leftArrow.addEventListener("click", () => {
	updateCarousel(currentIndex - 1);
});

rightArrow.addEventListener("click", () => {
	updateCarousel(currentIndex + 1);
});

// replace dots click binding to account for dotStep
dots.forEach((dot, i) => {
	dot.addEventListener("click", () => {
		updateCarousel(i * dotStep);
	});
});

cards.forEach((card, i) => {
	card.addEventListener("click", () => {
		updateCarousel(i);
	});
});

document.addEventListener("keydown", (e) => {
	if (e.key === "ArrowLeft") {
		updateCarousel(currentIndex - 1);
	} else if (e.key === "ArrowRight") {
		updateCarousel(currentIndex + 1);
	}
});

let touchStartX = 0;
let touchEndX = 0;

document.addEventListener("touchstart", (e) => {
	touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener("touchend", (e) => {
	touchEndX = e.changedTouches[0].screenX;
	handleSwipe();
});

function handleSwipe() {
	const swipeThreshold = 50;
	const diff = touchStartX - touchEndX;

	if (Math.abs(diff) > swipeThreshold) {
		if (diff > 0) {
			updateCarousel(currentIndex + 1);
		} else {
			updateCarousel(currentIndex - 1);
		}
	}
}

updateCarousel(0);



// ===== JAVASCRIPT DO CARROSSEL DE PUBLICAÇÕES =====

let slideAtualPublicacoes = 0;
const carrosselPublicacoes = document.getElementById("carrosselPublicacoes");
const totalSlidesPublicacoes = 5;
const cardsPublicacoes = document.querySelectorAll(".card-publicacao-carrossel");

let slideAtualExpandido = 0;
const carrosselExpandido = document.getElementById("carrosselExpandido");

function atualizarIndicadoresPublicacoes() {
	const pontosIndicadores = document.querySelectorAll(".ponto-indicador-carrossel");
	pontosIndicadores.forEach((ponto, indice) => {
		ponto.classList.toggle("active", indice === slideAtualPublicacoes);
	});
}

function atualizarAtivoCardsPublicacoes() {
	cardsPublicacoes.forEach((card, indice) => {
		card.classList.toggle("active", indice === slideAtualPublicacoes);
	});
}

function navegarCarrosselPublicacoes(direcao) {
	const larguraCard = carrosselPublicacoes.querySelector(".card-publicacao-carrossel").offsetWidth + 30;

	slideAtualPublicacoes += direcao;

	if (slideAtualPublicacoes < 0) {
		slideAtualPublicacoes = totalSlidesPublicacoes - 1;
	} else if (slideAtualPublicacoes >= totalSlidesPublicacoes) {
		slideAtualPublicacoes = 0;
	}

	carrosselPublicacoes.scrollTo({
		left: slideAtualPublicacoes * larguraCard,
		behavior: "smooth"
	});

	atualizarIndicadoresPublicacoes();
	atualizarAtivoCardsPublicacoes();
}

function irParaSlidePublicacao(indice) {
	const larguraCard = carrosselPublicacoes.querySelector(".card-publicacao-carrossel").offsetWidth + 30;
	slideAtualPublicacoes = indice;

	carrosselPublicacoes.scrollTo({
		left: slideAtualPublicacoes * larguraCard,
		behavior: "smooth"
	});

	atualizarIndicadoresPublicacoes();
	atualizarAtivoCardsPublicacoes();
}

function navegarCarrosselExpandido(direcao) {
	const larguraCard = carrosselExpandido.querySelector(".card-expandido").offsetWidth + 40;

	slideAtualExpandido += direcao;

	if (slideAtualExpandido < 0) {
		slideAtualExpandido = totalSlidesPublicacoes - 1;
	} else if (slideAtualExpandido >= totalSlidesPublicacoes) {
		slideAtualExpandido = 0;
	}

	carrosselExpandido.scrollTo({
		left: slideAtualExpandido * larguraCard,
		behavior: "smooth"
	});
}

function abrirModoExpandido() {
	const overlay = document.getElementById("overlayTelaCheia");
	overlay.classList.add("ativo");
	document.body.style.overflow = "hidden";
}

function fecharModoExpandido() {
	const overlay = document.getElementById("overlayTelaCheia");
	overlay.classList.remove("ativo");
	document.body.style.overflow = "auto";
}

let intervaloAutoPlayPublicacoes = setInterval(() => {
	navegarCarrosselPublicacoes(1);
}, 5000);

carrosselPublicacoes.addEventListener("mouseenter", () => {
	clearInterval(intervaloAutoPlayPublicacoes);
});

carrosselPublicacoes.addEventListener("mouseleave", () => {
	intervaloAutoPlayPublicacoes = setInterval(() => {
		navegarCarrosselPublicacoes(1);
	}, 5000);
});

document.addEventListener("keydown", (evento) => {
	const overlayAtivo = document.getElementById("overlayTelaCheia").classList.contains("ativo");

	if (evento.key === "Escape" && overlayAtivo) {
		fecharModoExpandido();
	} else if (evento.key === "ArrowLeft") {
		if (overlayAtivo) {
			navegarCarrosselExpandido(-1);
		} else {
			navegarCarrosselPublicacoes(-1);
		}
	} else if (evento.key === "ArrowRight") {
		if (overlayAtivo) {
			navegarCarrosselExpandido(1);
		} else {
			navegarCarrosselPublicacoes(1);
		}
	}
});

// Inicializar cards ativos
atualizarAtivoCardsPublicacoes();

window.addEventListener('scroll', function () {
	const botao = document.querySelector('.botao-ver-mais-lateral');

	// Ajuste este valor (300) para quando você quer que apareça
	if (window.scrollY > 300) {
		botao.classList.add('ativo');
	} else {
		botao.classList.remove('ativo');
	}
});

// ===== SISTEMA DE MUDANÇA DE COR DO BOTÃO =====
function iniciarMudancaCoresBotao() {
	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				const secaoId = entry.target.id;
				const botao = document.querySelector('.botao-ver-mais-lateral');

				if (botao) {
					// Remove todas as classes de cor antigas
					botao.classList.remove('secao-sobre', 'secao-implementacao', 'secao-regulamento', 'secao-parceiros', 'secao-contato');

					// Adiciona a nova classe baseada na seção
					if (secaoId === 'sobre') {
						botao.classList.add('secao-sobre');
					} else if (secaoId === 'implementacao') {
						botao.classList.add('secao-implementacao');
					} else if (secaoId === 'regulamento') {
						botao.classList.add('secao-regulamento');
					} else if (secaoId === 'parceiros') {
						botao.classList.add('secao-parceiros');
					} else if (secaoId === 'contato') {
						botao.classList.add('secao-contato');
					}
				}
			}
		});
	}, {
		threshold: 0.3, // Quando 30% da seção estiver visível
		rootMargin: '-10% 0px -10% 0px'
	});

	// Observa cada seção
	const secoes = ['sobre', 'implementacao', 'regulamento', 'parceiros', 'contato'];
	secoes.forEach(id => {
		const secao = document.getElementById(id);
		if (secao) {
			observer.observe(secao);
		}
	});
}

// Inicia o sistema quando a página carregar
document.addEventListener('DOMContentLoaded', function () {
	iniciarMudancaCoresBotao();
});


// ===== JAVASCRIPT DA SEÇÃO DE INOVAÇÃO =====

function alternarExpandirProjeto(elemento) {
	const estaExpandido = elemento.classList.contains("expandido");

	// Fecha todos os cards
	document.querySelectorAll(".card-projeto-inovacao").forEach(card => {
		card.classList.remove("expandido");
	});

	// Se não estava expandido, expande o clicado
	if (!estaExpandido) {
		elemento.classList.add("expandido");
	}
}

// Fechar card ao clicar fora
document.addEventListener("click", (evento) => {
	if (!evento.target.closest(".card-projeto-inovacao")) {
		document.querySelectorAll(".card-projeto-inovacao").forEach(card => {
			card.classList.remove("expandido");
		});
	}
});

// Navegação por teclado
document.addEventListener("keydown", (evento) => {
	if (evento.key === "Escape") {
		document.querySelectorAll(".card-projeto-inovacao").forEach(card => {
			card.classList.remove("expandido");
		});
	}
});
