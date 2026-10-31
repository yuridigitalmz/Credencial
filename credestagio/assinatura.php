<?php
require_once __DIR__ . '/inc/bootstrap.php';
if (!utilizador()) { http_response_code(403); exit; }
$st = db()->prepare('SELECT assinatura FROM departamentos WHERE id = ?'); $st->execute([(int) ($_GET['id'] ?? 0)]);
$a = $st->fetchColumn();
$ficheiro = $a ? __DIR__ . '/uploads/assinaturas/' . basename($a) : null;
if (!$ficheiro || !is_file($ficheiro)) { http_response_code(404); exit; }
header('Content-Type: ' . (str_ends_with($ficheiro, '.png') ? 'image/png' : 'image/jpeg'));
header('Cache-Control: private, max-age=300');
readfile($ficheiro);
