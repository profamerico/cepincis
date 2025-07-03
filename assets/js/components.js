function loadComponent(componentName) {
  fetch(`/frontend/components/${componentName}.html`)
    .then(response => response.text())
    .then(data => {
      document.getElementById(componentName).innerHTML = data;
    });
}

// Carregar os componentes de cabeçalho e rodapé em todas as páginas
document.addEventListener("DOMContentLoaded", function() {
  loadComponent("header");
  loadComponent("footer");
});
