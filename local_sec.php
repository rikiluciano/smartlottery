<?php
// security.php
// Cebolla desactivada temporalmente para debug
function apply_onion_security($buffer) {
    return $buffer;
}
ob_start('apply_onion_security');
?>