<?php
$titulo = 'Perguntas Frequentes - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-header">
      <h1>Perguntas Frequentes</h1>
      <p>Respostas rápidas sobre reservas, pagamento e conta.</p>
    </div>

    <div class="policy-content">
      <h2>Como funciona o pagamento da reserva?</h2>
      <p>Você escolhe entre cartão de crédito (com opção de parcelamento) ou PIX. O pagamento é processado pelo PagBank; a reserva só é confirmada depois que o pagamento é aprovado.</p>

      <h2>Meus dados de cartão ficam salvos?</h2>
      <p>Não. O número do cartão é criptografado no seu próprio navegador antes de ser enviado — o Travel Hostel nunca recebe nem armazena o número completo do cartão.</p>

      <h2>Por que preciso confirmar meu e-mail no cadastro?</h2>
      <p>O código enviado por e-mail confirma que o endereço informado é realmente seu, evitando cadastros com e-mails falsos ou de terceiros. Veja mais na <a href="<?php echo routeUrl('contato'); ?>">página de contato</a>.</p>

      <h2>Como faço para anunciar meu hostel?</h2>
      <p>No seu perfil, solicite acesso de anfitrião. Depois de aprovado pela administração, você poderá cadastrar recintos, que ficam visíveis ao público após uma segunda aprovação (agora do próprio recinto).</p>

      <h2>Posso editar ou desativar um recinto que cadastrei?</h2>
      <p>Sim. Na área do anfitrião você pode editar os dados do recinto a qualquer momento e desativá-lo quando quiser — ele deixa de aparecer para os viajantes sem apagar o histórico de reservas já feitas.</p>

      <h2>Como funcionam as avaliações?</h2>
      <p>Só quem tem uma reserva paga pode avaliar o hostel e o anfitrião correspondente, o que mantém as notas confiáveis.</p>

      <h2>Posso cancelar uma reserva?</h2>
      <p>Cancelamentos são analisados caso a caso. Consulte os <a href="<?php echo routeUrl('termos'); ?>">Termos de Uso</a> ou entre em contato.</p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
