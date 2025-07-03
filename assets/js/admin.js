document.addEventListener("DOMContentLoaded", async () => {
  // Função para decodificar o JWT e verificar sua validade
  function decodeJWT(token) {
    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map(function(c) {
          return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
        })
        .join("")
    );
    return JSON.parse(jsonPayload);
  }

  // Verificar se o token existe no localStorage
  const token = localStorage.getItem("token");

  if (!token) {
    // Se não houver token, redireciona para a página de login
    window.location.href = "login.html";
    return;
  }

  try {
    const decodedToken = decodeJWT(token);

    // Verificar se o token ainda é válido (não expirou)
    const currentTime = Date.now() / 1000; // Em segundos
    if (decodedToken.exp < currentTime) {
      // Se o token expirou, redireciona para a página de login
      localStorage.removeItem("token");
      window.location.href = "login.html";
      return;
    }

    // Token válido, usuário pode acessar a página
    console.log("Usuário autenticado!");
    // Aqui você pode carregar os dados do usuário no frontend se necessário
  } catch (error) {
    console.error("Erro ao decodificar o token:", error);
    // Se o token estiver corrompido ou inválido, redireciona para o login
    localStorage.removeItem("token");
    window.location.href = "login.html";
  }

  // Botão de logout
  const logoutButton = document.getElementById("btnLogout");

  // Adicionando evento de clique
  logoutButton.addEventListener("click", () => {
    // Limpar os dados do usuário e o token do localStorage
    localStorage.removeItem("token");
    localStorage.removeItem("user");

    // Redirecionar para a página de login
    window.location.href = "login.html";
  });

  const postsListAdmin = document.getElementById("postsListAdmin");

  // Função para criar o conteúdo do post
  const createPostElement = post => {
    const postElement = document.createElement("div");
    postElement.classList.add("posts__item");

    // Convertendo a data para o formato brasileiro
    const dataPublicacao = new Date(
      post.data_publicacao + "T00:00:00"
    ).toLocaleDateString("pt-BR");

    // Convertendo as tags em uma lista
    const tagsList = post.tags
      .split(", ")
      .map(tag => `<span class="tag"><strong>${tag}</strong></span>`)
      .join(", "); // Agora as tags são separadas por vírgula

    postElement.innerHTML = `
      <div class="block__content">
        <h2 class="posts__title f--18">${post.titulo}</h2>
        <p class="posts__description">${post.descricao}</p>
        <small class="posts__date">Publicado em: <strong>${dataPublicacao}</strong></small>
        <div class="posts__tags">
          <small>Tags: ${tagsList}</small>
        </div>
      </div>
      <div class="block__panel">
        <button class="btn__editPost f--16" data-id="${post.id}">
          <ion-icon class="ion-icons f--16" name="create-outline"></ion-icon> Editar
        </button>
        <button class="btn__deletePost f--16" data-id="${post.id}">
          <ion-icon class="ion-icons f--16" name="trash-outline"></ion-icon> Excluir
        </button>
      </div>
    `;

    // Adicionando evento no botão "Deletar"
    const buttonDelete = postElement.querySelector(".btn__deletePost");
    buttonDelete.addEventListener("click", async event => {
      const postId = event.target.getAttribute("data-id");
      const confirmDelete = confirm(
        "Você tem certeza que deseja excluir esta postagem?"
      );
      if (confirmDelete) {
        try {
          const response = await fetch(
            `${API_URL}/api/posts/delete-post/${postId}`,
            {
              method: "DELETE",
              headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`
              }
            }
          );
          if (response.ok) {
            alert("Postagem excluída com sucesso!");
            postElement.remove(); // Remover o post da lista
          } else {
            alert("Erro ao excluir postagem.");
          }
        } catch (error) {
          console.error("Erro ao excluir postagem:", error);
          alert("Erro ao excluir postagem.");
        }
      }
    });

    // Adicionando evento no botão "Editar"
    const buttonEdit = postElement.querySelector(".btn__editPost");
    buttonEdit.addEventListener("click", () => {
      // Reseta as tags (desmarca todas as checkboxes)
      const checkboxes = document.querySelectorAll("input[name='tags']");
      checkboxes.forEach(checkbox => {
        checkbox.checked = false;
      });

      // Preenche os campos do modal com os dados do post
      document.getElementById("editTitulo").value = post.titulo;
      document.getElementById("editDescricao").value = post.descricao;
      document.getElementById("editConteudo").value = post.conteudo;

      const modalEdit = document.getElementById("modalEdit");
      const body = document.body;

      modalEdit.classList.add("active");
      body.classList.add("scrolling--disabled");

      // Marcar as tags associadas ao post no formulário de edição
      const tags = post.tags.split(", ");
      tags.forEach(tag => {
        const checkbox = document.getElementById(`edit_${tag}`);
        if (checkbox) {
          checkbox.checked = true;
        }
      });

      // Evento para salvar as alterações
      const formEditPost = document.getElementById("formEditPost");
      formEditPost.onsubmit = async e => {
        e.preventDefault();

        // Captura as tags selecionadas no formulário
        const selectedTags = [];
        const checkboxes = formEditPost.querySelectorAll(
          "input[name='tags']:checked"
        );
        checkboxes.forEach(checkbox => {
          selectedTags.push(checkbox.value);
        });

        // Dados atualizados
        const updatedPost = {
          titulo: formEditPost.titulo.value,
          descricao: formEditPost.descricao.value,
          conteudo: formEditPost.conteudo.value,
          tags: selectedTags.join(", ") // Converte as tags selecionadas para uma string separada por vírgulas
        };

        try {
          const response = await fetch(
            `${API_URL}/api/posts/update-post/${post.id}`,
            {
              method: "PUT",
              headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("token")}`
              },
              body: JSON.stringify(updatedPost)
            }
          );

          if (response.ok) {
            alert("Postagem atualizada com sucesso!");

            // Atualizar a postagem na lista sem recarregar a página
            postElement.querySelector(".posts__title").textContent =
              updatedPost.titulo;
            postElement.querySelector(".posts__description").textContent =
              updatedPost.descricao;

            // Atualiza as tags na postagem
            const tagsList = updatedPost.tags
              .split(", ")
              .map(tag => `<span class="tag"><strong>${tag}</strong></span>`)
              .join(", "); // Tags separadas por vírgulas

            const tagsElement = postElement.querySelector(".posts__tags");
            tagsElement.innerHTML = `<small>Tags: ${tagsList}</small>`;

            // Atualiza o objeto 'post' com as novas tags
            post.tags = updatedPost.tags; // Atualiza o objeto 'post' com as tags mais recentes

            // Fechar o modal
            modalEdit.classList.remove("active");
            body.classList.remove("scrolling--disabled");
          } else {
            alert("Erro ao atualizar postagem.");
          }
        } catch (error) {
          console.error("Erro ao atualizar postagem:", error);
          alert("Erro ao atualizar postagem.");
        }
      };
    });

    return postElement;
  };

  // Buscar postagens e exibir na página
  const posts = await getPosts();

  posts.reverse();

  posts.forEach(post => {
    const postElement = createPostElement(post);
    postsListAdmin.appendChild(postElement);
  });

  // Evento para adicionar nova postagem
  const buttonAdd = document.getElementById("btnAddPost");
  const modalAdd = document.getElementById("modalAdd");

  buttonAdd.addEventListener("click", () => {
    modalAdd.classList.add("active");
    document.body.classList.add("scrolling--disabled");

    // Evento para salvar as alterações da nova postagem
    const formAddPost = document.getElementById("formAddPost");

    formAddPost.onsubmit = async e => {
      e.preventDefault();

      // Coletando as tags selecionadas
      const selectedTags = [];
      const tagCheckboxes = formAddPost.querySelectorAll(
        'input[name="tags"]:checked'
      );

      tagCheckboxes.forEach(checkbox => {
        selectedTags.push(checkbox.value); // Adiciona o valor do checkbox ao array
      });

      if (selectedTags.length === 0) {
        alert("Por favor, selecione ao menos uma tag.");
        return;
      }

      const now = new Date();
      const dataPublicacao = new Date(
        now.getTime() - now.getTimezoneOffset() * 60000
      ).toISOString();

      const newPost = {
        titulo: formAddPost.titulo.value,
        descricao: formAddPost.descricao.value,
        conteudo: formAddPost.conteudo.value,
        tags: selectedTags.join(", "), // Converte o array de tags em uma string separada por vírgula
        data_publicacao: dataPublicacao
      };

      try {
        const response = await fetch(`${API_URL}/api/posts/create-post`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${localStorage.getItem("token")}`
          },
          body: JSON.stringify(newPost)
        });

        const responseData = await response.json();
        if (response.ok) {
          alert("Postagem criada com sucesso!");

          // Criar o novo elemento de post
          const newPostElement = createPostElement({
            id: responseData.id,
            titulo: newPost.titulo,
            descricao: newPost.descricao,
            conteudo: newPost.conteudo,
            tags: newPost.tags,
            data_publicacao: newPost.data_publicacao
          });

          postsListAdmin.appendChild(newPostElement); // Adicionar na lista de posts

          // Fechar o modal e limpar o formulário
          modalAdd.classList.remove("active");
          document.body.classList.remove("scrolling--disabled");
          formAddPost.reset();
        } else {
          alert("Erro ao criar postagem.");
        }
      } catch (error) {
        console.error("Erro ao criar postagem:", error);
        alert("Erro ao criar postagem.");
      }
    };
  });

  // Fechar modais
  const buttonCloseModal = () => {
    const modalEdit = document.getElementById("modalEdit");
    const modalAdd = document.getElementById("modalAdd");
    document.body.classList.remove("scrolling--disabled");

    if (modalEdit.classList.contains("active"))
      modalEdit.classList.remove("active");
    if (modalAdd.classList.contains("active"))
      modalAdd.classList.remove("active");
  };

  const buttonCloseModalEdit = document.getElementById("btnCloseModalEdit");
  const buttonCloseModalAdd = document.getElementById("btnCloseModalAdd");

  buttonCloseModalEdit.addEventListener("click", buttonCloseModal);
  buttonCloseModalAdd.addEventListener("click", buttonCloseModal);
});
