<?php
// BYPASS_CSRF_CHECKS - Adicionado para resolver problemas de CSRF
define('BYPASS_CSRF_CHECKS', false);

/**
 * Sistema de proteção CSRF
 * Gera e valida tokens CSRF para formulários
 */

class CSRF {
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';

    /**
     * Gera um token CSRF
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }

        // Armazenar token com timestamp
        $_SESSION[self::$sessionKey][$token] = time();

        // Limpar tokens antigos (mais de 1 hora)
        self::cleanOldTokens();

        return $token;
    }

    /**
     * Valida um token CSRF
     */
    public static function validateToken($token) {
        if (defined('BYPASS_CSRF_CHECKS') && BYPASS_CSRF_CHECKS) {
            return true;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($token)) {
            return false;
        }

        if (!isset($_SESSION[self::$sessionKey][$token])) {
            return false;
        }

        // Verificar se o token não expirou (1 hora)
        $tokenTime = $_SESSION[self::$sessionKey][$token];
        if (time() - $tokenTime > 3600) {
            unset($_SESSION[self::$sessionKey][$token]);
            return false;
        }

        return true;
    }

    /**
     * Gera campo hidden para formulário
     */
    public static function generateField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Valida token de requisição POST/PUT/DELETE
     */
    public static function validateRequest() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $token = null;
            
            // Tentar obter token de diferentes fontes
            if (isset($_POST[self::$tokenName])) {
                $token = $_POST[self::$tokenName];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            } elseif (isset($_GET[self::$tokenName])) {
                $token = $_GET[self::$tokenName];
            }

            if (!self::validateToken($token)) {
                http_response_code(403);
                echo json_encode(['error' => 'Token CSRF inválido ou expirado'], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }

    /**
     * Limpa tokens antigos
     */
    private static function cleanOldTokens() {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }

        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $token => $timestamp) {
            if ($currentTime - $timestamp > 3600) {
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
    }

    /**
     * Obtém token atual (sem gerar novo)
     */
    public static function getCurrentToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$sessionKey])) {
            return null;
        }

        // Retornar o token mais recente
        $tokens = $_SESSION[self::$sessionKey];
        if (empty($tokens)) {
            return null;
        }

        arsort($tokens);
        return array_key_first($tokens);
    }

    /**
     * Limpa todos os tokens
     */
    public static function clearTokens() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::$sessionKey]);
    }
}