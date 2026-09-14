<?php

// Cliente HTTP simples para a API de Pedidos do PagBank (sem SDK, apenas cURL nativo).
class PagBankService {

    private string $token;
    private string $baseUrl;

    public function __construct() {
        $this->token = (string) getenv('PAGBANK_TOKEN');
        $ambiente = getenv('PAGBANK_ENV') ?: 'sandbox';
        $this->baseUrl = $ambiente === 'production'
            ? 'https://api.pagseguro.com'
            : 'https://sandbox.api.pagseguro.com';
    }

    public function configurado(): bool {
        return $this->token !== '';
    }

    public function criarPedido(array $payload): array {
        return $this->request('POST', '/orders', $payload);
    }

    public function consultarPedido(string $orderId): array {
        return $this->request('GET', '/orders/' . rawurlencode($orderId));
    }

    // Busca um recurso autenticado por URL completa (usado para baixar a imagem do QR Code do PIX).
    public function buscarBinarioAutenticado(string $urlCompleta): array {
        if (!$this->configurado()) {
            return ['sucesso' => false, 'status' => 0, 'corpo' => '', 'contentType' => ''];
        }

        $ch = curl_init($urlCompleta);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: */*',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $corpo = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [
            'sucesso' => $corpo !== false && $status >= 200 && $status < 300,
            'status' => $status,
            'corpo' => $corpo ?: '',
            'contentType' => $contentType ?: 'image/png',
        ];
    }

    private function request(string $metodo, string $caminho, ?array $payload = null): array {
        if (!$this->configurado()) {
            return ['sucesso' => false, 'status' => 0, 'dados' => [], 'erro' => 'PAGBANK_TOKEN nao configurado.'];
        }

        $ch = curl_init($this->baseUrl . $caminho);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $corpo = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($corpo === false) {
            return ['sucesso' => false, 'status' => 0, 'dados' => [], 'erro' => $erroCurl ?: 'Falha de conexao com o PagBank.'];
        }

        $dados = json_decode($corpo, true);

        return [
            'sucesso' => $status >= 200 && $status < 300,
            'status' => $status,
            'dados' => is_array($dados) ? $dados : [],
        ];
    }
}
