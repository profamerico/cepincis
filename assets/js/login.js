document.addEventListener("DOMContentLoaded", () => {
  const formLogin = document.getElementById("formLogin");

  formLogin.addEventListener("submit", async event => {
    event.preventDefault();

    const email = document.getElementById("loginEmail").value;
    const senha = document.getElementById("loginSenha").value;

    try {
      const res = await fetch(`${API_URL}/api/users/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, senha })
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || "Erro ao fazer login");
      }

      // Salva o token no localStorage
      localStorage.setItem("token", data.token);
      localStorage.setItem("user", JSON.stringify(data.user));

      alert("Login bem-sucedido!");
      window.location.href = "admin.html"; // Redireciona para outra página
    } catch (error) {
      alert(error.message);
    }
  });
});
