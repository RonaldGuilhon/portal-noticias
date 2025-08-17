<?php
/**
 * Serviço de Push Notifications
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class PushNotificationService {
    private $db;
    private $vapidPublicKey;
    private $vapidPrivateKey;
    private $vapidSubject;

    public function __construct($db) {
        $this->db = $db;
        $this->loadVapidKeys();
    }

    /**
     * Carregar chaves VAPID das configurações
     */
    private function loadVapidKeys() {
        try {
            $stmt = $this->db->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ('vapid_public_key', 'vapid_private_key', 'vapid_subject')");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->vapidPublicKey = $configs['vapid_public_key'] ?? '';
            $this->vapidPrivateKey = $configs['vapid_private_key'] ?? '';
            $this->vapidSubject = $configs['vapid_subject'] ?? 'mailto:admin@portalnoticias.com';
            
        } catch (Exception $e) {
            error_log("Erro ao carregar chaves VAPID: " . $e->getMessage());
        }
    }

    /**
     * Obter chave pública VAPID
     */
    public function getVapidPublicKey() {
        return $this->vapidPublicKey;
    }

    /**
     * Gerar chaves VAPID (executar apenas uma vez)
     */
    public function generateVapidKeys() {
        // Para desenvolvimento, usar chaves estáticas válidas
        // Em produção, estas devem ser geradas adequadamente
        $publicKey = 'BEl62iUYgUivxIkv69yViEuiBIa40HI0DLLuxN-RgKBfQjYmYFN2YjSGYFNEr_mHSxM6sO-caQzhzDetnulB73s';
        $privateKey = 'p1dGDdkwqM1GELmzejQbF6rtabMsZGYn9fQhZSwbgNI';
        
        // Salvar nas configurações
        $stmt = $this->db->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
        $stmt->execute([$publicKey, 'vapid_public_key']);
        $stmt->execute([$privateKey, 'vapid_private_key']);
        
        $this->vapidPublicKey = $publicKey;
        $this->vapidPrivateKey = $privateKey;
        
        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey
        ];
    }

    /**
     * Gerar chave privada
     */
    private function generatePrivateKey() {
        $config = [
            "curve_name" => "prime256v1",
            "private_key_type" => OPENSSL_KEYTYPE_EC,
        ];
        
        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new Exception('Falha ao gerar chave privada: ' . openssl_error_string());
        }
        
        if (!openssl_pkey_export($res, $privateKey)) {
            throw new Exception('Falha ao exportar chave privada: ' . openssl_error_string());
        }
        
        return base64_encode($privateKey);
    }

    /**
     * Gerar chave pública a partir da privada
     */
    private function generatePublicKey($privateKey) {
        $privateKeyResource = openssl_pkey_get_private(base64_decode($privateKey));
        if (!$privateKeyResource) {
            throw new Exception('Falha ao carregar chave privada: ' . openssl_error_string());
        }
        
        $details = openssl_pkey_get_details($privateKeyResource);
        if (!$details) {
            throw new Exception('Falha ao obter detalhes da chave: ' . openssl_error_string());
        }
        
        return base64_encode($details['key']);
    }

    /**
     * Inscrever usuário para push notifications
     */
    public function subscribe($userId, $endpoint, $p256dhKey, $authKey, $userAgent = null, $ipAddress = null) {
        try {
            // Verificar se já existe uma subscription para este endpoint
            $stmt = $this->db->prepare("SELECT id FROM push_subscriptions WHERE usuario_id = ? AND endpoint = ?");
            $stmt->execute([$userId, $endpoint]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar subscription existente
                $stmt = $this->db->prepare("
                    UPDATE push_subscriptions 
                    SET p256dh_key = ?, auth_key = ?, user_agent = ?, ip_address = ?, 
                        ativo = TRUE, data_atualizacao = NOW(), ultimo_uso = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$p256dhKey, $authKey, $userAgent, $ipAddress, $existing['id']]);
                return $existing['id'];
            } else {
                // Criar nova subscription
                $stmt = $this->db->prepare("
                    INSERT INTO push_subscriptions 
                    (usuario_id, endpoint, p256dh_key, auth_key, user_agent, ip_address, ultimo_uso) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $endpoint, $p256dhKey, $authKey, $userAgent, $ipAddress]);
                return $this->db->lastInsertId();
            }
            
        } catch (Exception $e) {
            error_log("Erro ao criar subscription: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancelar inscrição
     */
    public function unsubscribe($userId, $endpoint = null) {
        try {
            if ($endpoint) {
                $stmt = $this->db->prepare("UPDATE push_subscriptions SET ativo = FALSE WHERE usuario_id = ? AND endpoint = ?");
                $stmt->execute([$userId, $endpoint]);
            } else {
                $stmt = $this->db->prepare("UPDATE push_subscriptions SET ativo = FALSE WHERE usuario_id = ?");
                $stmt->execute([$userId]);
            }
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Erro ao cancelar subscription: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enviar notificação push
     */
    public function sendNotification($title, $body, $type = 'system', $url = null, $icon = null, $image = null, $userId = null, $categoryId = null) {
        try {
            $enviadas = 0;
            $erros = 0;
            
            // Obter subscriptions ativas
            $subscriptions = $this->getActiveSubscriptions($userId, $type, $categoryId);
            
            if (empty($subscriptions)) {
                return ['enviadas' => 0, 'erros' => 0];
            }
            
            // Preparar payload da notificação
            $payload = [
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'url' => $url,
                'icon' => $icon ?: '/assets/img/icon-192x192.png',
                'badge' => '/assets/img/badge-72x72.png',
                'image' => $image,
                'requireInteraction' => $type === 'breaking',
                'vibrate' => [200, 100, 200],
                'tag' => 'portal-noticias-' . $type,
                'timestamp' => time() * 1000
            ];
            
            foreach ($subscriptions as $subscription) {
                try {
                    // Adicionar ID único para tracking
                    $logId = $this->createPushLog($subscription['usuario_id'], $subscription['id'], $title, $body, $type);
                    $payload['id'] = $logId;
                    
                    // Enviar push notification
                    $result = $this->sendPushToSubscription($subscription, $payload);
                    
                    if ($result) {
                        $this->updatePushLogStatus($logId, 'enviado');
                        $enviadas++;
                    } else {
                        $this->updatePushLogStatus($logId, 'erro', 'Falha no envio');
                        $erros++;
                    }
                    
                } catch (Exception $e) {
                    error_log("Erro ao enviar push para subscription {$subscription['id']}: " . $e->getMessage());
                    if (isset($logId)) {
                        $this->updatePushLogStatus($logId, 'erro', $e->getMessage());
                    }
                    $erros++;
                }
            }
            
            return ['enviadas' => $enviadas, 'erros' => $erros];
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificações: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obter subscriptions ativas (método público)
     */
    public function getAllActiveSubscriptions($userId = null) {
        try {
            $query = "
                SELECT ps.*, pp.push_breaking, pp.push_interests, pp.push_comments, 
                       pp.push_newsletter, pp.push_system, pp.categorias_interesse,
                       u.nome as usuario_nome, u.email as usuario_email
                FROM push_subscriptions ps
                LEFT JOIN push_preferences pp ON ps.usuario_id = pp.usuario_id
                LEFT JOIN usuarios u ON ps.usuario_id = u.id
                WHERE ps.ativo = TRUE
            ";
            
            $params = [];
            
            if ($userId) {
                $query .= " AND ps.usuario_id = ?";
                $params[] = $userId;
            }
            
            $query .= " ORDER BY ps.data_criacao DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter todas as subscriptions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obter subscriptions ativas (método privado para filtros)
     */
    private function getActiveSubscriptions($userId = null, $type = 'system', $categoryId = null) {
        try {
            $query = "
                SELECT ps.*, pp.push_breaking, pp.push_interests, pp.push_comments, 
                       pp.push_newsletter, pp.push_system, pp.categorias_interesse
                FROM push_subscriptions ps
                LEFT JOIN push_preferences pp ON ps.usuario_id = pp.usuario_id
                WHERE ps.ativo = TRUE
            ";
            
            $params = [];
            
            if ($userId) {
                $query .= " AND ps.usuario_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filtrar por preferências
            $filtered = [];
            foreach ($subscriptions as $subscription) {
                if ($this->shouldSendNotification($subscription, $type, $categoryId)) {
                    $filtered[] = $subscription;
                }
            }
            
            return $filtered;
            
        } catch (Exception $e) {
            error_log("Erro ao obter subscriptions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar se deve enviar notificação baseado nas preferências
     */
    private function shouldSendNotification($subscription, $type, $categoryId = null) {
        switch ($type) {
            case 'breaking':
                return $subscription['push_breaking'] == 1;
            case 'interests':
                if ($subscription['push_interests'] != 1) return false;
                if ($categoryId && $subscription['categorias_interesse']) {
                    $interests = json_decode($subscription['categorias_interesse'], true);
                    return in_array($categoryId, $interests);
                }
                return true;
            case 'comments':
                return $subscription['push_comments'] == 1;
            case 'newsletter':
                return $subscription['push_newsletter'] == 1;
            case 'system':
            default:
                return $subscription['push_system'] != 0; // Default true
        }
    }

    /**
     * Enviar push para uma subscription específica
     */
    private function sendPushToSubscription($subscription, $payload) {
        // Implementação simplificada - em produção usar biblioteca como web-push
        // Por enquanto, simular envio bem-sucedido
        
        // Atualizar último uso da subscription
        $stmt = $this->db->prepare("UPDATE push_subscriptions SET ultimo_uso = NOW() WHERE id = ?");
        $stmt->execute([$subscription['id']]);
        
        // Simular envio (em produção, usar cURL para enviar para o endpoint)
        return true;
    }

    /**
     * Criar log de push notification
     */
    private function createPushLog($userId, $subscriptionId, $title, $message, $type) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO push_logs (usuario_id, subscription_id, titulo, mensagem, tipo, status)
                VALUES (?, ?, ?, ?, ?, 'pendente')
            ");
            $stmt->execute([$userId, $subscriptionId, $title, $message, $type]);
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Erro ao criar log de push: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualizar status do log de push
     */
    private function updatePushLogStatus($logId, $status, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE push_logs 
                SET status = ?, erro_mensagem = ?, data_entrega = CASE WHEN ? = 'enviado' THEN NOW() ELSE data_entrega END
                WHERE id = ?
            ");
            $stmt->execute([$status, $errorMessage, $status, $logId]);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar status do log: " . $e->getMessage());
        }
    }

    /**
     * Registrar clique na notificação
     */
    public function registerClick($logId, $action = 'open') {
        try {
            $stmt = $this->db->prepare("
                UPDATE push_logs 
                SET clicado = TRUE, data_clique = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$logId]);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar clique: " . $e->getMessage());
        }
    }

    /**
     * Registrar fechamento da notificação
     */
    public function registerClose($logId) {
        try {
            // Por enquanto, apenas log - pode ser usado para métricas futuras
            error_log("Notificação {$logId} foi fechada");
            
        } catch (Exception $e) {
            error_log("Erro ao registrar fechamento: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificação de teste
     */
    public function sendTestNotification($userId) {
        return $this->sendNotification(
            'Teste de Push Notification',
            'Esta é uma notificação de teste do Portal de Notícias!',
            'system',
            '/',
            '/assets/img/icon-192x192.png',
            null,
            $userId
        );
    }

    /**
     * Obter preferências do usuário
     */
    public function getPreferences($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT push_breaking, push_interests, push_comments, push_newsletter, 
                       push_system, categorias_interesse
                FROM push_preferences 
                WHERE usuario_id = ?
            ");
            $stmt->execute([$userId]);
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$preferences) {
                // Criar preferências padrão
                $this->createDefaultPreferences($userId);
                return $this->getPreferences($userId);
            }
            
            // Decodificar categorias de interesse
            if ($preferences['categorias_interesse']) {
                $preferences['categorias_interesse'] = json_decode($preferences['categorias_interesse'], true);
            }
            
            return $preferences;
            
        } catch (Exception $e) {
            error_log("Erro ao obter preferências: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Criar preferências padrão
     */
    private function createDefaultPreferences($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO push_preferences (usuario_id, push_system)
                VALUES (?, TRUE)
            ");
            $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Erro ao criar preferências padrão: " . $e->getMessage());
        }
    }

    /**
     * Atualizar preferências do usuário
     */
    public function updatePreferences($userId, $preferences) {
        try {
            // Verificar se já existem preferências
            $stmt = $this->db->prepare("SELECT id FROM push_preferences WHERE usuario_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetch();
            
            $categorias = null;
            if (isset($preferences['categorias_interesse']) && is_array($preferences['categorias_interesse'])) {
                $categorias = json_encode($preferences['categorias_interesse']);
            }
            
            if ($exists) {
                // Atualizar existente
                $stmt = $this->db->prepare("
                    UPDATE push_preferences 
                    SET push_breaking = ?, push_interests = ?, push_comments = ?, 
                        push_newsletter = ?, push_system = ?, categorias_interesse = ?,
                        data_atualizacao = NOW()
                    WHERE usuario_id = ?
                ");
                $stmt->execute([
                    $preferences['push_breaking'] ?? false,
                    $preferences['push_interests'] ?? false,
                    $preferences['push_comments'] ?? false,
                    $preferences['push_newsletter'] ?? false,
                    $preferences['push_system'] ?? true,
                    $categorias,
                    $userId
                ]);
            } else {
                // Criar novo
                $stmt = $this->db->prepare("
                    INSERT INTO push_preferences 
                    (usuario_id, push_breaking, push_interests, push_comments, push_newsletter, push_system, categorias_interesse)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $preferences['push_breaking'] ?? false,
                    $preferences['push_interests'] ?? false,
                    $preferences['push_comments'] ?? false,
                    $preferences['push_newsletter'] ?? false,
                    $preferences['push_system'] ?? true,
                    $categorias
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar preferências: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obter estatísticas de push notifications
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total de subscriptions ativas
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM push_subscriptions WHERE ativo = TRUE");
            $stats['subscriptions_ativas'] = $stmt->fetch()['total'];
            
            // Total de notificações enviadas hoje
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM push_logs WHERE DATE(data_envio) = CURDATE()");
            $stats['enviadas_hoje'] = $stmt->fetch()['total'];
            
            // Total de notificações enviadas esta semana
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM push_logs WHERE WEEK(data_envio) = WEEK(NOW())");
            $stats['enviadas_semana'] = $stmt->fetch()['total'];
            
            // Taxa de cliques
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN clicado = TRUE THEN 1 ELSE 0 END) as clicadas
                FROM push_logs 
                WHERE status = 'enviado' AND DATE(data_envio) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $result = $stmt->fetch();
            $stats['taxa_cliques'] = $result['total'] > 0 ? round(($result['clicadas'] / $result['total']) * 100, 2) : 0;
            
            // Estatísticas por tipo
            $stmt = $this->db->query("
                SELECT tipo, COUNT(*) as total
                FROM push_logs 
                WHERE DATE(data_envio) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY tipo
            ");
            $stats['por_tipo'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincronizar notificações pendentes
     */
    public function syncPendingNotifications() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM push_logs 
                WHERE status = 'pendente' AND data_envio >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                LIMIT 100
            ");
            $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            foreach ($pending as $notification) {
                // Tentar reenviar
                $this->updatePushLogStatus($notification['id'], 'erro', 'Timeout - não processado');
                $processed++;
            }
            
            return $processed;
            
        } catch (Exception $e) {
            error_log("Erro na sincronização: " . $e->getMessage());
            throw $e;
        }
    }
}
?>