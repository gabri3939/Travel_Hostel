// main.js: funcionalidades de carregamento de hostels, busca, validacao de formulario e CEP.
// ─── Dados estáticos de fallback (usados quando o banco não está disponível) ──
var hostelsDataFallback = [
  {
    id: 1,
    nome: "Morato Hostel Center",
    cidade: "Francisco Morato",
    estado: "SP",
    descricao: "Hostel simples e aconchegante no centro de Francisco Morato.",
    preco_diaria: 15,
    avaliacao: 4.5,
    total_avaliacoes: 120,
    imagem_url:
      "https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80",
  },
  {
    id: 2,
    nome: "Rocha Vibes Hostel",
    cidade: "Franco da Rocha",
    estado: "SP",
    descricao: "Ambiente tranquilo com area verde e espaco para descanso.",
    preco_diaria: 18,
    avaliacao: 4.6,
    total_avaliacoes: 98,
    imagem_url:
      "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&q=80",
  },
  {
    id: 3,
    nome: "Caieiras Eco Hostel",
    cidade: "Caieiras",
    estado: "SP",
    descricao: "Hostel ecologico cercado pela natureza e trilhas.",
    preco_diaria: 20,
    avaliacao: 4.7,
    total_avaliacoes: 150,
    imagem_url:
      "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&q=80",
  },
  {
    id: 4,
    nome: "São Paulo Downtown",
    cidade: "São Paulo",
    estado: "SP",
    descricao: "Hostel moderno no centro de Sao Paulo perto de tudo.",
    preco_diaria: 25,
    avaliacao: 4.8,
    total_avaliacoes: 320,
    imagem_url:
      "https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=600&q=80",
  },
  {
    id: 5,
    nome: "Rio Beach Hostel",
    cidade: "Rio de Janeiro",
    estado: "RJ",
    descricao: "Hostel na praia com vista incrivel e clima animado.",
    preco_diaria: 30,
    avaliacao: 4.9,
    total_avaliacoes: 500,
    imagem_url:
      "https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600&q=80",
  },
  {
    id: 6,
    nome: "Curitiba Green Hostel",
    cidade: "Curitiba",
    estado: "PR",
    descricao: "Hostel sustentavel com ambiente calmo e organizado.",
    preco_diaria: 22,
    avaliacao: 4.6,
    total_avaliacoes: 210,
    imagem_url:
      "https://images.unsplash.com/photo-1560969184-10fe8719e047?w=600&q=80",
  },
];

// Inicialização
document.addEventListener("DOMContentLoaded", function () {
  carregarHostels();
  setMinDate();
  var si = document.getElementById("searchInput");
  if (si)
    si.addEventListener("keypress", function (e) {
      if (e.key === "Enter") searchHostels();
    });
  initPasswordToggles();
});

function initPasswordToggles() {
  var toggles = document.querySelectorAll(".password-toggle");
  toggles.forEach(function (toggle) {
    toggle.addEventListener("click", function () {
      var wrapper = this.closest(".password-wrapper");
      if (!wrapper) return;
      var input = wrapper.querySelector(
        'input[type="password"], input[type="text"]',
      );
      if (!input) return;
      var isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      var icon = this.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
      }
      this.setAttribute(
        "aria-label",
        isPassword ? "Ocultar senha" : "Mostrar senha",
      );
    });
  });
}

// Carrega hostels do banco via AJAX
function carregarHostels() {
  var grid = document.getElementById("featuredHostelsGrid");
  if (!grid) return;

  // Exibe skeletons enquanto carrega
  grid.innerHTML = gerarSkeletons(6);

  // URL_PUBLIC é definida pelo router.php e impressa no header.php
  var url =
    (typeof URL_PUBLIC !== "undefined" ? URL_PUBLIC : "") +
    "/config/hostels.php?destaque=1&limite=6";

  fetch(url)
    .then(function (res) {
      if (!res.ok) throw new Error("Falha na requisicao");
      return res.json();
    })
    .then(function (hostels) {
      if (!hostels || hostels.erro || hostels.length === 0)
        throw new Error("Sem dados");
      renderizarCards(grid, hostels);
    })
    .catch(function () {
      // Fallback: usa array estático
      renderizarCards(grid, hostelsDataFallback);
    });
}

// ─── Renderiza lista de hostels no grid ───────────────────────────────────────
function renderizarCards(grid, hostels) {
  grid.innerHTML = hostels.map(criarCard).join("");
}

// ─── Cria o HTML de um card de hostel ─────────────────────────────────────────
function criarCard(h) {
  var imagem =
    h.imagem_url ||
    "https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80";
  var avaliacao = parseFloat(h.avaliacao).toFixed(1);
  var preco = "R$ " + parseFloat(h.preco_diaria).toFixed(2).replace(".", ",");
  var localizacao = h.cidade + (h.estado ? ", " + h.estado : "");

  return (
    '<div class="hostel-card">' +
    '<div class="hostel-card-image">' +
    '<img src="' +
    imagem +
    '" alt="' +
    h.nome +
    '" loading="lazy" onerror="this.src=\'https://via.placeholder.com/500x400?text=Hostel\'">' +
    '<span class="hostel-card-badge"><i class="fa-solid fa-star"></i> ' +
    avaliacao +
    "</span>" +
    "</div>" +
    '<div class="hostel-card-body">' +
    '<h3 class="hostel-card-title">' +
    h.nome +
    "</h3>" +
    '<div class="hostel-card-location"><i class="fa-solid fa-location-dot"></i><span>' +
    localizacao +
    "</span></div>" +
    '<p class="hostel-card-description">' +
    (h.descricao || "") +
    "</p>" +
    '<div class="hostel-card-rating">' +
    '<span class="hostel-card-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>' +
    '<span class="hostel-card-reviews">(' +
    h.total_avaliacoes +
    " avaliacoes)</span>" +
    "</div>" +
    "</div>" +
    '<div class="hostel-card-footer">' +
    '<div class="hostel-card-price">' +
    '<span class="hostel-card-price-label">A partir de</span>' +
    '<span class="hostel-card-price-value">' +
    preco +
    "</span>" +
    "</div>" +
    '<button class="btn btn-primary btn-sm">Ver Detalhes</button>' +
    "</div>" +
    "</div>"
  );
}

// ─── Skeleton Loading ─────────────────────────────────────────────────────────
function gerarSkeletons(n) {
  var skeleton =
    '<div class="hostel-card hostel-card--skeleton">' +
    '<div class="hostel-card-image skeleton-img"></div>' +
    '<div class="hostel-card-body">' +
    '<div class="skeleton-line skeleton-titulo"></div>' +
    '<div class="skeleton-line skeleton-texto"></div>' +
    '<div class="skeleton-line skeleton-texto skeleton-curto"></div>' +
    "</div></div>";
  var html = "";
  for (var i = 0; i < n; i++) html += skeleton;
  return html;
}

// ─── Busca por destino ────────────────────────────────────────────────────────
function searchHostels() {
  var input = document.getElementById("searchInput");
  if (!input || !input.value.trim()) {
    alert("Digite um local para buscar");
    return;
  }

  var termo = input.value.trim().toLowerCase();
  var cards = document.querySelectorAll(
    "#featuredHostelsGrid .hostel-card:not(.hostel-card--skeleton)",
  );

  if (cards.length === 0) {
    alert("Buscando por: " + input.value.trim());
    return;
  }

  cards.forEach(function (card) {
    var local = card.querySelector(".hostel-card-location");
    var nome = card.querySelector(".hostel-card-title");
    var texto = (
      (local ? local.textContent : "") +
      " " +
      (nome ? nome.textContent : "")
    ).toLowerCase();
    card.style.display = texto.indexOf(termo) !== -1 ? "" : "none";
  });
}

// ─── Data mínima nos campos de data ──────────────────────────────────────────
function setMinDate() {
  var hoje = new Date().toISOString().split("T")[0];
  ["checkInDate", "checkOutDate"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.min = hoje;
  });
}

// ─── Máscara de CPF ───────────────────────────────────────────────────────────
function mascaraCPF(input) {
  var v = input.value.replace(/\D/g, "").slice(0, 11);
  v = v
    .replace(/(\d{3})(\d)/, "$1.$2")
    .replace(/(\d{3})(\d)/, "$1.$2")
    .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
  input.value = v;
}

// ─── Máscara de Telefone
function mascaraTelefone(input) {
  var v = input.value.replace(/\D/g, "").slice(0, 11);
  v = v.replace(/^(\d{2})(\d)/g, "($1) $2").replace(/(\d)(\d{4})$/, "$1-$2");
  input.value = v;
}

// Máscara de CEP
function mascaraCEP(input) {
  var v = input.value.replace(/\D/g, "").slice(0, 8);
  input.value = v.replace(/(\d{5})(\d)/, "$1-$2");
  if (v.length === 8) {
    buscarCEP();
  }
}

//  Busca CEP e preenche endereço automaticamente
function buscarCEP() {
  var cepInput = document.getElementById("cep");
  if (!cepInput) return;

  var cep = cepInput.value.replace(/\D/g, "");
  var feedback = document.getElementById("cepFeedback");
  feedback.textContent = "";

  if (cep.length !== 8) {
    return;
  }

  fetch("https://viacep.com.br/ws/" + cep + "/json/")
    .then(function (response) {
      if (!response.ok) throw new Error("Falha na requisicao do CEP");
      return response.json();
    })
    .then(function (data) {
      if (data.erro) {
        throw new Error("CEP nao encontrado");
      }

      var street = document.getElementById("street");
      var city = document.getElementById("city");
      var state = document.getElementById("state");

      if (street) street.value = data.logradouro || "";
      if (city) city.value = data.localidade || "";
      if (state) state.value = data.uf || "";
    })
    .catch(function () {
      if (feedback) {
        feedback.textContent = "CEP invalido ou nao encontrado.";
      }
    });
}

// ─── Valida CPF antes de enviar o cadastro ────────────────────────────────────
function isCPFValido(cpf) {
  cpf = cpf.replace(/\D/g, "");
  if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;

  var soma = 0;
  for (var i = 1; i <= 9; i++) {
    soma += parseInt(cpf.charAt(i - 1), 10) * (11 - i);
  }

  var resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.charAt(9), 10)) return false;

  soma = 0;
  for (i = 1; i <= 10; i++) {
    soma += parseInt(cpf.charAt(i - 1), 10) * (12 - i);
  }

  resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  return resto === parseInt(cpf.charAt(10), 10);
}

function validarCadastroForm() {
  var cpf = document.getElementById("cpf");
  if (cpf && cpf.value.trim() && !isCPFValido(cpf.value)) {
    alert("CPF invalido. Verifique e tente novamente.");
    cpf.focus();
    return false;
  }

  var password = document.getElementById("password");
  var confirm = document.getElementById("confirmPassword");
  if (password && password.value.length < 8) {
    alert("A senha deve ter no minimo 8 caracteres.");
    password.focus();
    return false;
  }

  if (password && confirm && password.value !== confirm.value) {
    alert("As senhas nao coincidem.");
    confirm.focus();
    return false;
  }

  var cep = document.getElementById("cep");
  if (cep && cep.value.trim()) {
    var cepLimpo = cep.value.replace(/\D/g, "");
    if (cepLimpo.length !== 8) {
      alert("Informe um CEP valido.");
      cep.focus();
      return false;
    }
  }

  return true;
}

function validarLogin() {
  var email = document.getElementById("email");
  var password = document.getElementById("password");

  if (!email || !password) {
    return true;
  }

  if (!email.value.trim() || !password.value.trim()) {
    alert("Preencha email e senha para continuar.");
    if (!email.value.trim()) {
      email.focus();
    } else {
      password.focus();
    }
    return false;
  }

  return true;
}

// ─── Retorna n hostels do fallback (compatibilidade) ──────────────────────────
function getFeaturedHostels(n) {
  return hostelsDataFallback.slice(0, n || 6);
}
