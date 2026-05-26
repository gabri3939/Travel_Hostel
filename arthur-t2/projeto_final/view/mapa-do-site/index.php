<?php include ROOT . '/view/layouts/header.php'; ?>

<section style="padding:3rem 1rem;max-width:900px;margin:0 auto;">
  <h1 style="margin-bottom:2rem;font-size:1.8rem;">Mapa do Site</h1>

  <div style="margin-bottom:2rem;">
    <h2 style="font-size:1rem;color:#3b82f6;border-bottom:2px solid #e5e7eb;padding-bottom:.4rem;margin-bottom:.8rem;">
      <i class="fa-solid fa-house"></i> Páginas Principais
    </h2>
    <ul style="list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;">
      <li><a href="<?php echo URL_BASE; ?>/">Início</a></li>
      <li><a href="<?php echo URL_BASE; ?>/hostels">Todos os Hostels</a></li>
      <li><a href="<?php echo URL_BASE; ?>/login">Login</a></li>
      <li><a href="<?php echo URL_BASE; ?>/cadastro">Cadastro</a></li>
      <li><a href="<?php echo URL_BASE; ?>/politica-privacidade">Política de Privacidade</a></li>
    </ul>
  </div>

  <?php if (!empty($categorias)): ?>
  <div style="margin-bottom:2rem;">
    <h2 style="font-size:1rem;color:#3b82f6;border-bottom:2px solid #e5e7eb;padding-bottom:.4rem;margin-bottom:.8rem;">
      <i class="fa-solid fa-tags"></i> Hostels por Categoria
    </h2>
    <ul style="list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.4rem;">
      <?php foreach ($categorias as $cat): ?>
      <li><a href="<?php echo URL_BASE; ?>/hostels/categoria/<?php echo htmlspecialchars($cat['slug']); ?>">
        <?php echo htmlspecialchars($cat['nome']); ?>
      </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <?php if (!empty($hostels)): ?>
  <div>
    <h2 style="font-size:1rem;color:#3b82f6;border-bottom:2px solid #e5e7eb;padding-bottom:.4rem;margin-bottom:.8rem;">
      <i class="fa-solid fa-bed"></i> Hostels
    </h2>
    <ul style="list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem;">
      <?php foreach ($hostels as $h): ?>
      <li><a href="<?php echo URL_BASE; ?>/hostel/<?php echo htmlspecialchars($h['slug']); ?>">
        <?php echo htmlspecialchars($h['nome']); ?>
      </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <p style="margin-top:2rem;font-size:.83rem;color:#aaa;">
    Sitemap XML para motores de busca:
    <a href="<?php echo URL_BASE; ?>/sitemap.xml" target="_blank"><?php echo URL_BASE; ?>/sitemap.xml</a>
  </p>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
