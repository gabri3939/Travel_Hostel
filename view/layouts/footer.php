<?php // Rodape comum exibido em todas as paginas. ?>
<footer class="footer">
  <div class="container">
    <div class="grid grid-4">
      <div class="footer-section">
        <h4>Travel Hostel</h4>
        <p>Sua plataforma confiavel para encontrar hostels incriveis em todo o mundo.</p>
      </div>
      <div class="footer-section">
        <h4>Links Uteis</h4>
        <ul>
          <li><a href="#">Sobre Nos</a></li>
          <li><a href="#">Contato</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Politica</h4>
        <ul>
          <li><a href="<?php echo routeUrl('politica'); ?>">Politica de Privacidade</a></li>
          <li><a href="#">Termos de Uso</a></li>
          <li><a href="#">Cookies</a></li>
          <li><a href="#">Seguranca</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Redes Sociais</h4>
        <div class="social-links">
          <a href="#" class="social-link">Facebook</a>
          <a href="#" class="social-link">Instagram</a>
          <a href="#" class="social-link">Twitter</a>
          <a href="#" class="social-link">LinkedIn</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Travel Hostel. Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<script>
  var URL_BASE = '<?php echo URL_BASE; ?>';
  var URL_PUBLIC = '<?php echo URL_PUBLIC; ?>';
</script>
<script src="<?php echo URL_PUBLIC; ?>/js/main.js"></script>
<script src="<?php echo URL_PUBLIC; ?>/js/navbar.js"></script>
</body>
</html>
