// Lista de passageiros em tempo real (guia e ADM).
// - Atualiza sozinha a cada poucos segundos: o que a equipe inclui e o que outros guias marcam aparece aqui.
// - Check-in/check-out com resposta imediata na tela; sem sinal, as marcações ficam numa fila e são reenviadas.
// - Aba "Mapa do carro": ao tocar numa poltrona mostra o passageiro, telefone para ligar e destaca (piscando)
//   as outras poltronas da mesma reserva (mesma "Venda").
(function () {
  'use strict';

  var raiz = document.getElementById('lista-passageiros');
  if (!raiz) return;

  var base = raiz.dataset.base;
  var urlIncluir = raiz.dataset.incluir;
  var diaria = raiz.dataset.diaria;
  var csrf = raiz.dataset.csrf;
  var admin = raiz.dataset.admin === '1';
  var INTERVALO = 8000;
  var CHAVE_FILA = 'fila-passageiros-' + base + '-' + diaria;
  // Colunas da tabela (na ordem da planilha). O tipo de passageiro aparece junto ao nome.
  var CAMPOS = ['tipo_documento', 'documento', 'nascimento', 'venda', 'embarque', 'poltrona', 'telefone', 'observacao'];
  var CAMPOS_FORM = ['nome'].concat(CAMPOS, ['tipo_pax']);
  var NOMES_MARCA = { checkin: 'Check-in', checkout: 'Check-out', noshow: 'No-show' };

  var dados = null;
  var fila = lerFila();
  var enviando = false;
  var selecionada = null;
  var editando = null;
  var $ = function (sel, ctx) { return (ctx || raiz).querySelector(sel); };

  // ---------- utilidades ----------
  function el(tag, classe, texto) {
    var n = document.createElement(tag);
    if (classe) n.className = classe;
    if (texto !== undefined && texto !== null) n.textContent = texto;
    return n;
  }
  function normalizar(s) {
    return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  }
  function lerFila() {
    try { return JSON.parse(localStorage.getItem(CHAVE_FILA) || '[]'); } catch (e) { return []; }
  }
  function salvarFila() {
    try { localStorage.setItem(CHAVE_FILA, JSON.stringify(fila)); } catch (e) { /* armazenamento indisponível */ }
  }
  function horaAgora() {
    var d = new Date();
    return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
  }
  function status(texto, tipo) {
    var s = $('[data-status]');
    s.className = 'lp-status' + (tipo ? ' ' + tipo : '');
    s.textContent = '';
    s.appendChild(el('span', 'ponto'));
    s.appendChild(document.createTextNode(' ' + texto));
  }
  function avisar(texto) {
    var t = el('div', 'toast', texto);
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 4000);
  }
  function post(url, campos) {
    var fd = new FormData();
    fd.append('_csrf', csrf);
    Object.keys(campos).forEach(function (k) { fd.append(k, campos[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) {
        var tipo = r.headers.get('Content-Type') || '';
        if (tipo.indexOf('application/json') === -1) {
          var erro = new Error('Sua sessão expirou. Recarregue a página e entre novamente.');
          erro.definitivo = true;
          throw erro;
        }
        return r.json().then(function (j) {
          if (!r.ok) { var e2 = new Error(j.erro || 'Não foi possível salvar.'); e2.definitivo = true; throw e2; }
          return j;
        });
      });
  }

  // ---------- carregamento e atualização automática ----------
  function carregar() {
    if (!diaria || diaria === '0') return;
    fetch(base + '/dados?diaria=' + encodeURIComponent(diaria), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) {
        if ((r.headers.get('Content-Type') || '').indexOf('application/json') === -1) {
          throw new Error('sessao');
        }
        return r.json();
      })
      .then(function (j) {
        if (j.erro) throw new Error(j.erro);
        dados = j;
        aplicarFilaLocal();
        renderizar();
        status('Atualizado às ' + j.atualizado + (fila.length ? ' · ' + fila.length + ' marcação(ões) aguardando conexão' : ''), fila.length ? 'pendente' : 'ok');
      })
      .catch(function (e) {
        if (e.message === 'sessao') {
          status('Sessão expirada. Recarregue a página.', 'erro');
        } else {
          status('Sem conexão. Mostrando a última versão' + (fila.length ? ' · ' + fila.length + ' marcação(ões) na fila' : '') + '.', 'pendente');
        }
      });
  }

  // Mantém na tela as marcações ainda não confirmadas pelo servidor.
  function aplicarFilaLocal() {
    fila.forEach(function (m) {
      var p = acharPassageiro(m.id);
      if (!p) return;
      if (m.desfazer) {
        p[m.tipo] = null;
        if (m.tipo === 'checkin') p.checkout = null;
      } else if (!p[m.tipo]) {
        p[m.tipo] = { hora: m.hora, por: 'você (enviando)' };
        if (m.tipo === 'checkin') p.noshow = null;
      }
    });
  }

  function acharPassageiro(id) {
    if (!dados) return null;
    for (var i = 0; i < dados.passageiros.length; i++) {
      if (dados.passageiros[i].id === id) return dados.passageiros[i];
    }
    return null;
  }

  function marcar(p, tipo, desfazer) {
    if (!dados.pode_marcar) {
      avisar('O check-in só pode ser feito no dia do trabalho.');
      return;
    }
    if (desfazer && !window.confirm('Desfazer o ' + NOMES_MARCA[tipo] + ' de ' + p.campos.nome.v + '?')) {
      return;
    }
    if (tipo === 'noshow' && !desfazer && !window.confirm('Registrar que ' + p.campos.nome.v + ' não compareceu (no-show)?')) {
      return;
    }
    fila.push({ id: p.id, tipo: tipo, desfazer: desfazer ? 1 : 0, hora: horaAgora() });
    salvarFila();
    aplicarFilaLocal();
    renderizar();
    enviarFila();
  }

  function enviarFila() {
    if (enviando || !fila.length) return;
    enviando = true;
    var m = fila[0];
    post(base + '/' + m.id + '/marcar', { diaria: diaria, tipo: m.tipo, desfazer: m.desfazer })
      .then(function (j) {
        fila.shift();
        salvarFila();
        dados = j;
        aplicarFilaLocal();
        renderizar();
        enviando = false;
        status('Atualizado às ' + j.atualizado, 'ok');
        enviarFila();
      })
      .catch(function (e) {
        enviando = false;
        if (e.definitivo) {
          fila.shift();
          salvarFila();
          avisar(e.message);
          carregar();
          enviarFila();
        } else {
          status('Sem conexão · ' + fila.length + ' marcação(ões) aguardando para enviar', 'pendente');
        }
      });
  }

  // ---------- aba Lista ----------
  function passaFiltro(p) {
    var filtro = $('[data-filtro]').value;
    var busca = normalizar($('[data-busca]').value);
    if (filtro === 'sem-checkin' && (p.checkin || p.noshow)) return false;
    if (filtro === 'noshow' && !p.noshow) return false;
    if (filtro === 'checkin' && !p.checkin) return false;
    if (filtro === 'checkout' && !p.checkout) return false;
    if (filtro === 'ajustados' && !p.incluido_guia && !Object.keys(p.campos).some(function (c) { return p.campos[c].g; })) return false;
    if (!busca) return true;
    return ['nome', 'documento', 'venda', 'poltrona', 'embarque'].some(function (c) {
      return normalizar(p.campos[c].v).indexOf(busca) !== -1;
    });
  }

  function celula(p, campo) {
    var c = p.campos[campo];
    var td = el('td', 'col-' + campo);
    if (campo === 'telefone' && c.bruto) {
      var a = el('a', 'link-tel', c.v);
      a.href = 'tel:+55' + c.bruto.replace(/^55(?=\d{10,11}$)/, '');
      td.appendChild(a);
    } else {
      td.textContent = c.v;
    }
    if (c.g || p.incluido_guia) {
      td.classList.add('ed-guia');
      td.title = p.incluido_guia ? 'Incluído pelo guia' : 'Ajustado pelo guia. Original: ' + (c.o || '(vazio)');
    }
    return td;
  }

  function botaoMarca(p, tipo) {
    var feito = p[tipo];
    var b = el('button', 'marcacao' + (feito ? ' feita' : ''));
    b.type = 'button';
    if (feito) {
      b.appendChild(el('span', 'marca-hora', '✓ ' + feito.hora));
      if (feito.por) b.appendChild(el('span', 'marca-por', feito.por));
      b.setAttribute('aria-label', NOMES_MARCA[tipo] + ' registrado às ' + feito.hora + '. Toque para desfazer.');
      if (tipo === 'noshow') b.insertBefore(el('span', 'marca-hora', 'No-show'), b.firstChild);
    } else {
      b.textContent = NOMES_MARCA[tipo];
      b.disabled = !dados.pode_marcar || (tipo === 'checkout' && !p.checkin);
    }
    if (tipo === 'noshow') b.classList.add('marca-noshow');
    b.addEventListener('click', function () { marcar(p, tipo, !!feito); });
    return b;
  }

  function renderizarLista() {
    var corpo = $('[data-corpo]');
    corpo.textContent = '';
    var visiveis = dados.passageiros.filter(passaFiltro);
    if (!visiveis.length) {
      var tr = el('tr');
      var td = el('td', 'vazio-linha', dados.passageiros.length ? 'Nenhum passageiro neste filtro.' : 'A lista ainda está vazia.');
      td.colSpan = 13;
      tr.appendChild(td);
      corpo.appendChild(tr);
      return;
    }
    visiveis.forEach(function (p) {
      var tr = el('tr', (p.checkin ? 'com-checkin' : '') + (p.noshow ? ' com-noshow' : '') + (p.incluido_guia ? ' incluido-guia' : ''));
      tr.appendChild(el('td', 'col-n', String(p.n)));
      var tdNome = celula(p, 'nome');
      if (p.campos.tipo_pax.bruto === 'crianca' || p.campos.tipo_pax.bruto === 'colo') {
        tdNome.appendChild(el('span', 'tag-pax' + (p.campos.tipo_pax.g ? ' ed-guia' : ''), p.campos.tipo_pax.v));
      }
      if (p.equipe) {
        var tagEquipe = el('span', 'tag-pax tag-equipe', 'Equipe · fora da contagem');
        tdNome.appendChild(tagEquipe);
      }
      tr.appendChild(tdNome);
      var ci = el('td', 'col-marca');
      // Quem não compareceu mostra o no-show no lugar do check-in (tocar desfaz).
      if (p.noshow) {
        ci.appendChild(botaoMarca(p, 'noshow'));
      } else {
        ci.appendChild(botaoMarca(p, 'checkin'));
        if (!p.checkin && dados.pode_marcar) ci.appendChild(botaoMarca(p, 'noshow'));
      }
      tr.appendChild(ci);
      var co = el('td', 'col-marca'); co.appendChild(botaoMarca(p, 'checkout')); tr.appendChild(co);
      CAMPOS.forEach(function (c) { tr.appendChild(celula(p, c)); });
      var acoes = el('td', 'col-acoes');
      var ed = el('button', 'btn btn-texto btn-p btn-editar');
      ed.innerHTML = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
      ed.setAttribute('aria-label', 'Editar ' + p.campos.nome.v);
      ed.title = 'Editar passageiro';
      ed.type = 'button';
      ed.addEventListener('click', function () { abrirDialogo(p); });
      acoes.appendChild(ed);
      tr.appendChild(acoes);
      // No celular a tabela vira cartões: cada célula mostra o nome da sua coluna.
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (rotulosColunas[i]) td.dataset.rotulo = rotulosColunas[i];
      });
      corpo.appendChild(tr);
    });
  }
  var rotulosColunas = Array.prototype.map.call(raiz.querySelectorAll('.lp-tabela thead th'), function (th) {
    return th.textContent.trim();
  });

  // ---------- aba Mapa do carro ----------
  function chavePoltrona(v) {
    return String(v || '').trim().toUpperCase().replace(/^0+(?=\d)/, '');
  }

  function renderizarMapa() {
    var carro = $('[data-carro]');
    var titulo = $('[data-carro-titulo]');
    var semPoltrona = $('[data-sem-poltrona]');
    carro.textContent = '';
    semPoltrona.textContent = '';
    var veic = dados.veiculo;
    if (!veic) {
      titulo.textContent = '';
      carro.appendChild(el('p', 'vazio', 'O veículo desta viagem/tour não foi definido. ' + (admin ? 'Escolha o veículo em Editar dados da viagem/tour.' : 'Peça à coordenação para cadastrar.')));
      return;
    }
    titulo.textContent = veic.rotulo + (veic.bloqueadas.length ? ' · ' + veic.bloqueadas.length + ' bloqueada(s)' : '');

    var porPoltrona = {};
    var foraDoMapa = [];
    dados.passageiros.forEach(function (p) {
      var k = chavePoltrona(p.campos.poltrona.v);
      var n = parseInt(k, 10);
      if (!k || String(n) !== k || n < 1 || n > veic.lugares) {
        foraDoMapa.push(p);
        return;
      }
      (porPoltrona[k] = porPoltrona[k] || []).push(p);
    });

    var selPass = selecionada && porPoltrona[selecionada] ? porPoltrona[selecionada][0] : null;
    var venda = selPass ? normalizar(selPass.campos.venda.v) : '';
    var relacionadas = {};
    if (venda) {
      dados.passageiros.forEach(function (p) {
        var k = chavePoltrona(p.campos.poltrona.v);
        if (p !== selPass && k && normalizar(p.campos.venda.v) === venda) relacionadas[k] = true;
      });
    }

    var bloqueadas = {};
    (veic.bloqueadas || []).forEach(function (n) { bloqueadas[String(n)] = true; });

    // Ônibus DD: piso superior com as primeiras poltronas e piso inferior com as últimas.
    var pisos = veic.piso_inferior > 0
      ? [['Piso superior', 1, veic.lugares - veic.piso_inferior], ['Piso inferior', veic.lugares - veic.piso_inferior + 1, veic.lugares]]
      : [[null, 1, veic.lugares]];

    pisos.forEach(function (piso) {
      var area = carro;
      if (piso[0]) {
        area = el('div', 'piso');
        area.appendChild(el('p', 'rotulo piso-titulo', piso[0]));
        carro.appendChild(area);
      }
      var fileira = null;
      for (var n = piso[1]; n <= piso[2]; n++) {
        var pos = (n - piso[1]) % veic.por_fileira;
        if (pos === 0) {
          fileira = el('div', 'fileira');
          area.appendChild(fileira);
        }
        if (pos === 2) fileira.appendChild(el('span', 'corredor'));
        fileira.appendChild(botaoPoltrona(String(n), porPoltrona[String(n)] || [], bloqueadas, relacionadas));
      }
    });

    renderizarInfo(selecionada ? (porPoltrona[selecionada] || []) : null, Object.keys(relacionadas), !!bloqueadas[selecionada]);

    if (foraDoMapa.length) {
      semPoltrona.appendChild(el('p', 'rotulo', 'Sem poltrona no mapa (' + foraDoMapa.length + ')'));
      var ul = el('ul', 'sem-poltrona');
      foraDoMapa.forEach(function (p) {
        var li = el('li', null, p.campos.nome.v + (p.campos.poltrona.v ? ' · poltrona ' + p.campos.poltrona.v : ''));
        ul.appendChild(li);
      });
      semPoltrona.appendChild(ul);
    }
  }

  function foiAjustado(p) {
    return p.incluido_guia || Object.keys(p.campos).some(function (c) { return p.campos[c].g; });
  }

  function botaoPoltrona(k, ocupantes, bloqueadas, relacionadas) {
    var b = el('button', 'poltrona');
    b.type = 'button';
    b.textContent = k;
    if (bloqueadas[k]) b.classList.add('bloqueada');
    if (ocupantes.length) b.classList.add('ocupada');
    if (ocupantes.length && ocupantes[0].checkin) b.classList.add('checkin');
    if (ocupantes.length && ocupantes.every(function (p) { return p.noshow; })) b.classList.add('noshow');
    var adultos = ocupantes.filter(function (p) { return p.campos.tipo_pax.bruto !== 'colo'; });
    if (adultos.length > 1 || (ocupantes.length && bloqueadas[k])) b.classList.add('conflito');
    if (ocupantes.length > adultos.length) b.classList.add('com-colo');
    if (ocupantes.some(foiAjustado)) b.classList.add('editada');
    if (selecionada === k) b.classList.add('selecionada');
    if (relacionadas[k]) b.classList.add('reserva');
    b.setAttribute('aria-label', 'Poltrona ' + k + (bloqueadas[k] ? ' (bloqueada)' : '')
      + (ocupantes.length ? ': ' + ocupantes.map(function (p) { return p.campos.nome.v; }).join(', ') : ': livre'));
    b.dataset.poltrona = k;
    b.addEventListener('click', function () {
      selecionada = this.dataset.poltrona === selecionada ? null : this.dataset.poltrona;
      renderizarMapa();
      // No celular o painel fica abaixo do mapa: leva o guia direto aos dados do passageiro.
      if (selecionada && window.innerWidth < 900) {
        $('[data-info]').scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
    return b;
  }

  // Local de embarque em destaque, com o horário de saída cadastrado para aquele ponto.
  function linhaEmbarque(p) {
    var local = p.campos.embarque.v;
    var linha = el('p', 'info-embarque' + (p.campos.embarque.g ? ' ed-guia' : ''));
    linha.appendChild(el('span', 'rotulo', 'Embarque'));
    if (!local) {
      linha.appendChild(el('strong', 'texto-3', 'Não informado'));
      return linha;
    }
    var origem = (dados.origens || []).filter(function (o) { return normalizar(o.local) === normalizar(local); })[0];
    linha.appendChild(el('strong', null, local + (origem && origem.horario ? ' · ' + origem.horario.slice(0, 5) : '')));
    return linha;
  }

  function renderizarInfo(ocupantes, relacionadas, bloqueada) {
    var info = $('[data-info]');
    info.textContent = '';
    if (ocupantes === null) {
      info.appendChild(el('p', 'rotulo', 'Toque numa poltrona'));
      info.appendChild(el('p', 'texto-2', 'Veja quem está nela, ligue para o passageiro e faça o check-in.'));
      return;
    }
    info.appendChild(el('p', 'sobretitulo', 'Poltrona ' + selecionada));
    if (!ocupantes.length) {
      info.appendChild(el('h3', null, bloqueada ? 'Bloqueada' : 'Livre'));
      if (bloqueada) info.appendChild(el('p', 'texto-2', 'Poltrona bloqueada pela coordenação para esta viagem.'));
      return;
    }
    if (bloqueada) {
      info.appendChild(el('p', 'alerta-mini erro', 'Atenção: esta poltrona está bloqueada, mas há passageiro nela na lista.'));
    }
    var naoColo = ocupantes.filter(function (p) { return p.campos.tipo_pax.bruto !== 'colo'; });
    if (naoColo.length > 1) {
      info.appendChild(el('p', 'alerta-mini erro', 'Atenção: ' + naoColo.length + ' passageiros com esta poltrona na lista.'));
    }
    ocupantes.forEach(function (p) {
      var bloco = el('div', 'info-passageiro');
      var nome = el('h3', p.campos.nome.g || p.incluido_guia ? 'ed-guia' : null, p.campos.nome.v);
      bloco.appendChild(nome);
      bloco.appendChild(linhaEmbarque(p));
      var dl = el('dl', 'ficha ficha-compacta');
      [['Documento', (p.campos.tipo_documento.v ? p.campos.tipo_documento.v + ' ' : '') + p.campos.documento.v],
       ['Tipo', p.campos.tipo_pax.v], ['Nascimento', p.campos.nascimento.v], ['Venda', p.campos.venda.v],
       ['Observação', p.campos.observacao.v]].forEach(function (par) {
        if (!par[1] || !String(par[1]).trim()) return;
        dl.appendChild(el('dt', null, par[0]));
        dl.appendChild(el('dd', null, par[1]));
      });
      bloco.appendChild(dl);

      if (relacionadas.length && p.campos.venda.v) {
        bloco.appendChild(el('p', 'alerta-mini reserva',
          'Reserva ' + p.campos.venda.v + ' também ocupa a(s) poltrona(s) ' + relacionadas.sort(function (a, b) { return a - b; }).join(', ') + ' (piscando no mapa).'));
        // Como no check-in por pedido dos sistemas de reserva: embarca o grupo da reserva de uma vez.
        var daReserva = dados.passageiros.filter(function (x) {
          return normalizar(x.campos.venda.v) === normalizar(p.campos.venda.v) && !x.checkin;
        });
        if (daReserva.length > 1 && dados.pode_marcar) {
          var todos = el('button', 'btn btn-contorno btn-p', 'Check-in da reserva inteira (' + daReserva.length + ')');
          todos.type = 'button';
          todos.addEventListener('click', function () {
            if (!window.confirm('Fazer check-in de ' + daReserva.length + ' passageiros da reserva ' + p.campos.venda.v + '?')) return;
            daReserva.forEach(function (x) { fila.push({ id: x.id, tipo: 'checkin', desfazer: 0, hora: horaAgora() }); });
            salvarFila();
            aplicarFilaLocal();
            renderizar();
            enviarFila();
          });
          bloco.appendChild(todos);
        }
      }

      var acoes = el('div', 'info-acoes');
      if (p.campos.telefone.bruto) {
        var ligar = el('a', 'btn btn-primario btn-p', 'Ligar ' + p.campos.telefone.v);
        ligar.href = 'tel:+55' + p.campos.telefone.bruto.replace(/^55(?=\d{10,11}$)/, '');
        acoes.appendChild(ligar);
      } else {
        acoes.appendChild(el('span', 'texto-2', 'Sem telefone cadastrado'));
      }
      if (p.noshow) {
        acoes.appendChild(botaoMarca(p, 'noshow'));
      } else {
        acoes.appendChild(botaoMarca(p, 'checkin'));
        acoes.appendChild(botaoMarca(p, 'checkout'));
        if (!p.checkin && dados.pode_marcar) acoes.appendChild(botaoMarca(p, 'noshow'));
      }
      var ed = el('button', 'btn btn-texto btn-p', 'Editar');
      ed.type = 'button';
      ed.addEventListener('click', function () { abrirDialogo(p); });
      acoes.appendChild(ed);
      bloco.appendChild(acoes);
      info.appendChild(bloco);
    });
  }

  function renderizar() {
    if (!dados) return;
    Object.keys(dados.totais).forEach(function (k) {
      var alvo = raiz.querySelector('[data-tot="' + k + '"]');
      if (alvo) alvo.textContent = dados.totais[k];
    });
    renderizarLista();
    renderizarMapa();
  }

  // ---------- edição e inclusão ----------
  var dialogo = $('[data-dialogo]');
  var form = $('[data-form-passageiro]');

  function abrirDialogo(p) {
    editando = p;
    $('[data-dlg-titulo]', dialogo).textContent = p ? 'Editar passageiro' : 'Incluir passageiro';
    $('[data-dlg-aviso]', dialogo).textContent = admin
      ? (p && (p.incluido_guia || Object.keys(p.campos).some(function (c) { return p.campos[c].g; })) ? 'Os campos com ajuste do guia mostram o valor original abaixo.' : '')
      : 'Sua alteração aparece destacada na lista e a coordenação recebe um e-mail.';
    $('[data-dlg-erro]', dialogo).hidden = true;
    CAMPOS_FORM.forEach(function (c) {
      var campo = form.elements[c];
      var dado = p ? p.campos[c] : null;
      var valor = dado ? (c === 'nascimento' || c === 'telefone' ? dado.v : dado.bruto) : '';
      // Valor antigo que não está na lista (ex.: embarque de antes da regra): aparece para ser trocado.
      if (campo.tagName === 'SELECT' && valor && !Array.prototype.some.call(campo.options, function (o) { return o.value === valor; })) {
        var op = el('option', null, valor + ' (não cadastrado)');
        op.value = valor;
        campo.appendChild(op);
      }
      campo.value = valor;
      var orig = form.querySelector('[data-original="' + c + '"]');
      orig.hidden = !(admin && dado && dado.g);
      orig.textContent = dado && dado.g ? 'Original: ' + (dado.o || '(vazio)') : '';
    });
    var remover = $('[data-remover]', dialogo);
    var incorporar = $('[data-incorporar]', dialogo);
    if (remover) remover.hidden = !p;
    if (incorporar) incorporar.hidden = !(p && (p.incluido_guia || Object.keys(p.campos).some(function (c) { return p.campos[c].g; })));
    if (dialogo.showModal) dialogo.showModal(); else dialogo.setAttribute('open', '');
    form.elements.nome.focus();
  }

  function fecharDialogo() {
    if (dialogo.close) dialogo.close(); else dialogo.removeAttribute('open');
    editando = null;
  }

  function erroDialogo(msg) {
    var e = $('[data-dlg-erro]', dialogo);
    e.textContent = msg;
    e.hidden = false;
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var campos = {};
    CAMPOS_FORM.forEach(function (c) { campos[c] = form.elements[c].value; });
    var url = editando ? base + '/' + editando.id + '/editar' : urlIncluir;
    var salvar = $('[data-salvar]', dialogo);
    salvar.disabled = true;
    post(url, campos)
      .then(function () {
        fecharDialogo();
        avisar(admin ? 'Passageiro salvo.' : 'Alteração salva. A coordenação foi avisada.');
        carregar();
      })
      .catch(function (e) { erroDialogo(e.definitivo ? e.message : 'Sem conexão. Tente de novo quando tiver sinal.'); })
      .then(function () { salvar.disabled = false; });
  });
  $('[data-fechar]', dialogo).addEventListener('click', fecharDialogo);

  var botaoRemover = $('[data-remover]', dialogo);
  if (botaoRemover) {
    botaoRemover.addEventListener('click', function () {
      if (!editando || !window.confirm('Remover ' + editando.campos.nome.v + ' da lista?')) return;
      post(base + '/' + editando.id + '/remover', {}).then(function () { fecharDialogo(); carregar(); })
        .catch(function (e) { erroDialogo(e.message); });
    });
  }
  var botaoIncorporar = $('[data-incorporar]', dialogo);
  if (botaoIncorporar) {
    botaoIncorporar.addEventListener('click', function () {
      if (!editando) return;
      post(base + '/' + editando.id + '/incorporar', {}).then(function () {
        fecharDialogo(); avisar('Ajustes incorporados ao cadastro.'); carregar();
      }).catch(function (e) { erroDialogo(e.message); });
    });
  }
  $('[data-incluir-abrir]').addEventListener('click', function () { abrirDialogo(null); });

  // ---------- abas, filtros e ciclo de atualização ----------
  raiz.querySelectorAll('[data-aba]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      raiz.querySelectorAll('[data-aba]').forEach(function (b) { b.setAttribute('aria-selected', b === botao ? 'true' : 'false'); });
      raiz.querySelectorAll('[data-painel]').forEach(function (p) { p.hidden = p.dataset.painel !== botao.dataset.aba; });
    });
  });
  $('[data-busca]').addEventListener('input', function () { if (dados) renderizarLista(); });
  $('[data-filtro]').addEventListener('change', function () { if (dados) renderizarLista(); });

  carregar();
  enviarFila();
  setInterval(function () {
    if (document.visibilityState === 'visible' && !(dialogo.open)) {
      enviarFila();
      carregar();
    }
  }, INTERVALO);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') carregar(); });
  window.addEventListener('online', function () { enviarFila(); carregar(); });
})();
