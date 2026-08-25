# Plano de teste - Arthur

# Testes do Arthur

Copie cada linha para a planilha.

| Linha | Rota                          | O que fazer                                                                 | Mensagem/resultado                                        | Depois                                    |
| ----- | ----------------------------- | --------------------------------------------------------------------------- | --------------------------------------------------------- | ----------------------------------------- |
| 3     | `router.php?pagina=cadastro`  | Digitar um nome com 2 letras, por exemplo `Jo`.                             | Deve aparecer: `O nome deve ter pelo menos 3 caracteres.` | Tirar print da mensagem.                  |
| 6     | `router.php?pagina=anfitriao` | Colocar mais de 500 caracteres na descricao do hostel.                      | A descricao deve ficar com no maximo 500 caracteres.      | Entrar como anfitriao e testar pela tela. |
| 7     | `router.php?pagina=anfitriao` | Enviar uma descricao com mais de 500 caracteres ignorando o limite da tela. | O banco deve receber somente 500 caracteres.              | Conferir no banco.                        |
| 9     | `router.php?pagina=cadastro`  | Digitar CEP, CPF e telefone.                                                | Os campos devem aplicar as mascaras automaticamente.      | Tirar print dos campos formatados.        |
| 12    | `router.php?pagina=cadastro`  | Tentar cadastrar deixando nome, e-mail ou senha vazios.                     | O formulario nao deve ser enviado e deve mostrar erro.    | Tirar print do bloqueio.                  |

## O que ja foi testado

- Linha 3: aprovado.
- Linhas 6 e 7: aprovado no codigo; falta testar pela tela e conferir no banco.
- Linha 9: aprovado.
- Linha 12: aprovado.

## O que falta fazer

1. Tirar os prints.
2. Testar a descricao pela area do anfitriao.
3. Conferir no banco se a descricao ficou com 500 caracteres.
4. Colocar os resultados e prints na planilha.

- Linha 3: `view/cadastro/index.php; controller/usuarioController.php`
