<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>404 CEPIN-CIS</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="./img/Captura_de_tela_2026-03-23_165121-removebg-preview.png">

    <style>
        :root {
            --bg: #08100d;
            --bg-secondary: #0d1713;
            --panel: #101c17;
            --border: #22352c;
            --text: #edf4ef;
            --muted: #9eafa5;
            --accent: #2f8f56;
            --accent-hover: #3ba766;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(
                    circle at 50% 35%,
                    rgba(47, 143, 86, 0.10),
                    transparent 38%
                ),
                var(--bg);
            color: var(--text);
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;
        }

        header {
            height: 84px;
            display: flex;
            align-items: center;
            padding: 0 60px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(8, 16, 13, 0.92);
        }

        .logo {
            color: var(--text);
            text-decoration: none;
            font-size: 29px;
            font-weight: 900;
            letter-spacing: -1.5px;
            font-family: 'Aldrich', sans-serif;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 24px;
        }

        .error-container {
            width: min(720px, 100%);
            text-align: center;
        }

        .error-code {
            margin: 0;
            font-size: clamp(110px, 20vw, 190px);
            line-height: 0.85;
            font-weight: 900;
            letter-spacing: -10px;
            color: transparent;
            -webkit-text-stroke: 2px rgba(237, 244, 239, 0.18);
            background: linear-gradient(
                180deg,
                #edf4ef 0%,
                #527461 100%
            );
            -webkit-background-clip: text;
            background-clip: text;
        }

        .icon {
            width: 76px;
            height: 76px;
            margin: 34px auto 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: var(--panel);
        }

        .icon svg {
            width: 36px;
            height: 36px;
            fill: none;
            stroke: var(--accent);
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            margin: 0;
            font-size: clamp(27px, 4vw, 38px);
            line-height: 1.15;
            letter-spacing: -1px;
        }

        .description {
            max-width: 530px;
            margin: 16px auto 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }

        .actions {
            margin-top: 32px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 24px;
            border-radius: 8px;
            border: 1px solid var(--border);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }

        .button-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .button-primary:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
            transform: translateY(-1px);
        }

        .button-secondary {
            background: var(--panel);
            color: var(--text);
        }

        .button-secondary:hover {
            background: #17251f;
            border-color: #315043;
            transform: translateY(-1px);
        }

        footer {
            padding: 24px;
            text-align: center;
            color: #687a70;
            font-size: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
        }

        @media (max-width: 700px) {
            header {
                height: 72px;
                padding: 0 24px;
            }

            .logo {
                font-size: 24px;
            }

            main {
                padding: 40px 20px;
            }

            .error-code {
                letter-spacing: -6px;
            }

            .icon {
                width: 66px;
                height: 66px;
                margin-top: 28px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="/" class="logo" aria-label="CEPIN-CIS">
        CEPIN-CIS
    </a>
</header>

<main>
    <section class="error-container" aria-labelledby="error-title">

        <p class="error-code" aria-hidden="true">404</p>

        <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 48 48">
                <path d="M18 35h12"></path>
                <path d="M19 39h10"></path>
                <path d="M24 5v4"></path>
                <path d="M8.5 11.5l2.8 2.8"></path>
                <path d="M39.5 11.5l-2.8 2.8"></path>
                <path d="M5 24h4"></path>
                <path d="M39 24h4"></path>
                <path d="M14 27a10 10 0 1 1 20 0c0 3.3-1.6 5.7-4 7H18c-2.4-1.3-4-3.7-4-7Z"></path>
            </svg>
        </div>

        <h1 id="error-title">Página não encontrada</h1>

        <p class="description">
            A página que você tentou acessar não existe, foi movida
            ou o endereço informado está incorreto.
        </p>

        <div class="actions">
            <a href="/" class="button button-primary">
                Voltar para o início
            </a>

            <a href="javascript:history.back()" class="button button-secondary">
                Página anterior
            </a>
        </div>

    </section>
</main>

<footer>
    CEPIN-CIS · Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis
</footer>

</body>
</html>