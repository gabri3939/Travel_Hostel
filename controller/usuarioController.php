<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once ROOT . '/model/usuarioModel.php';

class usuarioController {

    private function redirect(string $pagina, array $params = []): void {
        header('Location: ' . routeUrl($pagina, $params));
        exit;
    }

    private function syncUsuarioSession(array $usuario): void {
        $_SESSION['usuario_nome'] = $usuario['nome'] ?? ($_SESSION['usuario_nome'] ?? '');
        $_SESSION['usuario_email'] = $usuario['email'] ?? ($_SESSION['usuario_email'] ?? '');
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
                    SELECT h.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.icone AS categoria_icone
                    FROM hostels h
                    LEFT JOIN categorias c ON c.id = h.categoria_id
                    WHERE h.slug = :slug
                    LIMIT 1
                ");
                $stmt->execute([':slug' => $slug]);
                $hostel = $stmt->fetch(PDO::FETCH_ASSOC);
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
                $hostels = $pdo->query("SELECT nome, slug FROM hostels WHERE slug IS NOT NULL ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
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

            return $mail->send();
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
                $fullUser = $model->getUsuarioByEmail($resultado['email']);
                if ($fullUser) {
                    $this->syncUsuarioSession($fullUser);
                }
                $this->redirect('home');
            }
        }

        require_once ROOT . '/view/login/index.php';
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
            if (isset($_POST['rating'])) {
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
                    $mimeType = mime_content_type($file['tmp_name']);
                    if (!$mimeType || !in_array($mimeType, $allowed, true)) {
                        $mensagem = 'Formato invalido. Use JPG, PNG, GIF ou WEBP.';
                        $tipoMensagem = 'erro';
                    } else {
                        $ext = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
                        if ($ext === '') {
                            $ext = match ($mimeType) {
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/gif' => 'gif',
                                'image/webp' => 'webp',
                                default => 'jpg',
                            };
                        }

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

        require_once ROOT . '/view/usuario/index.php';
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

        $mimeType = mime_content_type($file['tmp_name']);
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
