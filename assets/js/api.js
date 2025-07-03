const API_URL = "http://localhost:3000"; // URL do backend

// Buscar todas as postagens
async function getPosts() {
  try {
    const res = await fetch(`${API_URL}/api/posts`);
    if (!res.ok) {
      throw new Error(`Erro na API: ${res.status}`);
    }
    const posts = await res.json();
    return posts;
  } catch (error) {
    console.error("Erro ao buscar postagens:", error);
    return []; // Retorna um array vazio caso haja erro
  }
}

// Buscar todos os usuários
async function getUsers() {
  try {
    const res = await fetch(`${API_URL}/api/users`);
    if (!res.ok) {
      throw new Error(`Erro na API: ${res.status}`);
    }
    const users = await res.json();
    return users;
  } catch (error) {
    console.error("Erro ao buscar usuários: ", error);
    return []; // Retorna um array vazio caso haja erro
  }
}
