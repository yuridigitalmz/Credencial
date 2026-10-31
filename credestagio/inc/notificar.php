<?php
/** Envia e-mail em texto simples e guarda sempre uma cópia (visível em Registo > E-mails, útil sem SMTP) */
function enviar_email(string $para, string $assunto, string $corpo): void {
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) return;
    $host = preg_replace('/[^A-Za-z0-9.\-]/', '', explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]) ?: 'localhost';
    $ok = function_exists('mail') && @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo,
        "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: " . APP_NOME . " <naoresponder@$host>");
    db()->prepare('INSERT INTO emails_log (destinatario, assunto, corpo, estado) VALUES (?,?,?,?)')
        ->execute([$para, $assunto, $corpo, $ok ? 'Enviado' : 'Simulado']);
}

/** Notificação no painel do estudante (+ e-mail, se tiver) */
function notificar(int $estudanteId, string $mensagem, ?string $email = null): void {
    db()->prepare('INSERT INTO notificacoes (estudante_id, mensagem) VALUES (?,?)')->execute([$estudanteId, $mensagem]);
    if ($email) enviar_email($email, 'Actualização do seu pedido — ' . APP_NOME, $mensagem . "\n\nEntre em " . site_url('/index.php') . ' para ver os detalhes.');
}
