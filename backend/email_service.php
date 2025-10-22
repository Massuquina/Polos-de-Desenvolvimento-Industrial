<?php
/**
 * Serviço de notificações por email
 * Versão melhorada com suporte a PHPMailer e SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Tentar carregar PHPMailer via Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    define('PHPMAILER_AVAILABLE', true);
} else {
    define('PHPMAILER_AVAILABLE', false);
}

class EmailService {
    private $logger;
    private $config;
    
    public function __construct() {
        global $logger;
        $this->logger = $logger;
        
        // Configurações de email (podem ser movidas para .env)
        $this->config = [
            'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
            'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
            'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
            'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls', // tls ou ssl
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@pdi.gov.ao',
            'from_name' => $_ENV['FROM_NAME'] ?? 'PDI Angola - Sistema de Gestão',
            // Converter string/boolean para boolean de forma robusta
            'enabled' => filter_var($_ENV['EMAIL_ENABLED'] ?? (ENVIRONMENT === 'production' ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN),
            // Controla se deve usar o fallback nativo mail() quando SMTP não está configurado
            'native_fallback' => filter_var($_ENV['EMAIL_NATIVE_FALLBACK'] ?? 'false', FILTER_VALIDATE_BOOLEAN)
        ];
    }
    
    /**
     * Envia um email usando PHPMailer (preferido) ou mail() nativo
     */
    public function sendEmail($to, $subject, $body, $isHtml = true, $attachments = []) {
        if (!$this->config['enabled']) {
            $this->logger->info('Email sending is disabled', [
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }
        
        // Usar PHPMailer se disponível e minimamente configurado
        if (
            PHPMAILER_AVAILABLE &&
            !empty($this->config['smtp_host']) &&
            !empty($this->config['smtp_username']) &&
            !empty($this->config['smtp_password'])
        ) {
            return $this->sendWithPHPMailer($to, $subject, $body, $isHtml, $attachments);
        }
        
        // Fallback para mail() nativo, se habilitado explicitamente
        if ($this->config['native_fallback']) {
            return $this->sendWithNativeMail($to, $subject, $body, $isHtml);
        }

        // SMTP não configurado e fallback desabilitado
        $this->logger->error('Email not sent: SMTP not configured and native fallback disabled', [
            'to' => $to,
            'subject' => $subject,
            'smtp_host' => $this->config['smtp_host'],
            'smtp_username' => $this->config['smtp_username'] ? '[set]' : '[empty]'
        ]);
        return false;
    }
    
    /**
     * Envia email usando PHPMailer com SMTP
     */
    private function sendWithPHPMailer($to, $subject, $body, $isHtml = true, $attachments = []) {
        try {
            $mail = new PHPMailer(true);
            // Ativar debug SMTP opcionalmente
            $enableDebug = filter_var($_ENV['SMTP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN) || (defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG');
            if ($enableDebug) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $logger = $this->logger;
                $mail->Debugoutput = function ($str) use ($logger) {
                    $logger->info('SMTP debug', ['message' => $str]);
                };
            }
            
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_secure'];
            $mail->Port = $this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Configurações do remetente
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addReplyTo($this->config['from_email'], $this->config['from_name']);
            
            // Destinatário
            if (is_array($to)) {
                foreach ($to as $email) {
                    $mail->addAddress($email);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Anexos
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                } else {
                    $mail->addAttachment($attachment);
                }
            }
            
            // Conteúdo
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if ($isHtml) {
                $mail->AltBody = strip_tags($body);
            }
            
            $mail->send();
            
            $this->logger->info('Email sent successfully via PHPMailer', [
                'to' => $to,
                'subject' => $subject,
                'method' => 'PHPMailer SMTP'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to send email via PHPMailer', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Envia email usando mail() nativo (fallback)
     */
    private function sendWithNativeMail($to, $subject, $body, $isHtml = true) {
        if (!$this->config['enabled']) {
            $this->logger->info('Email sending is disabled', [
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }
        
        try {
            // Headers básicos
            $headers = [
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                'Reply-To: ' . $this->config['from_email'],
                'X-Mailer: PDI Angola System'
            ];
            
            if ($isHtml) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            }
            
            // Enviar email usando mail() (básico)
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                $this->logger->info('Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject
                ]);
            } else {
                $this->logger->error('Failed to send email', [
                    'to' => $to,
                    'subject' => $subject
                ]);
            }
            
            return $success;
            
        } catch (Exception $e) {
            $this->logger->error('Email sending exception', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Envia notificação de nova infraestrutura
     */
    public function notifyNewInfraestrutura($infraestrutura, $recipients = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Nova Infraestrutura Registada - PDI Angola';
        $body = $this->generateInfraestruturaNotificationHTML($infraestrutura, 'criada');
        
        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendEmail($email, $subject, $body)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'infraestrutura', $infraestrutura['id'], [
            'type' => 'new_infraestrutura',
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia notificação de atualização de infraestrutura
     */
    public function notifyUpdateInfraestrutura($infraestrutura, $recipients = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Infraestrutura Atualizada - PDI Angola';
        $body = $this->generateInfraestruturaNotificationHTML($infraestrutura, 'atualizada');
        
        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendEmail($email, $subject, $body)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'infraestrutura', $infraestrutura['id'], [
            'type' => 'update_infraestrutura',
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia notificação de nova empresa
     */
    public function notifyNewEmpresa($empresa, $recipients = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Nova Empresa Registada - PDI Angola';
        $body = $this->generateEmpresaNotificationHTML($empresa, 'registada');
        
        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendEmail($email, $subject, $body)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'empresa', $empresa['id'], [
            'type' => 'new_empresa',
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia notificação de ocupação de lote
     */
    public function notifyLoteOcupado($lote, $empresa, $recipients = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Lote Ocupado - PDI Angola';
        $body = $this->generateLoteOcupadoNotificationHTML($lote, $empresa);
        
        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendEmail($email, $subject, $body)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'lote', $lote['id'], [
            'type' => 'lote_ocupado',
            'empresa_id' => $empresa['id'],
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia relatório periódico
     */
    public function sendPeriodicReport($reportPath, $recipients = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Relatório Periódico - PDI Angola - ' . date('d/m/Y');
        $body = $this->generatePeriodicReportHTML();
        
        $sent = 0;
        foreach ($recipients as $email) {
            // TODO: Implementar anexo do relatório
            if ($this->sendEmail($email, $subject, $body)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'report', null, [
            'type' => 'periodic_report',
            'report_path' => $reportPath,
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia alerta de sistema
     */
    public function sendSystemAlert($message, $level = 'warning', $recipients = [], $attachments = []) {
        if (empty($recipients)) {
            $recipients = $this->getAdminEmails();
        }
        
        $subject = 'Alerta do Sistema - PDI Angola - ' . strtoupper($level);
        $body = $this->generateSystemAlertHTML($message, $level);
        
        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendEmail($email, $subject, $body, true, $attachments)) {
                $sent++;
            }
        }
        
        $this->logger->userActivity('send_notification', 'system', null, [
            'type' => 'system_alert',
            'level' => $level,
            'message' => $message,
            'recipients_count' => count($recipients),
            'sent_count' => $sent
        ]);
        
        return $sent;
    }
    
    /**
     * Envia notificação de boas-vindas para novo usuário
     */
    public function sendWelcomeEmail($user, $temporaryPassword = null) {
        $subject = 'Bem-vindo ao Sistema PDI Angola';
        $body = $this->generateWelcomeHTML($user, $temporaryPassword);
        
        $success = $this->sendEmail($user['email'], $subject, $body);
        
        $this->logger->userActivity('send_notification', 'user', $user['id'], [
            'type' => 'welcome_email',
            'success' => $success
        ]);
        
        return $success;
    }
    
    /**
     * Envia notificação de reset de senha
     */
    public function sendPasswordResetEmail($user, $resetToken) {
        $subject = 'Reset de Senha - PDI Angola';
        $body = $this->generatePasswordResetHTML($user, $resetToken);
        
        $success = $this->sendEmail($user['email'], $subject, $body);
        
        $this->logger->userActivity('send_notification', 'user', $user['id'], [
            'type' => 'password_reset',
            'success' => $success
        ]);
        
        return $success;
    }
    
    /**
     * Obtém emails dos administradores
     */
    private function getAdminEmails() {
        try {
            $db = new Database();
            $sql = "SELECT u.email 
                    FROM usuarios u 
                    JOIN niveis_acesso na ON u.nivel_acesso_id = na.id 
                    WHERE na.nome = 'Administrador' AND u.ativo = 1";
            
            $admins = $db->select($sql);
            return array_column($admins, 'email');
            
        } catch (Exception $e) {
            $this->logger->error('Failed to get admin emails', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Gera HTML para notificação de infraestrutura
     */
    private function generateInfraestruturaNotificationHTML($infraestrutura, $action) {
        $userName = 'Sistema';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userName = $_SESSION['user']['nome'] ?? 'Sistema';
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>PDI Angola - Notificação</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Infraestrutura {$action}</h3>
            
            <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p><strong>Polo:</strong> {$infraestrutura['polo_nome']}</p>
                <p><strong>Província:</strong> {$infraestrutura['provincia_nome']}</p>
                <p><strong>Município:</strong> {$infraestrutura['municipio_nome']}</p>
                " . ($infraestrutura['sector_nome'] ? "<p><strong>Sector:</strong> {$infraestrutura['sector_nome']}</p>" : "") . "
                <p><strong>Estado:</strong> {$infraestrutura['estado_atual']}</p>
                " . ($infraestrutura['area'] ? "<p><strong>Área:</strong> " . number_format($infraestrutura['area'], 2) . " hectares</p>" : "") . "
            </div>
            
            <p><strong>Ação realizada por:</strong> {$userName}</p>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para notificação de empresa
     */
    private function generateEmpresaNotificationHTML($empresa, $action) {
        $userName = 'Sistema';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userName = $_SESSION['user']['nome'] ?? 'Sistema';
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>PDI Angola - Notificação</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Empresa {$action}</h3>
            
            <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p><strong>Nome da Empresa:</strong> {$empresa['nome_empresa']}</p>
                " . ($empresa['nif'] ? "<p><strong>NIF:</strong> {$empresa['nif']}</p>" : "") . "
                <p><strong>Polo:</strong> {$empresa['polo_nome']}</p>
                " . ($empresa['lote_nome'] ? "<p><strong>Lote:</strong> {$empresa['lote_nome']}</p>" : "") . "
                " . ($empresa['sector_nome'] ? "<p><strong>Sector:</strong> {$empresa['sector_nome']}</p>" : "") . "
                <p><strong>Status:</strong> " . ucfirst($empresa['status']) . "</p>
                " . ($empresa['num_funcionarios'] ? "<p><strong>Funcionários:</strong> " . number_format($empresa['num_funcionarios']) . "</p>" : "") . "
            </div>
            
            <p><strong>Ação realizada por:</strong> {$userName}</p>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para notificação de lote ocupado
     */
    private function generateLoteOcupadoNotificationHTML($lote, $empresa) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>PDI Angola - Notificação</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Lote Ocupado</h3>
            
            <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h4>Informações do Lote:</h4>
                <p><strong>Nome do Lote:</strong> {$lote['nome_lote']}</p>
                <p><strong>Polo:</strong> {$lote['polo_nome']}</p>
                " . ($lote['area_m2'] ? "<p><strong>Área:</strong> " . number_format($lote['area_m2'], 2) . " m²</p>" : "") . "
                
                <h4>Empresa Ocupante:</h4>
                <p><strong>Nome:</strong> {$empresa['nome_empresa']}</p>
                " . ($empresa['nif'] ? "<p><strong>NIF:</strong> {$empresa['nif']}</p>" : "") . "
                " . ($empresa['sector_nome'] ? "<p><strong>Sector:</strong> {$empresa['sector_nome']}</p>" : "") . "
                " . ($empresa['num_funcionarios'] ? "<p><strong>Funcionários:</strong> " . number_format($empresa['num_funcionarios']) . "</p>" : "") . "
            </div>
            
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para relatório periódico
     */
    private function generatePeriodicReportHTML() {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>PDI Angola - Relatório Periódico</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Relatório Periódico - " . date('d/m/Y') . "</h3>
            
            <p>Caro(a) administrador(a),</p>
            
            <p>Segue em anexo o relatório periódico do Sistema PDI Angola com as estatísticas e informações mais recentes sobre as infraestruturas, lotes e empresas nos Polos de Desenvolvimento Industrial.</p>
            
            <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p><strong>Período:</strong> " . date('d/m/Y', strtotime('-7 days')) . " a " . date('d/m/Y') . "</p>
                <p><strong>Gerado em:</strong> " . date('d/m/Y H:i:s') . "</p>
            </div>
            
            <p>Para mais detalhes, acesse o sistema em: <a href='" . APP_URL . "'>" . APP_URL . "</a></p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para alerta de sistema
     */
    private function generateSystemAlertHTML($message, $level) {
        $colors = [
            'info' => '#2196f3',
            'warning' => '#ff9800',
            'error' => '#f44336',
            'critical' => '#d32f2f'
        ];
        
        $color = $colors[$level] ?? $colors['info'];
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid {$color}; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: {$color}; margin: 0;'>PDI Angola - Alerta do Sistema</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <div style='background: {$color}; color: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h3 style='margin: 0 0 10px 0;'>Alerta de " . strtoupper($level) . "</h3>
                <p style='margin: 0;'>{$message}</p>
            </div>
            
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            
            <p>Por favor, verifique o sistema e tome as medidas necessárias.</p>
            
            <p>Acesse o sistema em: <a href='" . APP_URL . "'>" . APP_URL . "</a></p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para email de boas-vindas
     */
    private function generateWelcomeHTML($user, $temporaryPassword) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>Bem-vindo ao PDI Angola</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Olá, {$user['nome']}!</h3>
            
            <p>Bem-vindo ao Sistema PDI Angola. Sua conta foi criada com sucesso.</p>
            
            <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h4>Dados de Acesso:</h4>
                <p><strong>Email:</strong> {$user['email']}</p>
                <p><strong>Nível de Acesso:</strong> {$user['nivel_nome']}</p>
                " . ($temporaryPassword ? "<p><strong>Senha Temporária:</strong> {$temporaryPassword}</p>" : "") . "
            </div>
            
            " . ($temporaryPassword ? "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p><strong>Importante:</strong> Por favor, altere sua senha no primeiro acesso por motivos de segurança.</p>
            </div>" : "") . "
            
            <p>Acesse o sistema em: <a href='" . APP_URL . "'>" . APP_URL . "</a></p>
            
            <p>Se tiver dúvidas, entre em contato com o administrador do sistema.</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Gera HTML para reset de senha
     */
    private function generatePasswordResetHTML($user, $resetToken) {
        $resetUrl = APP_URL . "/reset-password.php?token=" . $resetToken;
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px; color: #333;'>
            <div style='border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px;'>
                <h2 style='color: #d32f2f; margin: 0;'>PDI Angola - Reset de Senha</h2>
                <p style='margin: 5px 0; color: #666;'>Sistema de Gestão de Infraestruturas</p>
            </div>
            
            <h3>Olá, {$user['nome']}!</h3>
            
            <p>Recebemos uma solicitação para redefinir a senha da sua conta no Sistema PDI Angola.</p>
            
            <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p>Para redefinir sua senha, clique no link abaixo:</p>
                <p><a href='{$resetUrl}' style='color: #d32f2f; font-weight: bold;'>Redefinir Senha</a></p>
            </div>
            
            <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <p><strong>Importante:</strong></p>
                <ul>
                    <li>Este link é válido por 24 horas</li>
                    <li>Se você não solicitou esta redefinição, ignore este email</li>
                    <li>Por motivos de segurança, não compartilhe este link</li>
                </ul>
            </div>
            
            <p>Se o link não funcionar, copie e cole a URL abaixo no seu navegador:</p>
            <p style='word-break: break-all; color: #666;'>{$resetUrl}</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='color: #666; font-size: 12px;'>
                Esta é uma notificação automática do Sistema PDI Angola.<br>
                Por favor, não responda a este email.
            </p>
        </body>
        </html>
        ";
    }
    
    /**
     * Configura as definições de email
     */
    public function configure($config) {
        $this->config = array_merge($this->config, $config);
        
        $this->logger->info('Email service configured', [
            'smtp_host' => $this->config['smtp_host'],
            'enabled' => $this->config['enabled']
        ]);
    }
    
    /**
     * Testa a configuração de email
     */
    public function testConfiguration($testEmail) {
        $subject = 'Teste de Configuração - PDI Angola';
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 20px;'>
            <h2>Teste de Configuração</h2>
            <p>Se você recebeu este email, a configuração do sistema de email está funcionando corretamente.</p>
            <p>Data/Hora: " . date('d/m/Y H:i:s') . "</p>
        </body>
        </html>
        ";
        
        return $this->sendEmail($testEmail, $subject, $body);
    }
}

// Instância global do serviço de email
$GLOBALS['emailService'] = new EmailService();
?>

