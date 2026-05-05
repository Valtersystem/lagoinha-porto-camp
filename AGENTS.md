# Regras de Desenvolvimento

Este arquivo guarda o contexto e as regras do projeto para manter consistencia durante o desenvolvimento.

## Contexto do Projeto

- Projeto: Lagoinha Porto Camp.
- Produto: sistema web/PWA para gestao de pessoas em um acampamento.
- Publico operacional: equipe responsavel por organizar aproximadamente 300 participantes.
- Stack principal: Laravel 13, PHP 8.3, Inertia, Vue 3, TypeScript, Vite e Tailwind CSS.
- Idioma padrao da interface e do conteudo: portugues.
- Fuso de referencia para datas e eventos: Europe/Lisbon.

## Objetivo

- Ajudar a equipe do acampamento a gerenciar participantes, presenca, check-in, check-out e quartos.
- Construir uma experiencia rapida e confiavel para uso durante o evento, inclusive em telas menores.
- Construir uma experiencia clara, moderna e confiavel para o projeto.
- Priorizar fluxos reais de uso antes de telas decorativas.
- Manter o codigo simples, consistente e facil de evoluir.

## Dominio do Produto

- O sistema deve considerar uma escala inicial de cerca de 300 pessoas.
- Participante e uma entidade central do sistema.
- Presenca, check-in e check-out sao fluxos operacionais essenciais.
- O gerenciamento de quartos deve indicar onde cada pessoa vai dormir.
- Acampamento e uma entidade central para organizar data, valor, lotes e pagamentos do evento.
- Usuarios devem ser vinculados explicitamente ao acampamento que vao participar.
- Todo usuario vinculado a um acampamento deve ter obrigacao de pagamento, independente do papel.
- O usuario nao precisa pagar o valor total de uma vez; o administrador deve registrar em quantas parcelas o pagamento sera feito.
- Somente o administrador pode liberar uma pessoa vinculada ao acampamento para ir sem pagar, registrando essa escolha no sistema.
- Telas de operacao devem priorizar busca rapida, filtros, leitura facil e acoes com poucos cliques.
- Dados de pessoas e hospedagem devem ser tratados com cuidado, evitando exposicao desnecessaria.
- Mudancas que afetem presenca, check-in, check-out, quartos ou pagamentos devem ser pensadas com impacto operacional no dia do acampamento.

## Usuarios, Papeis e Permissoes

- O sistema deve ter controle claro de usuarios, papeis e permissoes.
- Papeis iniciais:
  - Administrador: lider geral do acampamento, com acesso total ao projeto e a todos os CRUDs.
  - Monitor: participante do acampamento que tambem acompanha e monitora grupos.
  - Lider de equipe: usuario responsavel por uma equipe ou grupo especifico.
  - Participante: pessoa do acampamento com acesso limitado as informacoes e acoes relacionadas a sua propria participacao.
- O modelo de permissoes deve ser escalavel para permitir novos papeis no futuro sem reescrever a arquitetura.
- Evitar regras de permissao espalhadas de forma duplicada pelo frontend e backend.
- O backend deve ser a fonte final de autorizacao para qualquer acao sensivel.
- O frontend pode esconder ou desabilitar acoes conforme o papel do usuario, mas nunca deve ser a unica camada de seguranca.
- Qualquer nova tela ou acao deve considerar quais papeis podem visualizar, criar, editar, excluir ou executar aquela acao.
- A nomenclatura dos papeis deve ser clara para usuarios nao tecnicos.
- O administrador deve conseguir gerir usuarios e definir papeis.

## Regras Gerais

- Antes de alterar algo, entender a estrutura existente e seguir os padroes locais.
- Nao mexer em arquivos gerados ou dependencias, como `vendor/`, `node_modules/` e `public/build/`, salvo necessidade explicita.
- Manter alteracoes pequenas e relacionadas ao pedido atual.
- Nao introduzir bibliotecas novas sem motivo claro.
- Preferir nomes descritivos para componentes, paginas, controllers, models e migrations.
- Evitar refactors grandes junto com features pequenas.
- Preservar configuracoes, conteudo e alteracoes existentes feitas pelo usuario.

## Frontend

- Usar Vue 3 com Inertia seguindo a estrutura em `resources/js`.
- Usar Tailwind CSS como base de estilo.
- O PWA deve ser tratado como requisito central da experiencia mobile.
- O PWA usa `public/manifest.webmanifest`, `public/sw.js` e icones em `public/pwa/`.
- A instalacao PWA deve continuar leve e compativel com Vite 8; evitar plugin PWA que nao declare compatibilidade com a versao atual do Vite.
- O PWA deve ter navegacao parecida com aplicativo de celular, com menu inferior para as principais areas.
- Cores, fontes, raios, sombras, espacamentos e outros estilos-base devem ficar concentrados em arquivo(s) de configuracao do tema/design system.
- O arquivo `resources/css/theme.css` e a fonte principal dos tokens visuais globais do sistema.
- Evitar valores visuais soltos e repetidos diretamente nos componentes quando puderem virar tokens ou classes reutilizaveis.
- Mudancas globais de cor, fonte ou estilo devem ser feitas primeiro na configuracao central do tema.
- Priorizar responsividade em mobile e desktop.
- Evitar textos longos dentro de botoes, cards compactos ou elementos pequenos.
- Manter estados de loading, erro e vazio quando a tela depender de dados.
- Usar componentes reutilizaveis quando houver repeticao real.
- Nao concentrar todas as funcionalidades em uma unica pagina; separar o sistema em telas e rotas por fluxo de trabalho.
- A interface deve parecer produto final, nao landing page generica.

## Navegacao e Acessibilidade

- O menu inferior deve ser claro, rapido de usar e adequado para toque em celular.
- O layout autenticado deve usar `AuthenticatedLayout.vue` como shell principal, com lateral no desktop e menu inferior no mobile.
- Itens do menu devem ter rotulo visivel, estado ativo, area de toque confortavel e, quando usar icones, texto alternativo ou nome acessivel.
- A navegacao principal deve separar areas como painel, participantes, presenca, check-in/check-out, quartos e configuracoes conforme o crescimento do sistema.
- Cada pagina deve ter responsabilidade clara e nao misturar fluxos operacionais demais.
- Garantir boa navegabilidade com titulos claros, hierarquia visual, feedback de acao, estados de foco e caminho de retorno quando necessario.
- Acessibilidade deve ser considerada desde o inicio: contraste adequado, foco via teclado, labels em campos, botoes com nomes claros e estrutura semantica.
- O menu e as telas devem respeitar permissoes de usuario, mostrando apenas areas e acoes permitidas para cada papel.

## Backend

- Seguir convencoes do Laravel para rotas, controllers, requests, models e migrations.
- Inicialmente o backend e o frontend funcionam juntos na estrutura padrao Laravel + Inertia.
- O sistema deve ser desenvolvido com possibilidade futura de evoluir para API sem reescrever regras principais.
- Evitar colocar regras de negocio diretamente em componentes Vue ou views.
- Preferir manter regras importantes em controllers, requests, policies, models, services ou actions conforme a necessidade.
- Validar dados no backend, mesmo quando houver validacao no frontend.
- Usar migrations para mudancas de banco.
- Manter regras de negocio fora das views.
- Evitar consultas duplicadas ou carregamento desnecessario de relacionamentos.

## Arquitetura e Evolucao

- A primeira versao pode ser monolito Laravel com Inertia, mas a arquitetura deve permitir separacao futura em API.
- Separar responsabilidades desde o inicio: interface, autorizacao, validacao, regras de negocio e persistencia.
- Evitar dependencias fortes entre telas e estrutura interna do banco quando uma camada intermediaria simples resolver.
- Nomes de rotas, controllers e contratos de dados devem ser claros o bastante para uma futura camada de API.
- Antes de criar atalhos tecnicos, considerar se eles dificultam expor o mesmo fluxo via API no futuro.

## Banco de Dados

- Nomear tabelas e colunas de forma clara e consistente.
- Criar migrations reversiveis sempre que possivel.
- Usar seeds/factories quando forem uteis para desenvolvimento ou testes.
- Nao apagar dados ou alterar migrations antigas sem confirmar o impacto.

## Design e Conteudo

- Manter tom acolhedor, direto e em portugues.
- Evitar visual poluido: boa hierarquia, espacos consistentes e contraste legivel.
- Manter identidade visual consistente por meio de tokens de cor, tipografia e componentes reutilizaveis.
- Ao criar novas telas, reutilizar o tema central antes de criar estilos especificos.
- Nao usar blocos explicativos dentro da UI para descrever como a propria UI funciona.
- Imagens e elementos visuais devem ajudar a entender o evento, comunidade ou acao da pagina.

## Verificacao

Comandos uteis:

```bash
npm run build
composer test
```

Quando relevante, tambem verificar:

```bash
php artisan test
php artisan migrate
npm run dev
```

## Fluxo de Trabalho

- Ler este arquivo antes de iniciar novas tarefas.
- Atualizar este arquivo quando uma nova decisao importante de arquitetura, design ou produto for tomada.
- Ao finalizar uma alteracao, informar arquivos principais modificados e verificacoes executadas.
- Se algum ponto estiver ambiguo e puder afetar produto, dados ou experiencia do usuario, perguntar antes de seguir.
