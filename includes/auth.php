<?php
/**
 * Autenticación del Agente DK — sesión PHP + tabla users (bcrypt).
 * El primer acceso (sin usuarios) dispara el alta del administrador.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/** Cantidad de usuarios registrados (0 = falta el setup inicial). */
function auth_user_count(): int {
    try { return (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn(); }
    catch (Throwable $e) { return 0; }
}

/**
 * Crea un usuario con contraseña hasheada.
 * @return array{ok:bool, id?:int, error?:string}
 */
function auth_create_user(string $username, string $password, string $name = ''): array {
    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z0-9_.@-]{3,60}$/', $username)) {
        return ['ok' => false, 'error' => 'Usuario inválido (3-60 caracteres: letras, números, . _ @ -).'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
    }
    try {
        $st = db()->prepare("INSERT INTO users (username, pass_hash, name) VALUES (?, ?, ?)");
        $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), trim($name) ?: $username]);
        return ['ok' => true, 'id' => (int)db()->lastInsertId()];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate') !== false || strpos($msg, '23000') !== false) {
            return ['ok' => false, 'error' => 'Ese usuario ya existe.'];
        }
        return ['ok' => false, 'error' => 'No se pudo crear el usuario.'];
    }
}

/**
 * Valida credenciales y abre sesión.
 * @return array{ok:bool, error?:string}
 */
function auth_login(string $username, string $password): array {
    $username = strtolower(trim($username));
    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'Completá usuario y contraseña.'];
    }
    $st = db()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $st->execute([$username]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['pass_hash'])) {
        return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
    }
    if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) {
        db()->prepare("UPDATE users SET pass_hash = ? WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);            // evita fijación de sesión
    $_SESSION['uid'] = (int)$u['id'];
    db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$u['id']]);
    return ['ok' => true];
}

/** Cierra la sesión actual. */
function auth_logout(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Devuelve el usuario logueado (id, username, name, last_login) o null. */
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $uid = (int)($_SESSION['uid'] ?? 0);
    if (!$uid) { $cache = null; return null; }
    try {
        $st = db()->prepare("SELECT id, username, name, last_login FROM users WHERE id = ? LIMIT 1");
        $st->execute([$uid]);
        $cache = $st->fetch() ?: null;
    } catch (Throwable $e) { $cache = null; }
    return $cache;
}

function is_logged_in(): bool { return current_user() !== null; }

/** Guard para páginas (redirige al login si no hay sesión). */
function require_login(): void {
    if (!is_logged_in()) { header('Location: index.php?page=login'); exit; }
}

/** Guard para endpoints AJAX (responde 401 JSON si no hay sesión). */
function require_auth_api(): void {
    if (!is_logged_in()) { json_out(['ok' => false, 'error' => 'No autenticado.'], 401); }
}

/**
 * Cambia la contraseña del usuario logueado (verifica la actual).
 * @return array{ok:bool, error?:string}
 */
function auth_change_password(string $current, string $new): array {
    $u = current_user();
    if (!$u) return ['ok' => false, 'error' => 'No autenticado.'];
    if (strlen($new) < 8) return ['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
    $st = db()->prepare("SELECT pass_hash FROM users WHERE id = ?");
    $st->execute([$u['id']]);
    $row = $st->fetch();
    if (!$row || !password_verify($current, $row['pass_hash'])) {
        return ['ok' => false, 'error' => 'La contraseña actual no es correcta.'];
    }
    db()->prepare("UPDATE users SET pass_hash = ? WHERE id = ?")
        ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
    return ['ok' => true];
}

/** Lista de usuarios (sin el hash). */
function auth_list_users(): array {
    try {
        return db()->query("SELECT id, username, name, last_login, created_at FROM users ORDER BY id")->fetchAll();
    } catch (Throwable $e) { return []; }
}

/**
 * Elimina un usuario (no a uno mismo, y nunca el último).
 * @return array{ok:bool, error?:string}
 */
function auth_delete_user(int $id): array {
    $u = current_user();
    if (!$u) return ['ok' => false, 'error' => 'No autenticado.'];
    if ($id === (int)$u['id']) return ['ok' => false, 'error' => 'No podés eliminar tu propia cuenta.'];
    if ((int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn() <= 1) {
        return ['ok' => false, 'error' => 'Debe quedar al menos un usuario.'];
    }
    db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    return ['ok' => true];
}
