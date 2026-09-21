<?php
session_start();
require __DIR__ . '/nps-auth.php';

if (isset($_GET['sair'])) {
  $_SESSION = [];
  session_destroy();
  header('Location: /pesquisa-nps');
  exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $senha = $_POST['senha'] ?? '';
  if (nps_verify($usuario, $senha, $NPS_USERS)) {
    session_regenerate_id(true);
    $_SESSION['nps_user'] = $usuario;
    header('Location: /pesquisa-nps');
    exit;
  } else {
    $erro = 'Usuário ou senha inválidos.';
  }
}

$autenticado = isset($_SESSION['nps_user']);

if (!$autenticado) {
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Acesso restrito — 645 Turismo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { margin: 0; background: #000; color: #F2F5F3; font-family: Montserrat, system-ui, sans-serif; -webkit-font-smoothing: antialiased; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
  * { box-sizing: border-box; }
  .card { width: 100%; max-width: 380px; border: 1px solid rgba(242,245,243,0.14); background: #05100C; padding: clamp(28px,4vw,40px); }
  .eyebrow { margin: 0 0 14px; font-size: 11px; font-weight: 600; letter-spacing: 0.28em; text-transform: uppercase; color: #53D9B2; }
  h1 { margin: 0 0 26px; font-family: Newsreader, Georgia, serif; font-weight: 400; font-size: 28px; color: #fff; }
  label { display: block; margin-bottom: 16px; }
  label span { display: block; margin-bottom: 8px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: rgba(242,245,243,0.5); }
  input { width: 100%; padding: 13px 14px; border: 1px solid rgba(242,245,243,0.2); background: #000; color: #F2F5F3; outline: none; font-size: 15px; font-family: inherit; }
  input:focus { border-color: #53D9B2; }
  button { width: 100%; margin-top: 8px; padding: 15px; border: none; border-radius: 999px; background: #53D9B2; color: #000; font-size: 12.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; min-height: 44px; font-family: inherit; }
  button:hover { background: #FADA28; }
  .erro { margin: 0 0 18px; padding: 12px 14px; border: 1px solid rgba(232,103,79,0.4); background: rgba(232,103,79,0.08); color: #E8674F; font-size: 13px; }
</style>
</head>
<body>
  <form class="card" method="post" autocomplete="off">
    <p class="eyebrow">Painel interno</p>
    <h1>Acesso restrito</h1>
    <?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <label><span>Usuário</span><input type="text" name="usuario" autocomplete="off" required autofocus></label>
    <label><span>Senha</span><input type="password" name="senha" autocomplete="off" required></label>
    <button type="submit">Entrar</button>
  </form>
</body>
</html>
<?php
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<script src="./support.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
<x-dc>
<helmet>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Pesquisa de satisfação — Trem da República</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,300;6..72,400&family=Montserrat:wght@400;500;600;700;900&display=swap" rel="stylesheet">
<style>
  body { margin: 0; background: #000; color: #F2F5F3; font-family: Montserrat, system-ui, sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
  * { box-sizing: border-box; }
  a { color: #53D9B2; text-decoration: none; }
  a:hover { color: #FADA28; }
  img { display: block; max-width: 100%; }
  input, textarea, select, button { font-family: inherit; font-size: 15px; }
  ::selection { background: #53D9B2; color: #000; }
</style>
</helmet>

<div style="background:#000;min-height:100vh">

  <header style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;padding:18px clamp(18px,4vw,40px);border-bottom:1px solid rgba(242,245,243,0.12)">
    <img src="assets/logo-branco.png" alt="645 Turismo" style="height:28px;width:auto" />
    <div style="display:flex;align-items:center;gap:20px">
      <span style="font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:rgba(242,245,243,0.45)">Painel interno · não listado no site</span>
      <a href="/pesquisa-nps?sair=1" style="font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;border-bottom:1px solid rgba(83,217,178,0.5);padding-bottom:3px">Sair</a>
    </div>
  </header>

  <section data-screen-label="Cabeçalho" style="max-width:1320px;margin:0 auto;padding:clamp(36px,5vw,60px) clamp(18px,4vw,40px) 0">
    <p style="margin:0 0 18px;font-size:11.5px;font-weight:600;letter-spacing:0.3em;text-transform:uppercase;color:#53D9B2">Pesquisa de satisfação</p>
    <h1 style="margin:0;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(32px,5.5vw,64px);line-height:1.03;letter-spacing:-0.02em;color:#fff;max-width:20ch;text-wrap:balance">Trem da República — NPS por data</h1>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:30px">
      <button type="button" onClick="{{ abrirPainel }}" style="{{ abaBtnStyle.painel }}">Painel</button>
      <button type="button" onClick="{{ abrirMetodologia }}" style="{{ abaBtnStyle.metodologia }}">Metodologia e perguntas</button>
      <button type="button" onClick="{{ recarregar }}" disabled="{{ atualizando }}" title="{{ atualizadoTitle }}" style="{{ abaBtnStyle.atualizar }}">{{ atualizarLabel }}</button>
    </div>
  </section>

  <sc-if value="{{ abaPainel }}" hint-placeholder-val="{{ true }}">

  <sc-if value="{{ mostrarAviso }}" hint-placeholder-val="{{ false }}">
    <section style="max-width:1320px;margin:0 auto;padding:28px clamp(18px,4vw,40px) 0">
      <div style="border:1px solid rgba(250,218,40,0.4);background:rgba(250,218,40,0.07);padding:24px;display:grid;gap:14px">
        <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#FADA28">Não consegui ler a planilha automaticamente</p>
        <p style="margin:0;max-width:76ch;font-size:14.5px;line-height:1.7;color:rgba(242,245,243,0.72)">Para a leitura automática, a planilha precisa estar compartilhada como "qualquer pessoa com o link pode ver". Como alternativa, cole abaixo o CSV da aba (Arquivo → Fazer download → CSV) e o painel calcula tudo na hora.</p>
        <textarea onChange="{{ colarCsv }}" rows="4" placeholder="Cole aqui o CSV da aba de respostas" style="width:100%;border:1px solid rgba(242,245,243,0.2);background:#000;color:#F2F5F3;padding:14px;line-height:1.5;resize:vertical"></textarea>
      </div>
    </section>
  </sc-if>

  <section data-screen-label="Filtros" style="max-width:1320px;margin:0 auto;padding:clamp(30px,4vw,44px) clamp(18px,4vw,40px) 0">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:1px;background:rgba(242,245,243,0.14);border:1px solid rgba(242,245,243,0.14)">
      <label style="display:block;background:#05100C;padding:18px 20px">
        <span style="display:block;margin-bottom:9px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#53D9B2">Segmento</span>
        <select onChange="{{ mudarSegmento }}" value="{{ segmentoAtual }}" style="width:100%;border:none;background:#05100C;color:#F2F5F3;outline:none;padding:0">
          <sc-for list="{{ opcoesSegmento }}" as="o" hint-placeholder-count="4">
            <option value="{{ o.valor }}">{{ o.rotulo }}</option>
          </sc-for>
        </select>
      </label>
      <label style="display:block;background:#000;padding:18px 20px">
        <span style="display:block;margin-bottom:9px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Ano</span>
        <select onChange="{{ mudarAno }}" value="{{ anoAtual }}" style="width:100%;border:none;background:#000;color:#F2F5F3;outline:none;padding:0">
          <sc-for list="{{ opcoesAno }}" as="o" hint-placeholder-count="2">
            <option value="{{ o.valor }}">{{ o.rotulo }}</option>
          </sc-for>
        </select>
      </label>
      <label style="display:block;background:#000;padding:18px 20px">
        <span style="display:block;margin-bottom:9px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Mês</span>
        <select onChange="{{ mudarMes }}" value="{{ mesAtual }}" style="width:100%;border:none;background:#000;color:#F2F5F3;outline:none;padding:0">
          <sc-for list="{{ opcoesMes }}" as="o" hint-placeholder-count="2">
            <option value="{{ o.valor }}">{{ o.rotulo }}</option>
          </sc-for>
        </select>
      </label>
      <label style="display:block;background:#000;padding:18px 20px">
        <span style="display:block;margin-bottom:9px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Data específica</span>
        <select onChange="{{ mudarData }}" value="{{ dataAtual }}" style="width:100%;border:none;background:#000;color:#F2F5F3;outline:none;padding:0">
          <sc-for list="{{ opcoesData }}" as="o" hint-placeholder-count="2">
            <option value="{{ o.valor }}">{{ o.rotulo }}</option>
          </sc-for>
        </select>
      </label>
      <label style="display:block;background:#000;padding:18px 20px">
        <span style="display:block;margin-bottom:9px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Tipo de NPS</span>
        <select onChange="{{ mudarPerfil }}" value="{{ perfilAtual }}" style="width:100%;border:none;background:#000;color:#F2F5F3;outline:none;padding:0">
          <option value="todos">Todos os perfis</option>
          <option value="promotor">Promotores (9-10)</option>
          <option value="neutro">Neutros (7-8)</option>
          <option value="detrator">Detratores (0-6)</option>
        </select>
      </label>
      <div style="background:#000;padding:18px 20px;display:flex;align-items:flex-end">
        <button type="button" onClick="{{ limparFiltros }}" style="padding:12px 22px;border-radius:999px;border:1px solid rgba(242,245,243,0.3);background:transparent;color:#F2F5F3;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;min-height:42px" style-hover="border-color:#53D9B2;color:#53D9B2">Limpar filtros</button>
      </div>
    </div>
    <sc-if value="{{ avisoSegmento }}" hint-placeholder-val="{{ false }}">
      <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#FADA28">{{ avisoSegmento }}</p>
    </sc-if>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:22px">
      <button type="button" onClick="{{ exportarXlsx }}" style="display:inline-flex;align-items:center;gap:9px;padding:13px 22px;border-radius:999px;border:1px solid rgba(83,217,178,0.5);background:rgba(83,217,178,0.08);color:#53D9B2;font-size:11.5px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;min-height:44px" style-hover="background:#53D9B2;color:#000">Exportar XLSX</button>
      <span style="font-size:12px;color:rgba(242,245,243,0.42);align-self:center">{{ contadorExport }}</span>
    </div>
  </section>

  <section data-screen-label="Indicadores" style="max-width:1320px;margin:0 auto;padding:clamp(30px,4vw,44px) clamp(18px,4vw,40px) 0">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:1px;background:rgba(242,245,243,0.14);border:1px solid rgba(242,245,243,0.14)">
      <div style="background:#05100C;padding:clamp(26px,3vw,36px) clamp(22px,2.4vw,30px)">
        <p style="margin:0 0 12px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">NPS</p>
        <p style="margin:0;font-family:Newsreader,Georgia,serif;font-size:clamp(52px,8vw,88px);line-height:0.9;color:#53D9B2">{{ nps }}</p>
        <p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:rgba(242,245,243,0.55)">{{ npsClassificacao }}</p>
      </div>
      <div style="background:#000;padding:clamp(26px,3vw,36px) clamp(22px,2.4vw,30px)">
        <p style="margin:0 0 12px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Respostas</p>
        <p style="margin:0;font-family:Newsreader,Georgia,serif;font-size:clamp(40px,6vw,64px);line-height:0.95;color:#fff">{{ totalRespostas }}</p>
        <p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:rgba(242,245,243,0.55)">{{ periodoTexto }}</p>
      </div>
      <div style="background:#000;padding:clamp(26px,3vw,36px) clamp(22px,2.4vw,30px);display:grid;gap:14px;align-content:start">
        <p style="margin:0;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Distribuição</p>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
          <span style="font-size:14px;color:rgba(242,245,243,0.75)">Promotores</span>
          <span style="font-size:18px;font-weight:700;color:#53D9B2">{{ pctPromotores }}</span>
        </div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
          <span style="font-size:14px;color:rgba(242,245,243,0.75)">Neutros</span>
          <span style="font-size:18px;font-weight:700;color:#FADA28">{{ pctNeutros }}</span>
        </div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
          <span style="font-size:14px;color:rgba(242,245,243,0.75)">Detratores</span>
          <span style="font-size:18px;font-weight:700;color:#E8674F">{{ pctDetratores }}</span>
        </div>
      </div>
      <div style="background:#000;padding:clamp(26px,3vw,36px) clamp(22px,2.4vw,30px)">
        <p style="margin:0 0 12px;font-size:10.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Nota média</p>
        <p style="margin:0;font-family:Newsreader,Georgia,serif;font-size:clamp(40px,6vw,64px);line-height:0.95;color:#fff">{{ notaMedia }}</p>
        <p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:rgba(242,245,243,0.55)">Escala de 0 a 10</p>
      </div>
    </div>
  </section>

  <section data-screen-label="NPS por data" style="max-width:1320px;margin:0 auto;padding:clamp(40px,5vw,64px) clamp(18px,4vw,40px) 0">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:18px;padding-bottom:22px;border-bottom:1px solid rgba(242,245,243,0.14)">
      <h2 style="margin:0;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,40px);line-height:1.1;color:#fff">NPS por data</h2>
      <span style="font-size:12px;color:rgba(242,245,243,0.45)">Escala de -100 a 100</span>
    </div>
    <div style="display:grid;gap:1px;background:rgba(242,245,243,0.14);border:1px solid rgba(242,245,243,0.14);border-top:none">
      <sc-for list="{{ porData }}" as="d" hint-placeholder-count="5">
        <div style="background:#000;padding:15px clamp(14px,2vw,24px);display:grid;grid-template-columns:minmax(min(70px,22vw),130px) minmax(30px,1fr) minmax(30px,64px) minmax(34px,62px);gap:clamp(8px,2vw,14px);align-items:center">
          <span style="font-size:13px;font-weight:600;color:rgba(242,245,243,0.82)">{{ d.rotulo }}</span>
          <span style="display:block;position:relative;height:10px;background:rgba(242,245,243,0.08)">
            <span style="{{ d.barraStyle }}"></span>
          </span>
          <span style="font-size:15px;font-weight:700;text-align:right;color:#53D9B2">{{ d.nps }}</span>
          <span style="font-size:12px;text-align:right;color:rgba(242,245,243,0.42)">{{ d.qtdTexto }}</span>
        </div>
      </sc-for>
    </div>
    <p style="margin:16px 0 0;font-size:13px;color:rgba(242,245,243,0.45)">{{ notaDatas }}</p>
  </section>

  <section data-screen-label="Avaliações" style="max-width:1320px;margin:0 auto;padding:clamp(40px,5vw,64px) clamp(18px,4vw,40px) clamp(60px,8vw,100px)">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:18px;padding-bottom:26px;border-bottom:1px solid rgba(242,245,243,0.14)">
      <h2 style="margin:0;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,40px);line-height:1.1;color:#fff">Avaliações dos passageiros</h2>
      <span style="font-size:12px;color:rgba(242,245,243,0.45)">{{ contadorAvaliacoes }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:1px;background:rgba(242,245,243,0.14)">
      <sc-for list="{{ avaliacoes }}" as="a" hint-placeholder-count="3">
        <article style="background:#000;padding:clamp(24px,3vw,32px) clamp(22px,2.4vw,28px);display:grid;gap:14px;align-content:start">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:14px">
            <span style="font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:rgba(242,245,243,0.45)">{{ a.data }}</span>
            <span style="{{ a.notaStyle }}">{{ a.nota }}</span>
          </div>
          <p style="margin:0;font-family:Newsreader,Georgia,serif;font-size:19px;line-height:1.5;color:rgba(242,245,243,0.9)">{{ a.texto }}</p>
          <span style="{{ a.perfilStyle }}">{{ a.perfil }}</span>
        </article>
      </sc-for>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:26px">
      <sc-if value="{{ temMais }}" hint-placeholder-val="{{ true }}">
        <button type="button" onClick="{{ verMais }}" style="padding:15px 28px;border-radius:999px;background:#53D9B2;border:none;color:#000;font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;min-height:44px" style-hover="background:#FADA28">Ver mais 3 avaliações</button>
      </sc-if>
      <sc-if value="{{ podeRecolher }}" hint-placeholder-val="{{ false }}">
        <button type="button" onClick="{{ recolher }}" style="padding:15px 28px;border-radius:999px;border:1px solid rgba(242,245,243,0.3);background:transparent;color:#F2F5F3;font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;min-height:44px" style-hover="border-color:#53D9B2;color:#53D9B2">Mostrar apenas 3</button>
      </sc-if>
    </div>
  </section>
  </sc-if>

  <sc-if value="{{ abaMetodologia }}" hint-placeholder-val="{{ false }}">
    <section data-screen-label="Metodologia" style="max-width:1000px;margin:0 auto;padding:clamp(36px,5vw,60px) clamp(18px,4vw,40px) clamp(60px,8vw,100px);display:grid;gap:clamp(40px,5vw,60px)">

      <div>
        <p style="margin:0 0 16px;font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#53D9B2">Como calculamos</p>
        <h2 style="margin:0 0 18px;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,36px);line-height:1.15;color:#fff">Método de cálculo do NPS</h2>
        <p style="margin:0 0 14px;max-width:74ch;font-size:15px;line-height:1.75;color:rgba(242,245,243,0.72)">Cada resposta de 0 a 10 é classificada em <b style="color:#53D9B2">promotor</b> (nota 9 ou 10), <b style="color:#FADA28">neutro</b> (nota 7 ou 8) ou <b style="color:#E8674F">detrator</b> (nota 0 a 6). O NPS é a diferença entre o percentual de promotores e o percentual de detratores, sempre calculado sobre o total de respostas do período filtrado (não sobre respostas filtradas por perfil):</p>
        <div style="margin:20px 0;padding:20px 24px;border:1px solid rgba(83,217,178,0.3);background:rgba(83,217,178,0.06);font-family:ui-monospace,Menlo,monospace;font-size:14px;color:#53D9B2">NPS = % Promotores − % Detratores</div>
        <p style="margin:0;max-width:74ch;font-size:15px;line-height:1.75;color:rgba(242,245,243,0.72)">O resultado varia de -100 a 100 e é classificado em quatro zonas: <b>75 a 100</b> — zona de excelência; <b>50 a 74</b> — zona de qualidade; <b>0 a 49</b> — zona de aperfeiçoamento; <b>-100 a -1</b> — zona crítica. A "Nota média" exibida no painel é a média aritmética simples das notas de 0 a 10 do período e perfil filtrados (métrica auxiliar, não faz parte do cálculo do NPS).</p>
      </div>

      <div>
        <p style="margin:0 0 16px;font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#53D9B2">Filtros</p>
        <h2 style="margin:0 0 18px;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,36px);line-height:1.15;color:#fff">O que cada filtro faz</h2>
        <div style="display:grid;gap:1px;background:rgba(242,245,243,0.14);border:1px solid rgba(242,245,243,0.14)">
          <div style="background:#000;padding:20px 22px">
            <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#fff">Segmento</p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:rgba(242,245,243,0.65)">Escolhe qual pergunta de recomendação (0 a 10) alimenta o NPS e os indicadores: Geral, Guiamento e Transporte, Restaurante ou Trem da República. Cada opção usa a coluna correspondente identificada na planilha — veja abaixo qual coluna foi associada a cada uma.</p>
          </div>
          <div style="background:#000;padding:20px 22px">
            <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#fff">Ano, Mês e Data específica</p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:rgba(242,245,243,0.65)">Restringem o período das respostas consideradas, com base na data de cada resposta. São independentes do segmento escolhido.</p>
          </div>
          <div style="background:#000;padding:20px 22px">
            <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#fff">Tipo de NPS</p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:rgba(242,245,243,0.65)">Mostra apenas promotores, neutros ou detratores nas Avaliações listadas abaixo. O NPS e a Distribuição continuam calculados sobre o total do período — esse filtro não altera o valor do NPS, apenas a lista de comentários exibida.</p>
          </div>
        </div>
      </div>

      <div>
        <p style="margin:0 0 16px;font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#53D9B2">Fonte dos dados</p>
        <h2 style="margin:0 0 18px;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,36px);line-height:1.15;color:#fff">Perguntas completas da planilha</h2>
        <p style="margin:0 0 20px;max-width:74ch;font-size:14.5px;line-height:1.7;color:rgba(242,245,243,0.6)">Texto exato dos cabeçalhos identificados automaticamente na planilha conectada. Se um segmento aparecer como "não identificado", a pesquisa ainda não tem uma pergunta própria para esse assunto — o filtro desse segmento fica sem dados até que a coluna exista.</p>
        <div style="display:grid;gap:1px;background:rgba(242,245,243,0.14);border:1px solid rgba(242,245,243,0.14)">
          <sc-for list="{{ perguntas }}" as="p" hint-placeholder-count="4">
            <div style="background:#000;padding:20px 22px">
              <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#53D9B2">{{ p.rotulo }}</p>
              <p style="{{ p.textoStyle }}">{{ p.texto }}</p>
            </div>
          </sc-for>
          <div style="background:#000;padding:20px 22px">
            <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:rgba(242,245,243,0.5)">Data da resposta</p>
            <p style="{{ colunaDataStyle }}">{{ colunaDataTexto }}</p>
          </div>
        </div>
      </div>

      <div>
        <p style="margin:0 0 16px;font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#53D9B2">Exportação</p>
        <h2 style="margin:0 0 18px;font-family:Newsreader,Georgia,serif;font-weight:400;font-size:clamp(24px,3.4vw,36px);line-height:1.15;color:#fff">Como exportar os dados filtrados</h2>
        <p style="margin:0;max-width:74ch;font-size:15px;line-height:1.75;color:rgba(242,245,243,0.72)">No painel, o botão "Exportar XLSX" exporta exatamente as respostas visíveis com os filtros atuais (segmento, ano, mês, data e tipo de NPS), com uma linha por resposta (data, nota, perfil e comentário).</p>
      </div>

    </section>
  </sc-if>
</div>
</x-dc>
<script type="text/x-dc" data-dc-script data-props="{&quot;planilhaId&quot;:{&quot;editor&quot;:&quot;text&quot;,&quot;default&quot;:&quot;10_QFiQnAMqfrchpYoWjtFfqxIC2Qys4p9X5SeLGgIng&quot;,&quot;tsType&quot;:&quot;string&quot;,&quot;section&quot;:&quot;Dados&quot;},&quot;gid&quot;:{&quot;editor&quot;:&quot;text&quot;,&quot;default&quot;:&quot;951152274&quot;,&quot;tsType&quot;:&quot;string&quot;,&quot;section&quot;:&quot;Dados&quot;}}">
class Component extends DCLogic {
  state = { linhas: [], cab: [], colunasNota: {}, colunasTexto: {}, iData: -1, status: 'carregando', aba: 'painel', segmento: 'geral', ano: 'todos', mes: 'todos', data: 'todas', perfil: 'todos', limite: 3, ultimaAtualizacao: null };

  perfilDe(nota) { return nota >= 9 ? 'promotor' : (nota <= 6 ? 'detrator' : 'neutro'); }

  segmentosDef() {
    return [
      { chave: 'geral', rotulo: 'Geral', reNota: /geral|de forma geral|no geral|ag[eê]ncia|645/i, reTexto: /coment|sugest|cr[ií]tic|elogi|observa|deixe|escreva|opini/i },
      { chave: 'guiamento', rotulo: 'Guiamento e Transporte', reNota: /guiamento|guia|transporte|[oô]nibus|motorista|condutor/i, reTexto: /guiamento|guia|transporte|[oô]nibus|motorista|condutor/i },
      { chave: 'restaurante', rotulo: 'Restaurante', reNota: /restaurante|almo[çc]o|refei[çc][ãa]o|comida|gastronom/i, reTexto: /restaurante|almo[çc]o|refei[çc][ãa]o|comida|gastronom/i },
      { chave: 'trem', rotulo: 'Trem da República', reNota: /trem|locomotiva|vag[ãa]o|ferrovi/i, reTexto: /trem|locomotiva|vag[ãa]o|ferrovi/i }
    ];
  }

  rotuloSegmento(chave) {
    const d = this.segmentosDef().find(d => d.chave === chave);
    return d ? d.rotulo : chave;
  }

  normalizarTrem(txt) {
    return typeof txt === 'string' ? txt.replace(/trem\s+republicano/gi, 'Trem da República') : txt;
  }

  tempoDesde(ts) {
    if (!ts) return 'ainda não atualizado';
    const seg = Math.max(0, Math.round((Date.now() - ts) / 1000));
    if (seg < 10) return 'agora mesmo';
    if (seg < 60) return 'há ' + seg + ' segundos';
    const min = Math.round(seg / 60);
    if (min < 60) return 'há ' + min + (min === 1 ? ' minuto' : ' minutos');
    const hor = Math.round(min / 60);
    if (hor < 24) return 'há ' + hor + (hor === 1 ? ' hora' : ' horas');
    const dia = Math.round(hor / 24);
    return 'há ' + dia + (dia === 1 ? ' dia' : ' dias');
  }

  tabBtnStyle(ativo) {
    return 'padding:13px 22px;border-radius:999px;font-size:11.5px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;min-height:44px;'
      + (ativo ? 'border:1px solid #53D9B2;background:#53D9B2;color:#000;' : 'border:1px solid rgba(242,245,243,0.28);background:transparent;color:#F2F5F3;');
  }

  detectarColunasNota(cab, corpo) {
    const notaValida = (i) => {
      let ok = 0, total = 0;
      corpo.forEach(l => {
        const s = String(l[i] || '').trim();
        if (!s) return;
        total++;
        const n = Number(s.replace(',', '.'));
        if (!isNaN(n) && n >= 0 && n <= 10) ok++;
      });
      return total > 0 && ok / total > 0.7;
    };
    const candidatos = [];
    for (let i = 0; i < cab.length; i++) if (notaValida(i)) candidatos.push(i);

    const defs = this.segmentosDef();
    const usados = new Set();
    const colunasNota = {};
    defs.filter(d => d.chave !== 'geral').forEach(d => {
      const i = candidatos.find(i => !usados.has(i) && d.reNota.test(cab[i]));
      if (i !== undefined) { colunasNota[d.chave] = i; usados.add(i); }
    });
    let iGeral = candidatos.find(i => !usados.has(i) && /geral|de forma geral|no geral|ag[eê]ncia|645/i.test(cab[i]));
    if (iGeral === undefined) iGeral = candidatos.find(i => !usados.has(i) && /nps|recomend/i.test(cab[i]));
    if (iGeral === undefined) iGeral = candidatos.find(i => !usados.has(i) && /nota|avalia|satisfa|classific/i.test(cab[i]));
    if (iGeral === undefined) iGeral = candidatos.find(i => !usados.has(i));
    if (iGeral !== undefined) { colunasNota.geral = iGeral; usados.add(iGeral); }

    return colunasNota;
  }

  detectarColunasTexto(cab, corpo, colunasNota, iData) {
    const textoValido = (i) => {
      const media = corpo.reduce((s, l) => s + String(l[i] || '').length, 0) / Math.max(corpo.length, 1);
      const numericos = corpo.filter(l => String(l[i] || '').trim() !== '' && !isNaN(Number(String(l[i]).replace(',', '.')))).length;
      const datas = corpo.filter(l => String(l[i] || '').trim() !== '' && this.parseData(l[i])).length;
      return media > 12 && numericos < corpo.length * 0.3 && datas < corpo.length * 0.3;
    };
    const usadosNota = new Set(Object.values(colunasNota));
    if (iData >= 0) usadosNota.add(iData);
    const usados = new Set();
    const colunasTexto = {};
    this.segmentosDef().filter(d => d.chave !== 'geral').forEach(d => {
      for (let i = 0; i < cab.length; i++) {
        if (usadosNota.has(i) || usados.has(i)) continue;
        if (d.reTexto.test(cab[i]) && textoValido(i)) { colunasTexto[d.chave] = i; usados.add(i); break; }
      }
    });
    let iGeral = -1;
    for (let i = 0; i < cab.length; i++) {
      if (usadosNota.has(i) || usados.has(i)) continue;
      if (/coment|sugest|cr[ií]tic|elogi|observa|deixe|escreva|opni|opini/i.test(cab[i]) && textoValido(i)) { iGeral = i; break; }
    }
    if (iGeral < 0) {
      for (let i = 0; i < cab.length; i++) {
        if (usadosNota.has(i) || usados.has(i)) continue;
        if (/melhor|experi|relat/i.test(cab[i]) && textoValido(i)) { iGeral = i; break; }
      }
    }
    if (iGeral < 0) {
      let melhor = -1, maior = 12;
      cab.forEach((h, i) => {
        if (usadosNota.has(i) || usados.has(i)) return;
        const media = corpo.reduce((s, l) => s + String(l[i] || '').length, 0) / Math.max(corpo.length, 1);
        if (media > maior) { maior = media; melhor = i; }
      });
      iGeral = melhor;
    }
    if (iGeral >= 0) colunasTexto.geral = iGeral;
    return colunasTexto;
  }

  componentDidMount() {
    this.carregar();
    this._relogio = setInterval(() => this.forceUpdate(), 30000);
  }

  componentWillUnmount() {
    clearInterval(this._relogio);
  }

  get planilhaId() { return this.props.planilhaId ?? '10_QFiQnAMqfrchpYoWjtFfqxIC2Qys4p9X5SeLGgIng'; }
  get gid() { return this.props.gid ?? '951152274'; }

  carregar() {
    this.setState({ status: 'carregando', ultimaAtualizacao: Date.now() });
    const urls = [
      'https://docs.google.com/spreadsheets/d/' + this.planilhaId + '/gviz/tq?tqx=out:csv&gid=' + this.gid,
      'https://docs.google.com/spreadsheets/d/' + this.planilhaId + '/export?format=csv&gid=' + this.gid
    ];
    const tentar = (i) => {
      if (i >= urls.length) { this.setState({ status: 'erro' }); return; }
      fetch(urls[i])
        .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(txt => {
          if (/<html/i.test(txt.slice(0, 400))) throw new Error('html');
          this.processarCsv(txt, 'planilha');
        })
        .catch(() => tentar(i + 1));
    };
    tentar(0);
  }

  parseCsv(txt) {
    const linhas = [];
    let campo = '', linha = [], dentro = false;
    for (let i = 0; i < txt.length; i++) {
      const c = txt[i];
      if (dentro) {
        if (c === '"') { if (txt[i + 1] === '"') { campo += '"'; i++; } else { dentro = false; } }
        else { campo += c; }
      } else if (c === '"') { dentro = true; }
      else if (c === ',') { linha.push(campo); campo = ''; }
      else if (c === '\n') { linha.push(campo); linhas.push(linha); linha = []; campo = ''; }
      else if (c !== '\r') { campo += c; }
    }
    if (campo.length || linha.length) { linha.push(campo); linhas.push(linha); }
    return linhas.filter(l => l.some(v => String(v).trim() !== ''));
  }

  parseData(v) {
    const s = String(v).trim();
    let m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
    if (m) {
      const ano = m[3].length === 2 ? 2000 + Number(m[3]) : Number(m[3]);
      return new Date(ano, Number(m[2]) - 1, Number(m[1]));
    }
    m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    const d = new Date(s);
    return isNaN(d.getTime()) ? null : d;
  }

  processarCsv(txt, origem) {
    const bruto = this.parseCsv(txt);
    if (bruto.length < 2) { this.setState({ status: 'vazio' }); return; }
    const cab = bruto[0].map(h => String(h).trim());
    const corpo = bruto.slice(1);

    const colunasNota = this.detectarColunasNota(cab, corpo);
    if (Object.keys(colunasNota).length === 0) { this.setState({ status: 'vazio', cab, colunasNota: {}, colunasTexto: {} }); return; }

    const acha = (re, filtro) => {
      for (let i = 0; i < cab.length; i++) if (re.test(cab[i]) && (!filtro || filtro(i))) return i;
      return -1;
    };
    const dataValida = (i) => {
      let ok = 0, total = 0;
      corpo.forEach(l => {
        const s = String(l[i] || '').trim();
        if (!s) return;
        total++;
        if (this.parseData(s)) ok++;
      });
      return total > 0 && ok / total > 0.7;
    };
    let iData = acha(/viajei|no dia|data.*(embarque|passeio|viagem|visita)/i, dataValida);
    if (iData < 0) iData = acha(/^data(?!\/hora)/i, dataValida);
    if (iData < 0) iData = acha(/carimbo|timestamp|data/i, dataValida);
    if (iData < 0) iData = 0;

    const colunasTexto = this.detectarColunasTexto(cab, corpo, colunasNota, iData);

    const linhas = corpo.map(l => {
      const notas = {};
      Object.keys(colunasNota).forEach(k => {
        const raw = String(l[colunasNota[k]] || '').trim();
        if (!raw) return;
        const n = Number(raw.replace(',', '.'));
        if (!isNaN(n) && n >= 0 && n <= 10) notas[k] = n;
      });
      const textos = {};
      Object.keys(colunasTexto).forEach(k => {
        textos[k] = String(l[colunasTexto[k]] || '').trim();
      });
      return { data: this.parseData(l[iData]), notas, textos };
    }).filter(r => Object.keys(r.notas).length > 0);

    this.setState({
      linhas, cab, colunasNota, colunasTexto, iData,
      status: linhas.length ? (origem === 'planilha' ? 'ok' : 'colado') : 'vazio',
      limite: 3
    });
  }

  fmt(d) {
    if (!d) return 'Sem data';
    return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
  }

  calcNps(itens) {
    if (!itens.length) return null;
    const p = itens.filter(i => i.nota >= 9).length;
    const d = itens.filter(i => i.nota <= 6).length;
    return Math.round((p - d) / itens.length * 100);
  }

  renderVals() {
    const { linhas, cab, colunasNota, colunasTexto, iData, status, aba, segmento, ano, mes, data, perfil, limite, ultimaAtualizacao } = this.state;
    const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    const comSeg = linhas
      .filter(l => l.notas[segmento] !== undefined)
      .map(l => ({ data: l.data, nota: l.notas[segmento], texto: l.textos[segmento] || l.textos.geral || '' }));

    let f = comSeg;
    if (ano !== 'todos') f = f.filter(l => l.data && String(l.data.getFullYear()) === ano);
    if (mes !== 'todos') f = f.filter(l => l.data && String(l.data.getMonth()) === mes);
    if (data !== 'todas') f = f.filter(l => l.data && this.fmt(l.data) === data);
    const base = f;
    if (perfil !== 'todos') f = f.filter(l => this.perfilDe(l.nota) === perfil);

    const anos = [...new Set(comSeg.filter(l => l.data).map(l => l.data.getFullYear()))].sort((a, b) => b - a);
    const mesesDisp = [...new Set(
      comSeg.filter(l => l.data && (ano === 'todos' || String(l.data.getFullYear()) === ano)).map(l => l.data.getMonth())
    )].sort((a, b) => a - b);
    const datasDisp = [...new Set(
      comSeg.filter(l => l.data
        && (ano === 'todos' || String(l.data.getFullYear()) === ano)
        && (mes === 'todos' || String(l.data.getMonth()) === mes)
      ).sort((a, b) => b.data - a.data).map(l => this.fmt(l.data))
    )];

    const grupos = {};
    base.forEach(l => { const k = this.fmt(l.data); (grupos[k] = grupos[k] || []).push(l); });
    const chaveData = (k) => { const p = k.split('/'); return new Date(Number(p[2]), Number(p[1]) - 1, Number(p[0])); };
    const porData = Object.keys(grupos)
      .sort((a, b) => chaveData(b) - chaveData(a))
      .slice(0, 30)
      .map(k => {
        const n = this.calcNps(grupos[k]);
        const cor = n >= 50 ? '#53D9B2' : (n >= 0 ? '#FADA28' : '#E8674F');
        return {
          rotulo: k,
          nps: n,
          qtdTexto: grupos[k].length + (grupos[k].length === 1 ? ' resp.' : ' resp.'),
          barraStyle: 'position:absolute;top:0;bottom:0;left:50%;width:' + Math.max(Math.abs(n) / 2, 1) + '%;background:' + cor + ';'
            + (n < 0 ? 'transform:translateX(-100%);' : '')
        };
      });

    const total = base.length;
    const nps = this.calcNps(base);
    const pct = (fn) => total ? Math.round(base.filter(fn).length / total * 100) + '%' : '—';
    const media = f.length ? (f.reduce((s, i) => s + i.nota, 0) / f.length).toFixed(1).replace('.', ',') : '—';

    const comTexto = f.filter(l => l.texto && l.texto.length > 3).sort((a, b) => (b.data || 0) - (a.data || 0));
    const avaliacoes = comTexto.slice(0, limite).map(l => {
      const promotor = l.nota >= 9, detrator = l.nota <= 6;
      const cor = promotor ? '#53D9B2' : (detrator ? '#E8674F' : '#FADA28');
      return {
        data: this.fmt(l.data),
        nota: l.nota,
        texto: this.normalizarTrem(l.texto),
        perfil: promotor ? 'Promotor' : (detrator ? 'Detrator' : 'Neutro'),
        perfilStyle: 'font-size:11px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:' + cor,
        notaStyle: 'display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:30px;border-radius:999px;font-size:14px;font-weight:700;background:' + cor + ';color:#000'
      };
    });

    const rotuloPeriodo = data !== 'todas'
      ? data
      : (mes !== 'todos' ? meses[Number(mes)] + (ano !== 'todos' ? ' de ' + ano : '') : (ano !== 'todos' ? 'Ano ' + ano : 'Todo o período'));

    const dadosCarregados = status === 'ok' || status === 'colado';
    const segmentoSemColuna = dadosCarregados && colunasNota[segmento] === undefined;

    const perguntas = this.segmentosDef().map(d => {
      const idx = colunasNota[d.chave];
      const encontrada = idx !== undefined && cab[idx] !== undefined;
      return {
        rotulo: d.rotulo,
        texto: encontrada ? this.normalizarTrem(cab[idx]) : 'Não identificada nesta planilha.',
        textoStyle: 'margin:0;font-size:14.5px;line-height:1.6;color:' + (encontrada ? 'rgba(242,245,243,0.85)' : 'rgba(242,245,243,0.4)') + (encontrada ? '' : ';font-style:italic')
      };
    });
    const colunaDataEncontrada = iData >= 0 && cab[iData] !== undefined && dadosCarregados;
    const colunaDataTexto = colunaDataEncontrada ? this.normalizarTrem(cab[iData]) : 'Não identificada nesta planilha.';
    const colunaDataStyle = 'margin:0;font-size:14.5px;line-height:1.6;color:' + (colunaDataEncontrada ? 'rgba(242,245,243,0.85)' : 'rgba(242,245,243,0.4)') + (colunaDataEncontrada ? '' : ';font-style:italic');

    const abaPainel = aba !== 'metodologia';
    const abaMetodologia = aba === 'metodologia';

    const exportarXlsx = () => {
      if (!window.XLSX) { alert('A biblioteca de exportação ainda está carregando. Tente novamente em alguns segundos.'); return; }
      const linhasExport = f.map(l => ({
        Data: this.fmt(l.data),
        Segmento: this.rotuloSegmento(segmento),
        Nota: l.nota,
        Perfil: this.perfilDe(l.nota) === 'promotor' ? 'Promotor' : (this.perfilDe(l.nota) === 'detrator' ? 'Detrator' : 'Neutro'),
        Comentario: this.normalizarTrem(l.texto) || ''
      }));
      const ws = window.XLSX.utils.json_to_sheet(linhasExport);
      const wb = window.XLSX.utils.book_new();
      window.XLSX.utils.book_append_sheet(wb, ws, 'NPS');
      window.XLSX.writeFile(wb, 'nps-' + segmento + '-' + Date.now() + '.xlsx');
    };
    return {
      mostrarAviso: status === 'erro' || status === 'vazio',
      avisoSegmento: segmentoSemColuna ? 'Não encontramos uma pergunta de NPS para "' + this.rotuloSegmento(segmento) + '" nesta planilha. Veja a aba "Metodologia e perguntas" para conferir as colunas identificadas.' : '',
      recarregar: () => this.carregar(),
      colarCsv: (e) => { const v = e.target.value; if (v && v.trim().length > 20) this.processarCsv(v, 'colado'); },

      aba,
      abaPainel,
      abaMetodologia,
      abrirPainel: () => this.setState({ aba: 'painel' }),
      abrirMetodologia: () => this.setState({ aba: 'metodologia' }),
      abaBtnStyle: {
        painel: this.tabBtnStyle(abaPainel),
        metodologia: this.tabBtnStyle(abaMetodologia),
        atualizar: this.tabBtnStyle(false) + (status === 'carregando' ? 'opacity:0.55;cursor:not-allowed;' : '')
      },
      atualizando: status === 'carregando',
      atualizarLabel: status === 'carregando' ? 'Atualizando…' : 'Atualizar painel',
      atualizadoTitle: status === 'carregando' ? 'Atualizando…' : 'Atualizado ' + this.tempoDesde(ultimaAtualizacao),

      opcoesSegmento: this.segmentosDef().map(d => ({ valor: d.chave, rotulo: d.rotulo })),
      segmentoAtual: segmento,
      mudarSegmento: (e) => this.setState({ segmento: e.target.value, limite: 3 }),

      perguntas,
      colunaDataTexto,
      colunaDataStyle,

      exportarXlsx,
      contadorExport: f.length + (f.length === 1 ? ' resposta no filtro atual' : ' respostas no filtro atual'),

      opcoesAno: [{ valor: 'todos', rotulo: 'Todos os anos' }].concat(anos.map(a => ({ valor: String(a), rotulo: String(a) }))),
      opcoesMes: [{ valor: 'todos', rotulo: 'Todos os meses' }].concat(mesesDisp.map(m => ({ valor: String(m), rotulo: meses[m] }))),
      opcoesData: [{ valor: 'todas', rotulo: 'Todas as datas' }].concat(datasDisp.map(d => ({ valor: d, rotulo: d }))),
      anoAtual: ano,
      mesAtual: mes,
      dataAtual: data,
      mudarAno: (e) => this.setState({ ano: e.target.value, mes: 'todos', data: 'todas', limite: 3 }),
      mudarMes: (e) => this.setState({ mes: e.target.value, data: 'todas', limite: 3 }),
      mudarData: (e) => this.setState({ data: e.target.value, limite: 3 }),
      perfilAtual: perfil,
      mudarPerfil: (e) => this.setState({ perfil: e.target.value, limite: 3 }),
      limparFiltros: () => this.setState({ ano: 'todos', mes: 'todos', data: 'todas', perfil: 'todos', limite: 3 }),

      nps: nps === null ? '—' : nps,
      npsClassificacao: nps === null ? 'Sem respostas no filtro'
        : nps >= 75 ? 'Zona de excelência'
        : nps >= 50 ? 'Zona de qualidade'
        : nps >= 0 ? 'Zona de aperfeiçoamento' : 'Zona crítica',
      totalRespostas: perfil === 'todos' ? total : f.length + ' de ' + total,
      periodoTexto: perfil === 'todos' ? rotuloPeriodo : rotuloPeriodo + ' · ' + ({ promotor: 'promotores', neutro: 'neutros', detrator: 'detratores' })[perfil],
      pctPromotores: pct(i => i.nota >= 9),
      pctNeutros: pct(i => i.nota === 7 || i.nota === 8),
      pctDetratores: pct(i => i.nota <= 6),
      notaMedia: media,
      npsNota: perfil === 'todos' ? 'Calculado sobre todas as respostas do período' : 'NPS e distribuição sempre sobre o total do período',

      porData,
      notaDatas: porData.length ? 'Mostrando ' + porData.length + ' data(s) mais recentes do filtro.' : 'Nenhuma data no filtro atual.',

      avaliacoes,
      contadorAvaliacoes: comTexto.length ? 'Mostrando ' + avaliacoes.length + ' de ' + comTexto.length : 'Sem comentários no filtro',
      temMais: comTexto.length > avaliacoes.length,
      podeRecolher: limite > 3,
      verMais: () => this.setState(s => ({ limite: s.limite + 3 })),
      recolher: () => this.setState({ limite: 3 })
    };
  }
}
</script>
</body>
</html>
