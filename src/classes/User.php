<?php
declare(strict_types=1);

class User
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername(trim($username));
        if (!$user) {
            return null;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }

    public function findById(int $userId): ?array
    {
        return $this->db->queryOne(
            'SELECT user_id, username, role, created_at FROM USERS WHERE user_id = :user_id',
            [':user_id' => $userId]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->queryOne(
            'SELECT user_id, username, password_hash, role, created_at FROM USERS WHERE username = :username',
            [':username' => $username]
        );
    }

    public function create(string $username, string $plainPassword, string $role = 'attendee'): int
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        return $this->db->insert(
            'INSERT INTO USERS (username, password_hash, role) VALUES (:username, :password_hash, :role)',
            [
                ':username'      => trim($username),
                ':password_hash' => $hash,
                ':role'          => $role,
            ]
        );
    }

    public function updatePassword(int $userId, string $newPlainPassword): bool
    {
        $hash = password_hash($newPlainPassword, PASSWORD_BCRYPT);
        $affected = $this->db->execute(
            'UPDATE USERS SET password_hash = :password_hash WHERE user_id = :user_id',
            [':password_hash' => $hash, ':user_id' => $userId]
        );
        return $affected > 0;
    }

    public function delete(int $userId): bool
    {
        $affected = $this->db->execute(
            'DELETE FROM USERS WHERE user_id = :user_id',
            [':user_id' => $userId]
        );
        return $affected > 0;
    }
}
