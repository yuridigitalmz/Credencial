<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if (!empty($_SERVER['HTTPS'])) header('Strict-Transport-Security: max-age=31536000');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");

if (session_status() === PHP_SESSION_NONE) {
    session_name('CREDSESS');
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '+02:00'"); // hora de Moçambique, igual à do PHP
        } catch (PDOException) {
            http_response_code(500);
            exit('Não foi possível ligar à base de dados. Verifique o ficheiro config.php.');
        }
    }
    return $pdo;
}

function e(mixed $v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function url(string $p): string { return BASE_URL . $p; }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }
function data_pt(?string $d): string { return $d ? date('d/m/Y', strtotime($d)) : '—'; }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_exigir(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Pedido inválido ou expirado. Volte atrás e tente de novo.');
    }
}

function flash(?string $msg = null, string $tipo = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = [$tipo, $msg]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function utilizador(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (isset($_SESSION['papel'], $_SESSION['uid'])) {
        $sql = $_SESSION['papel'] === 'admin'
            ? 'SELECT a.id, a.nome, a.usuario, a.departamento_id, a.trocar_senha, d.nome AS departamento FROM administradores a LEFT JOIN departamentos d ON d.id = a.departamento_id WHERE a.id = ?'
            : 'SELECT e.*, c.nome AS curso, c.faculdade, d.id AS dep_id, d.nome AS departamento, d.chefe_nome, d.assinatura FROM estudantes e JOIN cursos c ON c.id = e.curso_id JOIN departamentos d ON d.id = c.departamento_id WHERE e.id = ?';
        $st = db()->prepare($sql); $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function exigir(string $papel): array {
    if (isset($_SESSION['papel']) && time() - ($_SESSION['ult'] ?? 0) > INACTIVIDADE) {
        session_unset(); session_destroy(); redirect('/index.php?expirada=1');
    }
    $_SESSION['ult'] = time();
    $u = utilizador();
    if (!$u || $_SESSION['papel'] !== $papel) redirect('/index.php');
    if (!empty($u['trocar_senha']) && basename($_SERVER['SCRIPT_NAME']) !== 'senha.php') {
        flash('Por segurança, defina uma nova senha antes de continuar.', 'info');
        redirect('/conta/senha.php');
    }
    return $u;
}

function consulta(string $sql, array $par = []): array { $st = db()->prepare($sql); $st->execute($par); return $st->fetchAll(); }

function site_url(string $p = ''): string {
    $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = SITE_URL !== '' ? rtrim(SITE_URL, '/') : ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $host);
    return $base . BASE_URL . $p;
}

/** Regista uma acção no histórico de actividade (nunca interrompe o sistema) */
function registar(string $acao, string $detalhe = '', ?string $papel = null, ?int $uid = null, ?string $nome = null): void {
    try {
        $u = ($papel === null) ? utilizador() : null;
        db()->prepare('INSERT INTO registo (papel, utilizador_id, nome, acao, detalhe, ip) VALUES (?,?,?,?,?,?)')
            ->execute([$papel ?? ($_SESSION['papel'] ?? null), $uid ?? ($_SESSION['uid'] ?? null), $nome ?? ($u['nome'] ?? null),
                       $acao, mb_substr($detalhe, 0, 250), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable) {}
}
