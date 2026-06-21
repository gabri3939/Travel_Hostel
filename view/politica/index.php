<?php
// Pagina de politica de privacidade para o projeto Travel Hostel.
$titulo = 'Política de Privacidade - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <h1>Política de Privacidade</h1>
      <p>Esta página explica de forma clara como o Travel Hostel trata os dados dos usuários.</p>
    </div>

    <div class="policy-content">
      <style>
        .policy-content table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
        }
        .policy-content table td {
          border: 1px solid #d1d5db;
          padding: 12px 14px;
          vertical-align: top;
        }
        .policy-content table tr:nth-child(odd) {
          background-color: #f8fafc;
        }
        .policy-content table td:first-child {
          width: 28%;
          font-weight: 600;
          color: #111827;
          background-color: #f4f6f8;
        }
      </style>
      <h2>Dados que coletamos</h2>
      <p>Coletamos apenas o que é necessário para criar seu cadastro e manter a conta ativa.</p>

      <table>
        <tr>
          <td><strong>Nome</strong></td>
          <td>Usado para identificar o usuário no sistema e personalizar a conta.</td>
        </tr>
        <tr>
          <td><strong>Email</strong></td>
          <td>Usado para login, verificação de conta e comunicação importante.</td>
        </tr>
        <tr>
          <td><strong>CPF</strong></td>
          <td>Usado apenas para validação básica de cadastro e segurança.</td>
        </tr>
        <tr>
          <td><strong>CEP</strong></td>
          <td>Usado para preencher endereço de forma mais rápida e melhorar a experiência.</td>
        </tr>
      </table>

      <h2>Como usamos os dados</h2>
      <table>
        <tr>
          <td><strong>Conta</strong></td>
          <td>Para criar e proteger seu acesso ao Travel Hostel.</td>
        </tr>
        <tr>
          <td><strong>Comunicação</strong></td>
          <td>Para enviar avisos sobre cadastro, verificação e atualizações importantes.</td>
        </tr>
        <tr>
          <td><strong>Segurança</strong></td>
          <td>Para prevenir acessos não autorizados e manter a conta segura.</td>
        </tr>
      </table>

      <h2>O que não fazemos</h2>
      <p>Para manter sua privacidade, não usamos dados que não sejam necessários para o serviço.</p>
      <table>
        <tr>
          <td><strong>Não coletamos</strong></td>
          <td>Informações de biometria ou dados financeiros avançados.</td>
        </tr>
        <tr>
          <td><strong>Não compartilhamos</strong></td>
          <td>Seus dados com grandes empresas ou redes de publicidade.</td>
        </tr>
        <tr>
          <td><strong>Não usamos</strong></td>
          <td>Marketing complexo que exija análise pesada do seu comportamento.</td>
        </tr>
      </table>

      <h2>Segurança dos dados</h2>
      <p>Usamos práticas de segurança para manter suas informações protegidas. As senhas são armazenadas de forma segura e o acesso é controlado.</p>

      <h2>Seus direitos</h2>
      <table>
        <tr>
          <td><strong>Correção</strong></td>
          <td>Você pode pedir para atualizar seus dados se algo estiver incorreto.</td>
        </tr>
        <tr>
          <td><strong>Exclusão</strong></td>
          <td>Você pode solicitar a exclusão de seus dados quando quiser.</td>
        </tr>
        <tr>
          <td><strong>Contato</strong></td>
          <td>Entre em contato para receber suporte sobre sua conta.</td>
        </tr>
      </table>

      <h2>Atualizações desta política</h2>
      <p>Esta política pode ser atualizada para melhorar o serviço ou cumprir novas regras. As mudanças serão publicadas nesta mesma página.</p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
