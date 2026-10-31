<?php
/** Regras para pedir credencial: 3.º ano ou superior, propinas em dia e cadeiras do 1.º ao 3.º ano aprovadas */
function elegibilidade(array $u): array {
    $motivos = []; $pend = [];
    if ((int) $u['ano'] < 3) $motivos[] = 'Só estudantes do 3.º ano ou superior podem pedir estágio (está no ' . (int) $u['ano'] . '.º ano).';
    if ($u['financeiro'] !== 'Regularizada') $motivos[] = 'Tem propinas em dívida. Regularize a situação na secretaria.';
    if (EXIGIR_DISCIPLINAS) {
        $pend = consulta('SELECT d.ano, d.nome, n.nota FROM disciplinas d LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ?
            WHERE d.curso_id = ? AND d.ano <= 3 AND (n.nota IS NULL OR n.nota < 10) ORDER BY d.ano, d.nome', [$u['id'], $u['curso_id']]);
        if ($pend) $motivos[] = count($pend) . ' cadeira(s) do 1.º ao 3.º ano por aprovar.';
    }
    return ['elegivel' => !$motivos, 'motivos' => $motivos, 'pendentes' => $pend];
}
