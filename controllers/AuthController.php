<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $usersFile;

    public function __construct($usersFile = null) {
        $this->usersFile = $usersFile ?: __DIR__ . '/../storage/users.json';
        $this->ensureUsersFile();
    }

    private function ensureUsersFile() {
        $dir = dirname($this->usersFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->usersFile)) {
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $defaultUsers = [
                [
                    'id'           => 1,
                    'username'     => 'admin',
                    'password_hash'=> $defaultPassword,
                    'fullname'     => 'Administrador',
                    'email'        => 'admin@example.com',
                    'created_at'   => date('c')
                ]
            ];
            file_put_contents(
                $this->usersFile,
                json_encode($defaultUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        }
    }

    private function loadUsers(): array {
        $json = @file_get_contents($this->usersFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function saveUsers(array $users): void {
        file_put_contents(
            $this->usersFile,
            json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public function requireAuth(): void {
        if (empty($_SESSION['user'])) {
            header('Location: login.php');
            exit();
        }
    }

    public function redirectIfLoggedIn(): void {
        if (!empty($_SESSION['user'])) {
            header('Location: dashboard.php');
            exit();
        }
    }

    public function login(string $username, string $password): array {
        $errors = [];

        $username = trim($username);
        if ($username === '') {
            $errors[] = 'Usuário é obrigatório.';
        }
        if ($password === '') {
            $errors[] = 'Senha é obrigatória.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users = $this->loadUsers();
        $found = null;
        foreach ($users as $u) {
            if (($u['username'] ?? '') === $username) {
                $found = $u;
                break;
            }
        }

        if (!$found || !password_verify($password, $found['password_hash'] ?? '')) {
            return ['success' => false, 'errors' => ['Usuário ou senha inválidos.']];
        }

        $_SESSION['user'] = [
            'id'        => $found['id'],
            'username'  => $found['username'],
            'fullname'  => $found['fullname'] ?? $found['username'],
            'email'     => $found['email'] ?? '',
            'logged_at' => time()
        ];

        return ['success' => true];
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: login.php');
        exit();
    }

    public function register(string $username, string $password, string $fullname, string $email): array {
        $errors = [];
        $username = trim($username);
        $fullname = trim($fullname);
        $email    = trim($email);

        if ($username === '') {
            $errors[] = 'Usuário é obrigatório.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Senha deve ter ao menos 6 caracteres.';
        }
        if ($fullname === '') {
            $errors[] = 'Nome completo é obrigatório.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }

        $users = $this->loadUsers();
        foreach ($users as $u) {
            if (($u['username'] ?? '') === $username) {
                $errors[] = 'Usuário já existe.';
                break;
            }
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $nextId = 1;
        foreach ($users as $u) {
            if (isset($u['id']) && $u['id'] >= $nextId) {
                $nextId = $u['id'] + 1;
            }
        }

        $users[] = [
            'id'           => $nextId,
            'username'     => $username,
            'password_hash'=> password_hash($password, PASSWORD_DEFAULT),
            'fullname'     => $fullname,
            'email'        => $email,
            'created_at'   => date('c')
        ];

        $this->saveUsers($users);

        return ['success' => true];
    }

    public function getCurrentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    // update user profile (fullname, email, OPTIONAL password)
    public function updateProfile(array $data): array {
        $this->requireAuth();
        $current = $this->getCurrentUser();
        $id = $current['id'];

        $fullname = trim($data['fullname'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        $errors = [];
        if ($fullname === '') {
            $errors[] = 'Nome completo é obrigatório.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }
        if ($password !== '' && $password !== $passwordConfirm) {
            $errors[] = 'As senhas não coincidem.';
        }

        $users = $this->loadUsers();
        $foundIndex = null;
        foreach ($users as $idx => $u) {
            if (($u['id'] ?? null) === $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            $errors[] = 'Usuário não encontrado.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users[$foundIndex]['fullname'] = $fullname;
        $users[$foundIndex]['email']    = $email;
        if ($password !== '') {
            $users[$foundIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->saveUsers($users);

        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['email']    = $email;

        return ['success' => true];
    }

    // ONLY change password using current password + new password
    public function changePassword(string $currentPassword, string $newPassword, string $confirmPassword): array {
        $this->requireAuth();
        $currentUser = $this->getCurrentUser();
        $id = $currentUser['id'];

        $errors = [];

        if ($currentPassword === '') {
            $errors[] = 'Senha atual é obrigatória.';
        }
        if (strlen($newPassword) < 6) {
            $errors[] = 'Nova senha deve ter ao menos 6 caracteres.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'As senhas não coincidem.';
        }

        $users = $this->loadUsers();
        $foundIndex = null;
        foreach ($users as $idx => $u) {
            if (($u['id'] ?? null) === $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            $errors[] = 'Usuário não encontrado.';
        } else {
            if (!password_verify($currentPassword, $users[$foundIndex]['password_hash'] ?? '')) {
                $errors[] = 'Senha atual incorreta.';
            }
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users[$foundIndex]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->saveUsers($users);

        return ['success' => true];
    }
}
?>
