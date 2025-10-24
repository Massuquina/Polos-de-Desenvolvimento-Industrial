<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../database.php';
if(defined('ENVIRONMENT')&&ENVIRONMENT==='production'){http_response_code(404);echo json_encode(['error'=>'Endpoint não disponível em produção'],JSON_UNESCAPED_UNICODE);exit;}
header('Content-Type: application/json; charset=utf-8');
function tExists($db,$t){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?",[$t]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function fkExists($db,$t,$n){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=? AND CONSTRAINT_TYPE='FOREIGN KEY'",[$t,$n]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function getEngine($db,$t){$r=$db->selectOne("SELECT ENGINE eng FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?",[$t]);return isset($r['eng'])?$r['eng']:null;}
function trigExists($db,$n){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?",[$n]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
try{ $db=new Database();$changes=['created_tables'=>[],'converted_engines'=>[],'added_fks'=>[],'dropped_triggers'=>[],'added_triggers'=>[],'updates'=>[],'notes'=>[]];
  // Garantir InnoDB para FKs
  foreach(['empresas','polos_desenvolvimento','infraestruturas','lotes'] as $t){ if(tExists($db,$t)&&getEngine($db,$t)!=='InnoDB'){ try{$db->update("ALTER TABLE {$t} ENGINE=InnoDB");$changes['converted_engines'][]=$t;}catch(Exception $e){$changes['notes'][]='Falha ao converter engine de '+$t;} } }
  // 1) Tabela pivot empresas_polos
  if(tExists($db,'empresas')&&tExists($db,'polos_desenvolvimento')){
    if(!tExists($db,'empresas_polos')){ $db->update("CREATE TABLE empresas_polos (empresa_id INT NOT NULL, polo_id INT NOT NULL, data_instalacao DATE NULL, status ENUM('ativa','inativa','em_implementacao','em_expansao') NULL, PRIMARY KEY(empresa_id,polo_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$changes['created_tables'][]='empresas_polos'; }
    // FKs
    if(getEngine($db,'empresas_polos')==='InnoDB'){
      if(!fkExists($db,'empresas_polos','fk_ep_empresa')){ try{$db->update("ALTER TABLE empresas_polos ADD CONSTRAINT fk_ep_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE");$changes['added_fks'][]='empresas_polos.fk_ep_empresa';}catch(Exception $e){$changes['notes'][]='FK ep_empresa não adicionada.';} }
      if(!fkExists($db,'empresas_polos','fk_ep_polo')){ try{$db->update("ALTER TABLE empresas_polos ADD CONSTRAINT fk_ep_polo FOREIGN KEY (polo_id) REFERENCES polos_desenvolvimento(id) ON DELETE CASCADE");$changes['added_fks'][]='empresas_polos.fk_ep_polo';}catch(Exception $e){$changes['notes'][]='FK ep_polo não adicionada.';} }
    }
    // Popular pivot com dados existentes
    try{$db->update("INSERT IGNORE INTO empresas_polos (empresa_id, polo_id, data_instalacao, status) SELECT id, polo_id, data_instalacao, status FROM empresas WHERE polo_id IS NOT NULL");$changes['updates'][]='empresas_polos populated from empresas';}catch(Exception $e){$changes['notes'][]='Populate pivot falhou: '.$e->getMessage();}
  }
  // 2) Trocar triggers de total_empresas para pivot
  foreach(['after_empresa_insert','after_empresa_delete'] as $tr){ if(trigExists($db,$tr)){ try{$db->update("DROP TRIGGER {$tr}");$changes['dropped_triggers'][]=$tr;}catch(Exception $e){$changes['notes'][]='Não foi possível dropar '+$tr;} } }
  if(!trigExists($db,'after_empresas_polos_insert')){ try{$db->update("CREATE TRIGGER after_empresas_polos_insert AFTER INSERT ON empresas_polos FOR EACH ROW BEGIN UPDATE polos_desenvolvimento SET total_empresas = total_empresas + 1 WHERE id = NEW.polo_id; END");$changes['added_triggers'][]='after_empresas_polos_insert';}catch(Exception $e){$changes['notes'][]='Trigger insert empresas_polos não criada.';} }
  if(!trigExists($db,'after_empresas_polos_delete')){ try{$db->update("CREATE TRIGGER after_empresas_polos_delete AFTER DELETE ON empresas_polos FOR EACH ROW BEGIN UPDATE polos_desenvolvimento SET total_empresas = total_empresas - 1 WHERE id = OLD.polo_id; END");$changes['added_triggers'][]='after_empresas_polos_delete';}catch(Exception $e){$changes['notes'][]='Trigger delete empresas_polos não criada.';} }
  if(!trigExists($db,'after_empresa_insert_sync_pivot')){ try{$db->update("CREATE TRIGGER after_empresa_insert_sync_pivot AFTER INSERT ON empresas FOR EACH ROW BEGIN IF NEW.polo_id IS NOT NULL THEN INSERT IGNORE INTO empresas_polos (empresa_id,polo_id,data_instalacao,status) VALUES (NEW.id, NEW.polo_id, NEW.data_instalacao, NEW.status); END IF; END");$changes['added_triggers'][]='after_empresa_insert_sync_pivot';}catch(Exception $e){$changes['notes'][]='Trigger sync pivot não criada.';} }
  // Recalcular totais após migração
  try{$db->update("UPDATE polos_desenvolvimento pd SET total_empresas = (SELECT COUNT(*) FROM empresas_polos WHERE polo_id = pd.id)");$changes['updates'][]='Recompute total_empresas via pivot';}catch(Exception $e){$changes['notes'][]='Falha recompute total_empresas: '.$e->getMessage();}
  // 3) Unicidade: um lote por infraestrutura (pivot com UNIQUE(lote_id))
  if(tExists($db,'infraestruturas_lotes')){ try{$db->update("ALTER TABLE infraestruturas_lotes ADD CONSTRAINT uniq_lote_single_infra UNIQUE KEY (lote_id)");$changes['updates'][]='Unique(lote_id) em infraestruturas_lotes';}catch(Exception $e){$changes['notes'][]='Unique(lote_id) já existia ou falhou.';} }
  echo json_encode(['ok'=>true,'message'=>'Migração aplicada (pivot empresas_polos, triggers e unicidade em infra_lotes).','changes'=>$changes],JSON_UNESCAPED_UNICODE);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE); }
?>