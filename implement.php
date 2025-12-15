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


    <div class="ball"></div>

    <!-- ===== SEÇÃO DE INOVAÇÃO ===== -->
    <section class="secao-inovacao-areas-tematicas">
        <div class="container-inovacao-principal">
            <div class="coluna-esquerda-inovacao">
                <h3 class="cabecalho-categoria-inovacao">CEPIN-CIS</h3>
                <h2 class="titulo-principal-inovacao">Áreas temáticas</h2>
                <p class="descricao-inovacao">
                    As Áreas Temáticas nas quais serão alinhadas as linhas de pesquisas foram definidas para orientar as atividades do CEPIN-CIS e compreendem:
                </p>

                <div class="card-projeto-inovacao card-projeto-qw" onclick="alternarExpandirProjeto(this)">
                    <div class="cabecalho-card-projeto">
                        <span class="nome-projeto-inovacao">Formação de recursos humanos para cidades inteligentes e sustentáveis</span>
                        <div class="botao-expandir-projeto">+</div>
                    </div>
                    <div class="detalhes-projeto-expandido">
                        <div class="conteudo-detalhes-projeto">
                            <p class="descricao-detalhes-projeto">
                            Investigar materiais sustentáveis e promoção de economia circular, reduzindo o impacto ambiental.
                            </p>
                            <button class="botao-ver-mais-projeto" href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Ver Mais →</button>
                        </div>
                    </div>
                </div><br>
                <div class="card-projeto-inovacao card-projeto-qo" onclick="alternarExpandirProjeto(this)">
                    <div class="cabecalho-card-projeto">
                        <span class="nome-projeto-inovacao">Novos materiais e economia circular</span>
                        <div class="botao-expandir-projeto">+</div>
                    </div>
                    <div class="detalhes-projeto-expandido">
                        <div class="conteudo-detalhes-projeto">
                            <p class="descricao-detalhes-projeto">
                            Desenvolvimento de recursos humanos e métodos ágeis de capacitação para impulsionar a inovação em Cidades Inteligentes e Sustentáveis.
                            </p>
                            <button class="botao-ver-mais-projeto" href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Ver Mais →</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="coluna-direita-projetos">
                <div class="card-projeto-inovacao card-projeto-qw" onclick="alternarExpandirProjeto(this)">
                    <div class="cabecalho-card-projeto">
                        <span class="nome-projeto-inovacao">Desenvolvimento tecnológico e conectividade para cidades inteligentes e sustentáveis</span>
                        <div class="botao-expandir-projeto">+</div>
                    </div>
                    <div class="detalhes-projeto-expandido">
                        <div class="conteudo-detalhes-projeto">
                            <p class="descricao-detalhes-projeto">
                            Desenvolver tecnologias avançadas e soluções de conectividade para criar ambientes urbanos mais inteligentes, eficientes e sustentáveis.
                            </p>
                            <a class="botao-ver-mais-projeto" href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf" target="_blank" rel="noopener">Ver Mais →</a>
                        </div>
                    </div>
                </div>

                <div class="card-projeto-inovacao card-projeto-qo" onclick="alternarExpandirProjeto(this)">
                    <div class="cabecalho-card-projeto">
                        <span class="nome-projeto-inovacao">Descarbonização do ambiente construído</span>
                        <div class="botao-expandir-projeto">+</div>
                    </div>
                    <div class="detalhes-projeto-expandido">
                        <div class="conteudo-detalhes-projeto">
                            <p class="descricao-detalhes-projeto">
                            Promover a redução das emissões de carbono em edifícios, infraestrutura, mobilidade urbana, fontes de energia e saneamento ambiental.
                            </p>
                            <a class="botao-ver-mais-projeto" href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf" target="_blank" rel="noopener">Ver Mais →</a>
                        </div>
                    </div>
                </div>
                
                <div class="card-projeto-inovacao card-projeto-qu" onclick="alternarExpandirProjeto(this)">
                    <div class="cabecalho-card-projeto">
                        <span class="nome-projeto-inovacao">Monitoramento e operações urbanas inteligentes</span>
                        <div class="botao-expandir-projeto">+</div>
                    </div>
                    <div class="detalhes-projeto-expandido">
                        <div class="conteudo-detalhes-projeto">
                            <p class="descricao-detalhes-projeto">
                            Desenvolver soluções para monitorar e gerenciar a infraestrutura urbana, utilizando tecnologias como gêmeos digitais, plataformas digitais, sistemas autônomos e drones, para otimizar o funcionamento de serviços essenciais e melhorar a resiliência climática.
                            </p>
                            <a class="botao-ver-mais-projeto" href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf" target="_blank" rel="noopener">Ver Mais →</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="divisor">
        <div>
            <div class="containerdivisor">
            </div>
        </div>
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
    <script src="script.js"></script>


</body>

</html>