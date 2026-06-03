<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

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

    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
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

    public function requireAtLeastRole(string $requiredRole, string $fallback = 'dashboard.php'): void
    {
        $this->requireAuth();

        if (!$this->hasAtLeastRole($requiredRole, $this->getCurrentUser())) {
            header('Location: ' . $fallback);
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
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            'username' => trim($username)
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Usuario nao encontrado.']
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'errors' => ['Senha incorreta.']
            ];
        }

        $this->syncSessionUser($user);

        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function loginSocialUser(string $displayName, string $email = '', string $provider = 'social'): array
    {
        $displayName = trim($displayName);
        $email = trim($email);
        $provider = trim($provider) !== '' ? trim($provider) : 'social';

        if ($displayName === '') {
            return [
                'success' => false,
                'errors' => ['Nao foi possivel identificar o usuario da rede social.']
            ];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $username = $this->generateUsername($displayName, $email);

            $insert = $this->db->prepare("
                INSERT INTO users (
                    username,
                    password_hash,
                    fullname,
                    email,
                    role,
                    provider,
                    created_at,
                    updated_at
                )
                VALUES (
                    :username,
                    :password_hash,
                    :fullname,
                    :email,
                    :role,
                    :provider,
                    NOW(),
                    NOW()
                )
            ");

            $insert->execute([
                'username' => $username,
                'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'fullname' => $displayName,
                'email' => $email,
                'role' => self::ROLE_MEMBER,
                'provider' => $provider
            ]);

            $userId = (int) $this->db->lastInsertId();

            $select = $this->db->prepare("
                SELECT * FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $select->execute([
                'id' => $userId
            ]);

            $user = $select->fetch(PDO::FETCH_ASSOC);
        } else {
            $update = $this->db->prepare("
                UPDATE users
                SET
                    fullname = :fullname,
                    provider = :provider,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $update->execute([
                'fullname' => $displayName,
                'provider' => $provider,
                'id' => $user['id']
            ]);

            $refresh = $this->db->prepare("
                SELECT * FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $refresh->execute([
                'id' => $user['id']
            ]);

            $user = $refresh->fetch(PDO::FETCH_ASSOC);
        }

        $this->syncSessionUser($user);

        return [
            'success' => true,
            'user' => $user
        ];
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

    public function register(
        string $username,
        string $password,
        string $fullname,
        string $email
    ): array {
        $username = trim($username);
        $fullname = trim($fullname);
        $email = trim($email);

        if (
            $username === '' ||
            $password === '' ||
            $fullname === '' ||
            $email === ''
        ) {
            return [
                'success' => false,
                'errors' => ['Preencha todos os campos.']
            ];
        }

        $check = $this->db->prepare("
            SELECT id
            FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $check->execute([
            'username' => $username
        ]);

        if ($check->fetch()) {
            return [
                'success' => false,
                'errors' => ['Usuario ja existe.']
            ];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (
                username,
                password_hash,
                fullname,
                email,
                role,
                provider,
                created_at,
                updated_at
            )
            VALUES (
                :username,
                :password_hash,
                :fullname,
                :email,
                :role,
                :provider,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            'username' => $username,
            'password_hash' => $hashedPassword,
            'fullname' => $fullname,
            'email' => $email,
            'role' => self::ROLE_MEMBER,
            'provider' => 'local'
        ]);

        return [
            'success' => true
        ];
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

        return self::ROLE_DEFINITIONS[$roleKey]['label']
            ?? self::ROLE_DEFINITIONS[self::ROLE_MEMBER]['label'];
    }

    public function getRoleRank($roleOrUser = null): int
    {
        $roleKey = $this->normalizeRole($roleOrUser);

        return (int) (
            self::ROLE_DEFINITIONS[$roleKey]['rank']
            ?? self::ROLE_DEFINITIONS[self::ROLE_MEMBER]['rank']
        );
    }

    public function hasAtLeastRole(string $requiredRole, ?array $user = null): bool
    {
        return $this->getRoleRank($user)
            >= $this->getRoleRank($requiredRole);
    }

    public function isAcademicResearcher(?array $user = null): bool
    {
        return $this->normalizeRole(
            $user ?: $this->getCurrentUser()
        ) === self::ROLE_ACADEMIC_RESEARCHER;
    }

    public function isAssociateResearcher(?array $user = null): bool
    {
        return $this->normalizeRole(
            $user ?: $this->getCurrentUser()
        ) === self::ROLE_ASSOCIATE_RESEARCHER;
    }

    public function isFullResearcher(?array $user = null): bool
    {
        return $this->normalizeRole(
            $user ?: $this->getCurrentUser()
        ) === self::ROLE_FULL_RESEARCHER;
    }

    public function isAdmin(?array $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->normalizeRole($user) === self::ROLE_ADMIN;
    }

    public function canAccessResearchWorkspace(?array $user = null): bool
    {
        return $this->hasAtLeastRole(
            self::ROLE_ACADEMIC_RESEARCHER,
            $user ?: $this->getCurrentUser()
        );
    }

    public function canManageOrientations(?array $user = null): bool
    {
        return $this->hasAtLeastRole(
            self::ROLE_ASSOCIATE_RESEARCHER,
            $user ?: $this->getCurrentUser()
        );
    }

    public function canCreateProjects(?array $user = null): bool
    {
        return $this->hasAtLeastRole(
            self::ROLE_FULL_RESEARCHER,
            $user ?: $this->getCurrentUser()
        );
    }

    public function listUsers(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM users
            ORDER BY id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findUserByUsernameOrEmail(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE username = :identifier
               OR email = :identifier
            LIMIT 1
        ");

        $stmt->execute([
            'identifier' => $identifier
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function resetPasswordForUserId(int $userId, string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (strlen($password) < 6) {
            $errors[] = 'A nova senha deve ter ao menos 6 caracteres.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'As senhas nao coincidem.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $userId
        ]);

        return [
            'success' => true
        ];
    }

    private function normalizeUser(array $user): array
    {
        return [
            'id' => (int) ($user['id'] ?? 0),
            'username' => (string) ($user['username'] ?? ''),
            'fullname' => (string) ($user['fullname'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => $this->normalizeRole($user),
            'provider' => (string) ($user['provider'] ?? 'local'),
            'created_at' => (string) ($user['created_at'] ?? ''),
            'updated_at' => (string) ($user['updated_at'] ?? '')
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

    private function generateUsername(string $displayName, string $email): string
    {
        $base = $email !== ''
            ? strstr($email, '@', true)
            : $displayName;

        $base = strtolower(trim($base));
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $counter++;
            $username = $base . $counter;
        }

        return substr($username, 0, 24);
    }

    private function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            'username' => $username
        ]);

        return (bool) $stmt->fetch();
    }

    public function updateProfile(array $data): array
    {
        $this->requireAuth();

        $currentUser = $this->getCurrentUser();

        $fullname = trim((string) ($data['fullname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? '');

        $errors = [];

        if ($fullname === '') {
            $errors[] = 'Nome completo e obrigatorio.';
        }

        if ($email === '') {
            $errors[] = 'Email e obrigatorio.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalido.';
        }

        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'A nova senha deve ter ao menos 6 caracteres.';
        }

        if ($password !== '' && $password !== $passwordConfirm) {
            $errors[] = 'As senhas nao coincidem.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        if ($password !== '') {
            $stmt = $this->db->prepare("
            UPDATE users
            SET
                fullname = :fullname,
                email = :email,
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
        ");

            $stmt->execute([
                'fullname' => $fullname,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $currentUser['id']
            ]);
        } else {
            $stmt = $this->db->prepare("
            UPDATE users
            SET
                fullname = :fullname,
                email = :email,
                updated_at = NOW()
            WHERE id = :id
        ");

            $stmt->execute([
                'fullname' => $fullname,
                'email' => $email,
                'id' => $currentUser['id']
            ]);
        }

        $refresh = $this->db->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

        $refresh->execute([
            'id' => $currentUser['id']
        ]);

        $updatedUser = $refresh->fetch(PDO::FETCH_ASSOC);

        $this->syncSessionUser($updatedUser);

        return [
            'success' => true
        ];
    }

    public function changePassword(
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): array {
        $this->requireAuth();

        $currentUser = $this->getCurrentUser();

        $stmt = $this->db->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

        $stmt->execute([
            'id' => $currentUser['id']
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Usuario nao encontrado.']
            ];
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

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Senha atual incorreta.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $update = $this->db->prepare("
        UPDATE users
        SET
            password_hash = :password_hash,
            updated_at = NOW()
        WHERE id = :id
    ");

        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $user['id']
        ]);

        return [
            'success' => true
        ];
    }

    public function adminSaveUser(?int $id, array $data): array
    {
        $this->requireAdmin();

        $isCreate = empty($id);

        $username = trim((string) ($data['username'] ?? ''));
        $fullname = trim((string) ($data['fullname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $role = $this->normalizeRole(
            (string) ($data['role'] ?? self::ROLE_MEMBER)
        );

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

        $checkUsername = $this->db->prepare("
        SELECT id
        FROM users
        WHERE username = :username
    ");

        $checkUsername->execute([
            'username' => $username
        ]);

        $existingUser = $checkUsername->fetch(PDO::FETCH_ASSOC);

        if (
            $existingUser &&
            ($isCreate || (int) $existingUser['id'] !== (int) $id)
        ) {
            $errors[] = 'Ja existe outro usuario com esse login.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        if ($isCreate) {
            $insert = $this->db->prepare("
            INSERT INTO users (
                username,
                password_hash,
                fullname,
                email,
                role,
                provider,
                created_at,
                updated_at
            )
            VALUES (
                :username,
                :password_hash,
                :fullname,
                :email,
                :role,
                :provider,
                NOW(),
                NOW()
            )
        ");

            $insert->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $fullname,
                'email' => $email,
                'role' => $role,
                'provider' => 'admin'
            ]);

            $savedId = (int) $this->db->lastInsertId();
        } else {
            if ($password !== '') {
                $update = $this->db->prepare("
                UPDATE users
                SET
                    username = :username,
                    fullname = :fullname,
                    email = :email,
                    role = :role,
                    password_hash = :password_hash,
                    updated_at = NOW()
                WHERE id = :id
            ");

                $update->execute([
                    'username' => $username,
                    'fullname' => $fullname,
                    'email' => $email,
                    'role' => $role,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $id
                ]);
            } else {
                $update = $this->db->prepare("
                UPDATE users
                SET
                    username = :username,
                    fullname = :fullname,
                    email = :email,
                    role = :role,
                    updated_at = NOW()
                WHERE id = :id
            ");

                $update->execute([
                    'username' => $username,
                    'fullname' => $fullname,
                    'email' => $email,
                    'role' => $role,
                    'id' => $id
                ]);
            }

            $savedId = (int) $id;
        }

        $select = $this->db->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

        $select->execute([
            'id' => $savedId
        ]);

        $savedUser = $select->fetch(PDO::FETCH_ASSOC);

        $currentUser = $this->getCurrentUser();

        if (
            $currentUser &&
            (int) $currentUser['id'] === (int) $savedUser['id']
        ) {
            $this->syncSessionUser($savedUser);
        }

        return [
            'success' => true,
            'user' => $savedUser,
            'created' => $isCreate
        ];
    }

    public function deleteUser(int $id): array
    {
        $this->requireAdmin();

        $currentUser = $this->getCurrentUser();

        if (
            $currentUser &&
            (int) $currentUser['id'] === $id
        ) {
            return [
                'success' => false,
                'errors' => [
                    'Voce nao pode remover a propria conta pelo painel admin.'
                ]
            ];
        }

        $stmt = $this->db->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

        $stmt->execute([
            'id' => $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Usuario nao encontrado.']
            ];
        }

        $delete = $this->db->prepare("
        DELETE FROM users
        WHERE id = :id
    ");

        $delete->execute([
            'id' => $id
        ]);

        return [
            'success' => true,
            'user' => $user
        ];
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
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function slugifyUsername(string $value): string
    {
        $value = strtolower(trim($value));

        $value = preg_replace(
            '/[^a-z0-9]+/',
            '',
            $value
        );

        return substr((string) $value, 0, 24);
    }

    private function isAllowedSelfRegistrationEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        if (
            $email === '' ||
            strpos($email, '@') === false
        ) {
            return false;
        }

        $domain = (string) substr(
            strrchr($email, '@'),
            1
        );

        if ($domain === '') {
            return false;
        }

        if (
            preg_match(
                '/^(?:[a-z0-9-]+\.)*gov\.br$/i',
                $domain
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^(?:[a-z0-9-]+\.)*if[a-z0-9-]+\.edu\.br$/i',
                $domain
            ) === 1
        ) {
            return true;
        }

        return false;
    }

    private function nowIso(): string
    {
        return date('Y-m-d H:i:s');
    }
}
