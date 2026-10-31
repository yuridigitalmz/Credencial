<?php
function nav_admin(array $u): array {
    $n = ['p' => ['/admin/painel.php', 'Estatísticas'], 'r' => ['/admin/pedidos.php', 'Pedidos']];
    if (!$u['departamento_id']) {
        $n['e'] = ['/admin/estudantes.php', 'Estudantes'];
        $n['n'] = ['/admin/notas.php', 'Notas'];
        $n['d'] = ['/admin/disciplinas.php', 'Disciplinas'];
        $n['x'] = ['/admin/estrutura.php', 'Estrutura'];
        $n['l'] = ['/admin/ligacoes.php', 'Horário e Jornal'];
    }
    $n['a'] = ['/admin/assinaturas.php', 'Assinaturas'];
    if (!$u['departamento_id']) $n['g'] = ['/admin/registo.php', 'Registo'];
    $n['s'] = ['/conta/senha.php', 'Alterar senha'];
    return $n;
}
/** Navegação do estudante: Horário e Jornal abrem os sistemas externos (ou "em breve" se ainda não houver ligação) */
function nav_aluno(): array {
    $novas = (int) (consulta('SELECT COUNT(*) n FROM notificacoes WHERE estudante_id = ? AND lida = 0', [$_SESSION['uid'] ?? 0])[0]['n'] ?? 0);
    $n = ['p' => ['/aluno/painel.php', 'O meu painel' . ($novas ? " ($novas)" : '')], 'c' => ['/aluno/curricular.php', 'Plano curricular']];
    foreach (consulta('SELECT chave, url FROM ligacoes ORDER BY chave') as $l) {
        $n[$l['chave']] = [$l['url'] ?: null, $l['chave'] === 'horario' ? 'Horário' : 'Jornal'];
    }
    $n['s'] = ['/conta/senha.php', 'Alterar senha'];
    return $n;
}
function escopo(array $u): ?int { return $u['departamento_id'] ? (int) $u['departamento_id'] : null; }
function so_secretaria(array $u): void { if ($u['departamento_id']) redirect('/admin/painel.php'); }
