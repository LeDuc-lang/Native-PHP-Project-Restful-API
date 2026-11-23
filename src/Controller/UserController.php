<?php

require_once __DIR__ . '/../Model/User.php';

class UserController
{
    private User $user;
    private JwtService $jwtService;

    public function __construct()
    {
        $this->user = new User();
        $this->jwtService = new JwtService();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Retire les informations sensibles (mot de passe, jeton) des données renvoyées au client.
     */
    private function publicUser(?array $user): ?array
    {
        if ($user === null) {
            return null;
        }

        unset($user['password'], $user['token']);

        return $user;
    }

    public function list(): void
    {
        try {
            $result = $this->user->findAll();

            $users = $result === null ? null : array_map([$this, 'publicUser'], $result);

            sendResponseCustom('Successfully get all user data from database', $users);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    /**
     * Liste des joueurs disponibles pour une partie (connectés récemment).
     */
    public function available($authenticatedUserId): void
    {
        try {
            $users = $this->user->findAvailable((int)$authenticatedUserId);

            sendResponseCustom('Successfully get available users', $users ?? []);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function get($id): void
    {
        try {
            $result = $this->publicUser($this->user->findById((int)$id));

            $httpResponseCode = !$result ? 404 : 200;
            $status = !$result ? 'Error' : 'Success';
            $message = !$result ? 'User not found' : 'Successfully retrieved user data';

            sendResponseCustom($message, $result, $status, $httpResponseCode);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function create(): void
    {
        try {
            $requiredFields = [
                'first_name',
                'last_name',
                'pseudo',
                'birth_date',
                'gender',
                'email',
                'password',
            ];

            $inputData = [];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                $value = trim((string)($_POST[$field] ?? ''));
                if ($value === '') {
                    $missingFields[] = $field;
                }
                $inputData[$field] = $value;
            }

            if ($missingFields) {
                sendResponseCustom(
                    'Missing required fields: ' . implode(', ', $missingFields),
                    null,
                    'Error',
                    400
                );
                return;
            }

            $avatar = trim((string)($_POST['avatar'] ?? ''));
            $inputData['avatar'] = $avatar !== '' ? $avatar : null;

            if (!filter_var($inputData['email'], FILTER_VALIDATE_EMAIL)) {
                sendResponseCustom('Invalid email address', null, 'Error', 400);
                return;
            }

            if (!in_array($inputData['gender'], ['m', 'f', 'o'], true)) {
                sendResponseCustom('Gender must be one of: m, f, o', null, 'Error', 400);
                return;
            }

            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $inputData['birth_date'], $dateParts)
                || !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
                sendResponseCustom('Birth date must be a valid date using the YYYY-MM-DD format', null, 'Error', 400);
                return;
            }

            if (strlen($inputData['password']) < 6) {
                sendResponseCustom('Password must contain at least 6 characters', null, 'Error', 400);
                return;
            }

            if ($this->user->findByPseudoOrEmail($inputData['pseudo'], $inputData['email'])) {
                sendResponseCustom('Pseudo or email is already used', null, 'Error', 409);
                return;
            }

            $userId = $this->user->create(
                $inputData['first_name'],
                $inputData['last_name'],
                $inputData['pseudo'],
                $inputData['birth_date'],
                $inputData['gender'],
                $inputData['avatar'],
                $inputData['email'],
                password_hash($inputData['password'], PASSWORD_DEFAULT)
            );

            if (!$userId) {
                sendResponseCustom('New user creation failed', null, 'Error', 400);
                return;
            }

            $result = $this->publicUser($this->user->findById($userId));

            sendResponseCustom('Successfully create new user', $result, 'Success', 201);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function update($id, $authenticatedUserId): void
    {
        try {
            $id = (int)$id;

            if ($id !== (int)$authenticatedUserId) {
                sendResponseCustom('You are not allowed to update this account', null, 'Error', 403);
                return;
            }

            if (!$this->user->findById($id)) {
                sendResponseCustom('User not found', null, 'Error', 404);
                return;
            }

            $inputData = json_decode(file_get_contents('php://input'), true);
            if (!is_array($inputData)) {
                $inputData = $_POST;
            }

            if (!$inputData) {
                sendResponseCustom('Input data is empty', null, 'Error', 400);
                return;
            }

            $acceptedFields = ['pseudo', 'gender', 'avatar'];

            $toBeUpdated = [];
            foreach ($acceptedFields as $acceptedField) {
                if (!array_key_exists($acceptedField, $inputData)) {
                    continue;
                }

                $value = trim((string)$inputData[$acceptedField]);

                if ($acceptedField === 'avatar' && $value === '') {
                    $toBeUpdated[$acceptedField] = null;
                    continue;
                }

                if ($value !== '') {
                    $toBeUpdated[$acceptedField] = $value;
                }
            }

            if (!$toBeUpdated) {
                sendResponseCustom('Nothing to update', null, 'Error', 400);
                return;
            }

            if (isset($toBeUpdated['gender']) && !in_array($toBeUpdated['gender'], ['m', 'f', 'o'], true)) {
                sendResponseCustom('Gender must be one of: m, f, o', null, 'Error', 400);
                return;
            }

            if (isset($toBeUpdated['pseudo'])) {
                $usersWithSamePseudo = $this->user->findByPseudoOrEmail($toBeUpdated['pseudo'], '') ?: [];
                foreach ($usersWithSamePseudo as $existingUser) {
                    if ((int)$existingUser['id'] !== $id) {
                        sendResponseCustom('Pseudo is already used', null, 'Error', 409);
                        return;
                    }
                }
            }

            $this->user->setFields($id, $toBeUpdated);
            $result = $this->publicUser($this->user->findById($id));

            sendResponseCustom('Successfully update data', $result);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function replace($id): void
    {
        // TODO Nothing to do
    }

    public function delete($id, $authenticatedUserId): void
    {
        try {
            $id = (int)$id;

            if ($id !== (int)$authenticatedUserId) {
                sendResponseCustom('You are not allowed to delete this account', null, 'Error', 403);
                return;
            }

            if (!$this->user->findById($id)) {
                sendResponseCustom('User not found', null, 'Error', 404);
                return;
            }

            $this->user->deleteById($id);

            // Le compte supprimé ne doit plus pouvoir utiliser son Refresh Token.
            setcookie('refresh_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => false, // true in production (HTTPS)
                'samesite' => 'Lax',
            ]);

            sendResponseCustom('Successfully delete user');
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function authenticate(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            sendResponseCustom('Email and password are required', null, 'Error', 400);
            return;
        }

        try {
            $user = $this->user->findByEmail($email);

            $badResult = !$user || !password_verify($password, $user['password']);

            if ($badResult) {
                sendResponseCustom('Invalid credentials', null, 'Error', 401);
                return;
            }

            $userId = (int)$user['id'];
            $accessToken = $this->jwtService->createToken([
                'user_id' => $userId,
                'email' => $email,
            ]);

            $refreshToken = bin2hex(random_bytes(64));
            $expiresIn = (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800); // or 7 days (7*24*60*60)

            $this->user->storeRefreshToken($userId, $refreshToken, $expiresIn);
            $this->user->touchLastSeen($userId);

            setcookie('refresh_token', $refreshToken, [
                'expires' => time() + $expiresIn,
                'path' => '/',
                'httponly' => true,
                'secure' => false, // true in production (HTTPS)
                'samesite' => 'Lax',
            ]);

            sendResponseCustom('Successfully authenticate user', [
                'accessToken' => $accessToken,
                'user' => $this->publicUser($user),
            ]);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function deauthenticate($id): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->user->revokeRefreshToken((int)$id, $refreshToken);
        }

        $this->user->markOffline((int)$id);

        // Le cookie doit être supprimé avant l'envoi de la réponse.
        setcookie('refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => false, // true in production (HTTPS)
            'samesite' => 'Lax',
        ]);

        sendResponseCustom('Successfully deauthenticate user');
    }

    public function reauthenticate(): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if (!$refreshToken) {
            sendResponseCustom('No refresh token found', null, 'Error', 401);
            return;
        }

        $user = $this->user->findByToken($refreshToken);
        if (!$user) {
            sendResponseCustom('Invalid, revoked or expired refresh token', null, 'Error', 401);
            return;
        }

        try {
            $payload = ['user_id' => (int)$user['id'], 'email' => $user['email']];
            $accessToken = $this->jwtService->createToken($payload);
            $this->user->touchLastSeen((int)$user['id']);
        } catch (Exception $e) {
            logWithDate('Unknown error', $e->getMessage());
            sendResponse500();
            return;
        }

        sendResponseCustom('Successfully reauthenticate user', [
            'accessToken' => $accessToken,
            'user' => $this->publicUser($user),
        ]);
    }
}
