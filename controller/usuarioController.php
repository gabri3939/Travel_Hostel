<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once ROOT . '/model/usuarioModel.php';
require_once ROOT . '/model/reservaModel.php';
require_once ROOT . '/model/avaliacaoModel.php';
require_once ROOT . '/model/contatoModel.php';

class usuarioController {

    /**
     * Identifica imagens enviadas sem depender exclusivamente da extensão fileinfo.
     */
    private function detectarMimeImagem(string $arquivo): ?string {
        if ($arquivo === '' || !is_file($arquivo)) {
            return null;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $arquivo);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        $imagem = @getimagesize($arquivo);
        return is_array($imagem) && isset($imagem['mime']) ? $imagem['mime'] : null;
    }

    private function redirect(string $pagina, array $params = []): void {
        header('Location: ' . routeUrl($pagina, $params));
        exit;
    }

    private function syncUsuarioSession(array $usuario): void {
        $_SESSION['usuario_nome'] = $usuario['nome'] ?? ($_SESSION['usuario_nome'] ?? '');
        $_SESSION['usuario_email'] = $usuario['email'] ?? ($_SESSION['usuario_email'] ?? '');
        $_SESSION['usuario_nivel'] = $usuario['nivel'] ?? ($_SESSION['usuario_nivel'] ?? 'usuario');
        $_SESSION['usuario_avatar'] = normalizeAvatarUrl($usuario['avatar'] ?? '');
    }

    public function home() {
        require_once ROOT . '/view/home/index.php';
    }

    public function hostels() {
        require_once ROOT . '/view/hostels/index.php';
    }

    public function hostelDetalhe(string $slug) {
        if ($slug === '') {
            $this->redirect('hostels');
        }

        $hostel = null;

        require_once ROOT . '/config/conexao.php';
        try {
            $conexao = new Conexao();
            $pdo = $conexao->conectar();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT h.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.icone AS categoria_icone,
                           a.nome AS anfitriao_nome
                    FROM hostels h
                    LEFT JOIN categorias c ON c.id = h.categoria_id
                    LEFT JOIN usuarios a ON a.id = h.anfitriao_id
                       WHERE h.slug = :slug AND h.status_aprovacao = 'aprovado' AND h.ativo = 1
                    LIMIT 1
                ");
                $stmt->execute([':slug' => $slug]);
                $hostel = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($hostel) {
                    $stmtImagens = $pdo->prepare("SELECT caminho FROM hostel_imagens WHERE hostel_id = ? ORDER BY principal DESC, id ASC");
                    $stmtImagens->execute([(int) $hostel['id']]);
                    $hostel['imagens'] = $stmtImagens->fetchAll(PDO::FETCH_COLUMN);
                }
            }
        } catch (Exception $e) {
        }

        if (!$hostel) {
            $hostel = [
                'nome' => ucwords(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'cidade' => 'Brasil',
                'estado' => '',
                'pais' => 'Brasil',
                'descricao' => 'Informacoes em breve.',
                'preco_diaria' => 0,
                'avaliacao' => 0,
                'total_avaliacoes' => 0,
                'comodidades' => '',
                'imagem_url' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=800&q=80',
                'palavras_chave' => '',
                'categoria_nome' => '',
                'categoria_slug' => '',
                'categoria_icone' => 'fa-bed',
            ];
        }

        $avaliacoesHostel = [];
        $resumoAnfitriao = null;
        if (!empty($hostel['id'])) {
            $avaliacaoModel = new avaliacaoModel();
            $avaliacoesHostel = $avaliacaoModel->listarAvaliacoesHostel((int) $hostel['id']);
            if (!empty($hostel['anfitriao_id'])) {
                $resumoAnfitriao = $avaliacaoModel->resumoAnfitriao((int) $hostel['anfitriao_id']);
            }
        }

        require_once ROOT . '/view/hostel/detalhe.php';
    }

    public function mapaSite() {
        $titulo = 'Mapa do Site - Travel Hostel';
        $metaRobots = 'noindex, follow';

        $hostels = [];
        $categorias = [];

        require_once ROOT . '/config/conexao.php';
        try {
            $conexao = new Conexao();
            $pdo = $conexao->conectar();
            if ($pdo) {
                $hostels = $pdo->query("SELECT nome, slug FROM hostels WHERE slug IS NOT NULL AND status_aprovacao = 'aprovado' AND ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                $categorias = $pdo->query("SELECT nome, slug FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
        }

        require_once ROOT . '/view/mapa-do-site/index.php';
    }

    public function cadastro() {
        $mensagem = '';
        $tipoMensagem = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $cpf = trim($_POST['cpf'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $birthDate = trim($_POST['birthDate'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirmPass = trim($_POST['confirmPassword'] ?? '');
            $cep = trim($_POST['cep'] ?? '');
            $street = trim($_POST['street'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');

            if (empty($nome) || empty($email) || empty($password) || empty($confirmPass)) {
                $mensagem = 'Preencha todos os campos obrigatorios.';
                $tipoMensagem = 'erro';
            } elseif (strlen($nome) < 3) {
                $mensagem = 'O nome deve ter pelo menos 3 caracteres.';
                $tipoMensagem = 'erro';
            } elseif (!$email) {
                $mensagem = 'Informe um e-mail valido.';
                $tipoMensagem = 'erro';
            } elseif ($password !== $confirmPass) {
                $mensagem = 'As senhas nao coincidem.';
                $tipoMensagem = 'erro';
            } elseif (strlen($password) < 8) {
                $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
                $tipoMensagem = 'erro';
            } else {
                $model = new usuarioModel();
                if ($model->emailExiste($email)) {
                    $mensagem = 'Este e-mail ja esta cadastrado.';
                    $tipoMensagem = 'erro';
                } else {
                    $codigo = $this->gerarCodigoVerificacao();

                    $_SESSION['pending_user'] = [
                        'name' => $nome,
                        'email' => $email,
                        'cpf' => $cpf ?: null,
                        'phone' => $phone ?: null,
                        'birthDate' => $birthDate ?: null,
                        'password' => $password,
                        'cep' => $cep ?: null,
                        'street' => $street ?: null,
                        'city' => $city ?: null,
                        'state' => $state ?: null,
                    ];
                    $_SESSION['verification_code'] = $codigo;
                    $_SESSION['verification_expires'] = time() + 900;

                    if ($this->enviarCodigoVerificacao($email, $nome, $codigo)) {
                        $this->redirect('verificar');
                    } else {
                        error_log('[EMAIL FLOW] enviarCodigoVerificacao retornou false. ' .
                            'MAIL_USERNAME=' . (getenv('MAIL_USERNAME') ? 'set' : 'empty') . ', ' .
                            'MAIL_HOST=' . (getenv('MAIL_HOST') ?: 'empty') . ', ' .
                            'MAIL_PORT=' . (getenv('MAIL_PORT') ?: 'empty') . ', ' .
                            'PHP_SAPI=' . PHP_SAPI . ', ' .
                            'REQUEST_METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? 'none')
                        );
                    }

                    $mensagem = 'Nao foi possivel enviar o codigo de verificacao. Verifique as credenciais de email no arquivo .env.';
                    $tipoMensagem = 'erro';
                }
            }
        }

        require_once ROOT . '/view/cadastro/index.php';
    }

    public function verificar() {
        $mensagem = '';
        $tipoMensagem = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigo = trim($_POST['verificationCode'] ?? '');

            if ($codigo === '') {
                $mensagem = 'Informe o codigo de verificacao enviado por e-mail.';
                $tipoMensagem = 'erro';
            } elseif (empty($_SESSION['pending_user']) || empty($_SESSION['verification_code'])) {
                $mensagem = 'Sessao de verificacao expirada. Refaça o cadastro.';
                $tipoMensagem = 'erro';
            } elseif (time() > ($_SESSION['verification_expires'] ?? 0)) {
                $mensagem = 'O codigo expirou. Refaça o cadastro.';
                $tipoMensagem = 'erro';
                unset($_SESSION['pending_user'], $_SESSION['verification_code'], $_SESSION['verification_expires']);
            } elseif ($codigo !== $_SESSION['verification_code']) {
                $mensagem = 'Codigo invalido. Verifique o e-mail e tente novamente.';
                $tipoMensagem = 'erro';
            } else {
                $model = new usuarioModel();
                $dadosUser = $_SESSION['pending_user'];
                $resultado = $model->cadastrar($dadosUser);

                if ($resultado['sucesso']) {
                    $_SESSION['usuario_nome'] = $resultado['nome'];
                    $_SESSION['usuario_email'] = $resultado['email'];
                    $usuarioCriado = $model->getUsuarioByEmail($resultado['email']);
                    if ($usuarioCriado) {
                        $this->syncUsuarioSession($usuarioCriado);
                    }
                    unset($_SESSION['pending_user'], $_SESSION['verification_code'], $_SESSION['verification_expires']);
                    $this->redirect('home');
                }

                $mensagem = $resultado['mensagem'];
                $tipoMensagem = 'erro';
            }
        }

        require_once ROOT . '/view/verify/index.php';
    }

    public function politica() {
        require_once ROOT . '/view/politica/index.php';
    }

    public function termos() {
        require_once ROOT . '/view/termos/index.php';
    }

    public function cookies() {
        require_once ROOT . '/view/cookies/index.php';
    }

    public function seguranca() {
        require_once ROOT . '/view/seguranca/index.php';
    }

    public function sobre() {
        require_once ROOT . '/view/sobre/index.php';
    }

    public function contato() {
        $mensagem = '';
        $tipoMensagem = '';

        $nomePreenchido = $_SESSION['usuario_nome'] ?? '';
        $emailPreenchido = $_SESSION['usuario_email'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValido($_POST['csrf_token'] ?? null)) {
                $mensagem = 'Sessao expirada. Recarregue a pagina e tente novamente.';
                $tipoMensagem = 'erro';
            } else {
                $contatoModel = new contatoModel();
                $usuarioId = $this->getUsuarioIdSessao();

                $nomePreenchido = trim($_POST['nome'] ?? '');
                $emailPreenchido = trim($_POST['email'] ?? '');

                $resultado = $contatoModel->enviarMensagem(
                    $usuarioId,
                    $nomePreenchido,
                    $emailPreenchido,
                    trim($_POST['assunto'] ?? ''),
                    trim($_POST['mensagem'] ?? '')
                );

                $mensagem = $resultado['mensagem'];
                $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';

                if ($resultado['sucesso']) {
                    $nomePreenchido = $_SESSION['usuario_nome'] ?? '';
                    $emailPreenchido = $_SESSION['usuario_email'] ?? '';
                }
            }
        }

        $minhasMensagens = [];
        $usuarioIdAtual = $this->getUsuarioIdSessao();
        if ($usuarioIdAtual) {
            $contatoModel = new contatoModel();
            $minhasMensagens = $contatoModel->listarMensagensDoUsuario($usuarioIdAtual);
        }

        require_once ROOT . '/view/contato/index.php';
    }

    // Retorna o id do usuario logado (ou null), usado para vincular mensagens de contato sem exigir nova consulta em todo lugar.
    private function getUsuarioIdSessao(): ?int {
        if (empty($_SESSION['usuario_email'])) {
            return null;
        }

        $model = new usuarioModel();
        $usuario = $model->getUsuarioByEmail($_SESSION['usuario_email']);
        return $usuario ? (int) $usuario['id'] : null;
    }

    public function blog() {
        require_once ROOT . '/view/blog/index.php';
    }

    public function faq() {
        require_once ROOT . '/view/faq/index.php';
    }

    private function gerarCodigoVerificacao(): string {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function enviarCodigoVerificacao(string $email, string $nome, string $codigo): bool {
        try {
            $mailUsername = getenv('MAIL_USERNAME');
            $mailPassword = getenv('MAIL_PASSWORD');

            if (empty($mailUsername) || empty($mailPassword)) {
                error_log('[EMAIL ERROR] Credenciais de email nao configuradas no arquivo .env');
                return false;
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE') ?: 'tls';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->SMTPDebug = getenv('MAIL_DEBUG') === 'true' ? 2 : 0;
            $mail->Debugoutput = 'error_log';

            $fromEmail = getenv('MAIL_FROM') ?: $mailUsername;
            $fromName = getenv('MAIL_FROM_NAME') ?: 'Travel Hostel';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $nome);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Verificacao de E-mail - Travel Hostel';
            $mail->Body = '<p>Ola ' . htmlspecialchars($nome) . ',</p>'
                . '<p>Seu codigo de verificacao e: <strong>' . htmlspecialchars($codigo) . '</strong></p>'
                . '<p>Este codigo expira em 15 minutos.</p>';
            $mail->AltBody = 'Ola ' . $nome . ', seu codigo e: ' . $codigo . ' (expira em 15 minutos)';

            $sent = $mail->send();
            if (!$sent) {
                error_log('[EMAIL ERROR] PHPMailer ErrorInfo: ' . ($mail->ErrorInfo ?? 'unknown'));
            }
            return $sent;
        } catch (Exception $e) {
            error_log('[EMAIL ERROR] Falha ao enviar codigo para ' . $email . ': ' . $e->getMessage());
            return false;
        }
    }

    public function login() {
        $mensagem = '';
        $tipoMensagem = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new usuarioModel();
            $resultado = $model->login($_POST);

            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';

            if ($resultado['sucesso']) {
                $_SESSION['usuario_nome'] = $resultado['nome'];
                $_SESSION['usuario_email'] = $resultado['email'];
                $_SESSION['last_activity'] = time();
                $fullUser = $model->getUsuarioByEmail($resultado['email']);
                if ($fullUser) {
                    $this->syncUsuarioSession($fullUser);
                }
                $this->redirect('home');
            }
        }

        require_once ROOT . '/view/login/index.php';
    }
    public function recuperar(){
    $mensagem = '';
    $tipoMensagem = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $mensagem = 'Informe um e-mail válido.';
            $tipoMensagem = 'erro';
        } elseif ($acao === 'enviar') {
            $model = new usuarioModel();
            $usuario = $model->getUsuarioByEmail($email);

            if ($usuario) {
                $codigo = $this->gerarCodigoVerificacao();

                $_SESSION['recover_email'] = $email;
                $_SESSION['recover_code'] = $codigo;
                $_SESSION['recover_expires'] = time() + 900;

                $this->enviarCodigoVerificacao($email, $usuario['nome'], $codigo);
            }

            $mensagem = 'Se este e-mail estiver cadastrado, você receberá um código em breve.';
            $tipoMensagem = 'sucesso';

        } elseif ($acao === 'verificar') {
            $codigo = trim($_POST['verificationCode'] ?? '');

            if (empty($_SESSION['recover_code']) || empty($_SESSION['recover_email'])) {
                $mensagem = 'Sessão expirada. Solicite o código novamente.';
                $tipoMensagem = 'erro';
            } elseif (time() > ($_SESSION['recover_expires'] ?? 0)) {
                $mensagem = 'Código expirado. Solicite um novo.';
                $tipoMensagem = 'erro';
                unset($_SESSION['recover_code'], $_SESSION['recover_email'], $_SESSION['recover_expires']);
            } elseif ($codigo !== $_SESSION['recover_code']) {
                $mensagem = 'Código inválido. Tente novamente.';
                $tipoMensagem = 'erro';
            } else {
                $this->redirect('nova-senha');
            }
        }
    }

    require_once ROOT . '/view/recuperar-senha/index.php';
}
public function novaSenha() {
    $mensagem = '';
    $tipoMensagem = '';

    if (empty($_SESSION['recover_email'])) {
        $this->redirect('recuperar');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirmPassword'] ?? '');

        if (empty($password) || empty($confirmPassword)) {
            $mensagem = 'Preencha todos os campos.';
            $tipoMensagem = 'erro';
        } elseif (strlen($password) < 8) {
            $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
            $tipoMensagem = 'erro';
        } elseif ($password !== $confirmPassword) {
            $mensagem = 'As senhas não coincidem.';
            $tipoMensagem = 'erro';
        } else {
            $model = new usuarioModel();
            if ($model->atualizarSenha($_SESSION['recover_email'], $password)) {
                unset($_SESSION['recover_email'], $_SESSION['recover_code'], $_SESSION['recover_expires']);
                $mensagem = 'Senha atualizada com sucesso!';
                $tipoMensagem = 'sucesso';
                $this->redirect('login');
            } else {
                $mensagem = 'Erro ao atualizar a senha. Tente novamente.';
                $tipoMensagem = 'erro';
            }
        }
    }

    require_once ROOT . '/view/nova-senha/index.php';
}
    public function perfil() {
        $mensagem = '';
        $tipoMensagem = '';

        if (empty($_SESSION['usuario_email'])) {
            $this->redirect('login');
        }

        $model = new usuarioModel();
        $usuario = $model->getUsuarioByEmail($_SESSION['usuario_email']);

        if (!$usuario) {
            session_unset();
            session_destroy();
            $this->redirect('login');
        }

        $this->syncUsuarioSession($usuario);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['acao'] ?? '') === 'solicitar_anfitriao') {
                if ($model->solicitarAnfitriao((int) $usuario['id'])) {
                    $mensagem = 'Solicitacao enviada. Aguarde a analise do administrador.';
                    $tipoMensagem = 'sucesso';
                    $usuario = $model->getUsuarioByEmail($usuario['email']) ?: $usuario;
                } else {
                    $mensagem = 'Sua solicitacao ja esta em analise ou nao pode ser enviada.';
                    $tipoMensagem = 'erro';
                }
            } elseif (isset($_POST['rating'])) {
                $nota = (int) ($_POST['rating'] ?? 0);
                if ($nota < 1 || $nota > 5) {
                    $mensagem = 'Escolha uma nota de 1 a 5 estrelas.';
                    $tipoMensagem = 'erro';
                } elseif ($model->registrarAvaliacao($usuario['email'], $nota)) {
                    $mensagem = 'Obrigado pela avaliacao! Sua nota foi registrada.';
                    $tipoMensagem = 'sucesso';
                    $usuario = $model->getUsuarioByEmail($usuario['email']);
                    $this->syncUsuarioSession($usuario ?: []);
                } else {
                    $mensagem = 'Nao foi possivel registrar a avaliacao. Tente novamente.';
                    $tipoMensagem = 'erro';
                }
            } elseif (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['avatar'];
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $mensagem = 'Erro no upload do arquivo.';
                    $tipoMensagem = 'erro';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $mensagem = 'Arquivo muito grande. Maximo de 2MB.';
                    $tipoMensagem = 'erro';
                } else {
                    $mimeType = $this->detectarMimeImagem($file['tmp_name']);
                    if (!$mimeType || !in_array($mimeType, $allowed, true)) {
                        $mensagem = 'Formato invalido. Use JPG, PNG, GIF ou WEBP.';
                        $tipoMensagem = 'erro';
                    } else {
                        $ext = match ($mimeType) {
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp',
                            default => 'jpg',
                        };

                        $dir = ROOT . '/public/images/avatars';
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }

                        $filename = uniqid('av_', true) . '.' . $ext;
                        $dest = $dir . '/' . $filename;

                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            $pathDb = 'public/images/avatars/' . $filename;
                            if ($model->atualizarAvatar($usuario['email'], $pathDb)) {
                                $usuario = $model->getUsuarioByEmail($usuario['email']);
                                if ($usuario) {
                                    $this->syncUsuarioSession($usuario);
                                }
                                $mensagem = 'Foto de perfil atualizada com sucesso.';
                                $tipoMensagem = 'sucesso';
                            } else {
                                $mensagem = 'Nao foi possivel salvar a foto no banco.';
                                $tipoMensagem = 'erro';
                            }
                        } else {
                            $mensagem = 'Falha ao mover o arquivo enviado.';
                            $tipoMensagem = 'erro';
                        }
                    }
                }
            }
        }

        if ($mensagem === '' && !empty($_SESSION['perfil_mensagem'])) {
            $mensagem = $_SESSION['perfil_mensagem'];
            $tipoMensagem = $_SESSION['perfil_tipo'] ?? 'sucesso';
        }
        unset($_SESSION['perfil_mensagem'], $_SESSION['perfil_tipo']);

        $reservaModel = new reservaModel();
        $avaliacaoModel = new avaliacaoModel();
        $minhasReservas = $reservaModel->listarReservasUsuario((int) $usuario['id']);
        foreach ($minhasReservas as &$reservaItem) {
            $reservaItem['ja_avaliou_hostel'] = $avaliacaoModel->jaAvaliouHostel((int) $reservaItem['id']);
            $reservaItem['ja_avaliou_anfitriao'] = $avaliacaoModel->jaAvaliouAnfitriao((int) $reservaItem['id']);
        }
        unset($reservaItem);

        $reservasRecebidas = ($usuario['nivel'] ?? '') === 'anfitriao'
            ? $reservaModel->listarReservasDoAnfitriao((int) $usuario['id'])
            : [];

        require_once ROOT . '/view/usuario/index.php';
    }

    public function admin() {
        if (empty($_SESSION['usuario_email'])) {
            $this->redirect('login');
        }

        $model = new usuarioModel();
        $admin = $model->getUsuarioByEmail($_SESSION['usuario_email']);
        if (!$admin || ($admin['nivel'] ?? '') !== 'admin' || (array_key_exists('ativo', $admin) && !(int) $admin['ativo'])) {
            http_response_code(403);
            $titulo = 'Acesso negado';
            require_once ROOT . '/view/home/index.php';
            return;
        }

        $mensagem = '';
        $tipoMensagem = '';

        // O admin decide primeiro o acesso de anfitriao e depois os recintos enviados.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';
            $id = (int) ($_POST['id'] ?? 0);

            if ($acao === 'aprovar_anfitriao' || $acao === 'rejeitar_anfitriao') {
                $decisao = $acao === 'aprovar_anfitriao' ? 'aprovada' : 'rejeitada';
                if ($model->decidirSolicitacaoAnfitriao($id, $decisao, $_POST['motivo'] ?? '')) {
                    $mensagem = $decisao === 'aprovada' ? 'Solicitacao aprovada. O usuario agora e anfitriao.' : 'Solicitacao de anfitriao rejeitada.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel atualizar a solicitacao de anfitriao.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'aprovar_hostel' || $acao === 'rejeitar_hostel') {
                $status = $acao === 'aprovar_hostel' ? 'aprovado' : 'rejeitado';
                if ($model->atualizarAprovacaoHostel($id, $status, $_POST['motivo'] ?? '')) {
                    $mensagem = $status === 'aprovado' ? 'Recinto aprovado e publicado.' : 'Recinto rejeitado.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel atualizar a aprovacao do recinto.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'editar') {
                if ($model->atualizarUsuario($id, $_POST)) {
                    $mensagem = 'Usuario atualizado com sucesso.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel atualizar o usuario. Confira os dados.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'status') {
                $ativo = (int) ($_POST['ativo'] ?? 0) === 1;
                if ($id === (int) $admin['id']) {
                    $mensagem = 'O administrador conectado nao pode ser inativado.';
                    $tipoMensagem = 'erro';
                } elseif ($model->alterarStatusUsuario($id, $ativo)) {
                    $mensagem = $ativo ? 'Usuario reativado.' : 'Usuario inativado.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel alterar o status do usuario.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'marcar_lida') {
                $contatoModel = new contatoModel();
                $contatoModel->marcarComoLida($id);
            } elseif ($acao === 'responder_mensagem') {
                $contatoModel = new contatoModel();
                $msg = $contatoModel->getMensagem($id);
                $resposta = trim($_POST['resposta'] ?? '');

                if ($msg && $contatoModel->responderMensagem($id, $resposta)) {
                    $this->enviarRespostaContato($msg['email'], $msg['nome'], $msg['assunto'], $resposta);
                    $mensagem = 'Resposta enviada para ' . $msg['email'] . '.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel enviar a resposta. Escreva um texto antes de enviar.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'editar_hostel_admin') {
                if ($model->atualizarHostelAdmin($id, $_POST)) {
                    $mensagem = 'Recinto atualizado com sucesso.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel atualizar. Confira nome, cidade e preco.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'status_hostel_admin') {
                $ativoHostel = (int) ($_POST['ativo'] ?? 0) === 1;
                if ($model->alternarStatusHostelAdmin($id, $ativoHostel)) {
                    $mensagem = $ativoHostel ? 'Recinto reativado.' : 'Recinto desativado.';
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem = 'Nao foi possivel alterar o status do recinto.';
                    $tipoMensagem = 'erro';
                }
            } elseif ($acao === 'excluir_hostel') {
                $resultadoExclusao = $model->excluirHostel($id);
                $mensagem = $resultadoExclusao['mensagem'];
                $tipoMensagem = $resultadoExclusao['sucesso'] ? 'sucesso' : 'erro';
            }
        }

        $usuarios = $model->listarUsuarios();
        $solicitacoesAnfitriao = $model->listarSolicitacoesAnfitriao();
        $solicitacoesHostel = $model->listarSolicitacoesHostel();
        foreach ($solicitacoesHostel as &$solicitacao) {
            $solicitacao['imagens'] = $model->listarImagensHostel((int) $solicitacao['id']);
        }
        unset($solicitacao);

        $todosHostels = $model->listarTodosHostels();
        $categorias = $model->listarCategorias();

        $contatoModel = new contatoModel();
        $mensagensContato = $contatoModel->listarMensagens();

        $reservaModelAdmin = new reservaModel();
        $resumoFinanceiro = $reservaModelAdmin->resumoFinanceiro();

        require_once ROOT . '/view/admin/index.php';
    }

    // Envia por e-mail a resposta do admin a uma mensagem de contato.
    private function enviarRespostaContato(string $paraEmail, string $paraNome, string $assuntoOriginal, string $resposta): void {
        $mailUsername = getenv('MAIL_USERNAME');
        $mailPassword = getenv('MAIL_PASSWORD');
        if (empty($mailUsername) || empty($mailPassword)) {
            error_log('[EMAIL ERROR] Credenciais de email nao configuradas para responder contato.');
            return;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE') ?: 'tls';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(getenv('MAIL_FROM') ?: $mailUsername, getenv('MAIL_FROM_NAME') ?: 'Travel Hostel');
            $mail->addAddress($paraEmail, $paraNome);
            $mail->isHTML(true);
            $mail->Subject = 'Re: ' . $assuntoOriginal . ' - Travel Hostel';
            $mail->Body = '<p>Ola ' . htmlspecialchars($paraNome) . ',</p>'
                . '<p>Recebemos sua mensagem sobre "' . htmlspecialchars($assuntoOriginal) . '" e aqui esta nossa resposta:</p>'
                . '<blockquote style="border-left:3px solid #3b82f6;margin:0;padding:8px 16px;color:#333;">'
                . nl2br(htmlspecialchars($resposta))
                . '</blockquote>'
                . '<p>Equipe Travel Hostel</p>';
            $mail->send();
        } catch (Exception $e) {
            error_log('[EMAIL ERROR] Falha ao responder contato para ' . $paraEmail . ': ' . $e->getMessage());
        }
    }

    public function anfitriao() {
        if (empty($_SESSION['usuario_email'])) {
            $this->redirect('login');
        }

        $model = new usuarioModel();
        $anfitriao = $model->getUsuarioByEmail($_SESSION['usuario_email']);
        if (!$anfitriao || ($anfitriao['nivel'] ?? '') !== 'anfitriao' || (array_key_exists('ativo', $anfitriao) && !(int) $anfitriao['ativo'])) {
            http_response_code(403);
            $titulo = 'Acesso negado';
            require_once ROOT . '/view/home/index.php';
            return;
        }

        $mensagem = '';
        $tipoMensagem = '';
        $categorias = $model->listarCategorias();
        $acao = $_POST['acao'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editar_hostel') {
            $hostelId = (int) ($_POST['id'] ?? 0);
            if ($model->atualizarHostelAnfitriao($hostelId, (int) $anfitriao['id'], $_POST)) {
                $mensagem = 'Recinto atualizado com sucesso.';
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = 'Nao foi possivel atualizar. Confira nome, cidade e preco.';
                $tipoMensagem = 'erro';
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'status_hostel') {
            $hostelId = (int) ($_POST['id'] ?? 0);
            $ativo = (int) ($_POST['ativo'] ?? 0) === 1;
            if ($model->alternarStatusHostelAnfitriao($hostelId, (int) $anfitriao['id'], $ativo)) {
                $mensagem = $ativo ? 'Recinto reativado e visivel novamente.' : 'Recinto desativado. Ele deixa de aparecer para os viajantes.';
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = 'Nao foi possivel alterar o status do recinto.';
                $tipoMensagem = 'erro';
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Cada solicitacao recebe imagens locais e aguarda moderacao antes de publicar.
            $arquivos = $_FILES['imagens'] ?? null;
            $arquivosValidos = [];
            if ($arquivos && is_array($arquivos['name'] ?? null)) {
                foreach ($arquivos['name'] as $indice => $nomeArquivo) {
                    if (($arquivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $arquivo = [
                        'name' => $nomeArquivo,
                        'tmp_name' => $arquivos['tmp_name'][$indice] ?? '',
                        'error' => $arquivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE,
                        'size' => (int) ($arquivos['size'][$indice] ?? 0),
                    ];
                    $mime = $arquivo['tmp_name'] && is_uploaded_file($arquivo['tmp_name'])
                        ? $this->detectarMimeImagem($arquivo['tmp_name'])
                        : false;
                    if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > 5 * 1024 * 1024 || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $arquivosValidos = [];
                        break;
                    }
                    $arquivosValidos[] = $arquivo;
                }
            }

            if (count($arquivosValidos) < 1 || count($arquivosValidos) > 30) {
                $mensagem = 'Envie de 1 a 30 imagens JPG, PNG ou WEBP com ate 5MB cada.';
                $tipoMensagem = 'erro';
            } else {
                $hostelId = $model->cadastrarSolicitacaoHostel((int) $anfitriao['id'], $_POST);
                if (!$hostelId) {
                    $mensagem = 'Preencha nome, cidade e preco valido para enviar a solicitacao.';
                    $tipoMensagem = 'erro';
                } else {
                    $diretorio = ROOT . '/public/images/hostels';
                    if (!is_dir($diretorio)) {
                        mkdir($diretorio, 0755, true);
                    }
                    foreach ($arquivosValidos as $indice => $arquivo) {
                        $mimeArquivo = $this->detectarMimeImagem($arquivo['tmp_name']);
                        $extensao = match ($mimeArquivo) {
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                            default => 'jpg',
                        };
                        $nomeSeguro = bin2hex(random_bytes(16)) . '.' . $extensao;
                        $destino = $diretorio . '/' . $nomeSeguro;
                        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                            $model->salvarImagemHostel($hostelId, 'public/images/hostels/' . $nomeSeguro, $indice === 0);
                        }
                    }
                    $mensagem = 'Solicitacao enviada. O recinto ficara visivel apos aprovacao do administrador.';
                    $tipoMensagem = 'sucesso';
                }
            }
        }

        $meusRecintos = $model->listarHostelsDoAnfitriao((int) $anfitriao['id']);
        foreach ($meusRecintos as &$recintoComImagens) {
            $recintoComImagens['imagens'] = $model->listarImagensHostel((int) $recintoComImagens['id']);
        }
        unset($recintoComImagens);

        require_once ROOT . '/view/anfitriao/index.php';
    }

    public function uploadFotoCloudinary() {
        header('Content-Type: application/json');

        if (empty($_SESSION['usuario_email'])) {
            http_response_code(401);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nao autenticado.']);
            exit;
        }

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum arquivo enviado ou erro no upload.']);
            exit;
        }

        $file = $_FILES['foto'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            http_response_code(413);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Arquivo muito grande. Maximo 5MB.']);
            exit;
        }

        $mimeType = $this->detectarMimeImagem($file['tmp_name']);
        if (!$mimeType || !in_array($mimeType, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Formato invalido. Use JPG, PNG, GIF ou WEBP.']);
            exit;
        }

        $model = new usuarioModel();
        $resultado = $model->uploadFotoUsuario($_SESSION['usuario_email'], $file['tmp_name']);

        if ($resultado['sucesso']) {
            $_SESSION['usuario_avatar'] = normalizeAvatarUrl($resultado['url'] ?? '');
            http_response_code(200);
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode($resultado);
        }
        exit;
    }

    public function logout() {
        session_destroy();
        $this->redirect('home');
    }
}
