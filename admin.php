<?php
// admin.php - Editor Visual ITR Bot para Bitrix24
if (!isset($_GET['key']) || $_GET['key'] !== 'bitrix24-admin-2025') die('❌ Acesso negado');

$menusFile = __DIR__.'/menus.json';
if ($_POST['save']) {
    file_put_contents($menusFile, json_encode($_POST['menus'], JSON_PRETTY_PRINT));
    echo '<script>alert("✅ Menus salvos!"); window.location.reload();</script>';
}

$menus = json_decode(file_get_contents($menusFile) ?: '[]', true);
if (empty($menus)) {
    $menus = [
        0 => ['text' => '🍽️ Menu Principal', 'items' => [
            ['id'=>1, 'title'=>'📝 Enviar Mensagem', 'type'=>'text', 'text'=>'Olá #USER_NAME#! Como posso ajudar?'],
            ['id'=>2, 'title'=>'👨‍💼 Chamar Operador', 'type'=>'queue', 'text'=>'Transferindo para atendente...'],
            ['id'=>0, 'title'=>'❌ Encerrar Chat', 'type'=>'finish']
        ]],
        1 => ['text' => '⚙️ Menu Avançado', 'items' => []]
    ];
    file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>🛠️ ITR Bot Editor - Bitrix24</title>
    <meta charset="UTF-8">
    <style>
        *{font-family:Segoe UI,sans-serif;box-sizing:border-box;}
        body{max-width:900px;margin:20px auto;padding:20px;background:#f5f5f5;}
        .header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;border-radius:10px;text-align:center;}
        .menu{border:2px solid #ddd;background:white;border-radius:8px;margin:15px 0;padding:15px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
        .menu h3{margin:0 0 15px;color:#333;background:#f8f9fa;padding:10px;border-radius:5px;}
        .item{background:#e8f4f8;margin:8px 0;padding:12px;border-radius:6px;display:flex;align-items:center;gap:10px;border-left:4px solid #4CAF50;}
        .item select,.item input{flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;}
        .btn-save{background:#4CAF50;color:white;border:none;padding:15px 30px;font-size:18px;border-radius:8px;cursor:pointer;width:100%;margin:20px 0;}
        .btn-add{background:#2196F3;color:white;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;}
        .btn-del{background:#f44336;color:white;border:none;padding:8px 12px;border-radius:50%;cursor:pointer;font-size:14px;}
        .code{background:#2d3748;color:#a0aec0;padding:15px;border-radius:5px;font-family:monospace;font-size:13px;overflow:auto;white-space:pre-wrap;}
        footer{text-align:center;padding:20px;color:#666;}
    </style>
</head>
<body>
    <div class="header">
        <h1>🤖 ITR Bot Editor Visual</h1>
        <p>Configure menus do Bitrix24 sem programar</p>
    </div>

    <form method="POST">
        <input type="hidden" name="save" value="1">
        
        <?php foreach($menus as $id => $menu): ?>
        <div class="menu">
            <h3>🍕 Menu #<?=$id?> 
                <input name="menus[<?=$id?>][text]" value="<?=htmlspecialchars($menu['text'])?>" style="width:300px;float:right;">
            </h3>
            
            <?php foreach($menu['items'] as $i => $item): ?>
            <div class="item">
                <strong>#<?=$item['id']?></strong> 
                <input name="menus[<?=$id?>][items][<?=$i?>][title]" value="<?=htmlspecialchars($item['title'])?>">
                
                <select name="menus[<?=$id?>][items][<?=$i?>][type]">
                    <option <?=($item['type']=='text'?'selected':'')?> value="text">📝 Texto</option>
                    <option <?=($item['type']=='queue'?'selected':'')?> value="queue">👥 Fila Operador</option>
                    <option <?=($item['type']=='finish'?'selected':'')?> value="finish">✅ Encerrar</option>
                    <option <?=($item['type']=='user'?'selected':'')?> value="user">👤 Usuário Específico</option>
                </select>
                
                <input name="menus[<?=$id?>][items][<?=$i?>][text]" placeholder="Mensagem opcional..." value="<?=htmlspecialchars($item['text']??'')?>">
                <button type="button" class="btn-del" onclick="this.parentElement.remove()" title="Remover">×</button>
            </div>
            <?php endforeach; ?>
            
            <button type="button" class="btn-add" onclick="addItem(<?=$id?>)">+ Adicionar Item</button>
        </div>
        <?php endforeach; ?>

        <button class="btn-save">💾 SALVAR CONFIGURAÇÕES</button>
    </form>

    <div class="code">
📋 Código para colar no itr.php (função itrRun):
function itrRun($portalId, $dialogId, $userId, $message = '') {
    $menus = json_decode(file_get_contents(__DIR__.'/menus.json'), true) ?: [];
    if ($userId <= 0) return false;
    
    $itr = new Itr($portalId, $dialogId, 0, $userId);
    foreach($menus as $menuId => $menuData) {
        $menu = new ItrMenu($menuId);
        $menu->setText($menuData['text']);
        foreach($menuData['items'] as $item) {
            switch($item['type']) {
                case 'text': $menu->addItem($item['id'], $item['title'], ItrItem::sendText($item['text'])); break;
                case 'queue': $menu->addItem($item['id'], $item['title'], ItrItem::transferToQueue()); break;
                case 'finish': $menu->addItem($item['id'], $item['title'], ItrItem::finishSession()); break;
            }
        }
        $itr->addMenu($menu);
    }
    $itr->run(prepareText($message));
    return true;
}
    </div>

    <script>
    function addItem(menuId) {
        const html = `<div class="item">
            <strong>#${Math.floor(Math.random()*900)+100}</strong>
            <input name="menus[${menuId}][items][${Date.now()}][title]" placeholder="Título do botão">
            <select name="menus[${menuId}][items][${Date.now()}][type]">
                <option value="text">📝 Texto</option>
                <option value="queue">👥 Fila Operador</option>
                <option value="finish">✅ Encerrar</option>
            </select>
            <input name="menus[${menuId}][items][${Date.now()}][text]" placeholder="Mensagem...">
            <button type="button" class="btn-del" onclick="this.parentElement.remove()">×</button>
        </div>`;
        document.querySelector(`[onclick="addItem(${menuId})"]`).insertAdjacentHTML('beforebegin', html);
    }
    </script>

    <footer>
        🔑 Acesse: /admin.php?key=bitrix24-admin-2025 | 📱 Responsivo | 🚀 Coolify Ready
    </footer>
</body>
</html>
