<?php
function listarEstrutura($diretorio = '.', $nivel = 0) {
    // Ignora arquivos e pastas específicos
    $ignorar = array('.', '..', '.git', '.env', 'vendor', 'node_modules');
    
    // Lista todos os arquivos e diretórios
    $arquivos = scandir($diretorio);
    
    foreach($arquivos as $arquivo) {
        if(in_array($arquivo, $ignorar)) continue;
        
        $caminho = $diretorio . '/' . $arquivo;
        
        // Indentação baseada no nível
        echo str_repeat('    ', $nivel);
        
        if(is_dir($caminho)) {
            echo "📁 " . $arquivo . "\n";
            listarEstrutura($caminho, $nivel + 1);
        } else {
            echo "📄 " . $arquivo . "\n";
        }
    }
}

echo "Estrutura do Site:\n";
echo "=================\n\n";
listarEstrutura('.');
?>