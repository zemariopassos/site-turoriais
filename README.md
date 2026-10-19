# site-turoriais — 1.1.0

Plugin independente do tema para ensinar usuários a operar o WordPress.

## Instalação

1. No WordPress, abra **Plugins → Adicionar plugin → Enviar plugin**.
2. Envie `site-turoriais.zip`, instale e ative **site-turoriais**.
3. Abra **Tutoriais** no menu lateral.

## Vídeos locais

Em **Tutoriais → Adicionar tutorial**, informe título, instruções e clique em
**Enviar ou selecionar vídeo** para carregar um MP4 na Biblioteca de Mídia.
Publique a aula. O campo também aceita links individuais do YouTube e Vimeo.
O campo Ordem, em Atributos, controla a sequência dos vídeos locais.
Os limites de upload são os definidos pelo servidor do site.

## Playlist automática

Em **Tutoriais → Playlist do YouTube**, cole o endereço de uma playlist,
por exemplo `https://www.youtube.com/playlist?list=PL...`, e salve.
O endereço de um vídeo com o parâmetro `list` também é aceito.
O endereço do canal sozinho não determina qual playlist usar.

A playlist é exibida no player oficial do YouTube, acima das aulas locais.
O usuário escolhe as aulas pelo botão de lista do player; há também um link
para abrir a lista completa no YouTube. Não são criados posts individuais
para cada vídeo da playlist e não é necessária chave de API.

Vídeos adicionados, removidos ou reordenados na playlist passam a ser exibidos
ao abrir/recarregar a página, conforme a propagação das alterações pelo YouTube.
Não há sincronização por cron, limite de feed RSS ou cópia de arquivos para o site.
Use playlist pública ou não listada e vídeos com incorporação permitida.
Vídeos privados, removidos ou bloqueados pelo YouTube podem não reproduzir.
O acesso a vídeos não listados pelo YouTube não é protegido pelo login do WordPress.

Para remover a playlist, apague o campo e salve. Os MP4 continuam disponíveis.

## Acesso e dados existentes

Usuários com acesso ao painel (`read`) podem assistir. Apenas administradores
(`manage_options`) podem cadastrar, editar, excluir ou configurar as aulas.
Não há página pública, API REST ou arquivo público de tutoriais.
Os arquivos de mídia conservam as regras usuais de acesso da Biblioteca do WordPress.

Mantém o tipo de conteúdo `tutoriais`, o campo `_flaneur_tutorial_video_url` e
o endereço administrativo anterior para preservar os cadastros deste tema.
Não é preciso migrar os registros deste site. O ZIP contém somente o plugin:
para transferir aulas locais a outro site, transfira também posts e mídias.

Desativar ou excluir o plugin não apaga aulas, mídias ou configurações.
Não depende do tema Flaneur. Em cópias antigas desse tema, remova a inclusão
de `includes/php/tutoriais.php` antes de ativar o plugin para evitar menus duplicados.

## Serviço externo

Ao acessar uma biblioteca com playlist configurada ou vídeo externo,
o navegador conecta-se ao YouTube (ou Vimeo, para links individuais).
O player de YouTube usa o domínio `youtube-nocookie.com`.
Documentação: https://developers.google.com/youtube/player_parameters

Licença: GPL-2.0-or-later.

## Reutilizar e atualizar pelo GitHub

O repositório deve conter somente os arquivos deste plugin, sem o WordPress,
o tema, credenciais ou os arquivos de mídia dos sites.

Para instalar em outro site, baixe o ZIP de uma versão em **Releases** e envie
em **Plugins → Adicionar plugin → Enviar plugin**.

Para publicar uma alteração:

1. Altere o código e atualize a versão no cabeçalho de `site-turoriais.php`.
   Se alterar CSS ou JavaScript, atualize também a versão usada no enqueue.
2. Registre as mudanças em `CHANGELOG.md` e faça commit/push.
3. Gere um ZIP com a pasta `site-turoriais` contendo o PHP, `assets` e a documentação.
4. Crie uma Release no GitHub com a tag da versão, por exemplo `v1.0.1`,
   e anexe o ZIP `site-turoriais.zip`.

Para atualizar um site, envie o novo ZIP pelo instalador de plugins do WordPress
e escolha substituir a versão instalada. Os cadastros e as configurações são mantidos.

Desde a versão 1.1.0, o plugin consulta as Releases estáveis do repositório
`zemariopassos/site-turoriais` e integra as versões ao painel nativo de Plugins.
Quando publicarmos uma release mais recente com o anexo `site-turoriais.zip`,
o WordPress exibirá **Atualizar agora**. Não basta alterar arquivos na branch main:
é necessário publicar a Release com a tag da versão e o ZIP completo.

Na primeira instalação, envie o ZIP 1.1.0 manualmente. A partir dele, use o botão
**Atualizar agora** nas próximas versões. Em **Tutoriais → Atualizações**, você
pode conferir a conexão e clicar em **Verificar atualizações agora**.
As consultas usam cache de seis horas (uma hora em caso de falha), além da
verificação periódica do próprio WordPress. O plugin não ativa instalações
automáticas sem sua escolha. As opções nativas do WordPress continuam valendo.

O repositório público funciona sem token. Se você torná-lo privado futuramente,
adicione no `wp-config.php`, antes da linha final de configuração:

```php
define( 'SITE_TURORIAIS_GITHUB_TOKEN', 'SEU_TOKEN_DE_LEITURA' );
```

Use um token refinado restrito a esse repositório com Contents: Read-only.
Não inclua o token no plugin, no ZIP ou no GitHub. Se o token expirar ou não
tiver acesso, as atualizações ficam indisponíveis até corrigir a configuração;
a biblioteca de vídeos continua funcionando.

Para verificar versões e baixar atualizações, o servidor do site se conecta a
`api.github.com` e ao armazenamento de arquivos do GitHub. O ZIP é baixado por
HTTPS e seu SHA-256 é conferido quando fornecido pela API. O pacote deve conter
a pasta `site-turoriais`, incluindo `includes/github-updater.php` e `assets`.
