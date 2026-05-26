# 📧 COMO CONFIGURAR A VERIFICAÇÃO DE EMAIL

## ⚡ Opção 1: Gmail (Mais Simples para Testes)

1. **Ative a autenticação de 2 fatores** em sua conta Google
2. **Gere uma senha de app** em: https://myaccount.google.com/apppasswords
3. **Atualize o arquivo `.env`:**
   ```
   MAIL_USERNAME=seu_email@gmail.com
   MAIL_PASSWORD=a_senha_gerada_aqui_16_caracteres
   MAIL_FROM=seu_email@gmail.com
   ```

## ⚡ Opção 2: Mailtrap (Recomendado para Testes)

1. **Cadastre-se** em https://mailtrap.io
2. **Crie uma inbox de teste**
3. **Copie as credenciais SMTP** (Settings → SMTP Settings)
4. **Atualize o arquivo `.env`:**
   ```
   MAIL_HOST=live.smtp.mailtrap.io
   MAIL_USERNAME=seu_username@mailtrap.io
   MAIL_PASSWORD=sua_chave_api
   MAIL_PORT=587
   MAIL_SMTP_SECURE=tls
   ```
5. **Verifique os emails recebidos** no painel do Mailtrap

## ✅ Teste Seu Email

1. Acesse: `http://localhost/projeto_final/controller/router.php?pagina=cadastro`
2. Preencha o formulário de cadastro
3. Clique em "Criar Conta"
4. **Se funcionar:** Você será redirecionado para a página de verificação
5. **Se não funcionar:** Verifique os logs em `C:\xampp\logs\php_error.log`

## 🐛 Debug (Se Ainda Não Funcionar)

**Verifique os logs:**
```
C:\xampp\logs\php_error.log  ← Erros do PHP
```

**Procure por mensagens de erro como:**
- `[EMAIL ERROR] Credenciais de email não configuradas`
- `[EMAIL ERROR] Falha ao enviar código` + detalhes do erro
- `[EMAIL SUCCESS] Código de verificação enviado`

## ❓ Perguntas?

- **Gmail diz "app password não gerada"?** Confirme se tem 2FA ativado
- **Mailtrap mostra email não recebido?** Verifique o console de debug do Mailtrap
- **Error "Connection could not be established"?** Firewall bloqueando porta 587

---

**Próximas melhorias sugeridas:**
- [ ] Adicionar resend de código
- [ ] Armazenar código em banco de dados (não apenas sessão)
- [ ] Template HTML mais profissional para o email
