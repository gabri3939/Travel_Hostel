<?php
$titulo = 'Termos de Uso - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-header">
      <h1>Termos de Uso</h1>
      <p>Regras gerais para usar o Travel Hostel como viajante ou como anfitrião.</p>
    </div>

    <div class="policy-content">
      <style>
        .policy-content table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .policy-content table td { border: 1px solid #d1d5db; padding: 12px 14px; vertical-align: top; }
        .policy-content table tr:nth-child(odd) { background-color: #f8fafc; }
        .policy-content table td:first-child { width: 28%; font-weight: 600; color: #111827; background-color: #f4f6f8; }
      </style>

      <h2>1. Aceitação dos termos</h2>
      <p>Ao criar uma conta ou usar o Travel Hostel, você concorda com estes Termos de Uso e com a nossa <a href="<?php echo routeUrl('politica'); ?>">Política de Privacidade</a>. Se não concordar, não utilize a plataforma.</p>

      <h2>2. O que é o Travel Hostel</h2>
      <p>O Travel Hostel é uma plataforma que conecta viajantes a hostels e recintos cadastrados por anfitriões. Nós intermediamos a busca, a reserva e o pagamento, mas não somos os proprietários dos recintos anunciados.</p>

      <h2>3. Cadastro e conta</h2>
      <table>
        <tr><td>Idade mínima</td><td>Você deve ter capacidade civil para contratar (18 anos ou emancipado) para criar uma conta e realizar reservas.</td></tr>
        <tr><td>Dados verdadeiros</td><td>As informações de cadastro (nome, e-mail, CPF, telefone) devem ser verdadeiras. Contas com dados falsos podem ser suspensas.</td></tr>
        <tr><td>Segurança da conta</td><td>Você é responsável por manter sua senha em sigilo e por tudo que acontecer usando sua conta.</td></tr>
      </table>

      <h2>4. Reservas e pagamentos</h2>
      <table>
        <tr><td>Processamento</td><td>Os pagamentos são processados pelo PagBank, via cartão de crédito (com opção de parcelamento) ou PIX. O Travel Hostel não armazena os dados do seu cartão.</td></tr>
        <tr><td>Confirmação</td><td>A reserva só é confirmada após a aprovação do pagamento pelo PagBank.</td></tr>
        <tr><td>Cancelamentos e reembolsos</td><td>Cada caso é avaliado individualmente junto ao suporte, considerando o prazo até a data de check-in e as políticas do meio de pagamento utilizado.</td></tr>
      </table>

      <h2>5. Responsabilidades do anfitrião</h2>
      <p>Anfitriões devem manter as informações do recinto atualizadas e verdadeiras (preço, fotos, comodidades) e honrar as reservas confirmadas. Anúncios com informações enganosas podem ser rejeitados ou removidos pela administração.</p>

      <h2>6. Taxa da plataforma</h2>
      <p>O Travel Hostel retém uma taxa de serviço de 15% sobre o valor total de cada reserva paga, referente à intermediação, ao processamento do pagamento e à manutenção da plataforma. O restante (85%) é devido ao anfitrião do recinto. O valor líquido de cada reserva fica visível para o anfitrião no seu perfil.</p>

      <h2>7. Condutas proibidas</h2>
      <p>Não é permitido: burlar o sistema de pagamento, criar anúncios falsos, assediar outros usuários, ou tentar acessar contas de terceiros sem autorização.</p>

      <h2>8. Suspensão de conta</h2>
      <p>Contas que violarem estes termos podem ser suspensas ou removidas pela administração, a qualquer momento, sem aviso prévio em casos de fraude ou abuso.</p>

      <h2>9. Alterações nestes termos</h2>
      <p>Podemos atualizar estes Termos de Uso para refletir mudanças no serviço. A versão vigente estará sempre disponível nesta página.</p>

      <h2>10. Legislação aplicável</h2>
      <p>Estes termos são regidos pelas leis brasileiras. Dúvidas podem ser encaminhadas pela nossa <a href="<?php echo routeUrl('contato'); ?>">página de contato</a>.</p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
