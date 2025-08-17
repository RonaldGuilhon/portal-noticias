<?php
/**
 * Helper para JWT (JSON Web Token)
 * Portal de Notícias
 */

class JWTHelper {
    private $secret_key;
    private $algorithm;
    
    public function __construct() {
        $this->secret_key = JWT_SECRET ?? 'portal_noticias_secret_key_2024';
        $this->algorithm = 'HS256';
    }
    
    /**
     * Gerar token JWT
     */
    public function gerarToken($payload, $expiration = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret_key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Validar token JWT
     */
    public function validarToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secret_key, true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
        
        if ($signature !== $validSignature) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Decodificar token JWT sem validação
     */
    public function decodificarToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        $payload = $parts[1];
        return json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    }
}