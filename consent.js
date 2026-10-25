/* Consentimento de cookies (LGPD, Lei 13.709/2018) do site 645turismo.com.br.
 * - Só o que é estritamente necessário roda sem consentimento.
 * - Google Analytics (estatísticas) e a detecção de idioma por localização (consulta de IP) só rodam com aceite.
 * - A escolha vale por 12 meses e pode ser mudada a qualquer momento em "Preferências de cookies".
 */
(function () {
  'use strict';

  var CHAVE = 'cookie_consent_645';
  var VERSAO = 1;
  var VALIDADE_MS = 365 * 24 * 60 * 60 * 1000;
  var GA_ID = 'G-RBH1J54BYW';

  var lang = (document.documentElement.lang || 'pt').slice(0, 2);
  if (lang !== 'en' && lang !== 'es') lang = 'pt';
  var prefixo = lang === 'pt' ? '' : '/' + lang;

  var T = {
    pt: {
      titulo: 'Sua privacidade importa',
      texto: 'Usamos cookies essenciais para o site funcionar. Com a sua permissão, também usamos estatísticas de uso (Google Analytics) e a sua localização aproximada pelo IP para sugerir o idioma. Você escolhe, e pode mudar depois.',
      saiba: 'Política de Privacidade e Cookies',
      aceitar: 'Aceitar todos', recusar: 'Recusar opcionais', personalizar: 'Personalizar', salvar: 'Salvar escolhas',
      essenciais: 'Essenciais', essenciaisD: 'Guardam o idioma e a sua escolha de cookies. Sempre ativos.',
      stats: 'Estatísticas', statsD: 'Google Analytics: nos mostra quais páginas são mais úteis. Não identifica você pelo nome.',
      geo: 'Idioma por localização', geoD: 'Consulta o seu IP no serviço ipapi.co para sugerir inglês ou espanhol.',
      rotulo: 'Aviso de cookies'
    },
    en: {
      titulo: 'Your privacy matters',
      texto: 'We use essential cookies so the site works. With your permission, we also use usage statistics (Google Analytics) and your approximate location from your IP address to suggest a language. You choose, and you can change it later.',
      saiba: 'Privacy and Cookie Policy',
      aceitar: 'Accept all', recusar: 'Reject optional', personalizar: 'Customize', salvar: 'Save choices',
      essenciais: 'Essential', essenciaisD: 'Store your language and your cookie choice. Always on.',
      stats: 'Statistics', statsD: 'Google Analytics: shows us which pages are most useful. It does not identify you by name.',
      geo: 'Language by location', geoD: 'Looks up your IP on the ipapi.co service to suggest English or Spanish.',
      rotulo: 'Cookie notice'
    },
    es: {
      titulo: 'Tu privacidad importa',
      texto: 'Usamos cookies esenciales para que el sitio funcione. Con tu permiso, también usamos estadísticas de uso (Google Analytics) y tu ubicación aproximada por IP para sugerir el idioma. Vos elegís, y podés cambiarlo después.',
      saiba: 'Política de Privacidad y Cookies',
      aceitar: 'Aceptar todas', recusar: 'Rechazar opcionales', personalizar: 'Personalizar', salvar: 'Guardar elecciones',
      essenciais: 'Esenciales', essenciaisD: 'Guardan el idioma y tu elección de cookies. Siempre activas.',
      stats: 'Estadísticas', statsD: 'Google Analytics: nos muestra qué páginas son más útiles. No te identifica por nombre.',
      geo: 'Idioma por ubicación', geoD: 'Consulta tu IP en el servicio ipapi.co para sugerir inglés o español.',
      rotulo: 'Aviso de cookies'
    }
  }[lang];

  function ler() {
    try {
      var c = JSON.parse(localStorage.getItem(CHAVE) || 'null');
      if (c && c.v === VERSAO && c.ts && Date.now() - c.ts < VALIDADE_MS) return c;
    } catch (e) {}
    return null;
  }

  function gravar(stats, geo) {
    var antes = ler();
    var c = { v: VERSAO, stats: !!stats, geo: !!geo, ts: Date.now() };
    try { localStorage.setItem(CHAVE, JSON.stringify(c)); } catch (e) {}
    aplicar(c, antes);
    return c;
  }

  function apagarCookiesGA() {
    var nomes = document.cookie.split(';').map(function (p) { return p.split('=')[0].trim(); })
      .filter(function (n) { return n === '_ga' || n.indexOf('_ga_') === 0 || n === '_gid' || n.indexOf('_gat') === 0; });
    var host = location.hostname.split('.');
    var dominios = [location.hostname, '.' + location.hostname];
    if (host.length > 2) dominios.push('.' + host.slice(-3).join('.'));
    dominios.push('.' + host.slice(-2).join('.'));
    nomes.forEach(function (n) {
      dominios.forEach(function (d) {
        document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + d;
      });
      document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    });
  }

  var gaCarregado = false;
  function carregarGA() {
    if (gaCarregado) return;
    gaCarregado = true;
    window['ga-disable-' + GA_ID] = false;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', GA_ID, { allow_google_signals: false, allow_ad_personalization_signals: false });
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(s);
  }

  function aplicar(c, antes) {
    if (c.stats) {
      carregarGA();
    } else {
      window['ga-disable-' + GA_ID] = true;
      if (antes && antes.stats) apagarCookiesGA();
    }
    // Primeira visita numa página em português, sem idioma definido: a detecção por localização roda no próximo carregamento.
    if (c.geo && (!antes || !antes.geo) && !jaReiniciou() && !jaTemIdioma() && /^pt/i.test(document.documentElement.lang || 'pt') && !/^(en|es)/i.test(navigator.language || '')) {
      location.reload();
    }
  }

  function jaReiniciou() {
    try {
      if (sessionStorage.getItem('cc_reload')) return true;
      sessionStorage.setItem('cc_reload', '1');
    } catch (e) { return true; }
    return false;
  }

  function jaTemIdioma() {
    try { return !!localStorage.getItem('site_lang'); } catch (e) { return false; }
  }

  // ---------- Interface ----------
  var faixa = null;

  function el(tag, attrs, html) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (html) n.innerHTML = html;
    return n;
  }

  function botao(texto, primario, onclick) {
    var b = el('button', { type: 'button' }, texto);
    b.style.cssText = 'flex:1 1 150px;min-height:44px;padding:12px 18px;border-radius:999px;cursor:pointer;font:700 12px/1.2 Montserrat,system-ui,sans-serif;letter-spacing:0.08em;text-transform:uppercase;'
      + (primario ? 'background:#53D9B2;color:#000;border:1px solid #53D9B2;' : 'background:transparent;color:#F2F5F3;border:1px solid rgba(242,245,243,0.55);');
    b.onmouseenter = function () { b.style.borderColor = '#FADA28'; if (primario) b.style.background = '#FADA28'; };
    b.onmouseleave = function () { b.style.borderColor = primario ? '#53D9B2' : 'rgba(242,245,243,0.55)'; if (primario) b.style.background = '#53D9B2'; };
    b.onclick = onclick;
    return b;
  }

  function linha(id, titulo, desc, marcado, travado) {
    var l = el('label', { 'for': id });
    l.style.cssText = 'display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-top:1px solid rgba(242,245,243,0.12);cursor:' + (travado ? 'default' : 'pointer') + ';';
    var i = el('input', { type: 'checkbox', id: id });
    i.checked = marcado;
    i.disabled = !!travado;
    i.style.cssText = 'margin-top:3px;width:18px;height:18px;flex:0 0 auto;accent-color:#53D9B2;';
    var t = el('span', {}, '<strong style="display:block;font-size:13px;color:#fff">' + titulo + '</strong><span style="display:block;margin-top:2px;font-size:12.5px;line-height:1.5;color:rgba(242,245,243,0.65)">' + desc + '</span>');
    l.appendChild(i);
    l.appendChild(t);
    return l;
  }

  function fechar() {
    if (faixa && faixa.parentNode) faixa.parentNode.removeChild(faixa);
    faixa = null;
  }

  function abrir(detalhes) {
    fechar();
    var atual = ler() || { stats: false, geo: false };
    faixa = el('div', { role: 'dialog', 'aria-modal': 'false', 'aria-label': T.rotulo, 'aria-live': 'polite' });
    faixa.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:2147483000;background:#05100C;color:#F2F5F3;border-top:1px solid rgba(83,217,178,0.5);box-shadow:0 -12px 40px rgba(0,0,0,0.55);font-family:Montserrat,system-ui,sans-serif;max-height:90vh;overflow:auto;';
    var caixa = el('div');
    caixa.style.cssText = 'max-width:1100px;margin:0 auto;padding:20px clamp(16px,4vw,32px) 22px;';
    caixa.appendChild(el('p', {}, '<strong style="font-size:15px;color:#fff">' + T.titulo + '</strong>'));
    caixa.lastChild.style.cssText = 'margin:0 0 8px;';
    var p = el('p', {}, T.texto + ' <a href="' + prefixo + '/privacidade#cookies" style="color:#53D9B2;text-decoration:underline">' + T.saiba + '</a>.');
    p.style.cssText = 'margin:0;font-size:13px;line-height:1.6;color:rgba(242,245,243,0.8);max-width:78ch;';
    caixa.appendChild(p);

    var opcoes = el('div');
    opcoes.style.cssText = 'margin-top:14px;display:' + (detalhes ? 'block' : 'none') + ';max-width:78ch;';
    opcoes.appendChild(linha('cc-ess', T.essenciais, T.essenciaisD, true, true));
    opcoes.appendChild(linha('cc-stats', T.stats, T.statsD, !!atual.stats, false));
    opcoes.appendChild(linha('cc-geo', T.geo, T.geoD, !!atual.geo, false));
    caixa.appendChild(opcoes);

    var acoes = el('div');
    acoes.style.cssText = 'display:flex;flex-wrap:wrap;gap:10px;margin-top:16px;';
    var btnPers = botao(detalhes ? T.salvar : T.personalizar, false, function () {
      if (opcoes.style.display === 'none') {
        opcoes.style.display = 'block';
        btnPers.textContent = T.salvar;
        faixa.scrollTop = faixa.scrollHeight;
      } else {
        gravar(document.getElementById('cc-stats').checked, document.getElementById('cc-geo').checked);
        fechar();
      }
    });
    acoes.appendChild(botao(T.recusar, false, function () { gravar(false, false); fechar(); }));
    acoes.appendChild(btnPers);
    acoes.appendChild(botao(T.aceitar, true, function () { gravar(true, true); fechar(); }));
    caixa.appendChild(acoes);
    faixa.appendChild(caixa);
    document.body.appendChild(faixa);
  }

  window.abrirPreferenciasCookies = function () { abrir(true); };

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-cookie-prefs]');
    if (a) {
      e.preventDefault();
      abrir(true);
    }
  });

  function iniciar() {
    var c = ler();
    if (c) {
      aplicar(c, c);
    } else {
      window['ga-disable-' + GA_ID] = true;
      abrir(false);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();
