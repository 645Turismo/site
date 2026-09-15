# Site 645 Turismo

Site estático. Não precisa de build nem servidor: são arquivos HTML + a pasta `assets`.

## Páginas
- `index.html` — home
- `trabalhe-conosco.html` — trabalhe conosco (cadastro pelo Google Forms)
- `pesquisa-nps.html` — painel interno de NPS do Trem da República (sem link no menu)

## Publicar no GitHub Pages
1. Crie um repositório no GitHub (ex.: `645turismo-site`).
2. Suba o conteúdo desta pasta na raiz do repositório (arraste os arquivos em *Add file → Upload files*).
3. Em **Settings → Pages**, em *Source* escolha **Deploy from a branch**, branch `main` e pasta `/ (root)`. Salve.
4. Em poucos minutos o site fica em `https://SEU-USUARIO.github.io/645turismo-site/`.

## Usar o domínio 645turismo.com.br
1. Em **Settings → Pages → Custom domain**, escreva `645turismo.com.br` e salve.
2. No painel do seu domínio, crie os registros DNS:
   - `A` → 185.199.108.153, 185.199.109.153, 185.199.110.153, 185.199.111.153
   - `CNAME` de `www` → `SEU-USUARIO.github.io`
3. Volte em Pages e marque **Enforce HTTPS**.

## Manutenção
- Fotos ficam em `assets/`. Para trocar uma imagem, substitua o arquivo mantendo o mesmo nome.
- O painel de NPS lê a planilha do Google em tempo real; ela precisa continuar compartilhada como "qualquer pessoa com o link pode ver".
