# Deploy em producao

Este projeto foi preparado para publicar na VPS seguindo o mesmo padrao dos outros projetos em `/srv/projects`, com:

- proxy Nginx central em `/srv/proxy`
- stack Docker propria por projeto
- volumes persistentes em `/srv/volumes`
- certificado Let's Encrypt no host

## Arquivos principais

- `Dockerfile`
- `docker-compose.production.yml`
- `docker/nginx/default.conf`
- `docker/php/entrypoint.sh`
- `.env.production.example`
- `ops/production/proxy/lagoinha-porto-camp.sevenfaithtech.com.conf`
- `ops/production/systemd/lagoinha-porto-camp-deploy.service`
- `ops/production/systemd/lagoinha-porto-camp-deploy.timer`
- `.github/workflows/deploy-production.yml`

## Fluxo recomendado

1. Clonar o repositorio em `/srv/projects/lagoinha-porto-camp`
2. Criar `/srv/projects/lagoinha-porto-camp/.env.production`
3. Criar os volumes persistentes em `/srv/volumes/lagoinha-porto-camp`
4. Publicar a configuracao do proxy
5. Emitir o certificado para `lagoinha-porto-camp.sevenfaithtech.com`
6. Subir o stack com `docker compose -f docker-compose.production.yml up -d --build`
7. Ativar o timer do systemd para atualizar a branch `main` automaticamente

## Observacoes

- O `entrypoint` executa `migrate`, `db:seed` e `optimize` no container `app`.
- O `DatabaseSeeder` cria o administrador inicial com base nas variaveis `ADMIN_*`.
- Em ambiente nao local, `ADMIN_PASSWORD` precisa estar definido.
- O deploy automatico no servidor observa a branch `main` uma vez por minuto.
- O workflow do GitHub Actions e opcional; ele funciona se os secrets `PRODUCTION_HOST`, `PRODUCTION_USER` e `PRODUCTION_PASSWORD` forem configurados no repositorio.
