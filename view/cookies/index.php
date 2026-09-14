<?php
$titulo = 'Política de Cookies - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-header">
      <h1>Política de Cookies</h1>
      <p>Como o Travel Hostel usa cookies para manter você conectado com segurança.</p>
    </div>

    <div class="policy-content">
      <style>
        .policy-content table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .policy-content table td { border: 1px solid #d1d5db; padding: 12px 14px; vertical-align: top; }
        .policy-content table tr:nth-child(odd) { background-color: #f8fafc; }
        .policy-content table td:first-child { width: 28%; font-weight: 600; color: #111827; background-color: #f4f6f8; }
      </style>

      <h2>O que são cookies</h2>
      <p>Cookies são pequenos arquivos que um site guarda no seu navegador para lembrar informações entre uma página e outra, como o fato de você estar logado.</p>

      <h2>Quais cookies usamos</h2>
      <table>
        <tr>
          <td>Cookie de sessão (PHPSESSID)</td>
          <td>Essencial para manter você conectado após o login e lembrar itens como sua reserva em andamento. Expira automaticamente após 15 minutos de inatividade ou quando você sai da conta.</td>
        </tr>
      </table>
      <p>Não usamos cookies de publicidade, redes sociais ou rastreamento de terceiros.</p>

      <h2>Como gerenciar cookies</h2>
      <p>Você pode bloquear ou apagar cookies nas configurações do seu navegador. Como o cookie de sessão é essencial para o funcionamento do site, bloqueá-lo impede o login e a realização de reservas.</p>

      <h2>Atualizações desta política</h2>
      <p>Esta política pode ser atualizada caso o funcionamento do site mude. A versão vigente estará sempre disponível nesta página.</p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
