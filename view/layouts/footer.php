<?php // Rodape comum exibido em todas as paginas. ?>
<footer class="footer">
  <div class="container">
    <div class="grid grid-4">
      <div class="footer-section">
        <h4>Travel Hostel</h4>
        <p>Encontre hostels incríveis em todo o mundo.</p>
      </div>
      <div class="footer-section">
        <h4>Links Úteis</h4>
        <ul>
          <li><a href="<?php echo routeUrl('sobre'); ?>">Sobre Nós</a></li>
          <li><a href="<?php echo routeUrl('contato'); ?>">Contato</a></li>
          <li><a href="<?php echo routeUrl('blog'); ?>">Blog</a></li>
          <li><a href="<?php echo routeUrl('faq'); ?>">FAQ</a></li>
          <li><a href="<?php echo routeUrl('mapa-do-site'); ?>">Mapa do Site</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Política</h4>
        <ul>
          <li><a href="<?php echo routeUrl('politica'); ?>">Política de Privacidade</a></li>
          <li><a href="<?php echo routeUrl('termos'); ?>">Termos de Uso</a></li>
          <li><a href="<?php echo routeUrl('cookies'); ?>">Política de Cookies</a></li>
          <li><a href="<?php echo routeUrl('seguranca'); ?>">Segurança</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Redes Sociais</h4>
        <div class="social-links">
          <a href="#" class="social-link" aria-label="Instagram do Travel Hostel"><i class="fa-brands fa-instagram"></i> Instagram</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p style="color:#ffff">&copy; 2026 Travel Hostel. Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<script>
  var URL_BASE = '<?php echo URL_BASE; ?>';
  var URL_PUBLIC = '<?php echo URL_PUBLIC; ?>';
</script>
<script src="<?php echo assetUrl('js/main.js'); ?>"></script>
<script src="<?php echo assetUrl('js/navbar.js'); ?>"></script>
</body>
</html>
