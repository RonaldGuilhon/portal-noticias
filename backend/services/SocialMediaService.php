<?php
/**
 * Serviço de Integração com Redes Sociais
 * Portal de Notícias
 */

require_once __DIR__ . '/../../config-dev.php';
require_once __DIR__ . '/../utils/CacheManager.php';

class SocialMediaService {
    private $cache_manager;
    private $config;
    
    public function __construct() {
        $this->cache_manager = new CacheManager();
        $this->config = [
            'facebook' => [
                'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
                'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? '',
                'api_version' => 'v18.0'
            ],
            'google' => [
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
                'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? ''
            ],
            'twitter' => [
                'api_key' => $_ENV['TWITTER_API_KEY'] ?? '',
                'api_secret' => $_ENV['TWITTER_API_SECRET'] ?? '',
                'bearer_token' => $_ENV['TWITTER_BEARER_TOKEN'] ?? ''
            ],
            'linkedin' => [
                'client_id' => $_ENV['LINKEDIN_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['LINKEDIN_CLIENT_SECRET'] ?? '',
                'redirect_uri' => $_ENV['LINKEDIN_REDIRECT_URI'] ?? ''
            ]
        ];
    }
    
    /**
     * Obter URL de autenticação OAuth
     */
    public function getAuthUrl($provider, $redirect_uri = null) {
        switch ($provider) {
            case 'facebook':
                return $this->getFacebookAuthUrl($redirect_uri);
            case 'google':
                return $this->getGoogleAuthUrl($redirect_uri);
            case 'linkedin':
                return $this->getLinkedInAuthUrl($redirect_uri);
            default:
                throw new Exception('Provider não suportado: ' . $provider);
        }
    }
    
    /**
     * Processar callback OAuth
     */
    public function handleCallback($provider, $code, $state = null) {
        switch ($provider) {
            case 'facebook':
                return $this->handleFacebookCallback($code);
            case 'google':
                return $this->handleGoogleCallback($code);
            case 'linkedin':
                return $this->handleLinkedInCallback($code);
            default:
                throw new Exception('Provider não suportado: ' . $provider);
        }
    }
    
    /**
     * Compartilhar conteúdo nas redes sociais
     */
    public function shareContent($provider, $content, $access_token) {
        switch ($provider) {
            case 'facebook':
                return $this->shareToFacebook($content, $access_token);
            case 'twitter':
                return $this->shareToTwitter($content, $access_token);
            case 'linkedin':
                return $this->shareToLinkedIn($content, $access_token);
            default:
                throw new Exception('Provider não suportado: ' . $provider);
        }
    }
    
    /**
     * Facebook OAuth URL
     */
    private function getFacebookAuthUrl($redirect_uri) {
        $params = [
            'client_id' => $this->config['facebook']['app_id'],
            'redirect_uri' => $redirect_uri ?: (SITE_URL . '/backend/auth/callback/facebook'),
            'scope' => 'email,public_profile,pages_manage_posts,pages_read_engagement',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16))
        ];
        
        return 'https://www.facebook.com/' . $this->config['facebook']['api_version'] . '/dialog/oauth?' . http_build_query($params);
    }
    
    /**
     * Google OAuth URL
     */
    private function getGoogleAuthUrl($redirect_uri) {
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $redirect_uri ?: $this->config['google']['redirect_uri'],
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'state' => bin2hex(random_bytes(16))
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * LinkedIn OAuth URL
     */
    private function getLinkedInAuthUrl($redirect_uri) {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['linkedin']['client_id'],
            'redirect_uri' => $redirect_uri ?: $this->config['linkedin']['redirect_uri'],
            'scope' => 'r_liteprofile r_emailaddress w_member_social',
            'state' => bin2hex(random_bytes(16))
        ];
        
        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }
    
    /**
     * Processar callback do Facebook
     */
    private function handleFacebookCallback($code) {
        // Trocar código por access token
        $token_url = 'https://graph.facebook.com/' . $this->config['facebook']['api_version'] . '/oauth/access_token';
        $params = [
            'client_id' => $this->config['facebook']['app_id'],
            'client_secret' => $this->config['facebook']['app_secret'],
            'redirect_uri' => SITE_URL . '/backend/auth/callback/facebook',
            'code' => $code
        ];
        
        $response = $this->makeHttpRequest($token_url, 'POST', $params);
        $token_data = json_decode($response, true);
        
        if (!isset($token_data['access_token'])) {
            throw new Exception('Erro ao obter access token do Facebook');
        }
        
        // Obter dados do usuário
        $user_url = 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=' . $token_data['access_token'];
        $user_response = $this->makeHttpRequest($user_url);
        $user_data = json_decode($user_response, true);
        
        return [
            'provider' => 'facebook',
            'provider_id' => $user_data['id'],
            'name' => $user_data['name'],
            'email' => $user_data['email'] ?? '',
            'avatar' => $user_data['picture']['data']['url'] ?? '',
            'access_token' => $token_data['access_token']
        ];
    }
    
    /**
     * Processar callback do Google
     */
    private function handleGoogleCallback($code) {
        // Trocar código por access token
        $token_url = 'https://oauth2.googleapis.com/token';
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $response = $this->makeHttpRequest($token_url, 'POST', $params);
        $token_data = json_decode($response, true);
        
        if (!isset($token_data['access_token'])) {
            throw new Exception('Erro ao obter access token do Google');
        }
        
        // Obter dados do usuário
        $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_data['access_token'];
        $user_response = $this->makeHttpRequest($user_url);
        $user_data = json_decode($user_response, true);
        
        return [
            'provider' => 'google',
            'provider_id' => $user_data['id'],
            'name' => $user_data['name'],
            'email' => $user_data['email'],
            'avatar' => $user_data['picture'] ?? '',
            'access_token' => $token_data['access_token']
        ];
    }
    
    /**
     * Processar callback do LinkedIn
     */
    private function handleLinkedInCallback($code) {
        // Trocar código por access token
        $token_url = 'https://www.linkedin.com/oauth/v2/accessToken';
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['linkedin']['redirect_uri'],
            'client_id' => $this->config['linkedin']['client_id'],
            'client_secret' => $this->config['linkedin']['client_secret']
        ];
        
        $response = $this->makeHttpRequest($token_url, 'POST', $params);
        $token_data = json_decode($response, true);
        
        if (!isset($token_data['access_token'])) {
            throw new Exception('Erro ao obter access token do LinkedIn');
        }
        
        // Obter dados do usuário
        $headers = ['Authorization: Bearer ' . $token_data['access_token']];
        $profile_response = $this->makeHttpRequest('https://api.linkedin.com/v2/people/~:(id,firstName,lastName,profilePicture(displayImage~:playableStreams))', 'GET', null, $headers);
        $email_response = $this->makeHttpRequest('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', 'GET', null, $headers);
        
        $profile_data = json_decode($profile_response, true);
        $email_data = json_decode($email_response, true);
        
        $name = ($profile_data['firstName']['localized']['pt_BR'] ?? $profile_data['firstName']['localized']['en_US'] ?? '') . ' ' . 
                ($profile_data['lastName']['localized']['pt_BR'] ?? $profile_data['lastName']['localized']['en_US'] ?? '');
        
        return [
            'provider' => 'linkedin',
            'provider_id' => $profile_data['id'],
            'name' => trim($name),
            'email' => $email_data['elements'][0]['handle~']['emailAddress'] ?? '',
            'avatar' => $profile_data['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier'] ?? '',
            'access_token' => $token_data['access_token']
        ];
    }
    
    /**
     * Compartilhar no Facebook
     */
    private function shareToFacebook($content, $access_token) {
        $url = 'https://graph.facebook.com/me/feed';
        $params = [
            'message' => $content['message'],
            'link' => $content['url'] ?? '',
            'access_token' => $access_token
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $params);
        return json_decode($response, true);
    }
    
    /**
     * Compartilhar no Twitter
     */
    private function shareToTwitter($content, $access_token) {
        $url = 'https://api.twitter.com/2/tweets';
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ];
        
        $data = [
            'text' => $content['message'] . ' ' . ($content['url'] ?? '')
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', json_encode($data), $headers);
        return json_decode($response, true);
    }
    
    /**
     * Compartilhar no LinkedIn
     */
    private function shareToLinkedIn($content, $access_token) {
        $url = 'https://api.linkedin.com/v2/ugcPosts';
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0'
        ];
        
        $data = [
            'author' => 'urn:li:person:' . $this->getLinkedInPersonId($access_token),
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $content['message']
                    ],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'description' => [
                                'text' => $content['description'] ?? ''
                            ],
                            'originalUrl' => $content['url'] ?? '',
                            'title' => [
                                'text' => $content['title'] ?? ''
                            ]
                        ]
                    ]
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', json_encode($data), $headers);
        return json_decode($response, true);
    }
    
    /**
     * Obter ID da pessoa no LinkedIn
     */
    private function getLinkedInPersonId($access_token) {
        $cache_key = 'linkedin_person_id_' . md5($access_token);
        $cached = $this->cache_manager->get($cache_key);
        
        if ($cached) {
            return $cached;
        }
        
        $headers = ['Authorization: Bearer ' . $access_token];
        $response = $this->makeHttpRequest('https://api.linkedin.com/v2/people/~:(id)', 'GET', null, $headers);
        $data = json_decode($response, true);
        
        $person_id = $data['id'] ?? '';
        if ($person_id) {
            $this->cache_manager->set($cache_key, $person_id, 3600); // Cache por 1 hora
        }
        
        return $person_id;
    }
    
    /**
     * Fazer requisição HTTP
     */
    private function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Portal-Noticias/1.0'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro na requisição HTTP: ' . $error);
        }
        
        if ($http_code >= 400) {
            throw new Exception('Erro HTTP ' . $http_code . ': ' . $response);
        }
        
        return $response;
    }
    
    /**
     * Verificar se provider está configurado
     */
    public function isProviderConfigured($provider) {
        switch ($provider) {
            case 'facebook':
                return !empty($this->config['facebook']['app_id']) && !empty($this->config['facebook']['app_secret']);
            case 'google':
                return !empty($this->config['google']['client_id']) && !empty($this->config['google']['client_secret']);
            case 'twitter':
                return !empty($this->config['twitter']['api_key']) && !empty($this->config['twitter']['api_secret']);
            case 'linkedin':
                return !empty($this->config['linkedin']['client_id']) && !empty($this->config['linkedin']['client_secret']);
            default:
                return false;
        }
    }
    
    /**
     * Obter estatísticas de compartilhamento
     */
    public function getShareStats($url) {
        $cache_key = 'share_stats_' . md5($url);
        $cached = $this->cache_manager->get($cache_key);
        
        if ($cached) {
            return $cached;
        }
        
        $stats = [
            'facebook' => $this->getFacebookShareCount($url),
            'twitter' => 0, // Twitter removeu API pública de contagem
            'linkedin' => $this->getLinkedInShareCount($url),
            'total' => 0
        ];
        
        $stats['total'] = array_sum($stats);
        
        // Cache por 1 hora
        $this->cache_manager->set($cache_key, $stats, 3600);
        
        return $stats;
    }
    
    /**
     * Obter contagem de compartilhamentos do Facebook
     */
    private function getFacebookShareCount($url) {
        try {
            $api_url = 'https://graph.facebook.com/?id=' . urlencode($url) . '&fields=engagement&access_token=' . $this->config['facebook']['app_id'] . '|' . $this->config['facebook']['app_secret'];
            $response = $this->makeHttpRequest($api_url);
            $data = json_decode($response, true);
            
            return $data['engagement']['share_count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obter contagem de compartilhamentos do LinkedIn
     */
    private function getLinkedInShareCount($url) {
        // LinkedIn não oferece API pública para contagem de compartilhamentos
        return 0;
    }
}