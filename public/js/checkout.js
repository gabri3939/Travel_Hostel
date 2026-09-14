// checkout.js: seleciona metodo de pagamento (credito com parcelas, ou PIX), criptografa o cartao
// no navegador (PagBank) quando aplicavel, e acompanha a confirmacao do PIX por polling.
document.addEventListener('DOMContentLoaded', function () {
  initTabs();
  initInstallments();
  initCartao();
  initPix();
});

function mostrarErroGlobal(msg) {
  var erroBox = document.getElementById('checkoutErro');
  var sucessoBox = document.getElementById('checkoutSucesso');
  if (!erroBox) return;
  erroBox.textContent = msg;
  erroBox.hidden = false;
  if (sucessoBox) sucessoBox.hidden = true;
}

function mostrarSucessoGlobal(msg) {
  var erroBox = document.getElementById('checkoutErro');
  var sucessoBox = document.getElementById('checkoutSucesso');
  if (!sucessoBox) return;
  sucessoBox.textContent = msg;
  sucessoBox.hidden = false;
  if (erroBox) erroBox.hidden = true;
}

function initTabs() {
  var tabs = document.querySelectorAll('.payment-tab');
  if (!tabs.length) return;

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      var alvo = tab.getAttribute('data-tab');
      document.getElementById('painelCredito').hidden = alvo !== 'credito';
      document.getElementById('painelPix').hidden = alvo !== 'pix';

      document.getElementById('checkoutErro').hidden = true;
      document.getElementById('checkoutSucesso').hidden = true;
    });
  });
}

function initInstallments() {
  var select = document.getElementById('installments');
  if (!select || typeof CHECKOUT_CONFIG === 'undefined') return;

  var total = CHECKOUT_CONFIG.valorTotal;
  var opcoes = '';
  for (var n = 1; n <= 12; n++) {
    var parcela = total / n;
    var texto = n === 1
      ? '1x de R$ ' + parcela.toFixed(2).replace('.', ',') + ' (à vista)'
      : n + 'x de R$ ' + parcela.toFixed(2).replace('.', ',');
    opcoes += '<option value="' + n + '">' + texto + '</option>';
  }
  select.innerHTML = opcoes;
}

function initCartao() {
  var form = document.getElementById('formPagamento');
  if (!form) return;

  var btn = document.getElementById('btnPagar');

  form.addEventListener('submit', function (evento) {
    evento.preventDefault();
    document.getElementById('checkoutErro').hidden = true;
    document.getElementById('checkoutSucesso').hidden = true;

    if (typeof PagSeguro === 'undefined') {
      mostrarErroGlobal('Nao foi possivel carregar o modulo de pagamento. Recarregue a pagina.');
      return;
    }

    var numero = document.getElementById('cardNumber').value.replace(/\s+/g, '');
    var expMonth = document.getElementById('cardExpMonth').value.trim();
    var expYear = document.getElementById('cardExpYear').value.trim();
    var cvv = document.getElementById('cardCvv').value.trim();
    var holder = document.getElementById('cardHolder').value.trim();
    var holderCpf = document.getElementById('holderCpf').value.replace(/\D/g, '');
    var telefone = document.getElementById('telefone').value.replace(/\D/g, '');
    var parcelas = document.getElementById('installments').value || '1';

    if (!numero || !expMonth || !expYear || !cvv || !holder) {
      mostrarErroGlobal('Preencha todos os dados do cartao.');
      return;
    }
    if (holderCpf.length !== 11) {
      mostrarErroGlobal('Informe um CPF valido do titular do cartao.');
      return;
    }
    if (telefone.length < 10) {
      mostrarErroGlobal('Informe um telefone valido.');
      return;
    }

    var card = PagSeguro.encryptCard({
      publicKey: CHECKOUT_CONFIG.publicKey,
      holder: holder,
      number: numero,
      expMonth: expMonth,
      expYear: expYear,
      securityCode: cvv
    });

    if (card.hasErrors) {
      mostrarErroGlobal('Dados do cartao invalidos. Verifique numero, validade e CVV.');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Processando pagamento...';

    var corpo = new URLSearchParams();
    corpo.set('reserva_id', CHECKOUT_CONFIG.reservaId);
    corpo.set('csrf_token', CHECKOUT_CONFIG.csrfToken);
    corpo.set('card_encrypted', card.encryptedCard);
    corpo.set('exp_month', expMonth);
    corpo.set('exp_year', expYear);
    corpo.set('security_code', cvv);
    corpo.set('holder_name', holder);
    corpo.set('holder_cpf', holderCpf);
    corpo.set('telefone', telefone);
    corpo.set('installments', parcelas);

    fetch(CHECKOUT_CONFIG.pagarUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    })
      .then(function (res) { return res.json(); })
      .then(function (dados) {
        if (dados.sucesso) {
          mostrarSucessoGlobal(dados.mensagem);
          form.querySelectorAll('input, select, button').forEach(function (el) { el.disabled = true; });
          setTimeout(function () {
            window.location.href = URL_BASE + '/controller/router.php?pagina=perfil';
          }, 2500);
        } else {
          mostrarErroGlobal(dados.mensagem || 'Nao foi possivel processar o pagamento.');
          btn.disabled = false;
          btn.textContent = 'Tentar novamente';
        }
      })
      .catch(function () {
        mostrarErroGlobal('Falha de conexao. Tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Tentar novamente';
      });
  });
}

function initPix() {
  var btnGerar = document.getElementById('btnGerarPix');
  if (!btnGerar) return;

  btnGerar.addEventListener('click', function () {
    document.getElementById('checkoutErro').hidden = true;
    document.getElementById('checkoutSucesso').hidden = true;

    var cpf = document.getElementById('pixCpf').value.replace(/\D/g, '');
    var telefone = document.getElementById('pixTelefone').value.replace(/\D/g, '');

    if (cpf.length !== 11) {
      mostrarErroGlobal('Informe um CPF valido.');
      return;
    }
    if (telefone.length < 10) {
      mostrarErroGlobal('Informe um telefone valido.');
      return;
    }

    btnGerar.disabled = true;
    btnGerar.textContent = 'Gerando PIX...';

    var corpo = new URLSearchParams();
    corpo.set('reserva_id', CHECKOUT_CONFIG.reservaId);
    corpo.set('csrf_token', CHECKOUT_CONFIG.csrfToken);
    corpo.set('cpf', cpf);
    corpo.set('telefone', telefone);

    fetch(CHECKOUT_CONFIG.pixUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    })
      .then(function (res) { return res.json(); })
      .then(function (dados) {
        if (!dados.sucesso) {
          mostrarErroGlobal(dados.mensagem || 'Nao foi possivel gerar o PIX.');
          btnGerar.disabled = false;
          btnGerar.textContent = 'Tentar novamente';
          return;
        }

        document.getElementById('pixFormulario').hidden = true;
        document.getElementById('pixResultado').hidden = false;
        document.getElementById('pixImagem').src = dados.qr_imagem_url;
        document.getElementById('pixTexto').value = dados.qr_texto;

        iniciarPollingPix();
      })
      .catch(function () {
        mostrarErroGlobal('Falha de conexao. Tente novamente.');
        btnGerar.disabled = false;
        btnGerar.textContent = 'Tentar novamente';
      });
  });

  var btnCopiar = document.getElementById('btnCopiarPix');
  if (btnCopiar) {
    btnCopiar.addEventListener('click', function () {
      var campo = document.getElementById('pixTexto');
      campo.select();
      campo.setSelectionRange(0, 99999);
      if (navigator.clipboard) {
        navigator.clipboard.writeText(campo.value).then(function () {
          btnCopiar.textContent = 'Copiado!';
          setTimeout(function () { btnCopiar.textContent = 'Copiar'; }, 2000);
        }).catch(function () {
          document.execCommand('copy');
        });
      } else {
        document.execCommand('copy');
      }
    });
  }
}

function iniciarPollingPix() {
  var tentativas = 0;
  var maxTentativas = 90; // ~6 minutos com intervalo de 4s

  var intervalo = setInterval(function () {
    tentativas++;

    fetch(CHECKOUT_CONFIG.statusUrl + '&id=' + CHECKOUT_CONFIG.reservaId)
      .then(function (res) { return res.json(); })
      .then(function (dados) {
        if (!dados.sucesso) return;

        if (dados.status === 'pago') {
          clearInterval(intervalo);
          mostrarSucessoGlobal('Pagamento PIX confirmado! Sua reserva esta paga.');
          document.querySelector('.pix-aguardando').innerHTML = '<i class="fa-solid fa-check"></i> Pagamento confirmado!';
          setTimeout(function () {
            window.location.href = URL_BASE + '/controller/router.php?pagina=perfil';
          }, 2000);
        } else if (dados.status === 'recusado' || dados.status === 'cancelado') {
          clearInterval(intervalo);
          mostrarErroGlobal('O pagamento PIX nao foi confirmado. Gere um novo codigo se necessario.');
        }
      })
      .catch(function () {});

    if (tentativas >= maxTentativas) {
      clearInterval(intervalo);
      document.querySelector('.pix-aguardando').textContent = 'Tempo de confirmacao esgotado. Se ja pagou, atualize a pagina em instantes.';
    }
  }, 4000);
}
