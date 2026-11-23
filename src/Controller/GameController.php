<?php

require_once __DIR__ . '/../Model/Game.php';
require_once __DIR__ . '/../Model/User.php';

/**
 * Contrôleur du jeu collectif Motus.
 *
 * Toutes les méthodes reçoivent l'identifiant de l'utilisateur authentifié
 * (extrait du JWT par le front controller) et vérifient systématiquement les
 * droits avant toute action.
 */
class GameController
{
    private Game $game;
    private User $user;

    public function __construct()
    {
        $this->game = new Game();
        $this->user = new User();
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    /**
     * Lit un corps JSON (ou les données de formulaire en repli).
     */
    private function input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode((string)$raw, true);

        if (!is_array($data)) {
            $data = $_POST;
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Prépare une partie pour la réponse JSON.
     * Le mot cible n'est révélé qu'une fois la partie terminée.
     */
    private function present(array $game, ?array $attempts = null): array
    {
        $isActive = $game['status'] === 'active';

        $attempts = $attempts ?? $this->game->findAttempts((int)$game['id']);

        $data = [
            'id' => (int)$game['id'],
            'player1_id' => (int)$game['player1_id'],
            'player2_id' => (int)$game['player2_id'],
            'player1_pseudo' => $game['player1_pseudo'] ?? null,
            'player2_pseudo' => $game['player2_pseudo'] ?? null,
            'player1_avatar' => $game['player1_avatar'] ?? null,
            'player2_avatar' => $game['player2_avatar'] ?? null,
            'status' => $game['status'],
            'current_turn_user_id' => (int)$game['current_turn_user_id'],
            'winner_id' => $game['winner_id'] !== null ? (int)$game['winner_id'] : null,
            'attempts_count' => (int)$game['attempts_count'],
            'max_attempts' => Game::MAX_ATTEMPTS,
            'word_length' => Game::WORD_LENGTH,
            'turn_seconds' => Game::TURN_SECONDS,
            'seconds_left' => $isActive
                ? max(0, Game::TURN_SECONDS - (time() - (int)strtotime((string)($game['turn_started_at'] ?? ''))))
                : 0,
            'created_at' => $game['created_at'],
            'finished_at' => $game['finished_at'],
            'attempts' => $attempts,
        ];

        if (!$isActive) {
            $data['target_word'] = $game['target_word'];
        }

        return $data;
    }

    /**
     * POST /api/game/start  { "player2_id": 3 }
     */
    public function start(int $userId): void
    {
        $data = $this->input();
        $player2Id = (int)($data['player2_id'] ?? 0);

        if ($player2Id <= 0) {
            sendResponseCustom('player2_id is required', null, 'Error', 400);
            return;
        }

        if ($player2Id === $userId) {
            sendResponseCustom('You cannot play against yourself', null, 'Error', 400);
            return;
        }

        if (!$this->user->findById($player2Id)) {
            sendResponseCustom('Player not found', null, 'Error', 404);
            return;
        }

        if ($this->game->findActiveForUser($userId)) {
            sendResponseCustom('You are already in a game', null, 'Error', 409);
            return;
        }

        $gameId = $this->game->create($userId, $player2Id);

        if ($gameId === null) {
            sendResponseCustom('Unable to start the game (a player is already in a game)', null, 'Error', 409);
            return;
        }

        $game = $this->game->findById($gameId);

        sendResponseCustom('Game created', $this->present($game, []), 'Success', 201);
    }

    /**
     * GET /api/game/current
     * Retourne la partie en cours de l'utilisateur, ou null.
     */
    public function current(int $userId): void
    {
        $game = $this->game->findActiveForUser($userId);

        if ($game === null) {
            sendResponseCustom('No active game', null);
            return;
        }

        $this->game->enforceTurnDeadline((int)$game['id']);
        $game = $this->game->findById((int)$game['id']);

        sendResponseCustom('Active game found', $this->present($game));
    }

    /**
     * GET /api/game/{id}
     */
    public function show(int $gameId, int $userId): void
    {
        $this->game->enforceTurnDeadline($gameId);
        $game = $this->game->findById($gameId);

        if ($game === null) {
            sendResponseCustom('Game not found', null, 'Error', 404);
            return;
        }

        if ((int)$game['player1_id'] !== $userId && (int)$game['player2_id'] !== $userId) {
            sendResponseCustom('You are not a player of this game', null, 'Error', 403);
            return;
        }

        sendResponseCustom('Game retrieved', $this->present($game));
    }

    /**
     * POST /api/game/{id}/attempt  { "word": "maison" }
     */
    public function attempt(int $gameId, int $userId): void
    {
        $data = $this->input();
        $word = mb_strtolower(trim((string)($data['word'] ?? '')));

        if (!preg_match('/^[a-z]{6}$/', $word)) {
            sendResponseCustom('Word must contain exactly 6 letters (A-Z)', null, 'Error', 400);
            return;
        }

        $outcome = $this->game->playAttempt($gameId, $userId, $word);

        switch ($outcome['error']) {
            case null:
                sendResponseCustom('Attempt registered', [
                    'game' => $this->present($outcome['game']),
                    'attempt' => $outcome['attempt'],
                ]);
                break;
            case 'not_found':
                sendResponseCustom('Game not found', null, 'Error', 404);
                break;
            case 'forbidden':
                sendResponseCustom('You are not a player of this game', null, 'Error', 403);
                break;
            case 'not_active':
                sendResponseCustom('This game is not active anymore', null, 'Error', 409);
                break;
            case 'not_your_turn':
                sendResponseCustom('It is not your turn', null, 'Error', 409);
                break;
            case 'max_attempts':
                sendResponseCustom('Maximum number of attempts reached', null, 'Error', 409);
                break;
            case 'turn_skipped':
                sendResponseCustom(
                    'Time is up: the turn was given to the other player',
                    $this->present($outcome['game']),
                    'Error',
                    409
                );
                break;
            default:
                sendResponse500();
                break;
        }
    }

    /**
     * POST /api/game/{id}/cancel
     */
    public function cancel(int $gameId, int $userId): void
    {
        $game = $this->game->findById($gameId);

        if ($game === null) {
            sendResponseCustom('Game not found', null, 'Error', 404);
            return;
        }

        if ((int)$game['player1_id'] !== $userId && (int)$game['player2_id'] !== $userId) {
            sendResponseCustom('You are not a player of this game', null, 'Error', 403);
            return;
        }

        if ($game['status'] !== 'active') {
            sendResponseCustom('This game is not active anymore', null, 'Error', 409);
            return;
        }

        if (!$this->game->cancel($gameId, $userId)) {
            sendResponseCustom('Unable to cancel the game', null, 'Error', 409);
            return;
        }

        sendResponseCustom('Game cancelled', $this->present($this->game->findById($gameId)));
    }
}
