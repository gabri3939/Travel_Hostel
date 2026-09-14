<?php
$titulo = 'Blog - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:680px;">
    <div class="auth-header">
      <h1>Blog</h1>
      <p>Em breve: dicas de viagem, guias de cidades e novidades da plataforma.</p>
    </div>

    <div class="policy-content" style="text-align:center;">
      <i class="fa-solid fa-pen-nib" style="font-size:2.5rem;color:var(--color-primary-1);margin-bottom:12px;display:block;"></i>
      <p>Estamos preparando conteúdos sobre destinos, economia na viagem e como aproveitar melhor a experiência em hostels. Volte em breve!</p>
      <a href="<?php echo routeUrl('hostels'); ?>" class="btn btn-primary" style="margin-top:12px;">Explorar hostels enquanto isso</a>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
