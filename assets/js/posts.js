document.addEventListener("DOMContentLoaded", async () => {
  const postsList = document.getElementById("blockPosts");
  const filterButtons = document.querySelectorAll(".filter-btn");
  const urlParams = new URLSearchParams(window.location.search);
  const filterTag = urlParams.get("tag")
    ? urlParams.get("tag").trim().toLowerCase()
    : "todos";

  try {
    const posts = await getPosts();
    if (!posts || posts.length === 0) {
      console.warn("Nenhum post encontrado.");
      return;
    }

    posts.forEach(post => {
      const postElement = document.createElement("div");
      postElement.classList.add("posts__item", "block");

      const dataPublicacao = new Date(post.data_publicacao).toLocaleDateString(
        "pt-BR"
      );

      const tagsArray = post.tags.split(", ").map(tag => tag.trim());
      const tagsList = tagsArray
        .map(tag => `<span class="tag"><strong>${tag}</strong></span>`)
        .join(", ");

      postElement.setAttribute("data-tags", tagsArray.join(","));

      postElement.innerHTML = `
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

      postElement
        .querySelector(".btn__openPost")
        .addEventListener("click", () => {
          const postId = post.id;
          const postTitulo = encodeURIComponent(
            post.titulo.replace(/\s+/g, "-").toLowerCase()
          );
          window.location.href = `../../frontend/pages/post.html?id=${postId}&titulo=${postTitulo}`;
        });

      postsList.appendChild(postElement);
    });
  } catch (error) {
    console.error("Erro ao carregar posts:", error);
  }

  // Função para filtrar posts
  function filterPosts(filter) {
    document.querySelectorAll(".block").forEach(block => {
      const tags = block
        .getAttribute("data-tags")
        .split(",")
        .map(tag => tag.trim().toLowerCase());
      block.style.display =
        filter === "todos" || tags.includes(filter) ? "block" : "none";
    });
  }

  // Ativa o botão correspondente à tag da URL
  filterButtons.forEach(button => {
    const buttonTag = button.getAttribute("data-filter").trim().toLowerCase();

    button.classList.remove("selected");

    if (buttonTag === filterTag) {
      button.classList.add("selected");
    }

    // Evento de clique nos botões de filtro
    button.addEventListener("click", () => {
      filterButtons.forEach(btn => btn.classList.remove("selected"));
      button.classList.add("selected");

      const selectedFilter = button
        .getAttribute("data-filter")
        .trim()
        .toLowerCase();

      // Atualiza a URL sem recarregar a página
      const newUrl = new URL(window.location);
      newUrl.searchParams.set("tag", selectedFilter);
      window.history.pushState({}, "", newUrl);

      filterPosts(selectedFilter);
    });
  });

  // Aplica o filtro ao carregar a página
  filterPosts(filterTag);
});
