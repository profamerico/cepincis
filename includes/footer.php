<?php
$footerCurrentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$mobileFooterLinks = [
    ['href' => './index.php', 'label' => 'Home', 'icon' => 'fa-house', 'active' => $footerCurrentScript === 'index.php'],
    ['href' => './about.php#sobre', 'label' => 'Sobre', 'icon' => 'fa-circle-info', 'active' => $footerCurrentScript === 'about.php'],
    ['href' => './implement.php', 'label' => 'Areas', 'icon' => 'fa-diagram-project', 'active' => $footerCurrentScript === 'implement.php'],
    ['href' => 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf', 'label' => 'Regul.', 'icon' => 'fa-file-lines', 'active' => false, 'external' => true],
    ['href' => './contact.php', 'label' => 'Contato', 'icon' => 'fa-envelope', 'active' => $footerCurrentScript === 'contact.php'],
];
?>
    <!-- ===== FOOTER ===== -->
    <footer>
        <p>&copy; 2025 CEPIN-CIS | Todos os direitos reservados</p>
    </footer>

    <nav class="mobile-page-footer" aria-label="Navegacao rapida">
        <?php foreach ($mobileFooterLinks as $mobileFooterLink): ?>
            <a
                href="<?php echo htmlspecialchars((string) $mobileFooterLink['href'], ENT_QUOTES, 'UTF-8'); ?>"
                class="mobile-page-footer-link<?php echo !empty($mobileFooterLink['active']) ? ' is-active' : ''; ?>"
                <?php echo !empty($mobileFooterLink['active']) ? ' aria-current="page"' : ''; ?>
                <?php echo !empty($mobileFooterLink['external']) ? ' target="_blank" rel="noopener"' : ''; ?>
            >
                <i class="fa-solid <?php echo htmlspecialchars((string) $mobileFooterLink['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                <span><?php echo htmlspecialchars((string) $mobileFooterLink['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
