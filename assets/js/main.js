document.addEventListener("DOMContentLoaded", async () => {
  const postsList = document.getElementById("postsList");
  const searchBar = document.getElementById("searchBar");
  const clearSearchBtn = document.getElementById("clearSearchBtn");

  // Função para mostrar/ocultar o botão de limpar
  const toggleClearButton = () => {
    if (searchBar.value.trim().length > 0) {
      clearSearchBtn.style.display = "flex"; // Exibe o botão
    } else {
      clearSearchBtn.style.display = "none"; // Esconde o botão
    }
  };

  // Adicionando evento para digitação na barra de pesquisa
  searchBar.addEventListener("input", toggleClearButton);

  // Função para limpar a barra de pesquisa
  clearSearchBtn.addEventListener("click", () => {
    searchBar.value = ""; // Limpa o campo
    toggleClearButton(); // Atualiza a visibilidade do botão
    renderPosts(posts); // Exibe todos os posts novamente
  });

  // Buscar postagens e exibir na página
  let posts = await getPosts();
  posts.reverse();

  function renderPosts(filteredPosts) {
    postsList.innerHTML = ""; // Limpa a lista antes de renderizar

    filteredPosts.forEach(post => {
      const postElementBlock = document.createElement("div");
      postElementBlock.classList.add("posts__item");

      // Convertendo a data para o formato brasileiro
      const [ano, mes, dia] = post.data_publicacao.split("-");
      const dataPublicacao = new Date(ano, mes - 1, dia).toLocaleDateString(
        "pt-BR"
      );

      // Convertendo as tags em uma lista
      const tagsList = post.tags
        .split(", ")
        .map(tag => `<span class="tag"><strong>${tag}</strong></span>`)
        .join(", ");

      postElementBlock.innerHTML = `
        <div class="block__top">
            <h2 class="posts__title f--18">${post.titulo}</h2>
            <p class="posts__description">${post.descricao}</p>
        </div>
        <div class="block__bottom">
          <small class="posts__date">Publicado em: <strong>${dataPublicacao}</strong></small>
          <div class="postsList__tags">
            <small>Tags: ${tagsList}</small>
          </div>
          <button class="btn__openPost">Saiba mais</button>
        </div>
      `;

      // Adicionando evento no botão
      const button = postElementBlock.querySelector(".btn__openPost");
      button.addEventListener("click", () => {
        const postId = post.id;
        const postTitulo = encodeURIComponent(
          post.titulo.replace(/\s+/g, "-").toLowerCase()
        );
        window.location.href = `../frontend/pages/post.html?id=${postId}&titulo=${postTitulo}`;
      });

      postsList.appendChild(postElementBlock);
    });
  }

  // Exibir todos os posts inicialmente
  renderPosts(posts);

  // Adicionar evento de pesquisa
  searchBar.addEventListener("input", () => {
    const searchTerm = searchBar.value.toLowerCase();
    const filteredPosts = posts.filter(post =>
      post.titulo.toLowerCase().includes(searchTerm)
    );
    renderPosts(filteredPosts);
  });
});
