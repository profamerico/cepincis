<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    private const ROLE_MEMBER = 'member';
    private const ROLE_ACADEMIC_RESEARCHER = 'academic_researcher';
    private const ROLE_ASSOCIATE_RESEARCHER = 'associate_researcher';
    private const ROLE_FULL_RESEARCHER = 'full_researcher';
    private const ROLE_ADMIN = 'admin';

    private const ROLE_DEFINITIONS = [
        self::ROLE_MEMBER => [
            'label' => 'Usuario',
            'rank' => 10,
        ],
        self::ROLE_ACADEMIC_RESEARCHER => [
            'label' => 'Pesquisador Academico',
            'rank' => 20,
        ],
        self::ROLE_ASSOCIATE_RESEARCHER => [
            'label' => 'Pesquisador Associado',
            'rank' => 30,
        ],
        self::ROLE_FULL_RESEARCHER => [
            'label' => 'Pesquisador Pleno',
            'rank' => 40,
        ],
        self::ROLE_ADMIN => [
            'label' => 'Administrador',
            'rank' => 100,
        ],
    ];

    private $usersFile;

    public function __construct($usersFile = null)
    {
        $this->usersFile = $usersFile ?: __DIR__ . '/../storage/users.json';
        $this->ensureUsersFile();
    }

    public function requireAuth(): void
    {
        if ($this->getCurrentUser() === null) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            header('Location: dashboard.php');
            exit();
        }
    }

    public function redirectIfLoggedIn(): void
    {
        if ($this->getCurrentUser() !== null) {
            header('Location: dashboard.php');
            exit();
        }
    }

    public function login(string $username, string $password): array
    {
        $errors = [];
        $username = trim($username);

        if ($username === '') {
            $errors[] = 'Usuario e obrigatorio.';
        }
        if ($password === '') {
            $errors[] = 'Senha e obrigatoria.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        foreach ($this->loadUsers() as $user) {
            if (strcasecmp($user['username'], $username) !== 0) {
                continue;
            }

            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'errors' => ['Usuario ou senha invalidos.']];
            }

            $this->syncSessionUser($user);
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'errors' => ['Usuario ou senha invalidos.']];
    }

    public function loginSocialUser(string $displayName, string $email = '', string $provider = 'social'): array
    {
        $displayName = trim($displayName);
        $email = trim($email);
        $provider = trim($provider) !== '' ? trim($provider) : 'social';

        if ($displayName === '') {
            return ['success' => false, 'errors' => ['Nao foi possivel identificar o usuario da rede social.']];
        }

        $users = $this->loadUsers();
        $foundIndex = null;

        if ($email !== '') {
            foreach ($users as $index => $user) {
                if (($user['email'] ?? '') !== '' && strcasecmp($user['email'], $email) === 0) {
                    $foundIndex = $index;
                    break;
                }
            }
        }

        if ($foundIndex === null) {
            $username = $this->generateUniqueUsername($users, $displayName, $email, $provider);
            $users[] = [
                'id' => $this->getNextUserId($users),
                'username' => $username,
                'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'fullname' => $displayName,
                'email' => $email,
                'role' => self::ROLE_MEMBER,
                'provider' => $provider,
                'created_at' => $this->nowIso(),
                'updated_at' => $this->nowIso(),
            ];
            $foundIndex = array_key_last($users);
        } else {
            $users[$foundIndex]['fullname'] = $displayName;
            if ($email !== '') {
                $users[$foundIndex]['email'] = $email;
            }
            $users[$foundIndex]['provider'] = $provider;
            $users[$foundIndex]['updated_at'] = $this->nowIso();
        }

        $this->saveUsers($users);
        $savedUser = $this->loadUsers()[$foundIndex] ?? $users[$foundIndex];
        $this->syncSessionUser($savedUser);

        return ['success' => true, 'user' => $savedUser];
    }

    public function logout(): void
    {
        $this->clearLegacySessionKeys();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: login.php');
        exit();
    }

    public function register(string $username, string $password, string $fullname, string $email): array
    {
        $errors = [];
        $users = $this->loadUsers();

        $username = trim($username);
        $fullname = trim($fullname);
        $email = trim($email);

        if ($username === '') {
            $errors[] = 'Usuario e obrigatorio.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Senha deve ter ao menos 6 caracteres.';
        }
        if ($fullname === '') {
            $errors[] = 'Nome completo e obrigatorio.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalido.';
        }
        if ($this->findUserIndexByUsername($users, $username) !== null) {
            $errors[] = 'Usuario ja existe.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users[] = [
            'id' => $this->getNextUserId($users),
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'fullname' => $fullname,
            'email' => $email,
            'role' => self::ROLE_MEMBER,
            'provider' => 'local',
            'created_at' => $this->nowIso(),
            'updated_at' => $this->nowIso(),
        ];

        $this->saveUsers($users);

        return ['success' => true];
    }

    public function getCurrentUser(): ?array
    {
        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        return null;
    }

    public function getRoleDefinitions(): array
    {
        return self::ROLE_DEFINITIONS;
    }

    public function getRoleKey($roleOrUser = null): string
    {
        return $this->normalizeRole($roleOrUser);
    }

    public function getRoleLabel($roleOrUser = null): string
    {
        $roleKey = $this->normalizeRole($roleOrUser);

        return self::ROLE_DEFINITIONS[$roleKey]['label'] ?? self::ROLE_DEFINITIONS[self::ROLE_MEMBER]['label'];
    }

    public function getRoleRank($roleOrUser = null): int
    {
        $roleKey = $this->normalizeRole($roleOrUser);

        return (int) (self::ROLE_DEFINITIONS[$roleKey]['rank'] ?? self::ROLE_DEFINITIONS[self::ROLE_MEMBER]['rank']);
    }

    public function hasAtLeastRole(string $requiredRole, ?array $user = null): bool
    {
        return $this->getRoleRank($user) >= $this->getRoleRank($requiredRole);
    }

    public function isAdmin(?array $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->normalizeRole($user) === self::ROLE_ADMIN;
    }

    public function updateProfile(array $data): array
    {
        $this->requireAuth();

        $current = $this->getCurrentUser();
        $users = $this->loadUsers();
        $userId = (int) $current['id'];
        $userIndex = $this->findUserIndexById($users, $userId);

        if ($userIndex === null) {
            return ['success' => false, 'errors' => ['Usuario nao encontrado.']];
        }

        $fullname = trim((string) ($data['fullname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? '');

        $errors = [];
        if ($fullname === '') {
            $errors[] = 'Nome completo e obrigatorio.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalido.';
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'A nova senha deve ter ao menos 6 caracteres.';
        }
        if ($password !== '' && $password !== $passwordConfirm) {
            $errors[] = 'As senhas nao coincidem.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users[$userIndex]['fullname'] = $fullname;
        $users[$userIndex]['email'] = $email;
        $users[$userIndex]['updated_at'] = $this->nowIso();

        if ($password !== '') {
            $users[$userIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->saveUsers($users);
        $this->syncSessionUser($users[$userIndex]);

        return ['success' => true];
    }

    public function changePassword(string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $this->requireAuth();

        $currentUser = $this->getCurrentUser();
        $users = $this->loadUsers();
        $userIndex = $this->findUserIndexById($users, (int) $currentUser['id']);

        if ($userIndex === null) {
            return ['success' => false, 'errors' => ['Usuario nao encontrado.']];
        }

        $errors = [];

        if ($currentPassword === '') {
            $errors[] = 'Senha atual e obrigatoria.';
        }
        if (strlen($newPassword) < 6) {
            $errors[] = 'Nova senha deve ter ao menos 6 caracteres.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'As senhas nao coincidem.';
        }
        if (!password_verify($currentPassword, $users[$userIndex]['password_hash'])) {
            $errors[] = 'Senha atual incorreta.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $users[$userIndex]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $users[$userIndex]['updated_at'] = $this->nowIso();
        $this->saveUsers($users);

        return ['success' => true];
    }

    public function listUsers(): array
    {
        return $this->loadUsers();
    }

    public function getUserById(int $id): ?array
    {
        foreach ($this->loadUsers() as $user) {
            if ((int) $user['id'] === $id) {
                return $user;
            }
        }

        return null;
    }

    public function adminSaveUser(?int $id, array $data): array
    {
        $this->requireAdmin();

        $users = $this->loadUsers();
        $isCreate = empty($id);
        $userIndex = $isCreate ? null : $this->findUserIndexById($users, (int) $id);

        if (!$isCreate && $userIndex === null) {
            return ['success' => false, 'errors' => ['Usuario nao encontrado.']];
        }

        $username = trim((string) ($data['username'] ?? ''));
        $fullname = trim((string) ($data['fullname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = $this->normalizeRole((string) ($data['role'] ?? self::ROLE_MEMBER));

        $errors = [];
        if ($username === '') {
            $errors[] = 'Usuario e obrigatorio.';
        }
        if ($fullname === '') {
            $errors[] = 'Nome completo e obrigatorio.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalido.';
        }
        if ($isCreate && strlen($password) < 6) {
            $errors[] = 'Defina uma senha com pelo menos 6 caracteres.';
        }
        if (!$isCreate && $password !== '' && strlen($password) < 6) {
            $errors[] = 'A nova senha deve ter ao menos 6 caracteres.';
        }
        if ($this->findUserIndexByUsername($users, $username, $isCreate ? null : (int) $id) !== null) {
            $errors[] = 'Ja existe outro usuario com esse login.';
        }

        if (!$isCreate && $role !== self::ROLE_ADMIN && $this->isAdmin($users[$userIndex]) && $this->countAdmins($users, (int) $id) === 0) {
            $errors[] = 'Nao e possivel remover a permissao do ultimo admin.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($isCreate) {
            $savedUser = [
                'id' => $this->getNextUserId($users),
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $fullname,
                'email' => $email,
                'role' => $role,
                'provider' => 'local',
                'created_at' => $this->nowIso(),
                'updated_at' => $this->nowIso(),
            ];
            $users[] = $savedUser;
        } else {
            $users[$userIndex]['username'] = $username;
            $users[$userIndex]['fullname'] = $fullname;
            $users[$userIndex]['email'] = $email;
            $users[$userIndex]['role'] = $role;
            $users[$userIndex]['updated_at'] = $this->nowIso();

            if ($password !== '') {
                $users[$userIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $savedUser = $users[$userIndex];
        }

        $this->saveUsers($users);

        $currentUser = $this->getCurrentUser();
        if ($currentUser && (int) $currentUser['id'] === (int) $savedUser['id']) {
            $this->syncSessionUser($savedUser);
        }

        return ['success' => true, 'user' => $savedUser, 'created' => $isCreate];
    }

    public function deleteUser(int $id): array
    {
        $this->requireAdmin();

        $currentUser = $this->getCurrentUser();
        if ($currentUser && (int) $currentUser['id'] === $id) {
            return ['success' => false, 'errors' => ['Voce nao pode remover a propria conta pelo painel admin.']];
        }

        $users = $this->loadUsers();
        $userIndex = $this->findUserIndexById($users, $id);

        if ($userIndex === null) {
            return ['success' => false, 'errors' => ['Usuario nao encontrado.']];
        }

        if ($this->isAdmin($users[$userIndex]) && $this->countAdmins($users, $id) === 0) {
            return ['success' => false, 'errors' => ['Nao e possivel remover o ultimo admin.']];
        }

        $deletedUser = $users[$userIndex];
        unset($users[$userIndex]);
        $this->saveUsers(array_values($users));

        return ['success' => true, 'user' => $deletedUser];
    }

    private function ensureUsersFile(): void
    {
        $directory = dirname($this->usersFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!file_exists($this->usersFile)) {
            $this->saveUsers([$this->getDefaultAdminUser()]);
            return;
        }

        $contents = @file_get_contents($this->usersFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded) || empty($decoded)) {
            $this->saveUsers([$this->getDefaultAdminUser()]);
            return;
        }

        $needsRewrite = false;
        $normalizedUsers = [];

        foreach ($decoded as $user) {
            if (!is_array($user)) {
                $needsRewrite = true;
                continue;
            }

            $normalizedUser = $this->normalizeUser($user);
            $normalizedUsers[] = $normalizedUser;

            if ($normalizedUser != $user) {
                $needsRewrite = true;
            }
        }

        if ($needsRewrite) {
            $this->saveUsers($normalizedUsers);
        }
    }

    private function getDefaultAdminUser(): array
    {
        return [
            'id' => 1,
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'fullname' => 'Administrador',
            'email' => 'admin@example.com',
            'role' => self::ROLE_ADMIN,
            'provider' => 'local',
            'created_at' => $this->nowIso(),
            'updated_at' => $this->nowIso(),
        ];
    }

    private function loadUsers(): array
    {
        if (!file_exists($this->usersFile)) {
            return [];
        }

        $contents = @file_get_contents($this->usersFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return [];
        }

        $users = [];
        foreach ($decoded as $user) {
            if (is_array($user)) {
                $users[] = $this->normalizeUser($user);
            }
        }

        usort($users, static function (array $left, array $right): int {
            return (int) $left['id'] <=> (int) $right['id'];
        });

        return $users;
    }

    private function saveUsers(array $users): void
    {
        $normalizedUsers = [];

        foreach ($users as $user) {
            if (is_array($user)) {
                $normalizedUsers[] = $this->normalizeUser($user);
            }
        }

        file_put_contents(
            $this->usersFile,
            json_encode($normalizedUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizeUser(array $user): array
    {
        $id = isset($user['id']) ? (int) $user['id'] : 0;
        $username = trim((string) ($user['username'] ?? ''));
        $fullname = trim((string) ($user['fullname'] ?? $user['full_name'] ?? $username));
        $email = trim((string) ($user['email'] ?? ''));
        $role = $this->normalizeRole($user);
        $provider = trim((string) ($user['provider'] ?? 'local'));
        $passwordHash = (string) ($user['password_hash'] ?? $user['password'] ?? '');
        $createdAt = (string) ($user['created_at'] ?? $this->nowIso());
        $updatedAt = (string) ($user['updated_at'] ?? $createdAt);

        return [
            'id' => $id,
            'username' => $username,
            'password_hash' => $passwordHash,
            'fullname' => $fullname !== '' ? $fullname : $username,
            'email' => $email,
            'role' => $role,
            'provider' => $provider !== '' ? $provider : 'local',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function syncSessionUser(array $user): void
    {
        $user = $this->normalizeUser($user);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'role' => $user['role'],
            'role_label' => $this->getRoleLabel($user),
            'role_rank' => $this->getRoleRank($user),
            'provider' => $user['provider'],
            'created_at' => $user['created_at'],
            'logged_at' => time(),
        ];

        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['fullname'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['usuario_role'] = $user['role'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_email'] = $user['email'];
    }

    private function clearLegacySessionKeys(): void
    {
        unset(
            $_SESSION['user'],
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['usuario_email'],
            $_SESSION['usuario_role'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['provider_usado']
        );
    }

    private function normalizeRole($roleOrUser = null): string
    {
        $role = '';
        $userId = 0;
        $username = '';

        if (is_array($roleOrUser)) {
            $role = strtolower(trim((string) ($roleOrUser['role'] ?? '')));
            $userId = (int) ($roleOrUser['id'] ?? 0);
            $username = strtolower(trim((string) ($roleOrUser['username'] ?? '')));
        } else {
            $role = strtolower(trim((string) ($roleOrUser ?? '')));
        }

        if ($role === 'user') {
            $role = self::ROLE_MEMBER;
        }

        if (isset(self::ROLE_DEFINITIONS[$role])) {
            return $role;
        }

        if ($userId === 1 || $username === 'admin') {
            return self::ROLE_ADMIN;
        }

        return self::ROLE_MEMBER;
    }

    private function findUserIndexById(array $users, int $id): ?int
    {
        foreach ($users as $index => $user) {
            if ((int) ($user['id'] ?? 0) === $id) {
                return $index;
            }
        }

        return null;
    }

    private function findUserIndexByUsername(array $users, string $username, ?int $exceptId = null): ?int
    {
        foreach ($users as $index => $user) {
            if ($exceptId !== null && (int) ($user['id'] ?? 0) === $exceptId) {
                continue;
            }

            if (strcasecmp((string) ($user['username'] ?? ''), $username) === 0) {
                return $index;
            }
        }

        return null;
    }

    private function countAdmins(array $users, ?int $exceptId = null): int
    {
        $total = 0;

        foreach ($users as $user) {
            if ($exceptId !== null && (int) ($user['id'] ?? 0) === $exceptId) {
                continue;
            }

            if ($this->isAdmin($user)) {
                $total++;
            }
        }

        return $total;
    }

    private function getNextUserId(array $users): int
    {
        $highestId = 0;

        foreach ($users as $user) {
            $highestId = max($highestId, (int) ($user['id'] ?? 0));
        }

        return $highestId + 1;
    }

    private function generateUniqueUsername(array $users, string $displayName, string $email, string $provider): string
    {
        $baseSource = $email !== '' ? strstr($email, '@', true) : $displayName;
        $base = $this->slugifyUsername((string) $baseSource);

        if ($base === '') {
            $base = $this->slugifyUsername($provider);
        }
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 1;

        while ($this->findUserIndexByUsername($users, $candidate) !== null) {
            $suffix++;
            $candidate = $base . $suffix;
        }

        return $candidate;
    }

    private function slugifyUsername(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '', $value);

        return substr((string) $value, 0, 24);
    }

    private function nowIso(): string
    {
        return date('c');
    }
}
