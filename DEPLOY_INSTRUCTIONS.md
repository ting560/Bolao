# 🚀 Deploy para Produção - Bolão Pro

## 📁 Arquivos para Upload

Copie **TODOS** os arquivos da pasta `dist/` para o seu servidor:

```
dist/
├── index.html          ← Arquivo principal (CORRIGIDO)
├── assets/
│   └── index-DU06qEGA.js  ← Código JavaScript compilado
```

## 🔧 Configurações Importantes

### 1. Estrutura do Servidor
```
/seu-dominio.com/
├── index.html          ← Deve estar na raiz
├── assets/             ← Pasta com JS compilado
│   └── index-DU06qEGA.js
```

### 2. Configuração .htaccess (Apache)
Se estiver usando Apache, crie um arquivo `.htaccess` na raiz:

```apache
# Habilitar mod_rewrite
RewriteEngine On

# Redirecionar todas as rotas para index.html (para SPA)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ /index.html [L,QSA]

# Cache estático
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
```

### 3. Configuração nginx
Se estiver usando nginx:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /caminho/para/seus/arquivos;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 🛠️ Solução de Problemas Comuns

### Erro 404 em arquivos CSS/JS
**Problema**: `GET /index.css 404` ou `GET /index.tsx 404`

**Solução**: 
1. Verifique se copiou a pasta `assets/` completa
2. Confirme que o `index.html` está na raiz correta
3. Verifique permissões de leitura dos arquivos

### Erro Tailwind CSS CDN
**Problema**: Aviso sobre Tailwind via CDN

**Solução**: Já resolvido! O novo `index.html` usa estilos inline.

### Erros de Import Map
**Problema**: Erros com ESM.SH

**Solução**: Já resolvido! Removido o import map problemático.

## ✅ Checklist de Deploy

- [ ] Copiar pasta `dist/` completa para o servidor
- [ ] Verificar que `index.html` está na raiz
- [ ] Confirmar que pasta `assets/` existe com o arquivo JS
- [ ] Testar acesso direto aos arquivos: `seusite.com/assets/index-DU06qEGA.js`
- [ ] Verificar que não há erros 404 no console
- [ ] Testar todas as funcionalidades do sistema

## 🎯 URL Final

Após upload, seu sistema estará disponível em:
`https://www.radiopositivafm.com.br/bolao/`

Ou qual for o caminho que você configurar no seu servidor.

## 🆘 Suporte

Se continuar tendo problemas:
1. Verifique o console do navegador (F12)
2. Confirme que todos os arquivos foram uploadados
3. Verifique permissões do servidor (chmod 644 para arquivos, 755 para pastas)