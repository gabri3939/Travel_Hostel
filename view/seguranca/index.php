<?php
$titulo = 'Segurança - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-header">
      <h1>Segurança</h1>
      <p>O que fazemos para proteger sua conta e seus pagamentos, e como você pode se proteger também.</p>
    </div>

    <div class="policy-content">
      <style>
        .policy-content table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .policy-content table td { border: 1px solid #d1d5db; padding: 12px 14px; vertical-align: top; }
        .policy-content table tr:nth-child(odd) { background-color: #f8fafc; }
        .policy-content table td:first-child { width: 28%; font-weight: 600; color: #111827; background-color: #f4f6f8; }
      </style>

      <h2>Como protegemos sua conta</h2>
      <table>
        <tr><td>Senhas</td><td>Nunca guardamos sua senha em texto puro — ela é armazenada com hash criptográfico, então nem a equipe do Travel Hostel consegue vê-la.</td></tr>
        <tr><td>Verificação por e-mail</td><td>Todo novo cadastro precisa confirmar um código enviado por e-mail antes da conta ser criada.</td></tr>
        <tr><td>Bloqueio por tentativas</td><td>Após 5 tentativas de login incorretas, a conta fica temporariamente bloqueada para dificultar tentativas automatizadas de adivinhação de senha.</td></tr>
        <tr><td>Sessão com expiração</td><td>Sua sessão é encerrada automaticamente após 15 minutos de inatividade.</td></tr>
      </table>

      <h2>Como protegemos seu pagamento</h2>
      <table>
        <tr><td>Criptografia no navegador</td><td>Os dados do seu cartão são criptografados no seu próprio navegador antes de qualquer envio — nossos servidores nunca recebem o número completo do cartão.</td></tr>
        <tr><td>Processamento pelo PagBank</td><td>Todo pagamento (cartão ou PIX) é processado diretamente pelo PagBank, uma instituição de pagamento regulada.</td></tr>
      </table>

      <h2>Boas práticas para você</h2>
      <p>Nunca compartilhe sua senha com terceiros. Verifique se o endereço do site começa com "travelhostel" antes de digitar dados de login ou de cartão. Desconfie de mensagens pedindo confirmação de senha ou dados de cartão por e-mail — nós nunca solicitamos isso.</p>

      <h2>Encontrou uma vulnerabilidade?</h2>
      <p>Se você identificou um problema de segurança na plataforma, entre em contato imediatamente pela nossa <a href="<?php echo routeUrl('contato'); ?>">página de contato</a> descrevendo o que encontrou. Agradecemos o reporte responsável.</p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
