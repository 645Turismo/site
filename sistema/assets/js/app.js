// Comportamentos de interface do sistema de guias. Sem dependências; a CSP bloqueia scripts em linha.
(function () {
  'use strict';

  // Máscara de CPF: 000.000.000-00
  document.querySelectorAll('[data-mascara="cpf"]').forEach(function (campo) {
    campo.addEventListener('input', function () {
      var d = campo.value.replace(/\D/g, '').slice(0, 11);
      campo.value = d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    });
  });

  // Máscara de CNPJ: 00.000.000/0000-00
  document.querySelectorAll('[data-mascara="cnpj"]').forEach(function (campo) {
    campo.addEventListener('input', function () {
      var d = campo.value.replace(/\D/g, '').slice(0, 14);
      campo.value = d.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
    });
  });

  // CEP: preenche rua, bairro, cidade e estado (ViaCEP)
  document.querySelectorAll('[data-cep]').forEach(function (campo) {
    campo.addEventListener('blur', function () {
      var cep = campo.value.replace(/\D/g, '');
      if (cep.length !== 8) return;
      var form = campo.form;
      fetch('https://viacep.com.br/ws/' + cep + '/json/').then(function (r) { return r.json(); }).then(function (d) {
        if (!d || d.erro) return;
        var preencher = function (sel, valor) { var c = form.querySelector(sel); if (c && !c.value && valor) c.value = valor; };
        preencher('[data-cep-logradouro]', d.logradouro);
        preencher('[data-cep-bairro]', d.bairro);
        preencher('[data-cep-cidade]', d.localidade);
        var uf = form.querySelector('[data-cep-uf]');
        if (uf && d.uf) uf.value = d.uf;
      }).catch(function () { /* sem internet: preenchimento manual */ });
    });
  });

  // Disponibilidade: tocar no dia leva a data para o formulário
  document.querySelectorAll('[data-dia]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      var campo = document.querySelector('[data-agenda-data]');
      if (!campo) return;
      campo.value = botao.dataset.dia;
      document.querySelectorAll('.cal-dia.selecionado').forEach(function (b) { b.classList.remove('selecionado'); });
      botao.classList.add('selecionado');
      var ate = document.querySelector('[data-agenda-ate]');
      if (ate && ate.value && ate.value < campo.value) ate.value = '';
      if (window.innerWidth < 1000) document.getElementById('marcar').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // Busca nas perguntas frequentes
  var buscaFaq = document.querySelector('[data-busca-faq]');
  if (buscaFaq) {
    var normalizar = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    buscaFaq.addEventListener('input', function () {
      var termo = normalizar(buscaFaq.value.trim());
      var visiveis = 0;
      document.querySelectorAll('[data-faq] .faq-item').forEach(function (item) {
        var ok = !termo || normalizar(item.textContent).indexOf(termo) !== -1;
        item.hidden = !ok;
        if (ok) visiveis++;
        if (termo && ok) item.open = true;
      });
      var vazio = document.querySelector('[data-faq-vazio]');
      if (vazio) vazio.hidden = visiveis > 0;
    });
  }

  // Recado: escolhe para quem (todos, função ou viagem)
  var publicoAviso = document.querySelector('[data-publico-aviso]');
  if (publicoAviso) {
    var atualizarPublico = function () {
      document.querySelectorAll('[data-publico]').forEach(function (c) { c.hidden = c.dataset.publico !== publicoAviso.value; });
    };
    publicoAviso.addEventListener('change', atualizarPublico);
    atualizarPublico();
  }

  // Mostrar/ocultar senha
  document.querySelectorAll('[data-alternar-senha]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      var campo = botao.parentElement.querySelector('input');
      var mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
      botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
      botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });
  });

  // Confirmação antes de enviar formulários sensíveis
  document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirmar'))) {
        ev.preventDefault();
      }
    });
  });

  // Evita duplo envio
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (ev.defaultPrevented) return;
      if (form.dataset.enviando) { ev.preventDefault(); return; }
      form.dataset.enviando = '1';
      setTimeout(function () { delete form.dataset.enviando; }, 4000);
    });
  });

  // Mantém o item ativo do menu visível no celular
  var ativo = document.querySelector('.app-nav a.ativo');
  if (ativo && ativo.scrollIntoView) {
    ativo.scrollIntoView({ block: 'nearest', inline: 'center' });
  }

  // Tabelas no celular viram cartões: cada célula recebe o nome da coluna (exibido pelo CSS).
  document.querySelectorAll('table.tabela').forEach(function (tabela) {
    var rotulos = Array.prototype.map.call(tabela.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
    tabela.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (rotulos[i]) td.dataset.rotulo = rotulos[i];
      });
    });
  });

  // Painel "Mais" da barra de abas no celular
  var botaoMais = document.querySelector('[data-folha]');
  var folha = document.getElementById('folha');
  if (botaoMais && folha) {
    botaoMais.addEventListener('click', function () {
      var abrir = folha.hidden;
      folha.hidden = !abrir;
      botaoMais.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    });
    document.addEventListener('click', function (ev) {
      if (!folha.hidden && !folha.contains(ev.target) && !botaoMais.contains(ev.target)) {
        folha.hidden = true;
        botaoMais.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Formulário da viagem/tour: mostra só os campos que fazem sentido para o veículo e o pernoite escolhidos
  var formViagem = document.querySelector('[data-form-viagem]');
  if (formViagem) {
    var veiculo = formViagem.querySelector('[data-veiculo]');
    var atualizarCampos = function () {
      var tipo = veiculo.value;
      var opcao = veiculo.options[veiculo.selectedIndex];
      formViagem.querySelectorAll('[data-com-veiculo]').forEach(function (c) { c.hidden = !tipo; });
      formViagem.querySelectorAll('[data-so-outro]').forEach(function (c) { c.hidden = tipo !== 'outro'; });
      formViagem.querySelectorAll('[data-so-dd]').forEach(function (c) { c.hidden = tipo !== 'dd_64'; });
      var dica = formViagem.querySelector('[data-lugares-dica]');
      if (dica) dica.textContent = tipo && tipo !== 'outro' ? '(padrão: ' + opcao.dataset.lugares + ')' : (tipo === 'outro' ? '*' : '');
      var comPernoite = formViagem.querySelector('[data-pernoite]:checked');
      formViagem.querySelectorAll('[data-so-pernoite]').forEach(function (c) { c.hidden = !comPernoite || comPernoite.value !== '1'; });
    };
    // Origens: "+" acrescenta uma linha vazia; "×" remove (as duas primeiras ficam sempre).
    var listaOrigens = formViagem.querySelector('[data-origens]');
    var renumerarOrigens = function () {
      listaOrigens.querySelectorAll('[data-origem-linha]').forEach(function (linha, i) {
        linha.querySelector('.campo span').textContent = 'Origem ' + (i + 1) + (i === 0 ? ' *' : '');
        linha.querySelector('[data-origem-remover]').hidden = i < 2;
      });
    };
    formViagem.querySelector('[data-origem-adicionar]').addEventListener('click', function () {
      var linhas = listaOrigens.querySelectorAll('[data-origem-linha]');
      var nova = linhas[linhas.length - 1].cloneNode(true);
      nova.querySelectorAll('input').forEach(function (i) { i.value = ''; i.required = false; });
      nova.querySelector('input[type="text"]').placeholder = 'Outro ponto de embarque (opcional)';
      listaOrigens.appendChild(nova);
      renumerarOrigens();
      nova.querySelector('input[type="text"]').focus();
    });
    listaOrigens.addEventListener('click', function (ev) {
      var botao = ev.target.closest('[data-origem-remover]');
      if (botao) {
        botao.closest('[data-origem-linha]').remove();
        renumerarOrigens();
      }
    });

    veiculo.addEventListener('change', atualizarCampos);
    formViagem.querySelectorAll('[data-pernoite]').forEach(function (r) { r.addEventListener('change', atualizarCampos); });
    atualizarCampos();
  }

  // Tour de introdução do guia
  var tour = document.getElementById('tour');
  if (tour) {
    var passos = tour.querySelectorAll('[data-passo]');
    var progresso = tour.querySelector('[data-tour-progresso]');
    var proximo = tour.querySelector('[data-tour-proximo]');
    var atual = 0;

    var mostrar = function (i) {
      atual = i;
      passos.forEach(function (p, k) { p.hidden = k !== i; });
      if (progresso) progresso.style.width = ((i + 1) / passos.length * 100) + '%';
      proximo.textContent = i === passos.length - 1 ? 'Começar' : 'Próximo';
    };
    var abrir = function () {
      mostrar(0);
      tour.hidden = false;
      proximo.focus();
    };
    var fechar = function () {
      tour.hidden = true;
      if (tour.dataset.auto === '1') {
        tour.dataset.auto = '0';
        var dados = new FormData();
        dados.append('_csrf', tour.dataset.csrf);
        fetch('/guia/tour-visto', { method: 'POST', body: dados, credentials: 'same-origin' });
      }
    };

    proximo.addEventListener('click', function () {
      if (atual < passos.length - 1) mostrar(atual + 1); else fechar();
    });
    tour.querySelector('[data-tour-fechar]').addEventListener('click', fechar);
    tour.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') fechar(); });
    document.querySelectorAll('[data-tour-abrir]').forEach(function (b) { b.addEventListener('click', abrir); });
    if (tour.dataset.auto === '1') abrir();
  }
})();
