<?php
class UserManager
{
    private $users_file;

    public function __construct()
    {
        $this->users_file = __DIR__ . '/../data/users.json';
        $this->ensureFileExists();
    }

    private function ensureFileExists()
    {
        // Criar pasta data se não existir
        if (!file_exists(dirname($this->users_file))) {
            mkdir(dirname($this->users_file), 0777, true);
        }

        // Criar arquivo users.json se não existir
        if (!file_exists($this->users_file)) {
            file_put_contents($this->users_file, json_encode([]));
        }
    }

    public function register($username, $password)
    {
        $users = $this->getUsers();

        // Verificar se usuário já existe
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return false; // Usuário já existe
            }
        }

        // Adicionar novo usuário
        $new_user = [
            'id' => uniqid(),
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $new_user;

        // Salvar no arquivo
        if (file_put_contents($this->users_file, json_encode($users, JSON_PRETTY_PRINT))) {
            return $new_user;
        }

        return false;
    }

    public function login($username, $password)
    {
        $users = $this->getUsers();

        foreach ($users as $user) {
            if ($user['username'] === $username) {
                if (password_verify($password, $user['password'])) {
                    return $user; // Login bem-sucedido
                }
            }
        }

        return false; // Usuário não encontrado ou senha incorreta
    }

    public function userExists($username)
    {
        $users = $this->getUsers();

        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return true;
            }
        }

        return false;
    }

    private function getUsers()
    {
        if (!file_exists($this->users_file)) {
            return [];
        }

        $content = file_get_contents($this->users_file);
        $users = json_decode($content, true);

        return is_array($users) ? $users : [];
    }

    // Adicione estas funções ao UserManager.php (dentro da classe)

    public function updateProfile($user_id, $data)
    {
        $users = $this->getUsers();

        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                // Atualizar dados do usuário
                $user['email'] = $data['email'] ?? '';
                $user['bio'] = $data['bio'] ?? '';
                $user['full_name'] = $data['full_name'] ?? '';
                $user['updated_at'] = date('Y-m-d H:i:s');

                // Salvar no arquivo
                if (file_put_contents($this->users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                    return $user;
                }
            }
        }

        return false;
    }

    public function getUserById($user_id)
    {
        $users = $this->getUsers();

        foreach ($users as $user) {
            if ($user['id'] === $user_id) {
                return $user;
            }
        }

        return false;
    }

    public function changePassword($user_id, $current_password, $new_password)
    {
        $users = $this->getUsers();

        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                // Verificar senha atual
                if (password_verify($current_password, $user['password'])) {
                    // Atualizar senha
                    $user['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    $user['updated_at'] = date('Y-m-d H:i:s');

                    // Salvar no arquivo
                    if (file_put_contents($this->users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
