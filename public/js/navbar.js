// JS da navegacao responsiva: abre/fecha menu mobile e fecha com clique em links.
// Configura o toggle do menu mobile e fecha ao clicar em links
document.addEventListener("DOMContentLoaded", function () {
  var toggle = document.getElementById("navbarToggle");
  var menu   = document.getElementById("navbarMenu");
  if (toggle) {
    toggle.addEventListener("click", function () {
      this.classList.toggle("active");
      if (menu) menu.classList.toggle("active");
    });
  }
  document.querySelectorAll(".nav-link").forEach(function(link) {
    link.addEventListener("click", function() {
      if (toggle) toggle.classList.remove("active");
      if (menu)   menu.classList.remove("active");
    });
  });
});

// Adiciona classe 'scrolled' à navbar ao rolar a página
window.addEventListener("scroll", function () {
  var nb = document.getElementById("navbar");
  if (!nb) return;
  if (window.scrollY > 50) nb.classList.add("scrolled");
  else nb.classList.remove("scrolled");
});

// Fecha o menu mobile ao clicar fora dele
document.addEventListener("click", function (e) {
  var nb = document.querySelector(".navbar");
  if (nb && !nb.contains(e.target)) {
    var toggle = document.getElementById("navbarToggle");
    var menu   = document.getElementById("navbarMenu");
    if (toggle) toggle.classList.remove("active");
    if (menu)   menu.classList.remove("active");
  }
});
