<?php

/**
 * Modèle de la partie collective Motus.
 *
 * Une partie oppose deux joueurs (player1 = initiateur, player2 = invité).
 * Le mot cible est tiré au hasard dans une liste interne : aucune validation
 * par dictionnaire n'est demandée par le sujet.
 */
class Game
{
    public const MAX_ATTEMPTS = 8;
    public const WORD_LENGTH = 6;
    public const TURN_SECONDS = 30;

    /**
     * Mots cibles de 6 lettres (sans accent, sans espace).
     */
    private const TARGET_WORDS = [
        'maison', 'jardin', 'soleil', 'orange', 'banane', 'cerise', 'citron', 'fraise',
        'tomate', 'salade', 'nature', 'oiseau', 'cheval', 'voyage', 'cahier', 'crayon',
        'chaise', 'papier', 'balcon', 'rideau', 'plaque', 'marche', 'argent', 'billet',
        'ticket', 'caisse', 'client', 'compte', 'banque', 'credit', 'budget', 'emploi',
        'metier', 'bureau', 'patron', 'equipe', 'projet', 'loisir', 'course', 'joueur',
        'ballon', 'tennis', 'basket', 'cinema', 'acteur', 'public', 'entree', 'sortie',
        'camion', 'bateau', 'vallee', 'eglise', 'classe', 'eleve', 'maitre', 'phrase',
        'lettre', 'nombre', 'calcul', 'pensee', 'esprit', 'minute', 'saison', 'cadeau',
        'paques', 'cousin', 'voisin', 'chambre', 'douche', 'garage', 'grenier', 'pierre',
        'brique', 'cuivre', 'bronze', 'carton', 'coton', 'flamme', 'cendre', 'aviron',
    ];

    private ?PDO $conn = null;

    public function __construct()
    {
        try {
            $this->conn = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->conn;
    }

    /**
     * Tire un mot cible au hasard.
     */
    public function randomTargetWord(): string
    {
        return self::TARGET_WORDS[array_rand(self::TARGET_WORDS)];
    }

    /**
     * Retourne la partie en cours (statut "active") d'un utilisateur, ou null.
     */
    public function findActiveForUser(int $userId): ?array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT id FROM game ';
            $query .= "WHERE status = 'active' AND (player1_id = :id OR player2_id = :id) ";
            $query .= 'ORDER BY id DESC LIMIT 1';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $game = $stmt->fetch();

            return $game ? $this->findById((int)$game['id']) : null;
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    /**
     * Retourne une partie avec les pseudos des deux joueurs.
     */
    public function findById(int $gameId): ?array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT g.*, ';
            $query .= 'p1.pseudo AS player1_pseudo, ';
            $query .= 'p2.pseudo AS player2_pseudo ';
            $query .= 'FROM game g ';
            $query .= 'JOIN user p1 ON p1.id = g.player1_id ';
            $query .= 'JOIN user p2 ON p2.id = g.player2_id ';
            $query .= 'WHERE g.id = :id';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->execute();

            $game = $stmt->fetch();

            return $game ?: null;
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une partie si aucun des deux joueurs n'est déjà en jeu.
     *
     * Le verrou FOR UPDATE évite que deux créations simultanées placent un
     * même joueur dans deux parties.
     */
    public function create(int $player1Id, int $player2Id): ?int
    {
        try {
            throwDbNullConnection($this->conn);

            $this->conn->beginTransaction();

            $query = 'SELECT id FROM game ';
            $query .= "WHERE status = 'active' AND (player1_id IN (:p1, :p2) OR player2_id IN (:p1b, :p2b)) ";
            $query .= 'FOR UPDATE';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':p1', $player1Id, PDO::PARAM_INT);
            $stmt->bindParam(':p2', $player2Id, PDO::PARAM_INT);
            $stmt->bindParam(':p1b', $player1Id, PDO::PARAM_INT);
            $stmt->bindParam(':p2b', $player2Id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetch()) {
                $this->conn->rollBack();
                return null;
            }

            $targetWord = $this->randomTargetWord();

            $query = 'INSERT INTO game ';
            $query .= '(player1_id, player2_id, target_word, status, current_turn_user_id, turn_started_at, attempts_count, created_at) ';
            $query .= "VALUES (:p1, :p2, :target, 'active', :turn, NOW(), 0, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':p1', $player1Id, PDO::PARAM_INT);
            $stmt->bindParam(':p2', $player2Id, PDO::PARAM_INT);
            $stmt->bindParam(':target', $targetWord);
            $stmt->bindParam(':turn', $player1Id, PDO::PARAM_INT);
            $stmt->execute();

            $gameId = (int)$this->conn->lastInsertId();

            $this->conn->commit();

            return $gameId;
        } catch (PDOException|Exception $e) {
            if ($this->conn && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    /**
     * Vrai si le joueur courant a dépassé les 30 secondes imparties.
     */
    private function deadlinePassed(array $game): bool
    {
        $startedAt = strtotime((string)($game['turn_started_at'] ?? ''));

        return $startedAt !== false && (time() - $startedAt) > self::TURN_SECONDS;
    }

    private function nextPlayer(array $game): int
    {
        return (int)$game['current_turn_user_id'] === (int)$game['player1_id']
            ? (int)$game['player2_id']
            : (int)$game['player1_id'];
    }

    /**
     * Fait passer la main automatiquement quand le délai de 30 secondes est écoulé.
     * Appelée à chaque lecture de partie (polling) et avant chaque tentative.
     */
    public function enforceTurnDeadline(int $gameId): bool
    {
        try {
            throwDbNullConnection($this->conn);

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare('SELECT * FROM game WHERE id = :id FOR UPDATE');
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->execute();
            $game = $stmt->fetch();

            if (!$game || $game['status'] !== 'active' || !$this->deadlinePassed($game)) {
                $this->conn->rollBack();
                return false;
            }

            $nextTurn = $this->nextPlayer($game);
            $stmt = $this->conn->prepare(
                'UPDATE game SET current_turn_user_id = :turn, turn_started_at = NOW(), updated_at = NOW() WHERE id = :id'
            );
            $stmt->bindParam(':turn', $nextTurn, PDO::PARAM_INT);
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();

            return true;
        } catch (PDOException|Exception $e) {
            if ($this->conn && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            logWithDate('Query failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne les tentatives d'une partie, dans l'ordre.
     */
    public function findAttempts(int $gameId): array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT ga.id, ga.user_id, ga.word, ga.result, ga.attempt_number, ga.created_at, u.pseudo ';
            $query .= 'FROM game_attempt ga JOIN user u ON u.id = ga.user_id ';
            $query .= 'WHERE ga.game_id = :game_id ORDER BY ga.attempt_number ASC';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
            $stmt->execute();

            $attempts = $stmt->fetchAll();

            foreach ($attempts as &$attempt) {
                $decoded = json_decode((string)$attempt['result'], true);
                $attempt['result'] = is_array($decoded) ? $decoded : [];
                $attempt['attempt_number'] = (int)$attempt['attempt_number'];
                $attempt['user_id'] = (int)$attempt['user_id'];
            }
            unset($attempt);

            return $attempts;
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return [];
        }
    }

    /**
     * Calcule les couleurs Motus d'une proposition.
     *
     * - red    : lettre correcte à la bonne place
     * - yellow : lettre présente mais mal placée
     * - blue   : lettre absente
     *
     * Deux passes pour gérer les lettres en double.
     */
    public function computeResult(string $word, string $target): array
    {
        $wordLetters = mb_str_split(mb_strtolower($word));
        $targetLetters = mb_str_split(mb_strtolower($target));

        $result = array_fill(0, count($wordLetters), 'blue');
        $remaining = $targetLetters;

        // 1re passe : lettres bien placées.
        foreach ($wordLetters as $index => $letter) {
            if (isset($targetLetters[$index]) && $letter === $targetLetters[$index]) {
                $result[$index] = 'red';
                $remaining[$index] = null;
            }
        }

        // 2e passe : lettres présentes mais mal placées.
        foreach ($wordLetters as $index => $letter) {
            if ($result[$index] === 'red') {
                continue;
            }

            $foundIndex = array_search($letter, $remaining, true);
            if ($foundIndex !== false) {
                $result[$index] = 'yellow';
                $remaining[$foundIndex] = null;
            }
        }

        return $result;
    }

    /**
     * Joue une tentative de façon atomique.
     *
     * Retourne ['error' => code|null, 'game' => array|null, 'attempt' => array|null].
     * Codes d'erreur : not_found, forbidden, not_active, not_your_turn, max_attempts.
     */
    public function playAttempt(int $gameId, int $userId, string $word): array
    {
        try {
            throwDbNullConnection($this->conn);

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare('SELECT * FROM game WHERE id = :id FOR UPDATE');
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->execute();
            $game = $stmt->fetch();

            if (!$game) {
                $this->conn->rollBack();
                return ['error' => 'not_found', 'game' => null, 'attempt' => null];
            }

            if ((int)$game['player1_id'] !== $userId && (int)$game['player2_id'] !== $userId) {
                $this->conn->rollBack();
                return ['error' => 'forbidden', 'game' => null, 'attempt' => null];
            }

            if ($game['status'] !== 'active') {
                $this->conn->rollBack();
                return ['error' => 'not_active', 'game' => $this->findById($gameId), 'attempt' => null];
            }

            // Délai de 30 secondes dépassé : la main passe automatiquement à l'autre joueur.
            if ($this->deadlinePassed($game)) {
                $nextTurn = $this->nextPlayer($game);
                $stmt = $this->conn->prepare(
                    'UPDATE game SET current_turn_user_id = :turn, turn_started_at = NOW(), updated_at = NOW() WHERE id = :id'
                );
                $stmt->bindParam(':turn', $nextTurn, PDO::PARAM_INT);
                $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
                $stmt->execute();

                $this->conn->commit();

                return ['error' => 'turn_skipped', 'game' => $this->findById($gameId), 'attempt' => null];
            }

            if ((int)$game['current_turn_user_id'] !== $userId) {
                $this->conn->rollBack();
                return ['error' => 'not_your_turn', 'game' => $this->findById($gameId), 'attempt' => null];
            }

            $attemptNumber = (int)$game['attempts_count'] + 1;
            if ($attemptNumber > self::MAX_ATTEMPTS) {
                $this->conn->rollBack();
                return ['error' => 'max_attempts', 'game' => $this->findById($gameId), 'attempt' => null];
            }

            $result = $this->computeResult($word, (string)$game['target_word']);
            $encodedResult = json_encode($result, JSON_UNESCAPED_UNICODE);

            $query = 'INSERT INTO game_attempt (game_id, user_id, word, result, attempt_number, created_at) ';
            $query .= 'VALUES (:game_id, :user_id, :word, :result, :attempt_number, NOW())';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':word', $word);
            $stmt->bindParam(':result', $encodedResult);
            $stmt->bindParam(':attempt_number', $attemptNumber, PDO::PARAM_INT);
            $stmt->execute();

            $newStatus = 'active';
            $winnerId = null;
            $nextTurn = (int)$game['current_turn_user_id'] === (int)$game['player1_id']
                ? (int)$game['player2_id']
                : (int)$game['player1_id'];

            if ($word === (string)$game['target_word']) {
                $newStatus = 'won';
                $winnerId = $userId;
            } elseif ($attemptNumber >= self::MAX_ATTEMPTS) {
                $newStatus = 'draw';
            }

            $query = 'UPDATE game SET attempts_count = :attempts_count, status = :status, ';
            $query .= 'winner_id = :winner_id, current_turn_user_id = :turn, turn_started_at = NOW(), ';
            $query .= 'finished_at = :finished_at, updated_at = NOW() WHERE id = :id';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':attempts_count', $attemptNumber, PDO::PARAM_INT);
            $stmt->bindParam(':status', $newStatus);
            $stmt->bindParam(':winner_id', $winnerId, $winnerId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':turn', $nextTurn, PDO::PARAM_INT);
            $finishedAt = $newStatus === 'active' ? null : date('Y-m-d H:i:s');
            $stmt->bindParam(':finished_at', $finishedAt, $finishedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();

            return [
                'error' => null,
                'game' => $this->findById($gameId),
                'attempt' => [
                    'user_id' => $userId,
                    'word' => $word,
                    'result' => $result,
                    'attempt_number' => $attemptNumber,
                ],
            ];
        } catch (PDOException|Exception $e) {
            if ($this->conn && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            logWithDate('Query failed', $e->getMessage());
            return ['error' => 'server', 'game' => null, 'attempt' => null];
        }
    }

    /**
     * Annule une partie en cours (bouton « Quitter la partie »).
     */
    public function cancel(int $gameId, int $userId): bool
    {
        try {
            throwDbNullConnection($this->conn);

            $query = "UPDATE game SET status = 'cancelled', finished_at = NOW(), updated_at = NOW() ";
            $query .= "WHERE id = :id AND status = 'active' AND (player1_id = :uid OR player2_id = :uid2)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $gameId, PDO::PARAM_INT);
            $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':uid2', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return false;
        }
    }
}
