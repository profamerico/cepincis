<?php
$pageTitle = 'Contato | CEPIN-CIS';
$bodyClass = 'public-page';
$projectInterest = trim((string) ($_GET['project'] ?? ''));
$categoryInterest = trim((string) ($_GET['category'] ?? ''));
$hasContext = $projectInterest !== '' || $categoryInterest !== '';

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <section id="contato" class="public-contact-grid public-contact-grid--page">
        <article class="panel-card public-copy-card public-copy-card--featured">
            <h1>Contato</h1>

            <p>Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.</p>

            <?php if ($hasContext): ?>
                <div class="public-context-note">
                    <?php if ($projectInterest !== ''): ?>
                        <p><strong>Projeto:</strong> <?php echo htmlspecialchars($projectInterest, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if ($categoryInterest !== ''): ?>
                        <p><strong>Categoria:</strong> <?php echo htmlspecialchars($categoryInterest, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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
</main>

<?php include_once 'includes/footer.php'; ?>
