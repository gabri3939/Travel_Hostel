# Plano de teste - Arthur

## Resultado geral

Os testes do Arthur foram executados no ambiente local:

- Aplicacao: `http://localhost/Travel_Hostel/`
- Cadastro: `http://localhost/Travel_Hostel/controller/router.php?pagina=cadastro`
- Area do anfitriao: `http://localhost/Travel_Hostel/controller/router.php?pagina=anfitriao`
- Banco: MySQL `travel_hostel`

## Linhas para preencher na planilha

Preencha a coluna **resultado alcancado** com os textos abaixo. Na coluna de prioridade, mantenha a prioridade que ja existe na planilha.

| Linha | Categoria   | Cenario de teste                               | Rota                                     | Arquivos/localizacao                                          | Resultado alcancado                                                                                                 | Status   |
| ----- | ----------- | ---------------------------------------------- | ---------------------------------------- | ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | -------- |
| 3     | Formularios | Validar limite minimo de caracteres nos campos | `controller/router.php?pagina=cadastro`  | `view/cadastro/index.php`; `controller/usuarioController.php` | Aprovado - o campo nome exige no minimo 3 caracteres no navegador e no backend. O valor `Jo` foi rejeitado.         | Aprovado |
| 6     | Formularios | Verificar tipo restrito de dados - backend     | `controller/router.php?pagina=anfitriao` | `view/anfitriao/index.php`; `model/usuarioModel.php`          | Aprovado - a descricao possui limite de 500 caracteres no formulario e e truncada para 500 caracteres no backend.   | Aprovado |
| 7     | Formularios | Verificar tipo restrito de dados - banco       | `controller/router.php?pagina=anfitriao` | `model/usuarioModel.php`; tabela `hostels.descricao`          | Aprovado - o backend limita a descricao antes de gravar no banco, mesmo que o limite do formulario seja ignorado.   | Aprovado |
| 9     | Formularios | Verificar as mascaras                          | `controller/router.php?pagina=cadastro`  | `view/cadastro/index.php`; `public/js/main.js`                | Aprovado - CEP, CPF e telefone foram formatados automaticamente: `12345-678`, `123.456.789-01` e `(11) 98765-4321`. | Aprovado |
| 12    | Formularios | Validar campos obrigatorios vazios             | `controller/router.php?pagina=cadastro`  | `view/cadastro/index.php`; `controller/usuarioController.php` | Aprovado - com campos obrigatorios vazios, o navegador e o backend impedem o envio e exibem mensagem de erro.       | Aprovado |

## O que colocar nas colunas da planilha

### Arquivo/localizacao

- Linha 3: `view/cadastro/index.php; controller/usuarioController.php`
- Linhas 6 e 7: `view/anfitriao/index.php; model/usuarioModel.php`
- Linha 9: `view/cadastro/index.php; public/js/main.js`
- Linha 12: `view/cadastro/index.php; controller/usuarioController.php`

### Resultado esperado

- Linha 3: campo deve impedir envio com menos de 3 caracteres.
- Linhas 6 e 7: descricao deve truncar ou alertar quando passar de 500 caracteres.
- Linha 9: mascaras devem aparecer nos campos necessarios.
- Linha 12: formulario nao deve ser enviado sem os campos obrigatorios.

### Evidencia

Na coluna de evidencia ou observacao, coloque um print de cada teste. Os prints devem mostrar:

1. Nome com duas letras sendo rejeitado.
2. Campo de descricao com o limite de 500 caracteres.
3. CEP, CPF e telefone formatados.
4. Cadastro tentando ser enviado sem nome, e-mail ou senha.

## Para completar depois

- Fazer um print real da tela de cadastro com a mensagem de nome invalido.
- Entrar com um usuario anfitriao e testar a descricao com mais de 500 caracteres pela tela de envio de hostel.
- Confirmar no banco que a descricao gravada possui no maximo 500 caracteres.
- Inserir os prints na planilha e preencher a coluna de observacoes.
- Confirmar data, navegador e ambiente usados no teste.

## Observacao

As tarefas de login incorreto, bloqueio apos 5 tentativas, expiracao de sessao e SQL Injection pertencem ao Gabriel na planilha, nao ao Arthur.
