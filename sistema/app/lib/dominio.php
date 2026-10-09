<?php
// Vocabulário do negócio: tipos e status, com rótulo e cor (selo) para a interface.

const TIPOS_VIAGEM = [
  'city_tour' => 'City tour',
  'excursao' => 'Excursão',
  'receptivo' => 'Receptivo / transfer',
  'pedagogico' => 'Turismo pedagógico',
  'trem' => 'Passeio de trem',
  'evento' => 'Evento',
  'corporativo' => 'Corporativo / incentivo',
  'outro' => 'Outro',
];

// [rótulo, selo]
const STATUS_VIAGEM = [
  'rascunho' => ['Rascunho', 'neutro'],
  'publicada' => ['Publicada', 'sucesso'],
  'concluida' => ['Concluída', 'info'],
  'cancelada' => ['Cancelada', 'erro'],
];

const STATUS_ESCALA = [
  'convidado' => ['Convite pendente', 'aviso'],
  'confirmado' => ['Confirmada', 'sucesso'],
  'em_campo' => ['Em campo', 'sucesso'],
  'realizada' => ['Aguardando relatório', 'aviso'],
  'aguardando_nf' => ['Aguardando nota fiscal', 'aviso'],
  'nf_em_conferencia' => ['NF em conferência', 'info'],
  'a_pagar' => ['Pagamento programado', 'info'],
  'paga' => ['Paga', 'sucesso'],
  'recusado' => ['Recusada', 'neutro'],
  'cancelado' => ['Cancelada', 'neutro'],
  'falta' => ['Falta', 'erro'],
];

// Escalas que contam como "trabalho assumido" (exclui convite, recusa e cancelamento).
const ESCALAS_ASSUMIDAS = ['confirmado', 'em_campo', 'realizada', 'aguardando_nf', 'nf_em_conferencia', 'a_pagar', 'paga'];

const STATUS_CADASTRO = [
  'pre_cadastro' => ['Pré-cadastro (aguardando o guia)', 'aviso'],
  'rascunho' => ['Cadastro incompleto', 'aviso'],
  'em_analise' => ['Cadastro em análise', 'aviso'],
  'pendente' => ['Cadastro com pendência', 'erro'],
  'aprovado' => ['Cadastro aprovado', 'sucesso'],
  'reprovado' => ['Cadastro não aprovado', 'erro'],
  'inativo' => ['Cadastro inativo', 'neutro'],
  'bloqueado' => ['Cadastro bloqueado', 'erro'],
];

const ESPECIALIDADES = [
  'City tour', 'Turismo pedagógico', 'Excursões rodoviárias', 'Receptivo e transfer', 'Passeios de trem',
  'Turismo religioso', 'Ecoturismo e aventura', 'Turismo cultural e museus', 'Gastronomia', 'Eventos e corporativo',
];

function selo(array $mapa, ?string $chave): string {
  [$rotulo, $cor] = $mapa[$chave] ?? [(string) $chave, 'neutro'];
  return '<span class="selo selo-' . e($cor) . '">' . e($rotulo) . '</span>';
}

function data_extenso(string $data, bool $comDiaSemana = true): string {
  static $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
  static $dias = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
  $t = strtotime($data);
  $texto = date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1];
  return $comDiaSemana ? $dias[(int) date('w', $t)] . ', ' . $texto : $texto;
}

function dia_semana_curto(string $data): string {
  static $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
  return $dias[(int) date('w', strtotime($data))];
}

function mes_curto(string $data): string {
  static $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
  return $meses[(int) date('n', strtotime($data)) - 1];
}

function periodo_viagem(array $v): string {
  if ($v['data_inicio'] === $v['data_fim']) {
    return formatar_data($v['data_inicio']);
  }
  return formatar_data($v['data_inicio']) . ' a ' . formatar_data($v['data_fim']);
}

function saudacao(): string {
  $h = (int) date('G');
  return $h < 12 ? 'Bom dia' : ($h < 18 ? 'Boa tarde' : 'Boa noite');
}

function link_whatsapp(?string $telefone): string {
  $d = normalizar_celular($telefone);
  if ($d === '') {
    return '';
  }
  return 'https://wa.me/' . (strlen($d) <= 11 ? '55' . $d : $d);
}

function gerar_slug(string $texto): string {
  $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
  $t = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $t), '-'));
  return $t !== '' ? substr($t, 0, 60) : 'funcao';
}

function data_valida(string $data): bool {
  $d = DateTime::createFromFormat('Y-m-d', $data);
  return $d && $d->format('Y-m-d') === $data;
}

function hora_valida(string $hora): bool {
  return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora);
}

/** Converte "250,00" ou "1.250,50" em float. */
function ler_moeda(string $valor): float {
  $v = str_replace(['R$', ' ', '.'], '', $valor);
  return round((float) str_replace(',', '.', $v), 2);
}

// Quem pode ser escalado: guia aprovado ou guia pré-cadastrado pela equipe (antes e depois de completar o
// cadastro, enquanto a análise não termina). Cadastro público ainda não aprovado não entra.
const SQL_GUIA_ESCALAVEL = "(g.status = 'aprovado' OR (g.pre_cadastrado_por IS NOT NULL AND g.status IN ('pre_cadastro', 'em_analise')))";

function guia_escalavel(array $g): bool {
  return $g['status'] === 'aprovado'
    || (!empty($g['pre_cadastrado_por']) && in_array($g['status'], ['pre_cadastro', 'em_analise'], true));
}
