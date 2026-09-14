// hostel-gallery.js: lightbox de imagens do hostel + calculo dinamico do total da reserva.
document.addEventListener('DOMContentLoaded', function () {
  initLightbox();
  initBookingForm();
});

function initLightbox() {
  var galeria = document.getElementById('galeriaHostel');
  var lightbox = document.getElementById('lightbox');
  if (!galeria || !lightbox) return;

  var imgs = Array.prototype.map.call(galeria.querySelectorAll('img'), function (img) {
    return { src: img.getAttribute('data-full') || img.src, alt: img.alt };
  });

  var indiceAtual = 0;
  var lightboxImg = document.getElementById('lightboxImg');
  var lightboxCounter = document.getElementById('lightboxCounter');

  function abrir(indice) {
    if (!imgs.length) return;
    indiceAtual = (indice + imgs.length) % imgs.length;
    lightboxImg.src = imgs[indiceAtual].src;
    lightboxImg.alt = imgs[indiceAtual].alt || '';
    lightboxCounter.textContent = (indiceAtual + 1) + ' / ' + imgs.length;
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function fechar() {
    lightbox.hidden = true;
    document.body.style.overflow = '';
  }

  galeria.querySelectorAll('[data-index]').forEach(function (el) {
    el.style.cursor = 'pointer';
    el.addEventListener('click', function () {
      abrir(parseInt(el.getAttribute('data-index'), 10) || 0);
    });
  });

  var btnClose = document.getElementById('lightboxClose');
  var btnPrev = document.getElementById('lightboxPrev');
  var btnNext = document.getElementById('lightboxNext');

  if (btnClose) btnClose.addEventListener('click', fechar);
  if (btnPrev) btnPrev.addEventListener('click', function () { abrir(indiceAtual - 1); });
  if (btnNext) btnNext.addEventListener('click', function () { abrir(indiceAtual + 1); });

  lightbox.addEventListener('click', function (evento) {
    if (evento.target === lightbox) fechar();
  });

  document.addEventListener('keydown', function (evento) {
    if (lightbox.hidden) return;
    if (evento.key === 'Escape') fechar();
    if (evento.key === 'ArrowLeft') abrir(indiceAtual - 1);
    if (evento.key === 'ArrowRight') abrir(indiceAtual + 1);
  });
}

function initBookingForm() {
  var form = document.getElementById('formReserva');
  if (!form) return;

  var precoDiaria = parseFloat(form.getAttribute('data-preco') || '0');
  var checkin = document.getElementById('checkInDate');
  var checkout = document.getElementById('checkOutDate');
  var resumo = document.getElementById('resumoPreco');
  var resumoNoites = document.getElementById('resumoNoites');
  var resumoTotal = document.getElementById('resumoTotal');

  function atualizarResumo() {
    if (!checkin.value || !checkout.value) {
      resumo.hidden = true;
      return;
    }

    var dtIn = new Date(checkin.value + 'T00:00:00');
    var dtOut = new Date(checkout.value + 'T00:00:00');
    var noites = Math.round((dtOut - dtIn) / 86400000);

    if (noites <= 0) {
      resumo.hidden = true;
      return;
    }

    var total = noites * precoDiaria;
    resumoNoites.textContent = noites + (noites === 1 ? ' noite' : ' noites');
    resumoTotal.textContent = 'Total: R$ ' + total.toFixed(2).replace('.', ',');
    resumo.hidden = false;
  }

  checkin.addEventListener('change', function () {
    if (checkout.value && checkout.value <= checkin.value) {
      var proximo = new Date(checkin.value + 'T00:00:00');
      proximo.setDate(proximo.getDate() + 1);
      checkout.value = proximo.toISOString().split('T')[0];
    }
    checkout.min = checkin.value;
    atualizarResumo();
  });
  checkout.addEventListener('change', atualizarResumo);
}
