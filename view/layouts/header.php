<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title><?php echo isset($titulo) ? htmlspecialchars($titulo) : 'Travel Hostel'; ?></title>

  <?php if (!empty($metaDescricao)): ?>
  <meta name="description" content="<?php echo htmlspecialchars($metaDescricao); ?>"/>
  <?php else: ?>
  <meta name="description" content="Travel Hostel - encontre e reserve os melhores hostels do Brasil com os melhores precos."/>
  <?php endif; ?>

  <?php
  $kwGlobais = 'hostel, hospedagem barata, mochileiro, hostel brasil';
  $kwFinal = !empty($palavrasChave) ? htmlspecialchars($palavrasChave) . ', ' . $kwGlobais : $kwGlobais;
  ?>
  <meta name="keywords" content="<?php echo $kwFinal; ?>"/>

  <?php if (!empty($urlCanonica)): ?>
  <link rel="canonical" href="<?php echo htmlspecialchars($urlCanonica); ?>"/>
  <?php endif; ?>

  <meta property="og:type" content="website"/>
  <meta property="og:site_name" content="Travel Hostel"/>
  <meta property="og:title" content="<?php echo isset($titulo) ? htmlspecialchars($titulo) : 'Travel Hostel'; ?>"/>
  <meta property="og:description" content="<?php echo !empty($metaDescricao) ? htmlspecialchars($metaDescricao) : 'Encontre os melhores hostels do Brasil.'; ?>"/>
  <?php if (!empty($ogImagem)): ?>
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImagem); ?>"/>
  <?php endif; ?>

  <meta name="robots" content="<?php echo !empty($metaRobots) ? htmlspecialchars($metaRobots) : 'index, follow'; ?>"/>

  <?php if (!empty($schemaOrg)): ?>
  <script type="application/ld+json"><?php echo $schemaOrg; ?></script>
  <?php endif; ?>

  <link rel="sitemap" type="application/xml" href="<?php echo URL_BASE; ?>/sitemap.xml"/>

  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/styles.css"/>
  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/navbar.css"/>
  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/hero.css"/>
  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/hostels.css"/>
  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/footer.css"/>
  <link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/auth.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <script>
    var URL_PUBLIC = "<?php echo URL_PUBLIC; ?>";
    var URL_BASE = "<?php echo URL_BASE; ?>";
  </script>
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="container flex-between">
    <div class="navbar-brand">
      <a href="<?php echo isset($_SESSION['usuario_nome']) ? routeUrl('perfil') : routeUrl('home'); ?>" class="logo">
        <span class="logo-text">Travel Hostel</span>
      </a>
    </div>

    <div class="navbar-menu" id="navbarMenu">
      <a href="<?php echo routeUrl('home'); ?>" class="nav-link">Inicio</a>
      <a href="<?php echo routeUrl('hostels'); ?>" class="nav-link">Hostels</a>
      <a href="<?php echo routeUrl('hostels', ['categoria' => 'praia']); ?>" class="nav-link">Praia</a>
      <a href="<?php echo routeUrl('hostels', ['categoria' => 'natureza']); ?>" class="nav-link">Natureza</a>
      <a href="<?php echo routeUrl('hostels', ['categoria' => 'urbano']); ?>" class="nav-link">Urbano</a>
    </div>

    <div class="navbar-actions" id="navbarActions">
      <?php if (isset($_SESSION['usuario_nome'])): ?>
        <a href="<?php echo routeUrl('perfil'); ?>" class="user-link" style="display:inline-flex;align-items:center;gap:8px;margin-right:12px;">
          <?php if (!empty($_SESSION['usuario_avatar'])): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['usuario_avatar']); ?>" alt="Avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
          <?php endif; ?>
          <span style="font-weight:600; color:var(--color-primary-1); font-size:14px;">
            <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
          </span>
        </a>
        <a href="<?php echo routeUrl('logout'); ?>" class="btn btn-outline btn-sm">Sair</a>
      <?php else: ?>
        <a href="<?php echo routeUrl('login'); ?>" class="btn btn-outline btn-sm">Entrar</a>
        <a href="<?php echo routeUrl('cadastro'); ?>" class="btn btn-primary btn-sm">Cadastro</a>
      <?php endif; ?>
      <button class="navbar-toggle" id="navbarToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>
