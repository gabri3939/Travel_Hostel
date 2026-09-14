// anfitriao-upload.js: mostra uma previa visual das fotos escolhidas antes do envio,
// para o anfitriao confirmar que o upload pegou os arquivos certos.
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('imagens');
  var preview = document.getElementById('imagensPreview');
  var contador = document.getElementById('imagensContador');
  if (!input || !preview) return;

  var LIMITE_MAXIMO = 30;

  input.addEventListener('change', function () {
    preview.innerHTML = '';
    var arquivos = Array.prototype.slice.call(input.files || []);

    if (!arquivos.length) {
      contador.textContent = '';
      return;
    }

    contador.textContent = arquivos.length + ' de ' + LIMITE_MAXIMO + ' fotos selecionadas';
    contador.style.color = arquivos.length > LIMITE_MAXIMO ? '#b91c1c' : '#15803d';

    arquivos.forEach(function (arquivo, indice) {
      var item = document.createElement('div');
      item.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;background:#f8fafc;';

      var img = document.createElement('img');
      img.style.cssText = 'width:100%;height:90px;object-fit:cover;display:block;';
      img.alt = arquivo.name;
      img.src = URL.createObjectURL(arquivo);
      item.appendChild(img);

      var legenda = document.createElement('div');
      legenda.style.cssText = 'font-size:.7rem;color:#555;padding:3px 5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
      legenda.textContent = arquivo.name;
      item.appendChild(legenda);

      if (indice === 0) {
        var capa = document.createElement('span');
        capa.textContent = 'Capa';
        capa.style.cssText = 'position:absolute;top:4px;left:4px;background:#1808f9;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;';
        item.appendChild(capa);
      }

      preview.appendChild(item);
    });
  });
});
