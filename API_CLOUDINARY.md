# API de Upload de Foto e Gerenciamento de Níveis

## Configuração Cloudinary

As credenciais do Cloudinary foram configuradas no arquivo `.env`:

```
CLOUDINARY_URL=lM4AlqNrpJEm32Ytgcqa6g4dPz0
CLOUDINARY_SECRET=rgtcBmPSJy94q-cxwSXHEHD_LNM
CLOUDINARY_CLOUD_NAME=arthur-t2
```

## Instalação de Dependências

Para usar o Cloudinary SDK, execute:

```bash
composer install
```

O arquivo `composer.json` foi atualizado para incluir:

- `cloudinary/cloudinary_php: ^2.0`

## Banco de Dados

A tabela `usuarios` foi atualizada com:

- **Campo `nivel`**: ENUM com valores `usuario`, `anfitriao`, `admin`
- **Default**: `usuario`
- **Exemplos de níveis**:
  - `usuario`: Usuário comum (pode fazer reservas)
  - `anfitriao`: Host que oferece hospedagem
  - `admin`: Administrador do sistema

## Endpoints da API

### 1. Upload de Foto para Cloudinary

**Endpoint:** `POST /index.php?pagina=api/upload-foto`

**Headers:**

```
Content-Type: multipart/form-data
```

**Parâmetros:**

- `foto` (file, obrigatório): Arquivo de imagem (JPG, PNG, GIF, WebP)
- Tamanho máximo: 5MB

**Autenticação:**

- Requer usuário autenticado (sessão ativa)

**Resposta de Sucesso (200):**

```json
{
  "sucesso": true,
  "mensagem": "Foto enviada com sucesso!",
  "url": "https://res.cloudinary.com/arthur-t2/image/upload/...",
  "public_id": "travel-hostel/usuarios/usuario_1_foto"
}
```

**Resposta de Erro (400/401/413):**

```json
{
  "sucesso": false,
  "mensagem": "Descrição do erro"
}
```

**Exemplo JavaScript:**

```javascript
const formData = new FormData();
formData.append("foto", document.getElementById("fotoInput").files[0]);

fetch("/index.php?pagina=api/upload-foto", {
  method: "POST",
  body: formData,
})
  .then((response) => response.json())
  .then((data) => {
    if (data.sucesso) {
      console.log("URL da foto:", data.url);
    } else {
      console.error("Erro:", data.mensagem);
    }
  });
```

---

### 2. Alterar Nível do Usuário (Admin)

**Endpoint:** `POST /index.php?pagina=api/alterar-nivel`

**Headers:**

```
Content-Type: application/json
```

**Body:**

```json
{
  "email": "usuario@exemplo.com",
  "nivel": "anfitriao"
}
```

**Parâmetros:**

- `email` (string, obrigatório): Email do usuário a ser atualizado
- `nivel` (string, obrigatório): Novo nível (`usuario`, `anfitriao`, `admin`)

**Autenticação:**

- Requer usuário autenticado com nível `admin`

**Resposta de Sucesso (200):**

```json
{
  "sucesso": true,
  "mensagem": "Nível atualizado com sucesso!",
  "email": "usuario@exemplo.com",
  "nivel": "anfitriao"
}
```

**Resposta de Erro:**

```json
{
  "sucesso": false,
  "mensagem": "Descrição do erro"
}
```

**Exemplo JavaScript:**

```javascript
const data = {
  email: "usuario@exemplo.com",
  nivel: "anfitriao",
};

fetch("/index.php?pagina=api/alterar-nivel", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify(data),
})
  .then((response) => response.json())
  .then((result) => {
    if (result.sucesso) {
      console.log("Nível atualizado!");
    } else {
      console.error("Erro:", result.mensagem);
    }
  });
```

---

### 3. Obter Nível do Usuário Autenticado

**Endpoint:** `GET /index.php?pagina=api/get-nivel`

**Autenticação:**

- Requer usuário autenticado (sessão ativa)

**Resposta de Sucesso (200):**

```json
{
  "sucesso": true,
  "email": "usuario@exemplo.com",
  "nivel": "usuario"
}
```

**Resposta de Erro (401):**

```json
{
  "sucesso": false,
  "mensagem": "Não autenticado."
}
```

**Exemplo JavaScript:**

```javascript
fetch("/index.php?pagina=api/get-nivel")
  .then((response) => response.json())
  .then((data) => {
    console.log("Nível do usuário:", data.nivel);
  });
```

---

## Fluxo de Integração

### Para Upload de Foto:

1. Usuário faz login
2. Navega para página de perfil
3. Seleciona uma foto via input file
4. JavaScript envia POST request para `/index.php?pagina=api/upload-foto`
5. Cloudinary recebe e armazena a foto
6. URL da foto é salva no banco em `usuarios.avatar`
7. Sessão do usuário é atualizada com `usuario_avatar`

### Para Alterar Nível:

1. Admin faz login
2. Navega para painel admin
3. Seleciona usuário e novo nível
4. JavaScript envia POST request para `/index.php?pagina=api/alterar-nivel`
5. Banco de dados atualiza o campo `nivel` do usuário

---

## Estrutura do Banco de Dados

```sql
ALTER TABLE usuarios ADD COLUMN nivel ENUM('usuario', 'anfitriao', 'admin') DEFAULT 'usuario';
```

**Tipos de usuário:**

- `usuario`: Pessoa buscando hospedagem
- `anfitriao`: Oferece hospedagem (pode administrar hostels)
- `admin`: Administrador do sistema

---

## Tratamento de Erros

| HTTP Status | Significado                                     |
| ----------- | ----------------------------------------------- |
| 200         | Sucesso                                         |
| 400         | Requisição inválida (arquivo, dados, formato)   |
| 401         | Não autenticado                                 |
| 403         | Sem permissão (não é admin para alterar níveis) |
| 413         | Arquivo muito grande                            |
| 500         | Erro no servidor                                |

---

## Observações

- As fotos são armazenadas no Cloudinary com transformação automática (500x500 crop)
- Apenas formatos suportados: JPG, PNG, GIF, WebP
- Tamanho máximo: 5MB
- URLs do Cloudinary são permanentes e seguras
- Cada usuário pode ter apenas 1 foto de perfil (é sobrescrita)
- A mudança de nível é imediata no banco de dados
- Para revogar acesso admin, altere o nível do usuário

---

## Troubleshooting

### "Cloudinary não configurado"

- Verifique se o `.env` contém `CLOUDINARY_URL` e `CLOUDINARY_SECRET`
- Execute `composer install` para instalar as dependências

### "Falha ao fazer upload"

- Verifique se o arquivo é uma imagem válida
- Verifique o tamanho (máximo 5MB)
- Verifique as permissões de pasta

### "Apenas administradores podem alterar níveis"

- Apenas usuários com `nivel = 'admin'` podem usar este endpoint
- Execute no banco: `UPDATE usuarios SET nivel = 'admin' WHERE email = 'seu-email@exemplo.com'`

---

## Suporte

Para mais informações sobre Cloudinary, visite:

- https://cloudinary.com/documentation
- https://github.com/cloudinary/cloudinary_php
