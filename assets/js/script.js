document.addEventListener("DOMContentLoaded", () => {
  const waitForHeader = setInterval(() => {
    const navbarToggle = document.getElementById("navbar-toggle");
    const navbarNav = document.getElementById("navbar-nav");
    const body = document.body;

    if (navbarToggle) {
      navbarToggle.addEventListener("click", () => {
        navbarToggle.classList.toggle("active");
        navbarNav.classList.toggle("active");
        body.classList.toggle("menu--open");
      });

      clearInterval(waitForHeader); // Para de verificar quando encontrar o elemento
    }
  }, 100); // Checa a cada 100ms
});
