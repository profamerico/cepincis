<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEPIN-CIS</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Aldrich&family=Chivo:wght@300;400;700&display=swap"
        rel="stylesheet">
</head>

<body>

    <!-- ===== HEADER ===== -->
    <header>
        <a href="./index.php" class="logo">CEPIN-CIS</a>
        <nav>
            <a href="./about.php#sobre">Sobre</a>
            <a href="./implement.php">Áreas Temáticas</a>
            <a
                href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Regulamento</a>
            <a href="./contact.php">Contato</a>
            <a href="./index.php">
                <img class="imagensdoheader" src="./img/download (1).png"></img>
            </a>
            <a href="./login.php">
                <img class="imagensdoheader" src="./img/login.png"></img>
            </a>
        </nav>
    </header>

    <div class="js-cont">
        <div class="js-scroll">
            <div class="full-screen">

                <div class="ball"></div>

                <!-- ===== HERO ===== -->
                <section class="hero">
                    <h1>CEPIN-CIS</h1>
                    <h2>Centro de Pesquisa e Inovação em Cidades Inteligentes</h2>
                    <div class="hero-buttons">
                        <a href="./about.php#sobre" class="btn big">Saiba Mais</a>
                        <a href="./contact.php" class="btn big">Entre em Contato</a>
                    </div>
                </section>

                <!-- ===== CARROSSEL DE PUBLICAÇÕES ===== -->
                <section class="secao-publicacoes-recentes">
                    <div class="cabecalho-publicacoes">
                        <h3 class="titulo-categoria-publicacoes">do CEPIN-CIS:</h3>
                        <h2 class="titulo-principal-publicacoes">LINHAS DE PESQUISA</h2>
                    </div>

                    <div class="wrapper-carrossel-publicacoes">
                        <div class="container-carrossel-publicacoes" id="carrosselPublicacoes">

                            <div class="card-publicacao-carrossel card-publicacao-1">
                                <h3 class="categoria-card-publicacao">futura tag</h3>
                                <h2 class="titulo-card-publicacao">Dosador de Concreto Construído com Phyton</h2>
                                <p class="descricao-card-publicacao">Clique aqui para explorar o projeto</p>
                                <button class="botao-explorar-publicacao"><span>Explorar</span></button>
                            </div>

                            <div class="card-publicacao-carrossel card-publicacao-2">
                                <h3 class="categoria-card-publicacao"> futura tag</h3>
                                <h2 class="titulo-card-publicacao">Desenvolvimento de Ferramenta Digital Para Avaliação
                                    Técnica e Ambiental do Concreto</h2>
                                <p class="descricao-card-publicacao">Clique aqui para explorar o projeto</p>
                                <button class="botao-explorar-publicacao"><span>Explorar</span></button>
                            </div>

                            <div class="card-publicacao-carrossel card-publicacao-3">
                                <h3 class="categoria-card-publicacao">Em Breve</h3>
                                <h2 class="titulo-card-publicacao">Em Breve</h2>
                                <p class="descricao-card-publicacao">Clique aqui para explorar o edital</p>
                                <button class="botao-explorar-publicacao"><span>Explorar</span></button>
                            </div>


                        </div>

                        <div class="navegacao-carrossel-publicacoes">
                            <div class="botao-seta-carrossel" onclick="navegarCarrosselPublicacoes(-1)">◄</div>
                            <div class="botao-seta-carrossel" onclick="navegarCarrosselPublicacoes(1)">►</div>
                        </div>

                        <div class="indicadores-paginacao-carrossel">
                            <div class="ponto-indicador-carrossel active" onclick="irParaSlidePublicacao(0)"></div>
                            <div class="ponto-indicador-carrossel" onclick="irParaSlidePublicacao(1)"></div>
                        </div>
                    </div>
                </section>

                <section class="divisor">
                    <div>
                        <div class="containerdivisor">
                        </div>
                    </div>
                </section>


                <section class="content">
                    <!-- ===== SOBRE ===== -->
                    <section id="sobre" class="content">
                        <h2>Sobre</h2>
                        <p><br>
                            Os principais objetivos do CEPIN-CIS são desenvolver investigação fundamental ou aplicada
                            focada em
                            cidades inteligentes e sustentáveis, contribuir ativamente para a inovação por meio da
                            transferência de
                            tecnologia e oferecer atividades de extensão. <br><br>
                        </p>

                        <img src="./img/banner.png" alt="logo CEPIN-CIS" class="imagem-sobre">

                        <p>
                            <br><br>
                            O Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS),
                            implementado no IFSP
                            campus Caraguatatuba, tem como missão fomentar o desenvolvimento de cidades inteligentes e
                            sustentáveis,
                            funcionando como um repositório de tecnologias, laboratório de aplicação e agente de
                            interlocução entre
                            os setores público e privado. O CEPIN-CIS busca, de maneira inclusiva, colaborativa e
                            equitativa,
                            abordar as dimensões ambientais, econômicas, sociais e culturais da sustentabilidade, com o
                            objetivo de
                            melhorar a capacidade dos cidadãos de contribuir e desfrutar dos benefícios de um
                            desenvolvimento urbano
                            mais viável, resiliente e sustentável.<br><br>
                        </p>
                    </section>

                    <!-- ===== IMPLEMENTAÇÃO ===== -->
                    <section id="implementacao" class="content alt">
                        <h2>Implementação</h2>
                        <p>
                            A implementação do CEPIN-CIS ocorreu durante os anos de 2022 e 2023, com recursos obtidos
                            através do
                            edital nº PRP/IFSP 329/2021. A equipe de implementação foi composta por:<br><br></p>
                        <button class="btn-ver-mais">Ver Mais</button>

                        <div class="text-collapsed">
                            <section class="secao-inovacao-areas-tematicas">
                                <div class="container-inovacao-principal">
                                    <div class="coluna-esquerda-inovacao">
                                        <h3 class="cabecalho-categoria-inovacao">CEPIN-CIS</h3>
                                        <h2 class="titulo-principal-inovacao">Equipe de Implementação</h2>
                                        <p class="descricao-inovacao">
                                            Conheça a equipe responsável pela Implementação do CEPIN-CIS no IFSP Caraguatatuba. Os
                                            professores José Américo Alves Salvador Filho, Mario Tadashi Shimanuki, Vassiliki
                                            Teresinha Galvão Boulomytis e Adriana Marques.
                                        </p>
                                    </div>

                                    <div class="coluna-direita-projetos">
                                        <div class="card-projeto-inovacao card-projeto-carbonzero" onclick="alternarExpandirProjeto(this)">
                                            <div class="cabecalho-card-projeto">
                                                <span class="nome-projeto-inovacao">José Américo Alves Salvador Filho</span>
                                                <div class="botao-expandir-projeto">+</div>
                                            </div>
                                            <div class="detalhes-projeto-expandido">
                                                <div class="conteudo-detalhes-projeto">
                                                    <p class="descricao-detalhes-projeto">
                                                        lattes.cnpq.br/8494783082862407<br><br>

                                                        jasalvador@ifsp.edu.br<br><br>
                                                    </p>
                                                    <a class="botao-ver-mais-projeto" href="https://integra.ifsp.edu.br/p/jose-americo-alves-salvador-filho" target="_blank" rel="noopener">Ver Mais →</a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-projeto-inovacao card-projeto-urbsmart" onclick="alternarExpandirProjeto(this)">
                                            <div class="cabecalho-card-projeto">
                                                <span class="nome-projeto-inovacao">Adriana Marques</span>
                                                <div class="botao-expandir-projeto">+</div>
                                            </div>
                                            <div class="detalhes-projeto-expandido">
                                                <div class="conteudo-detalhes-projeto">
                                                    <p class="descricao-detalhes-projeto">
                                                        lattes.cnpq.br/5040566445402531<br><br>

                                                        adriana.marques@ifsp.edu.br<br><br>
                                                    </p>
                                                    <a class="botao-ver-mais-projeto" href="https://integra.ifsp.edu.br/p/adriana-marques" target="_blank" rel="noopener">Ver Mais →</a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-projeto-inovacao card-projeto-ecomat" onclick="alternarExpandirProjeto(this)">
                                            <div class="cabecalho-card-projeto">
                                                <span class="nome-projeto-inovacao">Mario Tadashi Shimanuki</span>
                                                <div class="botao-expandir-projeto">+</div>
                                            </div>
                                            <div class="detalhes-projeto-expandido">
                                                <div class="conteudo-detalhes-projeto">
                                                    <p class="descricao-detalhes-projeto">
                                                        lattes.cnpq.br/2673060331553099<br><br>

                                                        shimanuki@ifsp.edu.br<br><br>
                                                    </p>
                                                    <a class="botao-ver-mais-projeto" href="https://integra.ifsp.edu.br/p/mario-tadashi-shimanuki" target="_blank" rel="noopener">Ver Mais →</a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-projeto-inovacao card-projeto-educis escuro" onclick="alternarExpandirProjeto(this)">
                                            <div class="cabecalho-card-projeto">
                                                <span class="nome-projeto-inovacao">Vassiliki Teresinha Galvão Boulomytis</span>
                                                <div class="botao-expandir-projeto">+</div>
                                            </div>
                                            <div class="detalhes-projeto-expandido">
                                                <div class="conteudo-detalhes-projeto">
                                                    <p class="descricao-detalhes-projeto">
                                                        lattes.cnpq.br/9152140186894460<br><br>

                                                        vassiliki@ifsp.edu.br<br>
                                                    </p>
                                                    <a class="botao-ver-mais-projeto" href="https://integra.ifsp.edu.br/p/jose-americo-alves-salvador-filho" target="_blank" rel="noopener">Ver Mais →</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="titulo-principal-inovacao"><br><br><br><br>Linha do Tempo</h1>
                                <div class="slider-wrapper-container">
                                    <div class="slider-container">
                                        <div class="slider" id="slider">

                                            <div class="slider-wrapper" data-slide="2021">
                                                <div class="slider-item">
                                                    2021
                                                    <div class="bubble"><span>Visualizar</span></div>
                                                </div>
                                            </div>

                                            <div class="slider-wrapper" data-slide="2022">
                                                <div class="slider-item">
                                                    2022
                                                    <div class="bubble"><span>Visualizar</span></div>
                                                </div>
                                            </div>

                                            <div class="slider-wrapper" data-slide="2023">
                                                <div class="slider-item">
                                                    2023
                                                    <div class="bubble"><span>Visualizar</span></div>
                                                </div>
                                            </div>

                                            <div class="slider-wrapper" data-slide="2024">
                                                <div class="slider-item">
                                                    2024
                                                    <div class="bubble"><span>Visualizar</span></div>
                                                </div>
                                            </div>

                                            <div class="slider-wrapper" data-slide="2025">
                                                <div class="slider-item">
                                                    2025
                                                    <div class="bubble"><span>Visualizar</span></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Container de detalhes abaixo da linha do tempo -->
                                <div id="timeline-details" class="timeline-details" style="margin-top:16px; padding:16px; border-radius:12px; background:#0f172a; color:#e2e8f0; display:none;"></div>

                                <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
                                <script>
                                    // Animação de scroll infinito
                                    const slider = document.getElementById('slider');
                                    const sliderWidth = slider.scrollWidth;
                                    const clone = slider.innerHTML;
                                    slider.innerHTML += clone;

                                    gsap.to(slider, {
                                        x: -sliderWidth,
                                        duration: 25,
                                        ease: "none",
                                        repeat: -1
                                    });

                                    // Dados de descrição por ano
                                    const timelineContent = {
                                        "2021": "",
                                        "2022": "",
                                        "2023": "",
                                        "2024": "",
                                        "2025": ""
                                    };

                                    const details = document.getElementById('timeline-details');

                                    function showDetails(year) {
                                        const text = timelineContent[year] || "status ainda não disponível.";
                                        details.innerHTML = `<h3 style="margin:0 0 8px;">${year}</h3><p style="margin:0;">${text}</p>`;
                                        details.style.display = "block";
                                        details.scrollIntoView({
                                            behavior: "smooth",
                                            block: "nearest"
                                        });
                                    }

                                    // Delegação de clique nos cards
                                    slider.addEventListener('click', (e) => {
                                        const wrapper = e.target.closest('.slider-wrapper');
                                        if (!wrapper) return;
                                        const year = wrapper.getAttribute('data-slide');
                                        showDetails(year);
                                    });
                                </script>
                                <p><br><br><br></p>


                            </section>
                    </section>




                    <!-- ===== REGULAMENTO ===== -->
                    <section id="regulamento" class="content">
                        <h2>Regulamento</h2>
                        <p>
                            O regulamento do Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis
                            (CEPIN-CIS) foi
                            aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco
                            normativo consolida
                            a missão do CEPIN-CIS como espaço de fomento à pesquisa aplicada, à inovação tecnológica e à
                            reflexão
                            crítica sobre os desafios contemporâneos das cidades.

                            O regulamento estabelece as diretrizes para a participação de servidores e discentes
                            vinculados a
                            projetos de ensino, pesquisa ou extensão que dialoguem com as áreas temáticas do Centro,
                            além de abrir
                            espaço para a colaboração de pesquisadores externos
                        </p>

                        <a class="btn"
                            href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Clique
                            aqui para ver o regulamento</a>
                    </section>

                    <!-- ===== PARCEIROS ===== -->
                    <section id="parceiros" class="content alt">
                        <h2>Parceiros</h2>


                        <div class="carousel-container">
                            <button class="nav-arrow left">‹</button>
                            <div class="carousel-track">
                                <div class="card" data-index="0">
                                    <img src="./img/Kobenhavns.png" alt="copenhagen">
                                </div>
                                <div class="card" data-index="1">
                                    <img src="./img/Roma 3.png" alt="roma 3">
                                </div>
                                <div class="card" data-index="2">
                                    <img src="./img/FUHZOU UNIVERSITY SLIDER.png" alt="fuhzou">
                                </div>
                                <div class="card" data-index="3">
                                    <img src="./img/getis.png" alt="getis">
                                </div>
                                <div class="card" data-index="4">
                                    <img src="./img/i2v2.png" alt="i2">
                                </div>
                                <div class="card" data-index="5">
                                    <img src="./img/Enasa.png" alt="enasa">
                                </div>
                            </div>
                            <button class="nav-arrow right">›</button>
                        </div>

                        <div class="member-info">
                            <h2 class="member-name">David Kim</h2>
                            <p class="member-role">Founder</p>
                        </div>

                        <div class="dots">
                            <div class="dot active" data-index="0"></div>
                            <div class="dot" data-index="1"></div>
                            <div class="dot" data-index="2"></div>
                            <div class="dot" data-index="3"></div>
                            <div class="dot" data-index="4"></div>
                            <div class="dot" data-index="5"></div>
                        </div>
                    </section>



                    <!-- ===== CONTATO ===== -->
                    <section id="contato" class="content alt">
                        <h2>Contato</h2>
                        <p>
                            Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.
                        </p>
                        <a href="mailto:cepin.cis@ifspcaraguatatuba.edu.br" class="btn2">Enviar E-mail</a>
                        <p><br><br></p>

                        <div style="padding: 0 20px;">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228.4439040435433!2d-45.4258447087537!3d-23.636501255140573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-BR!2sbr!4v1763413745838!5m2!1spt-BR!2sbr"
                                width="100%"
                                height="450"
                                style="border:0; border-radius: 16px; overflow: hidden;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </section>
                </section>


                <!-- ===== FOOTER ===== -->
                <footer>
                    <p>© 2025 CEPIN-CIS | Todos os direitos reservados</p>
                </footer>
            </div>
        </div>
    </div>


    <script src="script.js"></script>
</body>

</html>