<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../database.php';
if(defined('ENVIRONMENT')&&ENVIRONMENT==='production'){http_response_code(404);echo json_encode(['error'=>'Endpoint não disponível em produção'],JSON_UNESCAPED_UNICODE);exit;}
header('Content-Type: application/json; charset=utf-8');
function tExists($db,$t){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?",[$t]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function cExists($db,$t,$c){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?",[$t,$c]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function iExists($db,$t,$i){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?",[$t,$i]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function fkExists($db,$t,$n){$r=$db->selectOne("SELECT COUNT(*) cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=? AND CONSTRAINT_TYPE='FOREIGN KEY'",[$t,$n]);return isset($r['cnt'])&&((int)$r['cnt']>0);} 
function getEngine($db,$t){$r=$db->selectOne("SELECT ENGINE eng FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?",[$t]);return isset($r['eng'])?$r['eng']:null;}
try{ $db=new Database();$changes=['added_columns'=>[],'created_tables'=>[],'added_fks'=>[],'added_indexes'=>[],'notes'=>[]];
  // empresa_id em infraestruturas
  if(tExists($db,'infraestruturas')&&tExists($db,'empresas')){
    if(!cExists($db,'infraestruturas','empresa_id')){ $db->update("ALTER TABLE infraestruturas ADD COLUMN empresa_id INT NULL" ); $changes['added_columns'][]='infraestruturas.empresa_id'; }
    if(!iExists($db,'infraestruturas','idx_infraestruturas_empresa')){ $db->update("CREATE INDEX idx_infraestruturas_empresa ON infraestruturas (empresa_id)" ); $changes['added_indexes'][]='infraestruturas.idx_infraestruturas_empresa'; }
    $engInfra=getEngine($db,'infraestruturas');$engEmp=getEngine($db,'empresas');
    if($engInfra==='InnoDB'&&$engEmp==='InnoDB'&&!fkExists($db,'infraestruturas','fk_infra_empresa')){
      try{$db->update("ALTER TABLE infraestruturas ADD CONSTRAINT fk_infra_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL");$changes['added_fks'][]='infraestruturas.fk_infra_empresa';}catch(Exception $e){$changes['notes'][]='FK empresa_id não adicionada: '+$e->getMessage();}
    }else{ $changes['notes'][]='Sem FK empresa_id (engines não InnoDB ou já existente).'; }
  }
  // tabela pivot infraestruturas_lotes
  if(tExists($db,'infraestruturas')&&tExists($db,'lotes')){
    if(!tExists($db,'infraestruturas_lotes')){ $db->update("CREATE TABLE infraestruturas_lotes (infraestrutura_id INT NOT NULL, lote_id INT NOT NULL, PRIMARY KEY(infraestrutura_id,lote_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); $changes['created_tables'][]='infraestruturas_lotes'; }
    $engInfra=getEngine($db,'infraestruturas');$engLotes=getEngine($db,'lotes');
    if($engInfra==='InnoDB'&&$engLotes==='InnoDB'){
      if(!fkExists($db,'infraestruturas_lotes','fk_il_infra')){ try{$db->update("ALTER TABLE infraestruturas_lotes ADD CONSTRAINT fk_il_infra FOREIGN KEY (infraestrutura_id) REFERENCES infraestruturas(id) ON DELETE CASCADE");$changes['added_fks'][]='infraestruturas_lotes.fk_il_infra';}catch(Exception $e){$changes['notes'][]='FK il_infra não adicionada.';} }
      if(!fkExists($db,'infraestruturas_lotes','fk_il_lote')){ try{$db->update("ALTER TABLE infraestruturas_lotes ADD CONSTRAINT fk_il_lote FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE CASCADE");$changes['added_fks'][]='infraestruturas_lotes.fk_il_lote';}catch(Exception $e){$changes['notes'][]='FK il_lote não adicionada.';} }
    }else{ $changes['notes'][]='Sem FKs na pivot (engines não InnoDB).'; }
  }
  echo json_encode(['ok'=>true,'message'=>'Migração aplicada (apenas alterações em falta).','changes'=>$changes],JSON_UNESCAPED_UNICODE);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE); }
?>