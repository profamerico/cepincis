<?php
$pageTitle = 'Sobre | CEPIN-CIS';
$bodyClass = 'public-page';
$regulationUrl = 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf';

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <section id="sobre" class="public-story-grid">
        <article class="panel-card public-copy-card public-copy-card--featured">
            <h1>Sobre nós</h1>

            <p>O Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS), implementado no IFSP campus Caraguatatuba, tem como missão fomentar o desenvolvimento de cidades inteligentes e sustentáveis. Para isso, atua como um repositório de tecnologias, um espaço dedicado à experimentação prática e um agente de interlocução capaz de estabelecer conexões produtivas entre os setores público e privado.</p>

            <p>Sua atuação se estrutura de maneira a promover, de forma inclusiva, colaborativa e equitativa, o debate e a construção de soluções que contemplem as dimensões ambientais, econômicas, sociais e culturais da sustentabilidade, buscando ampliar a capacidade dos cidadãos de contribuir para esse processo e, ao mesmo tempo, usufruir dos benefícios decorrentes de um desenvolvimento urbano mais viável, resiliente e comprometido com o futuro sustentável das cidades.</p>

            <p>Nesse sentido, o CEPIN-CIS direciona seus esforços para desenvolver investigação fundamental ou aplicada voltada ao campo das cidades inteligentes e sustentáveis, mantendo o foco em gerar conhecimento e estratégias capazes de apoiar avanços significativos nessa área. Paralelamente, dedica-se a contribuir ativamente para a inovação por meio da transferência de tecnologia, atuando como ponte entre pesquisa e aplicação prática.</p>
        </article>

        <article class="panel-card public-image-card public-image-card--banner">
            <img class="public-image-card__asset public-image-card__asset--light" src="./img/banner.png" alt="Logo CEPIN-CIS">
            <img class="public-image-card__asset public-image-card__asset--dark" src="./img/bannerescuro.png" alt="" aria-hidden="true">
        </article>
    </section>
</main>

<section id="infraestruturas" class="infraestruturas">
    <div class="infraestruturas-inner">
        <h2 class="titulo-infraestruturas-sobre">Infraestruturas</h2>

        <div class="infraestruturas-layout">
            <img src="./img/Monitor.png" alt="Monitor ilustrando a infraestrutura" class="monitor">

            <div class="infraestruturas-copy">
                <p class="descricao-infraestruturas">O CEPIN-CIS está localizado na sala 107B do IFSP Câmpus Caraguatatuba, sua infraestrutura conta com:</p>

                <div class="descricao-infraestruturas infraestruturas-list">
                    <p>03 Desktop HP 280 G5 SFF; Processador Intel® Core™ i7 da 10ª geração; Windows 10 Pro; SSD 512 GB.</p>
                    <p>PCIe® NVMe™; 16 GB; AMD Radeon™.</p>
                    <p>03 Monitor LG 23.8 Full HD 75Hz 5ms HDMI.</p>
                    <p>01 Notebook VAIO® FE15AMD® Ryzen 7; Windows 11 Home 16GB 512GB SSD FullHD.</p>
                    <p>01 Switch TP-Link 8 Portas TL-SG108E.</p>
                    <p>07 Kit Arduino contendo sensores, display, motores e a placa Mega 2560 R3.</p>
                    <p>01 Kit sensores diversos compatíveis com Arduino.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="page-shell public-shell">
    <section id="regulamento" class="panel-card public-simple-card">
        <h2>Regulamento</h2>

        <p>O regulamento do Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missão do CEPIN-CIS como espaço de fomento à pesquisa aplicada, à inovação tecnológica e à reflexão crítica sobre os desafios contemporâneos das cidades.</p>

        <p>O regulamento estabelece as diretrizes para a participação de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensão que dialoguem com as áreas temáticas do Centro, além de abrir espaço para a colaboração de pesquisadores externos.</p>

        <div class="hero-actions">
            <a class="dashboard-btn" href="<?php echo htmlspecialchars($regulationUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Clique aqui para ver o regulamento</a>
        </div>
    </section>

    <section id="contato" class="public-contact-grid">
        <article class="panel-card public-copy-card">
            <h2>Contato</h2>

            <p>Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.</p>

            <div class="public-contact-list">
                <div class="public-contact-item">
                    <strong>Email institucional</strong>
                    <a href="mailto:cepin.cis@ifspcaraguatatuba.edu.br">cepin.cis@ifspcaraguatatuba.edu.br</a>
                </div>

                <div class="public-contact-item">
                    <strong>Endereço</strong>
                    <span>IFSP Campus Caraguatatuba, sala 107B.</span>
                </div>
            </div>

            <div class="hero-actions">
                <a class="dashboard-btn" href="mailto:cepin.cis@ifspcaraguatatuba.edu.br">Enviar E-mail</a>
            </div>
        </article>

        <article class="panel-card public-map-card">
            <h2>Mapa</h2>

            <iframe
                class="public-map-frame"
                src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228.4439040435433!2d-45.4258447087537!3d-23.636501255140573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-BR!2sbr!4v1763413745838!5m2!1spt-BR!2sbr"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen=""
                title="Mapa do IFSP Campus Caraguatatuba"
            ></iframe>
        </article>
    </section>
</section>

<?php include_once 'includes/footer.php'; ?>
