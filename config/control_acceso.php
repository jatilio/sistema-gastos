<?php
function clavesIntentoFinanzas(string $ip,string $usuario): array {return [hash('sha256','ip|'.$ip),hash('sha256','usuario|'.$ip.'|'.mb_strtolower($usuario,'UTF-8'))];}
function accesoBloqueadoFinanzas(PDO $pdo,array $claves,int $ahora): bool {
 $q=$pdo->prepare('SELECT clave,fallos,inicio FROM intentos_acceso WHERE clave IN (?,?)');$q->execute($claves);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $fila){$limite=$fila['clave']===$claves[0]?20:5;if($ahora-(int)$fila['inicio']<900&&(int)$fila['fallos']>=$limite)return true;}return false;
}
function registrarIntentoFallidoFinanzas(PDO $pdo,array $claves,int $ahora): void {
 $q=$pdo->prepare('INSERT INTO intentos_acceso(clave,fallos,inicio) VALUES(?,1,?) ON DUPLICATE KEY UPDATE fallos=IF(inicio<=?,1,fallos+1),inicio=IF(inicio<=?,VALUES(inicio),inicio)');
 foreach($claves as $clave)$q->execute([$clave,$ahora,$ahora-900,$ahora-900]);
}
