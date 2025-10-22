<?php
// Página administrativa simples para testar email e geração de relatório PDF
// Bloqueada em produção por segurança

require_once __DIR__ . '/config.php';

if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    exit('Not Found');
}

require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email_service.php';
require_once __DIR__ . '/report_generator.php';

$message = null;
$reportLink = null;
// Último relatório (para exibir na UI após geração)
$lastReport = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'send_test_email') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $message = ['type' => 'error', 'text' => 'Informe um endereço de email válido.'];
        } else {
            global $emailService;
            $ok = $emailService->testConfiguration($email);
            if ($ok) {
                $message = ['type' => 'success', 'text' => 'Email de teste enviado com sucesso para ' . htmlspecialchars($email) . '.'];
            } else {
                $message = ['type' => 'error', 'text' => 'Falha ao enviar email de teste. Verifique a configuração SMTP.'];
            }
        }
    } elseif ($action === 'generate_infra_report') {
        try {
            $rg = new ReportGenerator();
            $path = $rg->generateInfraestruturaReport([]);
            $basename = basename($path);
            $reportLink = '/pdi_angola/backend/reports/' . $basename;
            $message = ['type' => 'success', 'text' => 'Relatório gerado: ' . htmlspecialchars($basename)];
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'Erro ao gerar relatório: ' . htmlspecialchars($e->getMessage())];
        }
    } elseif ($action === 'generate_lotes_report') {
        try {
            $rg = new ReportGenerator();
            $path = $rg->generateLotesReport([]);
            $basename = basename($path);
            $reportLink = '/pdi_angola/backend/reports/' . $basename;
            $message = ['type' => 'success', 'text' => 'Relatório de Lotes gerado: ' . htmlspecialchars($basename)];
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'Erro ao gerar relatório de Lotes: ' . htmlspecialchars($e->getMessage())];
        }
    } elseif ($action === 'generate_empresas_report') {
        try {
            $rg = new ReportGenerator();
            $path = $rg->generateEmpresasReport([]);
            $basename = basename($path);
            $reportLink = '/pdi_angola/backend/reports/' . $basename;
            $message = ['type' => 'success', 'text' => 'Relatório de Empresas gerado: ' . htmlspecialchars($basename)];
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'Erro ao gerar relatório de Empresas: ' . htmlspecialchars($e->getMessage())];
        }
    } elseif ($action === 'send_system_alert') {
        $alertMsg = trim($_POST['alert_message'] ?? '');
        $alertLevel = $_POST['alert_level'] ?? 'warning';
        $attachLast = isset($_POST['attach_last_report']) && $_POST['attach_last_report'] === '1';
        if ($alertMsg === '') {
            $message = ['type' => 'error', 'text' => 'Informe uma mensagem para o alerta.'];
        } else {
            global $emailService;
            $attachments = [];
            if ($attachLast) {
                try {
                    $rg = new ReportGenerator();
                    $reports = $rg->listReports();
                    if (!empty($reports)) {
                        $attachments[] = ['path' => $reports[0]['path'], 'name' => $reports[0]['filename']];
                        $lastReport = $reports[0];
                    }
                } catch (Exception $e) {
                    // Ignorar falha ao listar relatórios, envia sem anexo
                }
            }
            $sentCount = $emailService->sendSystemAlert($alertMsg, $alertLevel, [], $attachments);
            if ($sentCount > 0) {
                $msgText = 'Alerta de sistema enviado para ' . $sentCount . ' destinatário(s).';
                if (!empty($attachments)) {
                    $msgText .= ' Anexado: ' . htmlspecialchars($attachments[0]['name']);
                }
                $message = ['type' => 'success', 'text' => $msgText];
            } else {
                $message = ['type' => 'error', 'text' => 'Falha ao enviar alerta de sistema. Verifique a configuração de email.'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas Admin - PDI Angola</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #d32f2f; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 16px; margin-bottom: 20px; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #d32f2f; color: #fff; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; }
        button.secondary { background: #1976d2; }
        .msg { padding: 10px 12px; border-radius: 4px; margin-bottom: 16px; }
        .msg.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .msg.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .note { font-size: 12px; color: #666; }
        a.link { color: #1976d2; text-decoration: none; }
    </style>
    <!-- Bloqueada em produção por segurança -->
</head>
<body>
    <div class="container">
        <h1>Ferramentas Administrativas (Dev)</h1>
        <p class="note">Use esta página apenas em desenvolvimento. Em produção ela fica indisponível.</p>

        <?php if ($message): ?>
            <div class="msg <?= htmlspecialchars($message['type']) ?>"><?= $message['text'] ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Teste de Email (SMTP)</h2>
            <form method="post" class="actions">
                <input type="hidden" name="action" value="send_test_email">
                <div style="flex: 1 1 300px;">
                    <label for="email">Email de destino</label>
                    <input type="email" id="email" name="email" placeholder="exemplo@dominio.com" required>
                </div>
                <div>
                    <button type="submit">Enviar Email de Teste</button>
                </div>
            </form>
            <p class="note">Configurações via variáveis de ambiente ou constantes em config.php.</p>
        </div>

        <div class="card">
            <h2>Relatório de Infraestruturas</h2>
            <form method="post" class="actions">
                <input type="hidden" name="action" value="generate_infra_report">
                <div>
                    <button type="submit" class="secondary">Gerar Relatório PDF</button>
                </div>
            </form>
            <?php if ($reportLink): ?>
                <p>Download: <a class="link" href="<?= htmlspecialchars($reportLink) ?>" target="_blank"><?= htmlspecialchars($reportLink) ?></a></p>
            <?php endif; ?>
            <p class="note">Se o PDF não for gerado, um arquivo HTML é salvo como fallback em <code>backend/reports</code>.</p>
        </div>

        <div class="card">
            <h2>Relatórios de Lotes e Empresas</h2>
            <form method="post" class="actions" style="margin-bottom: 10px;">
                <input type="hidden" name="action" value="generate_lotes_report">
                <div>
                    <button type="submit" class="secondary">Gerar Relatório de Lotes (PDF)</button>
                </div>
            </form>
            <form method="post" class="actions">
                <input type="hidden" name="action" value="generate_empresas_report">
                <div>
                    <button type="submit" class="secondary">Gerar Relatório de Empresas (PDF)</button>
                </div>
            </form>
            <?php if ($reportLink): ?>
                <p>Download: <a class="link" href="<?= htmlspecialchars($reportLink) ?>" target="_blank"><?= htmlspecialchars($reportLink) ?></a></p>
            <?php endif; ?>
            <p class="note">Ambos usam o ReportGenerator e salvam em <code>backend/reports</code>.</p>
        </div>

        <div class="card">
            <h2>Enviar Alerta de Sistema</h2>
            <form method="post" class="actions">
                <input type="hidden" name="action" value="send_system_alert">
                <div style="flex: 1 1 300px;">
                    <label for="alert_message">Mensagem do alerta</label>
                    <input type="text" id="alert_message" name="alert_message" placeholder="Descreva o alerta" required>
                </div>
                <div>
                    <label for="alert_level">Nível</label>
                    <select id="alert_level" name="alert_level">
                        <option value="info">Info</option>
                        <option value="warning" selected>Warning</option>
                        <option value="error">Error</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="attach_last_report" value="1"> Anexar último relatório</label>
                </div>
                <div>
                    <button type="submit">Enviar Alerta de Sistema</button>
                </div>
            </form>
            <?php if ($lastReport): ?>
                <p>Último relatório: <code><?= htmlspecialchars($lastReport['filename']) ?></code></p>
            <?php endif; ?>
            <p class="note">Se configurado com PHPMailer+SMTP, anexos são enviados; no fallback nativo não há anexos.</p>
        </div>

    </div>
</body>
</html>
